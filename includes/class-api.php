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
	const STALE_CACHE_PREFIX = 'pg_p_stale_';
	const DEFAULT_TTL  = 21600; // 6 hours in seconds
	const STALE_CACHE_TTL = WEEK_IN_SECONDS;

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
			if ( false !== $cached && is_array( $cached ) && empty( $cached['error'] ) ) {
				return $cached;
			}
		}

		$numeric_id = ( 'snappshop' === $api_type ) ? preg_replace( '/\D/', '', $product_id ) : $product_id;
		if ( empty( $numeric_id ) ) {
			self::log_error( $api_type, $product_id, null, 'Invalid or empty product ID' );
			return false;
		}

		$headers = array(
			'Accept'  => 'application/json',
			'Referer' => 'snappshop' === $api_type ? 'https://snappshop.ir/' : 'https://www.digikala.com/',
		);

		// Determine API Endpoint
		if ( 'snappshop' === $api_type ) {
			$proxy = PG_Settings::get_snappshop_proxy_config();
			if ( $proxy['enabled'] && ! empty( $proxy['url'] ) ) {
				$url = $proxy['url'] . '/products/' . $numeric_id;
				if ( ! empty( $proxy['key'] ) ) {
					$headers['X-Product-Groups-Key'] = $proxy['key'];
				}
			} else {
				$url = "https://apix.snappshop.ir/products/v2/{$numeric_id}";
			}
		} elseif ( 'supermarket' === $api_type ) {
			$url = "https://api.digikala.com/fresh/v1/product/{$numeric_id}/";
		} else {
			$url = "https://api.digikala.com/v2/product/{$numeric_id}/";
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'headers'    => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			self::log_error( $api_type, $product_id, null, $response->get_error_message() );
			return self::get_stale_product( $product_id, $api_type );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$err_msg = wp_remote_retrieve_response_message( $response );
			$body    = wp_remote_retrieve_body( $response );
			if ( ! empty( $body ) ) {
				$err_json = json_decode( $body, true );
				if ( is_array( $err_json ) ) {
					if ( ! empty( $err_json['message'] ) && is_string( $err_json['message'] ) ) {
						$err_msg = $err_json['message'];
					} elseif ( ! empty( $err_json['error'] ) && is_string( $err_json['error'] ) ) {
						$err_msg = $err_json['error'];
					}
				}
			}
			self::log_error( $api_type, $product_id, $code, $err_msg ?: 'HTTP request returned non-200 status' );
			return self::get_stale_product( $product_id, $api_type );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			self::log_error( $api_type, $product_id, $code, 'Failed to parse JSON response body' );
			return self::get_stale_product( $product_id, $api_type );
		}

		if ( isset( $data['status'] ) && false === $data['status'] ) {
			$err_msg = ! empty( $data['message'] ) && is_string( $data['message'] ) ? $data['message'] : 'API returned status false';
			self::log_error( $api_type, $product_id, $code, $err_msg );
			return self::get_stale_product( $product_id, $api_type );
		}

		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			$err_msg = ! empty( $data['message'] ) && is_string( $data['message'] ) ? $data['message'] : 'Empty or invalid data field in API response';
			self::log_error( $api_type, $product_id, $code, $err_msg );
			return self::get_stale_product( $product_id, $api_type );
		}

		if ( 'snappshop' === $api_type ) {
			$raw_data = $data['data'];
			$content  = $raw_data['content'] ?? array();
			$title    = $content['title_fa'] ?? ( $raw_data['page']['title'] ?? ( $content['title_en'] ?? '' ) );

			if ( empty( $title ) ) {
				self::log_error( $api_type, $product_id, $code, 'Could not determine product title from Snapp Shop response' );
				return self::get_stale_product( $product_id, $api_type );
			}

			// Extract image URL
			$image_url = '';
			if ( ! empty( $raw_data['images'][0]['src'] ) ) {
				$image_url = $raw_data['images'][0]['src'];
			} elseif ( ! empty( $raw_data['page']['extra_meta'] ) ) {
				foreach ( $raw_data['page']['extra_meta'] as $meta ) {
					if ( 'og:image' === ( $meta['property'] ?? '' ) && ! empty( $meta['content'] ) ) {
						$image_url = $meta['content'];
						break;
					}
				}
			}

			// Extract price and stock from default_variant or first vendor
			$default_var   = $raw_data['default_variant'] ?? array();
			$target_var_id = $default_var['variation_id'] ?? '';
			$variants      = $raw_data['variants'] ?? array();

			$selected_vendor = null;
			foreach ( $variants as $variant ) {
				if ( ! empty( $variant['vendor'][0] ) ) {
					if ( $target_var_id && ( $variant['variation_id'] ?? '' ) === $target_var_id ) {
						$selected_vendor = $variant['vendor'][0];
						break;
					}
					if ( null === $selected_vendor ) {
						$selected_vendor = $variant['vendor'][0];
					}
				}
			}

			$selling_price = 0;
			$rrp_price     = 0;
			$stock         = 1;

			if ( $selected_vendor ) {
				$price_val   = isset( $selected_vendor['price'] ) ? (int) $selected_vendor['price'] : 0;
				$special_val = isset( $selected_vendor['special_price'] ) ? (int) $selected_vendor['special_price'] : 0;
				$stock       = isset( $selected_vendor['stock'] ) ? (int) $selected_vendor['stock'] : 0;

				if ( $special_val > 0 && $special_val < $price_val ) {
					$selling_price = $special_val;
					$rrp_price     = $price_val;
				} else {
					$selling_price = $price_val;
					$rrp_price     = $price_val;
				}
			}

			// Fallback: extract price from page.extra_meta if needed
			if ( 0 === $selling_price && ! empty( $raw_data['page']['extra_meta'] ) ) {
				foreach ( $raw_data['page']['extra_meta'] as $meta ) {
					if ( 'product:price:amount' === ( $meta['property'] ?? '' ) ) {
						$selling_price = (int) $meta['content'];
						$rrp_price     = $selling_price;
						break;
					}
				}
			}

			$status = ( $stock <= 0 ) ? 'out_of_stock' : 'marketable';

			$parsed = array(
				'id'      => $product_id,
				'title'   => sanitize_text_field( $title ),
				'image'   => esc_url_raw( $image_url ),
				'price'   => $selling_price,
				'rrp'     => $rrp_price,
				'status'  => $status,
				'source'  => 'snappshop',
				'time'    => time(),
			);
		} else {
			// Digikala (Normal or Supermarket)
			$raw = $data['data']['product'] ?? array();
			if ( empty( $raw ) || ! is_array( $raw ) ) {
				self::log_error( $api_type, $product_id, $code, 'Empty product field in Digikala response' );
				return self::get_stale_product( $product_id, $api_type );
			}

			$image_url = '';
			if ( ! empty( $raw['images']['main']['url'] ) ) {
				$image_url = is_array( $raw['images']['main']['url'] )
					? ( $raw['images']['main']['url'][0] ?? '' )
					: (string) $raw['images']['main']['url'];
			}

			$variant       = $raw['default_variant'] ?? array();
			$selling_price = isset( $variant['price']['selling_price'] ) ? (int) $variant['price']['selling_price'] : ( isset( $raw['price']['selling_price'] ) ? (int) $raw['price']['selling_price'] : 0 );
			$rrp_price     = isset( $variant['price']['rrp_price'] ) ? (int) $variant['price']['rrp_price'] : ( isset( $raw['price']['rrp_price'] ) ? (int) $raw['price']['rrp_price'] : $selling_price );
			$status        = sanitize_text_field( $raw['status'] ?? ( $variant['status'] ?? 'marketable' ) );

			$parsed = array(
				'id'      => $product_id,
				'title'   => sanitize_text_field( $raw['title_fa'] ?? ( $raw['title_en'] ?? '' ) ),
				'image'   => esc_url_raw( $image_url ),
				'price'   => $selling_price,
				'rrp'     => $rrp_price,
				'status'  => $status,
				'source'  => 'digikala',
				'time'    => time(),
			);
		}

		$hours       = absint( get_option( 'pg_cache_ttl', 6 ) );
		$default_ttl = ( $hours > 0 ? $hours : 6 ) * HOUR_IN_SECONDS;
		$ttl         = apply_filters( 'product_groups_api_cache_ttl', $default_ttl, $product_id, $api_type );
		set_transient( $cache_key, $parsed, $ttl );
		set_transient( self::get_stale_cache_key( $product_id, $api_type ), $parsed, self::STALE_CACHE_TTL );

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
		$type_prefix = 'n_';
		if ( 'supermarket' === $api_type ) {
			$type_prefix = 's_';
		} elseif ( 'snappshop' === $api_type ) {
			$type_prefix = 'snp_';
		}
		return self::CACHE_PREFIX . $type_prefix . md5( $product_id );
	}

	/**
	 * Build the longer-lived fallback cache key for a product.
	 *
	 * @param string $product_id Product ID.
	 * @param string $api_type API type.
	 * @return string
	 */
	private static function get_stale_cache_key( $product_id, $api_type ) {
		return self::STALE_CACHE_PREFIX . md5( $api_type . ':' . $product_id );
	}

	/**
	 * Return the last successful response during a temporary upstream failure.
	 *
	 * @param string $product_id Product ID.
	 * @param string $api_type API type.
	 * @return array|false
	 */
	private static function get_stale_product( $product_id, $api_type ) {
		$stale = get_transient( self::get_stale_cache_key( $product_id, $api_type ) );
		if ( ! is_array( $stale ) || empty( $stale['title'] ) ) {
			return false;
		}

		$stale['is_stale'] = true;
		return $stale;
	}

	/**
	 * Delete cached data for a specific product.
	 *
	 * @param string $product_id
	 * @param string $api_type
	 * @return bool
	 */
	public static function delete_product_cache( $product_id, $api_type = 'normal' ) {
		$deleted_fresh = delete_transient( self::get_cache_key( $product_id, $api_type ) );
		$deleted_stale = delete_transient( self::get_stale_cache_key( $product_id, $api_type ) );
		return $deleted_fresh || $deleted_stale;
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

	/**
	 * Log API request failures safely without sensitive data.
	 *
	 * @param string   $api_type     API type.
	 * @param string   $product_id   Product ID.
	 * @param int|null $status_code  HTTP response code if available.
	 * @param string   $error_message Sanitized short error message.
	 */
	private static function log_error( $api_type, $product_id, $status_code, $error_message ) {
		$code_str = $status_code ? (string) $status_code : 'N/A';
		$message  = sanitize_text_field( substr( trim( (string) $error_message ), 0, 200 ) );

		error_log(
			sprintf(
				'[Product Groups] API fetch failed | Type: %s | Product ID: %s | HTTP Status: %s | Error: %s',
				$api_type,
				$product_id,
				$code_str,
				$message
			)
		);
	}
}
