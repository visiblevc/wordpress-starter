<?php

/**
 * Class WPML_WPDB_And_SP_User
 */
abstract class WPML_Full_PT_API extends WPML_WPDB_And_SP_User {

	/** @var  WPML_Post_Translation $post_translations */
	protected $post_translations;

	/**
	 * @param wpdb                  $wpdb
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translations
	 */
	public function __construct( &$wpdb, &$sitepress, &$post_translations ) {
		parent::__construct( $wpdb, $sitepress );
		$this->post_translations = &$post_translations;
	}
}