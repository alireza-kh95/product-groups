<?php
/**
 * Product Grid Template.
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
				<?php if ( ! empty( $item['image'] ) ) : ?>
					<div class="pg-product-image-wrap">
						<img class="pg-product-image"
							 src="<?php echo esc_url( $item['image'] ); ?>"
							 alt="<?php echo esc_attr( $item['title'] ); ?>"
							 loading="lazy" />
					</div>
				<?php endif; ?>

				<div class="pg-product-content">
					<h3 class="pg-product-title"><?php echo esc_html( $item['title'] ); ?></h3>

					<p class="pg-product-price">
						<?php if ( ! empty( $item['pricing']['is_out_of_stock'] ) ) : ?>
							<span class="pg-out-of-stock"><?php esc_html_e( 'ناموجود', 'product-groups' ); ?></span>
						<?php elseif ( ! empty( $item['pricing']['has_discount'] ) ) : ?>
							<?php esc_html_e( 'قیمت:', 'product-groups' ); ?>
							<del><?php echo esc_html( $item['pricing']['formatted_rrp'] ); ?></del>
							<strong><?php echo esc_html( $item['pricing']['formatted_price'] ); ?> <?php esc_html_e( 'تومان', 'product-groups' ); ?></strong>
						<?php elseif ( ! empty( $item['pricing']['formatted_rrp'] ) ) : ?>
							<?php esc_html_e( 'قیمت:', 'product-groups' ); ?>
							<strong><?php echo esc_html( $item['pricing']['formatted_rrp'] ); ?> <?php esc_html_e( 'تومان', 'product-groups' ); ?></strong>
						<?php endif; ?>
					</p>

					<?php if ( ! empty( $item['affiliate_link'] ) ) : ?>
						<a href="<?php echo esc_url( $item['affiliate_link'] ); ?>"
						   class="pg-button"
						   target="_blank"
						   rel="noopener noreferrer nofollow">
							<?php esc_html_e( 'بررسی و خرید از دیجیکالا', 'product-groups' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
