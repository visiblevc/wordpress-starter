<?php

class WPML_Package_TM extends WPML_Package_TM_Jobs {
	public function __construct( $package ) {
		parent::__construct( $package );
	}

	public function get_translation_statuses() {
		global $sitepress;
		$package = $this->package;

		$post_trid = $this->get_trid();
        $items = array();
        if ( $post_trid ) {
            $translation_element_type = $package->get_translation_element_type();
            $post_translations        = $sitepress->get_element_translations( $post_trid, $translation_element_type );
            foreach ( $post_translations as $lang => $translation ) {
                $translation->trid = $post_trid;
                $item[ ]           = $this->set_translation_status( $package, $translation, $lang );
            }
        } else {
            $items[ ] = $package;
        }

        return $items;
    }

	/**
	 * @param $package WPML_Package
	 * @param $translation
	 * @param $lang
	 * @return mixed
	 */
	private function set_translation_status( $package, $translation, $lang ) {
		global $wpdb;

        if ( !$package->trid ) {
            $package->trid = $translation->trid;
        }

		$res_query = "SELECT status, needs_update, md5 FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d";
		$res_args = array( $translation->translation_id );
		$res_prepare = $wpdb->prepare( $res_query, $res_args );
		$res = $wpdb->get_row( $res_prepare );
		$_suffix = str_replace( '-', '_', $lang );
		$status = ICL_TM_NOT_TRANSLATED;
		if ( $res ) {
			$status = $res->status;
			$index = 'needs_update_' . $_suffix;
			$package->$index = $res->needs_update;
		}
		$index = 'status_' . $_suffix;
		$package->$index = apply_filters( 'wpml_translation_status', $status, $translation->trid, $lang, 'package' );

		return $package;
	}

	public function is_translation_in_progress() {
		global $wpdb;

		$post_translations = $this->get_post_translations();

		foreach ( $post_translations as $lang => $translation ) {
			$res_query    = "SELECT status, needs_update, md5
								FROM {$wpdb->prefix}icl_translation_status
								WHERE translation_id=%d";
			$res_prepared = $wpdb->prepare( $res_query, array( $translation->translation_id ) );
			$res          = $wpdb->get_row( $res_prepared );
			if ( $res && $res->status == ICL_TM_IN_PROGRESS ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Update translations
	 *
	 * @param bool $is_new_package
	 * @param bool $needs_update - when deleting single field we do not need to change the translation status of the form
	 *
	 * @return bool
	 */
	public function update_package_translations( $is_new_package, $needs_update = true ) {

		global $iclTranslationManagement;

		$updated = false;

		$package = $this->package;
		$trid    = $this->get_trid();

		if ( $is_new_package ) {
			$this->set_language_details( );

			return true;
		}

		$item_md5_translations = $this->get_item_md5_translations( $trid );

		if ( $item_md5_translations ) {
			$md5     = $iclTranslationManagement->post_md5( $this->package );

			if ( $md5 != $item_md5_translations[ 0 ]->md5 ) { //all translations need update

				$translation_package = $iclTranslationManagement->create_translation_package( $this->package );

				foreach ( $item_md5_translations as $translation ) {
					$translation_id = $translation->translation_id;
					$previous_state = $this->get_translation_state( $translation );

					$data = array();
					if ( ! empty( $previous_state ) ) {
						$data[ 'previous_state' ] = serialize( $previous_state );
					}
					$data = array(
						'translation_id'      => $translation_id,
						'translation_package' => serialize( $translation_package ),
						'md5'                 => $md5,
					);

					if ( $needs_update ) {
						$data[ 'needs_update' ] = 1;
					}

					$update_result = $iclTranslationManagement->update_translation_status( $data );

					$rid = $update_result[ 0 ];

					$this->update_translation_job( $rid, $this->package );

					//change job status only when needs update
					if ( $needs_update ) {
						$job_id = $this->get_translation_job_id( $rid );
						if ( $job_id ) {
							$this->update_translation_job_needs_update( $job_id );
							$updated = true;
						}
					}
				}
			}
		}

		return $updated;
	}

	private function get_item_md5_translations( $trid ) {
		global $wpdb;

		$item_md5_translations_query   = "
        	SELECT t.translation_id, s.md5
        	FROM {$wpdb->prefix}icl_translations t
        		NATURAL JOIN {$wpdb->prefix}icl_translation_status s
        	WHERE t.trid=%d
        	    AND t.source_language_code IS NOT NULL";
		$item_md5_translations_prepare = $wpdb->prepare( $item_md5_translations_query, $trid );
		$item_md5_translations         = $wpdb->get_results( $item_md5_translations_prepare );

		return $item_md5_translations;
	}

	private function get_translation_state( $translation ) {
		global $wpdb;

		$previous_state_query   = "
                        SELECT status, translator_id, needs_update, md5, translation_service, translation_package, timestamp, links_fixed
                        FROM {$wpdb->prefix}icl_translation_status
                        WHERE translation_id = %d
                    ";
		$previous_state_prepare = $wpdb->prepare( $previous_state_query, $translation->translation_id );
		$previous_state         = $wpdb->get_row( $previous_state_prepare, ARRAY_A );

		return $previous_state;
	}

	public function add_package_to_basket( $translation_action, $source_language, $target_language ) {
		if ( ! $this->validate_basket_package_item( $translation_action, $source_language ) ) {
			return false;
		}

		if ( $this->is_duplicate_or_do_nothing( $translation_action ) ) {
			$this->duplicate_or_do_nothing( $translation_action, $target_language );
		} elseif ( $translation_action == 1 ) {
			$this->send_package_to_basket( $source_language, $target_language );
		}

		return true;
	}

	private function duplicate_package( $package_id ) {
		//TODO: [WPML 3.3] duplication to be done
		//						$this->make_duplicate( $package_id, $language_code );
	}

	/**
	 * @param $translation_action
	 * @param $source_language
	 *
	 * @return bool
	 */
	private function validate_basket_package_item( $translation_action, $source_language ) {
		ICL_AdminNotifier::remove_message( 'the_basket_items_notification' );

		$package                = $this->package;
		$package_id             = $package->ID;
		$basket                 = TranslationProxy_Basket::get_basket();
		$package_is_valid       = true;
		$basket_source_language = TranslationProxy_Basket::get_source_language();
		if ( $basket && $basket_source_language ) {
			if ( $source_language != $basket_source_language ) {
				TranslationProxy_Basket::add_message( array(
					                                      'type' => 'update',
					                                      'text' => __( 'You cannot add packages in this language to the basket since it already contains posts, packages or strings of another source language!
					Either submit the current basket and then add the post or delete the posts of differing language in the current basket', 'sitepress' )
				                                      ) );
				TranslationProxy_Basket::update_basket();

				$package_is_valid = false;
			}
		}

		$select_one_lang_message = __( 'Please select at least one language to translate into.', 'sitepress' );

		if ( ! $translation_action ) {
			TranslationProxy_Basket::add_message( array(
				'type' => 'error',
				'text' => $select_one_lang_message,
			) );

			$package_is_valid = false;
		} else {
			TranslationProxy_Basket::remove_message( $select_one_lang_message );
		}

		if ( ! $package_id ) {
			TranslationProxy_Basket::add_message( array(
				                                      'type' => 'error',
				                                      'text' => __( 'Please select at least one document to translate.', 'sitepress' )
			                                      ) );

			$package_is_valid = false;

			return $package_is_valid;
		}

		return $package_is_valid;
	}

	private function is_duplicate_or_do_nothing( $translation_action ) {
		return $translation_action == 0 || $translation_action == 2;
	}

	private function duplicate_or_do_nothing( $translation_action, $target_language ) {
		$basket = TranslationProxy_Basket::get_basket();

		$package    = $this->package;
		$package_id = $package->ID;

		// iterate posts ids, check if they are in wp_options
		// if they are set to translate for this particular language
		// end then remove it
		// check if we have this post in wp_options
		// end remove
		if ( isset( $basket[ 'package' ][ $package_id ][ 'to_langs' ][ $target_language ] ) ) {
			unset( $basket[ 'package' ][ $package_id ][ 'to_langs' ][ $target_language ] );
			TranslationProxy_Basket::update_basket( $basket );
		}
		// if user want to duplicate this post, lets do this
		if ( $translation_action == 2 ) {
			$this->duplicate_package( $package_id );
		}
	}

	/**
	 * @param     $source_language
	 * @param     $target_language
	 *
	 * @throws WPML_Package_Exception
	 */
	public function send_package_to_basket( $source_language, $target_language ) {
		global $sitepress, $iclTranslationManagement;

		$package       = $this->package;
		$package_id    = $package->ID;
		$basket        = TranslationProxy_Basket::get_basket();
		$language_name = $sitepress->get_display_language_name( $target_language );

		$send_to_basket = true;
		$package_helper = new WPML_Package_Helper();
		$post           = $package_helper->get_translatable_item( null, $package_id );

		$post_title   = esc_html( $post->title );
		$element_type = $package->get_element_type_prefix() . '_' . $post->kind_slug;
		$trid         = $sitepress->get_element_trid( $package_id, $element_type );
		$job_id       = $iclTranslationManagement->get_translation_job_id( $trid, $target_language );
		if ( $job_id ) {
			$job_details    = $iclTranslationManagement->get_translation_job( $job_id );
			$send_to_basket = $this->validate_package_status( $job_details, $post_title, $language_name );
		}

		if ( $send_to_basket ) {
			$basket[ 'package' ][ $package_id ][ 'from_lang' ]                    = $source_language;
			$basket[ 'package' ][ $package_id ][ 'to_langs' ][ $target_language ] = 1;
			// set basket language if not already set
			if ( ! isset( $basket[ 'source_language' ] ) ) {
				$basket[ 'source_language' ] = $source_language;
			}
		}
		TranslationProxy_Basket::update_basket( $basket );
	}

	/**
	 * @param $job_details
	 * @param $post_title
	 * @param $language_name
	 *
	 * @return bool
	 * @throws WPML_Package_Exception
	 */
	private function validate_package_status( $job_details, $post_title, $language_name ) {
		$send_to_basket = true;
		$message_args   = array();

		if ( $job_details->status == ICL_TM_IN_PROGRESS ) {
			$message_args   = array(
				'type' => 'update',
				'text' => sprintf( __( 'Post "%s" will be ignored for %s, because translation is already in progress.', 'sitepress' ), $post_title, $language_name )
			);
			$send_to_basket = false;
		} elseif ( $job_details->status == ICL_TM_WAITING_FOR_TRANSLATOR ) {
			$message_args   = array(
				'type' => 'update',
				'text' => sprintf( __( 'Post "%s" will be ignored for %s, because translation is already waiting for translator.', 'sitepress' ), $post_title, $language_name )
			);
			$send_to_basket = false;
		}
		if ( ! $send_to_basket ) {
			TranslationProxy_Basket::add_message( $message_args );
			TranslationProxy_Basket::update_basket();
		}

		return $send_to_basket;
	}
	
	public function is_in_basket( $target_lang ) {
		if ( class_exists( 'TranslationProxy_Basket') ) {
			$basket = TranslationProxy_Basket::get_basket();
			return isset( $basket[ 'package' ][ $this->package->ID ][ 'to_langs' ][ $target_lang ] );
		} else {
			return false;
		}
		
	}
}
