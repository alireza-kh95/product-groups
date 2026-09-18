<?php
/**
 * GitHub Release Auto-Updater.
 *
 * Provides native WordPress plugin updates directly from GitHub releases
 * with 1-click manual check and automatic sync without requiring transients deletion.
 *
 * @package ProductGroups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PG_Updater {

	const REPOSITORY  = 'alireza-kh95/product-groups';
	const SLUG        = 'product-groups';
	const CACHE_KEY   = 'pg_github_release_cache';
	const THROTTLE_KEY= 'pg_update_page_checked';

	/**
	 * Boot the updater.
	 */
	public static function init() {
		// Native WP 5.8+ Update URI filter
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'filter_update_plugins' ), 10, 4 );

		// Core update transients
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );

		// Plugin information modal ("View version details")
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 10, 3 );

		// Clear cache upon update completion
		add_action( 'upgrader_process_complete', array( __CLASS__, 'on_upgrade_complete' ), 10, 2 );

		// Admin pages refresh hooks
		add_action( 'load-plugins.php', array( __CLASS__, 'refresh_on_admin_load' ) );
		add_action( 'load-update-core.php', array( __CLASS__, 'refresh_on_admin_load' ) );

		// 1-Click "Check for updates" row action in plugins list
		add_filter( 'plugin_action_links_' . PRODUCT_GROUPS_BASENAME, array( __CLASS__, 'add_action_links' ) );
		add_action( 'admin_action_pg_check_updates', array( __CLASS__, 'handle_manual_check' ) );
		add_action( 'admin_notices', array( __CLASS__, 'display_manual_check_notice' ) );

		// Authenticate requests if a token is configured for private repos
		add_filter( 'http_request_args', array( __CLASS__, 'authenticate_github_request' ), 10, 2 );
	}

	/**
	 * Get optional GitHub Access Token for private repositories.
	 *
	 * @return string|null
	 */
	public static function get_github_token() {
		if ( defined( 'PRODUCT_GROUPS_GITHUB_TOKEN' ) && PRODUCT_GROUPS_GITHUB_TOKEN ) {
			return PRODUCT_GROUPS_GITHUB_TOKEN;
		}

		if ( defined( 'SUCORP_GITHUB_TOKEN' ) && SUCORP_GITHUB_TOKEN ) {
			return SUCORP_GITHUB_TOKEN;
		}

		return apply_filters( 'product_groups_github_token', null );
	}

	/**
	 * Add Authorization header for GitHub API requests if token is present.
	 *
	 * @param array  $args
	 * @param string $url
	 * @return array
	 */
	public static function authenticate_github_request( $args, $url ) {
		$token = self::get_github_token();
		if ( ! $token ) {
			return $args;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$base = '/repos/' . self::REPOSITORY . '/';

		if ( 'api.github.com' === $host && 0 === strpos( $path, $base ) ) {
			$args['headers']['Authorization']        = 'Bearer ' . $token;
			$args['headers']['X-GitHub-Api-Version'] = '2022-11-28';
			$args['headers']['User-Agent']           = 'Product-Groups-Updater/' . PRODUCT_GROUPS_VERSION;

			if ( false !== strpos( $path, '/releases/assets/' ) ) {
				$args['headers']['Accept'] = 'application/octet-stream';
			} else {
				$args['headers']['Accept'] = 'application/vnd.github+json';
			}
		}

		return $args;
	}

	/**
	 * Add "Check for updates" link to plugin actions on plugins.php.
	 *
	 * @param array $links
	 * @return array
	 */
	public static function add_action_links( $links ) {
		if ( current_user_can( 'update_plugins' ) ) {
			$check_url = wp_nonce_url(
				admin_url( 'admin.php?action=pg_check_updates' ),
				'pg_check_updates_nonce'
			);

			$check_link = sprintf(
				'<a href="%s" style="color:#2271b1;font-weight:600;">%s</a>',
				esc_url( $check_url ),
				esc_html__( 'بررسی به‌روزرسانی', 'product-groups' )
			);

			$links['pg_check_update'] = $check_link;
		}

		return $links;
	}

	/**
	 * Handle 1-click manual update check action.
	 * Bypasses all caches, checks GitHub directly, updates transients, and redirects.
	 */
	public static function handle_manual_check() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'اجازه دسترسی ندارید.', 'product-groups' ) );
		}

		check_admin_referer( 'pg_check_updates_nonce' );

		// Flush all update caches
		self::purge_cache();

		// Fetch fresh release data from GitHub directly
		$release = self::fetch_release_from_github();

		if ( empty( $release ) ) {
			wp_safe_redirect( add_query_arg( array( 'pg_update_status' => 'error' ), admin_url( 'plugins.php' ) ) );
			exit;
		}

		// Force sync with WordPress core update_plugins transient
		self::sync_wp_transient( $release );

		if ( version_compare( $release['version'], PRODUCT_GROUPS_VERSION, '>' ) ) {
			$status = 'available';
		} else {
			$status = 'latest';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'pg_update_status' => $status,
					'pg_rel_ver'       => $release['version'],
				),
				admin_url( 'plugins.php' )
			)
		);
		exit;
	}

	/**
	 * Display admin notice after manual update check.
	 */
	public static function display_manual_check_notice() {
		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		if ( ! isset( $_GET['pg_update_status'] ) ) {
			return;
		}

		$status  = sanitize_text_field( $_GET['pg_update_status'] );
		$version = isset( $_GET['pg_rel_ver'] ) ? sanitize_text_field( $_GET['pg_rel_ver'] ) : '';

		if ( 'available' === $status ) {
			$update_url = wp_nonce_url(
				self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . PRODUCT_GROUPS_BASENAME ),
				'upgrade-plugin_' . PRODUCT_GROUPS_BASENAME
			);
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'گروه محصولات:', 'product-groups' ); ?></strong>
					<?php
					printf(
						/* translators: %s: new version number */
						esc_html__( 'نسخه جدید (%s) در گیت‌هاب موجود است!', 'product-groups' ),
						esc_html( $version )
					);
					?>
					<a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary" style="margin-right:10px;">
						<?php esc_html_e( 'به‌روزرسانی اکنون', 'product-groups' ); ?>
					</a>
				</p>
			</div>
			<?php
		} elseif ( 'latest' === $status ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong><?php esc_html_e( 'گروه محصولات:', 'product-groups' ); ?></strong>
					<?php
					printf(
						/* translators: %s: current version number */
						esc_html__( 'افزونه به‌روز است (نسخه %s). نیازی به به‌روزرسانی نیست.', 'product-groups' ),
						esc_html( PRODUCT_GROUPS_VERSION )
					);
					?>
				</p>
			</div>
			<?php
		} elseif ( 'error' === $status ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e( 'گروه محصولات:', 'product-groups' ); ?></strong>
					<?php esc_html_e( 'خطا در برقراری ارتباط با مخزن گیت‌هاب جهت بررسی به‌روزرسانی.', 'product-groups' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Purge local updater caches.
	 */
	public static function purge_cache() {
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( self::THROTTLE_KEY );
	}

	/**
	 * Fetch release data with short caching.
	 *
	 * @param bool $force_refresh
	 * @return array
	 */
	public static function get_latest_release( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return is_array( $cached ) ? $cached : array();
			}
		}

		$release = self::fetch_release_from_github();

		// Cache for 15 minutes on success, 3 minutes on failure
		$ttl = ! empty( $release ) ? ( 15 * MINUTE_IN_SECONDS ) : ( 3 * MINUTE_IN_SECONDS );
		set_site_transient( self::CACHE_KEY, $release, $ttl );

		return $release;
	}

	/**
	 * Query GitHub Releases API directly.
	 *
	 * @return array
	 */
	private static function fetch_release_from_github() {
		$url = 'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest';

		$headers = array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ProductGroups/' . PRODUCT_GROUPS_VERSION,
		);

		$token = self::get_github_token();
		if ( $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 12,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return self::parse_release_data( $data );
	}

	/**
	 * Parse GitHub release payload.
	 *
	 * @param mixed $data
	 * @return array
	 */
	private static function parse_release_data( $data ) {
		if ( ! is_array( $data ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) ) {
			return array();
		}

		$tag = $data['tag_name'] ?? '';
		if ( ! is_string( $tag ) || ! preg_match( '/^v?(\d+\.\d+(?:\.\d+)?)$/D', $tag, $matches ) ) {
			return array();
		}

		$version = $matches[1];
		$package = '';

		// Search for uploaded product-groups.zip asset
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				$name = $asset['name'] ?? '';
				if ( self::SLUG . '.zip' === $name || preg_match( '/^' . self::SLUG . '-.*\.zip$/', $name ) ) {
					// For private repos with tokens, use asset API URL; otherwise browser_download_url
					$token   = self::get_github_token();
					$package = ( $token && ! empty( $asset['url'] ) ) ? $asset['url'] : ( $asset['browser_download_url'] ?? '' );
					break;
				}
			}
		}

		// Fallback to repository zipball if no custom asset was attached
		if ( empty( $package ) && ! empty( $data['zipball_url'] ) ) {
			$package = $data['zipball_url'];
		}

		return array(
			'version'      => $version,
			'package'      => $package,
			'url'          => $data['html_url'] ?? ( 'https://github.com/' . self::REPOSITORY . '/releases' ),
			'published_at' => $data['published_at'] ?? '',
			'body'         => is_string( $data['body'] ?? null ) ? $data['body'] : '',
		);
	}

	/**
	 * Refresh update status on load of plugins.php or update-core.php.
	 * Detects ?force-check=1 and bypasses throttle.
	 */
	public static function refresh_on_admin_load() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$is_force_check = isset( $_GET['force-check'] );

		if ( ! $is_force_check && get_site_transient( self::THROTTLE_KEY ) ) {
			return;
		}

		// Throttle background check to once every 10 minutes unless forced
		set_site_transient( self::THROTTLE_KEY, 1, 10 * MINUTE_IN_SECONDS );

		if ( $is_force_check ) {
			self::purge_cache();
		}

		$release = self::get_latest_release( $is_force_check );
		if ( ! empty( $release ) ) {
			self::sync_wp_transient( $release );
		}
	}

	/**
	 * Sync our release data directly into WordPress's update_plugins transient.
	 *
	 * @param array $release
	 */
	private static function sync_wp_transient( $release ) {
		$updates = get_site_transient( 'update_plugins' );
		if ( ! is_object( $updates ) ) {
			$updates = new stdClass();
		}

		if ( ! isset( $updates->response ) || ! is_array( $updates->response ) ) {
			$updates->response = array();
		}
		if ( ! isset( $updates->no_update ) || ! is_array( $updates->no_update ) ) {
			$updates->no_update = array();
		}

		$item = (object) array(
			'id'            => 'https://github.com/' . self::REPOSITORY,
			'slug'          => self::SLUG,
			'plugin'        => PRODUCT_GROUPS_BASENAME,
			'new_version'   => $release['version'],
			'version'       => $release['version'],
			'url'           => $release['url'],
			'package'       => $release['package'],
			'requires_php'  => '7.4',
		);

		unset( $updates->response[ PRODUCT_GROUPS_BASENAME ], $updates->no_update[ PRODUCT_GROUPS_BASENAME ] );

		if ( version_compare( $release['version'], PRODUCT_GROUPS_VERSION, '>' ) ) {
			$updates->response[ PRODUCT_GROUPS_BASENAME ] = $item;
		} else {
			$updates->no_update[ PRODUCT_GROUPS_BASENAME ] = $item;
		}

		set_site_transient( 'update_plugins', $updates );
	}

	/**
	 * Filter for WordPress 5.8+ 'update_plugins_github.com'.
	 */
	public static function filter_update_plugins( $update, $plugin_data, $plugin_file, $locales ) {
		if ( PRODUCT_GROUPS_BASENAME !== $plugin_file ) {
			return $update;
		}

		$release = self::get_latest_release();
		if ( empty( $release ) ) {
			return $update;
		}

		if ( version_compare( $release['version'], PRODUCT_GROUPS_VERSION, '>' ) ) {
			return array(
				'id'           => 'https://github.com/' . self::REPOSITORY,
				'slug'         => self::SLUG,
				'version'      => $release['version'],
				'url'          => $release['url'],
				'package'      => $release['package'],
				'requires_php' => '7.4',
			);
		}

		return $update;
	}

	/**
	 * Inject update into site transient update_plugins.
	 *
	 * @param object $transient
	 * @return object
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = self::get_latest_release();
		if ( empty( $release ) ) {
			return $transient;
		}

		$item = (object) array(
			'id'           => 'https://github.com/' . self::REPOSITORY,
			'slug'         => self::SLUG,
			'plugin'       => PRODUCT_GROUPS_BASENAME,
			'new_version'  => $release['version'],
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires_php' => '7.4',
		);

		if ( version_compare( $release['version'], PRODUCT_GROUPS_VERSION, '>' ) ) {
			$transient->response[ PRODUCT_GROUPS_BASENAME ] = $item;
		} else {
			$transient->no_update[ PRODUCT_GROUPS_BASENAME ] = $item;
		}

		return $transient;
	}

	/**
	 * Provide plugin information popup modal data.
	 *
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object
	 */
	public static function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || self::SLUG !== ( $args->slug ?? '' ) ) {
			return $result;
		}

		$release = self::get_latest_release();
		if ( empty( $release ) ) {
			return $result;
		}

		$changelog = ! empty( $release['body'] )
			? wpautop( esc_html( $release['body'] ) )
			: '<p>' . esc_html__( 'تغییرات در یادداشت‌های انتشار گیت‌هاب موجود است.', 'product-groups' ) . '</p>';

		return (object) array(
			'name'          => __( 'گروه محصولات', 'product-groups' ),
			'slug'          => self::SLUG,
			'version'       => $release['version'],
			'author'        => '<a href="https://github.com/alireza-kh95">Alireza Khosravani</a>',
			'homepage'      => 'https://github.com/' . self::REPOSITORY,
			'download_link' => $release['package'],
			'requires'      => '5.8',
			'requires_php'  => '7.4',
			'last_updated'  => $release['published_at'],
			'sections'      => array(
				'description' => __( 'نمایش و ایجاد گروه‌های محصولات دیجیکالا با لینک افیلیت.', 'product-groups' ),
				'changelog'   => $changelog,
			),
		);
	}

	/**
	 * Clear caches when plugin is updated.
	 *
	 * @param WP_Upgrader $upgrader
	 * @param array       $hook_extra
	 */
	public static function on_upgrade_complete( $upgrader, $hook_extra ) {
		if ( 'plugin' === ( $hook_extra['type'] ?? '' ) && 'update' === ( $hook_extra['action'] ?? '' ) ) {
			$plugins = $hook_extra['plugins'] ?? array( $hook_extra['plugin'] ?? '' );
			if ( in_array( PRODUCT_GROUPS_BASENAME, $plugins, true ) ) {
				self::purge_cache();
			}
		}
	}
}
