<?php
/**
 * Product Grid Template.
 *
 * Supports both 'classic' (exact original v1.2 markup) and 'modern' card styles.
 *
 * @package ProductGroups
 *
 * @var array  $items      List of prepared product items.
 * @var string $card_style Current card style: 'classic' or 'modern'.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $items ) ) {
	return;
}

$card_style = isset( $card_style ) ? $card_style : PG_Settings::get_card_style();
?>

<div class="pg-shortcode-list <?php echo 'modern' === $card_style ? 'pg-style-modern' : 'pg-style-classic'; ?>">
	<div class="pg-product-grid">
		<?php foreach ( $items as $item ) : ?>
			<?php if ( 'modern' === $card_style ) : ?>
				<!-- Modern Animated Style (Opt-in via Settings) -->
				<div class="pg-product-item pg-item-modern">
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
							<?php else : ?>
								<?php esc_html_e( 'قیمت:', 'product-groups' ); ?>
								<strong><?php echo esc_html( $item['pricing']['formatted_price'] ?: $item['pricing']['formatted_rrp'] ); ?> <?php esc_html_e( 'تومان', 'product-groups' ); ?></strong>
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
			<?php else : ?>
				<!-- Classic Style (Exact Original v1.2 Layout - Default) -->
				<div class="pg-product-item">
					<?php if ( ! empty( $item['image'] ) ) : ?>
						<img class="pg-product-image" src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>"/>
					<?php endif; ?>

					<h3 class="pg-product-title"><?php echo esc_html( $item['title'] ); ?></h3>

					<p class="pg-product-price">
						<?php
						if ( ! empty( $item['pricing']['is_out_of_stock'] ) ) {
							echo '<strong>' . esc_html__( 'ناموجود', 'product-groups' ) . '</strong>';
						} elseif ( ! empty( $item['pricing']['has_discount'] ) ) {
							echo esc_html__( 'قیمت:', 'product-groups' ) . ' <del>' . esc_html( $item['pricing']['formatted_rrp'] ) . '</del> <strong>' . esc_html( $item['pricing']['formatted_price'] ) . ' ' . esc_html__( 'تومان', 'product-groups' ) . '</strong>';
						} else {
							echo esc_html__( 'قیمت:', 'product-groups' ) . ' <strong>' . esc_html( $item['pricing']['formatted_rrp'] ?: $item['pricing']['formatted_price'] ) . ' ' . esc_html__( 'تومان', 'product-groups' ) . '</strong>';
						}
						?>
					</p>

					<?php if ( ! empty( $item['affiliate_link'] ) ) : ?>
						<a href="<?php echo esc_url( $item['affiliate_link'] ); ?>" class="pg-button" target="_blank" rel="noopener noreferrer nofollow">
							<?php esc_html_e( 'بررسی و خرید از دیجیکالا', 'product-groups' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</div>
