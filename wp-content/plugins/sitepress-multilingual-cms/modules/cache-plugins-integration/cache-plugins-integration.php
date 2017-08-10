<?php
if(is_admin() || defined('XMLRPC_REQUEST')):

    class WPMLCpi{
        
        // supported plugins    
        static $wpml_cpi_plugins = array(
                'wp-super-cache/wp-cache.php' => 'WP_CPI_WP_Super_Cache'       
            );
        private $plugin_cp_class;  // plugin compatibility package class name
        private $settings;
        
        
        function __construct(){
            
            add_action('plugins_loaded', array($this, 'init'), 11);    // lower priority - allow packages to load
            
        }
        
        function init(){            
            global $sitepress_settings;
            
            $ap = get_option('active_plugins');
            $i = array_intersect($ap, array_keys(self::$wpml_cpi_plugins));            
            if(!empty($i)){
                $this->plugin_cp_class = self::$wpml_cpi_plugins[array_pop($i)];
                
                if(class_exists($this->plugin_cp_class) && method_exists($this->plugin_cp_class, 'clear_cache')){
                    
                    $this->settings = $sitepress_settings['modules']['caching-plugins-integration'];
                    $this->validate_settings();
                    
                    add_action('icl_page_overview_top', array($this, 'menu'));
                    wp_enqueue_script('wpml-cpi-scripts', ICL_PLUGIN_URL . '/modules/cache-plugins-integration/scripts.js', array(), ICL_SITEPRESS_VERSION);
                    
                    add_action('icl_ajx_custom_call', array($this, 'ajx_calls'), 1, 2);
                    
                    add_action('icl_st_add_string_translation', array($this, 'call_cache_clear'));
                    add_action('icl_st_unregister_string_multi', array($this, 'call_cache_clear'));
                    add_action('icl_st_unregister_string', array($this, 'call_cache_clear'));
                    
                    $ajx_request_exceptions = array(
                        'ajx_health_checked',
                        'save_language_pairs',
                        'toggle_content_translation',
                        'icl_admin_language_options',
                        'icl_page_sync_options',
                        'validate_language_domain',
                        'icl_save_theme_localization_type',
                        'dismiss_help',
                        'dismiss_page_estimate_hint',
                        'dismiss_upgrade_notice',
                        'dismiss_upgrade_notice',
                        'dismiss_translate_help',
                        'setup_got_to_step1',
                        'setup_got_to_step2',
                        'toggle_show_translations',
                        'icl_show_sidebar',
                    );
                    if( !isset($_REQUEST['icl_ajx_action']) || !in_array($_REQUEST['icl_ajx_action'], $ajx_request_exceptions)){
                        add_action('icl_save_settings', array($this, 'icl_save_settings_cb'), 10, 1);
                    }                    
                    
                    // when a post is sent from the translation server
	                $hrow = icl_xml2array( $this->get_raw_post_data() );
                    if(isset($hrow['methodCall']['methodName']['value']) && $hrow['methodCall']['methodName']['value'] == 'icanlocalize.set_translation_status'){
                        add_action('save_post', array($this, 'call_cache_clear'));
                    }
                    
                }
            }
            
        }

	    private function get_raw_post_data() {
		    $wpml_wp_api = new WPML_WP_API();

		    return $wpml_wp_api->get_raw_post_data();
	    }
        
        function validate_settings(){
            $save_settings = false;
            if(!isset($this->settings['automatic'])){
                $this->settings['automatic'] = 0;
                $save_settings = true;
            }
            if(!isset($this->settings['dirty_cache'])){
                $this->settings['dirty_cache'] = 0;
                $save_settings = true;
            }        
            if($save_settings){
                $this->save_settings();
            }
        }
        
        function save_settings(){
            global $sitepress;
            $iclsettings['modules']['caching-plugins-integration'] = $this->settings;
            remove_action('icl_save_settings', array($this, 'icl_save_settings_cb'), 10, 1);
            $sitepress->save_settings($iclsettings);
            add_action('icl_save_settings', array($this, 'icl_save_settings_cb'), 10, 1);
        }
        
        function ajx_calls($call, $data){
            if($call == 'wpml_cpi_options'){
	            $this->settings['automatic'] = (int) $data['automatic'];   
                if($this->settings['automatic'] == 1){
                    $this->settings['dirty_cache'] = 0;
                }
                $this->save_settings();
            }elseif($call == 'wpml_cpi_clear_cache'){                
                $this->call_cache_clear(true);
            }
        }
        
        function menu(){
            echo '<div class="updated message">';
            echo '<h3>' . sprintf(__('<i>%s</i> integration', 'sitepress'), str_replace('_', ' ', substr($this->plugin_cp_class, 7))) . '</h3>';
            echo '<p>';
            _e('You are using a caching plugin. When you translate strings, the cache needs to be cleared in order for the translation to display.', 'sitepress');
            echo '</p>';
            echo '<ul id="wpml_cpi_options">';
            if($this->settings['automatic']) { $checked='checked="checked"'; } else { $checked=''; }
            echo '<li><label><input type="radio" name="wpml_cpi_automatic" value="1" '.$checked.' />&nbsp;' 
                . __('Automatically clear the cache when strings are translated','sitepress').'</label></li>';
            if(!$this->settings['automatic']) { $checked='checked="checked"'; } else { $checked=''; }
            echo '<li><label><input type="radio" name="wpml_cpi_automatic" value="0" '.$checked.' />&nbsp;' 
                . __('I will clear the cache manually after translating strings','sitepress').'</label></li>';
            echo '</ul>';
            if(!$this->settings['automatic'] && $this->settings['dirty_cache']){
                echo '<p><input id="wpml_cpi_clear_cache" type="button" class="button secondary" value="' . __('Clear cache now','sitepress'). '"/></p>';
            }
            echo '</div>';
        }
        
        function icl_save_settings_cb($settings){
            if(!empty($settings)){
                $this->call_cache_clear();
            }            
        }
        
        function call_cache_clear($do_clear = false){
            if($this->settings['automatic'] || $do_clear){                
                call_user_func(array($this->plugin_cp_class , 'clear_cache'));
                $this->settings['dirty_cache'] = 0;
            }else{
                $this->settings['dirty_cache'] = 1;                
            }
            $this->save_settings();
        }
    }

    $wpml_cpi = new WPMLCpi;  

endif;
?>
