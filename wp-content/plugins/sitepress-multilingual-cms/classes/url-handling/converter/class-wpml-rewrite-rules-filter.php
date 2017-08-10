<?php

class WPML_Rewrite_Rules_Filter {
	/**
	 * @var array
	 */
	private $active_languages;

	/**
	 * @var WPML_URL_Filters
	 */
	private $wpml_url_filters;

	/**
	 * @param array $active_languages
	 * @param WPML_URL_Filters $wpml_url_filters
	 */
	public function __construct( $active_languages, $wpml_url_filters = null ) {
		$this->active_languages = $active_languages;

		if ( ! $wpml_url_filters ) {
			global $wpml_url_filters;
		}
		$this->wpml_url_filters = $wpml_url_filters;
	}


	/**
	 * @param string $htaccess_string Content of the .htaccess file
	 * @return string .htaccess file contents with adjusted RewriteBase
	 */
	public function rid_of_language_param( $htaccess_string ) {
		if ( $this->wpml_url_filters->frontend_uses_root() ) {
			foreach ( $this->active_languages as $lang_code ) {
				foreach ( array( '', 'index.php' ) as $base ) {
					$htaccess_string = str_replace(
						'/' . $lang_code . '/' . $base,
						'/' . $base,
						$htaccess_string
					);
				}
			}
		}

		return $htaccess_string;
	}
}