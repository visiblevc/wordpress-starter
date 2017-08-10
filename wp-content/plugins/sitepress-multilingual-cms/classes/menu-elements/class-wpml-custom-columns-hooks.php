<?php

/**
 * Class WPML_Custom_Columns_Hooks
 */
class WPML_Custom_Columns_Hooks extends WPML_WPDB_And_SP_User {

	/**
	 * @var WPML_Custom_Columns
	 */
	private $custom_columns;

	/**
	 * WPML_Custom_Columns constructor.
	 *
	 * @param WPDB $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( &$wpdb, &$sitepress ) {
		parent::__construct( $wpdb, $sitepress );
		// column with links to translations (or add translation) - low priority
		add_action( 'admin_init', array( $this, 'add_custom_columns_hooks' ), 1010 ); // accommodate Types init@999
	}

	/**
	 * Add custom columns hooks.
	 */
	public function add_custom_columns_hooks() {
		if ( $this->has_custom_columns() ) {
			$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post';
			switch ( $post_type ) {
				case 'post':
				case 'page':
					add_filter( 'manage_' . $post_type . 's_columns', array( $this->get_custom_column_instance(), 'add_posts_management_column' ) );
					if ( $this->get_custom_column_instance()->show_management_column_content( $post_type ) ) {
						add_filter( 'manage_' . $post_type . 's_custom_column', array( $this->get_custom_column_instance(), 'add_content_for_posts_management_column' ) );
					}
					break;
				default:
					if ( in_array( $post_type, array_keys( $this->sitepress->get_translatable_documents() ), true ) ) {
						add_filter( 'manage_' . $post_type . '_posts_columns', array( $this->get_custom_column_instance(), 'add_posts_management_column' ) );
						if ( is_post_type_hierarchical( $post_type ) ) {
							if ( $this->get_custom_column_instance()->show_management_column_content( $post_type ) ) {
								add_action( 'manage_pages_custom_column', array( $this->get_custom_column_instance(), 'add_content_for_posts_management_column' ) );
								add_action( 'manage_posts_custom_column', array( $this->get_custom_column_instance(), 'add_content_for_posts_management_column' ) ); // add this too - for more types plugin
							}
						} else {
							if ( $this->get_custom_column_instance()->show_management_column_content( $post_type ) ) {
								add_action( 'manage_posts_custom_column', array( $this->get_custom_column_instance(), 'add_content_for_posts_management_column' ) );
							}
						}
					}
			}
		}
	}

	/**
	 * Check if we need to add custom columns on page.
	 *
	 * @return bool
	 */
	private function has_custom_columns() {
		global $pagenow;
		if ( 'edit.php' === $pagenow
		|| 'edit-pages.php' === $pagenow
		|| ( 'admin-ajax.php' === $pagenow
		     && ( 'inline-save' === filter_input( INPUT_POST, 'action' )
		          || 'fetch-list' === filter_input( INPUT_GET, 'action' )
		     ) )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get custom columns handler instance.
	 *
	 * @return WPML_Custom_Columns
	 */
	private function get_custom_column_instance() {
		if ( null === $this->custom_columns ) {
			$this->custom_columns = new WPML_Custom_Columns( $this->wpdb, $this->sitepress );
		}

		return $this->custom_columns;
	}
}
