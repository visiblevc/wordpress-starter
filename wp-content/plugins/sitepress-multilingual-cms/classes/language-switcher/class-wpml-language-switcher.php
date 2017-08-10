<?php

/**
 * Class WPML_Language_Switcher
 *
 * Main class
 */
class WPML_Language_Switcher extends WPML_SP_User {

	/* @var array $dependencies */
	private $dependencies;

	/**
	 * WPML_Language_Switcher constructor.
	 *
	 * @param Sitepress $sitepress
	 * @param WPML_LS_Dependencies_Factory $dependencies
	 */
	public function __construct( SitePress $sitepress, WPML_LS_Dependencies_Factory $dependencies = null ) {
		parent::__construct( $sitepress );
		$this->dependencies = $dependencies ? $dependencies : new WPML_LS_Dependencies_Factory( $sitepress, self::parameters() );
	}

	public function init_hooks() {
		if ( $this->sitepress->get_setting( 'setup_complete' ) ) {
			add_action( 'widgets_init', array( 'WPML_LS_Widget', 'register' ), 20 );
		}

		$this->dependencies->templates()->init_hooks();
		$this->dependencies->settings()->init_hooks();
		$this->dependencies->render()->init_hooks();
		$this->dependencies->shortcodes()->init_hooks();
		$this->dependencies->inline_styles()->init_hooks();

		if ( is_admin() ) {
			if ( did_action( 'set_current_user' ) ) {
				$this->init_admin_hooks();
			} else {
				add_action( 'set_current_user', array( $this, 'init_admin_hooks' ) );
			}
		}
	}

	public function init_admin_hooks() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->dependencies->admin_ui()->init_hooks();
		}
	}
	/**
	 * @param string $group
	 * @param string $slot
	 *
	 * @return WPML_LS_Slot
	 */
	public function get_slot( $group, $slot ) {
		return $this->dependencies->settings()->get_slot( $group, $slot );
	}

	/**
	 * @param WPML_LS_Slot $slot
	 *
	 * @return string
	 */
	public function render( $slot ) {
		return $this->dependencies->render()->render( $slot );
	}

	/**
	 * @param string     $type 'sidebars', 'menus', 'statics'
	 * @param string|int $slug_or_id
	 *
	 * @return string
	 */
	public function get_button_to_edit_slot( $type, $slug_or_id ) {
		return $this->dependencies->admin_ui()->get_button_to_edit_slot( $type, $slug_or_id );
	}

	/**
	 * @return array
	 */
	public static function parameters() {
		return array(
			'css_prefix'     => 'wpml-ls-',
			'core_templates' => array(
				'dropdown'          => 'wpml-legacy-dropdown',
				'dropdown-click'    => 'wpml-legacy-dropdown-click',
				'list-vertical'     => 'wpml-legacy-vertical-list',
				'list-horizontal'   => 'wpml-legacy-horizontal-list',
				'post-translations' => 'wpml-legacy-post-translations',
				'menu-item'         => 'wpml-menu-item',
			),
		);
	}
}
