<?php

/**
 * Registers scripts so that they can be reused throughout WPML plugins
 */
function wpml_register_js_scripts() {
	wp_register_script( 'wpml-underscore-template-compiler',
		ICL_PLUGIN_URL . '/res/js/shared/wpml-template-compiler.js',
		array( "underscore" ) );
	wp_register_script( 'wpml-domain-validation',
		ICL_PLUGIN_URL . '/res/js/settings/wpml-domain-validation.js',
		array( "jquery" ) );
}

if ( is_admin() ) {
	add_action( 'admin_enqueue_scripts', 'wpml_register_js_scripts' );
} else {
	add_action( 'wp_enqueue_scripts', 'wpml_register_js_scripts' );
}
