<?php
/**
 * Digikala API Client with transient caching.
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PG_API {

	const CACHE_PREFIX = 'pg_p_';
	const DEFAULT_TTL  = 21600; // 6 hours in seconds

	/**
	 * Fetch product details from Digikala API with transient caching.
	 *
	 * @param string $product_id Digikala product ID.
	 * @param string $api_type API type: 'normal' or 'supermarket'.
	 * @param bool   $force_refresh Whether to bypass cache.
	 * @return array|false Product details or false on failure.
	 */
	public static function get_product( $product_id, $api_type = 'normal', $force_refresh = false ) {
		$product_id = sanitize_text_field( $product_id );
		if ( empty( $product_id ) ) {
			return false;
		}

		$cache_key = self::get_cache_key( $product_id, $api_type );

		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_array( $cached ) ) {
				return $cached;
			}
		}

		// Determine API Endpoint
		if ( 'supermarket' === $api_type ) {
			$url = "https://api.digikala.com/fresh/v1/product/{$product_id}/";
		} else {
			$url = "https://api.digikala.com/v2/product/{$product_id}/";
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'Mozilla/5.0 (WordPress; Product Groups Plugin)',
				'headers'    => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Negative cache for 3 minutes to avoid hammering failing API
			set_transient( $cache_key, array( 'error' => true ), 180 );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['data']['product'] ) ) {
			set_transient( $cache_key, array( 'error' => true ), 180 );
			return false;
		}

		$raw = $data['data']['product'];

		$image_url = '';
		if ( ! empty( $raw['images']['main']['url'] ) ) {
			$image_url = is_array( $raw['images']['main']['url'] )
				? ( $raw['images']['main']['url'][0] ?? '' )
				: (string) $raw['images']['main']['url'];
		}

		$parsed = array(
			'id'      => $product_id,
			'title'   => sanitize_text_field( $raw['title_fa'] ?? ( $raw['title_en'] ?? '' ) ),
			'image'   => esc_url_raw( $image_url ),
			'price'   => isset( $raw['default_variant']['price']['selling_price'] ) ? (int) $raw['default_variant']['price']['selling_price'] : 0,
			'rrp'     => isset( $raw['default_variant']['price']['rrp_price'] ) ? (int) $raw['default_variant']['price']['rrp_price'] : 0,
			'status'  => sanitize_text_field( $raw['status'] ?? 'marketable' ),
			'time'    => time(),
		);

		$ttl = apply_filters( 'product_groups_api_cache_ttl', self::DEFAULT_TTL, $product_id, $api_type );
		set_transient( $cache_key, $parsed, $ttl );

		return $parsed;
	}

	/**
	 * Build cache key.
	 *
	 * @param string $product_id
	 * @param string $api_type
	 * @return string
	 */
	public static function get_cache_key( $product_id, $api_type ) {
		// Transient key max length is 172 characters.
		$type_prefix = ( 'supermarket' === $api_type ) ? 's_' : 'n_';
		return self::CACHE_PREFIX . $type_prefix . md5( $product_id );
	}

	/**
	 * Delete cached data for a specific product.
	 *
	 * @param string $product_id
	 * @param string $api_type
	 * @return bool
	 */
	public static function delete_product_cache( $product_id, $api_type = 'normal' ) {
		return delete_transient( self::get_cache_key( $product_id, $api_type ) );
	}

	/**
	 * Invalidate all cached products for a given post.
	 *
	 * @param int $post_id
	 */
	public static function delete_group_cache( $post_id ) {
		$products = get_post_meta( $post_id, '_pg_products', true );
		$api_type = get_post_meta( $post_id, '_pg_api_type', true ) ?: 'normal';

		if ( is_array( $products ) ) {
			foreach ( $products as $product ) {
				$product_link = $product['product_link'] ?? '';
				$product_id   = PG_Utils::extract_product_id( $product_link );
				if ( $product_id ) {
					self::delete_product_cache( $product_id, $api_type );
				}
			}
		}
	}
}
