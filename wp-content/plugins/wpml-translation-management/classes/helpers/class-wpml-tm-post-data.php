<?php

/**
 * Class WPML_TM_Post_Data
 */
class WPML_TM_Post_Data {

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public static function strip_slashes_for_single_quote( $data ) {
		return str_replace( '\\\'', '\'', $data );
	}

}