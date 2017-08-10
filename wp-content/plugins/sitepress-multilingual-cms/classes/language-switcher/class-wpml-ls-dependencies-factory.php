<?php

class WPML_LS_Dependencies_Factory {

	/* @var SitePress $sitepress */
	private $sitepress;

	/* @var array $parameters */
	private $parameters;

	/* @var WPML_LS_Templates $templates */
	private $templates;

	/* @var WPML_LS_Slot_Factory $slot_factory */
	private $slot_factory;

	/* @var WPML_LS_Settings $settings */
	private $settings;

	/* @var WPML_LS_Model_Build $model_build */
	private $model_build;

	/* @var WPML_LS_Inline_Styles $inline_styles */
	private $inline_styles;

	/* @var WPML_LS_Render $render */
	private $render;

	/* @var WPML_LS_Admin_UI $admin_ui */
	private $admin_ui;

	/** @var WPML_LS_Shortcodes */
	private $shortcodes;

	/**
	 * WPML_LS_Dependencies_Factory constructor.
	 *
	 * @param SitePress $sitepress
	 * @param array $parameters
	 */
	public function __construct( SitePress $sitepress, array $parameters ) {
		$this->sitepress  = $sitepress;
		$this->parameters = $parameters;
	}

	/**
	 * @return SitePress
	 */
	public function sitepress() {
		return $this->sitepress;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function parameter( $key ) {
		return isset( $this->parameters[ $key ] ) ? $this->parameters[ $key ] : null;
	}

	/**
	 * @return WPML_LS_Templates
	 */
	public function templates() {
		if ( ! $this->templates ) {
			$this->templates = new WPML_LS_Templates();
		}

		return $this->templates;
	}

	/**
	 * @return WPML_LS_Slot_Factory
	 */
	public function slot_factory() {
		if ( ! $this->slot_factory ) {
			$this->slot_factory = new WPML_LS_Slot_Factory();
		}

		return $this->slot_factory;
	}

	/**
	 * @return WPML_LS_Settings
	 */
	public function settings() {
		if ( ! $this->settings ) {
			$this->settings = new WPML_LS_Settings( $this->templates(), $this->sitepress(), $this->slot_factory() );
		}

		return $this->settings;
	}

	/**
	 * @return WPML_LS_Model_Build
	 */
	public function model_build() {
		if ( ! $this->model_build ) {
			$this->model_build = new WPML_LS_Model_Build( $this->settings(), $this->sitepress(), $this->parameter( 'css_prefix' ) );
		}

		return $this->model_build;
	}

	/**
	 * @return WPML_LS_Inline_Styles
	 */
	public function inline_styles() {
		if ( ! $this->inline_styles ) {
			$this->inline_styles = new WPML_LS_Inline_Styles( $this->templates(), $this->settings(), $this->model_build() );
		}

		return $this->inline_styles;
	}

	/**
	 * @return WPML_LS_Render
	 */
	public function render() {
		if ( ! $this->render ) {
			$this->render = new WPML_LS_Render( $this->templates(), $this->settings(), $this->model_build(), $this->inline_styles(), $this->sitepress() );
		}

		return $this->render;
	}

	/**
	 * @return WPML_LS_Admin_UI
	 */
	public function admin_ui() {
		if ( ! $this->admin_ui ) {
			$this->admin_ui = new WPML_LS_Admin_UI( $this->templates(), $this->settings(), $this->render(), $this->inline_styles(), $this->sitepress() );
		}

		return $this->admin_ui;
	}

	/**
	 * @return WPML_LS_Shortcodes
	 */
	public function shortcodes() {
		if ( ! $this->shortcodes ) {
			$this->shortcodes = new WPML_LS_Shortcodes( $this->settings(), $this->render(), $this->sitepress() );
		}

		return $this->shortcodes;
	}

}