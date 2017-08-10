<?php

class WPML_PB_Shortcodes {

	/** @var  WPML_PB_Shortcode_Strategy $shortcode_strategy */
	private $shortcode_strategy;


	public function __construct( WPML_PB_Shortcode_Strategy $shortcode_strategy ) {
		$this->shortcode_strategy = $shortcode_strategy;
	}

	public function get_shortcodes( $content ) {

		$shortcodes = array();
		$pattern    = get_shortcode_regex( $this->shortcode_strategy->get_shortcodes() );

		if ( preg_match_all( '/' . $pattern . '/s', $content, $matches ) && isset( $matches[5] ) && ! empty( $matches[5] ) ) {
			for ( $index = 0; $index < sizeof( $matches[0] ); $index ++ ) {
				$shortcode = array(
					'block'      => $matches[0][ $index ],
					'tag'        => $matches[2][ $index ],
					'attributes' => $matches[3][ $index ],
					'content'    => $matches[5][ $index ],
				);

				// @todo perhaps here we must check the case when parent tag contains nested shortcode and regular text and ingore recurrent call
				$nested_shortcodes = array();
				if ( $shortcode['content'] ) {
					$nested_shortcodes = $this->get_shortcodes( $shortcode['content'] );
					if ( count( $nested_shortcodes ) ) {
						$shortcode['content'] = '';
					}
				}

				if ( count( $nested_shortcodes ) ) {
					$shortcodes = array_merge( $shortcodes, $nested_shortcodes );
				}
				$shortcodes[] = $shortcode;
			}
		}

		return $shortcodes;
	}
}
