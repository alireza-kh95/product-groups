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
			__( 'مدیریت محصولات این گروه', 'product-groups' ),
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
		<div class="pg-meta-container">
			<!-- Visual API Type Selector -->
			<div class="pg-api-selector-wrap">
				<div class="pg-section-heading">
					<label class="pg-section-title"><?php esc_html_e( 'انتخاب نوع API دیجیکالا', 'product-groups' ); ?></label>
					<span class="pg-section-badge"><?php esc_html_e( 'ضروری', 'product-groups' ); ?></span>
				</div>

				<div class="pg-api-options">
					<label class="pg-api-card <?php echo 'normal' === $api_type ? 'is-active' : ''; ?>">
						<input type="radio" name="pg_api_type" value="normal" <?php checked( $api_type, 'normal' ); ?> class="pg-api-radio" />
						<div class="pg-api-card-content">
							<div class="pg-api-card-icon pg-icon-store">
								<span class="dashicons dashicons-store"></span>
							</div>
							<div class="pg-api-card-text">
								<strong><?php esc_html_e( 'کالاهای معمولی (Normal API)', 'product-groups' ); ?></strong>
								<span><?php esc_html_e( 'لوازم دیجیتال، مد، خانه، ابزار و کلیه کالاهای عمومی دیجیکالا', 'product-groups' ); ?></span>
							</div>
						</div>
						<div class="pg-api-card-check">
							<span class="dashicons dashicons-yes"></span>
						</div>
					</label>

					<label class="pg-api-card <?php echo 'supermarket' === $api_type ? 'is-active' : ''; ?>">
						<input type="radio" name="pg_api_type" value="supermarket" <?php checked( $api_type, 'supermarket' ); ?> class="pg-api-radio" />
						<div class="pg-api-card-content">
							<div class="pg-api-card-icon pg-icon-fresh">
								<span class="dashicons dashicons-cart"></span>
							</div>
							<div class="pg-api-card-text">
								<strong><?php esc_html_e( 'سوپرمارکت و تندمصرف (Fresh / Jet)', 'product-groups' ); ?></strong>
								<span><?php esc_html_e( 'محصولات سوپرمارکتی و مصرفی دارای برچسب ارسال سریع', 'product-groups' ); ?></span>
							</div>
						</div>
						<div class="pg-api-card-check">
							<span class="dashicons dashicons-yes"></span>
						</div>
					</label>
				</div>
			</div>

			<!-- Product Items Repeater -->
			<div id="pg-wrapper">
				<div class="pg-actions-bar">
					<div class="pg-actions-left">
						<span class="pg-items-count">
							<?php esc_html_e( 'تعداد کل محصولات:', 'product-groups' ); ?>
							<strong id="pg-count-number"><?php echo esc_html( PG_Utils::to_persian_number( count( $products ) ) ); ?></strong>
						</span>
					</div>

					<div class="pg-actions-right">
						<button type="button" class="button button-primary pg-btn-add" id="pg-add-item">
							<span class="dashicons dashicons-plus-alt2"></span>
							<span><?php esc_html_e( 'افزودن محصول جدید', 'product-groups' ); ?></span>
						</button>
					</div>
				</div>

				<div id="pg-empty-notice" class="pg-empty-state" style="<?php echo ! empty( $products ) ? 'display:none;' : ''; ?>">
					<div class="pg-empty-icon">
						<span class="dashicons dashicons-products"></span>
					</div>
					<h3><?php esc_html_e( 'هنوز محصولی به این گروه اضافه نشده است', 'product-groups' ); ?></h3>
					<p><?php esc_html_e( 'برای شروع، روی دکمه «افزودن محصول جدید» کلیک کنید و لینک‌های دیجیکالا را وارد نمایید.', 'product-groups' ); ?></p>
					<button type="button" class="button button-secondary pg-btn-add-empty">
						<?php esc_html_e( 'افزودن اولین محصول', 'product-groups' ); ?>
					</button>
				</div>

				<div id="pg-items">
					<?php
					if ( ! empty( $products ) ) :
						foreach ( $products as $index => $product ) :
							$product_link   = esc_url( $product['product_link'] ?? '' );
							$affiliate_link = esc_url( $product['affiliate_link'] ?? '' );
							$product_id     = PG_Utils::extract_product_id( $product_link );
							?>
							<div class="pg-item" data-index="<?php echo esc_attr( $index ); ?>">
								<div class="pg-item-header">
									<div class="pg-item-badge">
										<span class="pg-item-index"><?php echo esc_html( $index + 1 ); ?></span>
										<span class="pg-item-title-label"><?php esc_html_e( 'محصول', 'product-groups' ); ?></span>
									</div>

									<div class="pg-item-tools">
										<span class="pg-detected-id" <?php echo $product_id ? '' : 'style="display:none;"'; ?>>
											<?php
											if ( $product_id ) {
												echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html( 'کد کالا: ' . PG_Utils::to_persian_number( $product_id ) );
											}
											?>
										</span>

										<div class="pg-reorder-group">
											<button type="button" class="button button-small pg-btn-move pg-btn-move-up" title="<?php esc_attr_e( 'انتقال به بالا', 'product-groups' ); ?>" <?php echo 0 === $index ? 'disabled' : ''; ?>>
												<span class="dashicons dashicons-arrow-up-alt2"></span>
											</button>
											<button type="button" class="button button-small pg-btn-move pg-btn-move-down" title="<?php esc_attr_e( 'انتقال به پایین', 'product-groups' ); ?>" <?php echo ( count( $products ) - 1 ) === $index ? 'disabled' : ''; ?>>
												<span class="dashicons dashicons-arrow-down-alt2"></span>
											</button>
										</div>

										<button type="button" class="button pg-remove-item" title="<?php esc_attr_e( 'حذف محصول', 'product-groups' ); ?>">
											<span class="dashicons dashicons-trash"></span>
											<span class="pg-btn-remove-text"><?php esc_html_e( 'حذف', 'product-groups' ); ?></span>
										</button>
									</div>
								</div>

								<div class="pg-item-body">
									<div class="pg-field-row">
										<div class="pg-field-col pg-col-product">
											<label for="pg_p_link_<?php echo esc_attr( $index ); ?>">
												<span class="dashicons dashicons-admin-links"></span>
												<?php esc_html_e( 'لینک محصول در دیجیکالا:', 'product-groups' ); ?>
												<span class="pg-required">*</span>
											</label>
											<div class="pg-input-wrapper">
												<input type="url"
													   id="pg_p_link_<?php echo esc_attr( $index ); ?>"
													   data-field="product_link"
													   name="pg_products[<?php echo esc_attr( $index ); ?>][product_link]"
													   placeholder="https://www.digikala.com/product/dkp-..."
													   value="<?php echo $product_link; ?>"
													   required />
											</div>
											<span class="pg-field-hint"><?php esc_html_e( 'شناسه dkp محصول در لینک باید وجود داشته باشد.', 'product-groups' ); ?></span>
										</div>

										<div class="pg-field-col pg-col-affiliate">
											<label for="pg_a_link_<?php echo esc_attr( $index ); ?>">
												<span class="dashicons dashicons-money-alt"></span>
												<?php esc_html_e( 'لینک افیلیت (همکاری در فروش):', 'product-groups' ); ?>
												<span class="pg-required">*</span>
											</label>
											<div class="pg-input-wrapper">
												<input type="url"
													   id="pg_a_link_<?php echo esc_attr( $index ); ?>"
													   data-field="affiliate_link"
													   name="pg_products[<?php echo esc_attr( $index ); ?>][affiliate_link]"
													   placeholder="https://affiliate.digikala.com/..."
													   value="<?php echo $affiliate_link; ?>"
													   required />
											</div>
											<span class="pg-field-hint"><?php esc_html_e( 'لینکی که کاربر پس از کلیک به آن هدایت می‌شود.', 'product-groups' ); ?></span>
										</div>
									</div>
								</div>
							</div>
							<?php
						endforeach;
					endif;
					?>
				</div>
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
