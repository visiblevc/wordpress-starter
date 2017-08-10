<?php

class WPML_LS_Inline_Styles {

    /* @var WPML_LS_Templates $templates */
    private $templates;

    /* @var WPML_LS_Settings $settings */
    private $settings;

    /* @var WPML_LS_Model_Build $model_build */
    private $model_build;

    /**
     * WPML_Language_Switcher_Render_Assets constructor.
     *
     * @param WPML_LS_Templates   $templates
     * @param WPML_LS_Settings    $settings
     * @param WPML_LS_Model_Build $model_build
     */
    public function __construct( $templates, $settings, $model_build ) {
        $this->templates   = $templates;
        $this->settings    = $settings;
        $this->model_build = $model_build;
    }

    public function init_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts_action' ), 20 );
    }

    /**
     * @param WPML_LS_Slot $slot
     *
     * @return string
     */
    private function get_slot_color_picker_css( $slot ) {
        $css = '';

        if ( $slot->is_menu() ) {
            $css = $this->get_slot_color_picker_css_for_menus( $slot );
        } elseif ( ! $slot->is_post_translations() ) {
            $css = $this->get_slot_color_picker_css_for_widgets_and_statics( $slot );
        }

        return $this->sanitize_css( $css );
    }

    /**
     * @param WPML_LS_Slot $slot
     *
     * @return string
     */
    private function get_slot_color_picker_css_for_menus( $slot ) {
        $css             = '';
        $prefix          = '.' . $this->model_build->get_css_prefix();
        $menu_item_class = $prefix . 'slot-' . $slot->slug();

        if ( $slot->get( 'background_other_normal' ) || $slot->get( 'font_other_normal' ) ) {
            $css .= "$menu_item_class,";
            $css .= " $menu_item_class a,";
            $css .= " $menu_item_class a:visited{";
            $css .= $slot->get( 'background_other_normal' ) ? "background-color:{$slot->get( 'background_other_normal' )};" : '';
            $css .= $slot->get( 'font_other_normal' ) ? "color:{$slot->get( 'font_other_normal' )};" : '';
            $css .= "}";
        }

        if ( $slot->get( 'font_other_hover' ) || $slot->get( 'background_other_hover' ) ) {
            $css .= "$menu_item_class:hover,";
            $css .= " $menu_item_class:hover a,";
            $css .= " $menu_item_class a:hover{";
            $css .= $slot->get( 'font_other_hover' ) ? "color:{$slot->get( 'font_other_hover' )};" : '';
            $css .= $slot->get( 'background_other_hover' ) ? "background-color:{$slot->get( 'background_other_hover' )};" : '';
            $css .= "}";
        }

        if ( $slot->get( 'font_current_normal' ) || $slot->get( 'background_current_normal' ) ) {
            $css .= "$menu_item_class{$prefix}current-language,";
            $css .= " $menu_item_class{$prefix}current-language a,";
            $css .= " $menu_item_class{$prefix}current-language a:visited{";
            $css .= $slot->get( 'font_current_normal' ) ? "color:{$slot->get( 'font_current_normal' )};" : '';
            $css .= $slot->get( 'background_current_normal' ) ? "background-color:{$slot->get( 'background_current_normal' )};" : '';
            $css .= "}";
        }

        if ( $slot->get( 'font_current_hover' ) || $slot->get( 'background_current_hover' ) ) {
            $css .= "$menu_item_class{$prefix}current-language:hover,";
            $css .= " $menu_item_class{$prefix}current-language:hover a,";
            $css .= " $menu_item_class{$prefix}current-language a:hover{";
            $css .= $slot->get( 'font_current_hover' ) ? "color:{$slot->get( 'font_current_hover' )};" : '';
            $css .= $slot->get( 'background_current_hover' ) ? "background-color:{$slot->get( 'background_current_hover' )};" : '';
            $css .= "}";
        }

        // Override parent menu styles for hierarchical menus
        if ( $slot->get( 'is_hierarchical' ) ) {

            if ( $slot->get( 'background_other_normal' ) || $slot->get( 'font_other_normal' ) ) {
                $css .= "$menu_item_class{$prefix}current-language $menu_item_class,";
                $css .= " $menu_item_class{$prefix}current-language $menu_item_class a,";
                $css .= " $menu_item_class{$prefix}current-language $menu_item_class a:visited{";
                $css .= $slot->get( 'background_other_normal' ) ? "background-color:{$slot->get( 'background_other_normal' )};" : '';
                $css .= $slot->get( 'font_other_normal' ) ? "color:{$slot->get( 'font_other_normal' )};" : '';
                $css .= "}";
            }

            if ( $slot->get( 'font_other_hover' ) || $slot->get( 'background_other_hover' ) ) {
                $css .= "$menu_item_class{$prefix}current-language $menu_item_class:hover,";
                $css .= " $menu_item_class{$prefix}current-language $menu_item_class:hover a,";
                $css .= " $menu_item_class{$prefix}current-language $menu_item_class a:hover {";
                $css .= $slot->get( 'font_other_hover' ) ? "color:{$slot->get( 'font_other_hover' )};" : '';
                $css .= $slot->get( 'background_other_hover' ) ? "background-color:{$slot->get( 'background_other_hover' )};" : '';
                $css .= "}";
            }
        }

        return $css;
    }

    /**
     * @param WPML_LS_Slot $slot
     *
     * @return string
     */
    private function get_slot_color_picker_css_for_widgets_and_statics ($slot ) {
        $css           = '';
        $prefix        = '.' . $this->model_build->get_css_prefix();
        $wrapper_class = '.' . $this->model_build->get_slot_css_main_class( $slot->group(), $slot->slug() );

        if ( $slot->get( 'background_normal' )  ) {
            $css .= "$wrapper_class{";
            $css .= "background-color:{$slot->get( 'background_normal' )};";
            $css .= "}";
        }

	    if ( $slot->get( 'border_normal' ) ) {
		    $css .= "$wrapper_class, $wrapper_class {$prefix}sub-menu, $wrapper_class a {";
		    $css .= "border-color:{$slot->get( 'border_normal' )};";
		    $css .= "}";
	    }

        if ( $slot->get( 'font_other_normal' ) || $slot->get( 'background_other_normal' ) ) {
            $css .= "$wrapper_class a {";
            $css .= $slot->get( 'font_other_normal' ) ? "color:{$slot->get( 'font_other_normal' )};" : '';
            $css .= $slot->get( 'background_other_normal' ) ? "background-color:{$slot->get( 'background_other_normal' )};" : '';
            $css .= "}";
        }

        if ( $slot->get( 'font_other_hover' ) || $slot->get( 'background_other_hover' ) ) {
            $css .= "$wrapper_class a:hover,$wrapper_class a:focus {";
            $css .= $slot->get( 'font_other_hover' ) ? "color:{$slot->get( 'font_other_hover' )};" : '';
            $css .= $slot->get( 'background_other_hover' ) ? "background-color:{$slot->get( 'background_other_hover' )};" : '';
            $css .= "}";
        }

        if ( $slot->get( 'font_current_normal' ) || $slot->get( 'background_current_normal' ) ) {
            $css .= "$wrapper_class {$prefix}current-language>a {";
            $css .= $slot->get( 'font_current_normal' ) ? "color:{$slot->get( 'font_current_normal' )};" : '';
            $css .= $slot->get( 'background_current_normal' ) ? "background-color:{$slot->get( 'background_current_normal' )};" : '';
            $css .= "}";
        }

        if ( $slot->get( 'font_current_hover' ) || $slot->get( 'background_current_hover' ) ) {
            $css .= "$wrapper_class {$prefix}current-language:hover>a, $wrapper_class {$prefix}current-language>a:focus {";
            $css .= $slot->get( 'font_current_hover' ) ? "color:{$slot->get( 'font_current_hover' )};" : '';
            $css .= $slot->get( 'background_current_hover' ) ? "background-color:{$slot->get( 'background_current_hover' )};" : '';
            $css .= "}";
        }

        return $css;
    }

    /**
     * @param array $slots
     *
     * @return string
     */
    public function get_slots_inline_styles( $slots ) {
        $all_styles = '';

        if ( $this->settings->can_load_styles() ) {

            foreach ( $slots as $slot ) {
                /* @var WPML_LS_Slot $slot */
                $css = $this->get_slot_color_picker_css( $slot );

                if ( $css ) {
                    $style_id    = 'wpml-ls-inline-styles-' . $slot->group() . '-' . $slot->slug();
                    $all_styles .= '<style type="text/css" id="' . $style_id . '">' . $css . '</style>' . PHP_EOL;
                }
            }
        }

        return $all_styles;
    }

    /**
     * @return string
     */
    public function get_additional_style() {
        $css = $this->sanitize_css( $this->settings->get_setting( 'additional_css' ) );

        if ( $css ) {
            $css = '<style type="text/css" id="wpml-ls-inline-styles-additional-css">' . $css . '</style>' . PHP_EOL;
        }

        return $css;
    }

    public function wp_enqueue_scripts_action() {
            $this->enqueue_inline_styles();
    }

    private function enqueue_inline_styles() {
        if ( $this->settings->can_load_styles() ) {
            $active_slots        = $this->settings->get_active_slots();
            $first_valid_handler = $this->get_first_valid_style_handler( $active_slots );

            foreach ( $active_slots as $slot ) {
                /* @var WPML_LS_Slot $slot */
                $css = $this->get_slot_color_picker_css( $slot );

                if ( empty( $css ) ) {
                    continue;
                }

                $template = $this->templates->get_template( $slot->template() );

                if ( $template->has_styles() ) {
                    wp_add_inline_style( $template->get_inline_style_handler(), $css );
                } else if ( $first_valid_handler ) {
                    wp_add_inline_style( $first_valid_handler, $css );
                } else {
                    echo $this->get_raw_inline_style_tag( $slot, $css );
                }
            }

            if ( $first_valid_handler ) {
                $additional_css = $this->sanitize_css( $this->settings->get_setting( 'additional_css' ) );

                if ( ! empty( $additional_css ) ) {
                    wp_add_inline_style( $first_valid_handler, $additional_css );
                }

            } else {
                echo $this->get_additional_style();
            }
        }
    }

    /**
     * @param array $active_slots
     *
     * @return bool|mixed|null|string
     */
    private function get_first_valid_style_handler( $active_slots ) {
        $first_handler = null;

        foreach ( $active_slots as $slot ) {
            /* @var WPML_LS_Slot $slot */
            $template = $this->templates->get_template( $slot->template() );
            $handler  = $template->get_inline_style_handler();

            if ( $handler ) {
                $first_handler = $handler;
                break;
            }
        }

        return $first_handler;
    }

    public function admin_output() {
        if ( $this->settings->can_load_styles() ) {
            $active_slots        = $this->settings->get_active_slots();

            foreach ( $active_slots as $slot ) {
                /* @var WPML_LS_Slot $slot */
                $css = $this->get_slot_color_picker_css( $slot );
                echo $this->get_raw_inline_style_tag( $slot, $css );
            }

            echo $this->get_additional_style();
        }
    }

    /**
     * @param WPML_LS_Slot $slot
     * @param string       $css
     *
     * @return string
     */
    private function get_raw_inline_style_tag( $slot, $css ) {
        $style_id = 'wpml-ls-inline-styles-' . $slot->group() . '-' . $slot->slug();
        return '<style type="text/css" id="' . $style_id . '">' . $css . '</style>' . PHP_EOL;
    }

    /**
     * @param string $css
     *
     * @return string
     */
    private function sanitize_css( $css ) {
        $css = wp_strip_all_tags( $css );
        $css = preg_replace('/\s+/S', " ", trim( $css ) );
        return $css;
    }
}