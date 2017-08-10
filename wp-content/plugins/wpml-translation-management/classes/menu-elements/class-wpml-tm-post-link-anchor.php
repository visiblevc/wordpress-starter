<?php

abstract class WPML_TM_Post_Link_Anchor extends WPML_TM_Post_Link {

	/**
	 * @var string $anchor
	 */
	private $anchor;

	public function __construct( &$sitepress, $post_id, $anchor ) {
		parent::__construct( $sitepress, $post_id );
		$this->anchor = $anchor;
	}

	public function __toString() {
		$opost = $this->sitepress->get_wp_api()->get_post( $this->post_id );

		return ! $opost
		       || ( in_array( $opost->post_status,
				array( 'draft', 'private', 'trash' ), true )
		            && $opost->post_author != $this->sitepress->get_wp_api()
		                                                      ->get_current_user_id() )
			? '' : sprintf( '<a href="%s">%s</a>',
				esc_url( $this->link_target() ),
				esc_html( $this->anchor ) );
	}

	protected abstract function link_target();
}