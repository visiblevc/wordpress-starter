<?php

class WPML_LS_Model_Build extends WPML_SP_User {

    /* @var WPML_LS_Settings $settings */
    private $settings;

    /* @var WPML_Mobile_Detect $mobile_detect */
    private $mobile_detect;

    /* @var bool $is_touch_screen */
    private $is_touch_screen = false;

    /* @var string $css_prefix */
    private $css_prefix;

    private $allowed_vars = array(
        'languages'              => 'array',
        'current_language_code'  => 'string',
        'css_classes'            => 'string',
        'backward_compatibility' => 'array',
    );

    private $allowed_language_vars = array(
        'code'                   => 'string',
        'url'                    => 'string',
        'flag_url'               => 'string',
        'flag_title'             => 'string',
        'native_name'            => 'string',
        'display_name'           => 'string',
        'is_current'             => 'bool',
        'css_classes'            => 'string',
        'db_id'                  => 'string',
        'menu_item_parent'       => 'mixed',
        'is_parent'              => 'bool',
        'backward_compatibility' => 'array',
    );

    /**
     * WPML_Language_Switcher_Render_Model constructor.
     *
     * @param WPML_LS_Settings $settings
     * @param SitePress                       $sitepress
     * @param string                          $css_prefix
     */
    public function __construct( $settings, $sitepress, $css_prefix ) {
        $this->settings   = $settings;
        $this->css_prefix = $css_prefix;
        parent::__construct( $sitepress );
    }

    /**
     * @param WPML_LS_Slot $slot
     * @param array        $template_data
     *
     * @return array
     */
    public function get( $slot, $template_data = array() ) {
        $vars = array();

        $vars['current_language_code']  = $this->sitepress->get_current_language();
        $vars['languages']              = $this->get_language_items( $slot, $template_data );
        $vars['css_classes']            = $this->get_slot_css_classes( $slot );

        $vars = $this->add_backward_compatibility_to_wrapper( $vars, $slot );

        return $this->sanitize_vars( $vars, $this->allowed_vars );
    }

    /**
     * @param WPML_LS_Slot $slot
     * @param array        $template_data
     *
     * @return array
     */
    private function get_language_items( $slot, $template_data ) {
        $ret = array();

        $get_ls_args = array(
            'skip_missing' => ! $this->settings->get_setting( 'link_empty' ),
        );

        if ( $slot->is_post_translations() ) {
            $get_ls_args['skip_missing'] = true;
        }

        $languages = $this->sitepress->get_ls_languages( $get_ls_args );
        $languages = is_array( $languages  ) ? $languages : array();

	    $languages = $this->order_languages( $languages, $slot->get( 'language_order_by' ), $slot->get( 'language_order_by' ) );

        if ( $languages ) {

            foreach ( $languages as $code => $data ) {

                $is_current_language = $code === $this->sitepress->get_current_language();

                if ( ! $slot->get( 'display_link_for_current_lang' ) && $is_current_language ) {
                    continue;
                }

                $ret[ $code ] = array(
                    'code' => $code,
                    'url'  => $data['url'],
                );

                /* @deprecated Use 'wpml_ls_language_url' instead */
                $ret[ $code ]['url'] = apply_filters( 'WPML_filter_link', $ret[ $code ]['url'], $data );

                /**
                 * This filter allows to change the URL for each languages links in the switcher
                 *
                 * @param string $ret[ $code ]['url'] The language URL to be filtered
                 * @param array  $data                The language information
                 */
                $ret[ $code ]['url'] = apply_filters( 'wpml_ls_language_url', $ret[ $code ]['url'], $data );

                $ret[ $code ]['url'] = $this->sitepress->get_wp_api()->is_admin() ? '#' : $ret[ $code ]['url'];

                $css_classes = $this->get_language_css_classes( $slot, $code );

                if ( $slot->get( 'display_flags' ) ) {
                    $ret[ $code ]['flag_url']   = $this->filter_flag_url( $data['country_flag_url'], $template_data );
                    $ret[ $code ]['flag_title'] = $data['native_name'];
                }

                if ( $slot->get( 'display_names_in_native_lang' ) ) {
                    $ret[ $code ]['native_name'] = $data['native_name'];
                }

	            if ( $slot->get( 'display_names_in_current_lang' ) ) {
                    $ret[ $code ]['display_name'] = $data['translated_name'];
                }

                if ( $is_current_language ) {
                    $ret[ $code ]['is_current'] = true;
                    array_push( $css_classes, $this->css_prefix . 'current-language' );
                }

                if ( $slot->is_menu() ) {
                    $ret[ $code ]['db_id']            = $this->get_menu_item_id( $code, $slot );
                    $ret[ $code ]['menu_item_parent'] = $slot->get( 'is_hierarchical' ) && ! $is_current_language
                        ? $this->get_menu_item_id( $this->sitepress->get_current_language(), $slot ) : 0;
                    $ret[ $code ]['is_parent'] = $slot->get( 'is_hierarchical' ) && $is_current_language
                        ? true : false;

                    if ( $ret[ $code ]['is_parent'] ) {
                        array_unshift( $css_classes, 'menu-item-has-children' );
                    }

                    array_unshift( $css_classes, 'menu-item' );
                    array_push( $css_classes, $this->css_prefix . 'menu-item' );
                }

                $ret[ $code ]['css_classes'] = $css_classes;
            }

            $i = 1;
            foreach ( $ret as &$lang ) {
                if( $i === 1 ) {
                    array_push( $lang['css_classes'], $this->css_prefix . 'first-item' );
                }

                if( $i === count( $ret ) ) {
                    array_push( $lang['css_classes'], $this->css_prefix . 'last-item' );
                }

                $lang = $this->add_backward_compatibility_to_languages( $lang, $slot );

                /**
                 * Filter the css classes for each language item
                 *
                 * @param array $lang['css_classes']
                 */
                $lang['css_classes'] = apply_filters( 'wpml_ls_model_language_css_classes', $lang['css_classes'] );

                $lang['css_classes'] = implode( ' ', $lang['css_classes'] );

                $lang = $this->sanitize_vars( $lang, $this->allowed_language_vars );
                $i++;
            }
        }

        return $ret;
    }

    /**
     * @param array $languages
     * @param string $order_by
     * @param string $order_way
     *
     * @return array
     */
    private function order_languages( $languages, $order_by, $order_way ) {
        $ret = $languages;

        if ( ! empty( $order_by ) && ! empty( $order_way ) ) {
            $method = 'order_by_' . $order_by;
            if ( is_callable( array( $this, $method ) ) ) {
                uasort( $ret, array( $this, 'order_by_' . $order_by ) );
            }

	        $ret = strtoupper( $order_way ) === 'ASC' ? array_reverse( $ret ) : $ret;
        }

        return $ret;
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    private function order_by_code( $a, $b ) {
        return strnatcmp( $a['code'], $b['code'] );
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    private function order_by_native_name( $a, $b ) {
        return strnatcmp( $a['native_name'], $b['native_name'] );
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    private function order_by_translated_name( $a, $b ) {
        return strnatcmp( $a['translated_name'], $b['translated_name'] );
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    private function order_by_id( $a, $b ) {
        return $a['id'] < $b['id'];
    }

    /**
     * @param string $url
     * @param array $template_data
     *
     * @return string
     */
    private function filter_flag_url( $url, $template_data = array() ) {
        $wp_upload_dir   = wp_upload_dir();
        $has_custom_flag = strpos( $url, $wp_upload_dir[ 'baseurl' ] . '/flags/' ) === 0 ? true : false;

        if ( ! $has_custom_flag && ! empty( $template_data['flags_base_uri'] ) ) {
            $url = trailingslashit( $template_data['flags_base_uri'] ) . basename( $url );

            if ( isset( $template_data['flag_extension'] ) ) {
                $old_ext = pathinfo( $url, PATHINFO_EXTENSION );
                $url = preg_replace( '#' . $old_ext . '$#', $template_data['flag_extension'], $url, 1 );
            }
        }

        return $url;
    }

    /**
     * @param WPML_LS_Slot $slot
     *
     * @return string
     */
    public function get_slot_css_classes( $slot ) {
        $classes = array( $this->get_slot_css_main_class( $slot->group(), $slot->slug() ) );

        $classes[] = trim( $this->css_prefix, '-' );

        if ( $this->sitepress->is_rtl( $this->sitepress->get_current_language() ) ) {
            $classes[] = $this->css_prefix . 'rtl';
        }

        $classes = $this->add_user_agent_touch_device_classes( $classes );

        /**
         * Filter the css classes for the language switcher wrapper
         * The wrapper is not available for menus
         *
         * @param array $classes
         */
        $classes = apply_filters( 'wpml_ls_model_css_classes', $classes );

        return implode( ' ', $classes );
    }

    /**
     * @param $group
     * @param $slug
     * @return string
     */
    public function get_slot_css_main_class( $group, $slug ) {
        return $this->css_prefix . $group . '-' . $slug;
    }

    /**
     * @param WPML_LS_Slot $slot
     * @param string       $code
     *
     * @return array
     */
    private function get_language_css_classes( $slot, $code ) {
        return array(
            $this->css_prefix . 'slot-' . $slot->slug(),
            $this->css_prefix . 'item',
            $this->css_prefix . 'item-' . $code,
        );
    }

    /**
     * @param array $classes
     *
     * @return array
     */
    private function add_user_agent_touch_device_classes($classes ) {

        if ( is_null( $this->mobile_detect ) ) {
            require_once ICL_PLUGIN_PATH . '/lib/mobile-detect.php';
            $this->mobile_detect   = new WPML_Mobile_Detect();
            $this->is_touch_screen = $this->mobile_detect->isMobile() || $this->mobile_detect->isTablet();
        }

        if ( $this->is_touch_screen ) {
            $classes[] = $this->css_prefix . 'touch-device';
        }

        return $classes;
    }

    /**
     * @return bool
     */
    private function needs_backward_compatibility() {
        return (bool) $this->settings->get_setting( 'migrated' );
    }

    /**
     * @param string       $code
     * @param WPML_LS_Slot $slot
     *
     * @return string
     */
    private function get_menu_item_id( $code, $slot ) {
        return $this->css_prefix . $slot->slug() . '-' . $code;
    }

    /**
     * @return string
     */
    public function get_css_prefix() {
        return $this->css_prefix;
    }

    /**
     * @param array $vars
     * @param array $allowed_vars
     *
     * @return array
     */
    private function sanitize_vars( $vars, $allowed_vars ) {
        $sanitized = array();

        foreach ( $allowed_vars as $allowed_var => $type ) {
            if ( isset( $vars[ $allowed_var ] ) ) {
                switch ( $type ) {
                    case 'array':
                        $sanitized[ $allowed_var ] = (array) $vars[ $allowed_var ];
                        break;

                    case 'string':
                        $sanitized[ $allowed_var ] = (string) $vars[ $allowed_var ];
                        break;

                    case 'bool':
                        $sanitized[ $allowed_var ] = (bool) $vars[ $allowed_var ];
                        break;

                    case 'mixed':
                        $sanitized[ $allowed_var ] = $vars[ $allowed_var ];
                        break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * @param array        $lang
     * @param WPML_LS_Slot $slot
     *
     * @return array
     */
    private function add_backward_compatibility_to_languages( $lang, $slot ) {

        if ( $this->needs_backward_compatibility() ) {

            $is_current_language = isset( $lang['is_current'] ) && $lang['is_current'];

            if ( $slot->is_menu() ) {

                if ( $is_current_language ) {
                    array_unshift( $lang['css_classes'], 'menu-item-language-current' );
                }

                array_unshift( $lang['css_classes'], 'menu-item-language' );
            }

            if ( $slot->is_sidebar() || $slot->is_shortcode_actions() ) {

                if ( $this->is_legacy_template( $slot->template(), 'list-vertical' )
                    || $this->is_legacy_template( $slot->template(), 'list-horizontal' )
                ){
                    array_unshift( $lang['css_classes'], 'icl-' . $lang['code'] );
                    $lang['backward_compatibility']['css_classes_a'] = $is_current_language ?
                        'lang_sel_sel' : 'lang_sel_other';
                }

                if ( $this->is_legacy_template( $slot->template(), 'dropdown' )
					|| $this->is_legacy_template( $slot->template(), 'dropdown-click' )
				){

                    if ( $is_current_language ) {
                        $lang['backward_compatibility']['css_classes_a'] = 'lang_sel_sel icl-' . $lang['code'];
                    } else {
                        array_unshift( $lang['css_classes'], 'icl-' . $lang['code'] );
                    }
                }
            }
        }

        return $lang;
    }

    /**
     * @param array        $vars
     * @param WPML_LS_Slot $slot
     *
     * @return mixed
     */
    private function add_backward_compatibility_to_wrapper( $vars, $slot ) {

        if ( $this->needs_backward_compatibility() ) {

            if ( $slot->is_sidebar() || $slot->is_shortcode_actions() ) {

                if ( $this->is_legacy_template( $slot->template(), 'list-vertical' )
                    || $this->is_legacy_template( $slot->template(), 'list-horizontal' )
                ){
                    $vars['backward_compatibility']['css_id'] = 'lang_sel_list';

                    if ( $this->is_legacy_template( $slot->template(), 'list-horizontal' ) ) {
                        $vars['css_classes'] = 'lang_sel_list_horizontal ' . $vars['css_classes'];
                    } else {
                        $vars['css_classes'] = 'lang_sel_list_vertical ' . $vars['css_classes'];
                    }
                }

                if ( $this->is_legacy_template( $slot->template(), 'dropdown' ) ) {
                    $vars['backward_compatibility']['css_id'] = 'lang_sel';
                }

                if ( $this->is_legacy_template( $slot->template(), 'dropdown-click' ) ) {
                    $vars['backward_compatibility']['css_id'] = 'lang_sel_click';
                }
            }

            if ( $slot->is_post_translations() ) {
                $vars['css_classes'] = 'icl_post_in_other_langs ' . $vars['css_classes'];
            }

            if ( $slot->is_footer() ) {
                $vars['backward_compatibility']['css_id'] = 'lang_sel_footer';
            }

            $vars['backward_compatibility']['css_classes_flag']    = 'iclflag';
            $vars['backward_compatibility']['css_classes_native']  = 'icl_lang_sel_native';
            $vars['backward_compatibility']['css_classes_display'] = 'icl_lang_sel_translated';
            $vars['backward_compatibility']['css_classes_bracket'] = 'icl_lang_sel_bracket';
        }

        return $vars;
    }

    /**
     * @param $template_slug
     * @param mixed|string|null $type
     *
     * @return bool
     */
    private function is_legacy_template( $template_slug, $type = null ) {
        $templates = $this->settings->get_core_templates();
        $ret = in_array( $template_slug, $templates, true);

        if ( $ret && array_key_exists( $type, $templates ) ) {
            $ret = $templates[ $type ] === $template_slug;
        }

        return $ret;
    }
}