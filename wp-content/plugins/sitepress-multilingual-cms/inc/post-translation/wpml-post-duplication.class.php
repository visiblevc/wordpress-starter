<?php

require_once dirname( __FILE__ ) . '/wpml-wordpress-actions.class.php';

/**
 * Class WPML_Post_Duplication
 *
 * @package    wpml-core
 * @subpackage post-translation
 */
class WPML_Post_Duplication extends WPML_WPDB_And_SP_User {

	function get_duplicates( $master_post_id ) {
		global $wpml_post_translations;
		$duplicates = array();

		$post_ids_query = " SELECT post_id
                            FROM {$this->wpdb->postmeta}
                            WHERE meta_key='_icl_lang_duplicate_of'
                                AND meta_value = %d
                                AND post_id <> %d";
		$post_ids_prepare = $this->wpdb->prepare( $post_ids_query, array( $master_post_id, $master_post_id ) );
		$post_ids         = $this->wpdb->get_col( $post_ids_prepare );
		foreach ( $post_ids as $post_id ) {
			$language_code                = $wpml_post_translations->get_element_lang_code( $post_id );
			$duplicates[ $language_code ] = $post_id;
		}

		return $duplicates;
	}

	function make_duplicate( $master_post_id, $lang ) {
		global $wpml_post_translations;

		/**
		 * @deprecated Use 'wpml_before_make_duplicate' instead
		 * @since      3.4
		 */
		do_action( 'icl_before_make_duplicate', $master_post_id, $lang );
		do_action( 'wpml_before_make_duplicate', $master_post_id, $lang );
		$master_post = get_post( $master_post_id );
		$is_duplicated = false;
		$translations  = $wpml_post_translations->get_element_translations( $master_post_id, false, false );
		if ( isset( $translations[ $lang ] ) ) {
			$post_array[ 'ID' ] = $translations[ $lang ];
			if ( WPML_WordPress_Actions::is_bulk_trash( $post_array[ 'ID' ] ) || WPML_WordPress_Actions::is_bulk_untrash( $post_array[ 'ID' ] ) ) {
				return true;
			}
			$is_duplicated = get_post_meta( $translations[ $lang ], '_icl_lang_duplicate_of', true );
		}
		$post_array['post_author']   = $master_post->post_author;
		$post_array['post_date']     = $master_post->post_date;
		$post_array['post_date_gmt'] = $master_post->post_date_gmt;
		$duplicated_post_content     = $this->duplicate_post_content( $lang, $master_post );
		$post_array['post_content']  = addslashes_gpc( $duplicated_post_content );
		$duplicated_post_title       = $this->duplicate_post_title( $lang, $master_post );
		$post_array['post_title']    = addslashes_gpc( $duplicated_post_title );
		$duplicated_post_excerpt     = $this->duplicate_post_excerpt( $lang, $master_post );
		$post_array['post_excerpt']  = addslashes_gpc( $duplicated_post_excerpt );
		if ( $this->sitepress->get_setting('sync_post_status' ) ) {
			$sync_post_status = true;
		} else {
			$sync_post_status = ( ! isset( $post_array[ 'ID' ] )
			                      || ( $this->sitepress->get_setting( 'sync_delete' ) && $master_post->post_status === 'trash' ) || $is_duplicated );
		}
		if ( $sync_post_status || ( isset( $post_array[ 'ID' ] ) && get_post_status( $post_array[ 'ID' ] ) === 'auto-draft' ) ) {
			$post_array[ 'post_status' ] = $master_post->post_status;
		}
		$post_array[ 'comment_status' ] = $master_post->comment_status;
		$post_array[ 'ping_status' ]    = $master_post->ping_status;
		$post_array[ 'post_name' ]      = $master_post->post_name;
		if ( $master_post->post_parent ) {
			$parent = $this->sitepress->get_object_id( $master_post->post_parent, $master_post->post_type, false, $lang );
			$post_array[ 'post_parent' ] = $parent;
		}
		$post_array['menu_order']     = $master_post->menu_order;
		$post_array['post_type']      = $master_post->post_type;
		$post_array['post_mime_type'] = $master_post->post_mime_type;
		$trid                         = $this->sitepress->get_element_trid( $master_post->ID,
		                                                                    'post_' . $master_post->post_type );
		$id                           = $this->save_duplicate( $post_array, $lang );

		require_once ICL_PLUGIN_PATH . '/inc/cache.php';
		icl_cache_clear();

		global $ICL_Pro_Translation;
		/** @var WPML_Pro_Translation $ICL_Pro_Translation */
		if ( $ICL_Pro_Translation ) {
			$ICL_Pro_Translation->fix_links_to_translated_content( $id, $lang );
		}
		if ( ! is_wp_error( $id ) ) {
			$ret = $this->run_wpml_actions( $master_post, $trid, $lang, $id, $post_array );
		} else {
			throw new Exception( $id->get_error_message() );
		}

		return $ret;
	}

	private function run_wpml_actions( $master_post, $trid, $lang, $id, $post_array ) {
		$master_post_id = $master_post->ID;
		$this->sitepress->set_element_language_details( $id, 'post_' . $master_post->post_type, $trid, $lang );
		$this->sync_duplicate_password( $master_post_id, $id );
		$this->sync_page_template( $master_post_id, $id );
		$this->duplicate_fix_children( $master_post_id, $lang );

		// make sure post name is copied
		$this->wpdb->update( $this->wpdb->posts, array( 'post_name' => $master_post->post_name ), array( 'ID' => $id ) );

		if ( $this->sitepress->get_option( 'sync_post_taxonomies' ) ) {
			$this->duplicate_taxonomies( $master_post_id, $lang );
		}
		$this->duplicate_custom_fields( $master_post_id, $lang );
		update_post_meta( $id, '_icl_lang_duplicate_of', $master_post->ID );

		// Duplicate post format after the taxonomies because post format is stored
		// as a taxonomy by WP.
		if ( $this->sitepress->get_setting( 'sync_post_format' ) ) {
			$_wp_post_format = get_post_format( $master_post_id );
			set_post_format( $id, $_wp_post_format );
		}
		if ( $this->sitepress->get_setting( 'sync_comments_on_duplicates' ) ) {
			$this->duplicate_comments( $master_post_id, $id );
		}
		$status_helper = wpml_get_post_status_helper();
		$status_helper->set_status( $id, ICL_TM_DUPLICATE );
		$status_helper->set_update_status( $id, false );
		do_action( 'icl_make_duplicate', $master_post_id, $lang, $post_array, $id );
		clean_post_cache( $id );

		return $id;
	}

	private function sync_page_template( $master_post_id, $duplicate_post_id ) {
		$_wp_page_template = get_post_meta( $master_post_id, '_wp_page_template', true );
		if ( ! empty( $_wp_page_template ) ) {
			update_post_meta( $duplicate_post_id, '_wp_page_template', $_wp_page_template );
		}
	}

	private function duplicate_comments( $master_post_id, $translated_id ) {
		global $sitepress;

		remove_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10 );
		$comments_on_master      = get_comments( array( 'post_id' => $master_post_id ) );
		$comments_on_translation = get_comments( array( 'post_id' => $translated_id, 'status' => 'any' ) );
		add_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
		foreach ( $comments_on_translation as $comment ) {
			wp_delete_comment( $comment->comment_ID, true );
			clean_comment_cache( $comment->comment_ID );
		}
		$iclTranslationManagement = wpml_load_core_tm();
		foreach ( $comments_on_master as $comment ) {
			$iclTranslationManagement->duplication_insert_comment( $comment->comment_ID );
			clean_comment_cache( $comment->comment_ID );
		}

		wp_update_comment_count_now( $master_post_id );
		wp_update_comment_count_now( $translated_id );
	}

	private function save_duplicate( $post_array, $lang ) {
		if ( isset( $post_array[ 'ID' ] ) ) {
			$id = wp_update_post( $post_array, true );
		} else {
			$create_post_helper = wpml_get_create_post_helper();
			$id                 = $create_post_helper->insert_post( $post_array, $lang, true );
		}

		return $id;
	}

	private function duplicate_fix_children( $master_post_id, $lang ) {
		$post_type       = $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT post_type FROM {$this->wpdb->posts} WHERE ID=%d", $master_post_id )
		);
		$master_children = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT ID FROM {$this->wpdb->posts} WHERE post_parent=%d AND post_type != 'revision'",
				$master_post_id
			)
		);
		$dup_parent      = icl_object_id( $master_post_id, $post_type, false, $lang );
		if ( $master_children ) {
			foreach ( $master_children as $master_child ) {
				$dup_child = icl_object_id( $master_child, $post_type, false, $lang );
				if ( $dup_child ) {
					$this->wpdb->update( $this->wpdb->posts, array( 'post_parent' => $dup_parent ), array( 'ID' => $dup_child ) );
				}
				$this->duplicate_fix_children( $master_child, $lang );
			}
		}
	}

	private function duplicate_taxonomies( $master_post_id, $lang ) {
		$post_type  = get_post_field( 'post_type', $master_post_id );
		$taxonomies = get_object_taxonomies( $post_type );
		$trid       = $this->sitepress->get_element_trid( $master_post_id, 'post_' . $post_type );
		if ( $trid ) {
			$translations = $this->sitepress->get_element_translations( $trid, 'post_' . $post_type, false, false, true );
			if ( isset( $translations[ $lang ] ) ) {
				$duplicate_post_id = $translations[ $lang ]->element_id;
				/* If we have an existing post, we first of all remove all terms currently attached to it.
				 * The main reason behind is the removal of the potentially present default category on the post.
				 */
				wp_delete_object_term_relationships( $duplicate_post_id, $taxonomies );
			} else {
				return false; // translation not found!
			}
		}
		$term_helper = wpml_get_term_translation_util();
		$term_helper->duplicate_terms( $master_post_id, $lang );

		return true;
	}

	private function sync_duplicate_password( $master_post_id, $duplicate_post_id ) {
		if ( post_password_required( $master_post_id ) ) {
			$sql = $this->wpdb->prepare( "UPDATE {$this->wpdb->posts} AS dupl,
									(SELECT org.post_password FROM {$this->wpdb->posts} AS org WHERE ID = %d ) AS pwd
									SET dupl.post_password = pwd.post_password
									WHERE dupl.ID = %d",
								   array( $master_post_id, $duplicate_post_id ) );
			$this->wpdb->query( $sql );
		}
	}

	private function duplicate_custom_fields( $master_post_id, $lang ) {
		$duplicate_post_id = false;
		$post_type         = get_post_field( 'post_type', $master_post_id );

		$trid = $this->sitepress->get_element_trid( $master_post_id, 'post_' . $post_type );
		if ( $trid ) {
			$translations = $this->sitepress->get_element_translations( $trid, 'post_' . $post_type );
			if ( isset( $translations[ $lang ] ) ) {
				$duplicate_post_id = $translations[ $lang ]->element_id;
			} else {
				return false; // translation not found!
			}
		}
		$default_exceptions = WPML_Config::get_custom_fields_translation_settings();
		$exceptions         = apply_filters( 'wpml_duplicate_custom_fields_exceptions', array() );
		$exceptions         = array_merge( $exceptions, $default_exceptions );
		$exceptions         = array_unique( $exceptions );

		$exceptions_in = ! empty( $exceptions )
			? 'AND meta_key NOT IN ( ' . wpml_prepare_in( $exceptions ) . ') ' : '';
		$from_where_string = "FROM {$this->wpdb->postmeta} WHERE post_id = %d " . $exceptions_in;
		$post_meta_master = $this->wpdb->get_results( "SELECT meta_key, meta_value " . $this->wpdb->prepare( $from_where_string,
																								 $master_post_id ) );
		$this->wpdb->query( "DELETE " . $this->wpdb->prepare( $from_where_string, $duplicate_post_id ) );

		foreach ( $post_meta_master as $post_meta ) {
			$is_serialized = is_serialized( $post_meta->meta_value );
			$meta_data     = array(
				'context'        => 'custom_field',
				'attribute'      => 'value',
				'key'            => $post_meta->meta_key,
				'is_serialized'  => $is_serialized,
				'post_id'        => $duplicate_post_id,
				'master_post_id' => $master_post_id,
			);

			/**
			 * @deprecated use 'wpml_duplicate_generic_string' instead, with the same arguments
			 */
			$icl_duplicate_generic_string = apply_filters( 'icl_duplicate_generic_string',
														   $post_meta->meta_value,
														   $lang,
														   $meta_data );
			$post_meta->meta_value        = $icl_duplicate_generic_string;
			$wpml_duplicate_generic_string = apply_filters( 'wpml_duplicate_generic_string',
															$post_meta->meta_value,
															$lang,
															$meta_data );
			$post_meta->meta_value         = $wpml_duplicate_generic_string;
			if ( ! is_serialized( $post_meta->meta_value ) ) {
				$post_meta->meta_value = maybe_serialize( $post_meta->meta_value );
			}
			$this->wpdb->insert( $this->wpdb->postmeta,
						   array(
							   'post_id'    => $duplicate_post_id,
							   'meta_key'   => $post_meta->meta_key,
							   'meta_value' => $post_meta->meta_value
						   ),
						   array( '%d', '%s', '%s' ) );
		}

		return true;
	}

	/**
	 * @param $lang
	 * @param $master_post
	 *
	 * @return mixed|void
	 */
	private function duplicate_post_content( $lang, $master_post ) {
		$duplicated_post_content_meta = array(
			'context'   => 'post',
			'attribute' => 'content',
			'key'       => $master_post->ID
		);
		$duplicated_post_content      = $master_post->post_content;
		$duplicated_post_content      = apply_filters( 'icl_duplicate_generic_string', $duplicated_post_content, $lang, $duplicated_post_content_meta );
		$duplicated_post_content      = apply_filters( 'wpml_duplicate_generic_string', $duplicated_post_content, $lang, $duplicated_post_content_meta );

		return $duplicated_post_content;
	}

	/**
	 * @param $lang
	 * @param $master_post
	 *
	 * @return mixed|void
	 */
	private function duplicate_post_title( $lang, $master_post ) {
		$duplicated_post_title_meta = array(
			'context'   => 'post',
			'attribute' => 'title',
			'key'       => $master_post->ID
		);
		$duplicated_post_title      = $master_post->post_title;
		$duplicated_post_title      = apply_filters( 'icl_duplicate_generic_string', $duplicated_post_title, $lang, $duplicated_post_title_meta );
		$duplicated_post_title      = apply_filters( 'wpml_duplicate_generic_string', $duplicated_post_title, $lang, $duplicated_post_title_meta );

		return $duplicated_post_title;
	}

	/**
	 * @param string $lang
	 * @param WP_Post $master_post
	 *
	 * @return mixed|void
	 */
	private function duplicate_post_excerpt( $lang, $master_post ) {
		$duplicated_post_excerpt_meta = array(
			'context'   => 'post',
			'attribute' => 'excerpt',
			'key'       => $master_post->ID
		);
		$duplicated_post_excerpt      = $master_post->post_excerpt;
		$duplicated_post_excerpt      = apply_filters( 'icl_duplicate_generic_string',
		                                               $duplicated_post_excerpt,
		                                               $lang,
		                                               $duplicated_post_excerpt_meta );
		$duplicated_post_excerpt      = apply_filters( 'wpml_duplicate_generic_string',
		                                               $duplicated_post_excerpt,
		                                               $lang,
		                                               $duplicated_post_excerpt_meta );

		return $duplicated_post_excerpt;
	}
}
