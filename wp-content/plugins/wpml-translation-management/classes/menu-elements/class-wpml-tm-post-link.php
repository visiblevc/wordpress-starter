<?php

abstract class WPML_TM_Post_Link extends WPML_SP_User {

	/**
	 * @var int $post
	 */
	protected $post_id;

	/**
	 * WPML_TM_Post_Link constructor.
	 *
	 * @param SitePress $sitepress
	 * @param int       $post_id
	 */
	public function __construct( &$sitepress, $post_id ) {
		parent::__construct( $sitepress );
		$this->post_id = $post_id;
	}
}