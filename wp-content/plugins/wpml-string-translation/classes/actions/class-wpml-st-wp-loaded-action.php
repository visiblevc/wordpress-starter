<?php

class WPML_ST_WP_Loaded_Action extends WPML_SP_User {

	/** @var WPML_String_Translation $st_instance */
	private $st_instance;

	/** @var string $pagenow */
	private $pagenow;

	/** @var string $get_page */
	private $get_page;

	public function __construct( &$sitepress, &$st_instance, &$pagenow, $get_page ) {
		parent::__construct( $sitepress );
		$this->st_instance = &$st_instance;
		$this->pagenow     = &$pagenow;
		$this->get_page    = $get_page;
	}

	public function run() {
		$string_settings = $this->sitepress->get_setting( 'st', array() );
		if ( ! isset( $string_settings['sw'] )
		     || ( $this->pagenow === 'admin.php'
		          && strpos( $this->get_page, 'theme-localization.php' ) !== false ) ) {
			$string_settings['sw'] = isset( $string_settings['sw'] )
				? $string_settings['sw'] : array();
			$this->sitepress->set_setting( 'st', $string_settings, true );
			$this->st_instance->initialize_wp_and_widget_strings();
		}
	}
}