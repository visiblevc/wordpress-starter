<?php

class WPML_BBPress_Filters extends WPML_SP_User {
	private $wpml_bbpress_api;
	private $wpml_url_converter;

	/**
	 * WPML_BBPress_Filters constructor.
	 *
	 * @param WPML_BBPress_API   $wpml_bbpress_api
	 * @param SitePress          $sitepress
	 * @param WPML_URL_Converter $wpml_url_converter
	 */
	public function __construct( $wpml_bbpress_api, $sitepress, $wpml_url_converter ) {
		$this->wpml_bbpress_api   = &$wpml_bbpress_api;
		$this->wpml_url_converter = &$wpml_url_converter;
		parent::__construct( $sitepress );
	}

	public function __destruct() {
		$this->remove_hooks();
	}

	public function add_hooks() {
		add_filter( 'author_link', array( $this, 'author_link_filter' ), 10, 3 );
	}

	public function remove_hooks() {
		remove_filter( 'author_link', array( $this, 'author_link_filter' ), 10 );
	}

	public function author_link_filter( $link, $author_id, $author_nicename ) {
		return $this->wpml_bbpress_api->bbp_get_user_profile_url( $author_id, $author_nicename );
	}
}
