<?php

class WPML_Get_Page_By_Path extends WPML_WPDB_And_SP_User {

	private $language;

	public function get( $page_name, $lang, $output = OBJECT, $post_type = 'page' ) {
		$this->language = $lang;
		add_filter( 'query', array( $this, 'get_page_by_path_filter' ) );
		$temp_lang_switch = new WPML_Temporary_Switch_Language( $this->sitepress, $lang );
		$page = get_page_by_path( $page_name, $output, $post_type );
		$temp_lang_switch->restore_lang();
		remove_filter( 'query', array( $this, 'get_page_by_path_filter' ) );
		return $page;
	}

	public function get_page_by_path_filter( $query ) {

		$debug_backtrace = $this->sitepress->get_backtrace( 6, true );

		if ( isset( $debug_backtrace[5]['function'] ) && $debug_backtrace[5]['function'] == 'get_page_by_path' ) {

			$where = $this->wpdb->prepare( "ID IN ( SELECT element_id FROM {$this->wpdb->prefix}icl_translations WHERE language_code = %s AND element_type LIKE 'post_%%' ) AND ", $this->language );

			$query = str_replace( "WHERE ", "WHERE " . $where, $query );
		}

		return $query;
	}

}
