<?php

class WPML_Translate_Link_Targets {

	/* @var AbsoluteLinks $absolute_links */
	private $absolute_links;
	/* @var WPML_Absolute_To_Permalinks $permalinks_converter */
	private $permalinks_converter;
	
	/**
	 * WPML_Translate_Link_Targets constructor.
	 *
	 * @param AbsoluteLinks $absolute_links
	 * @param WPML_Absolute_To_Permalinks $permalinks_converter
	 */
	public function __construct( $absolute_links, $permalinks_converter ) {
		$this->absolute_links       = $absolute_links;
		$this->permalinks_converter = $permalinks_converter;
	}
	
	/**
	 * convert_text
	 *
	 * @param string $text
	 * @return string
	 */
	
	public function convert_text( $text ) {
		if ( is_string( $text ) ) {
			$text = $this->absolute_links->convert_text( $text );
			$text = $this->permalinks_converter->convert_text( $text );
		}
		return $text;
	}
}