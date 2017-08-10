<?php

/**
 * @package wpml-core
 */
class WPML_UI_Help_Tab {
	private $wp_api;
	private $id;
	private $title;
	private $content;

	public function __construct( $wp_api, $id, $title, $content ) {
		$this->wp_api  = $wp_api;
		$this->id      = $id;
		$this->title   = $title;
		$this->content = $content;
	}
	
	public function init_hooks() {
		$this->wp_api->add_action( 'admin_head', array( $this, 'add_help_tab' ) );
	}
	
	public function add_help_tab() {
		$screen = $this->wp_api->get_current_screen();
		
		$screen->add_help_tab( array(
			'id'	    => $this->id,
			'title'	    => $this->title,
			'content'	=> $this->content,
		) );		
	}
	
}
