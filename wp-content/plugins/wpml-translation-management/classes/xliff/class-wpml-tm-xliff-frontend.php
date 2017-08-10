<?php
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once WPML_TM_PATH . '/inc/wpml_zip.php';

/**
 * Class WPML_TM_Xliff_Frontend
 */
class WPML_TM_Xliff_Frontend extends WPML_TM_Xliff_Shared {

	private $success;
	private $attachments = array();
	/** @var  SitePress $this ->sitepress */
	private $sitepress;
	private $export_archive_name;
	private $late_init_priority = 9999;

	/**
	 * WPML_TM_Xliff_Frontend constructor.
	 *
	 * @param WPML_Translation_Job_Factory $job_factory
	 * @param SitePress                    $sitepress
	 */
	public function __construct( &$job_factory, &$sitepress ) {
		parent::__construct( $job_factory );
		$this->sitepress = &$sitepress;
	}

	/**
	 * @return array
	 */
	function get_available_xliff_versions() {

		return array(
			"10" => "1.0",
			"11" => "1.1",
			"12" => "1.2"
		);
	}

	/**
	 * @return int
	 */
	public function get_init_priority() {
		return isset( $_POST['xliff_upload'] )
		|| ( isset( $_GET['wpml_xliff_action'] ) && $_GET['wpml_xliff_action'] === 'download' )
		|| isset( $_POST['wpml_xliff_export_all_filtered'] )
			? $this->get_late_init_priority() : 10;
	}

	/**
	 * @return int
	 */
	public function get_late_init_priority() {
		return $this->late_init_priority;
	}

	/**
	 * @return bool
	 */
	function init() {
		$this->attachments = array();
		$this->error       = null;
		if ( $this->sitepress->get_wp_api()->is_admin() ) {
			add_action( 'admin_head', array( $this, 'js_scripts' ) );
			add_action( 'wp_ajax_set_xliff_options', array(
				$this,
				'ajax_set_xliff_options'
			), 10, 2 );
			if ( ! $this->sitepress->get_setting( 'xliff_newlines' ) ) {
				$this->sitepress->set_setting( 'xliff_newlines', WPML_XLIFF_TM_NEWLINES_REPLACE, true );
			}
			if ( ! $this->sitepress->get_setting( 'tm_xliff_version' ) ) {
				$this->sitepress->set_setting( 'tm_xliff_version', '12', true );
			}
			if ( 1 < count( $this->sitepress->get_active_languages() ) ) {
				add_filter( 'wpml_translation_queue_actions', array(
					$this,
					'translation_queue_add_actions'
				) );
				add_action( 'wpml_xliff_select_actions', array(
					$this,
					'translation_queue_xliff_select_actions'
				), 10, 2 );
				add_action( 'wpml_translation_queue_do_actions_export_xliff', array(
					$this,
					'translation_queue_do_actions_export_xliff'
				), 10, 2 );
				add_action( 'wpml_translation_queue_after_display', array(
					$this,
					'translation_queue_after_display'
				), 10, 2 );
				add_action( 'wpml_translator_notification', array(
					$this,
					'translator_notification'
				), 10, 0 );
				add_filter( 'wpml_new_job_notification', array(
					$this,
					'new_job_notification'
				), 10, 2 );
				add_filter( 'wpml_new_job_notification_attachments', array(
					$this,
					'new_job_notification_attachments'
				) );
			}
			if ( isset( $_GET['wpml_xliff_action'] )
			     && $_GET['wpml_xliff_action'] === 'download'
			     && wp_verify_nonce( $_GET['nonce'], 'xliff-export' )
			) {
				$archive = $this->get_xliff_archive( $_GET["xliff_version"] );
				$this->stream_xliff_archive( $archive );
			}
			if ( isset( $_POST['wpml_xliff_export_all_filtered'] )
			     && wp_verify_nonce( $_POST['nonce'], 'xliff-export-all-filtered' )
			) {
				$job_ids = $this->get_all_filtered_job_ids();
				$archive = $this->get_xliff_archive( $_POST["xliff_version"], $job_ids );
				$this->stream_xliff_archive( $archive );
			}
			if ( isset( $_POST['xliff_upload'] ) ) {
				$this->import_xliff( $_FILES['import'] );
				if ( is_wp_error( $this->error ) ) {
					add_action( 'admin_notices', array( $this, '_error' ) );
				}
			}
			if ( isset( $_POST['icl_tm_action'] ) && $_POST['icl_tm_action'] === 'save_notification_settings' ) {
				$this->sitepress->save_settings(
					array(
						'include_xliff_in_notification' => isset( $_POST['include_xliff'] )
						                                   && $_POST['include_xliff']
					) );
			}
		}

		return true;
	}

	function ajax_set_xliff_options() {
		check_ajax_referer( 'icl_xliff_options_form_nonce', 'security' );
		$newlines = (int) $_POST['icl_xliff_newlines'];
		$this->sitepress->set_setting( "xliff_newlines", $newlines, true );
		$version = (int) $_POST['icl_xliff_version'];
		$this->sitepress->set_setting( "tm_xliff_version", $version, true );

		wp_send_json_success( array(
			'message'        => 'OK',
			'newlines_saved' => $newlines,
			'version_saved'  => $version
		) );
	}

	/**
	 * @param array $mail
	 * @param int   $job_id
	 *
	 * @return array
	 */
	function new_job_notification( $mail, $job_id ) {
		if ( $this->sitepress->get_setting( 'include_xliff_in_notification' ) ) {
			$xliff_version = $this->get_user_xliff_version();
			$xliff_file    = $this->get_xliff_file( $job_id, $xliff_version );
			$temp_dir      = get_temp_dir();
			$file_name     = $temp_dir . get_bloginfo( 'name' ) . '-translation-job-' . $job_id . '.xliff';
			$fh            = fopen( $file_name, 'w' );
			if ( $fh ) {
				fwrite( $fh, $xliff_file );
				fclose( $fh );
				$mail['attachment']           = $file_name;
				$this->attachments[ $job_id ] = $file_name;
				$mail['body'] .= __( ' - A xliff file is attached.', 'wpml-translation-management' );
			}
		}

		return $mail;
	}

	/**
	 * @param $job_ids
	 *
	 * @return string
	 */
	private function _get_zip_name_from_jobs( $job_ids ) {
		$min_job = min( $job_ids );
		$max_job = max( $job_ids );
		if ( $max_job == $min_job ) {
			return get_bloginfo( 'name' ) . '-translation-job-' . $max_job . '.zip';
		} else {
			return get_bloginfo( 'name' ) . '-translation-job-' . $min_job . '-' . $max_job . '.zip';
		}
	}

	/**
	 * @param $attachments
	 *
	 * @return array
	 */
	function new_job_notification_attachments( $attachments ) {
		$found   = false;
		$archive = new wpml_zip();

		foreach ( $attachments as $index => $attachment ) {
			if ( in_array( $attachment, $this->attachments ) ) {
				$fh         = fopen( $attachment, 'r' );
				$xliff_file = fread( $fh, filesize( $attachment ) );
				fclose( $fh );
				$archive->addFile( $xliff_file, basename( $attachment ) );

				unset( $attachments[ $index ] );
				$found = true;
			}
		}

		if ( $found ) {
			// add the zip file to the attachments.
			$archive_data = $archive->getZipData();
			$temp_dir     = get_temp_dir();
			$file_name    = $temp_dir
			                . $this->_get_zip_name_from_jobs(
					array_keys( $this->attachments ) );
			$fh           = fopen( $file_name, 'w' );
			fwrite( $fh, $archive_data );
			fclose( $fh );
			$attachments[] = $file_name;
		}

		return $attachments;
	}

	/**
	 * @param int    $job_id
	 * @param string $xliff_version
	 *
	 * @return string
	 */
	private function get_xliff_file( $job_id, $xliff_version = WPML_XLIFF_DEFAULT_VERSION ) {
		$xliff = new WPML_TM_Xliff_Writer( $this->job_factory, $xliff_version );

		return $xliff->generate_job_xliff( $job_id );
	}

	/**
	 * @param string     $xliff_version
	 * @param array|null $job_ids
	 *
	 * @return wpml_zip
	 *
	 * @throws Exception
	 */
	function get_xliff_archive( $xliff_version, $job_ids = array() ) {
		global $wpdb, $current_user;

		if ( empty( $job_ids ) && isset( $_GET['xliff_export_data'] ) ) {
			$data = unserialize( base64_decode( $_GET['xliff_export_data'] ) );
			$job_ids = isset( $data['job'] ) ? array_keys( $data['job'] ) : array();
		}

		$archive = new wpml_zip();
		foreach ( $job_ids as $job_id ) {
			$xliff_file = $this->get_xliff_file( $job_id, $xliff_version );

			// assign the job to this translator
			$rid        = $wpdb->get_var( $wpdb->prepare( "SELECT rid
														  FROM {$wpdb->prefix}icl_translate_job
														  WHERE job_id=%d ", $job_id ) );
			$data_value = array( 'translator_id' => $current_user->ID );
			$data_where = array( 'job_id' => $job_id );
			$wpdb->update( $wpdb->prefix . 'icl_translate_job', $data_value, $data_where );
			$data_where = array( 'rid' => $rid );
			$wpdb->update( $wpdb->prefix . 'icl_translation_status', $data_value, $data_where );
			$archive->addFile( $xliff_file, get_bloginfo( 'name' ) . '-translation-job-' . $job_id . '.xliff' );
		}

		$this->export_archive_name = $this->_get_zip_name_from_jobs( $job_ids );
		$archive->finalize();
		return $archive;
	}

	/**
	 * @param wpml_zip $archive
	 */
	private function stream_xliff_archive( $archive ) {
		if ( is_a( $archive, 'wpml_zip' ) ) {
			$archive->sendZip( $this->export_archive_name );
		}
		exit;
	}

	/**
	 * @return array
	 */
	public function get_all_filtered_job_ids() {
		/**
		 * @var TranslationManagement $iclTranslationManagement
		 * @var WPML_Translation_Job_Factory $wpml_translation_job_factory
		 */
		global $iclTranslationManagement, $wpml_translation_job_factory;

		$job_ids            = array();
		$current_translator = $iclTranslationManagement->get_current_translator();
		$can_translate      = $current_translator && $current_translator->ID > 0 && $current_translator->language_pairs;

		if( $can_translate ) {

			if ( isset( $_SESSION['translation_ujobs_filter'] ) ) {
				$icl_translation_filter = $_SESSION['translation_ujobs_filter'];
			}

			$icl_translation_filter['translator_id']      = $current_translator->ID;
			$icl_translation_filter['include_unassigned'] = true;

			$translation_jobs = $wpml_translation_job_factory->get_translation_jobs( (array) $icl_translation_filter, true );
			$job_ids          = wp_list_pluck( $translation_jobs, 'job_id' );
		}

		return $job_ids;
	}

	/**
	 * Stops any redirects from happening when we call the
	 * translation manager to save the translations.
	 *
	 * @param $location
	 *
	 * @return null
	 */
	function _stop_redirect( $location ) {

		return null;
	}

	/**
	 * @param array $file
	 *
	 * @return bool|WP_Error
	 */
	private function import_xliff( $file ) {
		global $current_user;

		// We don't want any redirects happening when we save the translation
		add_filter( 'wp_redirect', array( $this, '_stop_redirect' ) );

		$this->success = array();
		$contents      = array();

		if ( isset( $file['tmp_name'] ) && $file['tmp_name'] ) {
			$fh   = fopen( $file['tmp_name'], 'r' );
			$data = fread( $fh, 4 );
			fclose( $fh );
			if ( $data[0] == 'P' && $data[1] == 'K' && $data[2] == chr( 03 ) && $data[3] == chr( 04 ) ) {
				if ( class_exists( 'ZipArchive' ) ) {
					$z     = new ZipArchive();
					$zopen = $z->open( $file['tmp_name'],
						4 );
					if ( true !== $zopen ) {
						return new WP_Error( 'incompatible_archive', __( 'Incompatible Archive.' ) );
					}
					for ( $i = 0; $i < $z->numFiles; $i ++ ) {
						if ( ! $info = $z->statIndex( $i ) ) {
							return new WP_Error( 'stat_failed', __( 'Could not retrieve file from archive.' ) );
						}
						$content = $z->getFromIndex( $i );
						if ( false === $content ) {
							return new WP_Error( 'extract_failed', __( 'Could not extract file from archive.' ), $info['name'] );
						}
						$contents[ $info['name'] ] = $content;
					}
				} else {
					require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
					$archive = new PclZip( $file['tmp_name'] );
					// Is the archive valid?
					if ( false == ( $archive_files = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING ) ) ) {
						return new WP_Error( 'incompatible_archive', __( 'Incompatible Archive.' ), $archive->errorInfo( true ) );
					}
					if ( 0 == count( $archive_files ) ) {
						return new WP_Error( 'empty_archive', __( 'Empty archive.' ) );
					}
					foreach ( $archive_files as $content ) {
						$contents[ $content['filename'] ] = $content['content'];
					}
				}
			} else {
				$fh   = fopen( $file['tmp_name'], 'r' );
				$data = fread( $fh, $file['size'] );
				fclose( $fh );
				$contents[ $file['name'] ] = $data;
			}

			foreach ( $contents as $name => $content ) {
				if( $this->validate_file_name( $name ) ) {
					list( $job, $job_data ) = $this->validate_file( $name, $content, $current_user );
					if ( null !== $this->error ) {
						return $job_data;
					}
					wpml_tm_save_data( $job_data );
					$this->success[] = sprintf( __( 'Translation of job %s has been uploaded and completed.', 'wpml-translation-management' ), $job->job_id );
				}
			}

			if ( count( $this->success ) ) {
				add_action( 'admin_notices', array( $this, '_success' ) );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param $actions
	 * @param $action_name
	 */
	function translation_queue_xliff_select_actions( $actions, $action_name ) {
		if ( sizeof( $actions ) > 0 ):
			$user_version = $this->get_user_xliff_version();
			?>
			<div class="alignleft actions">
				<select name="<?php echo $action_name; ?>">
					<option
						value="-1" <?php echo $user_version == false ? "selected='selected'" : ""; ?>><?php _e( 'Bulk Actions' ); ?></option>
					<?php foreach ( $actions as $key => $action ): ?>
						<option
							value="<?php echo $key; ?>" <?php echo $user_version == $key ? "selected='selected'" : ""; ?>><?php echo $action; ?></option>
					<?php endforeach; ?>
				</select>
				<input type="submit" value="<?php esc_attr_e( 'Apply' ); ?>"
				       name="do<?php echo $action_name; ?>"
				       class="button-secondary action"/>
			</div>
			<?php
		endif;
	}

	/**
	 * @return string
	 */
	private function get_xliff_version_select_options() {
		$output = '';
		$user_version = (int) $this->get_user_xliff_version();
		foreach ( $this->get_available_xliff_versions() as $value => $label ) {
			$user_version = $user_version === false ? $value : $user_version;
			$output .= '<option value="' . $value . '"';
			$output .= $user_version === $value ? 'selected="selected"' : '';
			$output .= '>XLIFF ' . $label . '</option>';
		}

		return $output;
	}

	/**
	 * Adds the various possible XLIFF versions to translations queue page's export actions on display.
	 *
	 * @param array $actions
	 *
	 * @return array
	 */
	function translation_queue_add_actions( $actions ) {
		foreach ( $this->get_available_xliff_versions() as $key => $value ) {
			$actions[ $key ] = __( sprintf( 'Export XLIFF %s', $value ), 'wpml-translation-management' );
		}

		return $actions;
	}

	/**
	 * @param array  $data
	 * @param string $xliff_version
	 */
	function translation_queue_do_actions_export_xliff( $data, $xliff_version ) {
		?>
		<script type="text/javascript">
			<?php
			if (isset( $data['job'] )) { ?>

			var xliff_export_data = "<?php echo base64_encode( serialize( $data ) ); ?>";
			var xliff_export_nonce = "<?php echo wp_create_nonce( 'xliff-export' ); ?>";
			var xliff_version = "<?php echo $xliff_version; ?>";
			addLoadEvent(function () {
				window.location = "<?php echo htmlentities( $_SERVER['REQUEST_URI'] ) ?>&wpml_xliff_action=download&xliff_export_data=" + xliff_export_data + "&nonce=" + xliff_export_nonce + "&xliff_version=" + xliff_version;
			});
			<?php
			} else {
			?>
			var error_message = "<?php echo __( 'No translation jobs were selected for export.', 'wpml-translation-management' ); ?>";
			alert(error_message);
			<?php
			}
			?>
		</script>
		<?php
	}

	function _error() {
		if ( is_wp_error( $this->error ) ) {
			?>
			<div class="message error">
				<p><?php echo $this->error->get_error_message() ?></p></div>
			<?php
		}
	}

	function _success() {
		?>
		<div class="message updated"><p>
			<ul>
				<?php
				foreach ( $this->success as $message ) {
					echo '<li>' . $message . '</li>';
				}
				?>
			</ul>
			</p></div>
		<?php
	}

	function translation_queue_after_display() {
		$export_label = esc_html__( 'Export all jobs:', 'wpml-translation-management' );

		if ( isset( $_SESSION['translation_ujobs_filter'] ) ) {

			if ( ! empty( $_SESSION['translation_ujobs_filter']['type'] ) ) {
				$post_slug = preg_replace( '/^post_/', '', $_SESSION['translation_ujobs_filter']['type'], 1 );
				$post_type = get_post_type_object( $post_slug );
				$type      = $post_type->label;
			} else {
				$type      = __( 'All types', 'wpml-translation-management' );
			}

			$from   = ! empty( $_SESSION['translation_ujobs_filter']['from'] )
				? $this->sitepress->get_display_language_name( $_SESSION['translation_ujobs_filter']['from'] )
				: __( 'Any language', 'wpml-translation-management' );
			$to     = ! empty( $_SESSION['translation_ujobs_filter']['to'] )
				? $this->sitepress->get_display_language_name( $_SESSION['translation_ujobs_filter']['to'] )
				: __( 'Any language', 'wpml-translation-management' );
			$status = ! empty( $_SESSION['translation_ujobs_filter']['status'] ) && (int) $_SESSION['translation_ujobs_filter']['status'] !== ICL_TM_WAITING_FOR_TRANSLATOR
				? TranslationManagement::status2text( $_SESSION['translation_ujobs_filter']['status'] )
				: ( ! empty( $_SESSION['translation_ujobs_filter']['status'] ) ? __('Available to translate', 'wpml-translation-management') : 'All statuses' );

			$export_label = sprintf(
				esc_html__( 'Export all filtered jobs of %1$s from %2$s to %3$s in %4$s:', 'wpml-translation-management' ),
				'<b>' . $type   . '</b>',
				'<b>' . $from   . '</b>',
				'<b>' . $to     . '</b>',
				'<b>' . $status . '</b>'
			);
		}
		?>

		<br />
		<table class="widefat">
			<thead><tr><th><?php esc_html_e( 'Import / Export XLIFF', 'wpml-translation-management' ); ?></th></tr></thead>
			<tbody><tr><td>
				<form method="post" id="translation-xliff-export-all-filtered" action="">
					<label for="wpml_xliff_export_all_filtered"><?php echo $export_label; ?></label>
					<select name="xliff_version" class="select"><?php echo $this->get_xliff_version_select_options(); ?></select>
					<input type="submit" value="<?php esc_attr_e( 'Export', 'wpml-translation-management' ); ?>" name="wpml_xliff_export_all_filtered" id="xliff_download" class="button-secondary action" />
					<input type="hidden" value="<?php echo wp_create_nonce( 'xliff-export-all-filtered' ); ?>" name="nonce">
				</form>
				<hr>
				<form enctype="multipart/form-data" method="post" id="translation-xliff-upload" action="">
					<label for="upload-xliff-file"><?php _e( 'Select the xliff file or zip file to upload from your computer:&nbsp;', 'wpml-translation-management' ); ?></label>
					<input type="file" id="upload-xliff-file" name="import" /><input type="submit" value="<?php _e( 'Upload', 'wpml-translation-management' ); ?>" name="xliff_upload" id="xliff_upload" class="button-secondary action" />
				</form>
			</td></tr></tbody>
		</table>
		<?php
	}

	public function js_scripts() {
		?>
		<script type="text/javascript">
			var wpml_xliff_ajax_nonce = '<?php echo wp_create_nonce( "icl_xliff_options_form_nonce" ); ?>';
		</script>
		<?php
	}

	function translator_notification() {
		$checked = $this->sitepress->get_setting( 'include_xliff_in_notification' ) ? 'checked="checked"' : '';
		?>
		<input type="checkbox" name="include_xliff" id="icl_include_xliff"
		       value="1" <?php echo $checked; ?>/>
		<label
			for="icl_include_xliff"><?php _e( 'Include XLIFF files in notification emails', 'wpml-translation-management' ); ?></label>
		<?php
	}

	/**
	 * @return bool|string
	 */
	private function get_user_xliff_version() {

		return $this->sitepress->get_setting( "tm_xliff_version", false );
	}
}
