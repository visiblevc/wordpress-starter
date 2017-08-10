<?php

class WPML_ST_Upgrade_Command_Factory {
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	/**
	 * @param string $class_name
	 * 
	 * @throws WPML_ST_Upgrade_Command_Not_Found_Exception
	 * @return IWPML_St_Upgrade_Command
	 */
	public function create( $class_name ) {
		switch ( $class_name ) {
			case 'WPML_ST_Upgrade_Migrate_Originals' :
				$result = new WPML_ST_Upgrade_Migrate_Originals( $this->wpdb, $this->sitepress );
				break;
			case 'WPML_ST_Upgrade_Db_Cache_Command' :
				$result = new WPML_ST_Upgrade_Db_Cache_Command( $this->wpdb );
				break;
			case 'WPML_ST_Upgrade_Display_Strings_Scan_Notices' :
				$themes_and_plugins_settings = new WPML_ST_Themes_And_Plugins_Settings();
				$result                      = new WPML_ST_Upgrade_Display_Strings_Scan_Notices( $themes_and_plugins_settings );
				break;
			case 'WPML_ST_Upgrade_DB_String_Packages' :
				$result = new WPML_ST_Upgrade_DB_String_Packages( $this->wpdb );
				break;
			default:
				throw new WPML_ST_Upgrade_Command_Not_Found_Exception( $class_name );
		}

		return $result;
	}
}
