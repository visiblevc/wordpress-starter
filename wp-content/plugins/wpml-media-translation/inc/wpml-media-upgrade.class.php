<?php
class WPML_Media_Upgrade
{
	private static $versions = array(
		'2.0',
		'2.0.1',
	);

	static function run()
	{
		global $wpdb;

		//Workaround, as for some reasons, get_option() doesn't work only in this case
		$wpml_media_settings_prepared = $wpdb->prepare("select option_value from {$wpdb->prefix}options where option_name = %s", '_wpml_media');
		$wpml_media_settings = $wpdb->get_col( $wpml_media_settings_prepared );

		//Do not run upgrades if this is a new install (i.e.: plugin has no settings)
		if ( $wpml_media_settings || get_option( '_wpml_media_starting_help' ) ) {
			//echo 'OK';

			//Read the version stored in plugin settings and defaults to '1.6' (the last version before introducing the upgrade logic) if not found
			$current_version = WPML_Media::get_setting( 'version', '1.6' );

			$migration_ran = false;

			if ( version_compare( $current_version, WPML_MEDIA_VERSION, '<' ) ) {

				foreach ( self::$versions as $version ) {
					if ( version_compare( $version, WPML_MEDIA_VERSION, '<=' ) && version_compare( $version, $current_version, '>' ) ) {

						$upgrade_method = 'upgrade_' . str_replace( '.', '_', $version );
						if ( method_exists( __CLASS__, $upgrade_method ) ) {
							self::$upgrade_method();
							$migration_ran = true;
						}
					}
				}
			}
		} else {
			//Nothing to update, setting migration as ran
			$migration_ran = true;
		}

		//If any upgrade method has been completed, or there is nothing to update, update the version stored in plugin settings
		if ( $migration_ran ) {
			WPML_Media::update_setting( 'version', WPML_MEDIA_VERSION );
		}
	}

	private static function upgrade_2_0()
	{
		global $wpdb;
		global $sitepress;

		//Check if the old options are set and in case move them to the new plugin settings, then delete the old ones
		$old_starting_help = get_option( '_wpml_media_starting_help' );
		if ( $old_starting_help ) {
			WPML_Media::update_setting( 'starting_help', $old_starting_help );
			delete_option( '_wpml_media_starting_help' );
		}

		//Create translated media

		$target_language = $sitepress->get_default_language();
		$attachment_ids_prepared = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'attachment');
		$attachment_ids  = $wpdb->get_col( $attachment_ids_prepared );

		//Let's first set the language of all images in default languages
		foreach ( $attachment_ids as $attachment_id ) {
			$wpml_media_lang         = get_post_meta( $attachment_id, 'wpml_media_lang', true );
			$wpml_media_duplicate_of = get_post_meta( $attachment_id, 'wpml_media_duplicate_of', true );

			if ( !$wpml_media_duplicate_of && ( !$wpml_media_lang || $wpml_media_lang == $target_language ) ) {
				$trid = $sitepress->get_element_trid( $attachment_id, 'post_attachment' );
				if ( $trid ) {
					//Since trid exists, get the language from there
					$target_language = $sitepress->get_language_for_element( $attachment_id, 'post_attachment' );
				}

				$sitepress->set_element_language_details( $attachment_id, 'post_attachment', $trid, $target_language );
			}
		}

		//Then all the translations
		foreach ( $attachment_ids as $attachment_id ) {
			$wpml_media_lang         = get_post_meta( $attachment_id, 'wpml_media_lang', true );
			$wpml_media_duplicate_of = get_post_meta( $attachment_id, 'wpml_media_duplicate_of', true );

			if ( $wpml_media_duplicate_of ) {
				$source_language = null;
				$trid            = $sitepress->get_element_trid( $wpml_media_duplicate_of, 'post_attachment' );
				$source_language = false;
				if ( $trid ) {
					//Get the source language of the attachment, just in case is from a language different than the default
					$source_language = $sitepress->get_language_for_element( $wpml_media_duplicate_of, 'post_attachment' );

					//Fix bug on 1.6, where duplicated images are set to the default language
					if ( $wpml_media_lang == $source_language ) {
						$wpml_media_lang = false;
						$attachment      = get_post( $attachment_id );
						if ( $attachment->post_parent ) {
							$parent_post          = get_post( $attachment->post_parent );
							$post_parent_language = $sitepress->get_language_for_element( $parent_post->ID, 'post_' . $parent_post->post_type );
							if ( $post_parent_language ) {
								$wpml_media_lang = $post_parent_language;
							}
						}

						if ( !$wpml_media_lang ) {
							//Trash orphan image
							wp_delete_attachment( $attachment_id );
						}
					}
				}

				if ( $wpml_media_lang ) {
					$sitepress->set_element_language_details( $attachment_id, 'post_attachment', $trid, $wpml_media_lang, $target_language, $source_language );
				}
			}
		}


		//Remove old media translation meta
		//Remove both meta just in case
		$attachment_ids = $wpdb->get_col( $attachment_ids_prepared );
		foreach ( $attachment_ids as $attachment_id ) {
			delete_post_meta( $attachment_id, 'wpml_media_duplicate_of' );
			delete_post_meta( $attachment_id, 'wpml_media_lang' );
		}

		//Featured images
		WPML_Media::duplicate_featured_images();

	}

	private static function upgrade_2_0_1()
	{
		global $wpdb;
		global $sitepress;

		// Fixes attachments metadata among translations
		$sql = "
				SELECT t.element_id, t.trid, t.language_code
				FROM {$wpdb->prefix}icl_translations t
				  LEFT JOIN {$wpdb->postmeta} pm
				  ON t.element_id = pm.post_id AND pm.meta_key=%s
				WHERE t.element_type = %s AND pm.meta_id IS NULL AND element_id IS NOT NULL
				";
		$sql_prepared = $wpdb->prepare($sql, array('_wp_attachment_metadata', 'post_attachment'));

		$original_attachments = $wpdb->get_results( $sql_prepared );

		foreach ( $original_attachments as $original_attachment ) {
			$attachment_metadata = get_post_meta( $original_attachment->element_id, '_wp_attachment_metadata', true );
			if(!$attachment_metadata) {
				$attachment_translations = $sitepress->get_element_translations( $original_attachment->trid, 'post_attachment', true, true );
				// Get _wp_attachment_metadata first translation available
				foreach ( $attachment_translations as $attachment_translation ) {
					if ( $attachment_translation->language_code != $original_attachment->language_code ) {
						$attachment_metadata = get_post_meta( $attachment_translation->element_id, '_wp_attachment_metadata', true );
						// _wp_attachment_metadata found: save it in the original and go to the next attachment
						if ( $attachment_metadata ) {
							update_post_meta( $original_attachment->element_id, '_wp_attachment_metadata', $attachment_metadata );
							break;
						}
					}
				}
			}
		}

		return true;
	}

}