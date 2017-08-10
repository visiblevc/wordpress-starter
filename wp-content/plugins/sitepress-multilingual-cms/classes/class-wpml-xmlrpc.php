<?php

/**
 * @author OnTheGo Systems
 */
class WPML_XMLRPC extends WPML_SP_User {
	private $xmlrpc_call_methods_for_save_post;

	/**
	 * WPML_XMLRPC constructor.
	 *
	 * @param SitePress        $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		parent::__construct( $sitepress );
		$this->xmlrpc_call_methods_for_save_post = array( 'wp.newPost', 'wp.editPost', 'wp.newPage', 'wp.editPage' );
	}

	public function init_hooks() {
		add_action( 'xmlrpc_call_success_mw_newPost', array( $this, 'meta_weblog_xmlrpc_post_update_action' ), 10, 2 );
		add_action( 'xmlrpc_call_success_mw_editPost', array( $this, 'meta_weblog_xmlrpc_post_update_action' ), 10, 2 );
		add_action( 'xmlrpc_call', array( $this, 'xmlrpc_call' ) );
		add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc_methods' ) );
	}

	function get_languages( $args ) {
		list( $blog_id, $username, $password ) = $args;

		if ( ! $this->sitepress->get_wp_api()->get_wp_xmlrpc_server()->login( $username, $password ) ) {
			return $this->sitepress->get_wp_api()->get_wp_xmlrpc_server()->error;
		}

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true ); // hack - allow to force display language
		}

		return $this->sitepress->get_active_languages( true );
	}

	public function get_post_trid( $args ) {
		list( $blog_id, $username, $password, $element_id ) = $args;

		if ( ! $this->sitepress->get_wp_api()->get_wp_xmlrpc_server()->login( $username, $password ) ) {
			return $this->sitepress->get_wp_api()->get_wp_xmlrpc_server()->error;
		}

		$post_element = new WPML_Post_Element( $element_id, $this->sitepress );

		return $post_element->get_trid();
	}

	/**
	 * @param int   $post_ID
	 * @param array $args
	 *
	 * @throws \UnexpectedValueException
	 * @throws \InvalidArgumentException
	 */
	public function meta_weblog_xmlrpc_post_update_action( $post_ID, $args ) {
		$custom_fields = array();
		if ( array_key_exists( 'custom_fields', $args[3] ) ) {
			foreach ( $args[3]['custom_fields'] as $cf ) {
				$custom_fields[ $cf['key'] ] = $cf['value'];
			}
		}
		$post_language_code = $this->sitepress->get_default_language();
		$trid               = false;
		if ( array_key_exists( '_wpml_language', $custom_fields ) ) {
			$post_language_code = $custom_fields['_wpml_language'];
		}
		if ( array_key_exists( '_wpml_trid', $custom_fields ) ) {
			$trid = $custom_fields['_wpml_trid'];
		}
		$post_type = 'post';
		if ( array_key_exists( 'post_type', $args[3] ) ) {
			$post_type = $args[3]['post_type'];
		}

		$this->set_post_language( $post_ID, $post_type, $post_language_code, $trid );
	}

	public function save_post_action( $pidd, $post ) {
		$post_language_code = get_post_meta( $pidd, '_wpml_language', true );
		$post_language_code = $post_language_code ? $post_language_code : $this->sitepress->get_default_language();

		$trid = get_post_meta( $pidd, '_wpml_trid', true );
		$trid = $trid ? $trid : false;

		$this->set_post_language( $pidd, $post->post_type, $post_language_code, $trid );
	}

	/**
	 * @param int      $post_ID
	 * @param string   $post_type
	 * @param string   $post_language_code
	 * @param int|bool $trid
	 *
	 * @throws \InvalidArgumentException
	 * @throws \UnexpectedValueException
	 */
	private function set_post_language( $post_ID, $post_type, $post_language_code, $trid = false ) {
		if ( $post_language_code && $this->sitepress->is_translated_post_type( $post_type ) ) {
			$wpml_translations = new WPML_Translations( $this->sitepress );
			$post_element      = new WPML_Post_Element( $post_ID, $this->sitepress );
			if ( $post_language_code ) {
				$wpml_translations->set_language_code( $post_element, $post_language_code );
			}
			if ( $trid ) {
				$wpml_translations->set_trid( $post_element, $trid );
			}
		}
	}

	public function xmlrpc_call( $action ) {
		if ( in_array( $action, $this->xmlrpc_call_methods_for_save_post, true ) ) {
			add_action( 'save_post', array( $this, 'save_post_action' ), 10, 2 );
		}
	}

	public function xmlrpc_methods( $methods ) {
		/**
		 * Parameters:
		 * - int blog_id
		 * - string username
		 * - string password
		 * - int post_id
		 * Returns:
		 * - struct
		 *   - int trid
		 */
		$methods['wpml.get_post_trid'] = array( $this, 'get_post_trid' );
		/**
		 * Parameters:
		 * - int blog_id
		 * - string username
		 * - string password
		 * Returns:
		 * - struct
		 *   - array active languages
		 */
		$methods['wpml.get_languages'] = array( $this, 'get_languages' );

		return apply_filters( 'wpml_xmlrpc_methods', $methods );
	}
}
