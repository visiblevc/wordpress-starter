<?php

/**
 * Class WPML_LS_Slot
 */
class WPML_LS_Slot {

	/* @var array $properties */
	private $properties = array();

	/* @var array $protected_properties */
	private $protected_properties  = array(
		'slot_group',
		'slot_slug',
	);

	/**
	 * WPML_Language_Switcher_Slot constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args = array() ) {
		$this->set_properties( $args );
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function get( $property ) {
		return isset( $this->properties[ $property ] ) ? $this->properties[ $property ] : null;
	}

	/**
	 * @param string $property
	 * @param mixed  $value
	 */
	public function set( $property, $value ) {
		if ( ! in_array( $property, $this->protected_properties ) ) {
			$allowed_properties = $this->get_allowed_properties();
			if ( array_key_exists( $property, $allowed_properties ) ) {
				$meta_data = $allowed_properties[ $property ];
				$this->properties[ $property ] = $this->sanitize( $value, $meta_data );
			}
		}
	}

	/**
	 * @return mixed|string|null
	 */
	public function group() {
		return $this->get( 'slot_group' );
	}

	/**
	 * @return mixed|string|null
	 */
	public function slug() {
		return $this->get( 'slot_slug' );
	}

	/**
	 * @return bool
	 */
	public function is_menu() {
		return $this->group() === 'menus';
	}

	/**
	 * @return bool
	 */
	public function is_sidebar() {
		return $this->group() === 'sidebars';
	}

	/**
	 * @return bool
	 */
	public function is_footer() {
		return $this->group() === 'statics' && $this->slug() === 'footer';
	}

	/**
	 * @return bool
	 */
	public function is_post_translations() {
		return $this->group() === 'statics' && $this->slug() === 'post_translations';
	}

	/**
	 * @return bool
	 */
	public function is_shortcode_actions() {
		return $this->group() === 'statics' && $this->slug() === 'shortcode_actions';
	}

	/**
	 * @return bool
	 */
	public function is_enabled() {
		return $this->get( 'show' ) ? true : false;
	}

	/**
	 * @return mixed
	 */
	public function template() {
		return $this->get( 'template' );
	}

	/**
	 * @return mixed
	 */
	public function template_string() {
		return $this->get( 'template_string' );
	}

	/**
	 * @param array $args
	 */
	private function set_properties( array $args ) {
		foreach ( $this->get_allowed_properties() as $allowed_property => $meta_data ) {
			$value = isset( $args[ $allowed_property ] ) ? $args[ $allowed_property ] : null;
			$this->properties[ $allowed_property ] = $this->sanitize( $value, $meta_data );
		}
	}

	/**
	 * @return array
	 */
	protected function get_allowed_properties() {
		return array(
			'slot_group'                    => array( 'type' => 'string', 'force_missing_to' => '' ),
			'slot_slug'                     => array( 'type' => 'string', 'force_missing_to' => '' ),
			'show'                          => array( 'type' => 'int', 'force_missing_to' => 0 ),
			'template'                      => array( 'type' => 'string' ),
			'display_flags'                 => array( 'type' => 'int', 'force_missing_to' => 0 ),
			'display_link_for_current_lang' => array( 'type' => 'int', 'force_missing_to' => 0 ),
			'display_names_in_native_lang'  => array( 'type' => 'int', 'force_missing_to' => 0 ),
			'display_names_in_current_lang' => array( 'type' => 'int', 'force_missing_to' => 0 ),
			// Colors
			'background_normal'             => array( 'type' => 'string' ),
			'border_normal'                 => array( 'type' => 'string' ),
			'font_current_normal'           => array( 'type' => 'string' ),
			'font_current_hover'            => array( 'type' => 'string' ),
			'background_current_normal'     => array( 'type' => 'string' ),
			'background_current_hover'      => array( 'type' => 'string' ),
			'font_other_normal'             => array( 'type' => 'string' ),
			'font_other_hover'              => array( 'type' => 'string' ),
			'background_other_normal'       => array( 'type' => 'string' ),
			'background_other_hover'        => array( 'type' => 'string' ),
			'template_string'               => array( 'type' => 'string', 'twig_string' => 1 ),
		);
	}

	/**
	 * @param mixed $value
	 * @param array $meta_data
	 *
	 * @return mixed
	 */
	private function sanitize( $value, array $meta_data ) {
		if ( ! is_null( $value ) ) {
			switch( $meta_data['type'] ) {
				case 'string':
					$value = (string) $value;
					if ( array_key_exists( 'stripslashes', $meta_data ) && $meta_data['stripslashes'] ) {
						$value = stripslashes( $value );
					}
					if ( array_key_exists( 'twig_string', $meta_data ) ) {
						$value = preg_replace( '/<br\W*?\/>/', '', $value );
					} else {
						$value = sanitize_text_field( $value );
					}
					break;
				case 'int':
					$value = (int) $value;
					break;
			}
		} elseif ( array_key_exists( 'force_missing_to', $meta_data ) ) {
			$value = $meta_data['force_missing_to'];
		}

		return $value;
	}

	/**
	 * The use of a plain object does not work in Twig
	 * e.g: slot_settings[ option.name ~ "_normal" ] (see in panel-colors.twig)
	 *
	 * @return array
	 */
	public function get_model() {
		$model = array();

		foreach ( $this->properties as $property => $value ) {
			$model[ $property ] = $value;
		}

		return $model;
	}

	/**
	 * @param string $slug
	 *
	 * @return string|null
	 */
	protected function get_core_template( $slug ) {
		$parameters     = WPML_Language_Switcher::parameters();
		$core_templates = isset( $parameters['core_templates'] ) ? $parameters['core_templates'] : array();
		return isset( $core_templates[ $slug ] ) ? $core_templates[ $slug ] : null;
	}
}