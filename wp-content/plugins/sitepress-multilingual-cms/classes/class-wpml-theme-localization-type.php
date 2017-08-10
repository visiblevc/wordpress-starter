<?php

class WPML_Theme_Localization_Type extends WPML_Ajax_Factory implements IWPML_Ajax_Action {
	const USE_ST = 1;
	const USE_MO_FILES = 2;
	const USE_ST_AND_NO_MO_FILES = 3;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var WPML_MO_File_Search
	 */
	private $mo_file_search;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		if ( is_admin() ) {
			$this->init_ajax_actions();
		}

		if ( self::USE_ST_AND_NO_MO_FILES === $this->get_theme_localization_type() ) {
			add_filter( 'override_load_textdomain', array( $this, 'block_mo_loading_handler' ), 10, 3 );
		}
	}

	public function run() {
		$iclsettings = $this->sitepress->get_settings();

		$iclsettings['theme_localization_type'] = $this->retrieve_theme_localization_type();
		$iclsettings['theme_localization_load_textdomain'] = $this->retrieve_theme_localization_load_textdomain();
		$iclsettings['gettext_theme_domain_name'] = array_key_exists( 'textdomain_value', $_POST ) ? filter_var( $_POST['textdomain_value'], FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE ) : false;

		if ( self::USE_MO_FILES === $iclsettings['theme_localization_type'] ) {
			$iclsettings['theme_language_folders'] = $this->get_mo_file_search()->find_theme_mo_dirs();
		}

		$this->sitepress->save_settings( $iclsettings );

		do_action( 'wpml_post_save_theme_localization_type', $iclsettings );

		return new WPML_Ajax_Response( true, $iclsettings['theme_localization_type'] );
	}

	public function get_class_names() {
		return array( __CLASS__ );
	}

	public function create( $class_name ) {
		return $this;
	}

	public function init_ajax_actions() {
		new WPML_Ajax_Route( $this );
	}

	/**
	 * @return int
	 */
	private function retrieve_theme_localization_type() {
		$result = self::USE_MO_FILES;
		if ( array_key_exists( 'icl_theme_localization_type', $_POST ) ) {
			$var = filter_var( $_POST['icl_theme_localization_type'], FILTER_VALIDATE_INT );
			$options = array( self::USE_ST, self::USE_MO_FILES, self::USE_ST_AND_NO_MO_FILES );
			if ( in_array( $var, $options, true ) ) {
				$result = $var;
			}
		}

		return $result;
	}

	/**
	 * @return int
	 */
	private function retrieve_theme_localization_load_textdomain() {
		$result = 0;
		if ( array_key_exists( 'icl_theme_localization_load_td', $_POST ) ) {
			$var = filter_var( $_POST['icl_theme_localization_load_td'], FILTER_VALIDATE_INT );
			if ( false !== $var ) {
				$result = $var;
			}
		}

		return $result;
	}

	/**
	 * @return int
	 */
	public function get_theme_localization_type() {
		$settings = $this->sitepress->get_settings();
		if ( isset( $settings['theme_localization_type'] ) ) {
			return (int) $settings['theme_localization_type'];
		} else {
			return self::USE_MO_FILES;
		}
	}

	/**
	 * @return bool
	 */
	public function is_st_type() {
		return in_array( $this->get_theme_localization_type(), array( self::USE_ST, self::USE_ST_AND_NO_MO_FILES ), true );
	}

	/**
	 * Block loading of all MO files regardless domain or mofile name
	 *
	 * @param bool $override
	 * @param string $domain
	 * @param string $mofile
	 *
	 * @return bool
	 */
	public function block_mo_loading_handler( $override, $domain, $mofile ) {
		$override = true;
		return $override;
	}

	/**
	 * @return WPML_MO_File_Search
	 */
	public function get_mo_file_search() {
		if ( ! $this->mo_file_search ) {
			$this->mo_file_search = new WPML_MO_File_Search( $this->sitepress );
		}

		return $this->mo_file_search;
	}

	/**
	 * @param WPML_MO_File_Search $mo_file_search
	 * @return WPML_Theme_Localization_Type
	 */
	public function set_mo_file_search( WPML_MO_File_Search $mo_file_search ) {
		$this->mo_file_search = $mo_file_search;
		return $this;
	}
}
