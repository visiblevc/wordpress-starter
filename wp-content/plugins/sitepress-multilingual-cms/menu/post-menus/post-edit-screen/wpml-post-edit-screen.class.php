<?php

class WPML_Post_Edit_Screen extends WPML_SP_User {

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( &$sitepress ) {
		parent::__construct( $sitepress );
		if ( $this->sitepress->get_setting( 'language_negotiation_type' ) == 2 ) {
			add_filter( 'preview_post_link', array( $this, 'preview_post_link_filter' ), 10, 1 );
			add_filter( 'preview_page_link ', array( $this, 'preview_post_link_filter' ), 10, 1 );
		}
		add_action( 'wpml_scripts_setup', array( $this, 'scripts_setup' ), 10, 1 );
	}

	/**
	 * Enqueues scripts and styles for the post edit screen.
	 */
	function scripts_setup() {
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_style( 'sitepress-post-edit',
		                  ICL_PLUGIN_URL . '/res/css/post-edit.css',
		                  array(),
		                  ICL_SITEPRESS_VERSION );
		wp_enqueue_script( 'sitepress-post-edit',
		                   ICL_PLUGIN_URL . '/res/js/post-edit.js',
		                   array( 'jquery-ui-dialog', 'jquery-ui-autocomplete' ),
		                   ICL_SITEPRESS_VERSION );
	}

	/**
	 * Filters the preview links on the post edit screen so that they always point to the currently used language
	 * domain. This ensures that the user can actually see the preview, as he might not have the login cookie set for
	 * the actual language domain of the post.
	 *
	 * @param string $link
	 *
	 * @return mixed
	 */
	public function preview_post_link_filter( $link ) {
		$original_host = filter_var( $_SERVER['HTTP_HOST'], FILTER_SANITIZE_STRING );
		if ( $original_host ) {
			$domain = wpml_parse_url( $link, PHP_URL_HOST );
			$link   = str_replace( '//' . $domain . '/', '//' . $original_host . '/', $link );
		}

		return $link;
	}
}