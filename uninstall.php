<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package ProductGroups
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete updater transients
delete_site_transient( 'pg_github_release_cache' );
delete_site_transient( 'pg_update_page_checked' );

// Delete product cache transients from database
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pg_p_%' OR option_name LIKE '_transient_timeout_pg_p_%'" );
