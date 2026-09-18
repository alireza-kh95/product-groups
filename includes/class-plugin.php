<?php
/**
 * Main Plugin Orchestrator Class.
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PG_Plugin {

	/**
	 * Instance of this class.
	 *
	 * @var PG_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return PG_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
		$this->init_modules();
	}

	/**
	 * Initialize core modules.
	 */
	private function init_modules() {
		PG_Post_Type::init();
		PG_Meta_Box::init();
		PG_Shortcode::init();
		PG_Updater::init();
	}

	/**
	 * Register general hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Load plugin textdomain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'product-groups',
			false,
			dirname( PRODUCT_GROUPS_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register frontend assets (enqueued on demand by shortcode).
	 */
	public function register_frontend_assets() {
		wp_register_style(
			'pg-frontend-styles',
			PRODUCT_GROUPS_URL . 'assets/css/frontend.css',
			array(),
			PRODUCT_GROUPS_VERSION,
			'all'
		);
	}

	/**
	 * Enqueue admin assets on Product Group edit and listing screens.
	 *
	 * @param string $hook Current admin screen hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || PG_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Enqueue admin styles on edit/new and post list screens
		wp_enqueue_style(
			'pg-admin-styles',
			PRODUCT_GROUPS_URL . 'assets/css/admin.css',
			array(),
			PRODUCT_GROUPS_VERSION
		);

		// Enqueue admin script
		wp_enqueue_script(
			'pg-admin-script',
			PRODUCT_GROUPS_URL . 'assets/js/admin.js',
			array(),
			PRODUCT_GROUPS_VERSION,
			true
		);

		// Localize script strings
		wp_localize_script(
			'pg-admin-script',
			'pgAdminStrings',
			array(
				'productUrlPlaceholder'   => 'https://www.digikala.com/product/dkp-...',
				'affiliateUrlPlaceholder' => 'https://affiliate.digikala.com/...',
				'productUrlLabel'         => __( 'لینک محصول در دیجیکالا:', 'product-groups' ),
				'affiliateUrlLabel'       => __( 'لینک افیلیت (همکاری در فروش):', 'product-groups' ),
				'removeText'              => __( 'حذف', 'product-groups' ),
				'copiedText'              => __( 'کپی شد! ✓', 'product-groups' ),
				'confirmDelete'           => __( 'آیا از حذف این محصول اطمینان دارید؟', 'product-groups' ),
				'itemTitle'               => __( 'محصول', 'product-groups' ),
				'idDetected'              => __( 'کد کالا: ', 'product-groups' ),
				'idNotFound'              => __( 'شناسه dkp یافت نشد', 'product-groups' ),
			)
		);
	}
}
