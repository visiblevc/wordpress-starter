<?php

/**
 * Class WPML_ST_Upgrade_DB_String_Packages
 */
class WPML_ST_Upgrade_DB_String_Packages implements IWPML_St_Upgrade_Command {
	private $wpdb;

	/**
	 * WPML_ST_Upgrade_DB_String_Packages constructor.
	 *
	 * @param WPDB $wpdb
	 */
	public function __construct( WPDB $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function run() {
		$result = false;
		$sql_get_st_package_table_name = "SHOW TABLES LIKE '{$this->wpdb->prefix}icl_string_packages'";
		$sql_get_post_id_column_from_st_package = "SHOW COLUMNS FROM {$this->wpdb->prefix}icl_string_packages LIKE 'post_id'";

		$st_packages_table_exist = $this->wpdb->get_var( $sql_get_st_package_table_name ) === "{$this->wpdb->prefix}icl_string_packages";
		$post_id_column_exists = $st_packages_table_exist ? $this->wpdb->get_var( $sql_get_post_id_column_from_st_package ) === 'post_id' : false;
		if ( $st_packages_table_exist && ! $post_id_column_exists ) {
			$sql = "ALTER TABLE {$this->wpdb->prefix}icl_string_packages
				ADD COLUMN `post_id` INTEGER";
			$result = $this->wpdb->query( $sql );
		}

		return false !== $result;
	}

	public function run_ajax() {
		return $this->run();
	}

	public function run_frontend() {
	}

	/**
	 * @return string
	 */
	public static function get_command_id() {
		return __CLASS__ . '_2.4.2';
	}
}
