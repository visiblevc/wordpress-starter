<?php

class WPML_ST_MO_Downloader{
    const   LOCALES_XML_FILE = 'http://d2pf4b3z51hfy8.cloudfront.net/wp-locales.xml.gz';
    
    const   CONTEXT = 'WordPress';
    private $settings;
    private $xml;
    private $translation_files = array();
    
    
    function __construct(){
        global $wp_version;
        
        // requires Sitepress
        if(!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE) return;
        
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version);

        $this->settings = get_option('icl_adl_settings');
        
        if(empty($this->settings['wp_version']) || version_compare($wpversion, $this->settings['wp_version'], '>')){
            try{
                $this->updates_check(array('trigger' => 'wp-update'));    
            }catch(Exception $e){
                // do nothing - this is automated request for updates
            }
            
        }

		if ( get_transient('WPML_ST_MO_Downloader_lang_map') === false ) {
		   $this->set_lang_map_from_csv();
		}
		$this->lang_map = get_transient('WPML_ST_MO_Downloader_lang_map');
		$this->lang_map_rev = array_flip($this->lang_map);
        
        add_action('wp_ajax_icl_adm_updates_check', array($this, 'show_updates'));
        add_action('wp_ajax_icl_adm_save_preferences', array($this, 'save_preferences'));
        
            
    }

	function set_lang_map_from_csv() {
		$fh = fopen(WPML_ST_PATH . '/inc/lang-map.csv', 'r');
		while(list($locale, $code) = fgetcsv($fh)){
				$this->lang_map[$locale] = $code;            
		}   
		
		if (isset($this->lang_map) && is_array($this->lang_map)) {
			set_transient('WPML_ST_MO_Downloader_lang_map', $this->lang_map);
		}
		
	}

	function updates_check( $args = array() ) {
		global $wp_version, $sitepress;

		$wpversion = preg_replace( '#-(.+)$#', '', $wp_version );
		$trigger   = 'manual';
		extract( $args, EXTR_OVERWRITE );

		$active_languages = $sitepress->get_active_languages();
		$default_language = $sitepress->get_default_language();
		$this->load_xml();
		$this->get_translation_files();
		$updates = array();

		foreach ( $active_languages as $language ) {
			if ( $language != $default_language ) {
				if ( isset( $this->translation_files[ $language[ 'code' ] ] ) ) {
					foreach ( $this->translation_files[ $language[ 'code' ] ] as $project => $info ) {
						$this->settings[ 'translations' ][ $language[ 'code' ] ][ $project ][ 'available' ] = $info[ 'signature' ];
						if ( empty( $this->settings[ 'translations' ][ $language[ 'code' ] ][ $project ][ 'installed' ] ) ||
						     (isset( $info[ 'available' ] ) && $this->settings[ 'translations' ][ $language[ 'code' ] ][ $project ][ 'installed' ] != $info[ 'available' ])
						) {
							$updates[ 'languages' ][ $language[ 'code' ] ][ $project ] = $this->settings[ 'translations' ][ $language[ 'code' ] ][ $project ][ 'available' ];
						}
					}
				}

			}
		}

		$this->settings[ 'wp_version' ] = $wpversion;
		$this->settings[ 'last_time_xml_check' ]         = time();
		$this->settings[ 'last_time_xml_check_trigger' ] = $trigger;
		$this->save_settings();

		return $updates;

	}

	function show_updates() {
		global $sitepress;

		$html = '';

		try {
			$updates = $this->updates_check();
			// filter only core( & admin)
			$updates_core = array();
			if ( array_key_exists( 'languages', $updates ) && ! empty( $updates['languages'] ) ) {
				foreach ( $updates['languages'] as $k => $v ) {
					if ( ! empty( $v['core'] ) ) {
						$updates_core['languages'][ $k ]['core'] = $v['core'];
					}
					if ( ! empty( $v['admin'] ) ) {
						$updates_core['languages'][ $k ]['admin'] = $v['admin'];
					}
				}
			}
			$updates = $updates_core;
			if ( ! empty( $updates ) ) {
				$html .= '<table>';


				foreach ( $updates['languages'] as $language => $projects ) {
					$l = $sitepress->get_language_details( $language );

					if ( ! empty( $projects['core'] ) || ! empty( $projects['admin'] ) ) {

						$vkeys = array();
						foreach ( $projects as $key => $value ) {
							$vkeys[] = $key . '|' . $value;
						}
						$version_key = join( ';', $vkeys );


						$html .= '<tr>';
						$html .= '<td>' . sprintf( __( "Updated %s translation is available", 'wpml-string-translation' ),
								'<strong>' . $l['display_name'] . '</strong>' ) . '</td>';
						$html .= '<td align="right">';
						$html .= '<a href="' . admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&amp;download_mo=' . $language . '&amp;version=' . $version_key ) . '" class="button-secondary">' . __( 'Review changes and update', 'wpml-string-translation' ) . '</a>';
						$html .= '</td>';
						$html .= '<tr>';
						$html .= '</tr>';
					}

				}


				$html .= '</table>';
			} else {
				$html .= __( 'No updates found.', 'wpml-string-translation' );
			}

		} catch ( Exception $error ) {
			$html .= '<span style="color:#f00" >' . $error->getMessage() . '</span>';
		}

		echo json_encode( array( 'html' => $html ) );
		exit;
	}
    
    function save_preferences(){
        global $sitepress;
        
        $iclsettings['st']['auto_download_mo'] = @intval($_POST['auto_download_mo']);
        $iclsettings['hide_upgrade_notice'] = implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3));
	    $sitepress->save_settings($iclsettings);
	    if ( $iclsettings['st']['auto_download_mo'] ) {
		    try {
			    $this->updates_check( array( 'trigger' => 'setting-changed' ) );
		    } catch( Exception $e ) {
	        }
	    }
        wp_send_json_success( array('enabled' => $iclsettings['st']['auto_download_mo'] ) );
    }
    
    function save_settings(){
        update_option('icl_adl_settings', $this->settings);
    }
    
    function get_option($name){
        return isset($this->settings[$name]) ? $this->settings[$name] : null;
    }
    
    function load_xml(){
        if(!class_exists('WP_Http')) include_once ABSPATH . WPINC . '/class-http.php';
        $client = new WP_Http();
        $response = $client->request(self::LOCALES_XML_FILE, array('timeout'=>15, 'decompress'=>false));
        
        if(is_wp_error($response) || !in_array($response['response']['code'], array(200, 301, 300))){
			$load_xml_error_message = '';
			if (isset($response->errors)){
				$errors = '';
				foreach($response->errors as $error => $error_messages) {
					$errors .= $error . '<br/>';
					foreach($error_messages as $error_message) {
						$errors .= '- ' . $error_message . '<br/>';
					}
				}
				$load_xml_error_message .= sprintf(__('Failed downloading the language information file.', 'wpml-string-translation'), $errors);
				$load_xml_error_message .= '<br/>' . sprintf(__('Errors: %s', 'wpml-string-translation'), $errors);
			} else {
				$load_xml_error_message .= __('Failed downloading the language information file. Please go back and try a little later.', 'wpml-string-translation');
			}

			if(isset($response) && !is_wp_error($response) && isset($response['response'])) {
				$load_xml_error_message .= '<br/>Response: ' . $response['response']['code'] . ' ('. $response['response']['message'] . ').';
			}

			$this->xml = false;

			throw new Exception($load_xml_error_message);
		} elseif($response['response']['code'] == 200){
            require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
			$this->xml = new SimpleXMLElement(icl_gzdecode($response['body']));
		}
    }
    
    function get_mo_file_urls($wplocale){
		if(!$this->xml) return false;

        global $wp_version;        
        
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version)   ;
        $wpversion = join('.', array_slice(explode('.', $wpversion), 0, 2)) . '.x';
        
        $exp = explode('-', $wplocale);
        $language = $exp[0];
        $locale = isset($exp[1]) ? $wplocale : $language;
        
        $mo_files = array();
        
        $projects = $this->xml->xpath($language . '/' . $locale);
        if(!empty($projects)){
            
            $project_names = array();
            foreach($projects[0] as $project_name => $data){
                // subprojects
                if(empty($data->versions)){
                    $subprojects = $this->xml->xpath($language . '/' . $locale . '/' . $project_name);
                    if(!empty($subprojects)){
                        foreach($subprojects[0] as $sub_project_name => $sdata){
                            $project_names[] = $project_name . '/' . $sub_project_name ;    
                        }    
                    }
                }else{
                    $project_names[] = $project_name;
                }
            }
            
            if(!empty($project_names)){
                foreach($project_names as $project_name){
                    // try to get the corresponding version
                    $locv_path = $this->xml->xpath("{$language}/{$locale}/{$project_name}/versions/version[@number=\"" . $wpversion . "\"]");
                    // try to get the dev recent version
                    if(empty($locv_path)){
                        $locv_path = $this->xml->xpath("{$language}/{$locale}/{$project_name}/versions/version[@number=\"dev\"]");
                    }
                    if(!empty($locv_path)){
                        $mo_files[$project_name]['url']         = (string)$locv_path[0]->url;
                        $mo_files[$project_name]['signature']   = (string)$locv_path[0]['signature'];
                        $mo_files[$project_name]['translated']  = (string)$locv_path[0]['translated'];
                        $mo_files[$project_name]['untranslated']= (string)$locv_path[0]['untranslated'];                    
                    }                    
                }
            }
        }
        
        return $mo_files;
    }
    
    function get_translation_files(){
        global $sitepress;
        
        $active_languages = $sitepress->get_active_languages();

        foreach($active_languages as $language){            
            $locale = $sitepress->get_locale($language['code']);
            if(!isset($this->lang_map[$locale])) continue;
            $wplocale = $this->lang_map[$locale];
            
            $urls = $this->get_mo_file_urls($wplocale);            
            
            if(!empty($urls)){
                $this->translation_files[$language['code']] = $urls;    
            }
        }
                
        return $this->translation_files;
    }
    
    function get_translations($language, $args = array()){
        global $wpdb;

        $translations = array();
	    $types  = array('core');
        
        extract($args, EXTR_OVERWRITE);

        if(!class_exists('WP_Http')) include_once ABSPATH . WPINC . '/class-http.php';
        $client = new WP_Http();
        
        foreach($types as $type){
            
            if(isset($this->translation_files[$language][$type]['url'])){
            
                $response = $client->request($this->translation_files[$language][$type]['url'], array('timeout'=>15));
                
                if(is_wp_error($response)){
                    $err = __('Error getting the translation file. Please go back and try again.', 'wordpress-language');
                    if(isset($response->errors['http_request_failed'][0])){
                        $err .= '<br />' . $response->errors['http_request_failed'][0];
                    }
                    echo '<div class="error"><p>' . $err . '</p></div>';
                    return false;
                    
                }        
                
                $mo = new MO();
                $pomo_reader = new POMO_StringReader($response['body']);
                $mo->import_from_reader( $pomo_reader );
	            $data            = $wpdb->get_results( $wpdb->prepare( "
                            SELECT st.value, s.name, s.gettext_context
                            FROM {$wpdb->prefix}icl_string_translations st
                            JOIN {$wpdb->prefix}icl_strings s ON st.string_id = s.id
                            WHERE s.context = %s AND st.language = %s
							",
		            self::CONTEXT,
		            $language ) );
	            $string_existing = array();
	            foreach ( $data as $row ) {
		            $string_existing[ md5( $row->name . $row->gettext_context ) ] = $row->value;
	            }

                foreach($mo->entries as $key=>$v){
                    
                    $tpairs = array();
                    $v->singular = str_replace("\n",'\n', $v->singular);
                    $tpairs[] = array(
                        'string'          => $v->singular, 
                        'translation'     => $v->translations[0],
                        'name'            => md5($v->singular),
						'gettext_context' => $v->context
                    );
                    
                    if($v->is_plural){
                        $v->plural = str_replace("\n",'\n', $v->plural);
                        $tpairs[] = array(
                            'string'          => $v->plural, 
                            'translation'     => !empty($v->translations[1]) ? $v->translations[1] : $v->translations[0],
                            'name'            => md5($v->plural),
							'gettext_context' => $v->context
                        );
                    }
                    
                    foreach($tpairs as $pair){
		                $key                  = md5( $pair['name'] . $pair['gettext_context'] );
		                $existing_translation = isset( $string_existing[ $key ] ) ? $string_existing[ $key ] : null;
                        
                        if(empty($existing_translation)){
                            $translations['new'][] = array(
                                                    'string'          => $pair[ 'string' ],
                                                    'translation'     => '',
                                                    'new'             => $pair[ 'translation' ],
                                                    'name'            => $pair[ 'name' ],
													'gettext_context' => $pair[ 'gettext_context' ]
                            );
                        }else{
                            
                            if(strcmp($existing_translation, $pair['translation']) !== 0){
                                $translations['updated'][] = array(
                                                    'string'          => $pair[ 'string' ],
                                                    'translation'     => $existing_translation,
                                                    'new'             => $pair[ 'translation' ],
                                                    'name'            => $pair[ 'name' ],
													'gettext_context' => $pair[ 'gettext_context' ]
                                );
                            }
                            
                        }
                    } 
                }
            }
        }
        
        return $translations;
    }
    
    function save_translations($data, $language, $version = false){
        
       set_time_limit(0);
        
        if(false === $version){
            global $wp_version;        
            $version = preg_replace('#-(.+)$#', '', $wp_version)   ;            
        }    
        
        foreach( $data as $key => $string ) {
			if ( $string[ 'gettext_context' ] ) {
				$string_context = array(
										'domain' => self::CONTEXT,
										'context' => $string[ 'gettext_context' ]
										);
			} else {
				$string_context = self::CONTEXT;
			}
            $string_id = icl_register_string( $string_context, $string[ 'name' ], $string[ 'string' ] );
            if( $string_id ) {
                icl_add_string_translation( $string_id, $language, $string[ 'translation' ], ICL_TM_COMPLETE );
            }
        }    
        
        
        $version_projects = explode(';', $version);
        foreach($version_projects as $project){
            $exp = explode('|', $project);
            $this->settings['translations'][$language][$exp[0]]['time'] = time();
            $this->settings['translations'][$language][$exp[0]]['installed'] = $exp[1];
        }        
        
        $this->save_settings();
        
    }

}
