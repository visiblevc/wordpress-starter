<?php

/**
 * Class WPML_Taxonomy_Translation_UI
 */
class WPML_Taxonomy_Translation_UI extends WPML_SP_User {

	private $taxonomy = '';
	private $tax_selector = true;
	private $taxonomy_obj = false;
	private $screen_options = null;

	/**
	 * WPML_Taxonomy_Translation constructor.
	 *
	 * @param SitePress $sitepress
	 * @param string $taxonomy if given renders a specific taxonomy,
	 *                         otherwise renders a placeholder
	 * @param bool[] $args array with possible indices:
	 *                     'taxonomy_selector' => bool .. whether or not to show the taxonomy selector
	 * @param WPML_UI_Screen_Options_Factory $screen_options_factory
	 */
	public function __construct( &$sitepress, $taxonomy = '', $args = array(), $screen_options_factory = null ) {
		parent::__construct( $sitepress );
		$this->tax_selector = isset( $args['taxonomy_selector'] ) ? $args['taxonomy_selector'] : true;
		$this->taxonomy     = $taxonomy ? $taxonomy : false;
		if ( $taxonomy ) {
			$this->taxonomy_obj = get_taxonomy( $taxonomy );
		}

		if ( $screen_options_factory ) {
			$this->screen_options = $screen_options_factory->create_pagination( 'taxonomy_translation_per_page', ICL_TM_DOCS_PER_PAGE );
			$this->help_tab       = $screen_options_factory->create_help_tab(
				'taxonomy_translation_help_tab',
				esc_html__( 'Taxonomy Translation', 'sitepress' ),
				'<p>' . esc_html__( 'Descriptive content that will show in My Help Tab-body goes here.' ) . '</p>'
			);
		}

	}

	/**
	 * Echos the HTML that serves as an entry point for the taxonomy translation
	 * screen and enqueues necessary js.
	 */
	public function render() {
		WPML_Taxonomy_Translation_Table_Display::enqueue_taxonomy_table_js( $this->sitepress );
		$output = '<div class="wrap">';
		if ( $this->taxonomy ) {
			$output .= '<input type="hidden" id="tax-preselected" value="' . $this->taxonomy . '">';
		}
		if ( ! $this->tax_selector ) {
			$output .= '<input type="hidden" id="tax-selector-hidden" value="1"/>';
		}
		if ( $this->tax_selector ) {
			$output .= '<h1>' . esc_html__( 'Taxonomy Translation', 'sitepress' ) . '</h1>';
			$output .= '<br/>';
		}
		$output .= '<div id="wpml_tt_taxonomy_translation_wrap" data-items_per_page="' . $this->get_items_per_page() . '">';
		$output .= '<div class="loading-content"><span class="spinner" style="visibility: visible"></span></div>';
		$output .= '</div>';
		do_action( 'icl_menu_footer' );
		echo $output . '</div>';
	}

	/**
	 * @return int
	 */
	private function get_items_per_page() {
		$items_per_page = 10;
		if ( $this->screen_options ) {
			$items_per_page = $this->screen_options->get_items_per_page();
		}

		return $items_per_page;
	}
}
