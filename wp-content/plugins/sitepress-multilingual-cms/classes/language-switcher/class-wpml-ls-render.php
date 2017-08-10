<?php

class WPML_LS_Render extends WPML_SP_User {

	/* @var WPML_LS_Template $current_template */
	private $current_template;

	/* @var WPML_LS_Templates $templates */
	private $templates;

	/* @var WPML_LS_Settings $settings */
	private $settings;

	/* @var WPML_LS_Model_Build $model_build */
	private $model_build;

	/* @var WPML_LS_Inline_Styles &$inline_styles */
	private $inline_styles;

	/* @var WPML_LS_Assets $assets */
	private $assets;

	/**
	 * WPML_Language_Switcher_Menu constructor.
	 *
	 * @param WPML_LS_Templates     $templates
	 * @param WPML_LS_Settings      $settings
	 * @param WPML_LS_Model_Build   $model_build
	 * @param WPML_LS_Inline_Styles $inline_styles
	 * @param SitePress                            $sitepress
	 */
	public function __construct( $templates, $settings, $model_build, $inline_styles, $sitepress ) {
		$this->templates     = $templates;
		$this->settings      = $settings;
		$this->model_build   = $model_build;
		$this->inline_styles = $inline_styles;
		$this->assets        = new WPML_LS_Assets( $this->templates, $this->settings );
		parent::__construct( $sitepress );
	}

	public function init_hooks() {
		if ( $this->sitepress->get_setting( 'setup_complete' ) ) {
			add_filter( 'wp_nav_menu_objects',           array( $this, 'wp_nav_menu_objects_filter' ), 10, 2 );
			add_filter( 'the_content',                   array( $this, 'the_content_filter' ), 100 );
			add_action( 'wp_footer',                     array( $this, 'wp_footer_action' ), 19 );

			//@deprecated see 'wpml_add_language_selector'
			add_action( 'icl_language_selector',         array( $this, 'wpml_add_language_selector_action' ) );
			add_action( 'wpml_add_language_selector',    array( $this, 'wpml_add_language_selector_action' ) );
			add_action( 'wpml_footer_language_selector', array( $this, 'wpml_footer_language_selector_action' ) );

			$this->assets->init_hooks();
		}
	}

	/**
	 * @param WPML_LS_slot $slot
	 *
	 * @return string
	 */
	public function render( $slot ) {
		$html  = '';
		$model = array();

		if ( ! $this->is_hidden( $slot ) ) {

			$this->assets->maybe_late_enqueue_template( $slot->template() );
			$this->current_template = $this->templates->get_template( $slot->template() );

			if ( $slot->template_string() ) {
				$this->current_template->set_template_string( $slot->template_string() );
			}

			$model = $this->model_build->get( $slot, $this->current_template->get_template_data() );

			if ( $model['languages'] ) {
				$this->current_template->set_model( $model );
				$html = $this->current_template->get_html();
			}
		}

		return $this->filter_html( $html, $model, $slot );
	}

	/**
	 * @param string       $html
	 * @param array        $model
	 * @param WPML_LS_slot $slot
	 *
	 * @return string
	 */
	private function filter_html( $html, $model, $slot ) {
		/**
		 * @param string       $html   The HTML for the language switcher
		 * @param array        $model  The model passed to the template
		 * @param WPML_LS_slot $slot   The language switcher settings for this slot
		 */
		return apply_filters( 'wpml_ls_html', $html, $model, $slot );
	}

	/**
	 * @param WPML_LS_slot $slot
	 *
	 * @return array
	 */
	public function get_preview( $slot ) {
		$ret  = array();

		if ( $slot->is_menu() ) {
			$ret['html'] = $this->render_menu_preview( $slot );
		} else if ( $slot->is_post_translations() ) {
			$ret['html'] = $this->post_translations_label( $slot );
		} else {
			$ret['html'] = $this->render( $slot );
		}

		$ret['css']      = $this->current_template->get_styles( true );
		$ret['js']       = $this->current_template->get_scripts( true );
		$ret['styles']   = $this->inline_styles->get_slots_inline_styles( array( $slot ) );

		return $ret;
	}

	/**
	 * @param string $items
	 * @param object $args
	 *
	 * @return string
	 */
	public function wp_nav_menu_objects_filter( $items, $args ) {
		$args    = (object) $args;
		$menu_id = isset( $args->menu ) ? $this->retrieve_menu_id( $args->menu ) : null;

		if ( $menu_id ) {
			$slot = $this->settings->get_menu_settings_from_id( $menu_id );

			if(  $slot->is_enabled() && ! $this->is_hidden( $slot ) ) {
				$lang_items = $this->get_menu_items( $slot );

				if ( $lang_items ) {
					if ( 'before' === $slot->get( 'position_in_menu' ) ) {
						$items = array_merge( $lang_items, $items );
					} else {
						$items = array_merge( $items, $lang_items );
					}
				}
			}
		}

		return $items;
	}

	/**
	 * @param WP_Term|int|string $menu
	 *
	 * @return int|null
	 */
	private function retrieve_menu_id( $menu ) {
		$menu_id = null;

		if ( isset( $menu->term_id ) ) {
			$menu_id = $menu->term_id;
		} else {
			$menu = wp_get_nav_menu_object( $menu );

			if ( $menu ) {
				$menu_id = $menu->term_id;
			}
		}

		return $menu_id;
	}

	/**
	 * @param WPML_LS_Slot $slot
	 *
	 * @return array
	 */
	private function get_menu_items( $slot ) {
		$lang_items = array();
		$model      = $this->model_build->get( $slot );

		if ( isset( $model['languages'] ) ) {

			$this->current_template = $this->templates->get_template( $slot->template() );
			foreach ( $model['languages'] as $language_model ) {
				$this->current_template->set_model( $language_model );
				$item_content = $this->filter_html( $this->current_template->get_html(), $language_model, $slot );
				$lang_items[] = new WPML_LS_Menu_Item( $language_model, $item_content );
			}
		}

		return $lang_items;
	}

	/**
	 * @param WPML_LS_Slot $slot
	 *
	 * @return string
	 */
	private function render_menu_preview( $slot ) {
		$items    = $this->get_menu_items( $slot );
		$class    = $slot->get( 'is_hierarchical' ) ? 'wpml-ls-menu-hierarchical-preview' : 'wpml-ls-menu-flat-preview';
		$defaults = array( 'before' => '', 'after' => '', 'link_before' => '', 'link_after' => '', 'theme_location' => '' );
		$defaults = (object) $defaults;
		$output   = walk_nav_menu_tree( $items, 2, $defaults );

		$dummy_item = esc_html__( 'menu items', 'sitepress' );

		if ( $slot->get( 'position_in_menu' ) === 'before' ) {
			$output .= '<li class="dummy-menu-item"><a href="#">' . $dummy_item . '...</a></li>';
		} else {
			$output = '<li class="dummy-menu-item"><a href="#">...' . $dummy_item . '</a></li>' . $output;
		}

		return '<div><ul class="wpml-ls-menu-preview ' . $class . '">' . $output . '</ul></div>';
	}

	/**
	 * @param WPML_LS_Slot $slot
	 *
	 * @return bool true if the switcher is to be hidden
	 */
	private function is_hidden( $slot ) {
		if ( ! function_exists( 'wpml_home_url_ls_hide_check' ) ) {
			require ICL_PLUGIN_PATH . '/inc/post-translation/wpml-root-page-actions.class.php';
		}

		return wpml_home_url_ls_hide_check() && ! $slot->is_shortcode_actions();
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function the_content_filter( $content ) {
		$post_translations = '';
		$slot              = $this->settings->get_slot( 'statics', 'post_translations' );

		if ( $slot->is_enabled() && $this->sitepress->get_wp_api()->is_singular() ) {
			$post_translations = $this->post_translations_label( $slot	);
		}

		if ( $post_translations ) {
			if ( $slot->get( 'display_before_content' ) ) {
				$content = $post_translations . $content;
			}

			if ( $slot->get( 'display_after_content' ) ) {
				$content = $content . $post_translations;
			}
		}

		return $content;
	}

	/**
	 * @param WPML_LS_Slot $slot
	 *
	 * @return mixed|string|void
	 */
	public function post_translations_label( $slot ) {
		$css_classes = $this->model_build->get_slot_css_classes( $slot );
		$html        = $this->render( $slot );
		if ( $html ) {
			$html = sprintf( $slot->get( 'availability_text' ), $html );
			$html = '<p class="' . $css_classes . '">' . $html . '</p>';


			/* @deprecated use 'wpml_ls_post_alternative_languages' instead */
			$html = apply_filters( 'icl_post_alternative_languages', $html );

			/**
			 * @param string $html
			 */
			$html = apply_filters( 'wpml_ls_post_alternative_languages', $html );
		}

		return $html;
	}

	public function wp_footer_action() {
		$slot = $this->settings->get_slot( 'statics', 'footer' );
		if( $slot->is_enabled() ) {
			echo $this->render( $slot );
		}
	}

	public function wpml_add_language_selector_action() {
		$slot = $this->settings->get_slot( 'statics', 'shortcode_actions' );
		echo $this->render( $slot );
	}

	public function wpml_footer_language_selector_action() {
		$slot = clone $this->settings->get_slot( 'statics', 'footer' );
		$slot->set( 'show', 1 );
		echo $this->render( $slot );
	}
}
