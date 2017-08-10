<?php

/**
 * WPML_ST_String_Statuses class
 *
 * Get the translation status text for the given status
 */

class WPML_ST_String_Statuses {

	public static function get_status( $status ) {
		switch ( $status ) {
			case ICL_STRING_TRANSLATION_COMPLETE:
				return __( 'Translation complete', 'wpml-string-translation' );
			
			case ICL_STRING_TRANSLATION_PARTIAL:
				return __( 'Partial translation', 'wpml-string-translation' );
			
			case ICL_STRING_TRANSLATION_NEEDS_UPDATE:
				return __( 'Translation needs update', 'wpml-string-translation' );
			
			case ICL_STRING_TRANSLATION_NOT_TRANSLATED:
				return __( 'Not translated', 'wpml-string-translation' );
			
			case ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR:
				return __( 'Waiting for translator', 'wpml-string-translation' );
			
		}
		
		return '';
	}

}