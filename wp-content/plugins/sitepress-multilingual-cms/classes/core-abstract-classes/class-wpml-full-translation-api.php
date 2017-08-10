<?php

class WPML_Full_Translation_API extends WPML_Full_PT_API {

	/** @var  WPML_Term_Translation $term_translations */
	protected $term_translations;

	/**
	 * @param SitePress             $sitepress
	 * @param wpdb                  $wpdb
	 * @param WPML_Post_Translation $post_translations
	 * @param WPML_Term_Translation $term_translations
	 */
	function __construct( &$sitepress, &$wpdb, &$post_translations, &$term_translations ) {
		parent::__construct( $wpdb, $sitepress, $post_translations );
		$this->term_translations = &$term_translations;
	}
}