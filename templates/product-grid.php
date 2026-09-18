<?php
/**
 * Product Grid Template.
 *
 * Fully backward-compatible with v1.0 - v1.2 CSS selectors while offering modern aesthetics.
 *
 * @package ProductGroups
 *
 * @var array $items List of prepared product items.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $items ) ) {
	return;
}
?>

<div class="pg-shortcode-list">
	<div class="pg-product-grid">
		<?php foreach ( $items as $item ) : ?>
			<div class="pg-product-item">
				<div class="pg-product-image-wrap">
					<?php if ( ! empty( $item['pricing']['has_discount'] ) && ! empty( $item['pricing']['formatted_discount'] ) ) : ?>
						<span class="pg-discount-badge">
							<?php echo esc_html( $item['pricing']['formatted_discount'] ); ?>
							<span class="pg-discount-text"><?php esc_html_e( 'تخفیف', 'product-groups' ); ?></span>
						</span>
					<?php endif; ?>

					<?php if ( ! empty( $item['image'] ) ) : ?>
						<img class="pg-product-image"
							 src="<?php echo esc_url( $item['image'] ); ?>"
							 alt="<?php echo esc_attr( $item['title'] ); ?>"
							 loading="lazy" />
					<?php else : ?>
						<div class="pg-product-image-placeholder">
							<svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
							</svg>
						</div>
					<?php endif; ?>
				</div>

				<div class="pg-product-content">
					<h3 class="pg-product-title" title="<?php echo esc_attr( $item['title'] ); ?>">
						<?php echo esc_html( $item['title'] ); ?>
					</h3>

					<p class="pg-product-price">
						<?php if ( ! empty( $item['pricing']['is_out_of_stock'] ) ) : ?>
							<strong class="pg-out-of-stock"><?php esc_html_e( 'ناموجود', 'product-groups' ); ?></strong>
						<?php elseif ( ! empty( $item['pricing']['has_discount'] ) ) : ?>
							<?php esc_html_e( 'قیمت:', 'product-groups' ); ?>
							<del><?php echo esc_html( $item['pricing']['formatted_rrp'] ); ?></del>
							<strong><?php echo esc_html( $item['pricing']['formatted_price'] ); ?> <?php esc_html_e( 'تومان', 'product-groups' ); ?></strong>
						<?php elseif ( ! empty( $item['pricing']['formatted_price'] ) ) : ?>
							<?php esc_html_e( 'قیمت:', 'product-groups' ); ?>
							<strong><?php echo esc_html( $item['pricing']['formatted_price'] ); ?> <?php esc_html_e( 'تومان', 'product-groups' ); ?></strong>
						<?php endif; ?>
					</p>

					<?php if ( ! empty( $item['affiliate_link'] ) ) : ?>
						<a href="<?php echo esc_url( $item['affiliate_link'] ); ?>"
						   class="pg-button"
						   target="_blank"
						   rel="noopener noreferrer nofollow">
							<span><?php esc_html_e( 'بررسی و خرید از دیجیکالا', 'product-groups' ); ?></span>
							<svg class="pg-btn-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="9" cy="21" r="1"></circle>
								<circle cx="20" cy="21" r="1"></circle>
								<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
							</svg>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
