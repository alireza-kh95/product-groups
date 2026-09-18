<?php
/**
 * Plugin Settings Page & Options.
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PG_Settings {

	const OPTION_CARD_STYLE   = 'pg_card_style';
	const OPTION_CACHE_TTL    = 'pg_cache_ttl';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_pg_purge_cache', array( __CLASS__, 'handle_purge_cache' ) );
	}

	/**
	 * Add Settings submenu page under 'product_group'.
	 */
	public static function add_settings_menu() {
		add_submenu_page(
			'edit.php?post_type=' . PG_Post_Type::POST_TYPE,
			__( 'تنظیمات گروه محصولات', 'product-groups' ),
			__( 'تنظیمات', 'product-groups' ),
			'manage_options',
			'product-groups-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public static function register_settings() {
		register_setting(
			'pg_settings_group',
			self::OPTION_CARD_STYLE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_card_style' ),
				'default'           => 'classic',
			)
		);

		register_setting(
			'pg_settings_group',
			self::OPTION_CACHE_TTL,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 6,
			)
		);
	}

	/**
	 * Sanitize card style option.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function sanitize_card_style( $value ) {
		return in_array( $value, array( 'classic', 'modern' ), true ) ? $value : 'classic';
	}

	/**
	 * Get current card style (default: classic).
	 *
	 * @return string
	 */
	public static function get_card_style() {
		return get_option( self::OPTION_CARD_STYLE, 'classic' );
	}

	/**
	 * Handle 1-click purge all product cache.
	 */
	public static function handle_purge_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'اجازه دسترسی ندارید.', 'product-groups' ) );
		}

		check_admin_referer( 'pg_purge_cache_action', 'pg_purge_nonce' );

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pg_p_%' OR option_name LIKE '_transient_timeout_pg_p_%'" );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'product-groups-settings',
					'pg_cache_cleared' => '1',
				),
				admin_url( 'edit.php?post_type=' . PG_Post_Type::POST_TYPE )
			)
		);
		exit;
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_style = self::get_card_style();
		$cache_ttl     = get_option( self::OPTION_CACHE_TTL, 6 );

		if ( isset( $_GET['settings-updated'] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'تنظیمات با موفقیت ذخیره شد.', 'product-groups' ); ?></strong></p>
			</div>
			<?php
		}

		if ( isset( $_GET['pg_cache_cleared'] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'تمام کش‌های محصولات دیجیکالا با موفقیت پاکسازی شدند.', 'product-groups' ); ?></strong></p>
			</div>
			<?php
		}
		?>
		<div class="wrap pg-settings-wrap">
			<div class="pg-settings-header">
				<h1>
					<span class="dashicons dashicons-admin-generic" style="font-size:28px;width:28px;height:28px;vertical-align:middle;margin-left:6px;color:#2271b1;"></span>
					<?php esc_html_e( 'تنظیمات گروه محصولات', 'product-groups' ); ?>
				</h1>
				<p class="description">
					<?php esc_html_e( 'تنظیم استایل کارت‌های محصول در سایت و مدیریت حافظه کش API.', 'product-groups' ); ?>
				</p>
			</div>

			<form method="post" action="options.php" class="pg-settings-form">
				<?php settings_fields( 'pg_settings_group' ); ?>

				<!-- Card Style Setting -->
				<div class="pg-settings-card">
					<div class="pg-card-header">
						<h2><?php esc_html_e( 'استایل نمایش کارت‌های محصول در سایت', 'product-groups' ); ?></h2>
						<p class="description"><?php esc_html_e( 'استایل مورد نظر خود را برای نمایش محصولات در بخش کاربری سایت انتخاب کنید.', 'product-groups' ); ?></p>
					</div>

					<div class="pg-style-selector-grid">
						<!-- Classic Option (Default) -->
						<label class="pg-style-option <?php echo 'classic' === $current_style ? 'is-selected' : ''; ?>">
							<input type="radio" name="<?php echo esc_attr( self::OPTION_CARD_STYLE ); ?>" value="classic" <?php checked( $current_style, 'classic' ); ?> class="pg-style-radio" />
							<div class="pg-style-option-body">
								<div class="pg-style-preview pg-preview-classic">
									<div class="pg-mini-card">
										<div class="pg-mini-img"></div>
										<div class="pg-mini-title"></div>
										<div class="pg-mini-price"></div>
										<div class="pg-mini-btn"></div>
									</div>
								</div>
								<div class="pg-style-info">
									<div class="pg-style-title-row">
										<strong><?php esc_html_e( 'استایل کلاسیک (پیش‌فرض)', 'product-groups' ); ?></strong>
										<span class="pg-badge-default"><?php esc_html_e( 'همان استایل قبلی شما', 'product-groups' ); ?></span>
									</div>
									<p><?php esc_html_e( 'طراحی آشنا و استاندارد اولیه افزونه بدون تغییر در ساختار یا ظاهر سایت شما.', 'product-groups' ); ?></p>
								</div>
							</div>
							<div class="pg-style-check">
								<span class="dashicons dashicons-yes"></span>
							</div>
						</label>

						<!-- Modern Option -->
						<label class="pg-style-option <?php echo 'modern' === $current_style ? 'is-selected' : ''; ?>">
							<input type="radio" name="<?php echo esc_attr( self::OPTION_CARD_STYLE ); ?>" value="modern" <?php checked( $current_style, 'modern' ); ?> class="pg-style-radio" />
							<div class="pg-style-option-body">
								<div class="pg-style-preview pg-preview-modern">
									<div class="pg-mini-card">
										<div class="pg-mini-badge"></div>
										<div class="pg-mini-img"></div>
										<div class="pg-mini-title"></div>
										<div class="pg-mini-price"></div>
										<div class="pg-mini-btn pg-btn-modern"></div>
									</div>
								</div>
								<div class="pg-style-info">
									<div class="pg-style-title-row">
										<strong><?php esc_html_e( 'استایل مدرن و انیمیشن‌دار', 'product-groups' ); ?></strong>
										<span class="pg-badge-new"><?php esc_html_e( 'جدید', 'product-groups' ); ?></span>
									</div>
									<p><?php esc_html_e( 'انیمیشن‌های نرم روی کارت، برچسب تخفیف متحرک، آیکون سبد خرید و افکت Shimmer روی دکمه.', 'product-groups' ); ?></p>
								</div>
							</div>
							<div class="pg-style-check">
								<span class="dashicons dashicons-yes"></span>
							</div>
						</label>
					</div>
				</div>

				<!-- Cache Settings -->
				<div class="pg-settings-card">
					<div class="pg-card-header">
						<h2><?php esc_html_e( 'تنظیمات کش API دیجیکالا', 'product-groups' ); ?></h2>
						<p class="description"><?php esc_html_e( 'برای افزایش چشمگیر سرعت لود سایت، اطلاعات قیمت و تصویر محصولات دیجیکالا در دیتابیس کش می‌شوند.', 'product-groups' ); ?></p>
					</div>

					<div class="pg-form-row">
						<label for="pg_cache_ttl" class="pg-label">
							<?php esc_html_e( 'مدت زمان نگهداری کش (ساعت):', 'product-groups' ); ?>
						</label>
						<select name="<?php echo esc_attr( self::OPTION_CACHE_TTL ); ?>" id="pg_cache_ttl" class="pg-select">
							<option value="1" <?php selected( $cache_ttl, 1 ); ?>><?php esc_html_e( '۱ ساعت', 'product-groups' ); ?></option>
							<option value="3" <?php selected( $cache_ttl, 3 ); ?>><?php esc_html_e( '۳ ساعت', 'product-groups' ); ?></option>
							<option value="6" <?php selected( $cache_ttl, 6 ); ?>><?php esc_html_e( '۶ ساعت (پیش‌نهاد شده)', 'product-groups' ); ?></option>
							<option value="12" <?php selected( $cache_ttl, 12 ); ?>><?php esc_html_e( '۱۲ ساعت', 'product-groups' ); ?></option>
							<option value="24" <?php selected( $cache_ttl, 24 ); ?>><?php esc_html_e( '۲۴ ساعت', 'product-groups' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( 'نکته: هر زمان که یک گروه محصول را در پیشخوان ویرایش و ذخیره کنید، کش آن به صورت خودکار بلافاصله بروزرسانی می‌شود.', 'product-groups' ); ?>
						</p>
					</div>
				</div>

				<div class="pg-submit-bar">
					<?php submit_button( __( 'ذخیره تنظیمات', 'product-groups' ), 'primary button-hero', 'submit', false ); ?>
				</div>
			</form>

			<!-- Purge Cache Card -->
			<div class="pg-settings-card pg-purge-card">
				<div class="pg-card-header">
					<h2><?php esc_html_e( 'پاکسازی حافظه کش محصولات', 'product-groups' ); ?></h2>
					<p class="description"><?php esc_html_e( 'اگر قیمت‌ها یا تصاویر در دیجیکالا تغییر کرده‌اند و می‌خواهید بلافاصله تمام محصولات در سایت با جدیدترین اطلاعات بازخوانی شوند:', 'product-groups' ); ?></p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="pg_purge_cache" />
					<?php wp_nonce_field( 'pg_purge_cache_action', 'pg_purge_nonce' ); ?>
					<button type="submit" class="button button-secondary pg-btn-purge" onclick="return confirm('<?php echo esc_js( __( 'آیا از پاکسازی تمام کش‌های محصولات اطمینان دارید؟', 'product-groups' ) ); ?>');">
						<span class="dashicons dashicons-trash" style="vertical-align:middle;margin-left:4px;"></span>
						<?php esc_html_e( 'پاکسازی تمام کش‌های محصولات دیجیکالا', 'product-groups' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}
}
