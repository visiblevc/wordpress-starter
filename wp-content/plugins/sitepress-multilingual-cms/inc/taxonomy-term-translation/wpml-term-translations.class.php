<?php
require_once dirname( __FILE__ ) . '/wpml-update-term-action.class.php';

/**
 * @since      3.1.8
 *
 * Class WPML_Terms_Translations
 *
 * This class holds some basic functionality for translating taxonomy terms.
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 */
class WPML_Terms_Translations {

	/**
	 * @deprecated since Version 3.1.8.3
	 * @param object[]|string[] $terms
	 * @param   string[]|string $taxonomies This is only used by the WP core AJAX call that fetches the preview
	 *                                      auto-complete for flat taxonomy term adding
	 *
	 * @return mixed
	 */
	public static function get_terms_filter( $terms, $taxonomies ) {
		global $wpdb, $sitepress;

        $lang = $sitepress->get_current_language();

		foreach ( $taxonomies as $taxonomy ) {

			if ( $sitepress->is_translated_taxonomy( $taxonomy ) ) {

				$element_type = 'tax_' . $taxonomy;

				$query = $wpdb->prepare( "SELECT wptt.term_id
                                          FROM {$wpdb->prefix}icl_translations AS iclt
                                          JOIN {$wpdb->prefix}term_taxonomy AS wptt
                                            ON iclt.element_id = wptt.term_taxonomy_id
                                          WHERE language_code=%s AND element_type = %s", $lang, $element_type );

                $element_ids_array = $wpdb->get_col( $query );

				foreach ( $terms as $key => $term ) {
					if ( ! is_object( $term ) ) {
						$term = get_term_by( 'name', $term, $taxonomy );
					}
					if ( $term && isset( $term->taxonomy )
                         && $term->taxonomy === $taxonomy
                         && ! in_array( $term->term_id, $element_ids_array ) ) {
						unset( $terms[ $key ] );
					}
				}
			}
		}

		return $terms;
	}

	/**
	 * @param $slug
	 * @param $taxonomy
	 * @param $lang
	 * Creates a unique slug for a given term, using a scheme
	 * encoding the language code in the slug.
	 *
	 * @return string
	 */
	public static function term_unique_slug( $slug, $taxonomy, $lang ) {
        global $sitepress;

        $default_language = $sitepress->get_default_language();

		if ( $lang !== $default_language && self::term_slug_exists( $slug, $taxonomy ) ) {
			$slug .= '-' . $lang;
		}

		$i      = 2;
		$suffix = '-' . $i;

		if ( self::term_slug_exists( $slug, $taxonomy ) ) {
			while ( self::term_slug_exists( $slug . $suffix, $taxonomy ) ) {
				$i ++;
				$suffix = '-' . $i;
			}
			$slug .= $suffix;
		}

		return $slug;
	}

	/**
	 * @param      $slug
	 * @param bool $taxonomy
	 * If $taxonomy is given, then slug existence is checked only for the specific taxonomy.
	 *
	 * @return bool
	 */
	private static function term_slug_exists( $slug, $taxonomy = false ) {
		global $wpdb;

		$existing_term_prepared_query = $wpdb->prepare( "SELECT t.term_id
                                                         FROM {$wpdb->terms} t
                                                         JOIN {$wpdb->term_taxonomy} tt
                                                          ON t.term_id  = tt.term_id
                                                         WHERE t.slug = %s
                                                          AND tt.taxonomy = %s
                                                         LIMIT 1",
                                                        $slug,
                                                        $taxonomy );
		$term_id                      = $wpdb->get_var( $existing_term_prepared_query );

		return (bool) $term_id;
	}

	/**
	 *
	 * Once we create a new term, it could be that this term is actually the translation of another term in more than one taxonomy.
	 * In this case entries for all taxonomies have to be created in icl_translations.
	 * This action creates these entries.
	 *
	 * @param $tt_id
	 * @param $language_code
	 * @param $taxonomy
     *
     * @return bool
	 */
	public static function sync_ttid_action( $taxonomy, $tt_id, $language_code ) {
		global $wpdb, $sitepress, $wp_version;

        if(version_compare($wp_version, '4.1.1', '>=')){
            return false;
        }

		// First we get all taxonomies, to which the new term's original element belongs.
		$original_ttid   = $sitepress->get_original_element_id( $tt_id, 'tax_' . $taxonomy );
		$source_language = $sitepress->get_language_for_element( $original_ttid, 'tax_' . $taxonomy );

		$query_for_original_term_id = $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d", $original_ttid );
		$original_term_id           = $wpdb->get_var( $query_for_original_term_id );

		if ( $original_term_id ) {
			$taxonomy_query_prepared = $wpdb->prepare( "SELECT taxonomy, term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d", $original_term_id );
			$original_tax_terms      = $wpdb->get_results( $taxonomy_query_prepared );

			$query_for_translated_term_id = $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d", $tt_id );
			$translated_term_id           = $wpdb->get_var( $query_for_translated_term_id );

			$taxonomy_query_prepared   = $wpdb->prepare( "SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_id = %d", $translated_term_id );
			$taxonomies_on_translation = $wpdb->get_col( $taxonomy_query_prepared );

			foreach ( $original_tax_terms as $original_tax_term ) {
				if ( isset( $original_tax_term->taxonomy ) && isset( $original_tax_term->term_taxonomy_id ) ) {
					$original_taxonomy = $original_tax_term->taxonomy;
					$original_tax_ttid = $original_tax_term->term_taxonomy_id;
					if ( ! in_array( $original_taxonomy, $taxonomies_on_translation ) ) {
						$ttid_row = array( 'term_id' => $translated_term_id, 'taxonomy' => $original_taxonomy );
						if ( is_taxonomy_hierarchical( $taxonomy ) ) {
							$original_term_parent_query_prepared = $wpdb->prepare( "SELECT parent FROM {$wpdb->term_taxonomy} WHERE $original_tax_ttid = %d", $original_tax_ttid );
							$parent                              = $wpdb->get_var( $original_term_parent_query_prepared );
							if ( $parent > 0 ) {
								$ttid_row [ 'parent' ] = $parent;
							}
						}

						$update = false;
						$trid   = $sitepress->get_element_trid( $original_tax_ttid, 'tax_' . $original_taxonomy );

						if ( $trid ) {

							$data = array(
								'trid'                 => $trid,
								'language_code'        => $language_code,
								'source_language_code' => $source_language,
								'element_type'         => 'tax_' . $original_taxonomy
							);

							$existing_translations = $sitepress->get_element_translations( $trid, 'tax_' . $original_taxonomy );
							if ( isset( $existing_translations[ $language_code ] ) ) {
								$update = true;
							}

							if ( ! $update ) {
								$wpdb->insert( $wpdb->term_taxonomy, $ttid_row );
								$new_ttid             = $wpdb->insert_id;
								$data[ 'element_id' ] = $new_ttid;
								$wpdb->insert( $wpdb->prefix . 'icl_translations', $data );

								do_action(
									'wpml_translation_update',
									array(
										'type' => 'insert',
										'trid' => $data['trid'],
										'element_id' => $data['element_id'],
										'element_type' => $data['element_type'],
										'translation_id' => $wpdb->insert_id,
										'context' => 'tax'
									)
								);
							}
						}
					}
				}
			}
		}

        return true;
	}

	/**
	 * This function provides an action hook only used by WCML.
	 * It will be removed in the future and should not be implemented in new spots.
	 * @deprecated deprecated since version 3.1.8.3
	 *
	 * @param $taxonomy        string The identifier of the taxonomy the translation was just saved to.
	 * @param $translated_term array The associative array holding term taxonomy id and term id,
	 *                         as returned by wp_insert_term or wp_update_term.
	 */
	public static function icl_save_term_translation_action( $taxonomy, $translated_term ) {
		global $wpdb, $sitepress;

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			$term_taxonomy_id = $translated_term[ 'term_taxonomy_id' ];

			$original_ttid = $sitepress->get_original_element_id( $term_taxonomy_id, 'tax_' . $taxonomy );

			$original_tax_sql      = "SELECT * FROM {$wpdb->term_taxonomy} WHERE taxonomy=%s AND term_taxonomy_id = %d";
			$original_tax_prepared = $wpdb->prepare( $original_tax_sql, array( $taxonomy, $original_ttid ) );
			$original_tax          = $wpdb->get_row( $original_tax_prepared );

			do_action( 'icl_save_term_translation', $original_tax, $translated_term );
		}
	}

	/**
	 * Prints a hidden div, containing the list of allowed terms for a post type in each language.
	 * This is used to only display the correct categories and tags in the quick-edit fields of the post table.
	 *
	 * @param $column_name
	 * @param $post_type
	 */
	public static function quick_edit_terms_removal( $column_name, $post_type ) {
		global $sitepress, $wpdb;
		if ( $column_name == 'icl_translations' ) {
			$taxonomies                     = array_filter( get_object_taxonomies( $post_type ), array(
				$sitepress,
				'is_translated_taxonomy'
			) );
			$terms_by_language_and_taxonomy = array();
			
			if ( ! empty( $taxonomies ) ) {
				$res = $wpdb->get_results( "	SELECT language_code, taxonomy, term_id FROM {$wpdb->term_taxonomy} tt
 										JOIN {$wpdb->prefix}icl_translations t
 											ON t.element_id = tt.term_taxonomy_id
 												AND t.element_type = CONCAT('tax_', tt.taxonomy)
                                        WHERE tt.taxonomy IN (" . wpml_prepare_in( $taxonomies ) . " )" );
			} else {
				$res = array();
			}
		
			foreach ( $res as $term ) {
				$lang                                              = $term->language_code;
				$tax                                               = $term->taxonomy;
				$terms_by_language_and_taxonomy[ $lang ]           = isset( $terms_by_language_and_taxonomy[ $lang ] ) ? $terms_by_language_and_taxonomy[ $lang ] : array();
				$terms_by_language_and_taxonomy[ $lang ][ $tax ]   = isset( $terms_by_language_and_taxonomy[ $lang ][ $tax ] ) ? $terms_by_language_and_taxonomy[ $lang ][ $tax ] : array();
				$terms_by_language_and_taxonomy[ $lang ][ $tax ][] = $term->term_id;
			}
			$terms_json = wp_json_encode( $terms_by_language_and_taxonomy );
			$output     = '<div id="icl-terms-by-lang" style="display: none;">' . $terms_json . '</div>';
			echo $output;
		}
	}

	/**
	 * Creates a new term from an argument array.
	 * @param array $args
	 * @return array|bool
	 * Returns either an array containing the term_id and term_taxonomy_id of the term resulting from this database
	 * write or false on error.
	 */
	public static function create_new_term( $args ) {
		global $wpdb, $sitepress;

		/** @var string $taxonomy */
		$taxonomy = false;
		/** @var string $lang_code */
		$lang_code = false;
		/**
		 * Sets whether translations of posts are to be updated by the newly created term,
		 * should they be missing a translation still.
		 * During debug actions designed to synchronise post and term languages this should not be set to true,
		 * doing so introduces the possibility of removing terms from posts before switching
		 * them with their translation in the correct language.
		 * @var  bool
		 */
		$sync = false;

		extract( $args, EXTR_OVERWRITE );

		require_once dirname( __FILE__ ) . '/wpml-update-term-action.class.php';

		$new_term_action = new WPML_Update_Term_Action( $wpdb, $sitepress, $args );
		$new_term        = $new_term_action->execute();

		if ( $sync && $new_term && $taxonomy && $lang_code ) {
			self::sync_taxonomy_terms_language( $taxonomy );
		}

		return $new_term;
	}

	/**
	 * @param $args
	 * Creates an automatic translation of a term, the name of which is set as "original" . @ "lang_code" and the slug of which is set as "original_slug" . - . "lang_code".
	 *
	 * @return array|bool
	 */
	public function create_automatic_translation( $args ) {
		global $sitepress;

		$term                = false;
		$lang_code           = false;
		$taxonomy            = false;
		$original_id         = false;
		$original_tax_id     = false;
		$trid                = false;
		$original_term       = false;
		$update_translations = false;
		$source_language     = null;

		extract( $args, EXTR_OVERWRITE );

		if ( $trid && ! $original_id ) {
			$original_tax_id = $sitepress->get_original_element_id_by_trid( $trid );
			$original_term = get_term_by( 'term_taxonomy_id', $original_tax_id, $taxonomy, OBJECT, 'no' );
		}

		if ( $original_id && ! $original_tax_id ) {
			$original_term = get_term( $original_id, $taxonomy, OBJECT, 'no' );
			if ( isset ( $original_term[ 'term_taxonomy_id' ] ) ) {
				$original_tax_id = $original_term[ 'term_taxonomy_id' ];
			}
		}

		if ( ! $trid ) {
			$trid = $sitepress->get_element_trid( $original_tax_id, 'tax_' . $taxonomy );
		}

		if ( ! $source_language ) {
			$source_language = $sitepress->get_source_language_by_trid( $trid );
		}

		$existing_translations = $sitepress->get_element_translations( $trid, 'tax_' . $taxonomy );
		if ( $lang_code && isset( $existing_translations[ $lang_code ] ) ) {
			$new_translated_term = false;
		} else {

			if ( ! $original_term ) {
				if ( $original_id ) {
					$original_term = get_term( $original_id, $taxonomy, OBJECT, 'no' );
				} elseif ( $original_tax_id ) {
					$original_term = get_term_by( 'term_taxonomy_id', $original_tax_id, $taxonomy, OBJECT, 'no' );
				}
			}
			$translated_slug = false;

			if ( ! $term && isset( $original_term->name ) ) {
				$term = $original_term->name;

				/**
				 * @deprecated use 'wpml_duplicate_generic_string' instead, with the same arguments
				 */
                $term = apply_filters( 'icl_duplicate_generic_string',
                    $term,
                    $lang_code,
                    array( 'context' => 'taxonomy', 'attribute' => $taxonomy, 'key' => $original_term->term_id ) );

                $term = apply_filters( 'wpml_duplicate_generic_string',
	                $term,
                    $lang_code,
                    array( 'context' => 'taxonomy', 'attribute' => $taxonomy, 'key' => $original_term->term_id ) );
			}
			if ( isset( $original_term->slug ) ) {
				$translated_slug = $original_term->slug;

				/**
				 * @deprecated use 'wpml_duplicate_generic_string' instead, with the same arguments
				 */
                $translated_slug =  apply_filters( 'icl_duplicate_generic_string',
	                $translated_slug,
                    $lang_code,
                    array( 'context' => 'taxonomy_slug', 'attribute' => $taxonomy, 'key' => $original_term->term_id ) );

                $translated_slug =  apply_filters( 'wpml_duplicate_generic_string',
	                $translated_slug,
                    $lang_code,
                    array( 'context' => 'taxonomy_slug', 'attribute' => $taxonomy, 'key' => $original_term->term_id ) );

				$translated_slug = self::term_unique_slug( $translated_slug, $taxonomy, $lang_code );
			}
			$new_translated_term = false;
			if ( $term ) {
				$new_term_args = array(
					'term'                => $term,
					'slug'                => $translated_slug,
					'taxonomy'            => $taxonomy,
					'lang_code'           => $lang_code,
					'original_tax_id'     => $original_tax_id,
					'update_translations' => $update_translations,
					'trid'                => $trid,
					'source_language'     => $source_language
				);

				$new_translated_term = self::create_new_term( $new_term_args );
			}
		}

		return $new_translated_term;
	}

	/**
	 * @param      $taxonomy
	 *
	 * Sets all taxonomy terms to the correct language on each post, having at least one term from the taxonomy.
	 */
	public static function sync_taxonomy_terms_language( $taxonomy ) {
		$all_posts_in_taxonomy = get_posts( array( 'tax_query' => array( 'taxonomy' => $taxonomy ) ) );

		foreach ( $all_posts_in_taxonomy as $post_in_taxonomy ) {
			self::sync_post_and_taxonomy_terms_language( $post_in_taxonomy->ID, $taxonomy );
		}
	}

	/**
	 * @param      $post_id
	 *
	 * Sets all taxonomy terms ot the correct language for a given post.
	 */
	public static function sync_post_terms_language( $post_id ) {

		$taxonomies = get_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {
			self::sync_post_and_taxonomy_terms_language( $post_id, $taxonomy );
		}
	}

	/**
	 * @param             $post_id
	 * @param             $taxonomy
	 * Synchronizes a posts taxonomy term's languages with the posts language for all translations of the post.
	 *
	 */
	public static function sync_post_and_taxonomy_terms_language( $post_id, $taxonomy ) {
		global $sitepress;

		$post                     = get_post( $post_id );
		$post_type                = $post->post_type;
		$post_trid                = $sitepress->get_element_trid( $post_id, 'post_' . $post_type );
		$post_translations        = $sitepress->get_element_translations( $post_trid, 'post_' . $post_type );
		$terms_from_original_post = wp_get_post_terms( $post_id, $taxonomy );

		$is_original = true;

		if ( $sitepress->get_original_element_id( $post_id, 'post_' . $post_type ) != $post_id ) {
			$is_original = false;
		}

		foreach ( $post_translations as $post_language => $translated_post ) {

			$translated_post_id         = $translated_post->element_id;
			if ( ! $translated_post_id ) {
				continue;
			}
			$terms_from_translated_post = wp_get_post_terms( $translated_post_id, $taxonomy );
			if ( $is_original ) {
				$duplicates = $sitepress->get_duplicates( $post_id );
				if ( in_array( $translated_post_id, $duplicates ) ) {
					$terms = array_merge( $terms_from_original_post, $terms_from_translated_post );
				} else {
					$terms = $terms_from_translated_post;
				}
			} else {
				$terms = $terms_from_translated_post;
			}
			foreach ( (array) $terms as $term ) {
				$term_original_tax_id          = $term->term_taxonomy_id;
				$original_term_language_object = $sitepress->get_element_language_details( $term_original_tax_id, 'tax_' . $term->taxonomy );
				if ( $original_term_language_object && isset( $original_term_language_object->language_code ) ) {
					$original_term_language = $original_term_language_object->language_code;
				} else {
					$original_term_language = $post_language;
				}
				if ( $original_term_language != $post_language ) {
					$term_trid        = $sitepress->get_element_trid( $term_original_tax_id, 'tax_' . $term->taxonomy );
					$translated_terms = $sitepress->get_element_translations( $term_trid, 'tax_' . $term->taxonomy, false, false, true );

					$term_id = $term->term_id;
					wp_remove_object_terms( $translated_post_id, (int) $term_id, $taxonomy );

					if ( isset( $translated_terms[ $post_language ] ) ) {
						$term_in_correct_language = $translated_terms[ $post_language ];
						wp_set_post_terms( $translated_post_id, array( (int) $term_in_correct_language->term_id ), $taxonomy, true );
					}

					if ( isset( $term->term_taxonomy_id ) ) {
						wp_update_term_count( $term->term_taxonomy_id, $taxonomy );
					}
				}
				wp_update_term_count( $term_original_tax_id, $taxonomy );
			}
		}
	}

	/**
	 * @param int    $post_id    Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public static function set_object_terms_action( $post_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		global $sitepress;

		//TODO: [WPML 3.2] We have a better way to check if the post is an external type (e.g. Package).
		if ( get_post( $post_id ) ) {
			$bulk = false;

			if ( isset( $_REQUEST['bulk_edit'] ) ) {
				$bulk = true;
			}
			if ( $bulk ) {
				$tt_ids = array_merge( $tt_ids, $old_tt_ids );
				self::quick_edited_post_terms( $post_id, $taxonomy, $tt_ids, $bulk );
			}
			if ( $sitepress->get_setting( 'sync_post_taxonomies' ) ) {
				$term_actions_helper = $sitepress->get_term_actions_helper();
				$term_actions_helper->added_term_relationships( $post_id );
			}
		}
	}

	/**
	 * @param int    $post_id
	 * @param string $taxonomy
	 * @param array  $changed_ttids
	 * @param bool   $bulk
	 * Running this function will remove certain issues arising out of bulk adding of terms to posts of various languages.
	 * This case can result in situations in which the WP Core functionality adds a term to a post, before the language assignment
	 * operations of WPML are triggered. This leads to states in which terms can be assigned to a post even though their language
	 * differs from that of the post.
	 * This function behaves between hierarchical and flag taxonomies. Hierarchical terms from the wrong taxonomy are simply removed
	 * from the post. Flat terms are added with the same name but in the correct language.
	 * For flat terms this implies either the use of the existing term or the creation of a new one.
	 * This function uses wpdb queries instead of the WordPress API, it is therefore save to be run out of
	 * any language setting.
	 */
	public static function quick_edited_post_terms( $post_id, $taxonomy, $changed_ttids = array(), $bulk = false ) {
		global $wpdb, $sitepress, $wpml_post_translations;

		if ( !$sitepress->is_translated_taxonomy ( $taxonomy )
		     || !( $post_lang = $wpml_post_translations->get_element_lang_code ( $post_id ) )
		) {
			return;
		}

		$query_for_allowed_ttids = $wpdb->prepare( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE language_code = %s AND element_type = %s", $post_lang, 'tax_' . $taxonomy );
		$allowed_ttids = $wpdb->get_col( $query_for_allowed_ttids );
		$new_ttids = array();

		foreach ( $changed_ttids as $ttid ) {

			if ( ! in_array( $ttid, $allowed_ttids ) ) {

				$wrong_term_where = array( 'object_id' => $post_id, 'term_taxonomy_id' => $ttid );

				if ( is_taxonomy_hierarchical( $taxonomy ) ) {
					// Hierarchical terms are simply deleted if they land on the wrong language
					$wpdb->delete( $wpdb->term_relationships, array( 'object_id' => $post_id, 'term_taxonomy_id' => $ttid ) );
				} else {

					/* Flat taxonomy terms could also be given via their names and not their ttids
					 * In this case we append the ttids resulting from these names to the $changed_ttids array,
					 * we do this only in the case of these terms actually being present in another but the
					 * posts' language.
					 */

					$query_for_term_name = $wpdb->prepare( "SELECT t.name FROM {$wpdb->terms} AS t JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE tt.term_taxonomy_id=%d", $ttid );
					$term_name           = $wpdb->get_var( $query_for_term_name );

					$ttid_in_correct_lang = false;

					if ( ! empty( $allowed_ttids ) ) {

						$in = wpml_prepare_in($allowed_ttids, "%d");
						// Try to get the ttid of a term in the correct language, that has the same
						$ttid_in_correct_lang = $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id
							FROM
								{$wpdb->terms} AS t
								JOIN {$wpdb->term_taxonomy} AS tt
									ON t.term_id = tt.term_id
							WHERE t.name=%s AND tt.taxonomy=%s AND tt.term_taxonomy_id IN ({$in})", $term_name, $taxonomy ) );
					}
					if ( ! $ttid_in_correct_lang ) {
						/* If we do not have a term by this name in the given taxonomy and language we have to create it.
						 * In doing so we must avoid interactions with filtering by wpml on this functionality and ensure uniqueness for the slug of the newly created term.
						 */

						$new_term = wp_insert_term( $term_name, $taxonomy, array( 'slug' => self::term_unique_slug( sanitize_title( $term_name ), $taxonomy, $post_lang ) ) );
						if ( isset( $new_term[ 'term_taxonomy_id' ] ) ) {
							$ttid_in_correct_lang = $new_term[ 'term_taxonomy_id' ];
							$trid = $bulk ? $sitepress->get_element_trid( $ttid, 'tax_' . $taxonomy ) : false;
							$sitepress->set_element_language_details( $ttid_in_correct_lang, 'tax_' . $taxonomy, $trid, $post_lang );
						}
					}

					if ( ! in_array( $ttid_in_correct_lang, $changed_ttids ) ) {
						$wpdb->update( $wpdb->term_relationships, array( 'term_taxonomy_id' => $ttid_in_correct_lang ), $wrong_term_where );
						$new_ttids [ ] = $ttid_in_correct_lang;
					} else {
						$wpdb->delete( $wpdb->term_relationships, array('object_id'=>$post_id, 'term_taxonomy_id' => $ttid) );
					}
				}
			}
		}
		// Update term counts manually here, since using sql, will not trigger the updating of term counts automatically.
		wp_update_term_count ( array_merge ( $changed_ttids, $new_ttids ), $taxonomy );
	}

	/**
	 * Returns an array of all terms, that have a language suffix on them.
	 * This is used by troubleshooting functionality.
	 *
	 * @return array
	 */
	public static function get_all_terms_with_language_suffix() {
		global $wpdb;

		$lang_codes = $wpdb->get_col( "SELECT code FROM {$wpdb->prefix}icl_languages" );

		/* Build the expression to find all potential candidates for renaming.
		 * These must have the part "<space>@lang_code<space>" in them.
		 */

		$where_parts = array();

		foreach ( $lang_codes as $key => $code ) {
			$where_parts[ $key ] = "t.name LIKE '" . '% @' . esc_sql( $code ) . "%'";
		}

		$where = '(' . join( ' OR ', $where_parts ) . ')';

		$terms_with_suffix = $wpdb->get_results( "SELECT t.name, t.term_id, tt.taxonomy FROM {$wpdb->terms} AS t JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE {$where}" );

		$terms = array();

		foreach ( $terms_with_suffix as $term ) {

			if ( $term->name == WPML_Troubleshooting_Terms_Menu::strip_language_suffix( $term->name ) ) {
				continue;
			}

			$term_id = $term->term_id;

			$term_taxonomy_label = $term->taxonomy;

			$taxonomy = get_taxonomy($term->taxonomy);

			if ( $taxonomy && isset( $taxonomy->labels ) && isset( $taxonomy->labels->name ) ) {
				$term_taxonomy_label = $taxonomy->labels->name;
			}

			if ( isset( $terms[ $term_id ] ) && isset( $terms[ $term_id ][ 'taxonomies' ] ) ) {
				if ( ! in_array( $term_taxonomy_label, $terms[ $term_id ][ 'taxonomies' ] ) ) {
					$terms[ $term_id ][ 'taxonomies' ][ ] =$term_taxonomy_label;
				}
			} else {
				$terms[ $term_id ] = array( 'name' => $term->name, 'taxonomies' => array( $term_taxonomy_label ) );
			}
		}

		return $terms;
	}
}
