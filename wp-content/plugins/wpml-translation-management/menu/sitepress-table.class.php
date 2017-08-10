<?php
/**
 * @package wpml-core
 */

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( !class_exists( 'SitePress_Table' ) ) {
	class SitePress_Table extends WP_List_Table {
		public function __construct() {
			parent::__construct();
		}
	}
}
