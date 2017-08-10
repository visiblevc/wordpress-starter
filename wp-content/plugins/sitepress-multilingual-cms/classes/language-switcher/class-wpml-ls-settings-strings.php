<?php

class WPML_LS_Settings_Strings {

    private $strings_meta = array(
        'availability_text' => array( 'domain' => 'WPML',    'name' => 'Text for alternative languages for posts' ),
        'widget_title'      => array( 'domain' => 'Widgets', 'name' => 'widget title' ),
    );

	/* @var WPML_LS_Slot_Factory $slot_factory */
	private $slot_factory;

    public function __construct( $slot_factory ) {
	    $this->slot_factory = $slot_factory;
    }

	/**
     * @param array $new_settings
     * @param array $old_settings
     */
    public function register_all( $new_settings, $old_settings ) {
        $void_slot = array( 'show' => false );

        foreach ( $new_settings['sidebars'] as $slug => $slot_settings ) {
            $old_slot_settings = isset( $old_settings['sidebars'][ $slug ] )
	            ? $old_settings['sidebars'][ $slug ] : $this->slot_factory->get_slot( $void_slot );
            $this->register_slot_strings( $slot_settings, $old_slot_settings );
        }

        $post_translations_settings = isset( $new_settings['statics']['post_translations'] )
            ? $new_settings['statics']['post_translations'] : null;

        if ( $post_translations_settings ) {

            $old_slot_settings = isset( $old_settings['statics']['post_translations'] )
                ? $old_settings['statics']['post_translations'] : $this->slot_factory->get_slot( $void_slot );

            $this->register_slot_strings( $post_translations_settings, $old_slot_settings );
        }
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    public function translate_all( $settings ) {

        if ( isset( $settings['sidebars'] ) ) {
            foreach ( $settings['sidebars'] as &$slot_settings ) {
                $slot_settings = $this->translate_slot_strings( $slot_settings );
            }
        }

        if ( isset( $settings['statics']['post_translations'] ) ) {
            $settings['statics']['post_translations'] = $this->translate_slot_strings( $settings['statics']['post_translations'] );
        }

        return $settings;
    }

    /**
     * @param WPML_LS_Slot $slot
     * @param WPML_LS_Slot $old_slot
     */
    private function register_slot_strings( WPML_LS_Slot $slot, WPML_LS_Slot $old_slot ) {
        foreach ($this->strings_meta as $key => $string_meta ) {

            if ( $slot->get( $key ) ) {
                $old_string = $old_slot->get( $key );

                if ( $key === 'widget_title' && $old_string && function_exists( 'icl_st_update_string_actions' ) ) {
                    icl_st_update_string_actions( 'Widgets', $this->get_string_name( $key, $old_string ), $old_string, $slot->get( $key ) );
                } else {
                    do_action(
                        'wpml_register_single_string',
                        $this->strings_meta[ $key ]['domain'],
                        $this->get_string_name( $key, $slot->get( $key ) ),
	                    $slot->get( $key )
                    );
                }
            }
        }
    }

    /**
     * @param WPML_LS_Slot $slot
     *
     * @return WPML_LS_Slot
     */
    private function translate_slot_strings( $slot ) {
        foreach ( $this->strings_meta as $key => $string_meta ) {

            if ( $slot->get( $key ) ) {

                if ( $key === 'title' && function_exists( 'icl_sw_filters_widget_title' ) ) {
	                $translation = icl_sw_filters_widget_title( $slot->get( $key ) );
	                $slot->set( $key, $translation );
                } else {
                    $string_name  = $this->get_string_name( $key, $slot->get( $key ) );
                    $domain       = $this->strings_meta[ $key ]['domain'];
	                $translation  = apply_filters( 'wpml_translate_single_string', $slot->get( $key ), $domain, $string_name );
	                $slot->set( $key, $translation );
                }
            }
        }

        return $slot;
    }

    /**
     * @param string $key
     * @param string $string_value
     *
     * @return string
     */
    private function get_string_name( $key, $string_value ) {
        $name = $this->strings_meta[ $key ]['name'];

        if ( $key === 'widget_title' ) {
            $name = $name . ' - ' . md5( $string_value );
        }

        return $name;
    }
}