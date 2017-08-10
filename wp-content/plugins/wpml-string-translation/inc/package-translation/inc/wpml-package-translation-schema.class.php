<?php

class WPML_Package_Translation_Schema {

	private static $table_name;

	static function run_update() {
		$updates_run = get_option( 'wpml-package-translation-db-updates-run', array() );

		if ( defined( 'WPML_PT_VERSION_DEV' ) ) {
			delete_option( 'wpml-package-translation-string-packages-table-updated' );
			if ( ( $key = array_search( WPML_PT_VERSION_DEV, $updates_run ) ) !== false ) {
				unset( $updates_run[ $key ] );
			}
		}

		if ( ! in_array( '0.0.2', $updates_run ) ) {
			// We need to make sure we build everything for 0.0.2 because users may
			// only be updating the string translation plugin and may not do an
			// activation.
			self::build_icl_string_packages_table();
			self::build_icl_string_packages_columns_if_required();
			self::fix_icl_string_packages_ID_column();
			self::build_icl_strings_columns_if_required();

			$updates_run[ ] = '0.0.2';

			update_option( 'wpml-package-translation-db-updates-run', $updates_run );
		}

	}

	private static function current_table_has_column( $column ) {
		global $wpdb;

		$cols  = $wpdb->get_results( "SHOW COLUMNS FROM `" . self::$table_name . "`" );
		$found = false;
		foreach ( $cols as $col ) {
			if ( $col->Field == $column ) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	private static function add_string_package_id_to_icl_strings() {
		global $wpdb;
		$sql = "ALTER TABLE `" . self::$table_name . "`
						ADD `string_package_id` BIGINT unsigned NULL AFTER value,
						ADD INDEX (`string_package_id`)";

		return $wpdb->query( $sql );
	}

	private static function add_type_to_icl_strings() {
		global $wpdb;
		$sql = "ALTER TABLE `" . self::$table_name . "` ADD `type` VARCHAR(40) NOT NULL DEFAULT 'LINE' AFTER string_package_id";

		return $wpdb->query( $sql );
	}

	private static function add_title_to_icl_strings() {
		global $wpdb;
		$sql = "ALTER TABLE `" . self::$table_name . "` ADD `title` VARCHAR(160) NULL AFTER type";

		return $wpdb->query( $sql );
	}

	public static function build_icl_strings_columns_if_required() {
		global $wpdb;

		if ( ! get_option( 'wpml-package-translation-string-table-updated' ) ) {

			self::$table_name = $wpdb->prefix . 'icl_strings';

			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . self::$table_name . "'" ) == self::$table_name ) {
				if ( ! self::current_table_has_column( 'string_package_id' ) ) {
					self::add_string_package_id_to_icl_strings();
				}

				if ( ! self::current_table_has_column( 'type' ) ) {
					self::add_type_to_icl_strings();
				}

				if ( ! self::current_table_has_column( 'title' ) ) {
					self::add_title_to_icl_strings();
				}
				update_option( 'wpml-package-translation-string-table-updated', true );
			}
		}
	}

	private static function build_icl_string_packages_columns_if_required() {
		global $wpdb;

		if ( get_option( 'wpml-package-translation-string-packages-table-updated' ) != '0.0.2' ) {

			self::$table_name = $wpdb->prefix . 'icl_string_packages';

			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . self::$table_name . "'" ) == self::$table_name ) {
				if ( ! self::current_table_has_column( 'kind_slug' ) ) {
					self::add_kind_slug_to_icl_string_packages();
				}
				self::update_kind_slug();

				if ( ! self::current_table_has_column( 'view_link' ) ) {
					self::add_view_link_to_icl_string_packages();
				}
				update_option( 'wpml-package-translation-string-packages-table-updated', '0.0.2' );
			}
		}
	}

	private static function add_kind_slug_to_icl_string_packages() {
		global $wpdb;
		$sql    = "ALTER TABLE `" . self::$table_name . "` ADD `kind_slug` varchar(160) DEFAULT '' NOT NULL AFTER `ID`";
		$result = $wpdb->query( $sql );

		return $result;
	}

	private static function add_view_link_to_icl_string_packages() {
		global $wpdb;
		$sql = "ALTER TABLE `" . self::$table_name . "` ADD `view_link` TEXT NOT NULL AFTER `edit_link`";

		return $wpdb->query( $sql );
	}

	private static function fix_icl_string_packages_ID_column() {
		global $wpdb;
		self::$table_name = $wpdb->prefix . 'icl_string_packages';
		if ( self::current_table_has_column( 'id' ) ) {
			$sql = "ALTER TABLE `" . self::$table_name . "` CHANGE id ID BIGINT UNSIGNED NOT NULL auto_increment;";
			$wpdb->query( $sql );
		}
	}

	private static function build_icl_string_packages_table() {
		global $wpdb;

		$charset_collate = self::build_charset_collate();

		self::$table_name = $wpdb->prefix . 'icl_string_packages';
		$sql              = "
                 CREATE TABLE IF NOT EXISTS `" . self::$table_name . "` (
                  `ID` bigint(20) unsigned NOT NULL auto_increment,
                  `kind_slug` varchar(160) NOT NULL,
                  `kind` varchar(160) NOT NULL,
                  `name` varchar(160) NOT NULL,
                  `title` varchar(160) NOT NULL,
                  `edit_link` TEXT NOT NULL,
                  `view_link` TEXT NOT NULL,
                  `post_id` INTEGER DEFAULT NULL,
                  PRIMARY KEY  (`ID`)
                ) " . $charset_collate . "";
		if ( $wpdb->query( $sql ) === false ) {
			throw new Exception( $wpdb->last_error );
		}
	}

	private static function build_charset_collate() {
		$charset_collate = '';
		if ( self::wpdb_has_cap_collation() ) {
			$charset_collate .= self::build_default_char_set();
			$charset_collate .= self::build_collate();
		}

		return $charset_collate;
	}

	private static function wpdb_has_cap_collation() {
		global $wpdb;

		return method_exists( $wpdb, 'has_cap' ) && $wpdb->has_cap( 'collation' );
	}

	private static function build_default_char_set() {
		global $wpdb;
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}

		return $charset_collate;
	}

	private static function build_collate() {
		global $wpdb;
		$charset_collate = '';
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		return $charset_collate;
	}

	private static function update_kind_slug() {
		global $wpdb;
		$sql    = "SELECT kind FROM " . self::$table_name . " WHERE IFNULL(kind_slug,'')='' GROUP BY kind";
		$kinds  = $wpdb->get_col( $sql );
		$result = ( count( $kinds ) == 0 );
		foreach ( $kinds as $kind ) {
			$kind_slug = sanitize_title_with_dashes( $kind );
			$result    = $wpdb->update( self::$table_name, array( 'kind_slug' => $kind_slug ), array( 'kind' => $kind ) );
			if ( ! $result ) {
				break;
			}
		}

		return $result;
	}
	
}