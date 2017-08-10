<?php

/**
 * WPML_ST_Admin_String class
 */
Class WPML_ST_Admin_String extends WPML_ST_String {

	/**
	 * @var string $name
	 */
	private $name;

	/**
	 * @var string $value
	 */
	private $value;

	/**
	 * @param string $new_value
	 */
	public function update_value( $new_value ) {
		$this->fetch_name_and_value();
		if ( md5( $this->value ) !== $this->name ) {
			$this->value = $new_value;
			$this->set_property( 'value', $new_value );
			$this->update_status();
		}
	}

	private function fetch_name_and_value() {
		if ( is_null( $this->name ) || is_null( $this->value ) ) {
			$res = $this->wpdb->get_row(
				"SELECT name, value  " . $this->from_where_snippet() );
			$this->name  = $res->name;
			$this->value = $res->value;
		}
	}
}