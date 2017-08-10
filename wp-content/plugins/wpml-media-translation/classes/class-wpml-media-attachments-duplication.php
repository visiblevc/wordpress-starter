<?php

class WPML_Media_Attachments_Duplication {

	/**
	 * @var WPML_Post_Status
	 */
	private $status_helper;

	/**
	 * WPML_Media_Attachments_Duplication constructor.
	 *
	 * @param SitePress $sitepress
	 * @param null $status_helper
	 *
	 * @internal param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( $sitepress, WPML_Post_Status $status_helper = null ) {
		$this->sitepress   = $sitepress;
		$this->wpml_wp_api = $this->sitepress->get_wp_api();
		$this->status_helper = $status_helper;
		if ( null === $this->status_helper ) {
			$this->status_helper = wpml_get_post_status_helper();
		}
	}

	function create_duplicate_attachment( $attachment_id, $parent_id, $target_language ) {
		$duplicated_attachment_id = null;

		$attachment_post = $this->wpml_wp_api->get_post( $attachment_id );
		if ( $attachment_post ) {
			$translated_parent_id = false;

			$trid            = $this->sitepress->get_element_trid( $attachment_id, 'post_attachment' );
			$source_language = null;

			if ( $trid ) {
				//Get the source language of the attachment, just in case is from a language different than the default
				$source_language         = $this->sitepress->get_language_for_element( $attachment_id, 'post_attachment' );
				$attachment_translations = $this->sitepress->get_element_translations( $trid, 'post_attachment', true, true );
				if ( null !== $attachment_translations && is_array( $attachment_translations ) ) {
					foreach ( $attachment_translations as $attachment_translation ) {
						$duplicated_attachment_id = null;
						if ( $attachment_translation->language_code === $target_language ) {
							$duplicated_attachment = $this->wpml_wp_api->get_post( $attachment_translation->element_id );
							$translated_parent_id  = $parent_id;

							if ( null !== $duplicated_attachment ) {
								$duplicated_attachment_id = $attachment_translation->element_id;
								if ( $duplicated_attachment->post_parent ) {
									$translated_parent_id = $duplicated_attachment->post_parent;
								}

								if ( $translated_parent_id ) {
									$parent_post = $this->wpml_wp_api->get_post( $translated_parent_id );

									if ( $parent_post ) {
										$parent_id_language_code = $this->sitepress->get_language_for_element( $parent_post->ID, 'post_' . $parent_post->post_type );
										if ( $parent_id_language_code !== $target_language ) {
											$translated_parent_id = $this->get_object_id( $parent_post->ID, $parent_post->post_type, $target_language );
										} else {
											$translated_parent_id = $parent_post->ID;
										}
									}
								}
							}
							break;
						} else {
							$parent_post = $this->wpml_wp_api->get_post( $parent_id );
							if ( $parent_id && $parent_post ) {
								$parent_id_language_code = $this->sitepress->get_language_for_element( $parent_post->ID, 'post_' . $parent_post->post_type );
								if ( $parent_id_language_code !== $target_language ) {
									$translated_parent_id = $this->get_object_id( $parent_post->ID, $parent_post->post_type, $target_language );
								} else {
									$translated_parent_id = $parent_post->ID;
								}
							} else {
								$translated_parent_id = false;
							}
						}
					}
				}
			}

			if ( null !== $duplicated_attachment_id ) {
				$post = $this->wpml_wp_api->get_post( $duplicated_attachment_id );
				if ( null != $post ) {
					$post->post_parent = $translated_parent_id;
					if ( $this->is_valid_post_type( $post->post_type ) ) {
						wp_update_post( $post );
					}
				}
			} elseif ( $trid ) {
				$post = $this->wpml_wp_api->get_post( $attachment_id );
				//Do not attach this media if _wpml_media_duplicate is not set
				$post->post_parent = $translated_parent_id;
				$post->ID          = null;

				$add_attachment_filters_temp = null;
				if ( array_key_exists( 'add_attachment', $GLOBALS['wp_filter'] ) ) {
					$add_attachment_filters_temp = $GLOBALS['wp_filter']['add_attachment'];
					unset( $GLOBALS['wp_filter']['add_attachment'] );
				}
				$duplicated_attachment_id = wp_insert_post( $post );
				if ( null !== $add_attachment_filters_temp ) {
					$GLOBALS['wp_filter']['add_attachment'] = $add_attachment_filters_temp;
					unset( $add_attachment_filters_temp );
				}

				if ( 0 < $duplicated_attachment_id ) {
					$this->sitepress->set_element_language_details( $duplicated_attachment_id, 'post_attachment', $trid, $target_language, $source_language );
					$this->status_helper->set_status( $duplicated_attachment_id, ICL_TM_DUPLICATE );
					$this->status_helper->set_update_status( $duplicated_attachment_id, false );
				}
			}

			if ( null !== $duplicated_attachment_id ) {
				// duplicate the post meta data.
				$meta = $this->wpml_wp_api->get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
				update_post_meta( $duplicated_attachment_id, '_wp_attachment_metadata', $meta );
				update_post_meta( $duplicated_attachment_id, 'wpml_media_processed', 1 );
				$attached_file = $this->wpml_wp_api->get_post_meta( $attachment_id, '_wp_attached_file', true );
				update_post_meta( $duplicated_attachment_id, '_wp_attached_file', $attached_file );

				do_action( 'wpml_media_create_duplicate_attachment', $attachment_id, $duplicated_attachment_id );
			}
		}

		return $duplicated_attachment_id;
	}

	function is_valid_post_type( $post_type ) {
		global $wp_post_types;

		$post_types = array_keys( (array) $wp_post_types );

		return in_array( $post_type, $post_types, true );
	}

	private function get_object_id( $element_id, $element_type = 'post', $language_code = null ) {
		return $this->sitepress->get_object_id( $element_id, $element_type, false, $language_code );
	}
}
