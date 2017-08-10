<?php

class WPML_LS_Slot_Factory {

	/**
	 * @param array|WPML_LS_Slot $args
	 *
	 * @return WPML_LS_Slot
	 */
	public function get_slot( $args ) {

		if ( is_array( $args ) ) {

			$args['slot_group'] = isset( $args['slot_group'] ) ? $args['slot_group'] : null;
			$args['slot_slug']  = isset( $args['slot_slug'] ) ? $args['slot_slug'] : null;
			$slot               = new WPML_LS_Slot( $args );

			switch ( $args['slot_group'] ) {
				case 'menus':
					$slot = new WPML_LS_Menu_Slot( $args );
					break;

				case 'sidebars':
					$slot = new WPML_LS_Sidebar_Slot( $args );
					break;

				case 'statics':
					switch ( $args['slot_slug'] ) {
						case 'footer':
							$slot = new WPML_LS_Footer_Slot( $args );
							break;

						case 'post_translations':
							$slot = new WPML_LS_Post_Translations_Slot( $args );
							break;

						case 'shortcode_actions':
							$slot = new WPML_LS_Shortcode_Actions_Slot( $args );
							break;
					}
					break;
			}

		} else {
			$slot = $args;
		}

		return $slot;
	}

	/**
	 * @param string $slot_group
	 *
	 * @return array
	 */
	public function get_default_slot_arguments( $slot_group ) {
		$args = array(
			'slot_group' => $slot_group,
			'display_link_for_current_lang' => 1,
			'display_names_in_native_lang'  => 1,
			'display_names_in_current_lang' => 1,
		);

		if ( $slot_group === 'menus' ) {
			$args['template']        = $this->get_core_templates( 'menu-item' );
			$args['is_hierarchical'] = 1;
		} else if ( $slot_group === 'sidebars' ) {
			$args['template'] = $this->get_core_templates( 'dropdown' );
		}

		return $args;
	}

	/**
	 * @param string $slot_group
	 *
	 * @return WPML_LS_Slot
	 */
	public function get_default_slot( $slot_group ) {
		$slot_args = $this->get_default_slot_arguments( $slot_group );
		return $this->get_slot( $slot_args );
	}

	/**
	 * @param string $slug
	 *
	 * @return string|null
	 */
	public function get_core_templates( $slug ) {
		$parameters = WPML_Language_Switcher::parameters();
		$templates  = isset( $parameters['core_templates'] ) ? $parameters['core_templates'] : array();
		return isset( $templates[ $slug ] ) ? $templates[ $slug ] : null;
	}
}