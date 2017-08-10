<?php

/**
 * Exception for an invalid version comparison string
 */
class Whip_InvalidVersionComparisonString extends Exception {

	/**
	 * @param string $value The passed version comparison string.
	 */
	public function __construct( $value ) {
		parent::__construct(
			sprintf(
				'Invalid version comparison string. Example of a valid version comparison string: >=5.3. Passed version comparison string: %s',
				$value
			)
		);
	}
}
