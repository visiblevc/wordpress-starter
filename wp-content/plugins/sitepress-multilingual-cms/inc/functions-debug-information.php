<?php

function get_debug_info() {
	global $sitepress, $wpdb;

	return new WPML_Debug_Information( $wpdb, $sitepress );
}