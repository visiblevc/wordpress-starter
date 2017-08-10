<?php

class WPML_ST_Domain_Fallback {
	/**
	 * @var array
	 */
	private $domains = array( 'default', 'WordPress' );

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	public function has_fallback_domain( $domain ) {
		return in_array( $domain, $this->domains );
	}

	/**
	 * @param string $domain
	 *
	 * @return string
	 */
	public function get_fallback_domain( $domain ) {
		return 'default' === $domain ? 'WordPress' : 'default';
	}
}