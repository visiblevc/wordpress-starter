<?php

class WPML_Comment_Duplication{

	public function move_to_original($duplicate_of, $post_duplicates, $comment){
		global $wpml_post_translations, $wpdb;

		$_orig_lang                     = $wpml_post_translations->get_element_lang_code ( $duplicate_of );
		$post_duplicates[ $_orig_lang ] = $duplicate_of;
		$original_parent = get_comment_meta ( $comment[ 'comment_parent' ], '_icl_duplicate_of', true );
		$wpdb->update (
			$wpdb->comments,
			array(
				'comment_post_ID' => $duplicate_of,
				'comment_parent'  => $original_parent
			), array( 'comment_ID' => $comment['comment_ID'] ), array( '%d', '%d' ), array( '%d' )
		);
		wp_update_comment_count_now($duplicate_of);
	}

	public function get_correct_parent($comment, $dup_id){
		global $wpdb;

		$translated_parent = $wpdb->get_var (
			$wpdb->prepare (
				" SELECT cmb.comment_id
				  FROM {$wpdb->commentmeta} cm
				  JOIN {$wpdb->commentmeta} cmb
				    ON ( cmb.meta_value = cm.meta_value
				    AND cmb.meta_key = cm.meta_key)
				    OR cm.comment_id = cmb.meta_value
				  JOIN {$wpdb->comments} c
				    ON c.comment_ID = cmb.comment_id
				  WHERE cm.meta_key = '_icl_duplicate_of'
			        AND ( cm.comment_id = %d OR cm.meta_value = %d )
			        AND c.comment_post_ID = %d",
				$comment[ 'comment_parent' ],
				$comment[ 'comment_parent' ],
				$dup_id
			)
		);

		return $translated_parent;
	}

	public function insert_duplicated_comment( $comment, $dup_id, $original_cid ) {
		global $wpdb, $iclTranslationManagement;
		$dup_comment_id = $this->duplicate_exists ( $dup_id, $original_cid );
		remove_action ( 'wp_insert_comment', array( $iclTranslationManagement, 'duplication_insert_comment' ), 100 );

		if ( $dup_comment_id ) {
			$comment[ 'comment_ID' ] = $dup_comment_id;
			wp_update_comment ( $comment );
		} else {
			$wpdb->insert ( $wpdb->comments, $comment );
			$dup_comment_id = $wpdb->insert_id;

			add_action ( 'wp_insert_comment', array( $iclTranslationManagement, 'duplication_insert_comment' ), 100 );
			update_comment_meta ( $dup_comment_id, '_icl_duplicate_of', $original_cid );
			// comment meta
			$meta = $wpdb->get_results (
				$wpdb->prepare (
					"SELECT meta_key, meta_value FROM {$wpdb->commentmeta} WHERE comment_id=%d",
					$original_cid
				)
			);
			foreach ( $meta as $meta_row ) {
				$wpdb->insert (
					$wpdb->commentmeta,
					array(
						'comment_id' => $dup_comment_id,
						'meta_key'   => $meta_row->meta_key,
						'meta_value' => $meta_row->meta_value
					), array( '%d', '%s', '%s' )
				);
			}
		}

		wp_update_comment_count_now ( $dup_id );
	}

	private function duplicate_exists( $dup_id, $original_cid ) {
		global $wpdb;

		$duplicate = $wpdb->get_var (
			$wpdb->prepare (
				"	SELECT comm.comment_ID
					FROM {$wpdb->comments} comm
					JOIN {$wpdb->commentmeta} cm
						ON comm.comment_ID = cm.comment_id
					WHERE comm.comment_post_ID = %d
						AND cm.meta_key = '_icl_duplicate_of'
						AND cm.meta_value = %d
					LIMIT 1",
				$dup_id,
				$original_cid
			)
		);

		return $duplicate;
	}

}