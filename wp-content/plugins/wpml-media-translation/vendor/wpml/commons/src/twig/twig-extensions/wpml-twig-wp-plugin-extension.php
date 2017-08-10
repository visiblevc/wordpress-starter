<?php

class WPML_Twig_WP_Plugin_Extension extends Twig_Extension {

	/**
	 * Returns the name of the extension.
	 * @return string The extension name
	 */
	public function getName() {
		return 'wp_plugin';
	}

	public function getFilters() {
		return array(
			new Twig_SimpleFilter( 'wp_do_action', array( $this, 'wp_do_action_filter' ) ),
		);
	}

	public function wp_do_action_filter( $tag ) {
		do_action( $tag );
	}
}
