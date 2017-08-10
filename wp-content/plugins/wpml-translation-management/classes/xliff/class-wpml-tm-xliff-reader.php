<?php

abstract class WPML_TM_Xliff_Reader extends WPML_TM_Xliff_Shared {

	/**
	 * @param string $content Xliff file string content
	 *
	 * @return array
	 */
	public abstract function get_data( $content );

	/**
	 * Parse a XML containing the XLIFF
	 *
	 * @param string $content
	 *
	 * @return SimpleXMLElement|WP_Error The parsed XLIFF or a WP error in case it could not be parsed
	 */
	protected function load_xliff( $content ) {
		try {
			$xml = simplexml_load_string( $content );
		} catch ( Exception $e ) {
			$xml = false;
		}

		return $xml ? $xml
			: new WP_Error( 'not_xml_file',
				sprintf(
					__( 'The xliff file could not be read.', 'wpml-translation-management' )
				) );
	}
}