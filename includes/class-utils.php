<?php
/**
 * Utility helper functions.
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PG_Utils {

	/**
	 * Convert English digits to Persian digits.
	 *
	 * @param string|int|float $number Input number or string.
	 * @return string
	 */
	public static function to_persian_number( $number ) {
		$persian = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		$english = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );

		return str_replace( $english, $persian, (string) $number );
	}

	/**
	 * Extract Digikala product ID from URL or string.
	 * Handles URLs with dkp-12345, direct IDs, or standard product paths.
	 *
	 * @param string $url Product URL or text.
	 * @return string|null Product ID string or null if not found.
	 */
	public static function extract_product_id( $url ) {
		if ( empty( $url ) ) {
			return null;
		}

		$decoded_url = urldecode( trim( $url ) );

		// Standard Digikala dkp-XXXX format
		if ( preg_match( '/dkp-(\d+)/i', $decoded_url, $matches ) ) {
			return $matches[1];
		}

		// Product URL with numeric ID /product/12345/
		if ( preg_match( '#/product/(\d+)#i', $decoded_url, $matches ) ) {
			return $matches[1];
		}

		// Direct numeric ID
		if ( preg_match( '/^(\d+)$/', $decoded_url, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Format product pricing and return array with display data.
	 *
	 * @param int|float $price Selling price in Rials.
	 * @param int|float $rrp Recommended retail price in Rials.
	 * @param string    $status Product status ('marketable', 'out_of_stock', etc.).
	 * @return array Display information.
	 */
	public static function format_pricing( $price, $rrp, $status = 'marketable' ) {
		if ( 'out_of_stock' === $status ) {
			return array(
				'is_out_of_stock'    => true,
				'price_toman'        => 0,
				'rrp_toman'          => 0,
				'formatted_price'    => '',
				'formatted_rrp'      => '',
				'has_discount'       => false,
				'discount_percent'   => 0,
				'formatted_discount' => '',
			);
		}

		// Prices from Digikala API are in Rials; convert to Tomans (divide by 10)
		$price_toman = $price ? floor( (float) $price / 10 ) : 0;
		$rrp_toman   = $rrp ? floor( (float) $rrp / 10 ) : 0;

		// Fallback: If price_toman is 0 but rrp_toman exists, use rrp_toman
		if ( 0 === $price_toman && $rrp_toman > 0 ) {
			$price_toman = $rrp_toman;
		}

		// Fallback: If rrp_toman is 0 but price_toman exists, use price_toman
		if ( 0 === $rrp_toman && $price_toman > 0 ) {
			$rrp_toman = $price_toman;
		}

		$has_discount       = ( $price_toman > 0 && $rrp_toman > 0 && $price_toman < $rrp_toman );
		$discount_percent   = 0;
		$formatted_discount = '';

		if ( $has_discount ) {
			$discount_percent   = (int) round( ( ( $rrp_toman - $price_toman ) / $rrp_toman ) * 100 );
			$formatted_discount = self::to_persian_number( $discount_percent ) . '٪';
		}

		return array(
			'is_out_of_stock'    => false,
			'price_toman'        => $price_toman,
			'rrp_toman'          => $rrp_toman,
			'formatted_price'    => self::to_persian_number( number_format( $price_toman ) ),
			'formatted_rrp'      => self::to_persian_number( number_format( $rrp_toman ) ),
			'has_discount'       => $has_discount,
			'discount_percent'   => $discount_percent,
			'formatted_discount' => $formatted_discount,
		);
	}
}
