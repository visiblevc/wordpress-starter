<?php

class WPML_TM_Action_Helper {

	public function get_tm_instance(){

		return wpml_load_core_tm();
	}

	public function create_translation_package( $post ) {
		$package_helper = new WPML_Element_Translation_Package();

		return $package_helper->create_translation_package( $post );
	}

	public function add_translation_job( $rid, $translator_id, $translation_package ) {

		return $this->get_update_translation_action( $translation_package )->add_translation_job( $rid,
		                                                                                          $translator_id,
		                                                                                          $translation_package );
	}

	/**
	 * calculate post md5
	 *
	 * @param object|int $post
	 *
	 * @return string
	 * @todo full support for custom posts and custom taxonomies
	 */
	public function post_md5( $post ) {
		global $iclTranslationManagement, $wpdb, $sitepress_settings;

		//TODO: [WPML 3.2] Make it work with PackageTranslation: this is not the right way anymore
		if ( isset( $post->external_type ) && $post->external_type ) {
			$md5str = '';
			foreach ( $post->string_data as $key => $value ) {
				$md5str .= $key . $value;
			}
		} else {
			$post_tags = $post_categories = $custom_fields_values = array();
			if ( is_numeric( $post ) ) {
				$post = get_post( $post );
			}
			foreach ( wp_get_object_terms( $post->ID, 'post_tag' ) as $tag ) {
				$post_tags[] = $tag->name;
			}
			if ( is_array( $post_tags ) ) {
				sort( $post_tags, SORT_STRING );
			}
			foreach ( wp_get_object_terms( $post->ID, 'category' ) as $cat ) {
				$post_categories[] = $cat->name;
			}
			if ( is_array( $post_categories ) ) {
				sort( $post_categories, SORT_STRING );
			}

			// get custom taxonomies
			$taxonomies = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT tx.taxonomy
				FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->term_relationships} tr ON tx.term_taxonomy_id = tr.term_taxonomy_id
				WHERE tr.object_id =%d ",
			                                              $post->ID ) );
			sort( $taxonomies, SORT_STRING );
			if ( isset( $sitepress_settings['taxonomies_sync_option'] ) ) {
				foreach ( $taxonomies as $t ) {
					if ( taxonomy_exists( $t ) && isset( $sitepress_settings['taxonomies_sync_option'][ $t ] ) && $sitepress_settings['taxonomies_sync_option'][ $t ] == 1 ) {
						$taxs = array();
						foreach ( wp_get_object_terms( $post->ID, $t ) as $trm ) {
							$taxs[] = $trm->name;
						}
						if ( $taxs ) {
							sort( $taxs, SORT_STRING );
							$all_taxs[] = '[' . $t . ']:' . join( ',', $taxs );
						}
					}
				}
			}
			$custom_fields_values = array();
			if ( isset( $iclTranslationManagement->settings['custom_fields_translation'] ) && is_array( $iclTranslationManagement->settings['custom_fields_translation'] ) ) {
				foreach ( $iclTranslationManagement->settings['custom_fields_translation'] as $cf => $op ) {
					if ( $op == 2 || $op == 1 ) {
						$value = get_post_meta( $post->ID, $cf, true );
						if ( ! is_array( $value ) && ! is_object( $value ) ) {
							$custom_fields_values[] = $value;
						}
					}
				}
			}
			$md5str = $post->post_title . ';' . $post->post_content . ';' . join( ',', $post_tags ) . ';' . join( ',',
			                                                                                                      $post_categories ) . ';' . join( ',', $custom_fields_values );
			if ( ! empty( $all_taxs ) ) {
				$md5str .= ';' . join( ';', $all_taxs );
			}
			if ( wpml_get_setting_filter( false, 'translated_document_page_url' ) === 'translate' ) {
				$md5str .= $post->post_name . ';';
			}
		}
		$md5 = md5( $md5str );

		return $md5;
	}

	private function get_update_translation_action( $translation_package ) {
		require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-update-external-translation-data-action.class.php';
		require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-update-post-translation-data-action.class.php';

		return array_key_exists( 'type', $translation_package ) && $translation_package['type'] === 'post'
			? new WPML_TM_Update_Post_Translation_Data_Action() : new WPML_TM_Update_External_Translation_Data_Action();
	}
}