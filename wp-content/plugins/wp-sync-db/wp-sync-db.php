<?php
/*
Plugin Name: WP Sync DB
Description: Export, push, and pull to migrate your WordPress databases.
Author: Sean Lang
Version: 1.5
Author URI: http://slang.cx
GitHub Plugin URI: wp-sync-db/wp-sync-db
Network: True
*/

$GLOBALS['wpsdb_meta']['wp-sync-db']['version'] = '1.5';
$GLOBALS['wpsdb_meta']['wp-sync-db']['folder'] = basename( plugin_dir_path( __FILE__ ) );

// Define the directory seperator if it isn't already
if( !defined( 'DS' ) ) {
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		define('DS', '\\');
	}
	else {
		define('DS', '/');
	}
}

function wp_sync_db_loaded() {
	// if neither WordPress admin nor running from wp-cli, exit quickly to prevent performance impact
	if ( !is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) return;

	require_once 'class/wpsdb-base.php';
	require_once 'class/wpsdb-addon.php';
	require_once 'class/wpsdb.php';

	global $wpsdb;
	$wpsdb = new WPSDB( __FILE__ );
}

add_action( 'plugins_loaded', 'wp_sync_db_loaded' );

function wp_sync_db_init() {
	// if neither WordPress admin nor running from wp-cli, exit quickly to prevent performance impact
	if ( !is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) return;

	load_plugin_textdomain( 'wp-sync-db', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'init', 'wp_sync_db_init' );
