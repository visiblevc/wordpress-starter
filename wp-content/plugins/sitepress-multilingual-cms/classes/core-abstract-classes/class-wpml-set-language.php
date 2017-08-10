<?php

class WPML_Set_Language extends WPML_Full_Translation_API {

	/**
	 * @param int           $el_id
	 * @param string        $el_type
	 * @param int|bool|null $trid Trid the element is to be assigned to. Input that is == false will cause the term to
	 *                            be assigned a new trid and potential translation relations to/from it to disappear.
	 * @param string        $language_code
	 * @param null|string   $src_language_code
	 * @param bool          $check_duplicates
	 *
	 * @return bool|int|null|string
	 */
	public function set(
		$el_id,
		$el_type = 'post_post',
		$trid,
		$language_code,
		$src_language_code = null,
		$check_duplicates = true
	) {
		$this->clear_cache();
		if ( $check_duplicates && $el_id && (bool) ( $el_type_db = $this->check_duplicate( $el_type,
				$el_id ) ) === true
		) {
			throw new InvalidArgumentException( 'element_id and type do not match for element_id:' . $el_id . ' the database contains ' . $el_type_db . ' while this function was called with ' . $el_type );
		}

		$context = explode( '_', $el_type );
		$context = $context[0];

		$src_language_code = $src_language_code === $language_code ? null : $src_language_code;

		if ( $trid ) { // it's a translation of an existing element
			/** @var int $trid  is an integer if not falsy */
			$this->maybe_delete_orphan( $trid, $language_code, $el_id );
			if ( $el_id  && (bool) ( $translation_id = $this->is_language_change( $el_id, $el_type, $trid ) ) === true
			     && (bool) $this->trid_lang_trans_id( $trid, $language_code ) === false
			) {
				$this->wpdb->update(
					$this->wpdb->prefix . 'icl_translations',
					array( 'language_code' => $language_code ),
					array( 'translation_id' => $translation_id )
				);

				do_action(
					'wpml_translation_update',
					array(
						'type' => 'update',
						'trid' => $trid,
						'element_id' => $el_id,
						'element_type' => $el_type,
						'translation_id' => $translation_id,
						'context' => $context
					)
				);

			} elseif ( $el_id && (bool) ( $translation_id = $this->existing_element( $el_id, $el_type ) ) === true ) {
				$this->change_translation_of( $trid, $el_id, $el_type, $language_code, $src_language_code );
			} elseif ( (bool) ( $translation_id = $this->is_placeholder_update( $trid, $language_code ) ) === true ) {
				$this->wpdb->update(
					$this->wpdb->prefix . 'icl_translations',
					array( 'element_id' => $el_id ),
					array( 'translation_id' => $translation_id )
				);

				do_action(
					'wpml_translation_update',
					array(
						'type' => 'update',
						'trid' => $trid,
						'element_id' => $el_id,
						'element_type' => $el_type,
						'translation_id' => $translation_id,
						'context' => $context
					)
				);

			} elseif ( (bool) ( $translation_id = $this->trid_lang_trans_id( $trid, $language_code ) ) === false ) {
				$translation_id = $this->insert_new_row( $el_id, $trid, $el_type, $language_code, $src_language_code );
			}
		} else { // it's a new element or we are removing it from a trid
			$this->delete_existing_row( $el_type, $el_id );
			$translation_id = $this->insert_new_row( $el_id, false, $el_type, $language_code, $src_language_code );
		}

		$this->clear_cache();
		if ( $translation_id && substr( $el_type, 0, 4 ) === 'tax_' ) {
			$taxonomy = substr( $el_type, 4 );
			do_action( 'created_term_translation', $taxonomy, $el_id, $language_code );
		}
		do_action( 'icl_set_element_language', $translation_id, $el_id, $language_code, $trid );

		return $translation_id;
	}

	/**
	 * Returns the translation id belonging to a specific trid, language_code combination
	 *
	 * @param int    $trid
	 * @param string $lang
	 *
	 * @return null|int
	 */
	private function trid_lang_trans_id( $trid, $lang ) {

		return $this->wpdb->get_var( $this->wpdb->prepare( "SELECT translation_id
															FROM {$this->wpdb->prefix}icl_translations
															WHERE trid = %d
																AND language_code = %s
															LIMIT 1",
		                                                   $trid,
		                                                   $lang ) );
	}

	/**
	 * Changes the source_language_code of an element
	 *
	 * @param int    $trid
	 * @param int    $el_id
	 * @param string $el_type
	 * @param string $language_code
	 * @param string $src_language_code
	 */
	private function change_translation_of( $trid, $el_id, $el_type, $language_code, $src_language_code ) {
		$src_language_code = empty( $src_language_code )
			? $this->sitepress->get_source_language_by_trid( $trid ) : $src_language_code;
		if ( $src_language_code !== $language_code ) {
			$this->wpdb->update(
				$this->wpdb->prefix . 'icl_translations',
				array(
					'trid'                 => $trid,
					'language_code'        => $language_code,
					'source_language_code' => $src_language_code
				),
				array( 'element_type' => $el_type, 'element_id' => $el_id )
			);

			$context = explode( '_', $el_type );

			do_action(
				'wpml_translation_update',
				array(
					'type' => 'update',
					'trid' => $trid,
					'element_id' => $el_id,
					'element_type' => $el_type,
					'context' => $context[0],
				)
			);
		}
	}

	/**
	 * @param string $el_type
	 * @param int    $el_id
	 */
	private function delete_existing_row( $el_type, $el_id ) {

		$context = explode( '_', $el_type );
		$update_args = array(
			'element_id' => $el_id,
			'element_type' => $el_type,
			'context' => $context[0]
		);

		do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

		$this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->wpdb->prefix}icl_translations
					 WHERE element_type = %s
				      AND element_id = %d",
				$el_type,
				$el_id ) );

		do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );
	}

	/**
	 * Inserts a new row into icl_translations
	 *
	 * @param int    $el_id
	 * @param int    $trid
	 * @param string $el_type
	 * @param string $language_code
	 * @param string $src_language_code
	 *
	 * @return int Translation ID of the new row
	 */
	private function insert_new_row( $el_id, $trid, $el_type, $language_code, $src_language_code ) {
		$new = array(
			'element_type'  => $el_type,
			'language_code' => $language_code,
		);

		if ( $trid === false ) {
			$trid = 1 + $this->wpdb->get_var( "SELECT MAX(trid) FROM {$this->wpdb->prefix}icl_translations" );
		} else {
			$src_language_code           = empty( $src_language_code )
				? $this->sitepress->get_source_language_by_trid( $trid ) : $src_language_code;
			$new['source_language_code'] = $src_language_code;
		}

		$new['trid'] = $trid;
		if ( $el_id ) {
			$new['element_id'] = $el_id;
		}
		$this->wpdb->insert( $this->wpdb->prefix . 'icl_translations', $new );
		$translation_id = $this->wpdb->insert_id;

		$context = explode( '_', $el_type );

		do_action(
			'wpml_translation_update',
			array(
				'type' => 'insert',
				'trid' => $trid,
				'element_id' => $el_id,
				'element_type' => $el_type,
				'translation_id' => $translation_id,
				'context' => $context[0]
			)
		);

		return $translation_id;
	}

	/**
	 * Checks if a row exists for a concrete id, type and trid combination
	 * in icl_translations.
	 *
	 * @param int    $el_id
	 * @param string $el_type
	 * @param int    $trid
	 *
	 * @return null|int
	 */
	private function is_language_change( $el_id, $el_type, $trid ) {

		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT translation_id
				 FROM {$this->wpdb->prefix}icl_translations
				 WHERE element_type = %s
				   AND element_id = %d
				   AND trid = %d",
				$el_type,
				$el_id,
				$trid
			)
		);
	}

	/**
	 * Checks if a given trid, language_code combination contains a placeholder with NULL element_id
	 * and if so returns the translation id of this row.
	 *
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return null|string translation id
	 */
	private function is_placeholder_update( $trid, $language_code ) {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( "	SELECT translation_id
									FROM {$this->wpdb->prefix}icl_translations
									WHERE trid=%d
										AND language_code = %s
										AND element_id IS NULL",
				$trid,
				$language_code
			) );
	}

	/**
	 * Checks if a row in icl_translations exists for a concrete element type and id combination
	 *
	 * @param int    $el_id
	 * @param string $el_type
	 *
	 * @return null|int
	 */
	private function existing_element( $el_id, $el_type ) {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT translation_id
				                   FROM {$this->wpdb->prefix}icl_translations
				                   WHERE element_type= %s
				                    AND element_id= %d
				                   LIMIT 1",
				$el_type,
				$el_id
			)
		);
	}

	/**
	 * Checks if a trid contains an existing translation other than a specific element id and deletes that row if it
	 * exists.
	 *
	 * @param int    $trid
	 * @param string $language_code
	 * @param int    $correct_element_id
	 */
	private function maybe_delete_orphan( $trid, $language_code, $correct_element_id ) {
		$result = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT translation_id, element_type, element_id
				 FROM {$this->wpdb->prefix}icl_translations
				 WHERE   trid = %d
					AND language_code = %s
					AND element_id <> %d
					AND source_language_code IS NOT NULL
					",
				$trid,
				$language_code,
				$correct_element_id
			)
		);

		$translation_id = ( null != $result ? $result->translation_id : null );

		if ( $translation_id ) {

			$context = explode( '_', $result->element_type );
			$update_args = array(
				'trid' => $trid,
				'element_id' => $result->element_id,
				'element_type' => $result->element_type,
				'translation_id' => $translation_id,
				'context' => $context[0]
			);

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

			$this->wpdb->query(
				$this->wpdb->prepare(
					"DELETE FROM {$this->wpdb->prefix}icl_translations WHERE translation_id=%d",
					$translation_id
				)
			);

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );
		}
	}

	/**
	 * Checks if a duplicate element_id already exists with a different than the input type.
	 * This only applies to posts and taxonomy terms.
	 *
	 * @param string $el_type
	 * @param int    $el_id
	 *
	 * @return null|string null if no duplicate icl translations entry is found
	 * having a different than the input element type, the element type if a
	 * duplicate row is found.
	 */
	private function check_duplicate( $el_type, $el_id ) {
		$res   = false;
		$exp   = explode( '_', $el_type );
		$_type = $exp[0];
		if ( in_array( $_type, array( 'post', 'tax' ) ) ) {
			$res = $this->duplicate_from_db( $el_id, $el_type, $_type );
			if ( $res ) {
				$fix_assignments = new WPML_Fix_Type_Assignments( $this->sitepress );
				$fix_assignments->run();
				$res = $this->duplicate_from_db( $el_id, $el_type, $_type );
			}
		}

		return $res;
	}

	private function duplicate_from_db( $el_id, $el_type, $_type ) {

		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT element_type
                        FROM {$this->wpdb->prefix}icl_translations
                        WHERE element_id = %d
                          AND element_type <> %s
                          AND element_type LIKE %s",
				$el_id,
				$el_type,
				$_type . '%'
			)
		);
	}

	private function clear_cache() {
		$this->term_translations->reload();
		$this->post_translations->reload();
		$this->sitepress->get_translations_cache()->clear();
	}
}
