<?php

class WPML_PB_Update_API_Hooks_In_Content {

	/** @var  WPML_PB_API_Hooks_Strategy $strategy */
	private $strategy;

	public function __construct( WPML_PB_API_Hooks_Strategy $strategy ) {
		$this->strategy = $strategy;
	}

	public function update( $translated_post_id, $original_post, $string_translations, $lang ) {
		do_action(
			'wpml_page_builder_string_translated',
			$this->strategy->get_package_kind(),
			$translated_post_id,
			$original_post,
			$string_translations,
			$lang
		);

	}

}
