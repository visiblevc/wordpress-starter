<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Integrations {
	private $components = array(
		'page-builders' => array(
			'js_composer'    => array(
				'name'     => 'Visual Composer',
				'constant' => 'WPB_VC_VERSION',
			),
			'divi'           => array(
				'name'     => 'Divi',
				'constant' => 'ET_BUILDER_DIR',
			),
			'layouts'        => array(
				'name'     => 'Toolset Layouts',
				'constant' => 'WPDDL_VERSION',
			),
			'x-theme'        => array(
				'name'     => 'X Theme',
				'constant' => 'X_VERSION',
			),
			'enfold'         => array(
				'name'     => 'Enfold',
				'constant' => 'AVIA_FW',
			)/*,
			'avada'          => array(
				'name'     => 'Avada',
				'function' => 'Avada',
			),*/
		),
		'integrations'  => array(
			'woocommerce'  => array(
				'name'  => 'WooCommerce',
				'class' => 'WooCommerce',
			),
			'gravityforms' => array(
				'name'  => 'Gravity Forms',
				'class' => 'GFForms',
			),
			'buddypress'   => array(
				'name'  => 'BuddyPress',
				'class' => 'BuddyPress',
			),
			'bb-plugin'   => array(
				'name'  => 'Beaver Builder Plugin',
				'class' => 'FLBuilderLoader',
			),
		),
	);
	private $items = array();
	private $wpml_wp_api;

	/**
	 * WPML_Integrations constructor.
	 *
	 * @param WPML_WP_API $wpml_wp_api
	 */
	function __construct( WPML_WP_API $wpml_wp_api ) {
		$this->wpml_wp_api         = $wpml_wp_api;
		$this->fetch_items();
	}

	private function fetch_items() {
		foreach ( $this->get_components() as $type => $components ) {
			foreach ( (array) $components as $slug => $data ) {
				if ( $this->component_has_constant( $data ) || $this->component_has_function( $data ) || $this->component_has_class( $data ) ) {
					$this->items[ $slug ]         = array( 'name' => $this->get_component_name( $data ) );
					$this->items[ $slug ]['type'] = $type;
				}
			}
		}
	}

	public function get_results() {
		return $this->items;
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	private function component_has_constant( array $data ) {
		return array_key_exists( 'constant', $data ) && $data['constant'] && $this->wpml_wp_api->defined( $data['constant'] );
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	private function component_has_function( array $data ) {
		return array_key_exists( 'function', $data ) && $data['function'] && function_exists( $data['function'] );
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	private function component_has_class( array $data ) {
		return array_key_exists( 'class', $data ) && $data['class'] && class_exists( $data['class'] );
	}

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	private function get_component_name( array $data ) {
		return $data['name'];
	}

	/**
	 * @return array
	 */
	private function get_components() {
		return apply_filters( 'wpml_integrations_components', $this->components );
	}
}
