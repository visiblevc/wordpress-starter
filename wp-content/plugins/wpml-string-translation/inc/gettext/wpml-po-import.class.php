<?php

class WPML_PO_Import {

	private $lines;
	private $strings;
	private $error_str;
	
	public function __construct( $file_name ) {

		global $wpdb;
		
		$this->strings = array( );
		$this->error_str = '';
		$this->lines   = file( $file_name );

		$fuzzy = 0;
		$name = false;
		$context = '';
		for ( $k = 0; $k < count( $this->lines ); $k ++ ) {
			$date_time_flag = false;
			if ( 0 === strpos( $this->lines[ $k ], '#, fuzzy' ) ) {
				$fuzzy = 1;
				$k ++;
			}
			if ( 0 === strpos( $this->lines[ $k ], '# wpml-name: ' ) ) {
				$name = preg_replace( "/^# wpml-name: /i", '', trim( $this->lines[ $k ] ) );
				$k ++;
			}

			if ( preg_match( '#msgctxt "(.*)"#im', trim( $this->lines[ $k ] ), $matches ) ) { //we look for the line that poedit needs for unique identification of the string

				$context = $matches[ 1 ];
				//if ( preg_match( '/wpmldatei18/', $this->lines[ $k ] ) ) { //if it contains the date_time setting we add the flag to escape the control structures in the date time placeholder string
				//	$date_time_flag = true;
				//}
				$k ++;
			}
			$int = preg_match( '#msgid "(.*)"#im', trim( $this->lines[ $k ] ), $matches );
			if ( $int ) {
				list( $string, $k ) = $this->get_string( $matches[1], $k );
				
				$int    = preg_match( '#msgstr "(.*)"#im', trim( $this->lines[ $k + 1 ] ), $matches );
				if ( $int ) {
					list( $translation, $k ) = $this->get_string( $matches[ 1 ], $k + 1 );
				} else {
					$translation = "";
				}

				if ( $name === false ) {
					$name = md5( $string );
				}

				if ( $string ) {
					$string_exists = $wpdb->get_var( $wpdb->prepare( "
														SELECT id FROM {$wpdb->prefix}icl_strings 
														WHERE context=%s AND name=%s AND gettext_context=%s",
														esc_sql( $_POST[ 'icl_st_i_context_new' ] ? $_POST[ 'icl_st_i_context_new' ] : $_POST[ 'icl_st_i_context' ] ),
														$name,
														$context
														)
													);
	
					if ( $date_time_flag ) {
						$string      = str_replace( "\\\\", "\\", $string );
						$translation = str_replace( "\\\\", "\\", $translation );
						$name        = str_replace( "\\\\", "\\", $name );
					}
	
					$this->strings[ ] = array(
						'string'      => $string,
						'translation' => $translation,
						'name'        => $name,
						'fuzzy'       => $fuzzy,
						'exists'      => $string_exists,
						'context'     => $context
					);
				}
				$k ++;
				
				$name    = false;
				$context = '';
			}
			if ( $k < count( $this->lines ) && ! trim( $this->lines[ $k ] ) ) {
				$fuzzy = 0;
			}
		}
		if ( empty( $this->strings ) ) {
			$this->error_str = __( 'No string found', 'wpml-string-translation' );
		}
		
	}
	
	private function get_string( $string, $k ) {

		$string = $this->strip_slashes( $string );
		// check for multiline strings
		if ( $k + 1 < count( $this->lines ) ) {
			$int    = preg_match( '#^"(.*)"$#', trim( $this->lines[ $k + 1 ] ), $matches );
			while ( $int ) {
				$string .= $this->strip_slashes( $matches[ 1 ] );
				$k++;
				if ( $k + 1 < count( $this->lines ) ) {
					$int    = preg_match( '#^"(.*)"$#', trim( $this->lines[ $k + 1 ] ), $matches );
				} else {
					$int = false;
				}
			}
		}
		
		return array( $string, $k );
		
	}
	
	private function strip_slashes( $string ) {
		$string = str_replace( '\"', '"', $string );
		$string = str_replace( '\\\\', '\\', $string );
		return $string;		
	}
	
	public function has_strings( ) {
		return ! empty( $this->strings );
	}
	
	public function get_strings( ) {
		return $this->strings;
	}
	
	public function get_errors( ) {
		return $this->error_str;
	}
}
