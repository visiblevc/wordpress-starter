<?php
// adapted from http://wordpress.org/extend/plugins/black-studio-wpml-javascript-redirect/
// thanks to Blank Studio - http://www.blackstudio.it/

class WPML_Browser_Redirect extends WPML_SP_User {

	/**
	 * WPML_Browser_Redirect constructor.
	 *
	 * @param $sitepress
	 * @param $sitepress_settings
	 */
	public function __construct( &$sitepress ) {
		parent::__construct( $sitepress );
	}

	public function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init(){
		$root_page = $this->sitepress->get_root_page_utils();
        if( !is_admin() && !isset( $_GET['redirect_to'] ) && !preg_match( '#wp-login\.php$#', preg_replace("@\?(.*)$@", '', $_SERVER['REQUEST_URI'] ) )
        ) {
                add_action( 'wp_print_scripts', array( $this, 'scripts' ) );
        }
    }
    
    public function scripts(){
        // Enqueue javascripts
        wp_register_script('jquery.cookie', ICL_PLUGIN_URL . '/res/js/jquery.cookie.js', array('jquery'), ICL_SITEPRESS_VERSION);
        wp_register_script('wpml-browser-redirect', ICL_PLUGIN_URL . '/res/js/browser-redirect.js', array('jquery', 'jquery.cookie'), ICL_SITEPRESS_VERSION);
            
        $args['skip_missing'] = intval( $this->sitepress->get_setting( 'automatic_redirect' ) == 1 );
        
        // Build multi language urls array
        $languages      = $this->sitepress->get_ls_languages($args);
        $language_urls  = array();
        foreach($languages as $language) {
			if(isset($language['default_locale']) && $language['default_locale']) {
				$language_urls[$language['default_locale']] = $language['url'];
				$language_parts = explode('_', $language['default_locale']);
				if(count($language_parts)>1) {
					foreach($language_parts as $language_part) {
						if(!isset($language_urls[$language_part])) {
							$language_urls[$language_part] = $language['url'];
						}
					}
				}
			}
			$language_urls[$language['language_code']] = $language['url'];
        }
        // Cookie parameters
        $http_host = $_SERVER['HTTP_HOST'] == 'localhost' ? '' : $_SERVER['HTTP_HOST'];
        $cookie = array(
            'name' => '_icl_visitor_lang_js',
            'domain' => (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN? COOKIE_DOMAIN : $http_host),
            'path' => (defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/'), 
            'expiration' => $this->sitepress->get_setting( 'remember_language' ),
        );
        
        // Send params to javascript
        $params = array(
            'pageLanguage'      => defined('ICL_LANGUAGE_CODE')? ICL_LANGUAGE_CODE : get_bloginfo('language'),
            'languageUrls'      => $language_urls,
            'cookie'            => $cookie            
        );
        wp_localize_script('wpml-browser-redirect', 'wpml_browser_redirect_params', $params);        
        wp_enqueue_script('wpml-browser-redirect');
    }
}
