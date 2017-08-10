<?php

abstract class WPML_ST_String_Positions {

	const TEMPLATE_PATH = '/templates/string-tracking/';

	/**
	 * @var SitePress $sitepress
	 */
	protected $sitepress;

	/**
	 * @var WPML_ST_DB_Mappers_String_Positions $string_position_mapper
	 */
	protected $string_position_mapper;

	/**
	 * @var WPML_WP_API $wp_api
	 */
	protected $wp_api;

	/**
	 * @var IWPML_Template_Service $template_service
	 */
	protected $template_service;

	/**
	 * @var WP_Filesystem_Direct $filesystem
	 */
	protected $filesystem;

	/**
	 * @var WPML_File_Name_Converter $filename_converter
	 */
	protected $filename_converter;

	public function __construct(
		SitePress $sitePress,
		WPML_ST_DB_Mappers_String_Positions $string_position_mapper = null,
		IWPML_Template_Service $template_service = null,
		WPML_WP_API $wp_api = null
	) {
		$this->sitepress              = $sitePress;
		$this->string_position_mapper = $string_position_mapper;
		$this->template_service       = $template_service;
		$this->wp_api                 = $wp_api;
	}

	/**
	 * @param array  $model
	 * @param string $template_name
	 */
	protected function render( $model, $template_name ) {
		echo $this->get_template_service()->show( $model, $template_name );
	}

	/**
	 * @return WPML_ST_DB_Mappers_String_Positions
	 */
	protected function get_mapper() {
		global $wpdb;

		if ( ! $this->string_position_mapper ) {
			$this->string_position_mapper = new WPML_ST_DB_Mappers_String_Positions( $wpdb );
		}

		return $this->string_position_mapper;
	}

	/**
	 * @return WP_Filesystem_Direct
	 */
	protected function get_filesystem() {
		if ( ! $this->filesystem ) {
			$this->filesystem = $this->get_wp_api()->get_wp_filesystem_direct();
		}

		return $this->filesystem;
	}

	/**
	 * @return WPML_WP_API
	 */
	protected function get_wp_api() {
		if ( ! $this->wp_api ) {
			$this->wp_api = new WPML_WP_API();
		}

		return $this->wp_api;
	}

	/**
	 * @return WPML_File_Name_Converter
	 */
	protected function get_filename_converter() {
		if ( ! $this->filename_converter ) {
			$this->filename_converter = new WPML_File_Name_Converter();
		}

		return $this->filename_converter;
	}

	/**
	 * @return IWPML_Template_Service
	 */
	protected function get_template_service() {
		if ( ! $this->template_service ) {
			$loader = new Twig_Loader_Filesystem( array( WPML_ST_PATH . self::TEMPLATE_PATH ) );

			$options = array();

			if ( WP_DEBUG ) {
				$options['debug'] = true;
			}

			$twig_env               = new Twig_Environment( $loader, $options );
			$this->template_service = new WPML_Twig_Template( $twig_env );
		}

		return $this->template_service;
	}
}