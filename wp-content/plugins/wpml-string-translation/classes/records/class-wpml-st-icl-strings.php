<?php

class WPML_ST_ICL_Strings extends WPML_WPDB_User {

	private $table = 'icl_strings';
	private $string_id = 0;

	/**
	 * WPML_TM_ICL_Strings constructor.
	 *
	 * @param wpdb $wpdb
	 * @param int  $string_id
	 */
	public function __construct( &$wpdb, $string_id ) {
		parent::__construct( $wpdb );
		$string_id = (int) $string_id;
		if ( $string_id > 0 ) {
			$this->string_id = $string_id;
		} else {
			throw new InvalidArgumentException( 'Invalid String ID: ' . $string_id );
		}
	}

	/**
	 * @param array $args in the same format used by \wpdb::update()
	 *
	 * @return $this
	 */
	public function update( $args ) {
		$this->wpdb->update(
			$this->wpdb->prefix . $this->table, $args, array( 'id' => $this->string_id ) );

		return $this;
	}

	/**
	 * @return string
	 */
	public function value() {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT value
									FROM {$this->wpdb->prefix}{$this->table}
									WHERE id = %d LIMIT 1",
				$this->string_id ) );
	}

	/**
	 * @return string
	 */
	public function language() {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT language
									FROM {$this->wpdb->prefix}{$this->table}
									WHERE id = %d LIMIT 1",
				$this->string_id ) );
	}

	/**
	 * @return int
	 */
	public function status() {

		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT status
									FROM {$this->wpdb->prefix}{$this->table}
									WHERE id = %d LIMIT 1",
				$this->string_id ) );
	}
}