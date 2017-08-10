<?php

class WPML_TM_String_Xliff_Reader extends WPML_TM_Xliff_Reader {

	/**
	 * Retrieve the string translations from a XLIFF
	 *
	 * @param string $content The XLIFF representing a set of strings
	 *
	 * @return WP_Error|array The string translation representation or WP_Error
	 * on failure
	 */
	public function get_data( $content ) {
		$xliff = $this->load_xliff( $content );
		$data  = array();
		/** @var SimpleXMLElement $node */
		foreach ( $xliff->{'file'}->{'body'}->children() as $node ) {
			$target = $this->get_xliff_node_target( $node );

			if ( ! $target  && $target !== "0" ) {
				return $this->invalid_xliff_error();
			}
			$target                       = str_replace( '<br class="xliff-newline" />', "\n", $target );
			$attr                         = $node->attributes();
			$data[ (string) $attr['id'] ] = $target;
		}

		return $data;
	}
}