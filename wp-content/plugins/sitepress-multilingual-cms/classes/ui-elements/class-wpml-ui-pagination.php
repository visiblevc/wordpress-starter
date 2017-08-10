<?php
/**
 * @package wpml-core
 */

class WPML_UI_Pagination extends WP_List_Table {
	public function __construct( $total, $number_per_page ) {
		parent::__construct();
		
		$this->set_pagination_args( array( 'total_items' => $total,
										   'per_page'    => $number_per_page ) );
	}
	
	public function show() {
		$this->pagination( 'bottom' );
	}
}
