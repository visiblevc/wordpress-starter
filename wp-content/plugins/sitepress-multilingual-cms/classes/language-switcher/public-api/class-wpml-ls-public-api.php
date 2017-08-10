<?php
/**
 * Class WPML_LS_Public_API
 */
class WPML_LS_Public_API {

	/** @var WPML_LS_Settings $settings */
	private $settings;

	/** @var WPML_LS_Render $render */
	private $render;

	/** @var SitePress $sitepress */
	protected $sitepress;

	/** @var WPML_LS_Slot_Factory */
	private $slot_factory;

	/**
	 * WPML_LS_Public_API constructor.
	 *
	 * @param WPML_LS_Settings $settings
	 * @param WPML_LS_Render $render
	 * @param SitePress $sitepress
	 */
	public function __construct( WPML_LS_Settings $settings, WPML_LS_Render $render, SitePress $sitepress ) {
		$this->settings  = $settings;
		$this->render    = $render;
		$this->sitepress = $sitepress;
	}

	/**
	 * @param array       $args
	 * @param string|null $content
	 *
	 * @return string
	 */
	protected function render( $args, $content = null ) {
		$defaults_slot_args = $this->get_default_slot_args( $args );
		$slot_args          = array_merge( $defaults_slot_args, $args );

		$slot = $this->get_slot_factory()->get_slot( $slot_args );
		$slot->set( 'show', 1 );
		$slot->set( 'template_string', $content );

		if ( $slot->is_post_translations() ) {
			$output = $this->render->post_translations_label( $slot );
		} else {
			$output = $this->render->render( $slot );
		}

		return $output;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	private function get_default_slot_args( $args ) {
		$type = 'custom';

		if ( isset( $args['type'] ) ) {
			$type = $args['type'];
		}

		switch ( $type ) {
			case 'footer':
				$default_slot = $this->settings->get_slot( 'statics', 'footer' );
				break;

			case 'post_translations':
				$default_slot = $this->settings->get_slot( 'statics', 'post_translations' );
				break;

			case 'widget':
				$default_slot = $this->get_slot_factory()->get_default_slot( 'sidebars' );
				break;

			case 'custom':
			default:
				$default_slot = $this->settings->get_slot( 'statics', 'shortcode_actions' );
				break;
		}

		return $default_slot->get_model();
	}

	/**
	 * @return WPML_LS_Slot_Factory
	 */
	private function get_slot_factory() {
		if ( ! $this->slot_factory ) {
			$this->slot_factory = new WPML_LS_Slot_Factory();
		}

		return $this->slot_factory;
	}
}