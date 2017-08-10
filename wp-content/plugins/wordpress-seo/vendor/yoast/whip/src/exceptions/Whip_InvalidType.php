<?php

/**
 * Class InvalidType
 */
class Whip_InvalidType extends Exception {

	/**
	 * InvalidType constructor.
	 *
	 * @param string    $property
	 * @param string       $value
	 * @param string $expectedType
	 */
	public function __construct( $property, $value, $expectedType ) {
		parent::__construct( sprintf( '%s should be of type %s. Found %s.', $property, $expectedType, gettype( $value ) ) );
	}
}
