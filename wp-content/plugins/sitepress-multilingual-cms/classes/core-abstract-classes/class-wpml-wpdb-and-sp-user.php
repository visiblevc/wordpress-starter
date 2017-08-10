<?php

/**
 * Class WPML_WPDB_And_SP_User
 */
abstract class WPML_WPDB_And_SP_User extends WPML_WPDB_User {

	/** @var SitePress $sitepress */
	protected $sitepress;

	/**
	 * @param wpdb      $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( &$wpdb, &$sitepress ) {
		parent::__construct( $wpdb );
		$this->sitepress = &$sitepress;
	}
}