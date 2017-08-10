<?php

/**
 * WPML_Term_Actions Class
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 *
 */
class WPML_Term_Actions extends WPML_Full_Translation_API {

	/** @var bool $delete_recursion_flag */
	private $delete_recursion_flag = false;

	/**
	 * @param int                    $tt_id    Taxonomy Term ID of the saved Term
	 * @param string                 $taxonomy Taxonomy of the saved Term
	 */
	function save_term_actions( $tt_id, $taxonomy ) {
		if ( ! $this->sitepress->is_translated_taxonomy( $taxonomy ) ) {
			return;
		};
		$post_action  = filter_input( INPUT_POST, 'action' );
		$term_lang    = $this->get_term_lang( $tt_id, $post_action, $taxonomy );
		$trid         = $this->get_saved_term_trid( $tt_id, $post_action );
		$src_language = $this->term_translations->get_source_lang_code( $tt_id );
		$this->sitepress->set_element_language_details( $tt_id,
			'tax_' . $taxonomy, $trid, $term_lang, $src_language );
		$sync_meta_action = new WPML_Sync_Term_Meta_Action( $this->sitepress,
			$tt_id );
		$sync_meta_action->run();
	}

	/**
	 * @param int    $tt_id    term taxonomy id of the deleted term
	 * @param string $taxonomy taxonomy of the deleted term
	 */
	function delete_term_actions( $tt_id, $taxonomy ) {
		$icl_el_type = 'tax_' . $taxonomy;
		$trid        = $this->sitepress->get_element_trid( $tt_id, $icl_el_type );
		$lang_details = $this->sitepress->get_element_language_details( $tt_id, $icl_el_type );
		if ( $this->sitepress->get_setting( 'sync_delete_tax' )
		     && $this->delete_recursion_flag === false
		     && empty( $lang_details->source_language_code )
		) {
			// get translations
			$translations                = $this->sitepress->get_element_translations( $trid, $icl_el_type );
			$this->delete_recursion_flag = true;
			// delete translations
			$has_filter = remove_filter( 'get_term', array( $this->sitepress, 'get_term_adjust_id' ), 1 );
			foreach ( $translations as $translation ) {
				if ( (int) $translation->element_id !== (int) $tt_id ) {
					wp_delete_term( $translation->term_id, $taxonomy );
				}
			}
			if ( $has_filter ) {
				add_filter( 'get_term', array( $this->sitepress, 'get_term_adjust_id' ), 1, 1 );
			}
			$this->delete_recursion_flag = false;
		} else {
			if ( empty( $lang_details->source_language_code ) ) {
				$this->set_new_original_term( $trid, $lang_details->language_code );
			}
		}

		$update_args = array(
			'element_id' => $tt_id,
			'element_type' => $icl_el_type,
			'context' => 'tax'
		);

		do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

		$this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', array( 'element_type' => $icl_el_type, 'element_id' => $tt_id ) );
		do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );
	}

	/**
	 * @param $trid
	 * @param $deleted_language_code
	 */
	public function set_new_original_term( $trid, $deleted_language_code ) {
		if ( $trid && $deleted_language_code ) {
			$order_languages = $this->sitepress->get_setting( 'languages_order' );
			$this->term_translations->reload();
			$translations         = $this->term_translations->get_element_translations( false, $trid );
			$new_source_lang_code = false;
			foreach ( $order_languages as $lang_code ) {
				if ( isset( $translations[ $lang_code ] ) ) {
					$new_source_lang_code = $lang_code;
					break;
				}
			}
			if ( $new_source_lang_code ) {
				$rows_updated = $this->wpdb->update( $this->wpdb->prefix . 'icl_translations',
					array( 'source_language_code' => $new_source_lang_code ),
					array( 'trid' => $trid, 'source_language_code' => $deleted_language_code )
				);

				if ( 0 < $rows_updated ) {
					do_action( 'wpml_translation_update', array( 'trid' => $trid ) );
				}

				$this->wpdb->query( "UPDATE {$this->wpdb->prefix}icl_translations 
									 SET source_language_code = NULL 
									 WHERE language_code = source_language_code" );
			}
		}
	}

	/**
	 * This action is hooked to the 'deleted_term_relationships' hook.
	 * It removes terms from translated posts as soon as they are removed from the original post.
	 * It only fires, if the setting 'sync_post_taxonomies' is activated.
	 *
	 * @param int   $post_id      ID of the post the deleted terms were attached to
	 * @param array $delete_terms Array of term taxonomy id's for those terms that were deleted from the post.
	 */
	public function deleted_term_relationships( $post_id, $delete_terms ) {
		$post = get_post( $post_id );
		$trid = $this->sitepress->get_element_trid( $post_id, 'post_' . $post->post_type );
		if ( $trid ) {
			$translations = $this->sitepress->get_element_translations( $trid, 'post_' . $post->post_type );
			foreach ( $translations as $translation ) {
				if ( $translation->original == 1 && $translation->element_id == $post_id ) {
					$taxonomies = get_object_taxonomies( $post->post_type );
					foreach ( $taxonomies as $taxonomy ) {
						foreach ( $delete_terms as $delete_term ) {
							$trid = $this->sitepress->get_element_trid( $delete_term, 'tax_' . $taxonomy );
							if ( $trid ) {
								$tags = $this->sitepress->get_element_translations( $trid, 'tax_' . $taxonomy );
								foreach ( $tags as $tag ) {
									if ( ! $tag->original && isset( $translations[ $tag->language_code ] ) ) {
										$translated_post = $translations[ $tag->language_code ];
										$this->wpdb->delete( $this->wpdb->term_relationships,
										                     array(
											                     'object_id'        => $translated_post->element_id,
											                     'term_taxonomy_id' => $tag->element_id
										                     ) );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Copies taxonomy terms from original posts to their translation, if the translations of these terms exist
	 * and the option 'sync_post_taxonomies' is set.
	 *
	 * @param $object_id int ID of the object, that terms have just been added to.
	 */
	public function added_term_relationships( $object_id ) {
		$i                 = $this->wpdb->prefix . 'icl_translations';
		$current_ttids_sql =
			$this->wpdb->prepare( "SELECT
									ctt.taxonomy,
									ctt.term_id,
									p.ID
								FROM {$this->wpdb->posts} p
								JOIN {$i} i
								  ON p.ID = i.element_id
								     AND i.element_type = CONCAT('post_', p.post_type)
							    JOIN {$i} ip
									ON ip.trid = i.trid
										AND i.source_language_code = ip.language_code
								JOIN {$i} it
								JOIN {$this->wpdb->term_taxonomy} tt
								    ON tt.term_taxonomy_id = it.element_id
								       AND CONCAT('tax_', tt.taxonomy) = it.element_type
								JOIN {$this->wpdb->term_relationships} objrel
								  ON objrel.object_id = ip.element_id
								    AND objrel.term_taxonomy_id = tt.term_taxonomy_id
								JOIN {$i} itt
								  ON itt.trid = it.trid
								    AND itt.language_code = i.language_code
								JOIN {$this->wpdb->term_taxonomy} ctt
								  ON ctt.term_taxonomy_id = itt.element_id
								LEFT JOIN {$this->wpdb->term_relationships} trans_rel
									ON trans_rel.object_id = p.ID
										AND trans_rel.term_taxonomy_id = ctt.term_taxonomy_id
								WHERE  ip.element_id = %d
									AND trans_rel.object_id IS NULL",
			                      $object_id );

		$this->apply_added_term_changes( $this->wpdb->get_results( $current_ttids_sql ) );
	}

	/**
	 * @param array $corrections
	 *
	 * @uses \WPML_WP_API::wp_set_object_terms to add terms to posts, always appending terms
	 */
	private function apply_added_term_changes( $corrections ) {
		$changes = array();

		foreach ( $corrections as $correction ) {
			if ( ! $this->sitepress->is_translated_taxonomy( $correction->taxonomy ) ) {
				continue;
			}
			if ( ! isset( $changes[ $correction->taxonomy ] ) ) {
				$changes[ $correction->ID ][ $correction->taxonomy ] = array();
			}
			$changes[ $correction->ID ][ $correction->taxonomy ][] = (int) $correction->term_id;
		}
		foreach ( $changes as $post_id => $tax_changes ) {
			foreach ( $tax_changes as $taxonomy => $term_ids ) {
				remove_action( 'set_object_terms',
				               array( 'WPML_Terms_Translations', 'set_object_terms_action' ),
				               10 );
				$this->sitepress->get_wp_api()->wp_set_object_terms( $post_id, $term_ids, $taxonomy, true );
				add_action( 'set_object_terms',
				            array( 'WPML_Terms_Translations', 'set_object_terms_action' ),
				            10,
				            6 );
			}
		}
	}

	/**
	 * Gets the language under which a term is to be saved from the HTTP request and falls back on existing data in
	 * case the HTTP request does not contain the necessary data.
	 * If no language can be determined for the term to be saved under the default language is used as a fallback.
	 *
	 * @param int    $tt_id Taxonomy Term ID of the saved term
	 * @param string $post_action
	 * @param string $taxonomy
	 *
	 * @return null|string
	 */
	private function get_term_lang( $tt_id, $post_action, $taxonomy ) {
		$term_lang = filter_input( INPUT_POST,
		                           'icl_tax_' . $taxonomy . '_language',
		                           FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$term_lang = $term_lang ? $term_lang : $this->get_term_lang_ajax( $taxonomy, $post_action );
		$term_lang = $term_lang ? $term_lang : $this->get_lang_from_post( $post_action, $tt_id );

		$term_lang = $term_lang ? $term_lang : $this->sitepress->get_current_language();
		$term_lang = apply_filters( 'wpml_create_term_lang', $term_lang );
		$term_lang = $this->sitepress->is_active_language( $term_lang ) ? $term_lang
			: $this->sitepress->get_default_language();

		return $term_lang;
	}

	/**
	 * If no language could be set from the WPML $_POST variables as well as from the HTTP Referrer, then this function
	 * uses fallbacks to determine the language from the post the the term might be associated to.
	 * A post language determined from $_POST['icl_post_language'] will be used as term language.
	 * Also a check for whether the publishing of the term happens via quickpress is performed in which case the term
	 * is always associated with the default language.
	 * Next a check for the 'inline-save-tax' and the 'editedtag' action is performed. In case the check returns true
	 * the language of the term is not changed from what is saved for it in the database.
	 * If no term language can be determined from the above the $_POST['post_ID'] is checked as a last resort and in
	 * case it contains a valid post_ID the posts language is associated with the term.
	 *
	 * @param string $post_action
	 * @param int    $tt_id
	 *
	 * @return string|null Language code of the term
	 */
	private function get_lang_from_post( $post_action, $tt_id ) {
		$icl_post_lang = filter_input( INPUT_POST, 'icl_post_language' );
		$term_lang     = $post_action === 'editpost' && $icl_post_lang ? $icl_post_lang : null;
		$term_lang     = $post_action === 'post-quickpress-publish' ? $this->sitepress->get_default_language()
			: $term_lang;
		$term_lang     = ! $term_lang && $post_action === 'inline-save-tax' || $post_action === 'editedtag'
			? $this->term_translations->get_element_lang_code( $tt_id ) : $term_lang;
		$term_lang     = ! $term_lang && $post_action === 'inline-save'
			? $this->post_translations->get_element_lang_code(
				filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT )
			) : $term_lang;

		return $term_lang;
	}

	/**
	 * This function tries to determine the terms language from the HTTP Referer. This is used in case of ajax actions
	 * that save the term.
	 *
	 * @param string $taxonomy
	 * @param string $post_action
	 *
	 * @return null|string
	 */
	public function get_term_lang_ajax( $taxonomy, $post_action ) {
		if ( isset( $_POST['_ajax_nonce'] ) && filter_var( $_POST['_ajax_nonce'] ) !== false
		     && $post_action === 'add-' . $taxonomy
		) {
			$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
			parse_str( (string) wpml_parse_url( $referrer, PHP_URL_QUERY ), $qvars );
			$term_lang = ! empty( $qvars['post'] ) && $this->sitepress->is_translated_post_type(
				get_post_type( $qvars['post'] )
			)
				? $this->post_translations->get_element_lang_code( $qvars['post'] )
				: ( isset( $qvars['lang'] ) ? $qvars['lang'] : null );
		}

		return isset( $term_lang ) ? $term_lang : null;
	}

	private function get_saved_term_trid( $tt_id, $post_action ) {
		if ( $post_action === 'editpost' ) {
			$trid = $this->term_translations->get_element_trid( $tt_id );
		} elseif ( $post_action === 'editedtag' ) {
			$translation_of = filter_input( INPUT_POST, 'icl_translation_of', FILTER_VALIDATE_INT );
			$translation_of = $translation_of ? $translation_of : filter_input( INPUT_POST, 'icl_translation_of' );

			$trid = $translation_of === 'none' ? false
				: ( $translation_of
					? $this->term_translations->get_element_trid( $translation_of )
					: $trid = filter_input( INPUT_POST, 'icl_trid', FILTER_SANITIZE_NUMBER_INT )
				);
		} else {
			$trid = filter_input( INPUT_POST, 'icl_trid', FILTER_SANITIZE_NUMBER_INT );
			$trid = $trid
				? $trid
				: $this->term_translations->get_element_trid(
					filter_input( INPUT_POST, 'icl_translation_of', FILTER_VALIDATE_INT )
				);
			$trid = $trid ? $trid : $this->term_translations->get_element_trid( $tt_id );
		}

		return $trid;
	}
}