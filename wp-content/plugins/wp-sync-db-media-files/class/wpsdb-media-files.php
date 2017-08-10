<?php
class WPSDB_Media_Files extends WPSDB_Addon {
	protected $files_to_migrate;
	protected $responding_to_get_remote_media_listing = false;

	function __construct( $plugin_file_path ) {
		parent::__construct( $plugin_file_path );

		$this->plugin_slug = 'wp-sync-db-media-files';
		$this->plugin_version = $GLOBALS['wpsdb_meta']['wp-sync-db-media-files']['version'];

		if( ! $this->meets_version_requirements( '1.4b1' ) ) return;

		add_action( 'wpsdb_after_advanced_options', array( $this, 'migration_form_controls' ) );
		add_action( 'wpsdb_load_assets', array( $this, 'load_assets' ) );
		add_action( 'wpsdb_js_variables', array( $this, 'js_variables' ) );
		add_filter( 'wpsdb_accepted_profile_fields', array( $this, 'accepted_profile_fields' ) );
		add_filter( 'wpsdb_establish_remote_connection_data', array( $this, 'establish_remote_connection_data' ) );
		add_filter( 'wpsdb_nonces', array( $this, 'add_nonces' ) );

		// compatibility with CLI migraitons
		add_filter( 'wpsdb_cli_finalize_migration', array( $this, 'cli_migration' ), 10, 4 );

		// internal AJAX handlers
		add_action( 'wp_ajax_wpsdbmf_determine_media_to_migrate', array( $this, 'ajax_determine_media_to_migrate' ) );
		add_action( 'wp_ajax_wpsdbmf_migrate_media', array( $this, 'ajax_migrate_media' ) );

		// external AJAX handlers
		add_action( 'wp_ajax_nopriv_wpsdbmf_get_remote_media_listing', array( $this, 'respond_to_get_remote_media_listing' ) );
		add_action( 'wp_ajax_nopriv_wpsdbmf_push_request', array( $this, 'respond_to_push_request' ) );
		add_action( 'wp_ajax_nopriv_wpsdbmf_remove_local_attachments', array( $this, 'respond_to_remove_local_attachments' ) );
	}

	function get_local_attachments() {
		global $wpdb;
		$prefix = $wpdb->prefix;
		$temp_prefix = stripslashes( $_POST['temp_prefix'] );

		/*
		* We determine which media files need migrating BEFORE the database migration is finalized.
		* Because of this we need to scan the *_post & *_postmeta that are prefixed using the temporary prefix.
		* Though this should only happen when we're responding to a get_remote_media_listing() call AND it's a push OR
		* we're scanning local files AND it's a pull.
		*/

		if(
			( true == $this->responding_to_get_remote_media_listing && $_POST['intent'] == 'push' ) ||
			( false == $this->responding_to_get_remote_media_listing && $_POST['intent'] == 'pull' )
		) {

			$local_tables = array_flip( $this->get_tables() );

			$posts_table_name = "{$temp_prefix}{$prefix}posts";
			$postmeta_table_name = "{$temp_prefix}{$prefix}postmeta";

			if( isset( $local_tables[$posts_table_name] ) && isset( $local_tables[$postmeta_table_name] ) ) {
				$prefix = $temp_prefix . $prefix;
			}

		}

		$local_attachments = $wpdb->get_results(
			"SELECT `{$prefix}posts`.`post_modified_gmt` AS 'date', pm1.`meta_value` AS 'file', pm2.`meta_value` AS 'metadata'
			FROM `{$prefix}posts`
			INNER JOIN `{$prefix}postmeta` pm1 ON `{$prefix}posts`.`ID` = pm1.`post_id` AND pm1.`meta_key` = '_wp_attached_file'
			LEFT OUTER JOIN `{$prefix}postmeta` pm2 ON `{$prefix}posts`.`ID` = pm2.`post_id` AND pm2.`meta_key` = '_wp_attachment_metadata'
			WHERE `{$prefix}posts`.`post_type` = 'attachment'", ARRAY_A
		);

		if( is_multisite() ) {
			$blogs = $this->get_blogs();
			$prefix = $wpdb->prefix;
			foreach( $blogs as $blog ) {
				$posts_table_name = "{$temp_prefix}{$prefix}{$blog}_posts";
				$postmeta_table_name = "{$temp_prefix}{$prefix}{$blog}_postmeta";
				if( isset( $local_tables[$posts_table_name] ) && isset( $local_tables[$postmeta_table_name] ) ) {
					$prefix = $temp_prefix . $prefix;
				}
				$attachments = $wpdb->get_results(
					"SELECT `{$prefix}{$blog}_posts`.`post_modified_gmt` AS 'date', pm1.`meta_value` AS 'file', pm2.`meta_value` AS 'metadata', {$blog} AS 'blog_id'
					FROM `{$prefix}{$blog}_posts`
					INNER JOIN `{$prefix}{$blog}_postmeta` pm1 ON `{$prefix}{$blog}_posts`.`ID` = pm1.`post_id` AND pm1.`meta_key` = '_wp_attached_file'
					LEFT OUTER JOIN `{$prefix}{$blog}_postmeta` pm2 ON `{$prefix}{$blog}_posts`.`ID` = pm2.`post_id` AND pm2.`meta_key` = '_wp_attachment_metadata'
					WHERE `{$prefix}{$blog}_posts`.`post_type` = 'attachment'", ARRAY_A
				);

				$local_attachments = array_merge( $attachments, $local_attachments );
			}
		}

		$local_attachments = array_map( array( $this, 'process_attachment_data' ), $local_attachments );
		$local_attachments = array_filter( $local_attachments );

		return $local_attachments;
	}

	function get_flat_attachments( $attachments ) {
		$flat_attachments = array();
		foreach( $attachments as $attachment ) {
			$flat_attachments[] = $attachment['file'];
			if( isset( $attachment['sizes'] ) ) {
				$flat_attachments = array_merge( $flat_attachments, $attachment['sizes'] );
			}
		}
		return $flat_attachments;
	}

	function process_attachment_data( $attachment ) {
		if ( isset( $attachment['blog_id'] ) ) { // used for multisite
			if ( defined( 'UPLOADBLOGSDIR' ) ) {
				$upload_dir = sprintf( '%s/files/', $attachment['blog_id'] );
			} else {
				$upload_dir = sprintf( 'sites/%s/', $attachment['blog_id'] );
			}
			$attachment['file'] = $upload_dir . $attachment['file'];
		}
		$upload_dir = str_replace( basename( $attachment['file'] ), '', $attachment['file'] );
		if ( ! empty( $attachment['metadata'] ) ) {
			$attachment['metadata'] = @unserialize( $attachment['metadata'] );
			if ( ! empty( $attachment['metadata']['sizes'] ) && is_array( $attachment['metadata']['sizes'] ) ) {
				foreach ( $attachment['metadata']['sizes'] as $size ) {
					if ( empty( $size['file'] ) ) continue;
					$attachment['sizes'][] = $upload_dir . $size['file'];
				}
			}
		}
		unset( $attachment['metadata'] );
		return $attachment;
	}

	function uploads_dir() {
		if( defined( 'UPLOADBLOGSDIR' ) ) {
			$upload_dir = trailingslashit( ABSPATH ) . UPLOADBLOGSDIR;
		}
		else {
			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['basedir'];
		}
		return trailingslashit( $upload_dir );
	}

	function get_local_media() {
		$upload_dir = untrailingslashit( $this->uploads_dir() );
		if( ! file_exists( $upload_dir ) ) return array();

		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $upload_dir ), RecursiveIteratorIterator::SELF_FIRST );
		$local_media = array();

		foreach( $files as $name => $object ){
			$name = str_replace( array( $upload_dir . DS, '\\' ), array( '', '/' ), $name );
			$local_media[$name] = $object->getSize();
		}

		return $local_media;
	}

	function ajax_migrate_media() {
		$this->check_ajax_referer( 'migrate-media' );
		$this->set_time_limit();

		if ( $_POST['intent'] == 'pull' ) {
			$result = $this->process_pull_request();
			return $result;
		}

		$result = $this->process_push_request();
		return $result;
	}

	function process_pull_request() {
		$files_to_download = $_POST['file_chunk'];
		$remote_uploads_url = trailingslashit( $_POST['remote_uploads_url'] );
		$parsed = parse_url( $_POST['url'] );
		if( ! empty( $parsed['user'] ) ) {
			$credentials = sprintf( '%s:%s@', $parsed['user'], $parsed['pass'] );
			$remote_uploads_url = str_replace( '://', '://' . $credentials, $remote_uploads_url );
		}

		$upload_dir = $this->uploads_dir();

		$errors = array();
		foreach( $files_to_download as $file_to_download ) {
			$temp_file_path = $this->download_url( $remote_uploads_url . $file_to_download );

			if( is_wp_error( $temp_file_path ) ) {
				$download_error = $temp_file_path->get_error_message();
				$errors[] = __( sprintf( 'Could not download file: %1$s - %2$s', $remote_uploads_url . $file_to_download, $download_error ), 'wp-sync-db-media-files' );
				continue;
			}

			$date = str_replace( basename( $file_to_download ), '', $file_to_download );
			$new_path = $upload_dir . $date . basename( $file_to_download  );

			$move_result = @rename( $temp_file_path, $new_path );

			if( false === $move_result ) {
				$folder = dirname( $new_path );
				if( @file_exists( $folder ) ) {
					$errors[] =  __( sprintf( 'Error attempting to move downloaded file. Temp path: %1$s - New Path: %2$s', $temp_file_path, $new_path ), 'wp-sync-db-media-files' ) . ' (#103mf)';
				}
				else{
					if( false === @mkdir( $folder, 0755, true ) ) {
						$errors[] =  __( sprintf( 'Error attempting to create required directory: %s', $folder ), 'wp-sync-db-media-files' ) . ' (#104mf)';
					}
					else {
						$move_result = @rename( $temp_file_path, $new_path );
						if( false === $move_result ) {
							$errors[] =  __( sprintf( 'Error attempting to move downloaded file. Temp path: %1$s - New Path: %2$s', $temp_file_path, $new_path ), 'wp-sync-db-media-files' ) . ' (#105mf)';
						}
					}
				}
			}
		}

		if( ! empty( $errors ) ) {
			$return = array(
				'wpsdb_error'	=> 1,
				'body'			=> implode( '<br />', $errors ) . '<br />'
			);
			$result = $this->end_ajax( json_encode( $return ) );
			return $result;
		}

		// not required, just here because we have to return something otherwise the AJAX fails
		$return['success'] = 1;
		$result = $this->end_ajax( json_encode( $return ) );
		return $result;
	}

	function process_push_request() {
		$files_to_migrate = $_POST['file_chunk'];

		$upload_dir = $this->uploads_dir();

		$body = '';
		foreach( $files_to_migrate as $file_to_migrate ) {
			$body .= $this->file_to_multipart( $upload_dir . $file_to_migrate );
		}

		$post_args = array(
			'action'	=> 'wpsdbmf_push_request',
			'files'		=> serialize( $files_to_migrate )
		);

		$post_args['sig'] = $this->create_signature( $post_args, $_POST['key'] );

		$body .= $this->array_to_multipart( $post_args );

		$args['body'] = $body;
		$ajax_url = trailingslashit( $_POST['url'] ) . 'wp-admin/admin-ajax.php';
		$response = $this->remote_post( $ajax_url, '', __FUNCTION__, $args );
		$response = $this->verify_remote_post_response( $response );

		$result = $this->end_ajax( json_encode( $response ) );
		return $result;
	}

	function respond_to_push_request() {
		$filtered_post = $this->filter_post_elements( $_POST, array( 'action', 'files' ) );
		$filtered_post['files'] = stripslashes( $filtered_post['files'] );
		if ( ! $this->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpsdb_error' 	=> 1,
				'body'			=> $this->invalid_content_verification_error . ' (#101mf)',
			);
			$result = $this->end_ajax( serialize( $return ) );
			return $result;
		}

		if( ! isset( $_FILES['media'] ) ) {
			$return = array(
				'wpsdb_error' 	=> 1,
				'body'			=> __( '$_FILES is empty, the upload appears to have failed', 'wp-sync-db-media-files' ) . ' (#106mf)',
			);
			$result = $this->end_ajax( serialize( $return ) );
			return $result;
		}

		$upload_dir = $this->uploads_dir();

		$files = $this->diverse_array( $_FILES['media'] );
		$file_paths = unserialize( $filtered_post['files'] );
		$i = 0;
		$errors = array();
		foreach( $files as &$file ) {
			$destination = $upload_dir . $file_paths[$i];
			$folder = dirname( $destination );

			if( false === @file_exists( $folder ) && false === @mkdir( $folder, 0755, true ) ) {
				$errors[] = __( sprintf( 'Error attempting to create required directory: %s', $folder ), 'wp-sync-db-media-files' ) . ' (#108mf)';
				++$i;
				continue;
			}

			if( false === @move_uploaded_file( $file['tmp_name'], $destination ) ) {
				$errors[] = __( sprintf( 'A problem occurred when attempting to move the temp file "%1$s" to "%2$s"', $file['tmp_name'], $destination ), 'wp-sync-db-media-files' ) . ' (#107mf)';
			}
			++$i;
		}

		$return = array( 'success' => 1 );
		if( ! empty( $errors ) ) {
			$return = array(
				'wpsdb_error' 	=> 1,
				'body'			=> implode( '<br />', $errors ) . '<br />'
			);
		}
		$result = $this->end_ajax( serialize( $return ) );
		return $result;
	}

	function ajax_determine_media_to_migrate() {
		$this->check_ajax_referer( 'determine-media-to-migrate' );
		$this->set_time_limit();

		$local_attachments = $this->get_local_attachments();
		$local_media = $this->get_local_media();

		$data = array();
		$data['action'] = 'wpsdbmf_get_remote_media_listing';
		$data['temp_prefix'] = $this->temp_prefix;
		$data['intent'] = $_POST['intent'];
		$data['sig'] = $this->create_signature( $data, $_POST['key'] );
		$ajax_url = trailingslashit( $_POST['url'] ) . 'wp-admin/admin-ajax.php';
		$response = $this->remote_post( $ajax_url, $data, __FUNCTION__ );
		$response = $this->verify_remote_post_response( $response );

		$upload_dir = $this->uploads_dir();

		$remote_attachments = $response['remote_attachments'];
		$remote_media = $response['remote_media'];

		$this->files_to_migrate = array();

		if( $_POST['intent'] == 'pull' ) {
			$this->media_diff( $local_attachments, $remote_attachments, $local_media, $remote_media );
		}
		else {
			$this->media_diff( $remote_attachments, $local_attachments, $remote_media, $local_media );
		}

		$return['files_to_migrate'] = $this->files_to_migrate;
		$return['total_size'] = array_sum( $this->files_to_migrate );
		$return['remote_uploads_url'] = $response['remote_uploads_url'];

		// remove local/remote media if it doesn't exist on the local/remote site
		if( $_POST['remove_local_media'] == '1' ) {
			if( $_POST['intent'] == 'pull' ) {
				$this->remove_local_attachments( $remote_attachments );
			}
			else {
				$data = array();
				$data['action'] = 'wpsdbmf_remove_local_attachments';
				$data['remote_attachments'] = serialize( $local_attachments );
				$data['sig'] = $this->create_signature( $data, $_POST['key'] );
				$ajax_url = trailingslashit( $_POST['url'] ) . 'wp-admin/admin-ajax.php';
				$response = $this->remote_post( $ajax_url, $data, __FUNCTION__ );
				// the response is ignored here (for now) as this is not a critical task
			}
		}

		$result = $this->end_ajax( json_encode( $return ) );
		return $result;
	}

	function respond_to_remove_local_attachments() {
		$filtered_post = $this->filter_post_elements( $_POST, array( 'action', 'remote_attachments' ) );
		$filtered_post['remote_attachments'] = stripslashes( $filtered_post['remote_attachments'] );
		if ( ! $this->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpsdb_error' 	=> 1,
				'body'			=> $this->invalid_content_verification_error . ' (#109mf)',
			);
			$result = $this->end_ajax( serialize( $return ) );
			return $result;
		}

		$remote_attachments = @unserialize( $filtered_post['remote_attachments'] );
		if( false === $remote_attachments ) {
			$return = array(
				'wpsdb_error' 	=> 1,
				'body'			=> __( 'Error attempting to unserialize the remote attachment data', 'wp-sync-db-media-files' ) . ' (#110mf)',
			);
			$result = $this->end_ajax( serialize( $return ) );
			return $result;
		}

		$this->remove_local_attachments( $remote_attachments );

		$return = array(
			'success' 	=> 1,
		);
		$result = serialize( json_encode( $return ) );
		return $result;
	}

	function remove_local_attachments( $remote_attachments ) {
		$flat_remote_attachments = array_flip( $this->get_flat_attachments( $remote_attachments ) );
		$local_media = $this->get_local_media();
		// remove local media if it doesn't exist on the remote site
		$temp_local_media = array_keys( $local_media );
		$allowed_mime_types = array_flip( get_allowed_mime_types() );
		$upload_dir = $this->uploads_dir();
		foreach( $temp_local_media as $local_media_file ) {
			// don't remove folders
			if( false === is_file( $upload_dir . $local_media_file ) ) continue;
			$filetype = wp_check_filetype( $local_media_file );
			// don't remove files that we shouldn't remove, e.g. .php, .sql, etc
			if( false === isset( $allowed_mime_types[$filetype['type']] ) ) continue;
			// don't remove files that exist on the remote site
			if( true === isset( $flat_remote_attachments[$local_media_file] ) ) continue;

			@unlink( $upload_dir . $local_media_file );
		}
	}

	function media_diff( $site_a_attachments, $site_b_attachments, $site_a_media, $site_b_media ) {
		foreach( $site_b_attachments as $attachment ) {
			$local_attachment_key = $this->multidimensional_search( array( 'file' => $attachment['file'] ), $site_a_attachments );
			if( false === $local_attachment_key ) continue;
			$remote_timestamp = strtotime( $attachment['date'] );
			$local_timestamp = strtotime( $site_a_attachments[$local_attachment_key]['date'] );
			if( $local_timestamp >= $remote_timestamp ) {
				if( ! isset( $site_a_media[$attachment['file']] ) ) {
					$this->add_files_to_migrate( $attachment, $site_b_media );
				}
				else {
					$this->maybe_add_resized_images( $attachment, $site_b_media, $site_a_media );
				}
			}
			else {
				$this->add_files_to_migrate( $attachment, $site_b_media );
			}
		}
	}

	function add_files_to_migrate( $attachment, $remote_media ) {
		if( isset( $remote_media[$attachment['file']] ) ) {
			$this->files_to_migrate[$attachment['file']] = $remote_media[$attachment['file']];
		}
		if( empty( $attachment['sizes'] ) || apply_filters( 'wpsdb_exclude_resized_media', false ) ) return;
		foreach( $attachment['sizes'] as $size ) {
			if( isset( $remote_media[$size] ) ) {
				$this->files_to_migrate[$size] = $remote_media[$size];
			}
		}
	}

	function maybe_add_resized_images( $attachment, $site_b_media, $site_a_media ) {
		if( empty( $attachment['sizes'] ) || apply_filters( 'wpsdb_exclude_resized_media', false ) ) return;
		foreach( $attachment['sizes'] as $size ) {
			if( isset( $site_b_media[$size] ) && ! isset( $site_a_media[$size] ) ) {
				$this->files_to_migrate[$size] = $site_b_media[$size];
			}
		}
	}

	function respond_to_get_remote_media_listing() {
		$filtered_post = $this->filter_post_elements( $_POST, array( 'action', 'temp_prefix', 'intent' ) );
		if ( ! $this->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpsdb_error' 	=> 1,
				'body'			=> $this->invalid_content_verification_error . ' (#100mf)',
			);
			$result = $this->end_ajax( serialize( $return ) );
			return $result;
		}

		if( defined( 'UPLOADBLOGSDIR' ) ) {
			$upload_url = home_url( UPLOADBLOGSDIR );
		}
		else {
			$upload_dir = wp_upload_dir();
			$upload_url = $upload_dir['baseurl'];
		}

		$this->responding_to_get_remote_media_listing = true;

		$return['remote_attachments'] = $this->get_local_attachments();
		$return['remote_media'] = $this->get_local_media();
		$return['remote_uploads_url'] = $upload_url;

		$result = $this->end_ajax( serialize( $return ) );
		return $result;
	}

	function migration_form_controls() {
		$this->template( 'migrate' );
	}

	function accepted_profile_fields( $profile_fields ) {
		$profile_fields[] = 'media_files';
		$profile_fields[] = 'remove_local_media';
		return $profile_fields;
	}

	function load_assets() {
		$plugins_url = trailingslashit( plugins_url() ) . trailingslashit( $this->plugin_folder_name );
		$src = $plugins_url . 'asset/js/script.js';
		$version = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? time() : $this->plugin_version;
		wp_enqueue_script( 'wp-sync-db-media-files-script', $src, array( 'jquery', 'wp-sync-db-common', 'wp-sync-db-hook', 'wp-sync-db-script' ), $version, true );

		wp_localize_script( 'wp-sync-db-media-files-script', 'wpsdbmf_strings', array(
			'determining'				=> __( "Determining which media files to migrate, please wait...", 'wp-sync-db-media-files' ),
			'error_determining'			=> __( "Error while attempting to determine which media files to migrate.", 'wp-sync-db-media-files' ),
			'migration_failed'			=> __( "Migration failed", 'wp-sync-db-media-files' ),
			'problem_migrating_media'	=> __( "A problem occurred when migrating the media files.", 'wp-sync-db-media-files' ),
			'media_files'				=> __( "Media Files", 'wp-sync-db-media-files' ),
			'migrating_media_files'		=> __( "Migrating media files", 'wp-sync-db-media-files' ),
		) );

	}

	function establish_remote_connection_data( $data ) {
		$data['media_files_available'] = '1';
		$data['media_files_version'] = $this->plugin_version;
		if( function_exists( 'ini_get' ) ) {
			$max_file_uploads = ini_get( 'max_file_uploads' );
		}
		$max_file_uploads = ( empty( $max_file_uploads ) ) ? 20 : $max_file_uploads;
		$data['media_files_max_file_uploads'] = apply_filters( 'wpsdbmf_max_file_uploads', $max_file_uploads );
		return $data;
	}

	function multidimensional_search( $needle, $haystack ) {
		if( empty( $needle ) || empty( $haystack ) ) return false;

		foreach( $haystack as $key => $value ) {
			foreach ( $needle as $skey => $svalue ) {
				$exists = ( isset( $haystack[$key][$skey] ) && $haystack[$key][$skey] === $svalue );
			}
			if( $exists ) return $key;
		}

		return false;
	}

	function get_blogs() {
		global $wpdb;

		$blogs = $wpdb->get_results(
			"SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE spam = '0'
			AND deleted = '0'
			AND archived = '0'
			AND blog_id != 1
		");

		$clean_blogs = array();
		foreach( $blogs as $blog ) {
			$clean_blogs[] = $blog->blog_id;
		}

		return $clean_blogs;
	}

	function download_url( $url, $timeout = 300 ) {
		//WARNING: The file is not automatically deleted, The script must unlink() the file.
		if ( ! $url )
			return new WP_Error('http_no_url', __('Invalid URL Provided.'));

		$tmpfname = wp_tempnam($url);
		if ( ! $tmpfname )
			return new WP_Error('http_no_file', __('Could not create Temporary file.'));

		$response = wp_remote_get( $url, array( 'timeout' => $timeout, 'stream' => true, 'filename' => $tmpfname, 'reject_unsafe_urls' => false ) );

		if ( is_wp_error( $response ) ) {
			unlink( $tmpfname );
			return $response;
		}

		if ( 200 != wp_remote_retrieve_response_code( $response ) ){
			unlink( $tmpfname );
			return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
		}

		return $tmpfname;
	}

	function js_variables() {
		?>
		var wpsdb_media_files_version = '<?php echo $this->plugin_version; ?>';
		<?php
	}

	function verify_remote_post_response( $response ) {
		if ( false === $response ) {
			$return = array( 'wpsdb_error' => 1, 'body' => $this->error );
			$result = $this->end_ajax( json_encode( $return ) );
			return $result;
		}

		if ( ! is_serialized( trim( $response ) ) ) {
			$return = array( 'wpsdb_error'	=> 1, 'body' => $response );
			$result = $this->end_ajax( json_encode( $return ) );
			return $result;
		}

		$response = unserialize( trim( $response ) );

		if ( isset( $response['wpsdb_error'] ) ) {
			$result = $this->end_ajax( json_encode( $response ) );
			return $result;
		}
		return $response;
	}

	function add_nonces( $nonces ) {
		$nonces['migrate_media'] = wp_create_nonce( 'migrate-media' );
		$nonces['determine_media_to_migrate'] = wp_create_nonce( 'determine-media-to-migrate' );
		return $nonces;
	}

	function cli_migration( $outcome, $profile, $verify_connection_response, $initiate_migration_response ) {
		global $wpsdb, $wpsdb_cli;
		if ( true !== $outcome ) return $outcome;
		if ( !isset( $profile['media_files'] ) || '1' !== $profile['media_files'] ) return $outcome;

		if ( !isset( $verify_connection_response['media_files_max_file_uploads'] ) ) {
			return $wpsdb_cli->cli_error( __( 'WP Sync DB Media Files does not seems to be installed/active on the remote website.', 'wp-sync-db-media-files' ) );
		}

		$this->set_time_limit();
		$wpsdb->set_cli_migration();
		$this->set_cli_migration();

		$connection_info = explode( "\n", $profile['connection_info'] );

		$_POST['intent'] = $profile['action'];
		$_POST['url'] = trim( $connection_info[0] );
		$_POST['key'] = trim( $connection_info[1] );
		$_POST['remove_local_media'] = ( isset( $profile['remove_local_media'] ) ) ? 1 : 0;
		$_POST['temp_prefix'] = $verify_connection_response['temp_prefix'];

		do_action( 'wpsdb_cli_before_determine_media_to_migrate', $profile, $verify_connection_response, $initiate_migration_response );

		$response = $this->ajax_determine_media_to_migrate();
		if( is_wp_error( $determine_media_to_migrate_response = $wpsdb_cli->verify_cli_response( $response, 'ajax_determine_media_to_migrate()' ) ) ) return $determine_media_to_migrate_response;

		$remote_uploads_url = $determine_media_to_migrate_response['remote_uploads_url'];
		$files_to_migrate = $determine_media_to_migrate_response['files_to_migrate'];
		// seems like this value needs to be different depending on pull/push?
		$bottleneck = $wpsdb->get_bottleneck();

		while ( !empty( $files_to_migrate ) ) {
			$file_chunk_to_migrate = array();
			$file_chunk_size = 0;
			$number_of_files_to_migrate = 0;
			foreach ( $files_to_migrate as $file_to_migrate => $file_size ) {
				if ( empty( $file_chunk_to_migrate ) ) {
					$file_chunk_to_migrate[] = $file_to_migrate;
					$file_chunk_size += $file_size;
					unset( $files_to_migrate[$file_to_migrate] );
					++$number_of_files_to_migrate;
				} else {
					if ( ( $file_chunk_size + $file_size ) > $bottleneck || $number_of_files_to_migrate >= $verify_connection_response['media_files_max_file_uploads'] ) {
						break;
					} else {
						$file_chunk_to_migrate[] = $file_to_migrate;
						$file_chunk_size += $file_size;
						unset( $files_to_migrate[$file_to_migrate] );
						++$number_of_files_to_migrate;
					}
				}

				$_POST['file_chunk'] = $file_chunk_to_migrate;
				$_POST['remote_uploads_url'] = $remote_uploads_url;

				$response = $this->ajax_migrate_media();
				if( is_wp_error( $migrate_media_response = $wpsdb_cli->verify_cli_response( $response, 'ajax_migrate_media()' ) ) ) return $migrate_media_response;
			}
		}
		return true;
	}
}
