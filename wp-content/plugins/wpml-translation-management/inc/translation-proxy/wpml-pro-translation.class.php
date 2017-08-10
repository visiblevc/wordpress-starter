<?php
/**
 * @package wpml-core
 * @package wpml-core-pro-translation
 */

/**
 * Class WPML_Pro_Translation
 */
class WPML_Pro_Translation extends WPML_TM_Job_Factory_User {

	public $errors = array();
	/** @var TranslationManagement $tmg */
	private $tmg;

	/** @var  WPML_TM_CMS_ID $cms_id_helper */
	private $cms_id_helper;

	/** @var WPML_TM_Xliff_Reader_Factory $xliff_reader_factory */
	private $xliff_reader_factory;


	private $sitepress;

	private $update_pm;
	/**
	 * WPML_Pro_Translation constructor.
	 *
	 * @param WPML_Translation_Job_Factory $job_factory
	 */
	function __construct( &$job_factory ) {
		parent::__construct( $job_factory );
		global $iclTranslationManagement, $wpdb, $sitepress, $wpml_post_translations, $wpml_term_translations;
		
		$this->tmg                               =& $iclTranslationManagement;
		$this->xliff_reader_factory              = new WPML_TM_Xliff_Reader_Factory( $this->job_factory );
		$wpml_tm_records                         = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$this->cms_id_helper                     = new WPML_TM_CMS_ID( $wpml_tm_records, $job_factory );
		$this->sitepress                         = $sitepress;
		
		add_filter( 'xmlrpc_methods', array( $this, 'custom_xmlrpc_methods' ) );
		add_action( 'post_submitbox_start', array(
			$this,
			'post_submitbox_start'
		) );
		add_action( 'icl_ajx_custom_call', array(
			$this,
			'ajax_calls'
		), 10, 2 );

		$this->update_pm = new WPML_Update_PickUp_Method( $this->sitepress );
	}

	/**
	 * @return WPML_TM_CMS_ID
	 */
	public function &get_cms_id_helper() {
		return $this->cms_id_helper;
	}

	/**
	 * @param string $call
	 * @param array $data
	 */
	function ajax_calls( $call, $data ) {
		switch ( $call ) {
			case 'set_pickup_mode':
				$response = $this->update_pm->update_pickup_method( $data, $this->get_current_project() );
				if ( 'no-ts' === $response ) {
					wp_send_json_error( array( 'message' => __( 'Please activate translation service first.', 'sitepress' ) ) );
				}
				if ( 'cant-update' === $response ) {
					wp_send_json_error( array( 'message' => __( 'Could not update the translation pickup mode.', 'sitepress' ) ) );
				}

				wp_send_json_success( array( 'message' => __( 'Ok', 'sitepress' ) ) );
				break;
		}
	}

	public function get_current_project(){
		return TranslationProxy::get_current_project();
	}

	/**
	 * @param WP_Post|WPML_Package $post
	 * @param array                $target_languages
	 * @param int                  $translator_id
	 * @param int                  $job_id
	 *
*@return bool|int
	 */
	function send_post( $post, $target_languages, $translator_id, $job_id ) {
		/** @var TranslationManagement $iclTranslationManagement */
		global $sitepress, $iclTranslationManagement;

		$this->maybe_init_translation_management( $iclTranslationManagement );

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}
		if ( ! $post ) {
			return false;
		}

		$post_id             = $post->ID;
		$post_type           = $post->post_type;
		$element_type_prefix = $iclTranslationManagement->get_element_type_prefix_from_job_id( $job_id );
		$element_type        = $element_type_prefix . '_' . $post_type;

		$note = get_post_meta( $post_id, '_icl_translator_note', true );
		if ( ! $note ) {
			$note = null;
		}
		$err             = false;
		$res             = false;
		$source_language = $sitepress->get_language_for_element( $post_id, $element_type );
		$target_language = is_array( $target_languages ) ? end( $target_languages ) : $target_languages;
		if ( empty( $target_language ) || $target_language === $source_language ) {
			return false;
		}
		$translation = $this->tmg->get_element_translation( $post_id, $target_language, $element_type );
		if ( ! $translation ) { // translated the first time
			$err = true;
		}
		if ( ! $err && ( $translation->needs_update || $translation->status == ICL_TM_NOT_TRANSLATED || $translation->status == ICL_TM_WAITING_FOR_TRANSLATOR ) ) {
			$project = TranslationProxy::get_current_project();

			if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
				$job_object = new WPML_External_Translation_Job( $job_id );
			} else {
				$job_object = new WPML_Post_Translation_Job( $job_id );
				$job_object->load_terms_from_post_into_job();
			}

			list( $err, $project, $res ) = $job_object->send_to_tp( $project, $translator_id, $this->cms_id_helper, $this->tmg, $note );
			if ( $err ) {
				$this->enqueue_project_errors( $project );
			}

		}

		return $err ? false : $res; //last $ret
	}

	function server_languages_map( $language_name, $server2plugin = false ) {
		if ( is_array( $language_name ) ) {
			return array_map( array( $this, 'server_languages_map' ), $language_name );
		}
		$map = array(
			'Norwegian Bokmål'     => 'Norwegian',
			'Portuguese, Brazil'   => 'Portuguese',
			'Portuguese, Portugal' => 'Portugal Portuguese'
		);

		$map = $server2plugin ? array_flip( $map ) : $map;

		return isset( $map[ $language_name ] ) ? $map[ $language_name ] : $language_name;
	}

	/**
	 * @param $methods
	 *
	 * @return array
	 */
	function custom_xmlrpc_methods( $methods ) {
		$icl_methods['translationproxy.test_xmlrpc']        = '__return_true';
		$icl_methods['translationproxy.updated_job_status'] = array(
			$this,
			'xmlrpc_updated_job_status_with_log_method',
		);
		$methods                                            = array_merge( $methods, $icl_methods );
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST && preg_match( '#<methodName>([^<]+)</methodName>#i', $this->sitepress->get_wp_api()->get_raw_post_data(), $matches ) ) {
				$method = $matches[1];
			if ( array_key_exists( $method, $icl_methods ) ) {
				set_error_handler( array( $this, 'translation_error_handler' ), E_ERROR | E_USER_ERROR );
			}
		}

		return $methods;
	}

	/**
	 * @param array $args
	 *
	 * @param bool $bypass_auth
	 *
	 * @return int|string
	 */
	function xmlrpc_updated_job_status_with_log_method( $args, $bypass_auth = false ) {
		//XML-RPC requests happen here
		return $this->xmlrpc_updated_job_status_with_log( $args, $bypass_auth );
	}

	/**
	 * @param array $args
	 * @param bool  $bypass_auth
	 *
	 * @return int|string
	 */
	function xmlrpc_updated_job_status_with_log( $args, $bypass_auth = false ) {
		$project = TranslationProxy::get_current_project();
		$update  = new WPML_TM_Job_Update( $this, $project );

		$ret = $update->updated_job_status_with_log( $args, $bypass_auth );

		if ( null !== $update->get_last_job_data() ) {
			$logger_settings = new WPML_Jobs_Fetch_Log_Settings();
			$fetch_log_job   = new WPML_Jobs_Fetch_Log_Job( $this );
			$logger          = new WPML_Jobs_XMLRPC_Fetch_Log( $this, $logger_settings, $fetch_log_job );

			$logger->log_job_data( $update->get_last_job_data() );
		}

		return $ret;
	}

	/**
	 * @param array $args
	 * @param bool  $bypass_auth
	 *
	 * @return int|string
	 */
	function poll_updated_job_status_with_log( $args, $bypass_auth = false ) {
		$project = TranslationProxy::get_current_project();
		$update  = new WPML_TM_Job_Update( $this, $project );

		$ret = $update->updated_job_status_with_log( $args, $bypass_auth );

		if ( null !== $update->get_last_job_data() ) {
			$logger_settings = new WPML_Jobs_Fetch_Log_Settings();
			$fetch_log_job   = new WPML_Jobs_Fetch_Log_Job( $this );
			$logger          = new WPML_Jobs_Poll_Fetch_Log( $this, $logger_settings, $fetch_log_job );

			$logger->log_job_data( $update->get_last_job_data() );
		}

		return $ret;
	}

	/**
	 * @return WPML_WP_API
	 */
	function get_wpml_wp_api() {
		return $this->sitepress->get_wp_api();
	}

	/**
	 *
	 * Cancel translation for given cms_id
	 *
	 * @param $rid
	 * @param $cms_id
	 *
	 * @return bool
	 */
	function cancel_translation( $rid, $cms_id ) {
		/**
		 * @var WPML_String_Translation $WPML_String_Translation
		 * @var TranslationManagement $iclTranslationManagement
		 */
		global $sitepress, $wpdb, $WPML_String_Translation, $iclTranslationManagement;

		$res = false;
		if ( empty( $cms_id ) ) { // it's a string
			if ( isset( $WPML_String_Translation ) ) {
				$res = $WPML_String_Translation->cancel_remote_translation( $rid );
			}
		} else {
			$cms_id_parts = $this->cms_id_helper->parse_cms_id( $cms_id );
			$post_type    = $cms_id_parts[0];
			$_element_id  = $cms_id_parts[1];
			$_target_lang = $cms_id_parts[3];
			$job_id       = isset( $cms_id_parts[4] ) ? $cms_id_parts[4] : false;

			$element_type_prefix = 'post';
			if ( $job_id ) {
				$element_type_prefix = $iclTranslationManagement->get_element_type_prefix_from_job_id( $job_id );
			}

			$element_type = $element_type_prefix . '_' . $post_type;
			if ( $_element_id && $post_type && $_target_lang ) {
				$trid = $sitepress->get_element_trid( $_element_id, $element_type );
			} else {
				$trid = null;
			}

			if ( $trid ) {
				$translation_id_query   = "SELECT i.translation_id
																FROM {$wpdb->prefix}icl_translations i
																JOIN {$wpdb->prefix}icl_translation_status s
																ON i.translation_id = s.translation_id
																WHERE i.trid=%d
																	AND i.language_code=%s
																	AND s.status IN (%d, %d)
																LIMIT 1";
				$translation_id_args    = array(
					$trid,
					$_target_lang,
					ICL_TM_IN_PROGRESS,
					ICL_TM_WAITING_FOR_TRANSLATOR
				);
				$translation_id_prepare = $wpdb->prepare( $translation_id_query, $translation_id_args );
				$translation_id         = $wpdb->get_var( $translation_id_prepare );

				if ( $translation_id ) {
					global $iclTranslationManagement;
					$iclTranslationManagement->cancel_translation_request( $translation_id );
					$res = true;
				}
			}
		}

		return $res;
	}

	/**
	 *
	 * Downloads translation from TP and updates its document
	 *
	 * @param $translation_proxy_job_id
	 * @param $cms_id
	 *
	 * @return bool|string
	 *
	 */
	function download_and_process_translation( $translation_proxy_job_id, $cms_id ) {
		global $wpdb;

		if ( empty( $cms_id ) ) { // it's a string
			//TODO: [WPML 3.3] this should be handled as any other element type in 3.3
			$target = $wpdb->get_var( $wpdb->prepare( "SELECT target FROM {$wpdb->prefix}icl_core_status WHERE rid=%d", $translation_proxy_job_id ) );

			return $this->process_translated_string( $translation_proxy_job_id, $target );
		} else {
			$translation_id = $this->cms_id_helper->get_translation_id( $cms_id, TranslationProxy::get_current_service() );

			return ! empty ( $translation_id ) && $this->add_translated_document( $translation_id, $translation_proxy_job_id );
		}
	}

	/**
	 * @param int $translation_id
	 * @param int $translation_proxy_job_id
	 *
	 * @return bool
	 */
	function add_translated_document( $translation_id, $translation_proxy_job_id ) {
		global $wpdb, $sitepress;
		$project = TranslationProxy::get_current_project();

		$translation_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $translation_id ) );
		$translation      = $project->fetch_translation( $translation_proxy_job_id );
		if ( ! $translation ) {
			$this->errors = array_merge( $this->errors, $project->errors );
		} else {
			$translation = apply_filters( 'icl_data_from_pro_translation', $translation );
		}
		$ret = true;

		if ( ! empty( $translation ) && strpos( $translation, 'xliff' ) !== false ) {
			try {
				/** @var $job_xliff_translation WP_Error|array */
				$job_xliff_translation = $this->xliff_reader_factory
					->general_xliff_import()->import( $translation, $translation_id );
				if ( is_wp_error( $job_xliff_translation ) ) {
					$this->add_error( $job_xliff_translation->get_error_message() );

					return false;
				}
				wpml_tm_save_data( $job_xliff_translation );
				$translations = $sitepress->get_element_translations( $translation_info->trid, $translation_info->element_type, false, true, true );
				if ( isset( $translations[ $translation_info->language_code ] ) ) {
					$translation = $translations[ $translation_info->language_code ];
					if ( isset( $translation->element_id ) && $translation->element_id ) {
						$translation_post_type_prepared = $wpdb->prepare( "SELECT post_type FROM $wpdb->posts WHERE ID=%d", array( $translation->element_id ) );
						$translation_post_type          = $wpdb->get_var( $translation_post_type_prepared );
					} else {
						$translation_post_type = implode( '_', array_slice( explode( '_', $translation_info->element_type ), 1 ) );
					}
					if ( $translation_post_type == 'page' ) {
						$url = get_option( 'home' ) . '?page_id=' . $translation->element_id;
					} else {
						$url = get_option( 'home' ) . '?p=' . $translation->element_id;
					}
					$project->update_job( $translation_proxy_job_id, $url );
				} else {
					$project->update_job( $translation_proxy_job_id );
				}
			} catch ( Exception $e ) {
				$ret = false;
			}
		}

		return $ret;
	}

	function _content_get_link_paths($body) {

		$regexp_links = array(
			/*"/<a.*?href\s*=\s*([\"\']??)([^\"]*)[\"\']>(.*?)<\/a>/i",*/
			"/<a[^>]*href\s*=\s*([\"\']??)([^\"^>]+)[\"\']??([^>]*)>/i",
		);

		$links = array();

		foreach($regexp_links as $regexp) {
			if (preg_match_all($regexp, $body, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$links[] = $match;
				}
			}
		}
		return $links;
	}

	public static function _content_make_links_sticky( $element_id, $element_type = 'post', $string_translation = true ) {

		require_once ICL_PLUGIN_PATH . '/inc/absolute-links/absolute-links.class.php';
		$icl_abs_links = new AbsoluteLinks;

		if ( strpos( $element_type, 'post' ) === 0 ) {
			$icl_abs_links->process_post( $element_id );
		} elseif($element_type=='string') {
			$icl_abs_links->process_string( $element_id, $string_translation );
		}
	}

	public function fix_links_to_translated_content( $element_id, $target_lang_code, $element_type = 'post' ){
		global $wpdb, $sitepress, $wp_taxonomies;
		self::_content_make_links_sticky( $element_id, $element_type );

		$current_language = $sitepress->get_current_language();
		$sitepress->switch_lang( $target_lang_code );
		
		$wpml_element_type = $element_type;
		$body = false;
		if(strpos($element_type, 'post') === 0){
			$post_prepared = $wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID=%d", array($element_id));
			$post = $wpdb->get_row($post_prepared);
			$body = $post->post_content;
			$wpml_element_type = 'post_' . $post->post_type;
		}elseif($element_type=='string'){
			$body_prepared = $wpdb->prepare("SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE id=%d", array($element_id));
			$body = $wpdb->get_var($body_prepared);
		}
		$new_body = $body;

		$base_url_parts = parse_url(site_url());

		$links = $this->_content_get_link_paths($body);

		$all_links_fixed = 1;

		$pass_on_query_vars = array();
		$pass_on_fragments = array();

		$all_links_arr = array();

		foreach($links as $link_idx => $link) {
			$path = $link[2];
			$url_parts = parse_url($path);

			if(isset($url_parts['fragment'])){
				$pass_on_fragments[$link_idx] = $url_parts['fragment'];
			}

			if((!isset($url_parts['host']) or $base_url_parts['host'] == $url_parts['host']) and
				(!isset($url_parts['scheme']) or $base_url_parts['scheme'] == $url_parts['scheme']) and
				isset($url_parts['query'])) {
				$query_parts = explode('&', $url_parts['query']);

				foreach($query_parts as $query){
					// find p=id or cat=id or tag=id queries
					list($key, $value) = explode('=', $query);
					$translations = NULL;
					$is_tax = false;
					$kind = false;
					$taxonomy = false;
					if($key == 'p'){
						$kind = 'post_' . $wpdb->get_var( $wpdb->prepare("SELECT post_type
																		  FROM {$wpdb->posts}
																		  WHERE ID = %d ",
								$value));
					} else if($key == "page_id"){
						$kind = 'post_page';
					} else if($key == 'cat' || $key == 'cat_ID'){
						$kind = 'tax_category';
						$taxonomy = 'category';
					} else if($key == 'tag'){
						$is_tax = true;
						$taxonomy = 'post_tag';
						$kind = 'tax_' . $taxonomy;
						$value = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id
																FROM {$wpdb->terms} t
                                                                JOIN {$wpdb->term_taxonomy} x
                                                                  ON t.term_id = x.term_id
                                                                WHERE x.taxonomy = %s
                                                                  AND t.slug = %s", $taxonomy, $value ) );
					} else {
						$found = false;
						foreach($wp_taxonomies as $taxonomy_name => $taxonomy_object){
							if($taxonomy_object->query_var && $key == $taxonomy_object->query_var){
								$found = true;
								$is_tax = true;
								$kind = 'tax_' . $taxonomy_name;
								$value = $wpdb->get_var($wpdb->prepare("
                                    SELECT term_taxonomy_id
                                    FROM {$wpdb->terms} t
                                    JOIN {$wpdb->term_taxonomy} x
                                      ON t.term_id = x.term_id
                                    WHERE x.taxonomy = %s
                                      AND t.slug = %s",
                                    $taxonomy_name, $value ));
                                $taxonomy = $taxonomy_name;
                            }                        
                        }
                        if(!$found){
                            $pass_on_query_vars[$link_idx][] = $query;
                            continue;
                        } 
                    }

                    $link_id = (int)$value;  
                    
                    if (!$link_id) {
                        continue;
                    }

                    $trid = $sitepress->get_element_trid($link_id, $kind);
                    if(!$trid){
                        continue;
                    }
                    if($trid !== NULL){
                        $translations = $sitepress->get_element_translations($trid, $kind, false, false, true );
                    }
                    if(isset($translations[$target_lang_code]) && $translations[$target_lang_code]->element_id != null){
                        
                        // use the new translated id in the link path.
                        
                        $translated_id = $translations[$target_lang_code]->element_id;
                        
                        if($is_tax){
                            $translated_id = $wpdb->get_var($wpdb->prepare("SELECT slug
																			FROM {$wpdb->terms} t
																			JOIN {$wpdb->term_taxonomy} x
																				ON t.term_id=x.term_id
																			WHERE x.term_taxonomy_id = %d",
								$translated_id));
						}

						// if absolute links is not on turn into WP permalinks
						if ( $this->should_links_be_converted_back_to_permalinks( $element_type ) ) {
							////////
							$replace = false;
							if(preg_match('#^post_#', $kind)){
								$replace = get_permalink($translated_id);
							}elseif(preg_match('#^tax_#', $kind)){
								if ( is_numeric( $translated_id ) ) {
									$translated_id = (int) $translated_id;
								}
								$replace = get_term_link($translated_id, $taxonomy);
							}
							$new_link = str_replace($link[2], $replace, $link[0]);

							$replace_link_arr[$link_idx] = array('from'=> $link[2], 'to'=>$replace);
						}else{
							$replace = $key . '=' . $translated_id;
							$new_link = $link[0];
							if($replace) {
								$new_link = str_replace($query, $replace, $link[0]);
							}

							$replace_link_arr[$link_idx] = array('from'=> $query, 'to'=>$replace);
						}

						// replace the link in the body.
						// $new_body = str_replace($link[0], $new_link, $new_body);
						$all_links_arr[$link_idx] = array('from'=> $link[0], 'to'=>$new_link);
						// done in the next loop

					} else {
						// translation not found for this.
						$all_links_fixed = 0;
					}
				}
			}

		}

		if ( ! empty( $replace_link_arr ) ) {
			foreach ( $replace_link_arr as $link_idx => $rep ) {
				$rep_to   = $rep['to'];
				$fragment = '';

				// if sticky links is not ON, fix query parameters and fragments
				if ( $this->should_links_be_converted_back_to_permalinks( $element_type ) ) {
					if ( ! empty( $pass_on_fragments[ $link_idx ] ) ) {
						$fragment = '#' . $pass_on_fragments[ $link_idx ];
					}
					if ( ! empty( $pass_on_query_vars[ $link_idx ] ) ) {
						$url_glue = ( strpos( $rep['to'], '?' ) === false ) ? '?' : '&';
						$rep_to   = $rep['to'] . $url_glue . join( '&', $pass_on_query_vars[ $link_idx ] );
					}
				}

				$all_links_arr[ $link_idx ]['to'] = str_replace( $rep['to'], $rep_to . $fragment, $all_links_arr[ $link_idx ]['to'] );

			}
		}

		if(!empty($all_links_arr))
			foreach($all_links_arr as $link){
				$new_body = str_replace($link['from'], $link['to'], $new_body);
			}

		if ( $new_body != $body ){
			if ( strpos( $element_type, 'post' ) === 0 ) {
				$wpdb->update( $wpdb->posts, array( 'post_content' => $new_body ), array( 'ID' => $element_id) );
            } elseif ($element_type == 'string') {
                $wpdb->update( $wpdb->prefix . 'icl_string_translations', array( 'value' => $new_body ), array( 'id' => $element_id ) );
            }
        }
		
		$links_fixed_status_factory = new WPML_Links_Fixed_Status_Factory( $wpdb, new WPML_WP_API() );
		$links_fixed_status = $links_fixed_status_factory->create( $element_id, $wpml_element_type );
		$links_fixed_status->set( $all_links_fixed );

		$sitepress->switch_lang( $current_language );

		return sizeof( $all_links_arr );
        
    }

	function translation_error_handler($error_number, $error_string, $error_file, $error_line){
		switch($error_number){
			case E_ERROR:
			case E_USER_ERROR:
				throw new Exception ($error_string . ' [code:e' . $error_number . '] in '. $error_file . ':' . $error_line);
			case E_WARNING:
			case E_USER_WARNING:
				return true;
			default:
				return true;
		}

	}

	function should_links_be_converted_back_to_permalinks( $element_type ) {
		return 'string' === $element_type || empty( $GLOBALS['WPML_Sticky_Links'] );
	}
	
	function post_submitbox_start(){
		global $post, $iclTranslationManagement;
		if(empty($post)|| !$post->ID){
			return;
		}

		$translations = $iclTranslationManagement->get_element_translations($post->ID, 'post_' . $post->post_type);
		$show_box = 'display:none';
		foreach($translations as $t){
			if($t->element_id == $post->ID){
				return;
			}
			if($t->status == ICL_TM_COMPLETE && !$t->needs_update){
				$show_box = '';
				break;
			}
		}

		echo '<p id="icl_minor_change_box" style="float:left;padding:0;margin:3px;'.$show_box.'">';
		echo '<label><input type="checkbox" name="icl_minor_edit" value="1" style="min-width:15px;" />&nbsp;';
		echo __('Minor edit - don\'t update translation','sitepress');
		echo '</label>';
		echo '<br clear="all" />';
		echo '</p>';
	}

	private function process_translated_string( $translation_proxy_job_id, $language ) {
		$project     = TranslationProxy::get_current_project();
		$translation = $project->fetch_translation( $translation_proxy_job_id );
		$translation = apply_filters( 'icl_data_from_pro_translation', $translation );
		$ret         = false;
		$translation = $this->xliff_reader_factory->string_xliff_reader()->get_data( $translation );
		if ( $translation ) {
			$ret = icl_translation_add_string_translation( $translation_proxy_job_id, $translation, $language );
			if ( $ret ) {
				$project->update_job( $translation_proxy_job_id );
			}
		}

		return $ret;
	}

	private function add_error( $project_error ) {
		$this->errors[] = $project_error;
	}

	/**
	 * @param $project TranslationProxy_Project
	 */
	function enqueue_project_errors( $project ) {
		if ( isset( $project ) && isset( $project->errors ) && $project->errors ) {
			foreach ( $project->errors as $project_error ) {
				$this->add_error( $project_error );
			}
		}
	}

	/**
	 * @param TranslationManagement $iclTranslationManagement
	 */
	private function maybe_init_translation_management( $iclTranslationManagement ) {
		if ( empty( $this->tmg->settings ) ) {
			$iclTranslationManagement->init();
		}
	}
}
