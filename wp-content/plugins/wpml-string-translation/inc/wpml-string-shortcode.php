<?php

add_shortcode('wpml-string', 'wpml_string_shortcode');

function wpml_string_shortcode($atts, $value) {
    global $wpdb;
    
    extract(
        shortcode_atts( array(), $atts )
    );
    if (!isset($atts['context'])) {
        $atts['context'] = 'wpml-shortcode';
    }
    if (!isset($atts['name'])) {
        $atts['name'] = 'wpml-shortcode-' . md5( $value );
    }

    // register this string if it's not there already.
    $string = $wpdb->get_row( $wpdb->prepare( "SELECT id, value, status FROM {$wpdb->prefix}icl_strings WHERE context=%s AND name=%s", $atts['context'], $atts['name'] ) );    
    if( !$string || $string->value != $value){
        icl_register_string( $atts['context'], $atts['name'], $value );
    }

    return do_shortcode( icl_t( $atts['context'], $atts['name'], $value ) );
}
