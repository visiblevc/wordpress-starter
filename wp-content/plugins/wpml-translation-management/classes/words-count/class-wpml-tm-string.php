<?php

class WPML_TM_String extends WPML_TM_Translatable_Element {
	private $element_type;

	/**
	 * @var WPDB
	 */
	private $wpdb;
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var array|null|WP_Post
	 */
	private $string;

	public function __construct( $id, &$sitepress, &$wpdb ) {
		parent::__construct( $id );

		$this->wpdb          = &$wpdb;
		$this->sitepress     = &$sitepress;
		$this->string        = $this->get_string( $id );
		$this->element_type  = 'string';
		$this->language_code = $this->get_language_for_element( $id );
	}

	public function get_words_count() {
		$current_document = $this->string;
		$count            = $this->estimate_word_count();

		$count = apply_filters( 'wpml_tm_string_words_count', $count, $current_document );

		return $count;
	}

	private function estimate_word_count() {
		$words = 0;
		if ( isset( $this->language_code ) && $this->string ) {
			$words += $this->get_string_words_count( $this->language_code, $this->string->value );
		}

		return $words;
	}

	public function get_type_name( $label = null ) {
		return __( 'String', 'wpml-translation-management' );
	}

	private function get_string( $id ) {
		$string_query = "SELECT * FROM {$this->wpdb->prefix}icl_strings WHERE id=%s";
		$string_prepared = $this->wpdb->prepare($string_query, $id);
		return $this->wpdb->get_row($string_prepared);
	}

	private function get_language_for_element( $id ) {
		$string_query = "SELECT language FROM {$this->wpdb->prefix}icl_strings WHERE id=%s";
		$string_prepared = $this->wpdb->prepare($string_query, $id);
		return $this->wpdb->get_var($string_prepared);
	}
}