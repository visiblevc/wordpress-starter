<?php

/**
 * Class WPML_Post_Synchronization
 *
 * @package    wpml-core
 * @subpackage post-translation
 */

class WPML_Post_Synchronization extends WPML_SP_And_PT_User {

	/** @var bool[] */
	private $sync_parent_cpt = array();
	/** @var $sync_parent bool */
	private $sync_parent;
	/** @var $sync_delete bool */
	private $sync_delete;
	/** @var $sync_ping_status bool */
	private $sync_ping_status;
	/** @var $sync_post_date bool */
	private $sync_post_date;
	/** @var $sync_sticky_flag bool */
	private $sync_sticky_flag;
	/** @var $sync_post_format bool */
	private $sync_post_format;
	/** @var $sync_comment_status bool */
	private $sync_comment_status;
	/** @var $sync_page_template bool */
	private $sync_page_template;
	/** @var bool $sync_menu_order */
	private $sync_menu_order;
	/** @var $sync_password bool */
	private $sync_password;
	/** @var $sync_private_flag bool */
	private $sync_private_flag;

	/**
	 * @param array                 $settings
	 * @param WPML_Post_Translation $post_translations
	 * @param SitePress             $sitepress
	 */
	public function __construct( &$settings, &$post_translations, &$sitepress ) {
		parent::__construct( $post_translations, $sitepress );
		$this->sync_delete          = isset( $settings[ 'sync_delete' ] ) ? $settings[ 'sync_delete' ] : false;
		$this->sync_parent          = isset( $settings[ 'sync_page_parent' ] ) ? $settings[ 'sync_page_parent' ] : false;
		$this->sync_ping_status     = isset( $settings[ 'sync_ping_status' ] ) ? $settings[ 'sync_ping_status' ] : false;
		$this->sync_post_date       = isset( $settings[ 'sync_post_date' ] ) ? $settings[ 'sync_post_date' ] : false;
		$this->sync_post_format     = isset( $settings[ 'sync_post_format' ] ) ? $settings[ 'sync_post_format' ] : false;
		$this->sync_sticky_flag     = isset( $settings[ 'sync_sticky_flag' ] ) ? $settings[ 'sync_sticky_flag' ] : false;
		$this->sync_comment_status  = isset( $settings[ 'sync_comment_status' ] ) ? $settings[ 'sync_comment_status' ] : false;
		$this->sync_page_template   = isset( $settings[ 'sync_page_template' ] ) ? $settings[ 'sync_page_template' ] : false;
		$this->sync_password        = isset( $settings[ 'sync_password' ] ) ? $settings[ 'sync_password' ] : false;
		$this->sync_private_flag    = isset( $settings[ 'sync_private_flag' ] ) ? $settings[ 'sync_private_flag' ] : false;
		$this->sync_document_status = isset( $settings[ 'translated_document_status' ] ) ? $settings[ 'translated_document_status' ] : 1;
		$this->sync_menu_order      = isset( $settings[ 'sync_page_ordering' ] ) ? $settings[ 'sync_page_ordering' ] : array();
	}

	private function must_sync_parents( $post_type ) {
		if ( ! array_key_exists( $post_type, $this->sync_parent_cpt ) ) {
			$this->sync_parent_cpt[ $post_type ] = apply_filters( 'wpml_sync_parent_for_post_type', $this->sync_parent, $post_type );
		}

		return $this->sync_parent_cpt[ $post_type ];
	}

	/**
	 * Fixes parents of translations for hierarchical post types
	 *
	 * User changed parent for a post in $post_type and we are setting proper parent for $translation_id in
	 * $language_code_translated language
	 *
	 * @param string $post_type - post_type that should have the translated parents fixed
	 */
	private function maybe_fix_translated_parent( $post_type ) {
		if ( $this->must_sync_parents( $post_type ) ) {
			$sync_helper = wpml_get_hierarchy_sync_helper();
			$sync_helper->sync_element_hierarchy( $post_type );
		}
	}

	public function sync_with_duplicates( $post_id ) {
		$duplicates = $this->sitepress->get_duplicates( $post_id );
		foreach ( array_keys( $duplicates ) as $lang_code ) {
			$this->sitepress->make_duplicate( $post_id, $lang_code );
		}
	}

	public function delete_post_actions( $post_id, $keep_db_entries = false ) {
		$post_type            = get_post_type( $post_id );
		$post_type_exceptions = array( 'nav_menu_item' );
		if ( in_array( $post_type, $post_type_exceptions ) ) {
			return;
		}
		$trid           = $this->post_translation->get_element_trid( $post_id );
		$translated_ids = $this->post_translation->get_element_translations( $post_id, $trid, true );
		$lang_code      = $this->post_translation->get_element_lang_code( $post_id );
		$this->delete_translations( $translated_ids, $keep_db_entries );
		if ( ! $keep_db_entries ) {
			$this->post_translation->delete_post_translation_entry( $post_id );
			$this->set_new_original( $trid, $lang_code );
		}
		$this->post_translation->reload();
		require_once ICL_PLUGIN_PATH . '/inc/cache.php';
		icl_cache_clear( $post_type . 's_per_language', true );
		$this->maybe_fix_translated_parent( $post_type );
	}

	private function delete_translations( $translated_ids, $keep_db_entries ) {
		if ( $this->sync_delete && ! empty( $translated_ids ) ) {
			foreach ( $translated_ids as $trans_id ) {
				if ( ! $this->is_bulk_prevented( $trans_id ) ) {
					if ( $keep_db_entries ) {
						$this->post_translation->trash_translation( $trans_id );
					} else {
						wp_delete_post( $trans_id, true );
					}
				}
			}
		}
	}

	private function is_bulk_prevented( $post_id ) {

		return ( isset( $_GET[ 'delete_all' ] ) && $_GET[ 'delete_all' ] === 'Empty Trash' )
		       || in_array( $post_id, ( isset( $_GET[ 'ids' ] ) ? $_GET[ 'ids' ] : array() ) );
	}

	function untrashed_post_actions( $post_id ) {
		if ( $this->sync_delete ) {
			$translations = $this->post_translation->get_element_translations( $post_id, false, true );
			foreach ( $translations as $t_id ) {
				$this->post_translation->untrash_translation( $t_id );
			}
		}
		$post_type = get_post_type( $post_id );
		require_once ICL_PLUGIN_PATH . '/inc/cache.php';
		icl_cache_clear( $post_type . 's_per_language', true );
	}

	public function sync_with_translations( $post_id, $post_vars = false ) {
		global $wpdb;

		$wp_api            = $this->sitepress->get_wp_api();
		$term_count_update = new WPML_Update_Term_Count( $wp_api );
		
		$post           = get_post ( $post_id );
		$translated_ids = $this->post_translation->get_element_translations( $post_id, false, true );
		$post_format = $this->sync_post_format ? get_post_format( $post_id ) : null;
		$ping_status = $this->sync_ping_status ? ( pings_open( $post_id ) ? 'open' : 'closed' ) : null;
		$comment_status = $this->sync_comment_status ? ( comments_open( $post_id ) ? 'open' : 'closed' ) : null;
		$post_password = $this->sync_password ? $post->post_password : null;
		$sync_parent_private = $this->sync_private_flag && get_post_status( $post_id ) === 'private' ? 'private' : null;
		$menu_order = $this->sync_menu_order && ! empty( $post->menu_order ) ? $post->menu_order : null;
		$page_template = $this->sync_page_template && get_post_type( $post_id ) === 'page' ? get_post_meta( $post_id, '_wp_page_template', true ) : null;
		$post_date = $this->sync_post_date ? $wpdb->get_var( $wpdb->prepare( "SELECT post_date FROM {$wpdb->posts} WHERE ID=%d LIMIT 1", $post_id ) ) : null;


		if ( (bool) $post_vars === true ) {
			$this->sync_sticky_flag ( $this->post_translation->get_element_trid ( $post_id ), $post_vars );
		}

		foreach ( $translated_ids as $lang_code => $translated_pid ) {
			if ( 'private' === $sync_parent_private ) {
				$post_status = 'private';
			} else {
				$post_status = get_post_status( $translated_pid );
			}
			$this->sync_custom_fields ( $post_id, $translated_pid );
			if ( $post_format !== null ) {
				set_post_format ( $translated_pid, $post_format );
			}
			if ( $post_date !== null ) {
				$post_date_gmt = get_gmt_from_date ( $post_date );
				$data = array( 'post_date' => $post_date, 'post_date_gmt' => $post_date_gmt );
				$now = gmdate('Y-m-d H:i:59');
				$allow_post_statuses = array( 'private', 'pending', 'draft' );
				if ( mysql2date('U', $post_date_gmt, false) > mysql2date('U', $now, false) ) {
					if ( ! in_array( $post_status, $allow_post_statuses ) ) {
						$post_status = 'future';
					}
				}
				$data[ 'post_status' ] = $post_status;
				$wpdb->update ( $wpdb->posts, $data, array( 'ID' => $translated_pid ) );
				wp_schedule_single_event( strtotime( $post_date_gmt . '+1 second' ), 'publish_future_post', array( $translated_pid ) );
			}
			if ( $post_password !== null ) {
				$wpdb->update ( $wpdb->posts, array( 'post_password' => $post_password ), array( 'ID' => $translated_pid ) );
			}
			if ( $post_status !== null && ! in_array( get_post_status( $translated_pid ), array( 'auto-draft', 'draft', 'inherit', 'trash' ) ) ) {
				$wpdb->update ( $wpdb->posts, array( 'post_status' => $post_status ), array( 'ID' => $translated_pid ) );
				$term_count_update->update_for_post( $translated_pid );
			}
			if ( $post_status == null && $this->sync_private_flag && get_post_status( $translated_pid ) == 'private' ) {
				$wpdb->update ( $wpdb->posts, array( 'post_status' => get_post_status( $post_id ) ), array( 'ID' => $translated_pid ) );
				$term_count_update->update_for_post( $translated_pid );
			}
			if ( $ping_status !== null ) {
				$wpdb->update ( $wpdb->posts, array( 'ping_status' => $ping_status ), array( 'ID' => $translated_pid ) );
			}
			if ( $comment_status !== null ) {
				$wpdb->update ( $wpdb->posts, array( 'comment_status' => $comment_status ), array( 'ID' => $translated_pid ) );
			}
			if ( $page_template !== null ) {
				update_post_meta ( $translated_pid, '_wp_page_template', $page_template );
			}
			$this->sync_with_translations ( $translated_pid );
		}
		$this->maybe_fix_translated_parent( get_post_type( $post_id ) );

		if ( $menu_order !== null && (bool) $translated_ids !== false ) {
			$query = $wpdb->prepare(
				"UPDATE {$wpdb->posts}
				   SET menu_order=%s
				   WHERE ID IN (%s)",
				$menu_order,
				wpml_prepare_in( $translated_ids, '%d' )
			);
			$wpdb->query( $query );
		}
	}

	private function sync_sticky_flag($trid, $post_vars ){
		global $sitepress;

		if ( $this->sync_sticky_flag
		     && isset( $post_vars[ 'post_type' ])
		     && isset($post_vars[ 'post_status' ])
		     && $post_vars[ 'post_status' ] !== 'draft'
		     && $post_vars[ 'post_type' ] === 'post'
		) {
			// remove filter used to get language relevant stickies. get them all
			remove_filter( 'pre_option_sticky_posts', array( $sitepress, 'option_sticky_posts' ) );
			$sticky_posts = get_option( 'sticky_posts', array() );
			$translations = $this->post_translation->get_element_translations ( false, $trid, false );
			$sticky_posts = ( isset( $post_vars[ 'sticky' ] ) && $post_vars[ 'sticky' ] === 'sticky' )
				? array_unique( array_merge( $sticky_posts, $translations ) ) : array_diff( $sticky_posts, $translations );
			update_option( 'sticky_posts', $sticky_posts );
			add_filter( 'pre_option_sticky_posts', array( $sitepress, 'option_sticky_posts' ), 10, 2 ); // add filter back
		}
	}

	private function sync_custom_fields( $original_id, $post_id ) {
		if ( $original_id && $original_id != $post_id ) {
			$this->sitepress->copy_custom_fields ( $original_id, $post_id );
		} else {
			$translations = $this->post_translation->get_element_translations ( $post_id, false, true );
			foreach ( $translations as $t_id ) {
				$this->sitepress->copy_custom_fields ( $post_id, $t_id );
			}
		}
	}

	private function set_new_original( $trid, $removed_lang_code ) {
		if ( $trid && $removed_lang_code ) {
			$priorities = $this->sitepress->get_setting( 'languages_order' );
			$this->post_translation->reload();
			$translations         = $this->post_translation->get_element_translations( false, $trid );
			$new_source_lang_code = false;
			foreach ( $priorities as $lang_code ) {
				if ( isset( $translations[ $lang_code ] ) ) {
					$new_source_lang_code = $lang_code;
					break;
				}
			}
			if ( $new_source_lang_code ) {
				global $wpdb;

				$rows_updated = $wpdb->update( $wpdb->prefix . 'icl_translations',
				               array( 'source_language_code' => $new_source_lang_code ),
				               array( 'trid' => $trid, 'source_language_code' => $removed_lang_code )
				);

				if( 0 < $rows_updated ) {
					do_action( 'wpml_translation_update', array( 'trid' => $trid ) );
				}

				$wpdb->query( "	UPDATE {$wpdb->prefix}icl_translations
								SET source_language_code = NULL
								WHERE language_code = source_language_code" );
			}
		}
	}
}