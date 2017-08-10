<?php

final class WP_Installer{
    protected static $_instance = null;

    public $settings = array();

    private $repositories = array();

    protected $api_debug = '';

    private $config = array();

    protected $_plugins_renew_warnings = array();

    protected $_gz_on = false;

    private $admin_messages = array();

    private $_using_icl     = false;
    private $_wpml_version  = false;

    private $package_source = array();

    const SITE_KEY_VALIDATION_SOURCE_OTHER              = 0;
    const SITE_KEY_VALIDATION_SOURCE_DOWNLOAD_SPECIFIC  = 1;
    const SITE_KEY_VALIDATION_SOURCE_DOWNLOAD_REPORT    = 2;
    const SITE_KEY_VALIDATION_SOURCE_REGISTRATION       = 3;
    const SITE_KEY_VALIDATION_SOURCE_REVALIDATION       = 4;
    const SITE_KEY_VALIDATION_SOURCE_UPDATES_CHECK      = 5;

    public $dependencies;

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct(){

        if(!is_admin() || !is_user_logged_in()) return; //Only for admin

        $this->_gz_on = function_exists('gzuncompress') && function_exists('gzcompress');
        $this->settings = $this->get_settings();

        add_action('admin_notices', array($this, 'show_site_key_nags'));

        add_action('admin_notices', array($this, 'show_admin_messages'));

        add_action('admin_init', array($this, 'load_embedded_plugins'), 0);

        add_action('admin_menu', array($this, 'menu_setup'));
        add_action('network_admin_menu', array($this, 'menu_setup'));

        if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'installer_download_plugin'){
            add_filter( 'site_transient_update_plugins', array( $this, 'plugins_upgrade_check') );
        }
        add_filter('plugins_api', array( $this, 'custom_plugins_api_call'), 10, 3);
        add_filter('pre_set_site_transient_update_plugins', array( $this, 'plugins_upgrade_check'));

        // register repositories
        $this->load_repositories_list();

        if( empty($this->settings['last_repositories_update']) || time() - $this->settings['last_repositories_update'] > 86400
            || ( isset($_GET['force-check']) && $_GET['force-check'] == 1 ) ){
            $this->refresh_repositories_data();
        }

        // default config
        $this->config['plugins_install_tab'] = false;

        add_action('init', array($this, 'init'));

        //add_filter('wp_installer_buy_url', array($this, 'append_parameters_to_buy_url'));

        add_action('init', array($this,'load_locale'));

    }

    public function get_repositories() {

        return $this->repositories;

    }

    public function set_config($key, $value){

        $this->config[$key] = $value;

    }

    public function init(){
        global $pagenow;

        $this->dependencies = new Installer_Dependencies;

        if(empty($this->settings['_pre_1_0_clean_up'])) {
            $this->_pre_1_0_clean_up();
        }

        $this->settings = $this->_old_products_format_backwards_compatibility($this->settings);

        if ( !function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->_using_icl     = function_exists('wpml_site_uses_icl') && wpml_site_uses_icl();
        $this->_wpml_version  = defined('ICL_SITEPRESS_VERSION') ? ICL_SITEPRESS_VERSION : '';

        wp_enqueue_script('installer-admin', $this->res_url() . '/res/js/admin.js', array('jquery'), $this->version());
        wp_enqueue_style('installer-admin', $this->res_url() . '/res/css/admin.css', array(), $this->version());

        $translation_array = array(
            'installing'    => __( 'Installing %s', 'installer' ),
            'updating'      => __( 'Updating %s', 'installer' ),
            'activating'    => __( 'Activating %s', 'installer' )
        );

        wp_localize_script( 'installer-admin', 'installer_strings', $translation_array );

        if($pagenow == 'plugins.php'){
            add_action('admin_notices', array($this, 'setup_plugins_page_notices'));
            add_action('admin_notices', array($this, 'setup_plugins_renew_warnings'), 10);
            add_action('admin_notices', array($this, 'queue_plugins_renew_warnings'), 20);

            add_action('admin_init', array($this, 'setup_plugins_action_links'));

        }

        if($this->is_repositories_page()){
            add_action('admin_init', array($this, 'validate_repository_subscription'));
        }

        if(defined('DOING_AJAX')){
            add_action('wp_ajax_save_site_key', array($this, 'save_site_key'));
            add_action('wp_ajax_remove_site_key', array($this, 'remove_site_key_ajax'));
            add_action('wp_ajax_update_site_key', array($this, 'update_site_key'));

            add_action('wp_ajax_installer_download_plugin', array($this, 'download_plugin_ajax_handler'));
            add_action('wp_ajax_installer_activate_plugin', array($this, 'activate_plugin'));

            add_action('wp_ajax_installer_dismiss_nag', array($this, 'dismiss_nag'));
        }

        if($pagenow == 'update.php'){
            if(isset($_GET['action']) && $_GET['action'] == 'update-selected'){
                add_action('admin_head', array($this, 'plugin_upgrade_custom_errors'));         //iframe/bulk
            }else{
                add_action('all_admin_notices', array($this, 'plugin_upgrade_custom_errors'));  //regular/singular
            }
        }

        // WP 4.2
        if(defined('DOING_AJAX')){
            add_action('wp_ajax_update-plugin', array($this, 'plugin_upgrade_custom_errors'), 0); // high priority, before WP
        }

        //Include theme support
        include_once $this->plugin_path() . '/includes/class-installer-theme.php';

        // Extra information about the source of Installer
        $package_source_file = $this->plugin_path() . '/installer-source.json';
        if( file_exists( $package_source_file ) ){
            WP_Filesystem();
            global $wp_filesystem;
            $this->package_source = json_decode( $wp_filesystem->get_contents( $package_source_file ) );
        }
    }

    protected function log($message){
	    require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;
        if( defined('WPML_INSTALLER_LOGGING') && WPML_INSTALLER_LOGGING ){
            $wp_filesystem->put_contents( $this->plugin_path() . '/installer.log', current_time( 'mysql' ) . "\t" . $message . "\n" );
        }
    }

    public function register_admin_message($text, $type = 'updated'){
        $this->admin_messages[] = array('text' => $text, 'type' => $type);
    }

    public function show_admin_messages(){
        if(!empty($this->admin_messages)){
            $types = array( 'error', 'updated', 'notice' );
            foreach($this->admin_messages as $message){
                $class = in_array( $message['type'], $types ) ? $message['type'] : 'updated';
                ?>
                <div class="<?php echo $class ?>">
                    <p>
                        <?php echo $message['text'] ?>
                    </p>
                </div>
                <?php
            }
        }
    }

    public function load_locale(){
        $locale = get_locale();
        $locale = apply_filters( 'plugin_locale', $locale, 'installer' );
        $mo_file = $this->plugin_path() . '/locale/installer-' . $locale . '.mo';
        if(file_exists($mo_file)){
            load_textdomain( 'installer', $mo_file  );
        }
    }

    public function load_embedded_plugins(){
        if(file_exists($this->plugin_path() . '/embedded-plugins' )) {
            include_once $this->plugin_path() . '/embedded-plugins/embedded-plugins.class.php';
            $this->installer_embedded_plugins = new Installer_Embedded_Plugins();
        }
    }

    public function menu_setup(){
        global $pagenow;

        if(is_multisite() && !is_network_admin()){
            $this->menu_multisite_redirect();
            add_options_page(__('Installer', 'installer'), __('Installer', 'installer'), 'manage_options', 'installer', array($this, 'show_products'))            ;
        }else{
            if($this->config['plugins_install_tab'] && is_admin() && $pagenow == 'plugin-install.php'){
                // Default GUI, under Plugins -> Install
                add_filter('install_plugins_tabs', array($this, 'add_install_plugins_tab'));
                add_action('install_plugins_commercial', array($this, 'show_products'));
            }
        }

    }

    public function menu_url(){
        if(is_multisite()){
            if(is_network_admin()){
                $url = network_admin_url('plugin-install.php?tab=commercial');
            }else{
                $url = admin_url('options-general.php?page=installer');
            }
        }else{
            $url = admin_url('plugin-install.php?tab=commercial');
        }
        return $url;
    }

    private function menu_multisite_redirect(){
        global $pagenow;

        if($pagenow == 'plugin-install.php' && isset($_GET['tab']) && $_GET['tab'] == 'commercial'){
            wp_redirect($this->menu_url());
            exit;
        }

    }

    private function _pre_1_0_clean_up(){
        global $wpdb;

        if(!defined('WPRC_VERSION')){
            $old_tables = array(
                $wpdb->prefix . 'wprc_cached_requests',
                $wpdb->prefix . 'wprc_extension_types',
                $wpdb->prefix . 'wprc_extensions',
                $wpdb->prefix . 'wprc_repositories',
                $wpdb->prefix . 'wprc_repositories_relationships',
            );

            foreach($old_tables as $table){
                $wpdb->query(sprintf("DROP TABLE IF EXISTS %s", $table));
            }

        }

        $this->settings['_pre_1_0_clean_up'] = true;
        $this->save_settings();
    }

    public function setup_plugins_action_links(){

        $plugins = get_plugins();

        $repositories_plugins = array();

        if( !empty($this->settings['repositories']) ) {

            foreach ( $this->settings['repositories'] as $repository_id => $repository ) {

                foreach ( $repository['data']['packages'] as $package ) {

                    foreach ( $package['products'] as $product ) {

                        foreach ( $product['plugins'] as $plugin_slug ) {

                            $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                            if ( !isset($repositories_plugins[$repository_id][$download['slug']]) ) {
                                $repositories_plugins[$repository_id][$download['slug']] = array(
                                    'name' => $download['name'],
                                    'registered' => $this->plugin_is_registered( $repository_id, $download['slug'] ) ? 1 : 0
                                );
                            }

                        }

                    }

                }

                foreach ( $plugins as $plugin_id => $plugin ) {

                    $wp_plugin_slug = dirname( $plugin_id );
                    if ( empty($wp_plugin_slug) ) {
                        $wp_plugin_slug = basename( $plugin_id, '.php' );
                    }

                    foreach ( $repositories_plugins as $repository_id => $r_plugins ) {

                        foreach ( $r_plugins as $slug => $r_plugin ) {

                            if ( $wp_plugin_slug == $slug || $r_plugin['name'] == $plugin['Name'] || $r_plugin['name'] == $plugin['Title'] ) { //match order: slug, name, title

                                if ( $r_plugin['registered'] ) {
                                    add_filter( 'plugin_action_links_' . $plugin_id, array($this, 'plugins_action_links_registered') );
                                } else {
                                    add_filter( 'plugin_action_links_' . $plugin_id, array($this, 'plugins_action_links_not_registered') );
                                }

                            }

                        }

                    }


                }

            }
        }

    }

    public function plugins_action_links_registered($links){
        $links[] = '<a href="' . $this->menu_url() . '">' . __('Registered', 'installer') . '</a>';
        return $links;
    }

    public function plugins_action_links_not_registered($links){
        $links[] = '<a href="' . $this->menu_url() . '">' . __('Register', 'installer') . '</a>';
        return $links;
    }

    public function plugin_is_registered($repository_id, $slug){

        $registered = false;

        if( $this->repository_has_valid_subscription($repository_id) ){

            $subscription_type = $this->get_subscription_type_for_repository($repository_id);
            $r_plugins = array();

            foreach($this->settings['repositories'][$repository_id]['data']['packages'] as $package){

                foreach($package['products'] as $product){

                    if( $product['subscription_type'] == $subscription_type || $this->have_superior_subscription($subscription_type, $product) ) {

                        foreach ($product['plugins'] as $plugin_slug) {

                            $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                            if (!isset($rep_plugins[$download['slug']])) {
                                $r_plugins[$download['slug']] = $download['slug'];
                            }

                        }

                    }

                }

            }

            $registered = isset($r_plugins[$slug]);

        }


        return $registered;

    }

    public function version(){
        return WP_INSTALLER_VERSION;
    }

    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( dirname(__FILE__) ) );
    }

    public function plugin_url() {
        if(isset($this->config['in_theme_folder']) && !empty($this->config['in_theme_folder'])){
            $url = untrailingslashit(get_template_directory_uri() . '/' . $this->config['in_theme_folder']);
        }else{
            $url = untrailingslashit( plugins_url( '/', dirname(__FILE__) ) );
        }

        return $url;
    }

    public function is_repositories_page(){
        global $pagenow;

        return $pagenow == 'plugin-install.php' && isset($_GET['tab']) && $_GET['tab'] == 'commercial';
    }

    public function res_url(){
        if(isset($this->config['in_theme_folder']) && !empty($this->config['in_theme_folder'])){
            $url = untrailingslashit(get_template_directory_uri() . '/' . $this->config['in_theme_folder']);
        }else{
            $url = $this->plugin_url();
        }
        return $url;
    }

    public function save_settings(){

        $_settings = serialize($this->settings);
        if($this->_gz_on){
            $_settings =  gzcompress($_settings);
        }
        $_settings = base64_encode($_settings);

        update_option( 'wp_installer_settings', $_settings );

        if( is_multisite() && is_main_site() && isset($this->settings['repositories']) ){
            $network_settings = array();

            foreach( $this->settings['repositories'] as $rep_id => $repository ){
                if( isset($repository['subscription']) )
                    $network_settings[$rep_id] = $repository['subscription'];
            }

            update_site_option( 'wp_installer_network', $network_settings );


        }

    }

    public function get_settings($refresh = false){

        if($refresh || empty($this->settings)){

            $_settings = get_option('wp_installer_settings');


            if (is_array($_settings) || empty($_settings)) { //backward compatibility 1.1
                $this->settings = $_settings;

            } else {
                $_settings = base64_decode($_settings);
                if ($this->_gz_on) {
                    $_settings = gzuncompress($_settings);
                }
                $this->settings = unserialize($_settings);
            }

            if (is_multisite() && isset($this->settings['repositories'])) {
                $network_settings = maybe_unserialize(get_site_option('wp_installer_network'));
                if ($network_settings) {
                    foreach ($this->settings['repositories'] as $rep_id => $repository) {
                        if (isset($network_settings[$rep_id])) {
                            $this->settings['repositories'][$rep_id]['subscription'] = $network_settings[$rep_id];
                        }
                    }
                }
            }

            $this->load_hardcoded_site_keys();

            $this->settings = $this->_pre_1_6_backwards_compatibility($this->settings);

            $this->settings = $this->_old_products_format_backwards_compatibility($this->settings);

        }

        return $this->settings;
    }

    private function load_hardcoded_site_keys(){

        if( !empty( $this->settings['repositories'] ) ) {
            foreach ( $this->settings['repositories'] as $repository_id => $repository ) {

                if ( $site_key = self::get_repository_hardcoded_site_key( $repository_id ) ) {

                    $site_key_missing = empty($this->settings['repositories'][$repository_id]['subscription']['data']);
                    $site_key_changed = !$site_key_missing &&
                        $this->settings['repositories'][$repository_id]['subscription']['key'] != $site_key;

                    if ( $site_key_missing || $site_key_changed ) {

                        if ( !function_exists( 'get_plugins' ) ) {
                            require_once ABSPATH . 'wp-admin/includes/plugin.php';
                        }
                        $this->load_repositories_list();
                        $response = $this->save_site_key(
                            array(
                                'repository_id' => $repository_id,
                                'site_key' => $site_key,
                                'return' => true,
                                'nonce' => wp_create_nonce( 'save_site_key_' . $repository_id )
                            )
                        );

                        if ( !empty($response['error']) ) {
                            $this->remove_site_key( $repository_id );

                            $this->admin_messages[] = array(
                                'type' => 'error',
                                'text' => sprintf( __( 'You are using an invalid site key defined as the constant %s (most likely in wp-config.php). 
                                                Please remove it or use the correct value in order to be able to register correctly.', 'installer' ), 'OTGS_INSTALLER_SITE_KEY_' . strtoupper( $repository_id ) )
                            );

                        }

                    }

                }

            }
        }

    }

    public static function get_repository_hardcoded_site_key( $repository_id ){

        $site_key = false;

        $site_key_constant = 'OTGS_INSTALLER_SITE_KEY_' . strtoupper( $repository_id );
        if( defined( $site_key_constant ) ){
            $site_key = constant( $site_key_constant );
        }

        return $site_key;
    }

    //backward compatibility, will remove 'basename' in version 1.8
    private function _pre_1_6_backwards_compatibility($settings){

        if( version_compare($this->version(), '1.8', '<') && !empty($settings['repositories']) ){

            foreach ($settings['repositories'] as $repository_id => $repository) {

                foreach ($repository['data']['downloads']['plugins'] as $slug => $download) {

                    $settings['repositories'][$repository_id]['data']['downloads']['plugins'][$slug]['slug'] = $download['basename'];

                }
            }

        }

        return $settings;

    }

    //backward compatibility - support old products list format (downloads under products instead of global downloads list)
    private function _old_products_format_backwards_compatibility($settings){

        if( version_compare($this->version(), '1.8', '<') && !empty($settings['repositories']) && empty($this->_old_products_format_backwards_compatibility) ) {

            foreach ($settings['repositories'] as $repository_id => $repository) {

                $populate_downloads = false;

                foreach ($repository['data']['packages'] as $package_id => $package) {

                    foreach ($package['products'] as $product_id => $product) {

                        if (!isset($product['plugins'])) {

                            $populate_downloads = true;

                            foreach ($product['downloads'] as $download_id => $download) {

                                $settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['plugins'][] = $download['slug'];

                            }

                        }

                    }

                }

                if ($populate_downloads) {

                    // Add downloads branch
                    foreach ($repository['data']['packages'] as $package_id => $package) {

                        foreach ($package['products'] as $product_id => $product) {

                            foreach ($product['downloads'] as $download_id => $download) {

                                if (!isset($settings['repositories'][$repository_id]['data']['downloads']['plugins'][$download['slug']])) {
                                    $settings['repositories'][$repository_id]['data']['downloads']['plugins'][$download['slug']] = $download;
                                }

                                $settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['plugins'][] = $download['slug'];
                            }

                            unset($settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['downloads']);

                        }

                    }

                }

            }

            $this->_old_products_format_backwards_compatibility = true;

        }

        return $settings;

    }

    public function get_installer_site_url( $repository_id = false ){
        global $current_site;

        $site_url = get_site_url();

        if( $repository_id && is_multisite() && isset( $this->settings['repositories'] ) ){
            $network_settings = maybe_unserialize( get_site_option('wp_installer_network') );

            if ( isset( $network_settings[$repository_id] ) ) {
                $site_url = get_site_url( $current_site->blog_id );
            }

        }

        return $site_url;
    }

    public function show_site_key_nags(){
        $screen = get_current_screen();

        if($screen->base == 'settings_page_installer' || ($screen->base == 'plugin-install' && isset($_GET['tab']) && $_GET['tab'] == 'commercial')){
            return;
        }

        if(!empty($this->config['site_key_nags'])){

            foreach($this->config['site_key_nags'] as $nag){

                if(!$this->repository_has_subscription($nag['repository_id'] )){
                    $show = true;
                    if(!empty($nag['condition_cb'])){
                        $show = call_user_func($nag['condition_cb']);
                    }

                    if(empty($this->settings['dismissed_nags'][$nag['repository_id']]) && $show){
                        echo '<div class="updated error otgs-is-dismissible"><p>';
                        printf(__("To get automatic updates, you need to register %s for this site. %sRegister %s%s", 'sitepress'),
                            $nag['product_name'], '<a class="button-primary" href="' . $this->menu_url() . '">', $nag['product_name'], '</a>');

                        echo '</p>';
                        echo '<span class="installer-dismiss-nag notice-dismiss" data-repository="' . $nag['repository_id']  . '"><span class="screen-reader-text">' . __('Dismiss', 'sitepress') . '</span></span>';
                        echo '</div>';
                    }
                }

            }

        }

    }

    public function dismiss_nag(){
        $this->settings['dismissed_nags'][$_POST['repository']] = 1;

        $this->save_settings();

        echo json_encode(array());
        exit;
    }

    public function add_install_plugins_tab($tabs){

        $tabs['commercial'] = __('Commercial', 'installer');

        return $tabs;
    }

    public function load_repositories_list(){
        global $wp_installer_instances;

        foreach ($wp_installer_instances as $instance) {

            if (file_exists(dirname($instance['bootfile']) . '/repositories.xml')) {
                $config_file = dirname($instance['bootfile']) . '/repositories.xml';

                if (file_exists(dirname($instance['bootfile']) . '/repositories.sandbox.xml')) {
                    $config_file = dirname($instance['bootfile']) . '/repositories.sandbox.xml';
                    add_filter('https_ssl_verify', '__return_false');
                }

                $repos = simplexml_load_file($config_file);

                if($repos) {
                    foreach ($repos as $repo) {
                        $id = strval($repo->id);

                        $data['api-url'] = strval($repo->apiurl);
                        $data['products'] = strval($repo->products);

                        // excludes rule;
                        if (isset($this->config['repositories_exclude']) && in_array($id, $this->config['repositories_exclude'])) {
                            continue;
                        }

                        // includes rule;
                        if (isset($this->config['repositories_include']) && !in_array($id, $this->config['repositories_include'])) {
                            continue;
                        }

                        $this->repositories[$id] = $data;

                    }
                }

            }
        }

    }

    public function filter_repositories_list(){

        if(!empty($this->settings['repositories'])) {
            foreach ($this->settings['repositories'] as $id => $repo_data) {

                // excludes rule;
                if (isset($this->config['repositories_exclude']) && in_array($id, $this->config['repositories_exclude'])) {
                    unset($this->settings['repositories'][$id]);
                }

                // includes rule;
                if (isset($this->config['repositories_include']) && !in_array($id, $this->config['repositories_include'])) {
                    unset($this->settings['repositories'][$id]);
                }


            }
        }


    }

    public function refresh_repositories_data(){
        static $checked = false;

        if( defined('OTGS_DISABLE_AUTO_UPDATES') && OTGS_DISABLE_AUTO_UPDATES && empty($_GET['force-check']) || $checked ){

            if(empty($this->settings['repositories']) && $this->is_repositories_page()){

                foreach($this->repositories as $id => $data) {
                    $repository_names[] = $id;

                }

                $error = sprintf(__("Installer cannot display the products information because the automatic updating for %s was explicitly disabled with the configuration below (usually in wp-config.php):", 'installer'), strtoupper( join(', ', $repository_names) ));
                $error .= '<br /><br /><code>define("OTGS_DISABLE_AUTO_UPDATES", true);</code><br /><br />';
                $error .= sprintf(__("In order to see the products information, please run the %smanual updates check%s to initialize the products list or (temporarily) remove the above code.", 'installer'), '<a href="' . admin_url('update-core.php') . '">', '</a>');

                $this->register_admin_message($error, 'error');


            }

            return;
        }

        $checked = true;

        foreach($this->repositories as $id => $data){

            $response = wp_remote_get($data['products']);

            if(is_wp_error($response)){
                // http fallback
                $data['products'] = preg_replace("@^https://@", 'http://', $data['products']);
                $response = wp_remote_get($data['products']);
            }

            if(is_wp_error($response)){

                $error = sprintf(__("Installer cannot contact our updates server to get information about the available products and check for new versions. If you are seeing this message for the first time, you can ignore it, as it may be a temporary communication problem. If the problem persists and your WordPress admin is slowing down, you can disable automated version checks. Add the following line to your wp-config.php file:", 'installer'), strtoupper($id));
                $error .= '<br /><br /><code>define("OTGS_DISABLE_AUTO_UPDATES", true);</code>';

                $this->register_admin_message($error, 'error');

                continue;
            }

            if($response && isset($response['response']['code']) && $response['response']['code'] == 200){
                $body = wp_remote_retrieve_body($response);
                if($body){
                    $products = json_decode($body, true);

                    if(is_array($products)){
                        $this->settings['repositories'][$id]['data'] = $products;
                        $this->settings = $this->_pre_1_6_backwards_compatibility($this->settings);
                    }
                }

            }

            $this->log( sprintf("Checked for %s updates: %s", $id, $data['products']) );


        }

        // cleanup
        if(empty($this->settings['repositories'])){
            $this->settings['repositories'] = array();
        }
        foreach($this->settings['repositories'] as $id => $data){
            if(!in_array($id, array_keys($this->repositories))){
                unset($this->settings['repositories'][$id]);
            }
        }

        $this->settings['last_repositories_update']= time();

        $this->save_settings();

    }

    public function show_products($args = array()){

        $screen = get_current_screen();

        if($screen->base == 'settings_page_installer'){ // settings page
            echo '<div class="wrap">';
            echo '<h2>' . __('Installer', 'installer') . '</h2>';
            echo '<br />';
        }

        if(!is_array($args)) $args = array();
        if(empty($args['template'])) $args['template'] = 'default';

        $this->filter_repositories_list();

        if(!empty($this->settings['repositories'])){

            $this->localize_strings();
            $this->set_filtered_prices($args);
            $this->set_hierarchy_and_order();

            foreach($this->settings['repositories'] as $repository_id => $repository){

                if($args['template'] == 'compact'){

                    if(isset($args['repository']) && $args['repository'] == $repository_id){
                        include $this->plugin_path() . '/templates/products-compact.php';
                    }

                }else{

                    include $this->plugin_path() . '/templates/repository-listing.php';

                }

                unset($site_key, $subscription_type, $expired, $upgrade_options, $products_avaliable);

            }

        }else{

            echo '<center>' . __('No repositories defined.', 'installer') . '</center>';

        }

        if($screen->base == 'settings_page_installer'){ // settings page
            echo '</div>';
        }


    }

    public function get_product_price($repository_id, $package_id, $product_id, $incl_discount = false){

        $price = false;

        foreach($this->settings['repositories'][$repository_id]['data']['packages'] as $package ){

            if($package['id'] == $package_id){
                if(isset($package['products'][$product_id])){
                    if($incl_discount && isset($package['products'][$product_id]['price_disc'])){
                        $price = $package['products'][$product_id]['price_disc'];
                    }elseif(isset($package['products'][$product_id]['price'])){
                        $price = $package['products'][$product_id]['price'];
                    }
                }
                break;
            }
        }

        return $price;
    }

    private function _render_product_packages($packages, $subscription_type, $expired, $upgrade_options, $repository_id){

        $data = array();

        foreach($packages as $package_id => $package){

            $row = array('products' => array(), 'downloads' => array());
            foreach($package['products'] as $product){

                // filter out free subscriptions from being displayed as buying options
                if( empty($product['price']) && (empty($subscription_type) || $expired) ){
                    continue;
                }

                //consider equivalent subscriptions
                if( empty($product['subscription_type_equivalent'])){
	                $product['subscription_type_equivalent'] = '';
                }

                // buy base
                if(empty($subscription_type) || $expired) {

                    $p['url'] = $this->append_parameters_to_buy_url($product['url'], $repository_id);
                    if (!empty($product['price_disc'])) {
                        $p['label'] = $product['call2action'] . ' - ' . sprintf('$%s %s$%d%s (USD)', $product['price_disc'], '&nbsp;&nbsp;<del>', $product['price'], '</del>');
                    } else {
                        $p['label'] = $product['call2action'] . ' - ' . sprintf('$%d (USD)', $product['price']);
                    }
                    $row['products'][] = $p;

                    // renew
                } elseif(isset($subscription_type) && ($product['subscription_type'] == $subscription_type || $product['subscription_type_equivalent'] == $subscription_type)){

                    if($product['renewals']) {
                        foreach ($product['renewals'] as $renewal) {
                            $p['url'] = $this->append_parameters_to_buy_url($renewal['url'], $repository_id);
                            $p['label'] = $renewal['call2action'] . ' - ' . sprintf('$%d (USD)', $renewal['price']);
                        }

                        $row['products'][] = $p;
                    }

                }

                // upgrades
                if(!empty($upgrade_options[$product['subscription_type']])){

                    foreach($upgrade_options[$product['subscription_type']] as $stype => $upgrade){
                        if($stype != $subscription_type) continue;

                        $p['url'] = $this->append_parameters_to_buy_url($upgrade['url'], $repository_id);
                        if (!empty($upgrade['price_disc'])) {
                            $p['label'] = $upgrade['call2action'] . ' - ' . sprintf('$%s %s$%d%s (USD)', $upgrade['price_disc'], '&nbsp;&nbsp;<del>', $upgrade['price'], '</del>');
                        } else {
                            $p['label'] = $upgrade['call2action'] . ' - ' . sprintf('$%d (USD)', $upgrade['price']);
                        }
                        $row['products'][] = $p;

                    }

                }

                // downloads
                if(isset($subscription_type) && !$expired && ($product['subscription_type'] == $subscription_type || $product['subscription_type_equivalent'] == $subscription_type)){
                    foreach($product['plugins'] as $plugin_slug){

                        $row['downloads'][] = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                    }

                }

                //subpackages
                if(!empty($package['sub-packages'])){
                    $row['sub-packages'] = $package['sub-packages'];
                }

            }

            $row['id']          = $package['id'];
            $row['image_url']   = $package['image_url'];
            $row['name']        = $package['name'];
            $row['description'] = $package['description'];

            if(!empty($row['products']) || !empty($row['downloads']) || !empty($row['sub-packages'])){
                $data[] = $row;
            }


        }

        return $data;

    }

    public function get_extra_url_parameters(){

        $parameters = array();

        if(!empty($this->package_source)){
            foreach($this->package_source as $key => $val){
                $parameters[$key] = $val;
            }
        }

        $parameters['installer_version']  = WP_INSTALLER_VERSION;
        $parameters['theme']              = wp_get_theme()->get( 'Name' );
        $parameters['site_name']          = get_bloginfo( 'name' );

        return $parameters;
    }

    public function append_parameters_to_buy_url($url, $repository_id, $args = array()){

        $url = add_query_arg( array('icl_site_url' => $this->get_installer_site_url( $repository_id ) ), $url );

        $affiliate_id   = false;
        $affiliate_key  = false;

        // Add extra parameters for custom Installer packages
        if( !empty($this->package_source) ){
            $extra = $this->get_extra_url_parameters();

            if( !empty($extra['repository']) && $extra['repository'] == $repository_id ) {

                if( !empty($extra['affiliate_key']) && !empty($extra['user_id']) ){
                    $this->config['affiliate_id:' . $repository_id] = $extra['user_id'];
                    $this->config['affiliate_key:' . $repository_id] = $extra['affiliate_key'];
                    unset($extra['affiliate_key'], $extra['user_id'], $extra['repository']); // no need to include these ones
                }

                $url = add_query_arg($extra, $url);
            }

        }

        if(isset($this->config['affiliate_id:' . $repository_id]) && isset($this->config['affiliate_key:' . $repository_id])){

            $affiliate_id  = $this->config['affiliate_id:' . $repository_id];
            $affiliate_key = $this->config['affiliate_key:' . $repository_id];

        }elseif(isset($args['affiliate_id:' . $repository_id]) && isset($args['affiliate_key:' . $repository_id])){

            $affiliate_id   = $args['affiliate_id:' . $repository_id];
            $affiliate_key  = $args['affiliate_key:' . $repository_id];

        }elseif(defined('ICL_AFFILIATE_ID') && defined('ICL_AFFILIATE_KEY')){ //support for 1 repo

            $affiliate_id  = ICL_AFFILIATE_ID;
            $affiliate_key = ICL_AFFILIATE_KEY;

        }elseif(isset($this->config['affiliate_id']) && isset($this->config['affiliate_key'])) {
            // BACKWARDS COMPATIBILITY
            $affiliate_id = $this->config['affiliate_id'];
            $affiliate_key = $this->config['affiliate_key'];
        }

        if($affiliate_id && $affiliate_key){
            $url = add_query_arg(array('aid' => $affiliate_id, 'affiliate_key' => $affiliate_key), $url);
        }

        if($repository_id == 'wpml'){
            $url = add_query_arg(array('using_icl' => $this->_using_icl, 'wpml_version' => $this->_wpml_version), $url);
        }

        $url = apply_filters('wp_installer_buy_url', $url);

        $url = esc_url($url);

        return $url;

    }

    public function save_site_key($args = array()){

        $error = '';

        if( isset( $args['repository_id'] ) ){
            $repository_id  = $args['repository_id'];
        }elseif( isset( $_POST['repository_id'] ) ){
            $repository_id = sanitize_text_field( $_POST['repository_id'] );
        }else{
            $repository_id = false;
        }

        if( isset( $args['nonce'] ) ){
            $nonce = $args['nonce'];
        }elseif( isset($_POST['nonce'] ) ){
            $nonce = sanitize_text_field( $_POST['nonce'] );
        }else{
            $nonce = '';
        }

        if( isset( $args['site_key'] ) ){
            $site_key = $args['site_key'];
        } else {
            $site_key = sanitize_text_field( $_POST[ 'site_key_' . $repository_id] );
        }
        $site_key = preg_replace("/[^A-Za-z0-9]/", '', $site_key);

        if($repository_id && $nonce && wp_create_nonce('save_site_key_' . $repository_id) == $nonce){

            try {
                $subscription_data = $this->fetch_subscription_data( $repository_id, $site_key, self::SITE_KEY_VALIDATION_SOURCE_REGISTRATION );

                if ( $subscription_data ) {
                    $this->settings['repositories'][$repository_id]['subscription'] = array('key' => $site_key, 'data' => $subscription_data);
                    $this->save_settings();
                } else {
                    $error = __( 'Invalid site key for the current site.', 'installer' )
                        . '<br /><div class="installer-footnote">' .  __('Please note that the site key is case sensitive.', 'installer') . '</div>';
                }

            } catch (Exception $e ){
                $error = $e->getMessage();
                if( preg_match('#Could not resolve host: (.*)#', $error, $matches) || preg_match('#Couldn\'t resolve host \'(.*)\'#', $error, $matches) ){
                    $error = sprintf(__("%s cannot access %s to register. Try again to see if it's a temporary problem. If the problem continues, make sure that this site has access to the Internet. You can still use the plugin without registration, but you will not receive automated updates.", 'installer'),
                        '<strong><i>' . $this->get_generic_product_name($repository_id) . '</i></strong>',
                        '<strong><i>' . $matches[1]. '</i></strong>'
                    ) ;
                }
            }

        }

        $return = array('error' => $error);

        if($this->api_debug){
            $return['debug'] = $this->api_debug;
        }

        if(!empty($args['return'])){
            return $return;
        }else{
            echo json_encode($return);
            exit;
        }

    }

    /**
     * Alias for WP_Installer::get_repository_site_key
     * @see WP_Installer::get_repository_site_key()
     *
     * @param string $repository_id
     * @return string (site key) or bool
     */
    public function get_site_key($repository_id){
        return WP_Installer::get_repository_site_key( $repository_id );
    }

    public function remove_site_key( $repository_id ){
        if( isset( $this->settings['repositories'][$repository_id] ) ){
            unset($this->settings['repositories'][$repository_id]['subscription']);
            $this->save_settings();
            $this->refresh_repositories_data();
        }
    }

    public function remove_site_key_ajax(){
        if($_POST['nonce'] == wp_create_nonce('remove_site_key_' . $_POST['repository_id'])){
            $this->remove_site_key( $_POST['repository_id'] );
        }
        exit;
    }

    public function validate_repository_subscription(){
        $repository_id = isset($_GET['validate_repository']) ? sanitize_text_field( $_GET['validate_repository'] ) : false;
        if($repository_id){

            $site_key = $this->get_site_key($repository_id);
            if($site_key) {
                $subscription_data = $this->fetch_subscription_data( $repository_id, $site_key, self::SITE_KEY_VALIDATION_SOURCE_REVALIDATION);
                if(empty($subscription_data)){
                    unset($this->settings['repositories'][$repository_id]['subscription']);
                    delete_site_transient('update_plugins');
                    $this->save_settings();
                }
            }

            wp_redirect($this->menu_url() . '#repository-' . $repository_id);
            exit;

        }

    }

    public function update_site_key(){

        $error = '';

        $repository_id = sanitize_text_field ( $_POST['repository_id'] );
        if($_POST['nonce'] == wp_create_nonce('update_site_key_' . $repository_id )){

            $site_key = $this->get_site_key($_POST['repository_id']);

            if($site_key){
                try {
                    $subscription_data = $this->fetch_subscription_data( $repository_id, $site_key, self::SITE_KEY_VALIDATION_SOURCE_UPDATES_CHECK );

                    if ( $subscription_data ) {
                        $this->settings['repositories'][$repository_id]['subscription'] = array('key' => $site_key, 'data' => $subscription_data);

                        //also refresh products information
                        $this->refresh_repositories_data();

                        $this->save_settings();

                    } else {
                        unset($this->settings['repositories'][$repository_id]['subscription']);
                        $error = __( 'Invalid site key for the current site. If the error persists, try to unregister first and then register again with the same site key.', 'installer' );
                    }


                } catch (Exception $e ){
                    $error = $e->getMessage();
                    if( preg_match('#Could not resolve host: (.*)#', $error, $matches) || preg_match('#Couldn\'t resolve host \'(.*)\'#', $error, $matches) ){
                        $error = sprintf(__("%s cannot access %s to register. Try again to see if it's a temporary problem. If the problem continues, make sure that this site has access to the Internet. You can still use the plugin without registration, but you will not receive automated updates.", 'installer'),
                            '<strong><i>' . $this->get_generic_product_name($repository_id) . '</i></strong>',
                            '<strong><i>' . $matches[1]. '</i></strong>'
                        ) ;
                    }
                }

            }

        }

        echo json_encode(array('error' => $error));

        exit;
    }

    public function api_debug_log($text){

        if(defined('WPML_DEBUG_INSTALLER') && WPML_DEBUG_INSTALLER){

            if(!is_scalar($text)){
                $text = print_r($text, 1);
            }

            $this->api_debug .= $text . "\n";

        }

    }

    public function fetch_subscription_data( $repository_id, $site_key, $source = self::SITE_KEY_VALIDATION_SOURCE_OTHER ){

        $subscription_data = false;

        $args['body'] = array(
            'action'    => 'site_key_validation',
            'site_key'  => $site_key,
            'site_url'  => $this->get_installer_site_url( $repository_id ),
            'source'    => $source
        );

        if($repository_id == 'wpml'){
            $args['body']['using_icl']      = $this->_using_icl;
            $args['body']['wpml_version']   = $this->_wpml_version;
        }

        $args['body']['installer_version']  = WP_INSTALLER_VERSION;
        $args['body']['theme']              = wp_get_theme()->get( 'Name' );
        $args['body']['site_name']          = get_bloginfo( 'name' );

        $args['body']['versions']           = $this->get_local_product_versions( $repository_id );

        $args['timeout'] = 45;

        // Add extra parameters for custom Installer packages
        if( !empty($this->package_source) ){
            $extra = $this->get_extra_url_parameters();
            if( !empty($extra['repository']) && $extra['repository'] == $repository_id ) {
                unset($extra['repository']);
                foreach($extra as $key => $val){
                    $args['body'][$key] = $val;
                }
            }
        }

        $response = wp_remote_post($this->repositories[$repository_id]['api-url'], $args);

        $this->api_debug_log("POST {$this->repositories[$repository_id]['api-url']}");
        $this->api_debug_log($args);

        $this->log("POST {$this->repositories[$repository_id]['api-url']} - fetch subscription data");

        if( !is_wp_error($response) ){
            $datas = wp_remote_retrieve_body($response);

            if(is_serialized($datas)){
                $data =  unserialize($datas);
                $this->api_debug_log($data);

                if( !empty( $data->subscription_data ) ){
                    $subscription_data =  $data->subscription_data;
                }

                do_action( 'installer_fetched_subscription_data',  $data, $repository_id);

            }else{
                $this->api_debug_log($datas);
            }

        }else{

            $this->api_debug_log($response);
            throw new Exception( $response->get_error_message() );
        }

        return $subscription_data;

    }

    function get_local_product_versions( $repository_id ){

        $versions = array();

        foreach( $this->settings['repositories'][$repository_id]['data']['packages'] as $package_id => $package ){

            foreach( $package['products'] as $product_id => $product ){

                foreach( $product['plugins'] as $plugin_slug ){

                    $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                    if( empty( $versions[$download['slug']] ) ) {
                        $v = $this->get_plugin_installed_version($download['name'], $download['slug']);
                        if($v){
                            $versions[$download['slug']] = $v;
                        }
                    }

                }

            }

        }

        return $versions;
    }

    public function get_repository_site_key($repository_id){
        $site_key = false;

        if(!empty($this->settings['repositories'][$repository_id]['subscription']['key'])){
            $site_key = $this->settings['repositories'][$repository_id]['subscription']['key'];
        }

        return $site_key;
    }

    public function repository_has_valid_subscription($repository_id){

        $valid = false;

        if(!empty($this->settings['repositories'][$repository_id]['subscription'])){

            $subscription = $this->settings['repositories'][$repository_id]['subscription']['data'];
            $valid = ( $subscription->status == 1 && (strtotime($subscription->expires) > time() || empty($subscription->expires)) ) || $subscription->status == 4;

        }
        return $valid;

    }

    public function repository_has_subscription($repository_id){
        $key = false;
        if(!empty($this->settings['repositories'][$repository_id]['subscription']['key'])){
            $key = $this->settings['repositories'][$repository_id]['subscription']['key'];
        }

        return $key;

    }

    public function repository_has_expired_subscription($repository_id){

        return $this->repository_has_subscription($repository_id) && !$this->repository_has_valid_subscription($repository_id);

    }

    public function get_generic_product_name($repository_id){

        return $this->settings['repositories'][$repository_id]['data']['product-name'];

    }

    public function show_subscription_renew_warning($repository_id, $subscription_id){

        $show = false;

        $data = $this->settings['repositories'][$repository_id]['data'];
        if(!empty($data['subscriptions_meta'])){
            if(isset($data['subscriptions_meta']['expiration'])){

                if(!empty($data['subscriptions_meta']['expiration'][$subscription_id])){

                    $days       = $data['subscriptions_meta']['expiration'][$subscription_id]['days_warning'];
                    $message    = $data['subscriptions_meta']['expiration'][$subscription_id]['warning_message'];

                }else{

                    //defaults
                    $days       = 30;
                    $message    = __('You will have to renew your subscription in order to continue getting the updates and support.', 'installer');

                }

                if(!empty($this->settings['repositories'][$repository_id]['subscription'])){
                    $subscription = $this->settings['repositories'][$repository_id]['subscription'];

                    if($subscription['data']->subscription_type == $subscription_id && !empty($subscription['data']->expires)){

                        if(strtotime($subscription['data']->expires) < strtotime(sprintf("+%d day", $days))){

                            $days_to_expiration = ceil((strtotime($subscription['data']->expires) - time()) / 86400);

                            echo '<div><p class="installer-warn-box">' .
                                sprintf(_n('Your subscription expires in %d day.', 'Your subscription expires in %d days.', $days_to_expiration, 'installer'), $days_to_expiration) .
                                '<br />' . $message .
                                '</p></div>';

                            $show = true;

                        }

                    }

                }


            }
        }


        return $show;

    }

    public function setup_plugins_renew_warnings(){

        $plugins = get_plugins();

        $subscriptions_with_warnings = array();
        foreach($this->settings['repositories'] as $repository_id => $repository){

            if($this->repository_has_valid_subscription($repository_id)){
                $subscription_type = $this->settings['repositories'][$repository_id]['subscription']['data']->subscription_type;
                $expires           = $this->settings['repositories'][$repository_id]['subscription']['data']->expires;

                $never_expires = isset($this->settings['repositories'][$repository_id]['subscription'])
                    && empty($this->settings['repositories'][$repository_id]['subscription']['data']->expires)
                    && (
                        $this->settings['repositories'][$repository_id]['subscription']['data']->status == 4 ||
                        $this->settings['repositories'][$repository_id]['subscription']['data']->status == 1
                    );

                if(!$never_expires){
                    if(isset($this->settings['repositories'][$repository_id]['data']['subscriptions_meta']['expiration'][$subscription_type])){

                        $days_warning = $this->settings['repositories'][$repository_id]['data']['subscriptions_meta']['expiration'][$subscription_type]['days_warning'];
                        $custom_message    = $this->settings['repositories'][$repository_id]['data']['subscriptions_meta']['expiration'][$subscription_type]['warning_message'];

                    }else{
                        //defaults
                        $days_warning = 30;
                        $custom_message    = __('You will have to renew your subscription in order to continue getting the updates and support.', 'installer');
                    }

                    if(strtotime($expires) < strtotime(sprintf('+%d day', $days_warning)) ){

                        $days_to_expiration = ceil((strtotime($expires) - time()) / 86400);

                        $message = sprintf(_n('Your subscription expires in %d day.', 'Your subscription expires in %d days.', $days_to_expiration, 'installer'), $days_to_expiration);
                        $subscriptions_with_warnings[$subscription_type] = $message . ' ' . $custom_message;

                    }
                }

            }

        }



        foreach($plugins as $plugin_id => $plugin){

            $slug = dirname($plugin_id);
            if(empty($slug)) continue;

            foreach($this->settings['repositories'] as $repository_id => $repository){

                if($this->repository_has_valid_subscription($repository_id)){

                    foreach($repository['data']['packages'] as $package){

                        foreach($package['products'] as $product){

                            foreach($product['plugins'] as $plugin_slug){

                                $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                                if($download['slug'] == $slug || $download['name'] == $plugin['Name'] || $download['name'] == $plugin['Title']){ //match order: slug, name, title

                                    if(isset($subscriptions_with_warnings[$product['subscription_type']])){

                                        $this->_plugins_renew_warnings[$plugin_id] = $subscriptions_with_warnings[$product['subscription_type']];

                                    }

                                }

                            }

                        }

                    }

                }

            }

        }

    }

    public function queue_plugins_renew_warnings() {

        if(!empty($this->_plugins_renew_warnings)){

            foreach($this->_plugins_renew_warnings as $plugin_id => $message){

                add_action( "after_plugin_row_" . $plugin_id, array($this, 'plugins_renew_warning'), 10, 3 );
            }

        }

    }

    public function plugins_renew_warning($plugin_file, $plugin_data, $status){

        if(empty($this->_plugins_renew_warnings[$plugin_file])) return;

        $wp_list_table = _get_list_table('WP_Plugins_List_Table');
        ?>

        <tr class="plugin-update-tr"><td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="plugin-update colspanchange">
                <div class="update-message">
                    <?php
                    echo $this->_plugins_renew_warnings[$plugin_file]. ' ';
                    printf(__('%sRenew here%s.', 'installer'),
                        '<a href="' . $this->menu_url() . '">', '</a>');
                    ?>
                </div>
        </tr>

        <?php

    }

    public function get_subscription_type_for_repository($repository_id){

        $subscription_type = false;

        if(!empty($this->settings['repositories'][$repository_id]['subscription'])){
            $subscription_type = $this->settings['repositories'][$repository_id]['subscription']['data']->subscription_type;
        }

        return $subscription_type;

    }

    public function have_superior_subscription($subscription_type, $product){

        $have = false;

        if(is_array($product['upgrades'])){
            foreach($product['upgrades'] as $u){
                if($u['subscription_type'] == $subscription_type){
                    $have = true;
                    break;
                }
            }
        }

        return $have;
    }

    public function is_product_available_for_download($product_name, $repository_id){

        $available = false;

        $subscription_type = $this->get_subscription_type_for_repository($repository_id);
        $expired = $this->repository_has_expired_subscription($repository_id);

        if($this->repository_has_subscription($repository_id) && !$expired){

            $this->set_hierarchy_and_order();

            foreach($this->settings['repositories'][$repository_id]['data']['packages'] as $package_id => $package){

                $has_top_package = false;

                foreach($package['products'] as $product){

                    if($subscription_type == $product['subscription_type']){
                        $has_top_package = true;
                        if($product['name'] == $product_name){
                            return $available = true;
                        }
                    }

                }

                if(!empty($package['sub-packages'])){
                    foreach($package['sub-packages'] as $sub_package){
                        foreach($sub_package['products'] as $product){
                            if($product['name'] == $product_name && ($subscription_type == $product['subscription_type'] || $has_top_package)){
                                return $available = true;
                            }
                        }
                    }
                }

            }
        }

        return $available;

    }

    public function get_upgrade_options($repository_id){
        $all_upgrades = array();


        //get all products: packages and subpackages
        $all_products = array();
        foreach($this->settings['repositories'][$repository_id]['data']['packages'] as $package){
            foreach($package['products'] as $product) {
                $all_products[] = $product;
            }
            if(!empty($package['sub-packages'])){
                foreach($package['sub-packages'] as $subpackage){
                    foreach($subpackage['products'] as $product) {
                        $all_products[] = $product;
                    }

                }

            }

        }

        foreach( $all_products as $product ) {
            if ($product['upgrades']) {
                foreach ($product['upgrades'] as $upgrade) {
                    if ($this->repository_has_valid_subscription($repository_id) || ($this->repository_has_subscription($repository_id) && $upgrade['including_expired'])) {
                        $all_upgrades[$upgrade['subscription_type']][$product['subscription_type']] = $upgrade;
                    }
                }
            }
        }

        return $all_upgrades;

    }

    public function append_site_key_to_download_url($url, $key, $repository_id){

        $url_params['site_key'] = $key;
        $url_params['site_url'] = $this->get_installer_site_url( $repository_id );


        // Add extra parameters for custom Installer packages
        if( !empty($this->package_source) ){
            $extra = $this->get_extra_url_parameters();
            if( !empty($extra['repository']) && $extra['repository'] == $repository_id ) {
                unset($extra['repository']);
                foreach($extra as $key => $val){
                    $url_params[$key] = $val;
                }
            }
        }

        $url = add_query_arg($url_params, $url);

        if($repository_id == 'wpml'){
            $url = add_query_arg(array('using_icl' => $this->_using_icl, 'wpml_version' => $this->_wpml_version), $url);
        }

        return $url;

    }

    public function plugin_is_installed($name, $slug, $version = null){

        $is = false;

        $plugins = get_plugins();

        foreach($plugins as $plugin_id => $plugin){

            $wp_plugin_slug = dirname($plugin_id);

            // Exception: embedded plugins
            if( $wp_plugin_slug == $slug || $plugin['Name'] == $name  || $plugin['Title'] == $name || ( $wp_plugin_slug == $slug . '-embedded' || $plugin['Name'] == $name . ' Embedded' ) ){
                if($version){
                    if(version_compare($plugin['Version'], $version, '>=')){
                        $is = $plugin['Version'];
                    }
                }else{
                    $is = $plugin['Version'];
                }

                break;
            }

        }

        //exception: Types name difference
        if(!$is && $name == 'Types'){
            return $this->plugin_is_installed('Types - Complete Solution for Custom Fields and Types', $slug, $version);
        }

        return $is;
    }

    public function plugin_is_embedded_version($name, $slug){
        $is = false;

        $plugins = get_plugins();

        //false if teh full version is also installed
        $is_full_installed = false;
        foreach($plugins as $plugin_id => $plugin){

            if(($plugin['Name'] == $name && !preg_match("#-embedded$#", $slug)) ){
                $is_full_installed = true;
                break;
            }

        }

        if($is_full_installed){
            return false;
        }

        foreach($plugins as $plugin_id => $plugin){

            // TBD
            $wp_plugin_slug = dirname($plugin_id);
            if( $wp_plugin_slug == $slug . '-embedded' &&  $plugin['Name'] == $name . ' Embedded'){
                $is = true;
                break;
            }

        }

        return $is;

    }

    //Alias for plugin_is_installed
    public function get_plugin_installed_version($name, $slug){

        return $this->plugin_is_installed($name, $slug);

    }

    public function get_plugin_repository_version($repository_id, $slug){
        $version = false;

        if(!empty($this->settings['repositories'][$repository_id]['data']['packages'])){
            foreach($this->settings['repositories'][$repository_id]['data']['packages'] as $package){
                foreach($package['products'] as $product) {

                    foreach($product['plugins'] as $plugin_slug){

                        $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                        if($download['slug'] == $slug){
                            $version  = $download['version'];
                            break (3);
                        }

                    }

                }
            }
        }

        return $version;
    }

    public function is_uploading_allowed(){

        //_deprecated_function ( __FUNCTION__, '1.7.3', 'Installer_Dependencies::' . __FUNCTION__ );
        return $this->dependencies->is_uploading_allowed();

    }

    public function download_plugin_ajax_handler(){

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once $this->plugin_path() . '/includes/installer-upgrader-skins.php';

        $data    = json_decode( base64_decode( sanitize_text_field ( $_POST['data'] ) ), true );

        $ret        = false;
        $plugin_id  = false;
        $message    = '';

        //validate subscription
        $site_key = $this->get_repository_site_key($data['repository_id']);
        $subscription_data = $this->fetch_subscription_data( $data['repository_id'], $site_key , self::SITE_KEY_VALIDATION_SOURCE_DOWNLOAD_REPORT);

        if($subscription_data && !is_wp_error($subscription_data) && $this->repository_has_valid_subscription($data['repository_id'])){

            if($data['nonce'] == wp_create_nonce('install_plugin_' . $data['url'])){

                $upgrader_skins = new Installer_Upgrader_Skins(); //use our custom (mute) Skin
                $upgrader = new Plugin_Upgrader($upgrader_skins);

                remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

                $plugins = get_plugins();

                //upgrade or install?
                foreach($plugins as $id => $plugin){
                    $wp_plugin_slug = dirname($id);
                    $is_embedded = $this->plugin_is_embedded_version(preg_replace('/ Embedded$/', '', $plugin['Name']), preg_replace('/-embedded$/', '', $wp_plugin_slug));

                    if($wp_plugin_slug == $data['slug'] || $is_embedded && preg_replace('/-embedded$/', '', $wp_plugin_slug) == $data['slug']){
                        $plugin_id = $id;
                        break;
                    }
                }

                if($plugin_id && empty($is_embedded)){ //upgrade
                    $response['upgrade'] = 1;

                    $plugin_is_active = is_plugin_active($plugin_id);

                    $ret = $upgrader->upgrade($plugin_id);

                    if(!$ret && !empty($upgrader->skin->installer_error)){
                        if(is_wp_error($upgrader->skin->installer_error)){
                            $message = $upgrader->skin->installer_error->get_error_message() .
                                ' (' . $upgrader->skin->installer_error->get_error_data() . ')';
                        }
                    }

                    if($plugin_is_active){
                        //prevent redirects
                        add_filter('wp_redirect', '__return_false');
                        activate_plugin($plugin_id);
                    }

                }else{ //install

                    if($is_embedded){
                        delete_plugins(array($plugin_id));
                    }

                    $response['install'] = 1;
                    $ret = $upgrader->install($data['url']);
                    if(!$ret && !empty($upgrader->skin->installer_error)){
                        if(is_wp_error($upgrader->skin->installer_error)){
                            $message = $upgrader->skin->installer_error->get_error_message() .
                                ' (' . $upgrader->skin->installer_error->get_error_data() . ')';
                        }
                    }
                }

                $plugins = get_plugins(); //read again

                if($ret && !empty($_POST['activate'])){
                    foreach($plugins as $id => $plugin){
                        $wp_plugin_slug = dirname($id);
                        if($wp_plugin_slug == $data['slug']){
                            $plugin_version = $plugin['Version'];
                            $plugin_id = $id;
                            break;
                        }
                    }

                }

            }

        } else { //subscription not valid

            $ret = false;
            $message = __('Your subscription appears to no longer be valid. Please try to register again using a valid site key.', 'installer');
        }

        $response['version']     = isset($plugin_version) ? $plugin_version : 0;
        $response['plugin_id']   = $plugin_id;
        $response['nonce']       = wp_create_nonce('activate_' . $plugin_id);
        $response['success']     = $ret;
        $response['message']     = $message;

        echo json_encode( $response );
        exit;

    }

    public function download_plugin($slug, $url){

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once $this->plugin_path() . '/includes/installer-upgrader-skins.php';

        $upgrader_skins = new Installer_Upgrader_Skins(); //use our custom (mute) Skin
        $upgrader = new Plugin_Upgrader($upgrader_skins);

        remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

        $plugins = get_plugins();

        $plugin_id = false;

        //upgrade or install?
        foreach($plugins as $id => $plugin){
            $wp_plugin_slug = dirname($id);
            if($wp_plugin_slug == $slug){
                $plugin_id = $id;
                break;
            }
        }

        if($plugin_id){ //upgrade

            $plugin_is_active = is_plugin_active($plugin_id);

            $ret = $upgrader->upgrade($plugin_id);

            if($plugin_is_active){
                activate_plugin($plugin_id);
            }

        }else{ //install
            $ret = $upgrader->install($url);
        }

        return $ret;

    }

    public function activate_plugin(){

        $error = '';

        $plugin_id = sanitize_text_field ( $_POST['plugin_id'] );
        if(isset($_POST['nonce']) &&  $plugin_id && $_POST['nonce'] == wp_create_nonce('activate_' . $plugin_id )){

            // Deactivate any embedded version
            $plugin_slug = dirname($plugin_id);
            $active_plugins = get_option('active_plugins');
            foreach($active_plugins as $plugin){
                $wp_plugin_slug = dirname($plugin);
                if($wp_plugin_slug == $plugin_slug . '-embedded'){
                    deactivate_plugins(array($plugin));
                    break;
                }
            }

            //prevent redirects
            add_filter('wp_redirect', '__return_false', 10000);

            $return = activate_plugin($plugin_id);

            if(is_wp_error($return)){
                $error = $return->get_error_message();
            }

        }else{
            $error = 'error';
        }

        $ret = array('error' => $error);

        echo json_encode($ret);
        exit;

    }

    public function custom_plugins_api_call($false, $action, $args){

        if($action == 'plugin_information'){

            $plugins = get_plugins();
            $plugin_names = array();
            foreach( $plugins as $plugin_id => $plugin ) {
                // plugins by WP slug which (plugin folder) which can be different
                // will use this to compare by title
                $plugin_names[ dirname( $plugin_id ) ] = array(
                    'name'  => $plugin['Name'],
                    'title' => $plugin['Title'],
                );
            }

            $slug = $args->slug;

            foreach($this->settings['repositories'] as $repository_id => $repository){

                if(!$this->repository_has_valid_subscription($repository_id)){
                    $site_key = false;
                }else{
                    $site_key = $repository['subscription']['key'];
                }

                foreach($repository['data']['packages'] as $package){

                    foreach($package['products'] as $product){

                        foreach($product['plugins'] as $plugin_slug){

                            $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                            if( $download['slug'] == $slug ||
                                isset( $plugin_names[$slug] ) && (
                                    $plugin_names[$slug]['name'] == $download['name']  ||
                                    $plugin_names[$slug]['title'] == $download['name']
                                )
                            ){

                                if( !empty( $download['free-on-wporg'] ) ){
                                    return false; // use data from wordpress.org
                                }

                                $res = new stdClass();
                                $res->external = true;

                                $res->name = $download['name'];
                                $res->slug = $slug;
                                $res->version = $download['version'];
                                $res->author = '';
                                $res->author_profile = '';
                                $res->last_updated = $download['date'];

                                if($site_key){
                                    $res->download_link = $this->append_site_key_to_download_url($download['url'], $site_key, $repository_id);
                                }

                                $res->homepage = $repository['data']['url'];
                                $res->sections = array('Description' => $download['description'], 'Changelog' => $download['changelog']);

                                return $res;

                            }

                        }

                    }

                }

            }

        }

        return $false;

    }

    public function plugins_upgrade_check($update_plugins){

        if(!empty($this->settings['repositories'])){

            $plugins = get_plugins();

            foreach($plugins as $plugin_id => $plugin){

                $slug = dirname($plugin_id);
                if(empty($slug)) continue;

                $version = $plugin['Version'];
                $name = $plugin['Name'];

                foreach($this->settings['repositories'] as $repository_id => $repository){


                    if(!$this->repository_has_valid_subscription($repository_id)){
                        $site_key = false;
                    }else{
                        $site_key = $repository['subscription']['key'];
                        //$subscription_type = $this->get_subscription_type_for_repository($repository_id);
                    }

                    foreach($repository['data']['packages'] as $package){

                        foreach($package['products'] as $product){

                            foreach($product['plugins'] as $plugin_slug){

                                $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                                if(!empty($download['free-on-wporg'])) {
                                    continue;
                                }

                                if(empty($update_plugins->response[$plugin_id]) && ($download['slug'] == $slug || $download['name'] == $name ) && version_compare($download['version'], $version, '>')){

                                    $response = new stdClass();
                                    $response->id = 0;
                                    $response->slug = $slug;
                                    $response->plugin = $plugin_id;
                                    $response->new_version = $download['version'];
                                    $response->upgrade_notice = '';
                                    $response->url = $download['url'];
                                    if($site_key){
                                        $response->package = $this->append_site_key_to_download_url($download['url'], $site_key, $repository_id);
                                    }
                                    $update_plugins->checked[$plugin_id]  = $version;
                                    $update_plugins->response[$plugin_id] = $response;

                                }

                            }

                        }

                    }

                }

            }

        }

        return $update_plugins;

    }

    public function setup_plugins_page_notices(){

        $plugins = get_plugins();

        foreach($plugins as $plugin_id => $plugin){

            $slug = dirname($plugin_id);
            if(empty($slug)) continue;

            $name = $plugin['Name'];

            foreach($this->settings['repositories'] as $repository_id => $repository){

                if(!$this->repository_has_valid_subscription($repository_id)){
                    $site_key = false;
                }else{
                    $site_key = $repository['subscription']['key'];
                }

                foreach($repository['data']['packages'] as $package){

                    foreach($package['products'] as $product){

                        foreach($product['plugins'] as $plugin_slug){

                            $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                            if(!empty($download['free-on-wporg'])) {
                                continue;
                            }

                            if( $download['slug'] == $slug || $download['name'] == $name ){

                                if( !$site_key || !$this->plugin_is_registered($repository_id, $download['slug']) ){
                                    add_action( "after_plugin_row_" . $plugin_id, array($this, 'show_purchase_notice_under_plugin'), 10, 3 );
                                }

                            }

                        }

                    }

                }

            }

        }

    }

    public function show_purchase_notice_under_plugin($plugin_file, $plugin_data, $status){

        $wp_list_table = _get_list_table('WP_Plugins_List_Table');
        $wp_version = preg_replace( '/-(.+)$/', '', $GLOBALS['wp_version'] );

        if( version_compare( $wp_version, '4.6', '>=' ) ){

            ?>
            <tr class="plugin-update-tr installer-plugin-update-tr">
                <td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="plugin-update colspanchange">
                    <div class="notice inline notice-warning notice-alt">
                        <p class="installer-q-icon">
                            <?php printf( __('You must have a valid subscription in order to get upgrades or support for this plugin. %sPurchase a subscription or enter an existing site key%s.', 'installer'),
                                '<a href="' . $this->menu_url() . '">', '</a>'); ?>
                        </p>
                    </div>
                </td>
            </tr>
            <?php

        } else {

            ?>
            <tr class="plugin-update-tr">
                <td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="plugin-update colspanchange">
                    <div class="update-message installer-q-icon">
                        <?php printf( __('You must have a valid subscription in order to get upgrades or support for this plugin. %sPurchase a subscription or enter an existing site key%s.', 'installer'),
                            '<a href="' . $this->menu_url() . '">', '</a>'); ?>
                    </div>
                </td>
            </tr>
            <?php

        }

    }

    public function localize_strings(){

        if(!empty($this->settings['repositories'])){
            foreach($this->settings['repositories'] as $repository_id => $repository){
                //set name as call2action when don't have any
                //products
                foreach($repository['data']['packages'] as $package_id => $package){
                    foreach($package['products'] as $product_id => $product){
                        if(empty($product['call2action'])){
                            $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['call2action'] = $product['name'];
                        }

                        foreach($product['upgrades'] as $idx => $upg){
                            if(empty($upg['call2action'])){
                                $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['upgrades'][$idx]['call2action'] = $upg['name'];
                            }
                        }

                        foreach($product['renewals'] as $idx => $rnw){
                            if(empty($rnw['call2action'])){
                                $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['renewals'][$idx]['call2action'] = $rnw['name'];
                            }

                        }

                    }
                }
            }
        }

        global $sitepress;
        if(is_null($sitepress)){
            return;
        }

        // default strings are always in English
        $user_admin_language = $sitepress->get_admin_language();

        if($user_admin_language != 'en'){
            foreach($this->settings['repositories'] as $repository_id => $repository){

                $localization = $repository['data']['localization'];

                //packages
                foreach($repository['data']['packages'] as $package_id => $package){

                    if( isset($localization['packages'][$package_id]['name'][$user_admin_language]) ){
                        $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['name'] = $localization['packages'][$package_id]['name'][$user_admin_language];
                    }
                    if( isset($localization['packages'][$package_id]['description'][$user_admin_language]) ){
                        $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['description'] = $localization['packages'][$package_id]['description'][$user_admin_language];
                    }

                }

                //products
                foreach($repository['data']['packages'] as $package_id => $package){
                    foreach($package['products'] as $product_id => $product){

                        if( isset($localization['products'][$product_id]['name'][$user_admin_language]) ){
                            $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['name']
                                = $localization['products'][$product_id]['name'][$user_admin_language];
                        }
                        if( isset($localization['products'][$product_id]['description'][$user_admin_language]) ){
                            $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['description']
                                = $localization['products'][$product_id]['description'][$user_admin_language];
                        }
                        if( isset($localization['products'][$product_id]['call2action'][$user_admin_language]) ){
                            $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['name']
                                = $localization['products'][$product_id]['call2action'][$user_admin_language];
                        }


                    }
                }

                //subscription info
                if(isset($repository['data']['subscriptions_meta']['expiration'])){
                    foreach($repository['data']['subscriptions_meta']['expiration'] as $subscription_id => $note){
                        if(isset($localization['subscriptions-notes'][$subscription_id]['expiration-warning'][$user_admin_language])){
                            $this->settings['repositories'][$repository_id]['data']['subscriptions_meta']['expiration'][$subscription_id]['warning_message']
                                = $localization['subscriptions-notes'][$subscription_id]['expiration-warning'][$user_admin_language];
                        }
                    }
                }

            }
        }

    }

    public function get_matching_cp($repository, $args = array()){
        $match = false;


        $cp_name = $cp_author = false;

        if(isset($this->config['src_name']) && isset($this->config['src_author'])){

            $cp_name    = $this->config['src_name'];
            $cp_author  = $this->config['src_author'];

        }elseif(isset($args['src_name']) && isset($args['src_author'])){

            $cp_name    = $args['src_name'];
            $cp_author  = $args['src_author'];

        }

        if(isset($repository['data']['marketing_cp'])){

            foreach($repository['data']['marketing_cp'] as $cp){

                if(!empty($cp['exp']) && time() > $cp['exp']){
                    continue;
                }

                //Use theme_name for plugins too
                if(!empty($cp['theme_name'])){
                    if($cp['author_name'] == $cp_author && $cp['theme_name'] == $cp_name){
                        $match = $cp;
                        continue;
                    }
                }else{
                    if($cp['author_name'] == $cp_author){
                        $match = $cp;
                        continue;
                    }
                }

            }

        }

        return $match;
    }

    public function set_filtered_prices($args = array()){

        foreach($this->settings['repositories'] as $repository_id => $repository){

            $match = $this->get_matching_cp($repository, $args);

            if(empty($match)) continue;

            foreach($repository['data']['packages'] as $package_id => $package){

                foreach($package['products'] as $product_id => $product){

                    if($match['dtp'] == '%'){
                        $fprice = round( $product['price'] * (1 - $match['amt']/100), 2 );
                        $fprice = $fprice != round($fprice) ? sprintf('%.2f', $fprice) : round($fprice, 0);
                    }elseif($match['dtp'] == '-'){
                        $fprice = $product['price'] - $match['amt'];
                    }else{
                        $fprice = $product['price'];
                    }

                    if($fprice){
                        $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['price_disc'] = $fprice;

                        $url_glue = false !== strpos($this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['url'], '?') ? '&' : '?';
                        $cpndata = base64_encode(json_encode(array('theme_author' => $match['author_name'], 'theme_name' => $match['theme_name'], 'vlc' => $match['vlc'])));
                        $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['url'] .= $url_glue . 'cpn=' . $cpndata;

                        foreach($product['upgrades'] as $upgrade_id => $upgrade){

                            $fprice = false;
                            if($match['dtp'] == '%'){
                                $fprice = round( $upgrade['price'] * (1 - $match['amt']/100), 2 );
                                $fprice = $fprice != round($fprice) ? sprintf('%.2f', $fprice) : round($fprice, 0);
                            }elseif($match['dtp'] == '-'){
                                $fprice = $upgrade['price'] - $match['amt'];
                            }
                            if($fprice){
                                $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['upgrades'][$upgrade_id]['price_disc'] = $fprice;
                                $this->settings['repositories'][$repository_id]['data']['packages'][$package_id]['products'][$product_id]['upgrades'][$upgrade_id]['url'] .= $url_glue . 'cpn=' . $cpndata;
                            }


                        }

                    }

                }

            }

        }

    }

    public function set_hierarchy_and_order(){

        //2 levels
        if(!empty($this->settings['repositories'])) {
            foreach ($this->settings['repositories'] as $repository_id => $repository) {

                if( empty( $repository['data']['packages'] ) ) continue;

                $all_packages = $repository['data']['packages'];
                $ordered_packages = array();

                //backward compatibility - 'order'
                foreach($all_packages as $k => $v){
                    if(!isset($v['order'])){
                        $all_packages[$k]['order'] = 0;
                    }
                }

                //select parents
                foreach ($all_packages as $package_id => $package) {
                    if(empty($package['parent'])){
                        $ordered_packages[$package_id] = $package;
                    }
                }

                //add sub-packages
                foreach($all_packages as $package_id => $package){
                    if(!empty($package['parent'])) {
                        if(isset($ordered_packages[$package['parent']])){
                            $ordered_packages[$package['parent']]['sub-packages'][$package_id] = $package;
                        }
                    }
                }

                // order parents
                usort($ordered_packages, array($this, '_order_packages_callback'));
                //order sub-packages
                foreach($ordered_packages as $package_id => $package){
                    if(!empty($package['sub-packages'])) {
                        usort($ordered_packages[$package_id]['sub-packages'], create_function('$a, $b', 'return $a[\'order\'] > $b[\'order\'];'));
                    }
                }

                $this->settings['repositories'][$repository_id]['data']['packages'] = $ordered_packages;


            }
        }


    }

    public function _order_packages_callback($a, $b){
        return $a['order'] > $b['order'];
    }

    public function get_support_tag_by_name( $name, $repository ){

        if( is_array($this->settings['repositories'][$repository]['data']['support_tags'] )){
            foreach( $this->settings['repositories'][$repository]['data']['support_tags'] as $support_tag){
                if( $support_tag['name'] == $name ){
                    return $support_tag['url'];
                }
            }
        }

        return false;
    }

    public function plugin_upgrade_custom_errors(){

        if ( isset($_REQUEST['action']) ) {

            $action = isset($_REQUEST['action']) ? sanitize_text_field ( $_REQUEST['action'] ) : '';

            //bulk mode
            if('update-selected' == $action) {

                global $plugins;

                if(isset($plugins) && is_array($plugins)) {

                    foreach ($plugins as $k => $plugin) {
                        $plugin_repository = false;

                        $wp_plugin_slug = dirname($plugin);

                        foreach ($this->settings['repositories'] as $repository_id => $repository) {

                            foreach ($repository['data']['packages'] as $package) {

                                foreach ($package['products'] as $product) {

                                    foreach ($product['plugins'] as $plugin_slug) {

                                        $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                                        if ($download['slug'] == $wp_plugin_slug) {
                                            $plugin_repository = $repository_id;
                                            $product_name = $repository['data']['product-name'];
                                            $plugin_name = $download['name'];
                                            $free_on_wporg = !empty($download['free-on-wporg']);
                                            break;
                                        }

                                    }

                                }

                            }

                        }

                        if ($plugin_repository) {

                            //validate subscription
                            static $sub_cache = array();

                            if(empty($sub_cache[$plugin_repository])){
                                $site_key = $this->get_repository_site_key($plugin_repository);
                                if ($site_key) {
                                    $subscription_data = $this->fetch_subscription_data( $plugin_repository, $site_key, self::SITE_KEY_VALIDATION_SOURCE_REVALIDATION );
                                }

                                $sub_cache[$plugin_repository]['site_key']             = $site_key;
                                $sub_cache[$plugin_repository]['subscription_data']    = isset($subscription_data) ? $subscription_data : false;
                            }else{

                                $site_key           = $sub_cache[$plugin_repository]['site_key'];
                                $subscription_data  = $sub_cache[$plugin_repository]['subscription_data'];

                            }

                            if(!$site_key && !empty($free_on_wporg)){ // allow the download from wp.org
                                continue;
                            }

                            if (empty($site_key) || empty($subscription_data)) {


                                $error_message = sprintf(__("%s cannot update because your site's registration is not valid. Please %sregister %s%s again for this site first.", 'installer'),
                                    '<strong>' . $plugin_name . '</strong>', '<a target="_top" href="' . $this->menu_url() . '&validate_repository=' . $plugin_repository .
                                    '#repository-' . $plugin_repository . '">', $product_name, '</a>');

                                echo '<div class="updated error"><p>' . $error_message . '</p></div>';

                                unset($plugins[$k]);


                            }

                        }

                    }

                }

            }


            if( 'upgrade-plugin' == $action || 'update-plugin' == $action ) {

                $plugin = isset($_REQUEST['plugin']) ? trim( sanitize_text_field ( $_REQUEST['plugin'] ) ) : '';

                $wp_plugin_slug = dirname($plugin);

                $plugin_repository = false;

                foreach($this->settings['repositories'] as $repository_id => $repository){

                    foreach($repository['data']['packages'] as $package){

                        foreach($package['products'] as $product){

                            foreach($product['plugins'] as $plugin_slug){
                                $download = $this->settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                                //match by folder, will change to match by name and folder
                                if($download['slug'] == $wp_plugin_slug) {
                                    $plugin_repository = $repository_id;
                                    $product_name = $repository['data']['product-name'];
                                    $plugin_name = $download['name'];
                                    $free_on_wporg = !empty($download['free-on-wporg']);
                                    break;
                                }

                            }

                        }

                    }

                }

                if($plugin_repository) {

                    //validate subscription
                    $site_key = $this->get_repository_site_key($plugin_repository);
                    if ($site_key) {
                        $subscription_data = $this->fetch_subscription_data( $plugin_repository, $site_key, self::SITE_KEY_VALIDATION_SOURCE_REVALIDATION );
                    }

                    if ( (empty($site_key) || empty($subscription_data)) && empty($free_on_wporg)) {

                        $error_message = sprintf(__("%s cannot update because your site's registration is not valid. Please %sregister %s%s again for this site first.", 'installer'),
                            '<strong>'.$plugin_name . '</strong>', '<a href="' . $this->menu_url() . '&validate_repository=' . $plugin_repository .
                            '#repository-' . $plugin_repository . '">', $product_name, '</a>');

                        if(defined('DOING_AJAX')){ //WP 4.2

                            $status = array(
                                'update'     => 'plugin',
                                'plugin'     => $plugin,
                                'slug'       => sanitize_key( $_POST['slug'] ),
                                'oldVersion' => '',
                                'newVersion' => '',
                            );

                            $status['errorCode'] = 'wp_installer_invalid_subscription';
                            $status['error'] = $error_message;

                            wp_send_json_error( $status );

                        } else { // WP 4.1.1
                            echo '<div class="updated error"><p>' . $error_message . '</p></div>';


                            echo '<div class="wrap">';
                            echo '<h2>' . __( 'Update Plugin', 'installer' ) . '</h2>';
                            echo '<a href="' . admin_url('plugins.php') . '">' . __( 'Return to the plugins page', 'installer' ) . '</a>';
                            echo '</div>';
                            require_once(ABSPATH . 'wp-admin/admin-footer.php');
                            exit;

                        }

                    }


                }

            }
        }

    }

}
