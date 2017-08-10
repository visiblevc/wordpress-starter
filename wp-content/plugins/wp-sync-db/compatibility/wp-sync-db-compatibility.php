<?php
/*
Plugin Name: WP Sync DB Compatibility
Description: Prevents 3rd party plugins from being loaded during WP Sync DB specific operations
Author: Sean Lang
Version: 1.0
Author URI: http://slang.cx
*/

$GLOBALS['wpsdb_compatibility'] = true;


/**
* remove blog-active plugins
* @param array $plugins numerically keyed array of plugin names
* @return array
*/
function wpsdbc_exclude_plugins( $plugins ) {
	if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX || !isset( $_POST['action'] ) || false === strpos( $_POST['action'], 'wpsdb' ) ) return $plugins;
	$wpsdb_settings = get_option( 'wpsdb_settings' );
	if ( !empty( $wpsdb_settings['blacklist_plugins'] ) ) {
		$blacklist_plugins = array_flip( $wpsdb_settings['blacklist_plugins'] );
	}
	foreach( $plugins as $key => $plugin ) {
		if ( false !== strpos( $plugin, 'wp-sync-db' ) || !isset( $blacklist_plugins[$plugin] ) ) continue;
		unset( $plugins[$key] );
	}
	return $plugins;
}
add_filter( 'option_active_plugins', 'wpsdbc_exclude_plugins' );


/**
* remove network-active plugins
* @param array $plugins array of plugins keyed by name (name=>timestamp pairs)
* @return array
*/
function wpsdbc_exclude_site_plugins( $plugins ) {
	if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX || !isset( $_POST['action'] ) || false === strpos( $_POST['action'], 'wpsdb' ) ) return $plugins;
	$wpsdb_settings = get_option( 'wpsdb_settings' );
	if ( !empty( $wpsdb_settings['blacklist_plugins'] ) ) {
		$blacklist_plugins = array_flip( $wpsdb_settings['blacklist_plugins'] );
	}
	foreach( array_keys( $plugins ) as $plugin ) {
		if ( false !== strpos( $plugin, 'wp-sync-db' ) || !isset( $blacklist_plugins[$plugin] ) ) continue;
		unset( $plugins[$plugin] );
	}
	return $plugins;
}
add_filter( 'site_option_active_sitewide_plugins', 'wpsdbc_exclude_site_plugins' );
