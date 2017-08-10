<?php

class WPML_Post_Hierarchy_Sync extends WPML_Hierarchy_Sync {

	protected $element_id_column        = 'ID';
	protected $parent_element_id_column = 'ID';
	protected $parent_id_column         = 'post_parent';
	protected $element_type_column      = 'post_type';
	protected $element_type_prefix      = 'post_';

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( &$wpdb ) {
		parent::__construct( $wpdb );
		$this->elements_table = $wpdb->posts;
	}
}