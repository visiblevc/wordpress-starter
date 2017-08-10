<?php

class WPML_LS_Settings_Color_Presets {

    /**
     * @return array
     */
    public function get_defaults() {
        $void = array(
            'font_current_normal'       => '',
            'font_current_hover'        => '',
            'background_current_normal' => '',
            'background_current_hover'  => '',
            'font_other_normal'         => '',
            'font_other_hover'          => '',
            'background_other_normal'   => '',
            'background_other_hover'    => '',
            'border_normal'             => '',
            'background_normal'         => '',
        );

        $gray = array(
            'font_current_normal'       => '#222222',
            'font_current_hover'        => '#000000',
            'background_current_normal' => '#eeeeee',
            'background_current_hover'  => '#eeeeee',
            'font_other_normal'         => '#222222',
            'font_other_hover'          => '#000000',
            'background_other_normal'   => '#e5e5e5',
            'background_other_hover'    => '#eeeeee',
            'border_normal'             => '#cdcdcd',
            'background_normal'         => '#e5e5e5',
        );

        $white = array(
            'font_current_normal'       => '#444444',
            'font_current_hover'        => '#000000',
            'background_current_normal' => '#ffffff',
            'background_current_hover'  => '#eeeeee',
            'font_other_normal'         => '#444444',
            'font_other_hover'          => '#000000',
            'background_other_normal'   => '#ffffff',
            'background_other_hover'    => '#eeeeee',
            'border_normal'             => '#cdcdcd',
            'background_normal'         => '#ffffff',
        );

        $blue = array(
            'font_current_normal'       => '#ffffff',
            'font_current_hover'        => '#000000',
            'background_current_normal' => '#95bedd',
            'background_current_hover'  => '#95bedd',
            'font_other_normal'         => '#000000',
            'font_other_hover'          => '#ffffff',
            'background_other_normal'   => '#cbddeb',
            'background_other_hover'    => '#95bedd',
            'border_normal'             => '#0099cc',
            'background_normal'         => '#cbddeb',
        );

        return array(
            'void'  => array( 'label' => esc_html__( 'Clear all colors', 'sitepress' ), 'values' => $void ),
            'gray'  => array( 'label' => esc_html__( 'Gray', 'sitepress' ), 'values' => $gray ),
            'white' => array( 'label' => esc_html__( 'White', 'sitepress' ), 'values' => $white ),
            'blue'  => array( 'label' => esc_html__( 'Blue', 'sitepress' ), 'values' => $blue ),
        );
    }
}