<?php

class WPML_WPSEO_Redirection {

	private $wp_option_redirections = 'wpseo-premium-redirects-base';

	/**
	 * @return bool
	 */
	function is_redirection() {
		$redirections = $this->get_all_redirections();
		$url = $_SERVER['REQUEST_URI'];
		if( is_array( $redirections ) ) {
			foreach ( $redirections as $redirection ) {
				if ( $redirection['origin'] === $url || '/' . $redirection['origin'] === $url ){
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return mixed|void
	 */
	private function get_all_redirections() {
		return get_option( $this->wp_option_redirections );
	}
}