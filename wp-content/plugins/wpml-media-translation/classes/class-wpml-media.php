<?php

/**
 * Class WPML_Media
 */
class WPML_Media
{
	private static $settings;
	private static $settings_option_key = '_wpml_media';
	public $languages;
	public $parents;
	public $unattached;
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	function __construct( $ext = false, &$sitepress, &$wpdb ) {
		$this->sitepress = &$sitepress;
		$this->wpdb      = &$wpdb;
		add_action( 'wpml_loaded', array( $this, 'loaded' ), 2 );
		add_action( 'init', array($this, 'verify_wpml') );
		
	}

	function verify_wpml() {
		if ( is_admin() ) {
			$dependencies = new WPML_Media_Dependencies();
			$dependencies->check();
		}
	}
	
	public static function has_settings()
	{
		return get_option( self::$settings_option_key );
	}

	function loaded()
	{
		$this->plugin_localization();

		if ( is_admin() ) {
			WPML_Media_Upgrade::run();
		}

		self::init_settings();

		$this->overrides();

		global $wpdb, $sitepress, $sitepress_settings, $pagenow;

		if(!isset($sitepress)) return null;

		$active_languages = $sitepress->get_active_languages();
		if ( !self::get_setting( 'starting_help' ) && ( empty( $_GET[ 'page' ] ) || $_GET[ 'page' ] != 'wpml-media' ) ) {

			$total_attachments_prepared = $wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND ID NOT IN
                (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s)", array('attachment', 'wpml_media_processed'));
			$total_attachments = $wpdb->get_var( $total_attachments_prepared );

			if ( $total_attachments ) {
				if ( count( $active_languages ) > 1 ) {
					add_action( 'admin_notices', array( $this, 'first_time_notice' ) );
				}
			} else {
				self::update_setting( 'starting_help', 1 );
			}

		}

		$this->languages = null;

		add_action( 'save_post', array( $this, 'save_post_actions' ), 100, 2 );
		add_action( 'added_post_meta', array( $this, 'added_post_meta' ), 10, 4 );
		add_action( 'updated_postmeta', array( $this, 'updated_postmeta' ), 10, 4 );
		// do not run this when user is importing posts in Tools > Import
		if ( ! isset( $_GET[ 'import' ] ) || $_GET[ 'import' ] !== 'wordpress' ) {
			add_action( 'add_attachment', array( $this, 'save_attachment_actions' ) );
		}
		add_action( 'add_attachment', array( $this, 'save_translated_attachments' ) );
		// Attachment metadata hooks
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_generate_attachment_metadata' ), 10, 2 );

		if ( $this->is_admin_or_xmlrpc() && !$this->is_uploading_plugin_or_theme() ) {

			add_action( 'admin_head', array( $this, 'js_scripts' ) );

			if ( 1 < count( $active_languages ) ) {

				add_action( 'admin_menu', array( $this, 'menu' ) );
				add_filter( 'manage_media_columns', array( $this, 'manage_media_columns' ), 10, 1 );
				add_action( 'manage_media_custom_column', array( $this, 'manage_media_custom_column' ), 10, 2 );
				add_action( 'parse_query', array( $this, 'parse_query' ) );
				add_filter( 'views_upload', array( $this, 'views_upload' ) );
				add_action( 'icl_post_languages_options_after', array( $this, 'language_options' ) );

				// Post/page save actions

				add_action( 'icl_make_duplicate', array( $this, 'make_duplicate' ), 10, 4 );

				add_action( 'edit_attachment', array( $this, 'save_attachment_actions' ) );


				//wp_delete_file file filter
				add_filter( 'wp_delete_file', array( $this, 'delete_file' ) );

				if ( $pagenow == 'media-upload.php' ) {
					//Add the language filter to the media library
					add_action( 'media_upload_library', array( $this, 'language_filter' ), 1 );
					add_action( 'pre_get_posts', array( $this, 'filter_media_upload_items' ), 10, 1 );
				}
				add_action( 'wpml_media_create_duplicate_attachment', array( $this, 'invalidate_cache' ) );

				if ( $pagenow == 'media.php' ) {
					add_action( 'admin_footer', array( $this, 'media_language_options' ) );
				}

				if ( $pagenow == 'upload.php' ) {
					//Add the language filter to the media library (language_filter): /wp-admin/upload.php
					add_action( 'admin_footer', array( $this, 'language_filter_upload_page' ) );
				}

				add_action( 'wp_ajax_wpml_media_dismiss_starting_help', array( $this, 'dismiss_wpml_media_starting_help' ) );

				add_action( 'wp_ajax_wpml_media_set_initial_language', array( $this, 'batch_set_initial_language' ) );
				add_action( 'wp_ajax_wpml_media_translate_media', array( $this, 'batch_translate_media' ) );
				add_action( 'wp_ajax_wpml_media_duplicate_media', array( $this, 'batch_duplicate_media' ) );
				add_action( 'wp_ajax_wpml_media_duplicate_featured_images', array( $this, 'batch_duplicate_featured_images' ) );

				add_action( 'wp_ajax_wpml_media_mark_processed', array( $this, 'batch_mark_processed' ) );
				add_action( 'wp_ajax_wpml_media_scan_prepare', array( $this, 'batch_scan_prepare' ) );

				add_action( 'wp_ajax_wpml_media_set_content_prepare', array( $this, 'set_content_defaults_prepare' ) );
				add_action( 'wp_ajax_wpml_media_set_content_defaults', array( $this, 'set_content_defaults' ) );

				add_action( 'wp_ajax_set-post-thumbnail', array( $this, 'ajax_set_post_thumbnail' ), 0 );
				add_action( 'wp_ajax_find_posts', array( $this, 'find_posts_filter' ), 0 );
			}

			add_action( 'icl_pro_translation_saved', array( $this, 'icl_pro_translation_saved' ), 10, 1 );
		} else {
			if($sitepress_settings[ 'language_negotiation_type' ] == 2) {
				// Translate media url when in front-end and only when using custom domain
				add_filter( 'wp_get_attachment_url', array( $this, 'wp_get_attachment_url' ), 10, 2 );
			}
		}

		add_filter( 'WPML_filter_link', array( $this, 'filter_link' ), 10, 2 );
		add_filter( 'icl_ls_languages', array( $this, 'icl_ls_languages' ), 10, 1 );

		return null;
	}

	function is_admin_or_xmlrpc() {
		$is_admin = is_admin();
		$is_xmlrpc = (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST);
		return $is_admin || $is_xmlrpc;
	}

	function is_uploading_plugin_or_theme()
	{
		global $action;

		return ( isset( $action ) && ($action == 'upload-plugin' || $action == 'upload-theme') );
	}

	function plugin_localization()
	{
		load_plugin_textdomain( 'wpml-media', false, WPML_MEDIA_FOLDER . '/locale' );
	}

	/**
	 *    Needed by class init and by all static methods that use self::$settings
	 */
	public static function init_settings()
	{
		if ( !self::$settings )
			self::$settings = get_option( self::$settings_option_key );

		$default_settings = array(
			'version'              => false,
			'starting_help'        => false,
			'new_content_settings' => array(
				'always_translate_media' => true,
				'duplicate_media'        => true,
				'duplicate_featured'     => true
			)
		);

		if ( !self::$settings ) {
			self::$settings = $default_settings;
		}
	}

	/**
	 *    This method, called on 'plugins_loaded' action, overrides or replaces WPML default behavior
	 */
	public function overrides()
	{
		global $sitepress, $pagenow;

		//Removes the WPML language metabox on media and replace it with the custom one
		remove_action( 'admin_head', array( $sitepress, 'post_edit_language_options' ) );
		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' || $pagenow == 'edit.php' ) {
			add_action( 'admin_head', array( $this, 'post_edit_language_options' ) );
		}
	}

	public static function get_setting( $name, $default = false )
	{
		self::init_settings();
		if ( !isset( self::$settings[ $name ] ) || !self::$settings[ $name ] )
			return $default;

		return self::$settings[ $name ];
	}

	public static function update_setting( $name, $value )
	{
		self::init_settings();
		self::$settings[ $name ] = $value;

		return update_option( self::$settings_option_key, self::$settings );
	}

	function post_edit_language_options()
	{
		global $post, $sitepress, $pagenow;

		//Removes the language metabox on media
		if ( ( isset( $_POST['wp-preview'] ) && $_POST['wp-preview'] === 'dopreview' ) || is_preview() ) {
			$is_preview = true;
		} else {
			$is_preview = false;
		}

		//If not a media admin page, call the default WPML post_edit_language_options() method
		if ( ! ( $pagenow === 'upload.php' || $pagenow === 'media-upload.php' || $is_preview || ( isset( $post ) && $post->post_type === 'attachment' ) || is_attachment() ) ) {
			$sitepress->post_edit_language_options();
		}

	}

	function first_time_notice()
	{
		?>
		<div class="error message otgs-is-dismissible">
			<p>
				<?php _e( 'WPML Media Translation needs to set languages to existing media in your site.', 'wpml-media' ) ?>
				<a href="<?php echo admin_url( 'admin.php?page=wpml-media' ) ?>" class="button-secondary"><?php _e( 'Set media languages', 'wpml-media' ) ?></a>

				<span id="wpml_media_dismiss_1" class="notice-dismiss" 
					  onclick="jQuery.ajax({url:ajaxurl,method:'POST',data:{action:'wpml_media_dismiss_starting_help'},success:function(){jQuery('#wpml_media_dismiss_1').closest('.message').fadeOut()}}); return false;"><span class="screen-reader-text"><?php _e( "Dismiss", 'wpml-media' ) ?></span></span>
			</p>
		</div>
	<?php
	}

	function dismiss_wpml_media_starting_help()
	{
		self::update_setting( 'starting_help', 1 );
		exit;
	}

	function set_content_defaults_prepare()
	{
		$response = array( 'message' => __( 'Started...', 'wpml-media' ) );
		echo wp_json_encode( $response );
		exit;
	}

	function set_content_defaults()
	{
		$always_translate_media = $_POST[ 'always_translate_media' ];
		$duplicate_media        = $_POST[ 'duplicate_media' ];
		$duplicate_featured     = $_POST[ 'duplicate_featured' ];

		$content_defaults_option = array(
			'always_translate_media' => $always_translate_media == 'true',
			'duplicate_media'        => $duplicate_media == 'true',
			'duplicate_featured'     => $duplicate_featured == 'true'
		);

		$result = self::update_setting( 'new_content_settings', $content_defaults_option );

		$response = array(
			'result'  => $result,
			'message' => __( 'Default settings stored.', 'wpml-media' )
		);
		echo wp_json_encode( $response );
		exit;
	}

	function batch_scan_prepare()
	{
		global $wpdb;

		$response = array();
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'wpml_media_processed' ) );

		$response[ 'message' ] = __( 'Started...', 'wpml-media' );

		echo wp_json_encode( $response );
		exit;
	}

	function batch_set_initial_language()
	{
		global $wpdb, $sitepress;

		$default_language = $sitepress->get_default_language();
		$limit            = 10;

		$response    = array();
		$attachments_prepared = $wpdb->prepare( "
            SELECT SQL_CALC_FOUND_ROWS ID FROM {$wpdb->posts} WHERE post_type = %s AND ID NOT IN
            (SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type=%s) LIMIT %d", array( 'attachment', 'post_attachment', $limit ) );
		$attachments = $wpdb->get_col( $attachments_prepared );

		$found = $wpdb->get_var( "SELECT FOUND_ROWS()" );

		foreach ( $attachments as $attachment_id ) {
			$sitepress->set_element_language_details( $attachment_id, 'post_attachment', false, $default_language );
		}
		$response[ 'left' ] = max( $found - $limit, 0 );
		if ( $response[ 'left' ] ) {
			$response[ 'message' ] = sprintf( __( 'Setting language to media. %d left', 'wpml-media' ), $response[ 'left' ] );
		} else {
			$response[ 'message' ] = sprintf( __( 'Setting language to media: done!', 'wpml-media' ), $response[ 'left' ] );
		}

		echo wp_json_encode( $response );
		exit;
	}

	function batch_translate_media()
	{
		$response = array();

		$active_languages = count( $this->sitepress->get_active_languages() );
		$limit            = $this->get_batch_translate_limit( $active_languages );

		$sql              = "
            SELECT SQL_CALC_FOUND_ROWS p1.ID, p1.post_parent
            FROM {$this->wpdb->prefix}icl_translations t
            INNER JOIN {$this->wpdb->posts} p1
            	ON t.element_id = p1.ID
            LEFT JOIN {$this->wpdb->prefix}icl_translations tt
            	ON t.trid = tt.trid
			WHERE t.element_type = 'post_attachment'
				AND t.source_language_code IS null
			GROUP BY p1.ID, p1.post_parent
			HAVING count(tt.language_code) < %d
            LIMIT %d
        ";
		$sql_prepared = $this->wpdb->prepare($sql, array($active_languages, $limit));
		$attachments      = $this->wpdb->get_results( $sql_prepared );

		$found = $this->wpdb->get_var( "SELECT FOUND_ROWS()" );

		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				$lang = $this->sitepress->get_element_language_details( $attachment->ID, 'post_attachment' );
				$this->translate_attachments( $attachment->ID, $lang->language_code );
			}
		}

		$response[ 'left' ] = max( $found - $limit, 0 );
		if ( $response[ 'left' ] ) {
			$response[ 'message' ] = sprintf( esc_html__( 'Translating media. %d left', 'wpml-media' ), $response[ 'left' ] );
		} else {
			$response[ 'message' ] = sprintf( esc_html__( 'Translating media: done!', 'wpml-media' ), $response[ 'left' ] );
		}

		wp_send_json( $response );
	}

	/**
	 * @param int $active_languages
	 *
	 * @return int
	 */
	private function get_batch_translate_limit( $active_languages ) {
		$limit = $this->sitepress->get_wp_api()->constant( 'WPML_MEDIA_BATCH_LIMIT' );
		$limit = ! $limit ? floor( 10 / max( $active_languages - 1, 1 ) ) : $limit;
		return max( $limit, 1 );
	}

	function translate_attachments( $attachment_id, $source_language )
	{
		$content_defaults = self::get_setting( 'new_content_settings' );
		if ( !empty( $source_language ) && $content_defaults[ 'always_translate_media' ] ) {

			global $sitepress;

			$original_attachment_id = false;
			$trid                           = $sitepress->get_element_trid( $attachment_id, 'post_attachment' );
			if ( $trid ) {
				$translations         = $sitepress->get_element_translations( $trid, 'post_attachment', true, true );
				$translated_languages = false;
				$default_language = $sitepress->get_default_language();
				$default_language_attachment_id = false;
				foreach ( $translations as $translation ) {
					//Get the default language attachment ID
					if ( $translation->original ) {
						$original_attachment_id = $translation->element_id;
					}
					if($translation->language_code == $default_language) {
						$default_language_attachment_id = $translation->element_id;
					}
					//Store already translated versions
					$translated_languages[ ] = $translation->language_code;
				}
				// Original attachment is missing
				if ( !$original_attachment_id ) {
					$attachment = get_post( $attachment_id );
					if(!$default_language_attachment_id) {
						self::create_duplicate_attachment( $attachment_id, $attachment->post_parent, $default_language );
					} else {
						$sitepress->set_element_language_details($default_language_attachment_id, 'post_attachment', $trid, $default_language, null);
					}
					//Start over
					$this->translate_attachments( $attachment->ID, $source_language );
				} else {
					//Original attachment is present
					$original = get_post( $original_attachment_id );
					$codes    = array_keys( $sitepress->get_active_languages() );
					foreach ( $codes as $code ) {
						//If translation is not present, create it
						if ( !in_array( $code, $translated_languages ) ) {
								self::create_duplicate_attachment( $attachment_id, $original->post_parent, $code );
						}
					}
				}
			}
		}

	}

	static function create_duplicate_attachment( $attachment_id, $parent_id, $target_language )
	{
		global $sitepress;

		$attachment_duplication = new WPML_Media_Attachments_Duplication( $sitepress );

		return $attachment_duplication->create_duplicate_attachment( $attachment_id, $parent_id, $target_language );
	}

	static function is_valid_post_type( $post_type )
	{
		global $wp_post_types;

		$post_types = array_keys( (array)$wp_post_types );

		return in_array( $post_type, $post_types );
	}

	function update_attachment_metadata( $source_attachment_id ) {
		global $sitepress;

		$original_element_id = $sitepress->get_original_element_id( $source_attachment_id, 'post_attachment', false, false, true );
		if ( $original_element_id ) {
			$metadata = wp_get_attachment_metadata( $original_element_id );
			$this->synchronize_attachment_metadata( $metadata, $original_element_id );
		}
	}

	function wp_generate_attachment_metadata( $metadata, $attachment_id )
	{
		$this->synchronize_attachment_metadata($metadata, $attachment_id);

		return $metadata;
	}

	function added_post_meta( $meta_id, $object_id, $meta_key, $_meta_value )
	{
		if ( $meta_key != '_wp_attachment_metadata' ) {
			return;
		}

		$attachment_id = $object_id;
		$metadata      = $_meta_value;

		$this->synchronize_attachment_metadata( $metadata, $attachment_id );
	}

	function synchronize_attachment_metadata( $metadata, $attachment_id ) {
		global $sitepress, $wpdb;

		//Update _wp_attachment_metadata to all translations (excluding the current one)
		$trid = $sitepress->get_element_trid( $attachment_id, 'post_attachment' );
		if ( $trid ) {
			$translations = $sitepress->get_element_translations( $trid, 'post_attachment', true, true, true );
			foreach ( $translations as $translation ) {
				if ( $translation->element_id != $attachment_id ) {
					update_post_meta( $translation->element_id, '_wp_attachment_metadata', $metadata );
					$mime_type = get_post_mime_type( $attachment_id );
					if ( $mime_type ) {
						$wpdb->update( $wpdb->posts, array( 'post_mime_type' => $mime_type ), array( 'ID' => $translation->element_id ) );
					}
				}
			}
		}
	}

	function batch_duplicate_media()
	{
		global $wpdb;

		$limit = 10;

		$response = array();

		$attachments_prepared = $wpdb->prepare( "
            SELECT SQL_CALC_FOUND_ROWS p1.ID, p1.post_parent
            FROM {$wpdb->posts} p1
            WHERE post_type = %s
            AND ID NOT IN
            	(SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s)
            ORDER BY p1.ID ASC LIMIT %d", array('attachment', 'wpml_media_processed', $limit));

		$attachments = $wpdb->get_results( $attachments_prepared );
		$found       = $wpdb->get_var( "SELECT FOUND_ROWS()" );

		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				$this->create_duplicated_media( $attachment );
			}
		}

		$response[ 'left' ] = max( $found - $limit, 0 );
		if ( $response[ 'left' ] ) {
			$response[ 'message' ] = sprintf( __( 'Duplicating media. %d left', 'wpml-media' ), $response[ 'left' ] );
		} else {
			$response[ 'message' ] = sprintf( __( 'Duplicating media: done!', 'wpml-media' ), $response[ 'left' ] );
		}

		echo wp_json_encode( $response );
		exit;
	}

	/**
	 * @param $attachment WP_Post
	 */
	function create_duplicated_media( $attachment )
	{
		global $wpdb, $sitepress;

		static $parents_processed = array();

		if ( $attachment->post_parent && !in_array( $attachment->post_parent, $parents_processed ) ) {

			// see if we have translations.
			$post_type_prepared = $wpdb->prepare("SELECT post_type FROM {$wpdb->posts} WHERE ID = %d", array($attachment->post_parent));
			$post_type = $wpdb->get_var( $post_type_prepared );
			$trid_prepared = $wpdb->prepare("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type = %s", array($attachment->post_parent, 'post_' . $post_type));
			$trid      = $wpdb->get_var( $trid_prepared );
			if ( $trid ) {

				update_post_meta( $attachment->post_parent, '_wpml_media_duplicate', 1 );

				$attachments_prepared = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d", array('attachment', $attachment->post_parent));
				$attachments = $wpdb->get_col( $attachments_prepared );

				$translations = $sitepress->get_element_translations( $trid, 'post_' . $post_type );
				foreach ( $translations as $translation ) {
					if ( $translation->element_id && $translation->element_id != $attachment->post_parent ) {

						$attachments_in_translation_prepared = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d", array('attachment', $translation->element_id));
						$attachments_in_translation = $wpdb->get_col( $attachments_in_translation_prepared );
						if ( sizeof( $attachments_in_translation ) == 0 ) {
							// only duplicate attachments if there a none already.
							foreach ( $attachments as $attachment_id ) {
								// duplicate the attachment
								self::create_duplicate_attachment( $attachment_id, $translation->element_id, $translation->language_code );
							}
						}
					}
				}
			}

			$parents_processed[ ] = $attachment->post_parent;

		} else {
			// no parent - set to default language

			$target_language = $sitepress->get_default_language();

			//Getting the trid and language, just in case image translation already exists
			$trid = $sitepress->get_element_trid( $attachment->ID, 'post_attachment' );
			if ( $trid ) {
				$target_language = $sitepress->get_language_for_element( $attachment->ID, 'post_attachment' );
			}

			$sitepress->set_element_language_details( $attachment->ID, 'post_attachment', $trid, $target_language );

		}

		//Duplicate the post meta of the source element the translation
		$source_element_id = SitePress::get_original_element_id_by_trid( $trid );
		if ( $source_element_id ) {
			$this->update_attachment_metadata( $source_element_id );
		}

		update_post_meta( $attachment->ID, 'wpml_media_processed', 1 );
	}

	function batch_duplicate_featured_images()
	{
		$limit = 10;

		$response = array();

		$found = self::duplicate_featured_images( $limit );

		$response[ 'left' ] = max( $found - $limit, 0 );
		if ( $response[ 'left' ] ) {
			$response[ 'message' ] = sprintf( __( 'Duplicating featured images. %d left', 'wpml-media' ), $response[ 'left' ] );
		} else {
			$response[ 'message' ] = sprintf( __( 'Duplicating featured images: done!', 'wpml-media' ), $response[ 'left' ] );
		}

		echo wp_json_encode( $response );
		exit;
	}

	static function duplicate_featured_images( $limit = 0 )
	{
		global $wpdb, $sitepress;

		$count = 0;

		$featured_images_sql = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s";
		if ( $limit > 0 ) {
			$featured_images_sql .= " LIMIT %d";
		}
		$featured_images_sql_prepared = $wpdb->prepare($featured_images_sql, array('_thumbnail_id', $limit));
		$featured_images = $wpdb->get_results( $featured_images_sql_prepared );
		$processed       = $wpdb->get_var( "SELECT FOUND_ROWS()" );

		$thumbnails = array();
		foreach ( $featured_images as $featured ) {
			$thumbnails[ $featured->post_id ] = $featured->meta_value;
		}

		if ( sizeof( $thumbnails ) ) {
			//Posts IDs with found featured images
			$post_ids = wpml_prepare_in( array_keys( $thumbnails ), '%d' );
			$posts_prepared = "SELECT ID, post_type FROM {$wpdb->posts} WHERE ID IN ({$post_ids})";
			$posts    = $wpdb->get_results( $posts_prepared );
			foreach ( $posts as $post ) {
				$row_prepared = $wpdb->prepare("SELECT trid, source_language_code
												FROM {$wpdb->prefix}icl_translations
												WHERE element_id=%d
													AND element_type = %s",
				                               array($post->ID, 'post_' . $post->post_type));
				$row = $wpdb->get_row( $row_prepared );
				if ( $row && $row->trid && ( $row->source_language_code == null || $row->source_language_code == "" ) ) {
					update_post_meta( $post->ID, '_wpml_media_featured', 1 );

					$translations = $sitepress->get_element_translations( $row->trid, 'post_' . $post->post_type );
					foreach ( $translations as $translation ) {
						if ( $translation->element_id != $post->ID ) {
							if ( !in_array( $translation->element_id, array_keys( $thumbnails ) ) ) {

								// translation doesn't have a featured image
								$t_thumbnail_id = icl_object_id( $thumbnails[ $post->ID ], 'attachment', false, $translation->language_code );
								if ( $t_thumbnail_id == null ) {
									$dup_att_id     = self::create_duplicate_attachment( $thumbnails[ $post->ID ], $translation->element_id, $translation->language_code );
									$t_thumbnail_id = $dup_att_id;
								}

								if ( $t_thumbnail_id != null ) {
									update_post_meta( $translation->element_id, '_thumbnail_id', $t_thumbnail_id );
								}
								$count += 1;
							} elseif ( $thumbnails[ $post->ID ] ) {
								update_post_meta( $translation->element_id, '_thumbnail_id', $thumbnails[ $post->ID ] );
							}
							//Double check that there is a _thumbnail_id set and in case update _wpml_media_featured
							if ( get_post_meta( $translation->element_id, '_thumbnail_id', true ) ) {
								update_post_meta( $translation->element_id, '_wpml_media_featured', 1 );
							}
						}
					}
				}

			}
		}

		return $processed;
	}

	function batch_mark_processed()
	{
		/** @var $wpdb WPDB */
		global $wpdb;

		$response    = array();
		$attachments_prepared = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s", array('attachment'));
		$attachments = $wpdb->get_col( $attachments_prepared );
		foreach ( $attachments as $attachment_id ) {

			$find_meta_prepared = $wpdb->prepare("SELECT count(post_id) FROM {$wpdb->postmeta} WHERE meta_key=%s AND post_id = %d", array('wpml_media_processed', $attachment_id));
			$meta_exists = $wpdb->get_var($find_meta_prepared);

			if($meta_exists)  {
				$wpdb->update($wpdb->postmeta, array('meta_value' => 1), array('meta_key' => 'wpml_media_processed', 'post_id' => $attachment_id));
			} else {
				$wpdb->insert($wpdb->postmeta, array('meta_value'=>1, 'post_id' => $attachment_id, 'meta_key' => 'wpml_media_processed'));
			}
		}

		self::update_setting( 'starting_help', 1 );

		$response[ 'message' ] = __( 'Done!', 'wpml-media' );

		echo wp_json_encode( $response );

		exit;
	}

	function ajax_set_post_thumbnail()
	{
		$request_post_post_id = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);
		$this->sync_post_thumbnail( $request_post_post_id );
	}

	function sync_post_thumbnail( $post_id )
	{
		global $sitepress;

		if ( $post_id && get_post_meta( $post_id, '_wpml_media_featured', true ) ) {

			$request_post_thumbnail_id = filter_input(INPUT_POST, 'thumbnail_id', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);
			$thumbnail_id = $request_post_thumbnail_id ? $request_post_thumbnail_id : get_post_meta( $post_id, '_thumbnail_id', true );
			$trid         = $sitepress->get_element_trid( $post_id, 'post_' . get_post_type( $post_id ) );
			$translations = $sitepress->get_element_translations( $trid, 'post_' . get_post_type( $post_id ) );

			// is original
			$is_original = false;
			foreach ( $translations as $translation ) {
				if ( $translation->original == 1 && $translation->element_id == $post_id ) {
					$is_original = true;
				}
			}

			if ( $is_original ) {
				foreach ( $translations as $translation ) {
					if ( !$translation->original && $translation->element_id ) {
						if ( !$thumbnail_id || $thumbnail_id == "-1" ) {
							delete_post_meta( $translation->element_id, '_thumbnail_id' );
						} else {
							$translated_thumbnail_id = icl_object_id( $thumbnail_id, 'attachment', false, $translation->language_code );
							update_post_meta( $translation->element_id, '_thumbnail_id', $translated_thumbnail_id );
						}
					}
				}
			}
		}

	}

	function find_posts_filter()
	{
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
	}

	function pre_get_posts( $query )
	{
		$query->query[ 'suppress_filters' ]      = 0;
		$query->query_vars[ 'suppress_filters' ] = 0;
	}

	function media_language_options()
	{
		global $sitepress;
		$att_id = filter_input(INPUT_GET, 'attachment_id', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);
		$translations = $sitepress->get_element_translations( $att_id, 'post_attachment' );
		$current_lang = '';
		foreach ( $translations as $lang => $id ) {
			if ( $id == $att_id ) {
				$current_lang = $lang;
				unset( $translations[ $lang ] );
				break;
			}
		}

		$active_languages = icl_get_languages( 'orderby=id&order=asc&skip_missing=0' );
		$lang_links       = '';

		if ( $current_lang ) {

			$lang_links = '<strong>' . $active_languages[ $current_lang ][ 'native_name' ] . '</strong>';

		}

		foreach ( $translations as $lang => $id ) {
			$lang_links .= ' | <a href="' . admin_url( 'media.php?attachment_id=' . $id . '&action=edit' ) . '">' . $active_languages[ $lang ][ 'native_name' ] . '</a>';
		}


		echo '<div id="icl_lang_options" style="display:none">' . $lang_links . '</div>';
	}

	function icl_pro_translation_saved( $new_post_id )
	{
		global $wpdb;

		// Note: We can't use filter_input here because in only filters the original post values
		// and not ones that are added programatically.
		// http://php.net/manual/en/function.filter-input.php#99124
		$trid = filter_var( $_POST[ 'trid' ], FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);
		$lang = filter_var( $_POST[ 'lang' ], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE);

		$source_lang_prepared = $wpdb->prepare( "SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", array($trid));
		$source_lang = $wpdb->get_var( $source_lang_prepared );

		$this->duplicate_post_attachments( $new_post_id, $trid, $source_lang, $lang );
	}

	function duplicate_post_attachments( $pidd, $icl_trid, $source_lang = null, $lang = null )
	{
		global $wpdb;

		$request_post_icl_ajx_action = filter_input(INPUT_POST, 'icl_ajx_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE);
		$request_post_icl_post_language = filter_input(INPUT_POST, 'icl_post_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE);
		$request_post_post_id = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);

		if ( $icl_trid == "" ) {
			return;
		}

		if ( !$source_lang ) {
			$source_lang_prepared = $wpdb->prepare( "SELECT source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND trid=%d", array($pidd, $icl_trid));
			$source_lang = $wpdb->get_var( $source_lang_prepared );
		}

		// exception for making duplicates. language info not set when this runs and creating the duplicated posts 1/3
		if ( $request_post_icl_ajx_action == 'make_duplicates' && $request_post_icl_post_language ) {
			$source_lang_prepared = $wpdb->prepare( "SELECT language_code FROM {$wpdb->prefix}icl_translations
													 WHERE element_id = %d AND trid = %d", array($request_post_post_id, $icl_trid ));
			$source_lang = $wpdb->get_var( $source_lang_prepared );
			$lang        = $request_post_icl_post_language;

		}

		if ( $source_lang == null || $source_lang == "" ) {
			// This is the original see if we should copy to translations

            if ( $this->is_media_marked_to_duplication( $pidd )
                 || $this->is_feature_image_marked_to_duplication( $pidd )
            ) {
				$translations_prepared = $wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = %d", array($icl_trid));
				$translations = $wpdb->get_col( $translations_prepared );

				foreach ( $translations as $element_id ) {
					if ( $element_id && $element_id != $pidd ) {

						$lang_prepared = $wpdb->prepare( "SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND trid = %d", array($element_id, $icl_trid ));
						$lang = $wpdb->get_var( $lang_prepared );

						if ( $this->is_media_marked_to_duplication( $element_id ) ) {
							$source_attachments_prepared = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", array($pidd, 'attachment' ));
							$source_attachments = $wpdb->get_col( $source_attachments_prepared );
							$attachments_prepared = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", array($element_id, 'attachment' ));
							$attachments        = $wpdb->get_col( $attachments_prepared );

							foreach ( $source_attachments as $source_attachment_id ) {
								foreach ( $attachments as $attachment_id ) {
									$translation_attachment_id = icl_object_id( $attachment_id, 'attachment', false, $lang );
									if ( ! $translation_attachment_id ) {
										$this->create_duplicate_attachment_not_static( $source_attachment_id, $element_id, $lang );
									}
								}
							}
						}

						if ( $this->is_feature_image_marked_to_duplication( $element_id ) ) {
							$thumbnail_id = get_post_meta( $pidd, '_thumbnail_id', true );
							if ( $thumbnail_id ) {
								$t_thumbnail_id = icl_object_id( $thumbnail_id, 'attachment', false, $lang );
								if ( $t_thumbnail_id == null ) {
									$dup_att_id     = $this->create_duplicate_attachment_not_static( $thumbnail_id, $element_id, $lang );
									$t_thumbnail_id = $dup_att_id;
								}

								if ( $t_thumbnail_id != null ) {
									update_post_meta( $element_id, '_thumbnail_id', $t_thumbnail_id );
								}
							}
						}
					}
				}
			}

		} else {
			// This is a translation.

			// exception for making duplicates. language info not set when this runs and creating the duplicated posts 2/3
			if ( $request_post_icl_ajx_action === 'make_duplicates' ) {
				$source_id = $request_post_post_id;
			} else {
				$source_id_prepared = $wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE language_code = %s AND trid = %d", array($source_lang, $icl_trid));
				$source_id = $wpdb->get_var( $source_id_prepared );
			}

			if ( !$lang ) {
				$lang_prepared = $wpdb->prepare( "SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND trid = %d", array($pidd, $icl_trid));
				$lang = $wpdb->get_var( $lang_prepared );
			}

			// exception for making duplicates. language info not set when this runs and creating the duplicated posts 3/3
			if ( $request_post_icl_ajx_action === 'make_duplicates' ) {
				$duplicate = get_post_meta( $source_id, '_wpml_media_duplicate', true );
			} else {
				$duplicate = get_post_meta( $pidd, '_wpml_media_duplicate', true );
				if ( !$duplicate ) {
					// check the original state
					$duplicate = get_post_meta( $source_id, '_wpml_media_duplicate', true );
				}
			}

			if ( $duplicate ) {
				$source_attachments_prepared = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", array($source_id, 'attachment'));
				$source_attachments = $wpdb->get_col( $source_attachments_prepared );

				foreach ( $source_attachments as $source_attachment_id ) {
					$translation_attachment_id = icl_object_id( $source_attachment_id, 'attachment', false, $lang );

					if ( !$translation_attachment_id ) {
						self::create_duplicate_attachment( $source_attachment_id, $pidd, $lang );
					} else {
						$translated_attachment = get_post( $translation_attachment_id );
						if ( $translated_attachment && !$translated_attachment->post_parent ) {
							$translated_attachment->post_parent = $pidd;
							wp_update_post( $translated_attachment );
						}
					}

				}
			}

			$featured = get_post_meta( $pidd, '_wpml_media_featured', true );
			if ( $featured === "" ) {
				// check the original state
				$featured = get_post_meta( $source_id, '_wpml_media_featured', true );
			}

			if ( $featured ) {
				$thumbnail_id = get_post_meta( $source_id, '_thumbnail_id', true );
				if ( $thumbnail_id ) {
					$t_thumbnail_id = icl_object_id( $thumbnail_id, 'attachment', false, $lang );
					if ( $t_thumbnail_id == null ) {
						$dup_att_id     = self::create_duplicate_attachment( $thumbnail_id, $pidd, $lang );
						$t_thumbnail_id = $dup_att_id;
					}

					if ( $t_thumbnail_id != null ) {
						update_post_meta( $pidd, '_thumbnail_id', $t_thumbnail_id );
					}
				}

			}

		}

	}

	/**
	 * @param $source_attachment_id
	 * @param $pidd
	 * @param $lang
	 *
	 * @return int|null|WP_Error
	 */
	public function create_duplicate_attachment_not_static( $source_attachment_id, $pidd, $lang ) {
		return self::create_duplicate_attachment( $source_attachment_id, $pidd, $lang );
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function is_media_marked_to_duplication( $post_id ) {
		$t = get_post_meta( $post_id, '_wpml_media_duplicate', true );

		return ! empty( $t );
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function is_feature_image_marked_to_duplication( $post_id ) {
		$t = get_post_meta( $post_id, '_wpml_media_featured', true );
		
		return ! empty( $t );
	}

	/**
	 * @param $pidd int
	 * @param $post WP_Post
	 */
	function save_post_actions( $pidd, $post ) {
		if ( $post->post_type != 'attachment' && $post->post_status != "auto-draft" ) {
			$this->sync_attachments( $pidd, $post );
			$this->sync_post_thumbnail( $pidd );
		}

		if ( $post->post_type === 'attachment' ) {
			$metadata      = wp_get_attachment_metadata( $post->ID );
			$attachment_id = $pidd;
			if ( $metadata ) {
				$this->synchronize_attachment_metadata( $metadata, $attachment_id );
			}
		}
	}

	/**
	 * @param $pidd int
	 * @param $post WP_Post
	 */
	function sync_attachments( $pidd, $post )
	{
		if ( $post->post_type == 'attachment' || $post->post_status == "auto-draft" )
			return;

		global $wpdb, $sitepress;

		$posts_prepared = $wpdb->prepare("SELECT post_type, post_status FROM {$wpdb->posts} WHERE ID = %d", array($pidd));
		list( $post_type, $post_status ) = $wpdb->get_row( $posts_prepared, ARRAY_N );

		//checking - if translation and not saved before
		if ( isset( $_GET[ 'trid' ] ) && !empty( $_GET[ 'trid' ] ) && $post_status == 'auto-draft' ) {

			//get source language
			if ( isset( $_GET[ 'source_lang' ] ) && !empty( $_GET[ 'source_lang' ] ) ) {
				$src_lang = $_GET[ 'source_lang' ];
			} else {
				$src_lang = $sitepress->get_default_language();
			}

			//get source id
			$src_id_prepared =  $wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code=%s", array($_GET['trid'], $src_lang));
			$src_id = $wpdb->get_var( $src_id_prepared );

			//delete exist auto-draft post media
			$results_prepared = $wpdb->prepare("SELECT p.id FROM {$wpdb->posts} AS p LEFT JOIN {$wpdb->posts} AS p1 ON p.post_parent = p1.id WHERE p1.post_status = %s", array('auto-draft'));
			$results     = $wpdb->get_results( $results_prepared, ARRAY_A );
			$attachments = array();
			if ( !empty( $results ) ) {
				foreach ( $results as $result ) {
					$attachments[ ] = $result[ "id" ];
				}
				if ( !empty( $attachments ) ) {
					$in_attachments  = wpml_prepare_in( $attachments, '%d' );
					$delete_prepared = "DELETE FROM {$wpdb->posts} WHERE id IN (" . $in_attachments . ")";
					$wpdb->query( $delete_prepared );
					$delete_prepared = "DELETE FROM {$wpdb->postmeta} WHERE post_id IN (" . $in_attachments . ")";
					$wpdb->query( $delete_prepared );
				}
			}

			//checking - if set duplicate media
			if ( get_post_meta( $src_id, '_wpml_media_duplicate', true ) ) {
				//duplicate media before first save
				$this->duplicate_post_attachments( $pidd, $_GET[ 'trid' ], $src_lang, $sitepress->get_language_for_element( $pidd, 'post_' . $post_type ) );
			}
		}

		// exceptions
		if (
			!$sitepress->is_translated_post_type( $post_type )
			|| isset( $_POST[ 'autosave' ] )
			|| ( isset( $_POST[ 'post_ID' ] ) && $_POST[ 'post_ID' ] != $pidd )
			|| ( isset( $_POST[ 'post_type' ] ) && $_POST[ 'post_type' ] === 'revision' )
			|| $post_type === 'revision'
			|| get_post_meta( $pidd, '_wp_trash_meta_status', true )
			|| ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'restore' )
			|| $post_status === 'auto-draft'
		) {
			return;
		}

		if ( isset( $_POST[ 'icl_trid' ] ) ) {
			$content_defaults_option = self::get_setting( 'new_content_settings' );
			$duplicate               = false;
			$featured                = false;
			if ( $content_defaults_option ) {
				if ( isset( $_POST[ 'icl_duplicate_attachments' ] ) ) {
					$duplicate = (int) $_POST['icl_duplicate_attachments'];
				}
				if ( isset( $_POST[ 'icl_duplicate_featured_image' ] ) ) {
					$featured = (int) $_POST['icl_duplicate_featured_image'];
				}
			}

			// save the post from the edit screen.
			update_post_meta( $pidd, '_wpml_media_duplicate', $duplicate );
			update_post_meta( $pidd, '_wpml_media_featured', $featured );

			$icl_trid = $_POST[ 'icl_trid' ];
		} else {
			// get trid from database.
			$icl_trid_prepared = $wpdb->prepare("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type = %s", array($pidd, 'post_' . $post_type));
			$icl_trid = $wpdb->get_var( $icl_trid_prepared );
		}

		if ( $icl_trid ) {
			$language_details = $sitepress->get_element_language_details( $pidd, 'post_' . $post_type );

			// In some cases the sitepress cache doesn't get updated (e.g. when posts are created with wp_insert_post()
			// Only in this case, the sitepress cache will be cleared so we can read the element language details
			if ( !$language_details ) {
				$sitepress->get_translations_cache()->clear();
				$language_details = $sitepress->get_element_language_details( $pidd, 'post_' . $post_type );
			}
			if ( $language_details ) {
				$this->duplicate_post_attachments( $pidd, $icl_trid, $language_details->source_language_code, $language_details->language_code );
			}
		}
	}

//	function sync_attachments_metadata($post_id) {
//		$data =
//	}

	function make_duplicate( $master_post_id, $target_lang, $post_array, $target_post_id )
	{
		global $wpdb, $sitepress;

		$translated_attachment_id = false;
		//Get Master Post attachments
		$master_post_attachment_ids_prepared = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", array($master_post_id, 'attachment'));
		$master_post_attachment_ids = $wpdb->get_col( $master_post_attachment_ids_prepared );

		if ( $master_post_attachment_ids ) {
			foreach ( $master_post_attachment_ids as $master_post_attachment_id ) {

				$attachment_trid = $sitepress->get_element_trid( $master_post_attachment_id, 'post_attachment' );

				if ( $attachment_trid ) {
					//Get attachment translation
					$attachment_translations = $sitepress->get_element_translations( $attachment_trid, 'post_attachment' );

					foreach ( $attachment_translations as $attachment_translation ) {
						if ( $attachment_translation->language_code == $target_lang ) {
							$translated_attachment_id = $attachment_translation->element_id;
							break;
						}
					}
	
					if ( !$translated_attachment_id ) {
						$translated_attachment_id = self::create_duplicate_attachment( $master_post_attachment_id, wp_get_post_parent_id( $master_post_id ), $target_lang );
					}
	
					if ( $translated_attachment_id ) {
						//Set the parent post, if not already set
						$translated_attachment = get_post( $translated_attachment_id );
						if ( !$translated_attachment->post_parent ) {
							$prepared_query = $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_parent=%d WHERE ID=%d", array($target_post_id, $translated_attachment_id ));
							$wpdb->query( $prepared_query );
						}
					}
				}

			}
		}
		
		// Duplicate the featured image.

		$thumbnail_id = get_post_meta( $master_post_id, '_thumbnail_id', true );
		
		if ( $thumbnail_id ) {
			
			$thumbnail_trid = $sitepress->get_element_trid( $thumbnail_id, 'post_attachment' );
			
			if ( $thumbnail_trid ) {
				// translation doesn't have a featured image
				$t_thumbnail_id = icl_object_id( $thumbnail_id, 'attachment', false, $target_lang );
				if ( $t_thumbnail_id == null ) {
					$dup_att_id     = self::create_duplicate_attachment( $thumbnail_id, $target_post_id, $target_lang );
					$t_thumbnail_id = $dup_att_id;
				}
	
				if ( $t_thumbnail_id != null ) {
					update_post_meta( $target_post_id, '_thumbnail_id', $t_thumbnail_id );
				}
			}
		
		}
		return $translated_attachment_id;
	}

	/**
	 * Synchronizes _wpml_media_* meta fields with all translations
	 *
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param string|mixed $meta_value
	 */
	function updated_postmeta( $meta_id, $object_id, $meta_key, $meta_value )
	{
		if ( in_array( $meta_key, array( '_wpml_media_duplicate', '_wpml_media_featured' ) ) ) {
			global $sitepress;
			$el_type      = 'post_' . get_post_type( $object_id );
			$trid         = $sitepress->get_element_trid( $object_id, $el_type );
			$translations = $sitepress->get_element_translations( $trid, $el_type, true, true );
			foreach ( $translations as $translation ) {
				if ( $translation->element_id != $object_id ) {
					$t_meta_value = get_post_meta( $translation->element_id, $meta_key, true );
					if ( $t_meta_value != $meta_value ) {
						update_post_meta( $translation->element_id, $meta_key, $meta_value );
					}
				}
			}
		}
	}

	function save_attachment_actions( $post_id )
	{
		if ( $this->is_uploading_plugin_or_theme() && get_post_type($post_id) == 'attachment' )
			return;

		global $wpdb, $sitepress;

		$media_language = $sitepress->get_language_for_element( $post_id, 'post_attachment' );
		$trid           = false;
		if ( !empty( $media_language ) ) {
			$trid = $sitepress->get_element_trid( $post_id, 'post_attachment' );
		}
		if ( empty( $media_language ) ) {
			$parent_post_sql      = "SELECT p2.ID, p2.post_type FROM {$wpdb->posts} p1 JOIN {$wpdb->posts} p2 ON p1.post_parent = p2.ID WHERE p1.ID=%d";
			$parent_post_prepared = $wpdb->prepare( $parent_post_sql, array( $post_id ) );
			$parent_post = $wpdb->get_row( $parent_post_prepared );

			if ( $parent_post ) {
				$media_language = $sitepress->get_language_for_element( $parent_post->ID, 'post_' . $parent_post->post_type );
			}

			if ( empty( $media_language ) ) {
				$media_language = $sitepress->get_admin_language_cookie();
			}
			if ( empty( $media_language ) ) {
				$media_language = $sitepress->get_default_language();
			}

		}
		if ( !empty( $media_language ) ) {
			$sitepress->set_element_language_details( $post_id, 'post_attachment', $trid, $media_language );

			$this->save_translated_attachments( $post_id );
			$this->update_attachment_metadata( $post_id );
		}
	}

	function save_translated_attachments( $post_id )
	{
		if ( $this->is_uploading_plugin_or_theme() && get_post_type($post_id) == 'attachment' )
			return;

		global $sitepress;

		$language_details = $sitepress->get_element_language_details( $post_id, 'post_attachment' );
		if ( isset( $language_details ) ) {
			$this->translate_attachments( $post_id, $language_details->language_code );
		}
	}

	function language_options()
	{
		$translation   = false;
		$translated_id = null;
		$source_id = $this->get_original_post_id();

		//This is a translation with no original content
		if ( $translation && !$translated_id )
			return;

		echo '<br /><br /><strong>' . __( 'Media attachments', 'wpml-media' ) . '</strong>';

		$checked = '';
		if ( $translation ) {
			//This is a translation
			if ( $translated_id ) {
				$duplicate = get_post_meta( $translated_id, '_wpml_media_duplicate', true );
				//If not set, or false, always overrides with the source/original setting
				if ( !$duplicate ) {
					// use the source/original state
					$duplicate = get_post_meta( $source_id, '_wpml_media_duplicate', true );
				}
				$featured = get_post_meta( $translated_id, '_wpml_media_featured', true );
				//If not set, or false, always overrides with the source/original setting
				if ( !$featured ) {
					// use the source/state
					$featured = get_post_meta( $source_id, '_wpml_media_featured', true );
				}

			} else {
				// This is a new translation, use the source/original settings.
				$duplicate = get_post_meta( $source_id, '_wpml_media_duplicate', true );
				$featured  = get_post_meta( $source_id, '_wpml_media_featured', true );
			}

			if ( $duplicate ) {
				$checked = ' checked="checked"';
			}
			echo '<br /><label><input name="icl_duplicate_attachments" type="checkbox" value="1" ' . $checked . '/>&nbsp;' . __( 'Duplicate uploaded media from original', 'wpml-media' ) . '</label>';

			if ( $featured ) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			}
			echo '<br /><label><input name="icl_duplicate_featured_image" type="checkbox" value="1" ' . $checked . '/>&nbsp;' . __( 'Duplicate featured image from original', 'wpml-media' ) . '</label>';
		} else {
			//This is the source/original content
			$content_defaults_option = self::get_setting( 'new_content_settings' );
			if ( $content_defaults_option && !isset( $_GET[ 'post' ] ) ) {
				$duplicate = $content_defaults_option[ 'duplicate_media' ];
				$featured  = $content_defaults_option[ 'duplicate_featured' ];
			} else {
				$duplicate = get_post_meta( $source_id, '_wpml_media_duplicate', true );
				$featured  = get_post_meta( $source_id, '_wpml_media_featured', true );
			}

			if ( $duplicate ) {
				$checked = ' checked="checked"';
			}
			echo '<br /><label><input name="icl_duplicate_attachments" type="checkbox" value="1" ' . $checked . '/>&nbsp;' . __( 'Duplicate uploaded media to translations', 'wpml-media' ) . '</label>';

			if ( $featured ) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			}
			echo '<br /><label><input name="icl_duplicate_featured_image" type="checkbox" value="1" ' . $checked . '/>&nbsp;' . __( 'Duplicate featured image to translations', 'wpml-media' ) . '</label>';
		}
	}

	function manage_media_columns( $posts_columns )
	{
		if ( isset( $_REQUEST[ 'lang' ] ) && $_REQUEST[ 'lang' ] == 'all' )
			$posts_columns[ 'language' ] = __( 'Language', 'wpml-media' );

		return $posts_columns;
	}

	function manage_media_custom_column( $column_name, $id )
	{
		if ( isset( $_REQUEST[ 'lang' ] ) && $_REQUEST[ 'lang' ] == 'all' && $column_name == 'language' ) {
			global $sitepress;
			if ( !empty( $this->languages[ $id ] ) ) {
				echo $sitepress->get_display_language_name( $this->languages[ $id ], $sitepress->get_admin_language() );
			} else {
				echo __( 'None', 'wpml-media' );
			}
		}
	}

	function parse_query( $q )
	{
		global $pagenow;
		if ( $pagenow == 'upload.php' || $pagenow == 'media-upload.php' || ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'query-attachments' ) ) {
			$this->_get_lang_info();
		}
	}

	function _get_lang_info()
	{
		global $wpdb;

		// get the attachment languages.
		//if query-attachments need display all attachments
		if ( ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'query-attachments' ) ) {
			$results_prepared = $wpdb->prepare("SELECT ID, post_parent FROM {$wpdb->posts} WHERE post_type=%s", array('attachment'));
			$results = $wpdb->get_results( $results_prepared );
		} else {
			//don't display attachments auto-draft posts
			$results_prepared = $wpdb->prepare("SELECT p.ID, p.post_parent FROM {$wpdb->posts} AS p LEFT JOIN {$wpdb->posts} AS p1 ON p.post_parent = p1.id WHERE p1.post_status <> %s AND p.post_type=%s", array('auto-draft', 'attachment'));
			$results = $wpdb->get_results( $results_prepared );
		}
		$this->parents    = array();
		$this->unattached = array();
		foreach ( $results as $result ) {
			$this->parents[ $result->ID ] = $result->post_parent;
			if ( !$result->post_parent ) {
				$this->unattached[ ] = $result->ID;
			}
		}
		if ( ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'query-attachments' ) ) {
			//don't display attachments auto-draft posts
			$results_prepared = $wpdb->prepare("
											SELECT p.id, t.language_code
											FROM {$wpdb->posts} AS p
												LEFT JOIN {$wpdb->posts} AS p1 ON p.post_parent = p1.id
												INNER JOIN {$wpdb->prefix}icl_translations AS t ON p.id = t.element_id
											WHERE p1.post_status <> %s
												AND t.element_type=%s
										", array('auto-draft', 'post_attachment'));
			$results = $wpdb->get_results( $results_prepared );
		} else {
			$results_prepared     = $wpdb->prepare("
											SELECT p.id, t.language_code
											FROM {$wpdb->posts} AS p
												INNER JOIN {$wpdb->prefix}icl_translations AS t ON p.id = t.element_id
											WHERE t.element_type=%s
										", array('post_attachment'));
			$results = $wpdb->get_results( $results_prepared );
		}


		$this->languages = array();
		foreach ( $results as $result ) {
			$this->languages[ $result->id ] = $result->language_code;
		}

		// determine list of att without language set (with their parents)
		foreach ( $this->parents as $att_id => $parent_id ) {
			if ( !isset( $this->languages[ $att_id ] ) && isset( $parent_languages[ $parent_id ] ) ) {
				$missing_languages[ $att_id ] = $parent_id;
			}
		}
		// get language of their parents
		if ( !empty( $missing_languages ) ) {
			$results_prepared = "
                SELECT p.ID, t.language_code
                FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id AND t.element_type = CONCAT('post_', p.post_type)
                WHERE p.ID IN(" . wpml_prepare_in($missing_languages, '%d' ) . ")";
			$results = $wpdb->get_results( $results_prepared );
			foreach ( $results as $row ) {
				$parent_languages[ $row->ID ] = $row->language_code;
			}
		}

		// set language of their parents
		if ( isset( $parent_languages ) )
			foreach ( $this->parents as $att_id => $parent_id ) {
				if ( !isset( $this->languages[ $att_id ] ) ) {
					$this->languages[ $att_id ] = $parent_languages[ $parent_id ];
				}
			}

	}

	/**
	 *Add a filter to fix the links for attachments in the language switcher so
	 *they point to the corresponding pages in different languages.
	 */
	function filter_link( $url, $lang_info )
	{
		return $url;
	}

	function wp_get_attachment_url($url, $post_id) {
		global $sitepress;
		return $sitepress->convert_url($url);
	}

	function icl_ls_languages( $w_active_languages )
	{
		static $doing_it = false;

		if ( is_attachment() && !$doing_it ) {
			$doing_it = true;
			// Always include missing languages.
			$w_active_languages = icl_get_languages( 'skip_missing=0' );
			$doing_it           = false;
		}

		return $w_active_languages;
	}

	function get_post_metadata( $value, $object_id, $meta_key, $single )
	{
		if ( $meta_key == '_thumbnail_id' ) {

			global $wpdb;

			$thumbnail_prepared = $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", array($object_id, $meta_key));
			$thumbnail = $wpdb->get_var( $thumbnail_prepared );

			if ( $thumbnail == null ) {
				// see if it's available in the original language.

				$post_type_prepared = $wpdb->prepare( "SELECT post_type FROM {$wpdb->posts} WHERE ID = %d", array($object_id));
				$post_type = $wpdb->get_var( $post_type_prepared );
				$trid_prepared = $wpdb->prepare( "SELECT trid, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type = %s", array($object_id, 'post_'.$post_type));
				$trid      = $wpdb->get_row( $trid_prepared );
				if ( $trid ) {

					global $sitepress;

					$translations = $sitepress->get_element_translations( $trid->trid, 'post_' . $post_type );
					if ( isset( $translations[ $trid->source_language_code ] ) ) {
						$translation = $translations[ $trid->source_language_code ];
						// see if the original has a thumbnail.
						$thumbnail_prepared = $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", array($translation->element_id, $meta_key));
						$thumbnail = $wpdb->get_var( $thumbnail_prepared );
						if ( $thumbnail ) {
							$value = $thumbnail;
						}
					}
				}
			} else {
				$value = $thumbnail;
			}

		}

		return $value;
	}

	function menu()
	{
		if(!defined('ICL_PLUGIN_PATH')) return;
		global $sitepress;
		if(!isset($sitepress) || (method_exists($sitepress,'get_setting') && !$sitepress->get_setting( 'setup_complete' ))) return;

		$top_page = apply_filters( 'icl_menu_main_page', basename( ICL_PLUGIN_PATH ) . '/menu/languages.php' );

		add_submenu_page( $top_page,
						  __( 'Media translation', 'wpml-media' ),
						  __( 'Media translation', 'wpml-media' ), 'wpml_manage_media_translation',
						  'wpml-media', array( $this, 'menu_content' ) );
	}

	function menu_content()
	{
		global $wpdb;

		//Used by management.php
		$orphan_attachments_sql = "
																SELECT COUNT(*)
																FROM {$wpdb->posts}
																WHERE post_type = 'attachment'
																	AND ID NOT IN (
																		SELECT element_id
																		FROM {$wpdb->prefix}icl_translations
																		WHERE element_type='post_attachment'
																	)
																	";
		$orphan_attachments = $wpdb->get_var( $orphan_attachments_sql );


		include WPML_MEDIA_PATH . '/menu/management.php';
	}

	function js_scripts()
	{
		global $pagenow;
		if ( $pagenow == 'media.php' ) {
			wp_enqueue_script( 'wpml-media-language-options', WPML_MEDIA_URL . '/res/js/language_options.js', array(), WPML_MEDIA_VERSION, true );
		}
		if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'wpml-media' ) {
			wp_enqueue_script( 'wpml-media-settings', WPML_MEDIA_URL . '/res/js/settings.js', array(), WPML_MEDIA_VERSION, true );
		}
	}

	function language_filter()
	{
		global $sitepress;

		$lang_code = null;
		if ( isset( $_GET[ 'lang' ] ) ) {
			$lang_code = $_GET[ 'lang' ];
		} else {
			if ( method_exists( $sitepress, 'get_admin_language_cookie' ) ) {
				$lang_code = $sitepress->get_admin_language_cookie();
			}
		}

		$active_languages = $sitepress->get_active_languages();

		$active_languages[ ] = array( 'code' => 'all', 'display_name' => __( 'All languages', 'wpml-media' ) );
		$language_items      = array();
		foreach ( $active_languages as $lang ) {
			if ( $lang[ 'code' ] == $lang_code ) {
				$px = '<strong>';
				$sx = ' <span class="count">(' . $lang[ 'code' ] . ')</span></strong>';
			} else {
				$px = '<a href="' . $_SERVER[ 'REQUEST_URI' ] . '&lang=' . $lang[ 'code' ] . '">';
				$sx = '</a> <span class="count">(' . $lang[ 'code' ] . ')</span>';
			}
			$language_items[ ] = $px . $lang[ 'display_name' ] . $sx;
		}

		wp_enqueue_script( 'wpml-media-language-options', WPML_MEDIA_URL . '/res/js/language_options.js', array(), WPML_MEDIA_VERSION, true );
		wp_localize_script( 'wpml-media-language-options', 'language_items', $language_items );
	}

	//check if the image is not duplicated to another post before deleting it physically
	function views_upload( $views )
	{
		global $sitepress, $wpdb, $pagenow;

		if ( $pagenow == 'upload.php' ) {
			//get current language
			$lang = $sitepress->get_current_language();

			foreach ( $views as $key => $view ) {
				// extract the base URL and query parameters
				$href_count = preg_match( '/(href=["\'])([\s\S]+?)\?([\s\S]+?)(["\'])/', $view, $href_matches );
				if ( $href_count && isset( $href_args ) ) {
					$href_base = $href_matches[2];
					wp_parse_str( $href_matches[3], $href_args );
				} else {
					$href_base = 'upload.php';
					$href_args = array();
				}

				if ( $lang != 'all' ) {
					$sql = $wpdb->prepare("
						SELECT COUNT(p.id)
						FROM {$wpdb->posts} AS p
							INNER JOIN {$wpdb->prefix}icl_translations AS t
								ON p.id = t.element_id
						WHERE p.post_type = 'attachment'
						AND t.element_type='post_attachment'
						AND t.language_code = %s ", $lang);

					switch ( $key ) {
						case 'all';
							$and = " AND p.post_status != 'trash' ";
							break;
						case 'detached':
							$and = " AND p.post_status != 'trash' AND p.post_parent = 0 ";
							break;
						case 'trash':
							$and = " AND p.post_status = 'trash' ";
							break;
						default:
							if ( isset( $href_args['post_mime_type'] ) ) {
								$and = " AND p.post_status != 'trash' " . wp_post_mime_type_where( $href_args['post_mime_type'], 'p' );
							} else {
								$and = $wpdb->prepare(" AND p.post_status != 'trash' AND p.post_mime_type LIKE %s", $key . '%');
							}
					}

					$and = apply_filters( 'wpml-media_view-upload-sql_and', $and, $key, $view, $lang );

					$sql_and = $sql . $and;
					$sql = apply_filters( 'wpml-media_view-upload-sql', $sql_and, $key, $view, $lang );

					$res = apply_filters( 'wpml-media_view-upload-count', NULL, $key, $view, $lang );
					if ( NULL === $res ) {
					$res = $wpdb->get_col( $sql );
					}
					//replace count
					$view = preg_replace( '/\((\d+)\)/', '(' . $res[ 0 ] . ')', $view );
				}

				//replace href link, adding the 'lang' argument and the revised count
				$href_args['lang'] = $lang;
				$href_args = array_map( 'urlencode', $href_args );
				$new_href = add_query_arg( $href_args, $href_base );
				$views[ $key ] = preg_replace( '/(href=["\'])([\s\S]+?)(["\'])/', '$1' . $new_href . '$3', $view );
			}
		}

		return $views;
	}

	function language_filter_upload_page()
	{
		global $sitepress, $wpdb;

		//save query arguments for building language-specific links
		$href_args = array();
		foreach( $_GET as $key => $value ) {
			$href_args[ $key ] = urlencode( stripslashes( $value ) );
		}

		//get language code
		if ( isset( $_GET[ 'lang' ] ) ) {
			$lang_code = $_GET[ 'lang' ];
		} else {
			if ( method_exists( $sitepress, 'get_admin_language_cookie' ) ) {
				$lang_code = $sitepress->get_admin_language_cookie();
			}
			if ( empty( $lang_code ) ) {
				$lang_code = $sitepress->get_default_language();
			}
		}

		$active_languages = $sitepress->get_active_languages();

		$active_languages[ ] = array( 'code' => 'all', 'display_name' => __( 'All languages', 'wpml-media' ) );

		$langc[ 'all' ] = 0;
		$language_items = array();
		foreach ( $active_languages as $lang ) {
			//count language-specific attachments
			if ( $lang[ 'code' ] != 'all' ) {
			//select all attachments
			$sql = $wpdb->prepare("
				SELECT COUNT(p.id)
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->prefix}icl_translations AS t
					ON t.element_id = p.id
				WHERE p.post_type = 'attachment'
				AND t.element_type ='post_attachment'
				AND t.language_code=%s
			",$lang['code']);
				//handle trash setting
				if ( isset( $_GET[ 'status' ] ) ) {
					$sql .= " AND p.post_status = 'trash' ";
				} else {
					$sql .= " AND p.post_status != 'trash' ";
				}
			//select detached attachments
			if ( isset( $_GET[ 'detached' ] ) )
				$sql .= " AND p.post_parent = 0 ";
			//select mime type(image,etc) attachments
			if ( isset( $_GET[ 'post_mime_type' ] ) )
					$sql .= wp_post_mime_type_where( $_GET['post_mime_type'], 'p' );
	
				$sql = apply_filters( 'wpml-media_view-upload-page-sql', $sql, $lang );
	
				$res = apply_filters( 'wpml-media_view-upload-page-count', NULL, $lang );
				if ( NULL === $res ) {
			$res = $wpdb->get_col( $sql );
				}

				$langc[ $lang[ 'code' ] ] = $res[ 0 ];
			$langc[ 'all' ] += $res[ 0 ];
			}

			//generation language block
			if ( $lang[ 'code' ] == $lang_code ) {
				$px = '<strong>';
				$sx = ' <span class="count">(' . $langc[ $lang[ 'code' ] ] . ')</span></strong>';
			} else {
				$href_args['lang'] = $lang[ 'code' ];
				$px = '<a href="' . esc_url( add_query_arg( $href_args, '' ) ) . '">';
				$sx = '</a> <span class="count">(' . $langc[ $lang[ 'code' ] ] . ')</span>';
			}
			$language_items[ ] = $px . $lang[ 'display_name' ] . $sx;
		}

		wp_enqueue_script( 'wpml-media-language-options', WPML_MEDIA_URL . '/res/js/language_options.js', array(), WPML_MEDIA_VERSION, true );
		wp_localize_script( 'wpml-media-language-options', 'language_items', $language_items );

	}

	function delete_file( $file )
	{
		if ( $file ) {
			global $wpdb;
			//get file name from full name
			$file_name = $this->get_file_name_without_size_from_full_name( $file );
			//check file name in DB
			$attachment_prepared = $wpdb->prepare( "SELECT pm.meta_id, pm.post_id FROM {$wpdb->postmeta} AS pm WHERE pm.meta_value LIKE %s", array('%'. $file_name));
			$attachment = $wpdb->get_row( $attachment_prepared );
			//if exist return NULL(do not delete physically)
			if ( !empty( $attachment ) ) {
				$file = null;
			}
		}

		return $file;
	}

	public function get_file_name_without_size_from_full_name( $file ) {
		$file_name = preg_replace( '/^(.+)\-\d+x\d+(\.\w+)$/', '$1$2', $file );
		$file_name = preg_replace( '/^[\s\S]+(\/.+)$/', '$1', $file_name );
		$file_name = str_replace( '/', '', $file_name );

		return $file_name;
	}

	/**
	 * @param $ids
	 * @param $target_language
	 *
	 * @return array
	 */
	public function translate_attachment_ids( $ids, $target_language )
	{
		global $sitepress;
		$return_string = false;
		if ( !is_array( $ids ) ) {
			$attachment_ids = explode( ',', $ids );
			$return_string  = true;
		}

		$translated_ids = array();
		if ( !empty( $attachment_ids ) ) {
			foreach ( $attachment_ids as $attachment_id ) {
				//Fallback to the original ID
				$translated_id = $attachment_id;

				//Find the ID translation
				$trid = $sitepress->get_element_trid( $attachment_id, 'post_attachment' );
				if ( $trid ) {
					$id_translations = $sitepress->get_element_translations( $trid, 'post_attachment', false, true );
					foreach ( $id_translations as $language_code => $id_translation ) {
						if ( $language_code == $target_language ) {
							$translated_id = $id_translation->element_id;
							break;
						}
					}
				}

				$translated_ids[ ] = $translated_id;
			}
		}

		if ( $return_string ) {
			return implode( ',', $translated_ids );
		} else {
			return $translated_ids;
		}
	}

	private function get_original_post_id() {
		global $sitepress, $icl_meta_box_globals;
		$source_id = null;
		if ( sizeof( $icl_meta_box_globals[ 'translations' ] ) > 0 ) {
			$translations = $icl_meta_box_globals[ 'translations' ];
			$trans_data   = reset( $translations );
			if ( $trans_data !== false && is_numeric( $trans_data ) ) {
				$post_type = get_post_type( $trans_data );
				$source_id = $sitepress->get_original_element_id( $trans_data, 'post_' . $post_type );
			}
		}

		return $source_id;
	}

	/**
	 * Update query for media-upload.php page.
	 *
	 * @param object $query  WP_Query
	 */
	public function filter_media_upload_items( $query ) {
		$current_lang = $this->sitepress->get_current_language();
		$ids = icl_cache_get( '_media_upload_attachments' . $current_lang );

		if ( false === $ids  ) {
			$tbl = $this->wpdb->prefix . 'icl_translations';
			$db_query = "
				SELECT posts.ID
				FROM {$this->wpdb->posts} as posts, $tbl as icl_translations
				WHERE posts.post_type = 'attachment'
				AND icl_translations.element_id = posts.ID
				AND icl_translations.language_code = %s
				";

			$posts = $this->wpdb->get_results( $this->wpdb->prepare( $db_query, $current_lang ) );
			$ids = array();
			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$ids[] = absint( $post->ID );
				}
			}

			icl_cache_set( '_media_upload_attachments' . $current_lang, $ids );
		}

		$query->set( 'post__in', $ids );
	}

	/**
	 * Invalidate cache when new attachment is created.
	 */
	public function invalidate_cache() {
		global $sitepress;
		$active_languages = $sitepress->get_active_languages();
		foreach ( $active_languages as $active_language ) {
			icl_cache_clear( '_media_upload_attachments' . $active_language['code'] );
		}
	}
}
