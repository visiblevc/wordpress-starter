<?php

class WPML_LS_Settings_Sanitize {

    /**
     * @return array
     */
    private function get_global_settings_keys() {
        return array(
            'migrated'                        => array( 'type' => 'int', 'force_missing_to' => 1 ),
            'converted_menu_ids'              => array( 'type' => 'int', 'force_missing_to' => 0 ),
            'languages_order'                 => array( 'type' => 'array' ),
            'link_empty'                      => array( 'type' => 'int' ),
            'additional_css'                  => array( 'type' => 'string' ),
            'copy_parameters'                 => array( 'type' => 'string' ),
            // Slot groups
            'menus'                           => array( 'type' => 'array', 'force_missing_to' => array() ),
            'sidebars'                        => array( 'type' => 'array', 'force_missing_to' => array() ),
            'statics'                         => array( 'type' => 'array', 'force_missing_to' => array() ),
        );
    }

    /**
     * @param array $s
     * @return array
     */
    public function sanitize_all_settings( $s ) {
        $s = $this->sanitize_settings( $s, $this->get_global_settings_keys() );

        return $s;
    }

    /**
     * @param array $settings_slice
     * @param array $allowed_keys
     *
     * @return array
     */
    private function sanitize_settings( $settings_slice, $allowed_keys ) {
        $ret = array();

        foreach ( $allowed_keys as $key => $expected ) {
            if ( array_key_exists( $key, $settings_slice ) ) {
                switch( $expected['type'] ) {
                    case 'string':
                        $ret[ $key ] = (string) $settings_slice[ $key ];
                        break;
                    case 'int':
                        $ret[ $key ] = (int) $settings_slice[ $key ];
                        break;
                    case 'array':
                        $ret[ $key ] = (array) $settings_slice[ $key ];
                        break;
                }
            } elseif ( array_key_exists( 'force_missing_to', $expected ) ) {
                $ret[ $key ] = $expected['force_missing_to'];
            }
        }

        return $ret;
    }
}