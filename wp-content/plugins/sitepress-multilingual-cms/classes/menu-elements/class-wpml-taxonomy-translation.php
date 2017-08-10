<?php

/**
 * class WPML_Taxonomy_Translation
 * 
 * Used by WCML so be careful about modifications to the contructor
 *
 */

class WPML_Taxonomy_Translation {

	private $ui = null;

	/**
	 * WPML_Taxonomy_Translation constructor.
	 *
	 * @param string $taxonomy if given renders a specific taxonomy,
	 *                         otherwise renders a placeholder
	 * @param bool[] $args array with possible indices:
	 *                     'taxonomy_selector' => bool .. whether or not to show the taxonomy selector
	 * @param WPML_UI_Screen_Options_Factory $screen_options_factory
	 */
	public function __construct( $taxonomy = '', $args = array(), $screen_options_factory = null ) {
		global $sitepress;
		$this->ui = new WPML_Taxonomy_Translation_UI( $sitepress, $taxonomy, $args, $screen_options_factory );
	}

	/**
	 * Echos the HTML that serves as an entry point for the taxonomy translation
	 * screen and enqueues necessary js.
	 */
	public function render() {
		$this->ui->render();
	}
	
}
