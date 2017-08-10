<?php

class WPML_Term_Hierarchy_Sync extends WPML_Hierarchy_Sync {

	protected $element_id_column        = 'term_taxonomy_id';
	protected $parent_id_column         = 'parent';
	protected $parent_element_id_column = 'term_id';
	protected $element_type_column      = 'taxonomy';
	protected $element_type_prefix      = 'tax_';

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( &$wpdb ) {
		parent::__construct( $wpdb );

		$this->elements_table = $wpdb->term_taxonomy;
	}

	public function is_need_sync( $taxonomy, $ref_lang = false ) {

		return (bool) $this->get_unsynced_elements( $taxonomy, $ref_lang );
	}

	public function sync_element_hierarchy( $element_types, $ref_lang = false ) {
		/** @var WPML_Term_Filters $wpml_term_filters_general */
		global $wpml_term_filters_general;

		parent::sync_element_hierarchy( $element_types, $ref_lang );
		do_action( 'wpml_sync_term_hierarchy_done' );

		$element_types = (array) $element_types;

		foreach ( $element_types as $taxonomy ) {
			$wpml_term_filters_general->update_tax_children_option( $taxonomy );
		}
	}
}