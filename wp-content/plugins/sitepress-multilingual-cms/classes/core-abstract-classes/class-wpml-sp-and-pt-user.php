<?php

abstract class WPML_SP_And_PT_User extends WPML_SP_User {

	/** @var  WPML_Post_Translation $post_translation */
	protected $post_translation;

	/**
	 * @param WPML_Post_Translation $post_translation
	 * @param SitePress             $sitepress
	 */
	public function __construct( &$post_translation, &$sitepress ) {
		parent::__construct( $sitepress );
		$this->post_translation = &$post_translation;
	}
}