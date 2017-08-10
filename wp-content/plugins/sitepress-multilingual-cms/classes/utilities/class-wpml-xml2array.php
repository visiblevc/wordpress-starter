<?php

class WPML_XML2Array {
	public function __construct( &$contents, $get_attributes = true ) {

		$this->contents       = $contents;
		$this->get_attributes = $get_attributes;
	}

	public function run() {

		$xml_values = array();

		if ( $this->contents && function_exists( 'xml_parser_create' ) ) {
			$parser = xml_parser_create();
			xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
			xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
			xml_parse_into_struct( $parser, $this->contents, $xml_values );
			xml_parser_free( $parser );
		}

		//Initializations
		$xml_array = array();

		$current = &$xml_array;

		//Go through the tags.
		foreach ( $xml_values as $data ) {
			unset( $attributes, $value );//Remove existing values, or there will be trouble

			$tag        = $data['tag'];
			$type       = $data['type'];
			$level      = $data['level'];
			$value      = ( isset( $data['value'] ) ) ? $data['value'] : '';
			$attributes = isset( $data['attributes'] ) ? $data['attributes'] : array();
			$result     = array();

			if ( $this->get_attributes ) {//The second argument of the function decides this.
				$result = array();
				if ( isset( $value ) ) {
					$result['value'] = $value;
				}

				//Set the attributes too.
				if ( isset( $attributes ) ) {
					foreach ( $attributes as $attr => $val ) {
						if ( $this->get_attributes == 1 ) {
							$result['attr'][ $attr ] = $val;
						} //Set all the attributes in a array called 'attr'
						//TODO: [WPML 3.2] should we change the key name to 'wpml_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */
					}
				}
			} elseif ( isset( $value ) ) {
				$result = $value;
			}

			//See tag status and do the needed.
			if ( $type == "open" ) {//The starting of the tag '<tag>'
				$parent[ $level - 1 ] = &$current;

				if ( ! is_array( $current ) or ( ! in_array( $tag, array_keys( $current ) ) ) ) { //Insert New tag
					$current[ $tag ] = $result;
					$current         = &$current[ $tag ];

				} else { //There was another element with the same tag name
					if ( isset( $current[ $tag ][0] ) ) {
						array_push( $current[ $tag ], $result );
					} else {
						$current[ $tag ] = array( $current[ $tag ], $result );
					}
					$last    = count( $current[ $tag ] ) - 1;
					$current = &$current[ $tag ][ $last ];
				}

			} elseif ( $type == "complete" ) { //Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if ( ! isset( $current[ $tag ] ) ) { //New Key
					$current[ $tag ] = $result;

				} else { //If taken, put all things inside a list(array)
					if ( ( is_array( $current[ $tag ] ) and $this->get_attributes == 0 )//If it is already an array...
					     or ( isset( $current[ $tag ][0] ) and is_array( $current[ $tag ][0] ) and $this->get_attributes == 1 )
					) {
						array_push( $current[ $tag ], $result ); // ...push the new element into that array.
					} else { //If it is not an array...
						$current[ $tag ] = array(
							$current[ $tag ],
							$result
						); //...Make it an array using using the existing value and the new value
					}
				}

			} elseif ( $type == 'close' ) { //End of tag '</tag>'
				$current = &$parent[ $level - 1 ];
			}
		}

		return ( $xml_array );
	}

}