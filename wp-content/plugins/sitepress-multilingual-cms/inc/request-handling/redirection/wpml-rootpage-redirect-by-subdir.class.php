<?php
require_once ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-redirect-by-subdir.class.php';

class WPML_Rootpage_Redirect_By_Subdir extends WPML_Redirect_By_Subdir {

	/** @var array $urls */
	private $urls;

	/**
	 * @param  array                   $urls
	 * @param  WPML_Request            $request_handler
	 * @param  WPML_URL_Converter      $url_converter
	 * @param WPML_Language_Resolution $lang_resolution
	 *
	 */
	public function __construct( $urls, &$request_handler, &$url_converter, &$lang_resolution ) {
		parent::__construct( $url_converter, $request_handler, $lang_resolution );
		$this->urls = $urls;
	}

	public function get_redirect_target() {
		global $wpml_url_filters;

		$request_uri = $this->request_handler->get_request_uri();

		$target = parent::get_redirect_target();
		$target = $target
			? $target
			: ( ( $filtered_root_url = $wpml_url_filters->filter_root_permalink(
				wpml_strip_subdir_from_url( site_url() ) . $request_uri
			) ) !== wpml_strip_subdir_from_url( site_url() ) . $request_uri ? $filtered_root_url : false );

		if ( $target === false ) {
			$this->maybe_setup_rootpage();
		}

		return $target;
	}

	private function maybe_setup_rootpage() {
		if ( WPML_Root_Page::is_current_request_root() ) {
			if ( WPML_Root_Page::uses_html_root() ) {
				$html_file = ( false === strpos( $this->urls['root_html_file_path'], '/' ) ? ABSPATH : '' )
				             . $this->urls['root_html_file_path'];

				/** @noinspection PhpIncludeInspection */
				include $html_file;
				exit;
			} else {
				$root_page_actions = wpml_get_root_page_actions_obj();
				$root_page_actions->wpml_home_url_setup_root_page();
			}
		}
	}
}
