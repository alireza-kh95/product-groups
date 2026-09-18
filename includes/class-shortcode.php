<?php
/**
 * Shortcode Handler [product_group].
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PG_Shortcode {

	const SHORTCODE_TAG = 'product_group';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_shortcode( self::SHORTCODE_TAG, array( __CLASS__, 'render' ) );
	}

	/**
	 * Render shortcode output.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			self::SHORTCODE_TAG
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		$products = get_post_meta( $post_id, '_pg_products', true );
		if ( empty( $products ) || ! is_array( $products ) ) {
			return '<p class="pg-no-products">' . esc_html__( 'محصولی یافت نشد.', 'product-groups' ) . '</p>';
		}

		$api_type = get_post_meta( $post_id, '_pg_api_type', true );
		if ( empty( $api_type ) ) {
			$api_type = 'normal';
		}

		// Enqueue frontend styles when shortcode is rendered
		wp_enqueue_style( 'pg-frontend-styles' );

		$items = array();

		foreach ( $products as $product ) {
			$product_link   = $product['product_link'] ?? '';
			$affiliate_link = $product['affiliate_link'] ?? '';

			$product_id = PG_Utils::extract_product_id( $product_link );
			if ( ! $product_id ) {
				continue;
			}

			$data = PG_API::get_product( $product_id, $api_type );
			if ( ! $data || ! empty( $data['error'] ) ) {
				continue;
			}

			$pricing = PG_Utils::format_pricing( $data['price'], $data['rrp'], $data['status'] );

			$items[] = array(
				'id'             => $product_id,
				'title'          => $data['title'],
				'image'          => $data['image'],
				'pricing'        => $pricing,
				'affiliate_link' => $affiliate_link,
				'source'         => $data['source'] ?? ( 'snappshop' === $api_type ? 'snappshop' : 'digikala' ),
			);
		}

		if ( empty( $items ) ) {
			return '<p class="pg-no-products">' . esc_html__( 'محصولی یافت نشد.', 'product-groups' ) . '</p>';
		}

		ob_start();
		$template_path = PRODUCT_GROUPS_DIR . 'templates/product-grid.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
		return ob_get_clean();
	}
}
