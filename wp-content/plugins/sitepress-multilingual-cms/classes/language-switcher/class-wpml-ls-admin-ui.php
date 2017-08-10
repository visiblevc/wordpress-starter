<?php

class WPML_LS_Admin_UI extends WPML_Templates_Factory {

	const NONCE_NAME = 'wpml-language-switcher-admin';
	const MAIN_UI_TEMPLATE = 'layout-main.twig';
	const RESET_UI_TEMPLATE = 'layout-reset.twig';
	const BUTTON_TEMPLATE = 'layout-slot-edit-button.twig';
	const SLOT_SLUG_PLACEHOLDER = '%id%';

	/* @var WPML_LS_Templates $templates */
	private $templates;

	/* @var WPML_LS_Settings $settings */
	private $settings;

	/* @var WPML_LS_Render $render */
	private $render;

	/* @var WPML_LS_Inline_Styles $inline_styles */
	private $inline_styles;

	/* @var WPML_LS_Assets $assets */
	private $assets;

	/* @var SitePress $sitepress */
	private $sitepress;

	/**
	 * WPML_Language_Switcher_Menu constructor.
	 *
	 * @param WPML_LS_Templates $templates
	 * @param WPML_LS_Settings $settings
	 * @param WPML_LS_Render $render
	 * @param WPML_LS_Inline_Styles $inline_styles
	 * @param SitePress $sitepress
	 * @param WPML_LS_Assets $assets
	 */
	public function __construct( $templates, $settings, $render, $inline_styles, $sitepress, $assets = null ) {
		$this->templates     = $templates;
		$this->settings      = $settings;
		$this->render        = $render;
		$this->inline_styles = $inline_styles;
		$this->assets        = $assets ? $assets : new WPML_LS_Assets( $this->templates, $this->settings );
		$this->sitepress     = $sitepress;
		parent::__construct();
	}

	public function init_hooks() {
		add_action( 'wpml_admin_languages_navigation_items', array( $this, 'languages_navigation_items_filter' ) );
		add_action( 'wpml_admin_after_languages_url_format', array( $this, 'after_languages_url_format_action' ) );
		add_action( 'wpml_admin_after_wpml_love', array( $this, 'after_wpml_love_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_action' ) );
		add_action( 'admin_head', array( $this, 'admin_head_action' ) );
		add_action( 'wp_ajax_wpml-ls-save-settings', array( $this, 'save_settings_action' ) );
		add_action( 'wp_ajax_wpml-ls-update-preview', array( $this, 'update_preview_action' ) );
	}

	/**
	 * @return string
	 */
	public static function get_page_hook() {
		return ICL_PLUGIN_FOLDER . '/menu/languages.php';
	}

	/**
	 * @param string $hook
	 */
	public function admin_enqueue_scripts_action( $hook ) {
		if ( self::get_page_hook() === $hook ) {
			$suffix = $this->sitepress->get_wp_api()->constant( 'SCRIPT_DEBUG' ) ? '' : '.min';

			wp_register_script(
				'wpml-language-switcher-settings', ICL_PLUGIN_URL . '/res/js/language-switchers-settings' . $suffix . '.js',
				array( 'jquery', 'wp-util', 'jquery-ui-sortable', 'jquery-ui-dialog', 'wp-color-picker', 'wp-pointer' )
			);
			wp_enqueue_script( 'wpml-language-switcher-settings' );

			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_style( 'wp-color-picker' );

			wp_register_style(
				'wpml-language-switcher-settings',
				ICL_PLUGIN_URL . '/res/css/wpml-language-switcher-settings.css'
			);
			wp_enqueue_style( 'wpml-language-switcher-settings' );

			$js_vars = array(
				'nonce'         => wp_create_nonce( self::NONCE_NAME ),
				'menus'         => $this->settings->get_available_menus(),
				'sidebars'      => $this->settings->get_registered_sidebars(),
				'color_schemes' => $this->settings->get_default_color_schemes(),
				'strings'       => $this->get_javascript_strings(),
				'templates'     => $this->templates->get_all_templates_data(),
			);

			wp_localize_script( 'wpml-language-switcher-settings', 'wpml_language_switcher_admin', $js_vars );

			$this->assets->wp_enqueue_scripts_action();
		}
	}

	public function admin_head_action() {
		$this->inline_styles->admin_output();
	}

	public function save_settings_action() {
		if ( $this->has_valid_nonce() && isset( $_POST['settings'] ) ) {
			$new_settings = $this->parse_request_settings( 'settings' );
			$this->settings->save_settings( $new_settings );
			$this->maybe_complete_setup_wizard_step( $new_settings );
			$this->sitepress->get_wp_api()->wp_send_json_success( esc_html__( 'Settings saved', 'sitepress' ) );
		} else {
			$this->sitepress->get_wp_api()->wp_send_json_error( esc_html__( "You can't do that!", 'sitepress' ) );
		}
	}

	/**
	 * @param array $new_settings
	 */
	private function maybe_complete_setup_wizard_step( $new_settings ) {
		if ( isset( $new_settings['submit_setup_wizard'] ) && $new_settings['submit_setup_wizard'] == 1 ) {
			$setup_instance = wpml_get_setup_instance();
			$setup_instance->finish_step3();
		}
	}

	public function update_preview_action() {
		$preview = false;

		if ( $this->has_valid_nonce() ) {
			$settings = $this->parse_request_settings( 'slot_settings' );

			foreach ( array( 'menus', 'sidebars', 'statics' ) as $group ) {
				if ( isset( $settings[ $group ] ) ) {
					$slot_slug                                      = key( $settings[ $group ] );

					if ( preg_match( '/' . self::SLOT_SLUG_PLACEHOLDER . '/', $slot_slug ) ) {
						$new_slug = preg_replace( '/' . self::SLOT_SLUG_PLACEHOLDER . '/', '__id__', $slot_slug );
						$settings[ $group ][ $new_slug ] = $settings[ $group ][ $slot_slug ];
						unset( $settings[ $group ][ $slot_slug ] );
						$slot_slug = $new_slug;
					}

					$settings[ $group ][ $slot_slug ]['slot_slug']  = $slot_slug;
					$settings[ $group ][ $slot_slug ]['slot_group'] = $group;
					$settings                                       = $this->settings->convert_slot_settings_to_objects( $settings );
					$slot                                           = $settings[ $group ][ $slot_slug ];
					$preview                                        = $this->render->get_preview( $slot );
					$this->sitepress->get_wp_api()->wp_send_json_success( $preview );
				}
			}
		}

		if ( ! $preview ) {
			$this->sitepress->get_wp_api()->wp_send_json_error( esc_html__( 'Preview update failed', 'sitepress' ) );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null|string
	 */
	private function parse_request_settings( $key ) {
		$settings = array_key_exists( $key, $_POST ) ? $_POST[ $key ] : null;
		$settings = filter_var( $settings, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
		$settings = urldecode( $settings );
		parse_str( $settings, $settings_array );
		return $settings_array;
	}

	/**
	 * @return bool
	 */
	private function has_valid_nonce() {
		$nonce = filter_var( $_POST['nonce'], FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		return (bool) wp_verify_nonce( $nonce, self::NONCE_NAME );
	}

	/**
	 * @param array $items
	 *
	 * @return array
	 */
	public function languages_navigation_items_filter( $items ) {
		$item_to_insert  = array( '#wpml-ls-settings-form' => esc_html__( 'Language switcher options', 'sitepress' ) );
		$insert_position = array_search( '#lang-sec-2', array_keys( $items ), true ) + 1;

		$items = array_merge( array_slice( $items, 0, $insert_position ), $item_to_insert, array_slice( $items, $insert_position ) );

		$items['#wpml_ls_reset'] = esc_html__( 'Reset settings', 'sitepress' );

		return $items;
	}

	public function after_languages_url_format_action() {
		$setup_wizard_step = (int) $this->sitepress->get_setting( 'setup_wizard_step' );
		$setup_complete    = $this->sitepress->get_setting( 'setup_complete' );
		$active_languages  = $this->sitepress->get_active_languages();

		if ( 3 === $setup_wizard_step || ( ! empty( $setup_complete ) && count( $active_languages ) > 1 ) ) {
			$this->show( self::MAIN_UI_TEMPLATE, $this->get_main_ui_model() );
		}
	}

	/**
	 * @param string|bool $theme_wpml_config_file
	 */
	public function after_wpml_love_action( $theme_wpml_config_file ) {
		$setup_complete   = $this->sitepress->get_setting( 'setup_complete' );
		$active_languages = $this->sitepress->get_active_languages();

		if ( $setup_complete && count( $active_languages ) > 1 ) {
			$this->show( self::RESET_UI_TEMPLATE, $this->get_reset_ui_model( $theme_wpml_config_file ) );
		}
	}

	/**
	 * @param string     $type 'sidebars', 'menus', 'statics'
	 * @param string|int $slug_or_id
	 *
	 * @return string
	 */
	public function get_button_to_edit_slot( $type, $slug_or_id ) {
		$slug = $slug_or_id;

		if ( 'menus' === $type ) {
			$menu = wp_get_nav_menu_object( $slug_or_id );
			$slug = isset( $menu->term_id ) ? $menu->term_id : null;
		}

		$slot = $this->settings->get_slot( $type, $slug );
		$url  = admin_url( 'admin.php?page=' . WPML_LS_Admin_UI::get_page_hook() . '#' . $type . '/' . $slug );

		if ( $slot->slug() === $slug ) {
			$model = array(
				'action' => 'edit',
				'url'    => $url,
				'label'  => __( 'Customize the language switcher', 'sitepress' ),
			);
		} else {
			$model = array(
				'action' => 'add',
				'url'    => $url,
				'label'  => __( 'Add a language switcher', 'sitepress' ),
			);
		}

		return $this->get_view( self::BUTTON_TEMPLATE, $model );
	}

	protected function init_template_base_dir() {
		$this->template_paths = array(
			ICL_PLUGIN_PATH . '/templates/language-switcher-admin-ui/',
		);
	}

	/**
	 * @return string
	 */
	public function get_template() {
		return self::MAIN_UI_TEMPLATE;
	}

	/**
	 * @return array
	 */
	private function get_all_previews() {
		$previews = array();

		foreach ( array( 'menus', 'sidebars', 'statics' ) as $slot_group ) {

			$previews[ $slot_group ] = array();

			foreach ( $this->settings->get_setting( $slot_group ) as $slug => $slot_settings ) {
				$prev = $this->render->get_preview( $slot_settings );

				foreach ( array( 'html', 'css', 'js', 'styles' ) as $preview_part ) {
					$previews[ $slot_group ][ $slug ][ $preview_part ] = $prev[ $preview_part ];
				}
			}
		}

		return $previews;
	}

	/**
	 * This method is compulsory but should not be used
	 * Use "get_main_ui_model" and "get_reset_ui_model" instead
	 *
	 * @return array
	 */
	public function get_model() {
		return array();
	}

	/**
	 * @return array
	 */
	public function get_main_ui_model() {
		$slot_factory = new WPML_LS_Slot_Factory();

		$model = array(
			'strings'                  => array(
				'misc'              => $this->get_misc_strings(),
				'tooltips'          => $this->get_tooltip_strings(),
				'color_picker'      => $this->get_color_picker_strings(),
				'options'           => $this->get_options_section_strings(),
				'menus'             => $this->get_menus_section_strings(),
				'sidebars'          => $this->get_sidebars_section_strings(),
				'footer'            => $this->get_footer_section_strings(),
				'post_translations' => $this->get_post_translations_strings(),
				'shortcode_actions' => $this->get_shortcode_actions_strings(),
			),
			'data'                     => array(
				'templates' => $this->templates->get_templates(),
				'menus'     => $this->settings->get_available_menus(),
				'sidebars'  => $this->settings->get_registered_sidebars(),
			),
			'ordered_languages'        => $this->settings->get_ordered_languages(),
			'settings'                 => $this->settings->get_settings_model(),
			'settings_slug'            => $this->settings->get_settings_base_slug(),
			'previews'                 => $this->get_all_previews(),
			'color_schemes'            => $this->settings->get_default_color_schemes(),
			'notifications'            => $this->get_notifications(),
			'default_menus_slot'       => $slot_factory->get_default_slot_arguments( 'menus' ),
			'default_sidebars_slot'    => $slot_factory->get_default_slot_arguments( 'sidebars' ),
			'setup_complete'           => $this->sitepress->get_setting( 'setup_complete' ),
			'setup_step_2_nonce_field' => wp_nonce_field( 'setup_got_to_step2_nonce', '_icl_nonce_gts2', true, false ),
		);

		return $model;
	}

	/**
	 * @return array
	 */
	public function get_misc_strings() {
		return array(
			'no_templates'               => __( 'There are no templates available.', 'sitepress' ),
			'label_preview'              => _x( 'Preview', 'Language switcher preview', 'sitepress' ),
			'label_position'             => _x( 'Position', 'Language switcher preview', 'sitepress' ),
			'label_actions'              => _x( 'Actions', 'Language switcher preview', 'sitepress' ),
			'label_actions'              => _x( 'Actions', 'Language switcher preview', 'sitepress' ),
			'label_action'               => _x( 'Action', 'Language switcher preview', 'sitepress' ),
			'button_save'                => __( 'Save', 'sitepress' ),
			'button_cancel'              => __( 'Cancel', 'sitepress' ),
			'title_what_to_include'      => __( 'What to include in the language switcher:', 'sitepress' ),
			'label_include_flag'         => __( 'Flag', 'sitepress' ),
			'label_include_native_lang'  => __( 'Native language name', 'sitepress' ),
			'label_include_display_lang' => __( 'Language name in current language', 'sitepress' ),
			'label_include_current_lang' => __( 'Current language', 'sitepress' ),
			'templates_dropdown_label'   => __( 'Language switcher style:', 'sitepress' ),
			'templates_wpml_group'       => __( 'WPML', 'sitepress' ),
			'templates_custom_group'     => __( 'Custom', 'sitepress' ),
			'title_action_edit'          => __( 'Edit language switcher', 'sitepress' ),
			'title_action_delete'        => __( 'Delete language switcher', 'sitepress' ),
			'button_back'                => __( 'Back', 'sitepress' ),
			'button_next'                => __( 'Next', 'sitepress' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_tooltip_strings() {
		return array(
			'languages_order'               => array(
				'text' => __( 'This is the order in which the languages will be displayed in the language switcher.', 'sitepress' ),
			),
			'languages_without_translation' => array(
				'text' => __( 'Some content may not be translated to all languages. Choose what should appear in the language switcher when translation is missing.', 'sitepress' ),
			),
			'preserve_url_arguments'        => array(
				'text' => __( 'Add a comma-separated list of URL arguments that you want WPML to pass when switching languages.', 'sitepress' ),
				'link' => array(
					'text'   => __( 'Preserving URL arguments', 'sitepress' ),
					'url'    => 'https://wpml.org/documentation/getting-started-guide/language-setup/language-switcher-options/#preserving-url-parameters',
					'target' => '_blank',
				),
			),
			'additional_css'                => array(
				'text' => __( 'Enter CSS to add to the page. This is useful when you want to add styling to the language switcher, without having to edit the CSS file on the server.', 'sitepress' ),
				'link' => array(
					'text'   => __( 'Styling the language switcher with additional CSS', 'sitepress' ),
					'url'    => 'https://wpml.org/documentation/getting-started-guide/language-setup/language-switcher-options/#styling-the-language-switcher-with-additional-css',
					'target' => '_blank',
				),
			),
			'section_post_translations'     => array(
				'text' => __( 'You can display links to translation of posts before the post and after it. These links look like "This post is also available in..."', 'sitepress' ),
			),
			'add_menu_all_assigned'         => array(
				'text' => __( 'The button is disabled because all existing menus have language switchers. You can edit the settings of the existing language switchers.', 'sitepress' ),
			),
			'add_menu_no_menu'              => array(
				'text' => __( 'The button is disabled because there are no menus in the site. Add a menu and you can later enable a language switcher in it.', 'sitepress' ),
			),
			'add_sidebar_all_assigned'      => array(
				'text' => __( 'The button is disabled because all existing widget areas have language switchers. You can edit the settings of the existing language switchers.', 'sitepress' ),
			),
			'add_sidebar_no_sidebar'        => array(
				'text' => __( 'The button is disabled because there are no registered widget areas in the site.', 'sitepress' ),
			),
			'what_to_include'               => array(
				'text' => __( 'Elements to include in the language switcher.', 'sitepress' ),
			),
			'available_menus'               => array(
				'text' => __( 'Select the menus, in which to display the language switcher.', 'sitepress' ),
			),
			'available_sidebars'            => array(
				'text' => __( 'Select the widget area where to include the language switcher.', 'sitepress' ),
			),
			'available_templates'           => array(
				'text' => __( 'Select the style of the language switcher.', 'sitepress' ),
			),
			'menu_style_type'               => array(
				'text' => __( 'Select how to display the language switcher in the menu. Choose "List of languages" to display all the items at the same level or "Dropdown" to display the current language as parent and other languages as children.', 'sitepress' ),
			),
			'menu_position'                 => array(
				'text' => __( 'Select the position to display the language switcher in the menu.', 'sitepress' ),
			),
			'widget_title'                  => array(
				'text' => __( 'Enter the title of the widget or leave empty for no title.', 'sitepress' ),
			),
			'post_translation_position'     => array(
				'text' => __( 'Select the position to display the post translations links.', 'sitepress' ),
			),
			'alternative_languages_text'    => array(
				'text' => __( 'This text appears before the list of languages. Your text needs to include the string %s which is a placeholder for the actual links.', 'sitepress' ),
			),
			'backwards_compatibility'       => array(
				'text' => __( "Since WPML 3.6.0, the language switchers are not using CSS IDs and the CSS classes have changed. This was required to fix some bugs and match the latest standards. If your theme or your custom CSS is not relying on these old selectors, it's recommended to skip the backwards compatibility. However, it's still possible to re-activate this option later.", 'sitepress' ),
			),
			'show_in_footer'       => array(
				'text' => __( "You can display a language switcher in the site's footer. You can customize and style it here.", 'sitepress' ),
			),
		);
	}

	/**
	 * @return array
	 */
	public function get_options_section_strings() {
		return array(
			'section_title'                        => __( 'Language switcher options', 'sitepress' ),
			'section_description'                  => __( 'All language switchers in your site are affected by the settings in this section.', 'sitepress' ),
			'label_language_order'                 => __( 'Order of languages', 'sitepress' ),
			'tip_drag_languages'                   => __( 'Drag and drop the languages to change their order', 'sitepress' ),
			'label_languages_with_no_translations' => __( 'How to handle languages without translation', 'sitepress' ),
			'option_skip_link'                     => __( 'Skip language', 'sitepress' ),
			'option_link_home'                     => __( 'Link to home of language for missing translations', 'sitepress' ),
			'label_preserve_url_args'              => __( 'Preserve URL arguments', 'sitepress' ),
			'label_additional_css'                 => __( 'Additional CSS', 'sitepress' ),
			'label_migrated_toggle'                => __( 'Backwards compatibility', 'sitepress' ),
			'label_skip_backwards_compatibility'   => __( 'Skip backwards compatibility', 'sitepress' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_menus_section_strings() {
		return array(
			'section_title'         => __( 'Menu language switcher', 'sitepress' ),
			'add_button_label'      => __( 'Add a new language switcher to a menu', 'sitepress' ),
			'select_label'          => __( 'Menu', 'sitepress' ),
			'select_option_choose'  => __( 'Choose a menu', 'sitepress' ),
			'position_label'        => __( 'Position:', 'sitepress' ),
			'position_first_item'   => __( 'First menu item', 'sitepress' ),
			'position_last_item'    => __( 'Last menu item', 'sitepress' ),
			'is_hierarchical_label' => __( 'Language menu items style:', 'sitepress' ),
			'flat'                  => __( 'List of languages', 'sitepress' ),
			'flat_desc'             => __( 'good for menus that display items as a list', 'sitepress' ),
			'hierarchical'          => __( 'Dropdown', 'sitepress' ),
			'hierarchical_desc'     => __( 'good for menus that support drop-downs', 'sitepress' ),
			'dialog_title'          => __( 'Edit Menu Language Switcher', 'sitepress' ),
			'dialog_title_new'      => __( 'New Menu Language Switcher', 'sitepress' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_sidebars_section_strings() {
		return array(
			'section_title'        => __( 'Widget language switcher', 'sitepress' ),
			'add_button_label'     => __( 'Add a new language switcher to a widget area', 'sitepress' ),
			'select_label'         => __( 'Widget area', 'sitepress' ),
			'select_option_choose' => __( 'Choose a widget area', 'sitepress' ),
			'label_widget_title'   => __( 'Widget title:', 'sitepress' ),
			'dialog_title'         => __( 'Edit Widget Area Language Switcher', 'sitepress' ),
			'dialog_title_new'     => __( 'New Widget Area language switcher', 'sitepress' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_footer_section_strings() {
		return array(
			'section_title' => __( 'Footer language switcher', 'sitepress' ),
			'show'          => __( 'Show language switcher in footer', 'sitepress' ),
			'dialog_title'  => __( 'Edit Footer Language Switcher', 'sitepress' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_post_translations_strings() {
		return array(
			'section_title'                      => __( 'Links to translation of posts', 'sitepress' ),
			'show'                               => __( 'Show links above or below posts, offering them in other languages', 'sitepress' ),
			'position_label'                     => __( 'Position of link(s):', 'sitepress' ),
			'position_above'                     => __( 'Above post', 'sitepress' ),
			'position_below'                     => __( 'Below post', 'sitepress' ),
			'label_alternative_languages_text'   => __( 'Text for alternative languages for posts:', 'sitepress' ),
			'default_alternative_languages_text' => __( 'This post is also available in: %s', 'sitepress' ),
			'dialog_title'                       => __( 'Edit Post Translations Language Switcher', 'sitepress' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_shortcode_actions_strings() {

		$description_link_text = _x( "insert WPML's switchers in custom locations", 'Custom languuage switcher description: external link text', 'sitepress' );
		$description_link_url  = 'https://wpml.org/documentation/getting-started-guide/language-setup/language-switcher-options/#custom-locations';
		$description_link      = '<a href="' . $description_link_url . '" target="_blank">' . $description_link_text . '</a>';
		$description           = _x( 'Need more options? See how you can %s.', 'Custom languuage switcher description: text', 'sitepress' );

		return array(
			'section_title'          => __( 'Custom language switchers', 'sitepress' ),
			'section_description'    => sprintf( $description, $description_link ),
			'customize_button_label' => __( 'Customize', 'sitepress' ),
			'dialog_title'           => __( 'Edit Shortcode Actions Language Switcher', 'sitepress' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_color_picker_strings() {
		return array(
			'panel_title'          => __( 'Language switcher colors', 'sitepress' ),
			'label_color_preset'   => __( 'Color themes:', 'sitepress' ),
			'select_option_choose' => __( 'Select a preset', 'sitepress' ),
			'label_normal_scheme'  => __( 'Normal', 'sitepress' ),
			'label_hover_scheme'   => __( 'Hover', 'sitepress' ),
			'background'           => __( 'Background', 'sitepress' ),
			'border'               => __( 'Border', 'sitepress' ),
			'font_current'         => __( 'Current language font color', 'sitepress' ),
			'background_current'   => __( 'Current language background color', 'sitepress' ),
			'font_other'           => __( 'Other language font color', 'sitepress' ),
			'background_other'     => __( 'Other language background color', 'sitepress' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_javascript_strings() {
		return array(
			'confirmation_item_remove' => esc_html__( 'Do you really want to remove this item?', 'sitepress' ),
			'leave_text_box_to_save'   => esc_html__( 'Leave the text box to auto-save', 'sitepress' ),
		);
	}

	/**
	 * @param string|bool $theme_wpml_config_file
	 *
	 * @return array
	 */
	public function get_reset_ui_model( $theme_wpml_config_file ) {
		$reset_locations = esc_html__( 'in options, menus, widgets, footer and shortcode', 'sitepress' );

		$model = array(
			'title'                => __( 'Reset settings', 'sitepress' ),
			'description'          => sprintf( esc_html__( 'This will change the settings of your language switchers %s to their defaults as set by the theme. Please note that some switchers may be removed and others may be added.', 'sitepress' ), '<strong>(' . $reset_locations . ')</strong>' ),
			'theme_config_file'    => $theme_wpml_config_file,
			'explanation_text'     => sprintf( esc_html__( '* Your theme has a %s file, which sets the default values for WPML.', 'sitepress' ), '<strong title="' . esc_attr( $theme_wpml_config_file ) . '">wpml-config.xml</strong>' ),
			'confirmation_message' => __( 'Are you sure you want to reset to the default settings?', 'sitepress' ),
			'restore_page_url'     => admin_url( 'admin.php?page=' . self::get_page_hook() . '&restore_ls_settings=1' ),
			'restore_button_label' => __( 'Restore default', 'sitepress' )
		);

		return $model;
	}

	/**
	 * @return array
	 */
	private function get_notifications() {
		$notifications = array();

		if ( $this->sitepress->get_wp_api()->constant( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' ) ) {
			$notifications['css_not_loaded'] = sprintf(
				__( "%s is defined in your theme. The language switcher can only be customized using the theme's CSS.", 'sitepress' ),
				'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS'
			);
		}

		return $notifications;
	}
}
