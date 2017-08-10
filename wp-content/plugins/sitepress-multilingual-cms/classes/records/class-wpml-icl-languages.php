<?php

class WPML_ICL_Languages extends WPML_WPDB_User {

	private $table = 'icl_languages';
	private $code;
	private $default_locale;

	/**
	 * WPML_TM_ICL_Translation_Status constructor.
	 *
	 * @param wpdb   $wpdb
	 * @param string $code
	 * @param string $type
	 */
	public function __construct( &$wpdb, $code, $type = 'code' ) {
		parent::__construct( $wpdb );
		$code = (string) $code;
		if ( $code !== '' && $type === 'code' ) {
			$this->code = $code;
		} elseif ( $type === 'default_locale' && $code !== '' ) {
			$this->default_locale = $code;
		} else {
			throw new InvalidArgumentException( 'Empty language code or invalid column type!' );
		}
	}

	public function exists() {

		return (bool) $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT code
									FROM {$this->wpdb->prefix}{$this->table}
									WHERE code= %s LIMIT 1",
				$this->code() ) );
	}

	public function code() {
		if ( ! $this->code ) {
			$this->code = $this->wpdb->get_var(
				$this->wpdb->prepare( " SELECT code
										FROM {$this->wpdb->prefix}{$this->table}
										WHERE default_locale=%s
										LIMIT 1",
					$this->default_locale ) );
		}

		return $this->code;
	}
}