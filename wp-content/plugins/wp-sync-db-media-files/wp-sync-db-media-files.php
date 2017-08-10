<?php
/*
Plugin Name: WP Sync DB Media Files
Description: An extension of WP Sync DB that allows the migration of media files.
Author: Sean Lang
Version: 1.1.4b1
Author URI: http://slang.cx
GitHub Plugin URI: wp-sync-db/wp-sync-db-media-files
Network: True
*/

require_once 'version.php';
$GLOBALS['wpsdb_meta']['wp-sync-db-media-files']['folder'] = basename( plugin_dir_path( __FILE__ ) );

function wp_sync_db_media_files_loaded() {
	if ( ! class_exists( 'WPSDB_Addon' ) ) return;

	require_once 'class/wpsdb-media-files.php';

	global $wpsdb_media_files;
	$wpsdb_media_files = new WPSDB_Media_Files( __FILE__ );
}

add_action( 'plugins_loaded', 'wp_sync_db_media_files_loaded', 20 );

function wp_sync_db_media_files_init() {
	if ( ! class_exists( 'WPSDB_Addon' ) ) return;

	load_plugin_textdomain( 'wp-sync-db-media-files', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'admin_init', 'wp_sync_db_media_files_init', 20 );
