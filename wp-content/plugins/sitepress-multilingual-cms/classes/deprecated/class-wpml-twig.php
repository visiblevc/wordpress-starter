<?php

/**
 * Class WPML_Twig
 *
 * @deprecated @since 3.3.3
 */
class WPML_Twig {
	function __construct() {
		if ( ! class_exists( 'Twig_Autoloader' ) ) {
			require_once dirname( __FILE__ ) . '/../embedded/Twig/twig/lib/Twig/Autoloader.php';
		}
		add_action( 'init', array( $this, 'autoload' ) );
	}

	function autoload() {
		Twig_Autoloader::register();
	}
}
