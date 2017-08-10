<?php
function icl_upgrade_2_0_0_steps($step, $stepper){
    global $wpdb, $sitepress, $wp_post_types, $sitepress_settings;

    if(!isset($sitepress)) $sitepress = new SitePress;

    $TranslationManagement = new TranslationManagement;

	$default_language = $sitepress->get_default_language();

    define('ICL_TM_DISABLE_ALL_NOTIFICATIONS', true); // make sure no notifications are being sent

    //if(defined('icl_upgrade_2_0_0_runonce')){
    //  return;
    //}
    //define('icl_upgrade_2_0_0_runonce', true);

    // fix source_language_code
    // assume that the lowest element_id is the source language
    ini_set('max_execution_time', 300);

    $post_types = array_keys($wp_post_types);
    foreach($post_types as $pt){
        $types[] = 'post_' . $pt;
    }

    $temp_upgrade_data = get_option('icl_temp_upgrade_data',
            array('step' => 0, 'offset' => 0));

    switch($step) {

        case 1:
            // if the tables are missing, call the plugin activation routine
            $table_name = $wpdb->prefix.'icl_translation_status';
            if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
                icl_sitepress_activate();
            }

            $wpdb->query("ALTER TABLE `{$wpdb->prefix}icl_translations` CHANGE `element_type` `element_type` VARCHAR( 32 ) NOT NULL DEFAULT 'post_post'");
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}icl_translations` CHANGE `element_id` `element_id` BIGINT( 20 ) NULL DEFAULT NULL ");

            
            // fix source_language_code
            // all source documents must have null
			if ( isset( $types ) ) {
				$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}icl_translations SET source_language_code = NULL
					WHERE element_type IN('".join("','", $types)."') AND source_language_code = '' AND language_code=%s", $default_language ));
				// get translated documents with missing source language
				$res = $wpdb->get_results($wpdb->prepare("
					SELECT translation_id, trid, language_code
					FROM {$wpdb->prefix}icl_translations
					WHERE (source_language_code = '' OR source_language_code IS NULL)
						AND element_type IN('".join("','", $types)."')
						AND language_code <> %s
						", $default_language
					));
				foreach($res as $row){
					$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}icl_translations SET source_language_code = %s WHERE translation_id=%d", $default_language, $row->translation_id));
				}
			}

            $temp_upgrade_data['step'] = 2;
            update_option('icl_temp_upgrade_data', $temp_upgrade_data);

            return array('message' => __('Processing translations...', 'sitepress'));
            break;
            
        case 2:
        
            $limit = 100;
            $offset = $temp_upgrade_data['offset'];
            $processing = FALSE;


            //loop existing translations
			if ( isset( $types ) ) {
				$res = $wpdb->get_results( $wpdb->prepare(
								"SELECT * FROM {$wpdb->prefix}icl_translations
                                 WHERE element_type IN(" . wpml_prepare_in( $types ) . " )
                                    AND source_language_code IS NULL LIMIT %d  OFFSET %d", array($limit, $offset)
												)); 
				foreach( $res as $row){
					$processing = TRUE;
					// grab translations
					$translations = $sitepress->get_element_translations($row->trid, $row->element_type);

					$md5 = 0;
					$table_name = $wpdb->prefix.'icl_node';
					if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name){
						list($md5, $links_fixed) = $wpdb->get_row($wpdb->prepare("
							SELECT md5, links_fixed FROM {$wpdb->prefix}icl_node
							WHERE nid = %d
						", $row->element_id), ARRAY_N);
					}
					if(!$md5){
						$md5 = $TranslationManagement->post_md5($row->element_id);
					}

					$translation_package = $TranslationManagement->create_translation_package($row->element_id);


					foreach($translations as $lang => $t){
						if(!$t->original){

							// determine service and status
							$service = 'local';
							$needs_update = 0;

							list($rid, $status, $current_md5) = $wpdb->get_row($wpdb->prepare("
								SELECT c.rid, n.status , c.md5
								FROM {$wpdb->prefix}icl_content_status c
									JOIN {$wpdb->prefix}icl_core_status n ON c.rid = n.rid
								WHERE c.nid = %d AND target = %s
								ORDER BY rid DESC
								LIMIT 1
							", $row->element_id, $lang), ARRAY_N);

							$translator_id = false;
							if($rid){
								if($current_md5 != $md5){
									$needs_update = 1;
								}
								if($status == 3){
									$status = 10;
								}else{
									$status = 2;
								}
								$service = 'icanlocalize';

								foreach($sitepress_settings['icl_lang_status'] as $lpair){
									if($lpair['from'] == $row->language_code && $lpair['to'] == $lang && isset($lpair['translators'][0]['id'])){
										$translator_id = $lpair['translators'][0]['id'];
										break;
									}
								}

							}else{
								$status = 10;
								$translator_id =  $wpdb->get_var($wpdb->prepare("SELECT post_author FROM {$wpdb->posts} WHERE ID=%d", $t->element_id));
								$tlp = get_user_meta($translator_id, $wpdb->prefix.'language_pairs', true);
								$tlp[$row->language_code][$lang] = 1;
								$TranslationManagement->edit_translator($translator_id, $tlp);
							}


							// add translation_status record
							list($newrid) = $TranslationManagement->update_translation_status(array(
								'translation_id'        => $t->translation_id,
								'status'                => $status,
								'translator_id'         => $translator_id,
								'needs_update'          => $needs_update,
								'md5'                   => $md5,
								'translation_service'   => $service,
								'translation_package'   => serialize($translation_package),
								'links_fixed'           => intval(isset($links_fixed)?$links_fixed:0)
							));

                            $job_id = $TranslationManagement->add_translation_job( $newrid, $translator_id, $translation_package );
                            if ( $job_id && $status == 10 ) {
                                do_action( 'wpml_save_job_fields_from_post', $job_id );
                            }
                        }
                    }
                }
            }
            if ($processing) {
                update_option('icl_temp_upgrade_data', array('step' => 2, 'offset' => intval($offset+100)));
                $stepper->setNextStep(2);
            } else {
                update_option('icl_temp_upgrade_data', array('step' => 3, 'offset' => 99999999999999999999));
            }
            $message = $processing ? __('Processing translations...', 'sitepress') : __('Finalizing upgrade...', 'sitepress');
            return array('message' => $message);
            break;

    
        case 3:
            // removing the plugins text table; importing data into a Sitepress setting
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}icl_plugins_texts");
            if(!empty($results)){
                foreach($results as $row){
                    $cft[$row->attribute_name] = $row->translate + 1;
                }
				if ( isset( $cft ) ) {
					$iclsettings['translation-management']['custom_fields_translation'] = $cft;
                	$sitepress->save_settings($iclsettings);
				}

                $wpdb->query("DROP TABLE {$wpdb->prefix}icl_plugins_texts");
            }

            $iclsettings['language_selector_initialized'] = 1;

            if(get_option('_force_mp_post_http')){
                $iclsettings['troubleshooting_options']['http_communication'] = intval(get_option('_force_mp_post_http'));
                delete_option('_force_mp_post_http');
            }
            
            // set default translators
            if (isset($sitepress_settings['icl_lang_status'])) {
                foreach($sitepress_settings['icl_lang_status'] as $lpair){
                    if(!empty($lpair['translators'])){
                        $iclsettings['default_translators'][$lpair['from']][$lpair['to']] = array('id'=>$lpair['translators'][0]['id'], 'type'=>'icanlocalize');
                    }
                }
            }
            $sitepress->save_settings($iclsettings);            
            
            $iclsettings['migrated_2_0_0'] = 1;
            $sitepress->save_settings($iclsettings);
            delete_option('icl_temp_upgrade_data');
            return array('message' => __('Done', 'sitepress'), 'completed' => 1);
            break;

        default:
            return array('error' => __('Missing step', 'sitepress'), 'stop' => 1);
    }
}

// $iclsettings defined in upgrade.php
if(empty($iclsettings['migrated_2_0_0'])){
    wp_enqueue_script('icl-stepper', ICL_PLUGIN_URL . '/inc/upgrade-functions/2.0.0/stepper.js', array('jquery'));
    add_filter('admin_notices', 'icl_migrate_2_0_0');
    add_action('icl_ajx_custom_call', 'icl_ajx_upgrade_2_0_0', 1, 2);
}


function icl_migrate_2_0_0() {
	$ajax_action = 'wpml_upgrade_2_0_0';
	$ajax_action_none = wp_create_nonce($ajax_action);
	$link = 'index.php?icl_ajx_action=' . $ajax_action . '&nonce=' . $ajax_action_none;
    $txt = get_option('icl_temp_upgrade_data', FALSE) ? __('Resume Upgrade Process', 'sitepress') : __('Run Upgrade Process', 'sitepress');
    echo '<div class="message error" id="icl-migrate"><p><strong>'.__('WPML requires database upgrade', 'sitepress').'</strong></p>'
            .'<p>' . __('This normally takes a few seconds, but may last up to several minutes of very large databases.', 'sitepress') . '</p>'
            . '<p><a href="' . $link . '" style="" id="icl-migrate-start">' . $txt . '</a></p>'
            . '<div id="icl-migrate-progress" style="display:none; margin: 10px 0 20px 0;">'
            . '</div></div>';
}

function icl_ajx_upgrade_2_0_0($call, $request){
    if($call == 'wpml_upgrade_2_0_0'){
        $error = 0;
        $completed = 0;
        $stop = 0;
        $message = __('Starting the upgrade process...', 'sitepress');
        include_once ICL_PLUGIN_PATH . '/inc/upgrade-functions/2.0.0/stepper.php';
        include_once ICL_PLUGIN_PATH . '/inc/upgrade-functions/upgrade-2.0.0.php';
        $temp_upgrade_data = get_option('icl_temp_upgrade_data',
                array('step' => 0, 'offset' => 0));
        $step = isset($request['step']) ? $request['step'] : $temp_upgrade_data['step'];
        $migration = new Icl_Stepper($step);
        $migration->registerSteps(
                'icl_upgrade_2_0_0_steps',
                'icl_upgrade_2_0_0_steps',
                'icl_upgrade_2_0_0_steps');
        if (isset($request['init'])) {
            echo json_encode(array(
                'error' => $error,
                'output' => $migration->render(),
                'step' => $migration->getNextStep(),
                'message' => __('Creating new tables...', 'sitepress'),
                'stop' => $stop,
            ));
            exit;
        }        
        $data = $migration->init();
        @extract($data, EXTR_OVERWRITE);
        echo json_encode(array(
            'error' => $error,
            'completed' => $completed,
            'message' => $message,
            'step' => $migration->getNextStep(),
            'barWidth' => $migration->barWidth(),
            'stop' => $stop,
            ));    
    }
}

?>
