<?php
/**
 * Installer Class for Theme Support
 *
 * Supports automatic updates and installation of Toolset/WPML Themes
 *
 * @class       Installer_Theme_Class
 * @version     1.6
 * @category    Class
 * @author      OnTheGoSystems
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Installer_Theme_Class
 */
class Installer_Theme_Class {

    /** Theme Repository */
    private $theme_repo;

    /** Repository API */
    private $repository_api;

    /** Repository Theme Products */
    private $repository_theme_products;

    /** Site URL */
    private $installer_site_url;

    /** Site Key */
    private $installer_site_key;

    /** The Themes Option */
    protected $installer_themes_option;

    /** Update settings */
    protected $installer_themes_available_updates;

    /** The Themes */
    protected $installer_themes = array();

    /** Repository with themes */
    protected $installer_repo_with_themes;

    /** Active tab */
    protected $installer_theme_active_tab;

    /** Theme user registration */
    protected $theme_user_registration;

    /** Client active subscription */
    protected $installer_theme_subscription_type;

    public function __construct() {

        /** Properties */

        //Get installer repositories
        $installer_repositories = WP_Installer()->get_repositories();

        //Get repos with themes
        $repos_with_themes = $this->installer_theme_reposities_that_has_themes( $installer_repositories );

        if ( is_array( $repos_with_themes ) ) {
            //Assign to property
            $this->installer_repo_with_themes = $repos_with_themes;

            //Let's looped through repos with themes
            foreach ( $repos_with_themes as $k => $repo ) {

                //$repo could be 'toolset' or 'wpml'
                //Assign each repo with theme to property
                $this->theme_repo[] = $repo;

                if ( (isset($installer_repositories[$repo]['api-url'])) && (isset($installer_repositories[$repo]['products'])) ) {

                    //Define the rest of the properties based on the given repo
                    $this->repository_api[$repo] = $installer_repositories[$repo]['api-url'];
                    $this->repository_theme_products[$repo] = $installer_repositories[$repo]['products'];
                    $this->installer_site_url[$repo] = WP_Installer()->get_installer_site_url( $repo );
                    $this->installer_site_key[$repo] = WP_Installer()->get_site_key( $repo );
                    $this->theme_user_registration[$repo] = false;

                    if ( WP_Installer()->repository_has_valid_subscription( $repo ) ) {

                        $this->installer_theme_subscription_type = WP_Installer()->get_subscription_type_for_repository( $repo );
                        $this->installer_themes_option[$repo] = 'wp_installer_' . $repo . '_themes';
                        $this->installer_themes_available_updates[$repo] = 'wp_installer_' . $repo . '_updated_themes';
                        $this->installer_theme_active_tab = '';

                        //We only set themes available to this validated subscription
                        $this->installer_theme_available( $repo, $this->installer_theme_subscription_type );

                        add_action( 'installer_themes_support_set_up', array($this, 'installer_theme_sets_active_tab_on_init'), 10 );
                        $this->theme_user_registration[$repo] = true;
                    }

                    /** We are ready.. let's initialize .... */
                    $this->init();
                }
            }
            add_action( 'installer_themes_support_set_up', array($this, 'installer_theme_loaded_hooks') );
        }
    }

    /** Init */
    public function init() {
        add_action( 'admin_enqueue_scripts', array($this, 'installer_theme_enqueue_scripts') );
        add_filter( 'themes_api', array($this, 'installer_theme_api_override'), 10, 3 );
        add_filter( 'themes_api_result', array($this, 'installer_theme_api_override_response'), 10, 3 );
        add_filter( 'site_transient_update_themes', array($this, 'installer_theme_upgrade_check'), 10, 1 );
        add_action( 'http_api_debug', array($this, 'installer_theme_sync_native_wp_api'), 10, 5 );
        add_filter( 'installer_theme_hook_response_theme', array($this, 'installer_theme_add_num_ratings'), 10, 1 );
        add_filter( 'themes_update_check_locales', array($this, 'installer_theme_sync_call_wp_theme_api'), 10, 1 );
        add_filter( 'admin_url', array($this, 'installer_theme_add_query_arg_tab'), 10, 3 );
        add_filter( 'network_admin_url', array($this, 'installer_theme_add_query_arg_tab'), 10, 2 );
        add_action( 'wp_ajax_installer_theme_frontend_selected_tab', array($this, 'installer_theme_frontend_selected_tab'), 0 );
        add_action( 'wp_loaded', array($this, 'installer_themes_support_set_up_func') );
    }

    /** Enqueue scripts */
    public function installer_theme_enqueue_scripts() {
        $current_screen = $this->installer_theme_current_screen();
        $commercial_plugin_screen = $this->installer_theme_is_commercial_plugin_screen( $current_screen );
        if ( ('theme-install' == $current_screen) || ($commercial_plugin_screen) || ('theme-install-network' == $current_screen) ) {
            $repo_with_themes = $this->installer_repo_with_themes;
            $js_array = array();
            if ( is_array( $repo_with_themes ) ) {
                foreach ( $repo_with_themes as $k => $v ) {

                    //Hyperlink text
                    $theme_repo_name = $this->installer_theme_get_repo_product_name( $v );
                    $the_hyperlink_text = esc_js( $theme_repo_name );

                    if ( is_multisite() ) {
                        $admin_url_passed = network_admin_url();
                    } else {
                        $admin_url_passed = admin_url();
                    }

                    //Define
                    $js_array[$v] = array(
                        'the_hyperlink_text' => $the_hyperlink_text,
                        'registration_status' => $this->theme_user_registration[$v],
                        'is_commercial_plugin_tab' => $commercial_plugin_screen,
                        'registration_url' => $admin_url_passed . 'plugin-install.php?tab=commercial#installer_repo_' . $v
                    );

                }
            }

            if ( !(empty($js_array)) ) {
                wp_enqueue_script( 'installer-theme-install', WP_Installer()->res_url() . '/res/js/installer_theme_install.js', array('jquery', 'installer-admin'), WP_Installer()->version() );
                $installer_ajax_url = admin_url( 'admin-ajax.php' );

                if ( is_ssl() ) {
                    $installer_ajax_url = str_replace( 'http://', 'https://', $installer_ajax_url );
                } else {
                    $installer_ajax_url = str_replace( 'https://', 'http://', $installer_ajax_url );
                }

                //Case where user is subscribed to a subscription that does not have themes
                $subscription_js_check = $this->installer_theme_subscription_does_not_have_theme( $js_array );

                wp_localize_script( 'installer-theme-install', 'installer_theme_install_localize',
                    array(
                        'js_array_installer' => $js_array,
                        'ajaxurl' => $installer_ajax_url,
                        'no_associated_themes' => $subscription_js_check,
                        'installer_theme_frontend_selected_tab_nonce' => wp_create_nonce( 'installer_theme_frontend_selected_tab' )
                    )
                );
            }
        }
    }

    /** Case where user is subscribed to a subscription that does not have themes */
    protected function installer_theme_subscription_does_not_have_theme( $js_array ) {

        $any_subscription_has_theme = array();
        $number_of_registrations = array();

        //Step1, we looped through JS array
        foreach ( $js_array as $repo_slug => $js_details ) {

            //Step2, checked if user is registered
            if ( isset($this->theme_user_registration[$repo_slug]) ) {
                $registration_status = $this->theme_user_registration[$repo_slug];
                if ( $registration_status ) {

                    //Registered
                    $number_of_registrations[] = $repo_slug;

                    //Step3, we checked if the $repo_slug has available theme
                    $themes_available = false;
                    if ( isset($this->installer_themes[$repo_slug]) ) {
                        $themes_available = $this->installer_themes[$repo_slug];
                        if ( !(empty($themes_available)) ) {
                            //This subscription has theme
                            $themes_available = true;
                        }
                    }

                    if ( $themes_available ) {
                        $any_subscription_has_theme[] = $repo_slug;
                    }
                }
            }

        }

        //Step4, we are done looping, check if there are any repos that have themes
        if ( empty($registration_status) ) {

            //No registration on any repos
            return FALSE;

        } elseif ( !(empty($registration_status)) ) {

            //Has some registration on some repos
            //We then checked if this user has any active subscriptions
            if ( empty($any_subscription_has_theme) ) {
                //No subscription
                return TRUE;
            } else {
                //Has subscription found
                return FALSE;
            }
        }
    }

    /** Check if its the commercial plugin screen */
    private function installer_theme_is_commercial_plugin_screen( $current_screen ) {
        $commercial = false;
        if ( ('plugin-install' == $current_screen) || ('plugin-install-network' == $current_screen) ) {
            if ( isset($_GET['tab']) ) {
                $tab = sanitize_text_field( $_GET['tab'] );
                if ( 'commercial' == $tab ) {
                    $commercial = true;
                }
            }
        }
        return $commercial;
    }

    /** Current screen */
    private function installer_theme_current_screen() {

        $current_screen_loaded = false;

        if ( function_exists( 'get_current_screen' ) ) {

            $screen_output = get_current_screen();
            $current_screen_loaded = $screen_output->id;

        }

        return $current_screen_loaded;

    }

    /** Override WordPress Themes API */
    public function installer_theme_api_override( $api_boolean, $action, $args ) {

        //Let's checked if user is browsing our themes
        if ( isset($args->browse) ) {
            $browse = $args->browse;
            if ( in_array( $browse, $this->theme_repo ) ) {
                //Uniquely validated for our Themes
                if ( 'query_themes' == $action ) {
                    //User is querying or asking information about our themes, let's override
                    $api_boolean = true;
                }
            }
        } elseif ( isset($args->slug) ) {
            //We are installing our themes
            $theme_to_install = $args->slug;

            //Lets uniquely validate if this belongs to us
            //Check if this is OTGS theme
            $validate_check = $this->installer_themes_belong_to_us( $theme_to_install );
            if ( $validate_check ) {
                //Belongs to us
                if ( !(empty($theme_to_install)) ) {
                    $api_boolean = true;
                }
            }
        }

        return $api_boolean;
    }

    /** Override WordPress Themes API response with our own themes API*/
    public function installer_theme_api_override_response( $res, $action, $args ) {

        if ( true === $res ) {
            if ( isset($args->browse) ) {
                $browse = $args->browse;
                if ( in_array( $browse, $this->theme_repo ) ) {
                    //Uniquely validated for our themes
                    if ( 'query_themes' == $action ) {
                        //Client querying OTGS themes
                        //Check for registration status
                        if ( isset($this->theme_user_registration[$browse]) ) {
                            //Set
                            if ( !($this->theme_user_registration[$browse]) ) {
                                //Not registered yet
                                $res = new stdClass();
                                $res->info = array();
                                $res->themes = array();
                                return $res;
                            } else {
                                //Registered
                                $themes = $this->installer_theme_get_themes( '', $browse );
                                $res = $this->installer_theme_format_response( $themes, $action );
                            }
                        }
                    }
                }
            } elseif ( isset($args->slug) ) {
                //We are installing theme
                //Lets uniquely validate if this belongs to our theme
                $theme_to_install = $args->slug;

                //Lets uniquely validate if this belongs to us
                //Check if this is OTGS theme
                $validate_check = $this->installer_themes_belong_to_us( $theme_to_install );
                if ( $validate_check ) {
                    //Belongs to us
                    if ( ($res) && ('theme_information' == $action) ) {
                        $themes = $this->installer_theme_get_themes( '', $this->installer_theme_active_tab );
                        $res = $this->installer_theme_format_response( $themes, $action, $args->slug );
                    }
                }
            }
            return $res;
        } else {
            //Default WP Themes here
            $client_side_active_tab = get_option( 'wp_installer_clientside_active_tab' );
            if ( $client_side_active_tab ) {
                if ( !(in_array( $client_side_active_tab, $this->theme_repo )) ) {
                    //Not OTGS tab
                    return $res;
                }
            }

        }
    }

    /** Get Themes */
    private function installer_theme_get_themes( $product_url = '', $repo_source = '' ) {

        //Query API
        if ( empty($product_url) ) {
            //Not set
            if ( isset($this->repository_theme_products[$this->installer_theme_active_tab]) ) {
                $query_remote_url = $this->repository_theme_products[$this->installer_theme_active_tab];
            }

        } else {
            $query_remote_url = $product_url;
        }

        //Let's retrieved current installer settings so we won't be querying all the time
        $current_installer_settings = WP_Installer()->get_settings();

        //Set $themes to FALSE by default
        $themes = false;

        if ( (is_array( $current_installer_settings )) && (!(empty($current_installer_settings))) ) {

            //Set and already defined, retrieved $products
            if ( isset($current_installer_settings['repositories'][$repo_source]['data']) ) {
                $products = $current_installer_settings['repositories'][$repo_source]['data'];
                if ( isset($products['downloads']['themes']) ) {
                    $themes = $products['downloads']['themes'];
                }
            }

        } else {

            //Call API
            $response = wp_remote_get( $query_remote_url );

            if ( is_wp_error( $response ) ) {
                //Error detected: http fallback
                $query_remote_url = preg_replace( "@^https://@", 'http://', $query_remote_url );
                $response = wp_remote_get( $query_remote_url );
            }

            if ( !(is_wp_error( $response )) ) {
                //Not WP error
                //Evaluate response
                if ( $response && isset($response['response']['code']) && $response['response']['code'] == 200 ) {
                    //In this case, response is set and defined, proceed...
                    $body = wp_remote_retrieve_body( $response );
                    if ( $body ) {
                        $products = json_decode( $body, true );
                        if ( isset($products['downloads']['themes']) ) {
                            $themes = $products['downloads']['themes'];
                        }
                    }

                }
            }
        }

        //Return themes, can be filtered by user subscription type
        return apply_filters( 'installer_theme_get_themes', $themes, $this->installer_theme_active_tab );
    }

    /** Format response in compatibility with WordPress Theme API response */
    private function installer_theme_format_response( $themes, $action, $slug = '' ) {

        //Let's append download link only when retrieving theme information for installation
        if ( ('theme_information' == $action) && (!(empty($slug))) ) {

            //Only return one result -> the theme to be installed
            foreach ( $themes as $k => $theme ) {
                if ( $slug == $theme['basename'] ) {
                    $theme['download_link'] = WP_Installer()->append_site_key_to_download_url( $theme['url'], $this->installer_site_key[$this->installer_theme_active_tab], $this->installer_theme_active_tab );
                    $theme = json_decode( json_encode( $theme ), FALSE );
                    return $theme;
                }
            }

        } else {

            $res = new stdClass();
            $res->info = array();
            $res->themes = array();

            //Define info
            $res->info['page'] = 1;
            $res->info['pages'] = 10;

            //Let's count available themes		;
            $res->info['results'] = count( $themes );

            //Let's saved themes for easy access later on
            $this->installer_theme_savethemes_by_slug( $themes );

            //Let's defined available themes
            if ( isset($this->installer_theme_subscription_type) ) {
                //Has subscription type defined, let's saved what is associated with this subscription
                $this->installer_theme_available( $this->installer_theme_active_tab, $this->installer_theme_subscription_type );
            } else {
                $this->installer_theme_available( $this->installer_theme_active_tab );
            }

            //Let's add themes to the overriden WordPress API Theme response
            /** Installer 1.7.6: Update to compatible data format response from WP Theme API */
            $theme_compatible_array=array();
            if ((is_array($themes))) {
            	foreach ($themes as $k=>$v) {
            		$theme_compatible_array[]=(object)($v);
            	}
            }
            $res->themes = $theme_compatible_array;
            $res->themes = apply_filters( 'installer_theme_hook_response_theme', $res->themes );
            return $res;
        }
    }

    /** Let's save all available themes by its slug after any latest API query */
    private function installer_theme_savethemes_by_slug( $themes, $doing_query = false ) {

        if ( !($doing_query) ) {
            $this->installer_themes[$this->installer_theme_active_tab] = array();
        }

        if ( !(empty($themes)) ) {
            $themes_for_saving = array();
            foreach ( $themes as $k => $theme ) {
                if ( !($doing_query) ) {
                    if ( isset($theme['slug']) ) {
                        $theme_slug = $theme['slug'];
                        if ( !(empty($theme_slug)) ) {
                            $themes_for_saving[] = $theme_slug;
                        }
                    }
                } else {

                    if ( ((isset($theme['slug'])) && (isset($theme['version'])) &&
                            (isset($theme['theme_page_url']))) && (isset($theme['url']))
                    ) {
                        $theme_slug = $theme['slug'];
                        $theme_version = $theme['version'];
                        $theme_page_url = $theme['theme_page_url'];
                        $theme_url = $theme['url'];
                        if ( (!(empty($theme_slug))) && (!(empty($theme_version))) &&
                            (!(empty($theme_page_url))) && (!(empty($theme_url)))
                        ) {
                            //$theme_slug is unique for every theme
                            $themes_for_saving[$theme_slug] = array(
                                'version' => $theme_version,
                                'theme_page_url' => $theme_page_url,
                                'url' => $theme_url
                            );

                        }
                    }
                }

            }

            if ( !(empty($themes_for_saving)) ) {
                //Has themes for saving
                if ( !($doing_query) ) {
                    //Not doing query
                    $existing_themes = get_option( $this->installer_themes_option[$this->installer_theme_active_tab] );
                    if ( !($existing_themes) ) {
                        //Does not yet exists
                        delete_option( $this->installer_themes_option[$this->installer_theme_active_tab] );
                        update_option( $this->installer_themes_option[$this->installer_theme_active_tab], $themes_for_saving );
                    } else {
                        //exists, check if we need to update
                        if ( $existing_themes == $themes_for_saving ) {
                            //Equal, no need to update here
                        } else {
                            //Update
                            delete_option( $this->installer_themes_option[$this->installer_theme_active_tab] );
                            update_option( $this->installer_themes_option[$this->installer_theme_active_tab], $themes_for_saving );
                        }
                    }
                } else {
                    //Used for query purposes only, don't save anything
                    return $themes_for_saving;
                }
            }
        }
    }

    /** Available themes */
    private function installer_theme_available( $repo, $subscription_type = '' ) {

        $subscription_type = intval( $subscription_type );
        if ( $subscription_type > 0 ) {

            //Here we have a case of validated subscription
            //We need to set themes that is available to this subscription
            $themes_associated_with_subscription = $this->installer_themes[$repo] = $this->installer_theme_get_themes_by_subscription( $subscription_type, $repo );
            if ( !(empty($themes_associated_with_subscription)) ) {
                //Has themes
                $this->installer_themes[$repo] = $themes_associated_with_subscription;
            }
        } else {

            //Get themes
            $this->installer_themes[$repo] = get_option( $this->installer_themes_option[$repo] );
        }
    }

    /** Theme upgrade check */
    public function installer_theme_upgrade_check( $the_value ) {

        //Step1: Let's looped through repos with themes and check if we have updates available for them.
        if ( (is_array( $this->installer_repo_with_themes )) && (!(empty($this->installer_repo_with_themes))) ) {
            foreach ( $this->installer_repo_with_themes as $k => $repo_slug ) {
                //Step2: Let's checked if we have update for this theme
                $update_available = get_option( $this->installer_themes_available_updates[$repo_slug] );
                if ( $update_available ) {
                    if ( (is_array( $update_available )) && (!(empty($update_available))) ) {
                        //Has updates available coming from this specific theme repo
                        //Let's loop through the themes that needs update
                        foreach ( $update_available as $theme_slug => $v ) {
                            //Add to response API
                            $the_value->response [$theme_slug] = array(
                                'theme' => $theme_slug,
                                'new_version' => $v['new_version'],
                                'url' => $v['url'],
                                'package' => $v['package']
                            );
                        }
                    }
                }
            }
        }
        //Return
        return $the_value;
    }

    /** Return repositories that has themes */
    private function installer_theme_reposities_that_has_themes( $repositories, $ret_value = true, $doing_api_query = false ) {

        $repositories_with_themes = array();

        if ( (is_array( $repositories )) && (!(empty($repositories))) ) {

            //Let's checked if we have something before
            $themes = get_option( 'installer_repositories_with_theme' );

            if ( (!($themes)) || ($doing_api_query) ) {
                //Not yet defined
                //Loop through each repositories and check whether they have themes
                foreach ( $repositories as $k => $v ) {
                    if ( isset($v['products']) ) {
                        $products_url = $v['products'];
                        $themes = $this->installer_theme_get_themes( $products_url, $k );
                        if ( (is_array( $themes )) && (!(empty($themes))) ) {
                            //Repo has themes
                            $repositories_with_themes[] = $k;
                        }
                    }
                }
            } else {
                //Already set
                $repositories_with_themes = $themes;
            }

            if ( (((is_array( $repositories_with_themes )) && (!(empty($repositories_with_themes)))) && (!($themes))) || ($doing_api_query) ) {
                //Save to db
                update_option( 'installer_repositories_with_theme', $repositories_with_themes );
            }
        }

        if ( $ret_value ) {
            return $repositories_with_themes;
        }

    }

    /** When WordPress queries its own Themes API, we sync with our own */
    public function installer_theme_sync_native_wp_api( $response, $responsetext, $class, $args, $url ) {

        $api_native_string = 'api.wordpress.org/themes/';
        if ( (strpos( $url, $api_native_string ) !== false) ) {
            //WordPress is querying its own themes API
            $installer_repositories = WP_Installer()->get_repositories();

            //Query our own API and update repository values too
            $this->installer_theme_reposities_that_has_themes( $installer_repositories, false, true );
        }
    }

    /** Returns product name by theme repo slug */
    private function installer_theme_get_repo_product_name( $theme_repo ) {

        $theme_repo_name = false;

        if ( isset(WP_Installer()->settings['repositories'][$theme_repo]['data']['product-name']) ) {
            //Set
            $prod_name = WP_Installer()->settings['repositories'][$theme_repo]['data']['product-name'];
            if ( !(empty($prod_name)) ) {
                $theme_repo_name = $prod_name;
            }
        } else {
            //Not yet
            if ( $theme_repo == $this->theme_repo ) {
                $result = $this->installer_theme_general_api_query();
                if ( isset($result['product-name']) ) {
                    $product_name = $result['product-name'];
                    if ( !(empty($product_name)) ) {
                        $theme_repo_name = $product_name;
                    }
                }
            }
        }

        return $theme_repo_name;
    }

    /** General query API method, returns $products */
    private function installer_theme_general_api_query() {
        $products = false;
        $response = wp_remote_get( $this->repository_theme_products );
        if ( !(is_wp_error( $response )) ) {
            //Not WP error
            //Evaluate response
            if ( $response && isset($response['response']['code']) && $response['response']['code'] == 200 ) {
                //In this case, response is set and defined, proceed...
                $body = wp_remote_retrieve_body( $response );
                if ( $body ) {
                    $result = json_decode( $body, true );
                    if ( (is_array( $result )) && (!(empty($result))) ) {
                        $products = $result;
                    }
                }

            }
        }

        return $products;
    }

    /** General method to check if themes are OTGS themes based on its slug*/
    private function installer_themes_belong_to_us( $theme_slug ) {

        $found = false;
        $theme_slug = trim( $theme_slug );

        foreach ( $this->installer_themes as $repo_with_theme => $themes ) {
            foreach ( $themes as $k => $otgs_theme_slug ) {
                if ( $theme_slug == $otgs_theme_slug ) {
                    //match found! Theme belongs to otgs
                    return true;
                }
            }
        }
        return $found;

    }

    /** Sets active tab on init */
    public function installer_theme_sets_active_tab_on_init() {

        if ( isset ($_SERVER ['REQUEST_URI']) ) {
            $request_uri = $_SERVER ['REQUEST_URI'];
            if ( isset ($_GET ['browse']) ) {
                $active_tab = sanitize_text_field( $_GET['browse'] );
                $this->installer_theme_active_tab = $active_tab;
            } elseif ( isset ($_POST ['request'] ['browse']) ) {
                $active_tab = sanitize_text_field ( $_POST['request']['browse'] );
                $this->installer_theme_active_tab = $active_tab;
            } elseif ( (isset ($_GET ['theme_repo'])) && (isset ($_GET ['action'])) ) {
                $theme_repo = sanitize_text_field( $_GET['theme_repo'] );
                $the_action = sanitize_text_field( $_GET['action'] );
                if ( ('install-theme' == $the_action) && (!(empty($theme_repo))) ) {
                    $this->installer_theme_active_tab = $theme_repo;
                }
            } elseif ( wp_get_referer() ) {
                $referer = wp_get_referer();
                $parts = parse_url( $referer );
                if ( isset($parts['query']) ) {
                    parse_str( $parts['query'], $query );
                    if ( isset($query['browse']) ) {
                        $this->installer_theme_active_tab = $query['browse'];
                    }
                }
            }
        }
    }

    /** WP Theme API compatibility- added num ratings */
    /** Installer 1.7.6+ Added updated 'rating' field */
    public function installer_theme_add_num_ratings( $themes ) {

        if ( (is_array( $themes )) && (!(empty($themes))) ) {
            foreach ( $themes as $k => $v ) {
                if ( !(isset($v->num_ratings)) ) {
                    $themes[$k]->num_ratings = 100;
                }
                if ( !(isset($v->rating)) ) {
                	$themes[$k]->rating = 100;
                }
            }
        }

        return $themes;
    }

    /** When WordPress.org makes a call to its repository, let's run our own upgrade checks too */
    public function installer_theme_sync_call_wp_theme_api( $locales ) {

        $this->installer_theme_upgrade_theme_check();

        return $locales;
    }

    /** Upgrade theme check */
    private function installer_theme_upgrade_theme_check() {

        // Step1-> we get all installed themes in clients local themes directory
        $installed_themes = wp_get_themes();

        // Step2: We need to loop through each repository with themes
        foreach ( $this->installer_repo_with_themes as $k => $repo_slug ) {

            // We then need to retrieved the products URL for each of this repo
            $products_url = $this->repository_theme_products [$repo_slug];

            // Step3-> we get all available themes in our repository via API based on this URL
            $available_themes = $this->installer_theme_get_themes( $products_url, $repo_slug );

            if ( !($available_themes) ) {

                // API is not available as of the moment, return..
                return;
            } else {

                // We have available themes here...
                // Step4->let's simplify available themes data by slugs
                $simplified_available_themes = $this->installer_theme_savethemes_by_slug( $available_themes, true );

                // Step5->Let's loop through installed themes
                if ( (is_array( $installed_themes )) && (!(empty ($installed_themes))) ) {
                    $otgs_theme_updates_available = array();
                    foreach ( $installed_themes as $theme_slug => $theme_object ) {
                        if ( array_key_exists( $theme_slug, $simplified_available_themes ) ) {

                            // This is our theme
                            // Step6->Let's get version of the local theme installed
                            $local_version = $theme_object->get( 'Version' );

                            // Step7->Let's get the latest version of this theme, page URL and download URL from our repository
                            $repository_version = $simplified_available_themes [$theme_slug] ['version'];
                            $theme_page_url = $simplified_available_themes [$theme_slug] ['theme_page_url'];
                            $theme_download_url = $simplified_available_themes [$theme_slug] ['url'];

                            // Step8->Let's compare the version
                            if ( version_compare( $repository_version, $local_version, '>' ) ) {

                                // Update available for this theme
                                // Step9-> Define download URL with site key
                                $package_url = WP_Installer()->append_site_key_to_download_url( $theme_download_url, $this->installer_site_key [$repo_slug], $repo_slug );

                                //Step10-> Assign to updates array for later accessing.
                                $otgs_theme_updates_available[$theme_slug] = array(
                                    'theme' => $theme_slug,
                                    'new_version' => $repository_version,
                                    'url' => $theme_page_url,
                                    'package' => $package_url
                                );
                            }
                        }
                    }
                    //Exited the upgrade loop for this specific theme repository
                    if ( !(empty($otgs_theme_updates_available)) ) {
                        //Has updates
                        update_option( $this->installer_themes_available_updates[$repo_slug], $otgs_theme_updates_available );
                    } else {
                        //No updates
                        delete_option( $this->installer_themes_available_updates[$repo_slug] );
                    }

                }
            }
        }
    }

    /** When the user is on Themes install page OTG themes repository, let's the currently selected tab */
    public function installer_theme_add_query_arg_tab( $url, $path, $blog_id = null ) {

        $wp_install_string = 'update.php?action=install-theme';
        if ( $path == $wp_install_string ) {
            if ( isset($this->installer_theme_active_tab) ) {
                if ( !(empty($this->installer_theme_active_tab)) ) {
                    $url = add_query_arg( array(
                        'theme_repo' => $this->installer_theme_active_tab
                    ), $url );
                }
            }
        }
        return $url;
    }

    /** Save frontend theme tab selected */
    public function installer_theme_frontend_selected_tab() {
        if ( isset($_POST["frontend_tab_selected"]) ) {
            check_ajax_referer( 'installer_theme_frontend_selected_tab', 'installer_theme_frontend_selected_tab_nonce' );

            //Client_side_active_tab
            $frontend_tab_selected = sanitize_text_field( $_POST['frontend_tab_selected'] );
            if ( !(empty($frontend_tab_selected)) ) {
                //Front end tab selected
                update_option( 'wp_installer_clientside_active_tab', $frontend_tab_selected, false );

                //Check for registration status
                if ( isset($this->theme_user_registration[$frontend_tab_selected]) ) {
                    //Set
                    if ( !($this->theme_user_registration[$frontend_tab_selected]) ) {
                        //Not registered yet

                        if ( is_multisite() ) {
                            $admin_url_passed = network_admin_url();
                        } else {
                            $admin_url_passed = admin_url();
                        }

                        $registration_url = $admin_url_passed . 'plugin-install.php?tab=commercial#installer_repo_' . $frontend_tab_selected;

                        //Message and link
                        $theme_repo_name = $this->installer_theme_get_repo_product_name( $frontend_tab_selected );;
                        $response['unregistered_messages'] = sprintf( __( 'To install and update %s, please %sregister%s %s for this site.', 'installer' ),
                            $theme_repo_name, '<a href="' . $registration_url . '">', '</a>', $theme_repo_name );

                    }
                }

                $response['output'] = $frontend_tab_selected;
                echo json_encode( $response );
            }
            die();
        }
        die();
    }

    /** Installer loaded aux hooks */
    public function installer_theme_loaded_hooks() {

        if ( isset($this->installer_theme_subscription_type) ) {
            $subscription_type = intval( $this->installer_theme_subscription_type );
            if ( $subscription_type > 0 ) {
                //Client is subscribed
                add_filter( 'installer_theme_get_themes', array($this, 'installer_theme_filter_themes_by_subscription'), 10, 2 );
            }
        }

    }

    /** Get themes by subscription type */
    protected function installer_theme_get_themes_by_subscription( $subscription_type, $repo ) {

        $themes_associated_with_subscription = array();
        if ( isset(WP_Installer()->settings['repositories'][$repo]['data']['packages']) ) {
            //Set
            $packages = WP_Installer()->settings['repositories'][$repo]['data']['packages'];
            $available_themes_subscription = array();
            foreach ( $packages as $package_id => $package_details ) {
                if ( isset($package_details['products']) ) {
                    $the_products = $package_details['products'];
                    foreach ( $the_products as $product_slug => $product_details ) {
                        if ( isset($product_details['subscription_type']) ) {
                            $subscription_type_from_settings = intval( $product_details['subscription_type'] );
                            if ( $subscription_type_from_settings == $subscription_type ) {
                                //We found the subscription
                                if ( isset($product_details['themes']) ) {
                                    $themes_associated_with_subscription = $product_details['themes'];
                                    return $themes_associated_with_subscription;
                                }
                            }
                        }

                    }
                }
            }
        }
        return $themes_associated_with_subscription;
    }

    /** Filter API theme response according to user subscription */
    public function installer_theme_filter_themes_by_subscription( $themes, $active_tab ) {

        //Step1, we only filter OTGS themes
        $orig = count( $themes );
        if ( in_array( $active_tab, $this->theme_repo ) ) {
            //OTGS Theme
            //Step2, we retrieved the available themes based on client subscription
            if ( isset($this->installer_themes[$active_tab]) ) {
                $available_themes = $this->installer_themes[$active_tab];
                //Step3, we filter $themes based on this info
                if ( (is_array( $themes )) && (!(empty($themes))) ) {
                    foreach ( $themes as $k => $theme ) {
                        //Step4, get theme slug
                        if ( isset($theme['slug']) ) {
                            $theme_slug = $theme['slug'];
                            if ( !(empty($theme_slug)) ) {
                                if ( !(in_array( $theme_slug, $available_themes )) ) {
                                    //This theme is not in available themes
                                    unset($themes[$k]);
                                }
                            }
                        }
                    }
                }
            }
        }
        $new = count( $themes );
        if ( $orig != $new ) {
            //It is filtered
            $themes = array_values( $themes );
        }

        return $themes;
    }

    /** Hook to wp_loaded, fires when all Installer theme class is ready */
    public function installer_themes_support_set_up_func() {
        do_action( 'installer_themes_support_set_up' );
    }

}

/** Instantiate Installer Theme Class */
new Installer_Theme_Class;