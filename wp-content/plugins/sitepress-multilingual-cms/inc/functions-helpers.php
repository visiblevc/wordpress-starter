<?php
if ( ! function_exists( 'object_to_array' ) ) {
	function object_to_array( $obj ) {
		if ( is_object( $obj ) ) {
			$obj = (array) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = array();
			foreach ( $obj as $key => $val ) {
				$new[ $key ] = object_to_array( $val );
			}
		} else {
			$new = $obj;
		}

		return $new;
	}
}
