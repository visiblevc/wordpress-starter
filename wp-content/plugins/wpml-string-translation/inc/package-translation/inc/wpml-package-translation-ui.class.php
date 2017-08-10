<?php

class WPML_Package_Translation_UI {
	var $load_priority = 101;
	private $top_page_hook = '';
	private $menu_page_hook = '';

	public function __construct() {
		add_action( 'wpml_loaded', array( $this, 'loaded' ), $this->load_priority );
	}

	public function loaded() {
		
		if ($this->passed_dependencies()) {
			$this->set_admin_hooks();
			do_action( 'WPML_PT_HTML' );
		}
	}

	private function passed_dependencies() {
		return defined( 'ICL_SITEPRESS_VERSION' )
		       && defined( 'WPML_ST_VERSION' )
		       && defined( 'WPML_TM_VERSION' );
	}
	
	private function set_admin_hooks() {
		if(is_admin()) {
			add_action( 'admin_menu', array( $this, 'menu' ) );

			add_action( 'admin_register_scripts', array( $this, 'admin_register_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}
	}

	public function menu() {
		if ( ! defined( 'ICL_PLUGIN_PATH' ) ) {
			return;
		}
		global $sitepress;
		if ( ! isset( $sitepress ) || ( method_exists( $sitepress, 'get_setting' ) && ! $sitepress->get_setting( 'setup_complete' ) ) ) {
			return;
		}

		global $sitepress_settings;

		if ( ( ! isset( $sitepress_settings[ 'existing_content_language_verified' ] ) || ! $sitepress_settings[ 'existing_content_language_verified' ] ) ) {
			return;
		}

		if ( current_user_can( 'wpml_manage_string_translation' ) ) {
			$this->top_page_hook = apply_filters( 'icl_menu_main_page', basename( ICL_PLUGIN_PATH ) . '/menu/languages.php' );
			$this->menu_page_hook = add_submenu_page( $this->top_page_hook, __( 'Packages', 'wpml-string-translation' ), __( 'Packages', 'wpml-string-translation' ), 'wpml_manage_string_translation', 'wpml-package-management', array( 'WPML_Package_Translation_HTML_Packages', 'package_translation_menu' ) );
			$this->admin_register_scripts();
		}
	}

	function admin_register_scripts() {
		wp_register_script( 'wpml-package-trans-man-script', WPML_PACKAGE_TRANSLATION_URL . '/resources/js/wpml_package_management.js', array( 'jquery' ) );
	}

	function admin_enqueue_scripts($hook_suffix) {
		if ( $hook_suffix == $this->menu_page_hook ) {
			wp_enqueue_script( 'wpml-package-trans-man-script' );
		}
	}
}