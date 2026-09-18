<?php
/**
 * Product Group Meta Box Handler.
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PG_Meta_Box {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . PG_Post_Type::POST_TYPE, array( __CLASS__, 'save_meta_box' ) );
	}

	/**
	 * Add meta box to 'product_group' post type.
	 */
	public static function add_meta_box() {
		add_meta_box(
			'pg_meta_box',
			__( 'محصولات این گروه', 'product-groups' ),
			array( __CLASS__, 'render' ),
			PG_Post_Type::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box HTML.
	 *
	 * @param WP_Post $post
	 */
	public static function render( $post ) {
		$products = get_post_meta( $post->ID, '_pg_products', true );
		$api_type = get_post_meta( $post->ID, '_pg_api_type', true );

		if ( empty( $api_type ) ) {
			$api_type = 'normal';
		}

		if ( ! is_array( $products ) ) {
			$products = array();
		}

		wp_nonce_field( 'pg_save_products_action', 'pg_products_nonce' );
		?>
		<div class="pg-api-selector">
			<label for="pg_api_type"><?php esc_html_e( 'نوع API:', 'product-groups' ); ?></label>
			<select name="pg_api_type" id="pg_api_type">
				<option value="normal" <?php selected( $api_type, 'normal' ); ?>><?php esc_html_e( 'معمولی (Normal)', 'product-groups' ); ?></option>
				<option value="supermarket" <?php selected( $api_type, 'supermarket' ); ?>><?php esc_html_e( 'سوپرمارکتی (Supermarket)', 'product-groups' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'نوع API مورد استفاده برای فراخوانی اطلاعات محصولات این گروه را انتخاب کنید.', 'product-groups' ); ?></p>
		</div>

		<div id="pg-wrapper">
			<div class="pg-actions-bar">
				<button type="button" class="button button-primary" id="pg-add-item">
					<span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;margin-left:2px;"></span>
					<?php esc_html_e( 'افزودن محصول جدید', 'product-groups' ); ?>
				</button>
			</div>

			<div id="pg-empty-notice" class="pg-empty-state" style="<?php echo ! empty( $products ) ? 'display:none;' : ''; ?>">
				<p><?php esc_html_e( 'هنوز محصولی به این گروه اضافه نشده است. روی «افزودن محصول جدید» کلیک کنید.', 'product-groups' ); ?></p>
			</div>

			<div id="pg-items">
				<?php
				if ( ! empty( $products ) ) :
					foreach ( $products as $index => $product ) :
						$product_link   = esc_url( $product['product_link'] ?? '' );
						$affiliate_link = esc_url( $product['affiliate_link'] ?? '' );
						?>
						<div class="pg-item">
							<div class="pg-item-index"><?php echo esc_html( $index + 1 ); ?></div>
							<div class="pg-item-fields">
								<div class="pg-item-field">
									<label><?php esc_html_e( 'لینک محصول دیجیکالا:', 'product-groups' ); ?></label>
									<input type="url"
										   data-field="product_link"
										   name="pg_products[<?php echo esc_attr( $index ); ?>][product_link]"
										   placeholder="https://www.digikala.com/product/dkp-..."
										   value="<?php echo $product_link; ?>"
										   required />
								</div>
								<div class="pg-item-field">
									<label><?php esc_html_e( 'لینک افیلیت:', 'product-groups' ); ?></label>
									<input type="url"
										   data-field="affiliate_link"
										   name="pg_products[<?php echo esc_attr( $index ); ?>][affiliate_link]"
										   placeholder="https://..."
										   value="<?php echo $affiliate_link; ?>"
										   required />
								</div>
							</div>
							<button type="button" class="button pg-remove-item"><?php esc_html_e( 'حذف', 'product-groups' ); ?></button>
						</div>
						<?php
					endforeach;
				endif;
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['pg_products_nonce'] ) || ! wp_verify_nonce( $_POST['pg_products_nonce'], 'pg_save_products_action' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save API Type
		$allowed_api_types = array( 'normal', 'supermarket' );
		$api_type          = isset( $_POST['pg_api_type'] ) && in_array( $_POST['pg_api_type'], $allowed_api_types, true )
			? sanitize_text_field( $_POST['pg_api_type'] )
			: 'normal';

		update_post_meta( $post_id, '_pg_api_type', $api_type );

		// Invalidate cache for this group so changes reflect immediately
		PG_API::delete_group_cache( $post_id );

		// Sanitize & Save Products
		$sanitized_products = array();

		if ( isset( $_POST['pg_products'] ) && is_array( $_POST['pg_products'] ) ) {
			foreach ( $_POST['pg_products'] as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$product_link   = isset( $item['product_link'] ) ? esc_url_raw( trim( $item['product_link'] ) ) : '';
				$affiliate_link = isset( $item['affiliate_link'] ) ? esc_url_raw( trim( $item['affiliate_link'] ) ) : '';

				if ( ! empty( $product_link ) ) {
					$sanitized_products[] = array(
						'product_link'   => $product_link,
						'affiliate_link' => $affiliate_link,
					);
				}
			}
		}

		update_post_meta( $post_id, '_pg_products', $sanitized_products );
	}
}
