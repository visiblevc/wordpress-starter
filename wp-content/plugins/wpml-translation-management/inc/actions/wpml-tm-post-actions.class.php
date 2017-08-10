<?php

class WPML_TM_Post_Actions extends WPML_Translation_Job_Helper {

	/** @var  WPML_TM_Action_Helper $action_helper */
	private $action_helper;

	/** @var  WPML_TM_Blog_Translators $blog_translators */
	private $blog_translators;

	/** @var  WPML_TM_Records $tm_records */
	private $tm_records;

	/**
	 * WPML_TM_Post_Actions constructor.
	 *
	 * @param WPML_TM_Action_Helper    $helper
	 * @param WPML_TM_Blog_Translators $blog_translators
	 * @param WPML_TM_Records          $tm_records
	 */
	public function __construct( &$helper, &$blog_translators, &$tm_records ) {
		$this->action_helper    = $helper;
		$this->blog_translators = $blog_translators;
		$this->tm_records       = &$tm_records;
	}

	public function save_post_actions( $post_id, $post, $force_set_status = false ) {
		global $wpdb, $sitepress, $current_user;

		$trid = isset( $_POST['icl_trid'] ) && is_numeric( $_POST['icl_trid'] )
			? $_POST['icl_trid'] : $sitepress->get_element_trid( $post_id, 'post_' . $post->post_type );

		// set trid and lang code if front-end translation creating
		$trid = apply_filters( 'wpml_tm_save_post_trid_value', isset( $trid ) ? $trid : '', $post_id );
		$lang = apply_filters( 'wpml_tm_save_post_lang_value', isset( $lang ) ? $lang : '', $post_id );

		$trid = $this->maybe_retrive_trid_again( $trid, $post );
		$needs_second_update = array_key_exists( 'needs_second_update', $_POST ) ? (bool) $_POST['needs_second_update'] : false;

		// is this the original document?
		$is_original = empty( $trid )
			? false
			: ! (bool) $this->tm_records
				->icl_translations_by_element_id_and_type_prefix( $post_id, 'post_' . $post->post_type )
				->source_language_code();
		if ( ! empty( $trid ) && ! $is_original ) {
			$lang = $lang ? $lang : $this->get_save_post_lang( $lang, $post_id );
			$res  = $wpdb->get_row( $wpdb->prepare( "
			 SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL
		 ",
				$trid ) );
			if ( $res ) {
				$original_post_id = $res->element_id;
				$from_lang        = $res->language_code;
				$original_post    = get_post( $original_post_id );
				$md5              = $this->action_helper->post_md5( $original_post );
				$translation_id   = $this->tm_records
					->icl_translations_by_trid_and_lang( $trid, $lang )
					->translation_id();
				$user_id = $current_user->ID;
				$this->maybe_add_as_translator( $user_id, $lang, $from_lang );
				if ( $translation_id ) {
					$translation_package = $this->action_helper->create_translation_package( $original_post_id );
					list( $rid, $update ) = $this->action_helper->get_tm_instance()->update_translation_status( array(
						                                                                                            'translation_id'      => $translation_id,
						                                                                                            'status'              => isset( $force_set_status ) && $force_set_status > 0 ? $force_set_status : ICL_TM_COMPLETE,
						                                                                                            'translator_id'       => $user_id,
						                                                                                            'needs_update'        => $needs_second_update,
						                                                                                            'md5'                 => $md5,
						                                                                                            'translation_service' => 'local',
						                                                                                            'translation_package' => serialize( $translation_package )
					                                                                                            ) );
					if ( ! $update ) {
						$job_id = $this->action_helper->add_translation_job( $rid, $user_id, $translation_package );
					} else {
						$job_id_sql      = "SELECT MAX(job_id) FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d GROUP BY rid";
						$job_id_prepared = $wpdb->prepare( $job_id_sql, $rid );
						$job_id          = $wpdb->get_var( $job_id_prepared );
						$job_id          = $job_id ? $job_id : $this->action_helper->add_translation_job( $rid,
						                                                                                  $user_id,
						                                                                                  $translation_package );
					}

					// saving the translation
					do_action( 'wpml_save_job_fields_from_post', $job_id );
				}
			}
		}

		if ( ! empty( $trid ) && empty( $_POST['icl_minor_edit'] ) ) {
			$is_original  = false;
			$translations = $sitepress->get_element_translations( $trid, 'post_' . $post->post_type );
			foreach ( $translations as $translation ) {
				if ( $translation->original == 1 && $translation->element_id == $post_id ) {
					$is_original = true;
					break;
				}
			}

			if ( $is_original ) {
				$md5 = $this->action_helper->post_md5( $post_id );
				foreach ( $translations as $translation ) {
					if ( ! $translation->original ) {
						$emd5 = $this->tm_records->icl_translation_status_by_translation_id( $translation->translation_id )->md5();
						if ( $md5 !== $emd5 ) {
							$translation_package = $this->action_helper->create_translation_package( $post_id );
							$data                = array(
								'translation_id'      => $translation->translation_id,
								'needs_update'        => 1,
								'md5'                 => $md5,
								'translation_package' => serialize( $translation_package ),
							);
							$this->action_helper->get_tm_instance()->update_translation_status( $data );
						}
					}
				}
			}
		}
	}

	/**
	 * Adds the given language pair to the user.
	 *
	 * @param int    $user_id
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @used-by \WPML_TM_Post_Actions::save_post_actions to add language pairs to admin users automatically when saving
	 *                                                   a translation in a given language pair.
	 */
	private function maybe_add_as_translator( $user_id, $target_lang, $source_lang ) {
		if ( $target_lang && ! $this->blog_translators->is_translator( $user_id,
		                                                        array(
			                                                        'lang_from'      => $source_lang,
			                                                        'lang_to'        => $target_lang,
			                                                        'admin_override' => false
		                                                        ) )
		) {
			$this->action_helper->get_tm_instance()->add_translator( $user_id,
			                                                         array( $source_lang => array( $target_lang => 1 ) ) );
		}
	}

	private function get_save_post_lang( $lang, $post_id ) {
		if ( ( ! isset( $lang ) || ! $lang ) && ! empty( $_POST['icl_post_language'] ) ) {
			$lang = $_POST['icl_post_language'];
		} else {
			global $wpml_post_translations;

			$lang = $wpml_post_translations->get_element_lang_code( $post_id );
		}

		return $lang;
	}

	private function maybe_retrive_trid_again( $trid, $post ) {
		global $wpdb, $sitepress;
		$element_type_from_trid = $wpdb->get_var( $wpdb->prepare( "SELECT element_type FROM {$wpdb->prefix}icl_translations WHERE trid=%d", $trid ) );
		if ( $element_type_from_trid && $post->post_type !== $element_type_from_trid ) {
			$trid = $sitepress->get_element_trid( $post->ID, 'post_' . $post->post_type );
		}

		return $trid;
	}
}