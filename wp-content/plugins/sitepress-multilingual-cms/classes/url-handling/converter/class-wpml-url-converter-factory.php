<?php

class WPML_URL_Converter_Factory {
	/**
	 * @var array
	 */
	private $settings;

	/**
	 * @var string
	 */
	private $default_lang_code;

	/**
	 * @var array
	 */
	private $active_language_codes;

	/**
	 * @var WPML_Resolve_Object_Url_Helper_Factory
	 */
	private $object_url_helper_factory;

	const SUBDIR = 1;
	const DOMAIN = 2;

	/**
	 * @param array $settings
	 * @param string $default_lang_code
	 * @param array $active_language_codes
	 */
	public function __construct( $settings, $default_lang_code, $active_language_codes ) {
		$this->settings              = $settings;
		$this->default_lang_code     = $default_lang_code;
		$this->active_language_codes = $active_language_codes;
	}

	/**
	 * @return WPML_Resolve_Object_Url_Helper_Factory
	 */
	public function get_object_url_helper_factory() {
		if ( ! $this->object_url_helper_factory ) {
			$this->object_url_helper_factory = new WPML_Resolve_Object_Url_Helper_Factory();
		}

		return $this->object_url_helper_factory;
	}

	/**
	 * @param WPML_Resolve_Object_Url_Helper_Factory $factory
	 */
	public function set_object_url_helper_factory( WPML_Resolve_Object_Url_Helper_Factory $factory ) {
		$this->object_url_helper_factory = $factory;
	}

	/**
	 * @param int $url_type
	 *
	 * @return WPML_URL_Converter
	 */
	public function create( $url_type ) {
		switch ( $url_type ) {
			case self::SUBDIR:
				$wpml_url_converter = $this->create_subdir_converter();
				break;
			case self::DOMAIN:
				$wpml_url_converter = $this->create_domain_converter();
				break;
			default:
				$wpml_url_converter = $this->create_parameter_converter();
		}

		$home_url = new WPML_URL_Converter_Url_Helper();
		$wpml_url_converter->set_url_helper( $home_url );

		$tax_permalink_filters = new WPML_Tax_Permalink_Filters( $wpml_url_converter );
		$tax_permalink_filters->add_hooks();

		return $wpml_url_converter;
	}

	/**
	 * @return WPML_URL_Cached_Converter
	 */
	private function create_subdir_converter() {
		$dir_default = false;
		if ( ! isset( $this->settings['urls'] ) ) {
			$this->settings['urls'] = array();
		} else {
			if ( isset( $this->settings['urls']['directory_for_default_language'] ) ) {
				$dir_default = $this->settings['urls']['directory_for_default_language'];
			}
		}

		$strategy = new WPML_URL_Converter_Subdir_Strategy( $dir_default, $this->default_lang_code, $this->active_language_codes, $this->settings['urls'] );

		return new WPML_URL_Cached_Converter(
			$strategy,
			$this->get_object_url_helper_factory()->create(),
			$this->default_lang_code,
			$this->active_language_codes
		);
	}

	/**
	 * @return WPML_URL_Cached_Converter
	 */
	private function create_domain_converter() {
		$domains            = isset( $this->settings['language_domains'] ) ? $this->settings['language_domains'] : array();
		$wpml_wp_api        = new WPML_WP_API();
		$strategy = new WPML_URL_Converter_Domain_Strategy( $domains, $this->default_lang_code, $this->active_language_codes );
		$wpml_url_converter = new WPML_URL_Cached_Converter(
			$strategy,
			$this->get_object_url_helper_factory()->create(),
			$this->default_lang_code,
			$this->active_language_codes
		);

		$wpml_fix_url_domain = new WPML_Lang_Domain_Filters(
			$wpml_url_converter,
			$wpml_wp_api,
			new WPML_Debug_BackTrace( $wpml_wp_api->phpversion(), 7 )
		);
		$wpml_fix_url_domain->add_hooks();

		$xdomain_data_parser = new WPML_XDomain_Data_Parser( $this->settings );
		$xdomain_data_parser->init_hooks();

		return $wpml_url_converter;
	}

	/**
	 * @return WPML_URL_Cached_Converter
	 */
	private function create_parameter_converter() {
		$strategy = new WPML_URL_Converter_Parameter_Strategy( $this->default_lang_code, $this->active_language_codes );
		$wpml_url_converter = new WPML_URL_Cached_Converter(
			$strategy,
			$this->get_object_url_helper_factory()->create(),
			$this->default_lang_code,
			$this->active_language_codes
		);

		$filters = new WPML_Lang_Parameter_Filters();
		$filters->add_hooks();

		return $wpml_url_converter;
	}
}
