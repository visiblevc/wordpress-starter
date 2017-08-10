<?php

class WPML_Cookie {

	/**
	 * @param string $name
	 * @param string $value
	 * @param        $expires
	 * @param string $path
	 * @param        $domain
	 */
	public function set_cookie( $name, $value, $expires, $path, $domain ) {
		if ( $this->should_set_cookie() ) {
			setcookie( $name, $value, $expires, $path, $domain );
		}
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function get_cookie( $name ) {
		if ( isset( $_COOKIE[ $name ] ) ) {
			return $_COOKIE[ $name ];
		} else {
			return '';
		}
	}

	/**
	 * simple wrapper for \headers_sent
	 *
	 * @return bool
	 */
	public function headers_sent() {

		return headers_sent();
	}

	private function should_set_cookie() {
		return is_user_logged_in();
	}
}
