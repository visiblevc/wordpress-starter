<?php

class WPML_Post_Comments extends WPML_WPDB_User {

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( &$wpdb ) {
		parent::__construct( $wpdb );
		$this->hooks();
	}

	private function hooks() {
		add_action( 'wpml_troubleshooting_after_setup_complete_cleanup_end', array( $this, 'troubleshooting_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wpml_count_orphans', array( $this, 'count_orphans_action' ) );
		add_action( 'wp_ajax_wpml_delete_orphans', array( $this, 'delete_orphans_action' ) );
	}

	public function count_orphans_action() {
		if ( wpml_is_action_authenticated( 'wpml_orphan_comment' ) ) {
			wp_send_json_success( $this->get_orphan_comments( true ) );
		} else {
			wp_send_json_error( 'Wrong Nonce' );
		}
	}

	public function get_orphan_comments( $return_count = false, $limit = 10 ) {
		if ( $return_count ) {
			$columns = 'count(c.comment_id)';
		} else {
			$columns = 'DISTINCT c.comment_id';
		}

		$sql = "
		SELECT {$columns}
		FROM {$this->wpdb->prefix}comments c
		  INNER JOIN {$this->wpdb->prefix}icl_translations tc
		    ON c.comment_id = tc.element_id
		  INNER JOIN {$this->wpdb->posts} p
		    ON c.comment_post_ID = p.ID
		  INNER JOIN {$this->wpdb->prefix}icl_translations tp
		    ON p.ID = tp.element_id
		    AND CONCAT('post_', p.post_type) = tp.element_type
		WHERE tc.element_type = 'comment'
		      AND tp.language_code <> tc.language_code
		LIMIT 0, %d
		";
		$sql_prepared = $this->wpdb->prepare( $sql, $limit );
		if ( $return_count ) {
			$results = $this->wpdb->get_var( $sql_prepared );
		} else {
			$comments = $this->wpdb->get_col( $sql_prepared );
			$num_rows = $this->wpdb->num_rows;
			$results  = array( $comments, $num_rows );
		}

		return $results;
	}

	public function delete_orphans_action() {
		if ( wpml_is_action_authenticated( 'wpml_orphan_comment' ) ) {
			$result   = false;
			$data     = isset( $_POST['data'] ) ? $_POST['data'] : array();
			$how_many = null;
			if ( isset( $data['how_many'] ) && is_numeric( $data['how_many'] ) ) {
				$how_many = (int) $data['how_many'];
			}
			if ( $how_many ) {
				$result = $this->delete_orphans( $how_many );
			}
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( 'Wrong Nonce' );
		}
	}

	/**
	 * @param $hook
	 */
	public function enqueue_scripts( $hook ) {
		wp_register_script( 'wpml-orphan-comments', ICL_PLUGIN_URL . '/res/js/orphan-comments.js', array( 'jquery' ), ICL_SITEPRESS_VERSION, true );
		if ( basename( ICL_PLUGIN_PATH ) . '/menu/troubleshooting.php' === $hook ) {
			wp_enqueue_script( 'wpml-orphan-comments' );
		}
	}

	public function troubleshooting_action() {
		?>
		<div id="wpml_orphans">
		<h4><?php esc_html_e( "Remove comments that don't match the content's language", 'sitepress' ); ?></h4>
			<div id="wpml_orphans_count" style="display:none;">
			<p>
				<?php esc_html_e( "This will check for comments that have a language different than the content they belong to. If found, we can delete these comments for you. We call these 'orphan comments'.", 'sitepress' ); ?>
			</p>
			<p>
				<button type="button" class="button-secondary check-orphans">
					<?php esc_html_e( 'Check for orphan comments', 'sitepress' ); ?>
				</button>
			</p>
				<div class="count-in-progress">
					<span class="spinner is-active" style="float:none;"></span>
					<?php esc_html_e( 'Checking...', 'sitepress' ); ?>
				</div>
				<div class="no_orphans">
					<br>
					<?php esc_html_e( 'Good news! Your site has no orphan comments.', 'sitepress' ); ?>
				</div>
				<div class="orphans-check-results">
					<p>
						<br>
						<?php echo sprintf( esc_html__( '%s orphan comments found.', 'sitepress' ), '<span class="count">0</span>' ); ?>
					</p>
					<p>
						<button type="button" class="button-secondary clean-orphans">
							<?php esc_html_e( 'Clean orphan comments', 'sitepress' ); ?>
						</button>
					</p>
					<p>
						<?php esc_html_e( '* The clean task may take several minutes to complete.', 'sitepress' ); ?>
					</p>
					<div class="delete-in-progress">
						<span class="spinner is-active" style="float:none;"></span>&nbsp;<?php esc_html_e( 'Deleted comments:', 'sitepress' ); ?>&nbsp;<span class="deleted">0</span>
					</div>
				</div>
				<?php wp_nonce_field( 'wpml_orphan_comment_nonce', 'wpml_orphan_comment_nonce' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * @param $how_many
	 *
	 * @return false|int
	 */
	public function delete_orphans( $how_many ) {
		$results = $this->get_orphan_comments( false, $how_many );
		$comment_ids      = $results[0];
		$deleted_comments = 0;
		if ( $comment_ids ) {
			$comment_ids_flat = implode( ',', $comment_ids );
			$post_ids = $this->get_post_ids_from_comments_ids( $comment_ids_flat );
			$deleted_comments += $this->wpdb->query( "DELETE FROM {$this->wpdb->comments} WHERE comment_ID IN( {$comment_ids_flat} )" );
			$this->wpdb->query( "DELETE FROM {$this->wpdb->commentmeta} WHERE comment_ID IN( {$comment_ids_flat} )" );

			$update_arg_set = array();
			foreach ( $comment_ids as $comment_id ) {
				$update_args = array(
					'element_id' => $comment_id,
					'element_type' => 'comment',
					'context' => 'comment',
				);

				$update_arg_set[] = $update_args;

				do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );
			}

			$this->wpdb->query( "DELETE FROM {$this->wpdb->prefix}icl_translations WHERE element_id IN( {$comment_ids_flat} ) AND element_type = 'comment'" );

			foreach ( $update_arg_set as $update_args ) {
				do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );
			}

			$this->update_comments_count( $post_ids );
		}

		return $deleted_comments;
	}

	/**
	 * @param $post_ids
	 */
	private function update_comments_count( $post_ids ) {
		foreach ( $post_ids as $post_id ) {
			wp_update_comment_count( $post_id );
		}
	}

	/**
	 * @param string|array|int $comment_ids
	 *
	 * @return mixed
	 */
	private function get_post_ids_from_comments_ids( $comment_ids ) {
		if ( is_numeric( $comment_ids ) ) {
			$comment_ids = array( $comment_ids );
		}
		if ( is_array( $comment_ids ) ) {
			$comment_ids = implode( ',', $comment_ids );
		}

		return $this->wpdb->get_col( "SELECT DISTINCT comment_post_ID FROM {$this->wpdb->comments} WHERE comment_ID IN( {$comment_ids} )" );
	}
}

global $wpdb;
new WPML_Post_Comments( $wpdb );
