<?php
/**
 * @author OnTheGo Systems
 */
class WPML_Requirements {
	private $active_plugins       = array();
	private $missing_requirements = array();

	private $plugins = array(
		'wpml-media-translation'     => array(
			'version' => '2.1.24',
			'name'    => 'WPML Media Translation',
		),
		'wpml-string-translation'     => array(
			'version' => '2.5.2',
			'name'    => 'WPML String Translation',
		),
		'wpml-translation-management' => array(
			'version' => '2.2.7',
			'name'    => 'WPML Translation Management',
		),
		'woocommerce-multilingual'    => array(
			'version' => '2.5.2',
			'name'    => 'WooCommerce Multilingual',
			'url'     => '#',
		),
		'gravityforms-multilingual'   => array(
			'name' => 'GravityForms Multilingual',
			'url'  => '#',
		),
		'buddypress-multilingual'     => array(
			'name' => 'BuddyPress Multilingual',
			'url'  => '#',
		),
		'wpml-page-builders'          => array(
			'name' => 'WPML Page Builders',
			'url'  => '#',
		),
	);

	private $modules = array(
		'page-builders' => array(
			'url'          => 'https://wpml.org/documentation/translating-your-contents/page-builders/',
			'requirements' => array(
				'wpml-string-translation',
				'wpml-translation-management',
			),
		),
		'woocommerce'   => array(
			'url'          => '#',
			'requirements' => array(
				'woocommerce-multilingual',
				'wpml-translation-management',
				'wpml-string-translation',
				'wpml-media-translation',
			),
		),
		'gravityforms'  => array(
			'url'          => '#',
			'requirements' => array(
				'gravityforms-multilingual',
				'wpml-string-translation',
			),
		),
		'buddypress'    => array(
			'url'          => '#',
			'requirements' => array(
				'buddypress-multilingual',
			),
		),
		'bb-plugin'    => array(
			'url'          => '#',
			'requirements' => array(
				'wpml-page-builders',
			),
		),
	);

	/**
	 * WPML_Requirements constructor.
	 */
	public function __construct() {
		if ( function_exists( 'get_plugins' ) ) {
			$installed_plugins = get_plugins();
			foreach ( $installed_plugins as $plugin_file => $plugin_data ) {
				if ( is_plugin_active( $plugin_file ) ) {
					$plugin_slug                          = $this->get_plugin_slug( $plugin_data );
					$this->active_plugins[ $plugin_slug ] = $plugin_data;
				}
			}
		}
	}

	/**
	 * @param array $plugin_data
	 *
	 * @return string|null
	 */
	public function get_plugin_slug( array $plugin_data ) {
		$plugin_slug = null;
		if ( array_key_exists( 'Plugin Slug', $plugin_data ) && $plugin_data['Plugin Slug'] ) {
			$plugin_slug = $plugin_data['Plugin Slug'];
		} elseif ( array_key_exists( 'TextDomain', $plugin_data ) && $plugin_data['TextDomain'] ) {
			$plugin_slug = $plugin_data['TextDomain'];
		} elseif ( array_key_exists( 'Name', $plugin_data ) && $plugin_data['Name'] ) {
			$plugin_slug = $plugin_data['Name'];
		}

		return $plugin_slug;
	}

	/**
	 * @return array
	 */
	public function get_missing_requirements() {
		return $this->missing_requirements;
	}

	/**
	 * @param string $type
	 * @param string $slug
	 *
	 * @return array
	 */
	public function get_requirements( $type, $slug ) {
		$missing_plugins = $this->get_missing_plugins_for_type( $type, $slug );

		$requirements = array();

		if ( $missing_plugins ) {
			foreach ( $this->get_components_requirements_by_type( $type, $slug ) as $plugin_slug ) {
				$requirement            = $this->get_plugin_data( $plugin_slug );
				$requirement['missing'] = false;
				if ( in_array( $plugin_slug, $missing_plugins, true ) ) {
					$requirement['missing']       = true;
					$this->missing_requirements[] = $requirement;
				}
				$requirements[] = $requirement;
			}
		}

		return $requirements;
	}

	/**
	 * @param string $slug
	 *
	 * @return array
	 */
	function get_plugin_data( $slug ) {
		if ( array_key_exists( $slug, $this->plugins ) ) {
			return $this->plugins[ $slug ];
		}

		return array();
	}

	/**
	 * @param string $type
	 * @param string $slug
	 *
	 * @return array
	 */
	private function get_missing_plugins_for_type( $type, $slug ) {
		$requirements_keys   = $this->get_components_requirements_by_type( $type, $slug );
		$active_plugins_keys = array_keys( $this->active_plugins );

		return array_diff( $requirements_keys, $active_plugins_keys );
	}

	/**
	 * @return array
	 */
	private function get_components() {
		return apply_filters( 'wpml_requirements_components', $this->modules );
	}

	/**
	 * @param string $type
	 * @param string $slug
	 *
	 * @return array
	 */
	private function get_components_by_type( $type, $slug ) {
		$components = $this->get_components();
		if ( array_key_exists( $type, $components ) ) {
			return $components[ $type ];
		} elseif ( array_key_exists( $slug, $components ) ) {
			return $components[ $slug ];
		}

		return array();
	}

	/**
	 * @param string $type
	 * @param string $slug
	 *
	 * @return array
	 */
	private function get_components_requirements_by_type( $type, $slug ) {
		$components_requirements = $this->get_components_by_type( $type, $slug );
		if ( array_key_exists( 'requirements', $components_requirements ) ) {
			return $components_requirements['requirements'];
		}

		return array();
	}
}
