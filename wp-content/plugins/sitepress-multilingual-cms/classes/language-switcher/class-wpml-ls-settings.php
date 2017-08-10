<?php

class WPML_LS_Settings {

	const SETTINGS_SLUG = 'wpml_language_switcher';

	/** @var  SitePress $sitepress */
	protected $sitepress;

	/* @var array $settings */
	private $settings;

	/* @var WPML_LS_Templates $templates */
	private $templates;

	/* @var WPML_LS_Slot_Factory $slot_factory */
	private $slot_factory;

	/* @var WPML_LS_Migration $migration */
	private $migration;

	/* @var WPML_LS_Settings_Sanitize $sanitizer */
	private $sanitizer;

	/* @var WPML_LS_Settings_Strings $strings */
	private $strings;

	/* @var WPML_LS_Settings_Color_Presets $color_presets */
	private $color_presets;

	/**
	 * WPML_LS_Settings constructor.
	 *
	 * @param WPML_LS_Templates $templates
	 * @param SitePress                        $sitepress
	 * @param WPML_LS_Slot_Factory             $slot_factory
	 * @param WPML_LS_Migration                $migration
	 */
	public function __construct( $templates, $sitepress, $slot_factory, $migration = null ) {
		$this->templates      = $templates;
		$this->sitepress = &$sitepress;
		$this->slot_factory   = $slot_factory;
		$this->migration      = $migration;
		$this->sanitizer      = new WPML_LS_Settings_Sanitize();
		$this->strings        = new WPML_LS_Settings_Strings( $this->slot_factory );
		$this->color_presets  = new WPML_LS_Settings_Color_Presets();
	}

	public function init_hooks() {
		add_filter( 'widget_update_callback',         array( $this, 'widget_update_callback_filter' ), 10, 4 );
		add_action( 'update_option_sidebars_widgets', array( $this, 'update_option_sidebars_widgets_action' ), 10, 2 );
		add_action( 'wpml_reset_ls_settings',         array( $this, 'reset_ls_settings_action' ) );
	}

	/**
	 * @param array $ls_config
	 */
	public function reset_ls_settings_action( array $ls_config ) {
		$restore_ls_settings = ( isset( $_GET[ 'restore_ls_settings' ] ) && 1 == $_GET[ 'restore_ls_settings' ] );

		if ( ! $this->sitepress->get_setting( 'language_selector_initialized' ) || $restore_ls_settings	) {

			delete_option( self::SETTINGS_SLUG );
			$this->settings = null;

			if ( ! empty( $ls_config ) ) {

				$this->sitepress->set_setting( 'language_selector_initialized', 1 );
				$reset_settings = $this->read_config_settings_recursive( $ls_config[ 'key' ] );

				$converted_settings = $this->migration()->get_converted_settings( $reset_settings );

				if ( isset( $converted_settings['migrated'] ) && 1 === $converted_settings['migrated'] ) {
					$reset_settings = $converted_settings;
				} else {
					$reset_settings['migrated'] = 0;
				}

				$this->save_settings( $reset_settings );
			} else {
				$this->maybe_init_settings();
			}

			if ( $this->sitepress->get_setting( 'setup_complete' ) && $restore_ls_settings) {
				$this->sitepress->get_wp_api()->wp_safe_redirect( $this->get_restore_redirect_url() );
			}
		}
	}

	/**
	 * @return string|void
	 */
	public function get_restore_redirect_url() {
		return admin_url( 'admin.php?page=' . WPML_LS_Admin_UI::get_page_hook() . '&ls_reset=default' );
	}

	/**
	 * @param array $arr
	 *
	 * @return array
	 */
	public function read_config_settings_recursive( $arr ) {
		$ret = array();

		if ( is_array( $arr ) ) {
			foreach ( $arr as $v ) {
				if ( isset( $v['key'] ) && is_array( $v['key'] ) ) {
					$partial = ! is_numeric( key( $v['key'] ) ) ? array( $v['key'] ) : $v['key'];
					$ret[ $v['attr']['name'] ] = $this->read_config_settings_recursive( $partial );
				} else {
					$ret[ $v['attr']['name'] ] = $v['value'];
				}
			}
		}

		return $ret;
	}

	/**
	 * @return string
	 */
	public function get_settings_base_slug() {
		return self::SETTINGS_SLUG;
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		$this->maybe_init_settings();

		return $this->settings;
	}


	/**
	 * @return array
	 */
	public function get_settings_model() {
		$settings = $this->get_settings();

		foreach ( array( 'menus', 'sidebars', 'statics' ) as $group ) {
			$slots = $this->get_setting( $group );
			foreach ( $slots as $slot_slug => $slot ) {
				/* @var WPML_LS_Slot $slot */
				$settings[ $group ][ $slot_slug ] = $slot->get_model();
			}
		}

		return $settings;
	}

	/**
	 * @return array
	 */
	private function get_default_settings() {
		$core_templates = $this->get_core_templates();

		$footer_slot = array(
			'show'                          => 0,
			'display_names_in_current_lang' => 1,
			'template'                      => $core_templates['list-horizontal'],
			'slot_group'                    => 'statics',
			'slot_slug'                     => 'footer',
		);

		$post_translations = array(
			'show'                          => 0,
			'display_names_in_current_lang' => 1,
			'display_before_content'        => 1,
			'availability_text'             => esc_html__( 'This post is also available in: %s', 'sitepress' ),
			'template'                      => $core_templates['post-translations'],
			'slot_group'                    => 'statics',
			'slot_slug'                     => 'post_translations',
		);

		$shortcode_actions = array(
			'show'                          => 1,
			'display_names_in_current_lang' => 1,
			'template'                      => $core_templates['list-horizontal'],
			'slot_group'                    => 'statics',
			'slot_slug'                     => 'shortcode_actions',
		);

		return array(
			'menus'    => array(),
			'sidebars' => array(),
			'statics'  => array(
				'footer'            => $this->slot_factory->get_slot( $footer_slot ),
				'post_translations' => $this->slot_factory->get_slot( $post_translations ),
				'shortcode_actions' => $this->slot_factory->get_slot( $shortcode_actions ),
			),
			'additional_css'   => '',
		);
	}

	/**
	 * @return array
	 */
	private function get_shared_settings_keys() {
		return array(
			// SitePress::settings => WPML_LS_Settings::settings
			'languages_order'              => 'languages_order',
			'icl_lso_link_empty'           => 'link_empty',
			'icl_lang_sel_copy_parameters' => 'copy_parameters',
		);
	}

	private function init_shared_settings() {
		foreach ( $this->get_shared_settings_keys() as $sp_key => $ls_key ) {
			$this->settings[ $ls_key ] = $this->sitepress->get_setting( $sp_key );
		}
	}

	/**
	 * @param $new_settings
	 */
	private function persist_shared_settings( $new_settings ) {
		foreach ( $this->get_shared_settings_keys() as $sp_key => $ls_key ) {
			if( array_key_exists( $ls_key, $new_settings ) ) {
				$this->sitepress->set_setting( $sp_key, $new_settings[ $ls_key ] );
			}
		}

		$this->sitepress->save_settings();
	}

	private function maybe_init_settings() {
		if ( null === $this->settings ) {
			$this->settings = get_option( self::SETTINGS_SLUG );

			if ( ! $this->settings || ! isset( $this->settings['migrated'] ) ) {
				$this->settings   = $this->migration()->get_converted_settings( $this->sitepress->get_settings() );
				$default_settings = $this->get_default_settings();
				$this->settings   = wp_parse_args( $this->settings, $default_settings );
				$this->save_settings( $this->settings );
			}

			if ( ! isset( $this->settings['converted_menu_ids'] ) ) {
				$this->settings = $this->migration()->convert_menu_ids( $this->settings );
				$this->persist_settings( $this->settings );
			}

			$this->init_shared_settings();

			if ( ! $this->sitepress->get_wp_api()->is_admin() ) {
				$this->settings = $this->strings->translate_all( $this->settings );
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_registered_sidebars() {
		global $wp_registered_sidebars;

		return is_array( $wp_registered_sidebars ) ? $wp_registered_sidebars : array();
	}

	/**
	 * @return array
	 */
	public function get_available_menus() {
		$has_term_filter = remove_filter( 'get_term', array( $this->sitepress, 'get_term_adjust_id' ), 1, 1 );

		$ret          = array();
		$default_lang = $this->sitepress->get_default_language();
		$menus        = wp_get_nav_menus( array( 'orderby' => 'name' ) );
		if ( $menus ) {
			foreach ( $menus as $menu ) {
				$menu_details = $this->sitepress->get_element_language_details( $menu->term_taxonomy_id, 'tax_nav_menu' );
				if ( isset( $menu_details->language_code ) && $menu_details->language_code === $default_lang ) {
					$ret[ $menu->term_id ] = $menu;
				}
			}
		}

		if ( $has_term_filter ) {
			add_filter( 'get_term', array( $this->sitepress, 'get_term_adjust_id' ), 1, 1 );
		}

		return $ret;
	}

	/**
	 * @param $new_settings
	 */
	private function persist_settings( $new_settings ) {
		$this->persist_shared_settings( $new_settings );

		if ( null !== $new_settings && count( $new_settings ) > 0 ) {
			update_option( self::SETTINGS_SLUG, $new_settings );
		}
	}

	/**
	 * @param string $slot_group
	 * @param string $slot_slug
	 *
	 * @return WPML_LS_Slot
	 */
	public function get_slot( $slot_group, $slot_slug ) {
		$void_settings = array( 'show' => 0 );
		$groups        = $this->get_settings();
		$slot          = isset( $groups[ $slot_group ][ $slot_slug ] )
			? $groups[ $slot_group ][ $slot_slug ] : $this->slot_factory->get_slot( $void_settings );

		return $slot;
	}

	/**
	 * @param int $term_id
	 *
	 * @return WPML_LS_Slot
	 */
	public function get_menu_settings_from_id( $term_id ) {
		$menu_element = new WPML_Menu_Element( $term_id, $this->sitepress );
		$default_lang = $this->sitepress->get_default_language();

		if ( $menu_element->get_language_code() !== $default_lang ) {
			$nav_menu = $menu_element->get_translation( $default_lang )
				? $menu_element->get_translation( $default_lang )->get_wp_object() : null;

			$term_id = $nav_menu && ! is_wp_error( $nav_menu ) ? $nav_menu->term_id : null;
		}

		return $this->get_slot( 'menus', $term_id );
	}

	/**
	 * @return array
	 */
	public function get_active_slots() {
		$ret = array();

		foreach ( array( 'menus', 'sidebars', 'statics' ) as $group ) {
			$slots = $this->get_setting( $group );
			foreach ( $slots as $slot_slug => $slot ) {
				/* @var WPML_LS_Slot $slot */
				if ( $slot->is_enabled() ) {
					$ret[] = $slot;
				}
			}
		}

		return $ret;
	}

	/**
	 * @return array
	 */
	public function get_active_templates() {
		$ret          = array();
		$active_slots = $this->get_active_slots();

		foreach ( $active_slots as $slot ) {
			/* @var WPML_LS_Slot $slot */
			if ( $slot->is_enabled() ) {
				$ret[] = $slot->template();
			}
		}

		return array_unique( $ret );
	}

	/**
	 * @param string $key
	 *
	 * @return mixed string|array|null
	 */
	public function get_setting( $key ) {
		$this->maybe_init_settings();
		$setting = array_key_exists( $key, $this->settings ) ? $this->settings[ $key ] : null;

		return $setting;
	}

	/**
	 * @param array $new_settings
	 */
	public function save_settings( $new_settings ) {
		$this->maybe_init_settings();
		$new_settings             = $this->sanitizer->sanitize_all_settings( $new_settings );
		$new_settings['menus']    = array_intersect_key( $new_settings['menus'], $this->get_available_menus() );
		$new_settings['sidebars'] = array_intersect_key( $new_settings['sidebars'], $this->get_registered_sidebars() );
		$new_settings             = $this->convert_slot_settings_to_objects( $new_settings );
		$this->strings->register_all( $new_settings, $this->settings );
		$this->synchronize_widget_instances( $new_settings['sidebars'] );
		$this->persist_settings( $new_settings );
		$this->settings = $new_settings;
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function convert_slot_settings_to_objects( array $settings ) {
		foreach ( array( 'menus', 'sidebars', 'statics' ) as $group ) {

			if ( isset( $settings[ $group ] ) ) {

				foreach ( $settings[ $group ] as $slot_slug => $slot_settings ) {

					if ( is_array( $slot_settings ) ) {
						$slot_settings['slot_slug']  = $slot_slug;
						$slot_settings['slot_group'] = $group;
					}

					$settings[ $group ][ $slot_slug ] = $this->slot_factory->get_slot( $slot_settings );
				}
			}
		}

		return $settings;
	}

	/**
	 * @param $sidebar_slots
	 */
	private function synchronize_widget_instances( $sidebar_slots ) {
		require_once( ABSPATH . '/wp-admin/includes/widgets.php' );
		$wpml_ls_widget   = new WPML_LS_Widget();
		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( is_array( $sidebars_widgets ) ) {

			foreach ( $sidebars_widgets as $sidebar => $widgets ) {
				if ( 'wp_inactive_widgets' === $sidebar ) {
					continue;
				}

				$found = false;
				if ( is_array( $widgets ) ) {
					foreach ( $widgets as $key => $widget_id ) {
						if ( strpos( $widget_id, WPML_LS_Widget::SLUG ) === 0 ) {

							if ( $found ) { // Only synchronize the first LS widget instance per sidebar
								unset( $sidebars_widgets[ $sidebar ][ $key ] );
								continue;
							}

							$found = true;

							if ( ! isset( $sidebar_slots[ $sidebar ] ) ) {
								$wpml_ls_widget->delete_instance( $widget_id );
								unset( $sidebars_widgets[ $sidebar ][ $key ] );
							} else {
								$wpml_ls_widget->update_instance( $sidebar_slots[ $sidebar ], $widget_id );
							}
						}
					}
				}

				if ( ! $found ) {

					if ( isset( $sidebar_slots[ $sidebar ] ) ) {
						$new_instance_id = $wpml_ls_widget->create_new_instance( $sidebar_slots[ $sidebar ] );
						$sidebars_widgets[ $sidebar ] = is_array( $sidebars_widgets[ $sidebar ] ) ? $sidebars_widgets[ $sidebar ] : array();
						array_unshift( $sidebars_widgets[ $sidebar ], $new_instance_id );
					}
				}
			}
		}

		$is_hooked = has_action( 'update_option_sidebars_widgets', array( $this, 'update_option_sidebars_widgets_action' ) );

		if ( $is_hooked ) {
			remove_action( 'update_option_sidebars_widgets', array( $this, 'update_option_sidebars_widgets_action' ), 10 );
		}

		wp_set_sidebars_widgets( $sidebars_widgets );

		if ( $is_hooked ) {
			add_action( 'update_option_sidebars_widgets', array( $this, 'update_option_sidebars_widgets_action' ), 10, 2 );
		}
	}

	/**
	 * @param array $old_sidebars
	 * @param array $sidebars
	 */
	public function update_option_sidebars_widgets_action( $old_sidebars, $sidebars ) {
		unset( $sidebars['wp_inactive_widgets'], $sidebars['array_version'] );
		$this->maybe_init_settings();

		if ( is_array( $sidebars ) ) {
			foreach ( $sidebars as $sidebar_slug => $widgets ) {
				$this->synchronize_sidebar_settings( $sidebar_slug, $widgets );
			}
		}

		$this->save_settings( $this->settings );
	}

	/**
	 * @param string $sidebar_slug
	 * @param array $widgets
	 */
	private function synchronize_sidebar_settings( $sidebar_slug, $widgets ) {
		$this->settings['sidebars'][ $sidebar_slug ] = isset( $this->settings['sidebars'][ $sidebar_slug ] )
			? $this->settings['sidebars'][ $sidebar_slug ] : array();

		$widget_id = $this->find_first_ls_widget( $widgets );

		if ( $widget_id === false ) {
			unset( $this->settings['sidebars'][ $sidebar_slug ] );
		} else {
			$instance_number         = str_replace( WPML_LS_Widget::SLUG . '-', '', $widget_id );
			$widget_class_options    = get_option( 'widget_' . WPML_LS_Widget::SLUG );
			$widget_instance_options = isset( $widget_class_options[ $instance_number ] )
				? $widget_class_options[ $instance_number ] : array();

			$this->settings['sidebars'][ $sidebar_slug ] = $this->get_slot_from_widget_instance( $widget_instance_options );
		}
	}

	/**
	 * @param array      $instance
	 * @param array      $new_instance
	 * @param array|null $old_instance
	 * @param WP_Widget  $widget
	 *
	 * @return array
	 */
	public function widget_update_callback_filter( array $instance, array $new_instance, $old_instance, WP_Widget $widget ) {

		if ( strpos( $widget->id_base, WPML_LS_Widget::SLUG ) === 0 ) {
			$sidebar_id = isset( $_POST['sidebar'] ) ? filter_var( $_POST['sidebar'], FILTER_SANITIZE_STRING ) : false;
			$sidebar_id = $sidebar_id ? $sidebar_id : $this->find_parent_sidebar( $widget->id );
			if ( $sidebar_id ) {
				$this->maybe_init_settings();
				if ( isset( $this->settings['sidebars'][ $sidebar_id ] ) ) {
					$this->settings['sidebars'][ $sidebar_id ] = $this->get_slot_from_widget_instance( $instance );
				}

				$this->save_settings( $this->settings );
			}
		}

		return $instance;
	}

	/**
	 * @param array $widget_instance
	 *
	 * @return WPML_LS_Slot
	 */
	private function get_slot_from_widget_instance( $widget_instance ) {
		$slot = isset( $widget_instance['slot'] ) ? $widget_instance['slot'] : array();

		if ( ! is_a( $slot, 'WPML_LS_Sidebar_Slot' ) ) {
			$slot = $this->slot_factory->get_default_slot_arguments( 'sidebars' );
			$slot = $this->slot_factory->get_slot( $slot );
		}

		return $slot;
	}

	/**
	 * Find in which sidebar a language switcher instance is set
	 *
	 * @param string $widget_to_find
	 *
	 * @return bool|string
	 */
	private function find_parent_sidebar( $widget_to_find ) {
		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( is_array( $sidebars_widgets ) ) {
			foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
				if ( is_array( $widgets ) ) {
					foreach ($widgets as $widget) {
						if ($widget_to_find === $widget) {
							return $sidebar_id;
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Find the first language switcher in an array of widgets
	 *
	 * @param array $widgets
	 *
	 * @return string
	 */
	private function find_first_ls_widget( $widgets ) {
		$ret = false;
		$widgets = is_array( $widgets ) ? $widgets : array();
		foreach ( $widgets as $widget_id ) {
			if( strpos( $widget_id, WPML_LS_Widget::SLUG ) === 0 ) {
				$ret = $widget_id;
				break;
			}
		}
		return $ret;
	}

	/**
	 * @return array
	 */
	public function get_ordered_languages() {
		$active_languages = $this->sitepress->get_active_languages();

		foreach ( $active_languages as $code => $language ) {
			$active_languages[ $code ]['flag_url'] = $this->sitepress->get_flag_url( $code );
		}

		return $this->sitepress->order_languages( $active_languages );
	}

	/**
	 * @return array
	 */
	public function get_default_color_schemes() {
		return $this->color_presets->get_defaults();
	}

	/**
	 * @param mixed|null|string $slug
	 *
	 * @return mixed|array|string
	 */
	public function get_core_templates( $slug = null ) {
		$parameters     = WPML_Language_Switcher::parameters();
		$core_templates = isset( $parameters['core_templates'] ) ? $parameters['core_templates'] : array();
		$return         = $core_templates;

		if ( ! empty( $slug ) ) {
			$return = isset( $return[ $slug ] ) ? $return[ $slug ] : null;
		}

		return $return;
	}

	/**
	 * @param string|null $template_slug
	 *
	 * @return bool
	 */
	public function can_load_styles($template_slug = null ) {
		if ( $template_slug ) {
			$template = $this->templates->get_template( $template_slug );
			$can_load = ! ( $template->is_core() && $this->sitepress->get_wp_api()->constant( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' ) );
		} else {
			$can_load = ! $this->sitepress->get_wp_api()->constant( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' );
		}

		return $can_load;
	}

	/**
	 * @param string|null $template_slug
	 *
	 * @return bool
	 */
	public function can_load_script($template_slug = null ) {
		if ( $template_slug ) {
			$template = $this->templates->get_template( $template_slug );
			$can_load = ! ( $template->is_core() && $this->sitepress->get_wp_api()->constant( 'ICL_DONT_LOAD_LANGUAGES_JS' ) );
		} else {
			$can_load = ! $this->sitepress->get_wp_api()->constant( 'ICL_DONT_LOAD_LANGUAGES_JS' );
		}

		return $can_load;
	}

	/**
	 * @return WPML_LS_Migration
	 */
	private function migration() {
		if ( ! $this->migration ) {
			$this->migration = new WPML_LS_Migration( $this, $this->sitepress, $this->slot_factory );
		}

		return $this->migration;
	}
}