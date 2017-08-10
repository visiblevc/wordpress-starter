<?php

/**
 * Class WPML_SP_User
 *
 * Superclass for all WPML classes using the @global SitePress $sitepress directly
 *
 * @since 3.2.3
 */
abstract class WPML_SP_User {

	/** @var  SitePress $sitepress */
	protected $sitepress;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( &$sitepress ) {
		$this->sitepress = &$sitepress;
	}
}
