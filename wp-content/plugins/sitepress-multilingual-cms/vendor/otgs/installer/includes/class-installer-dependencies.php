<?php

class Installer_Dependencies{

    private $uploading_allowed = null;
    private $is_win_paths_exception = array();


    function __construct(){

        add_action( 'admin_init', array( $this, 'prevent_plugins_update_on_plugins_page' ), 100);



        global $pagenow;
        if($pagenow == 'update.php'){
            if(isset($_GET['action']) && $_GET['action'] == 'update-selected'){
                add_action('admin_head', array($this, 'prevent_plugins_update_on_updates_screen'));         //iframe/bulk
            }else{
                add_action('all_admin_notices', array($this, 'prevent_plugins_update_on_updates_screen'));  //regular/singular
            }
        }
        add_action('wp_ajax_update-plugin', array($this, 'prevent_plugins_update_on_updates_screen'), 0); // high priority, before WP

    }

    public function is_win_paths_exception($repository_id){

        if(!isset($this->is_win_paths_exception[$repository_id])) {

            $this->is_win_paths_exception[$repository_id] = false;

            if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {

                $windows_max_path_length    = 256;
                $longest_path['wpml']       = 109;
                $longest_path['toolset']    = 99;

                $margin                     = 15;

                $upgrade_path_length = strlen( WP_CONTENT_DIR . '/upgrade' );

                $installer_settings = WP_Installer()->settings;

                if ( is_array( $installer_settings['repositories'][$repository_id]['data']['downloads']['plugins'] ) ) {
                    $a_plugin = current( $installer_settings['repositories'][$repository_id]['data']['downloads']['plugins'] );
                    $url = WP_Installer()->append_site_key_to_download_url( $a_plugin['url'], 'xxxxxx', $repository_id );
                    $tmpfname = wp_tempnam( $url );

                    $tmpname_length = strlen( basename( $tmpfname ) ) - 4; // -.tmp

                    if ( $upgrade_path_length + $tmpname_length + $longest_path[$repository_id] + $margin > $windows_max_path_length ) {

                        $this->is_win_paths_exception[$repository_id] = true;

                    }

                }


            }

        }

        return $this->is_win_paths_exception[$repository_id];

    }

    public function is_uploading_allowed(){

        if(!isset($this->uploading_allowed)){
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once WP_Installer()->plugin_path() . '/includes/installer-upgrader-skins.php';

            $upgrader_skins = new Installer_Upgrader_Skins(); //use our custom (mute) Skin
            $upgrader = new Plugin_Upgrader($upgrader_skins);

            ob_start();
            $res = $upgrader->fs_connect( array(WP_CONTENT_DIR, WP_PLUGIN_DIR) );
            ob_end_clean();

            if ( ! $res || is_wp_error( $res ) ) {
                $this->uploading_allowed = false;
            }else{
                $this->uploading_allowed = true;
            }
        }

        return $this->uploading_allowed;

    }

    public function cant_download($repository_id){

        return !$this->is_uploading_allowed() || $this->is_win_paths_exception($repository_id);

    }

    public function win_paths_exception_message(){
        return __('Downloading is not possible. WordPress cannot create required folders because of the
                                        256 characters limitation of the current Windows environment.', 'installer');
    }

    public function prevent_plugins_update_on_plugins_page(){

        $plugins = get_site_transient( 'update_plugins' );
        if ( isset($plugins->response) && is_array($plugins->response) ) {
            $plugins_with_updates = array_keys( $plugins->response );
        }

        if( !empty($plugins_with_updates) ) {

            $plugins = get_plugins();

            $installer_settings = WP_Installer()->settings;
            foreach ($installer_settings['repositories'] as $repository_id => $repository) {

                if ($this->is_win_paths_exception($repository_id)) {

                    $repositories_plugins = array();
                    foreach ($repository['data']['packages'] as $package) {
                        foreach ($package['products'] as $product) {
                            foreach ($product['plugins'] as $plugin_slug) {
                                $download = $installer_settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];
                                if ( empty($download['free-on-wporg']) ) {
                                    $repositories_plugins[$download['slug']] = $download['name'];
                                }
                            }
                        }
                    }

                    foreach ($plugins as $plugin_id => $plugin) {

                        if( in_array( $plugin_id, $plugins_with_updates ) ) {

                            $wp_plugin_slug = dirname($plugin_id);
                            if (empty($wp_plugin_slug)) {
                                $wp_plugin_slug = basename($plugin_id, '.php');
                            }

                            foreach ($repositories_plugins as $slug => $name) {
                                if ($wp_plugin_slug == $slug || $name == $plugin['Name'] || $name == $plugin['Title']) { //match order: slug, name, title

                                    remove_action("after_plugin_row_$plugin_id", 'wp_plugin_update_row', 10, 2);
                                    add_action("after_plugin_row_$plugin_id", array($this, 'wp_plugin_update_row_win_exception'), 10, 2);

                                }
                            }

                        }

                    }

                }


            }

        }

    }

    public function wp_plugin_update_row_win_exception(){
        $wp_list_table = _get_list_table('WP_Plugins_List_Table');
        echo '<tr class="plugin-update-tr">';
        echo '<td  class="plugin-update colspanchange" colspan="' . esc_attr( $wp_list_table->get_column_count() ) .
            '"><div class="update-message">' . $this->win_paths_exception_message() . '</div></td>';
        echo '</tr>';
    }

    public function prevent_plugins_update_on_updates_screen(){

        if ( isset($_REQUEST['action']) ) {

            $action = isset($_REQUEST['action']) ? sanitize_text_field ( $_REQUEST['action'] ) : '';

            $installer_settings = WP_Installer()->settings;

            //bulk mode
            if('update-selected' == $action) {

                global $plugins;

                if(isset($plugins) && is_array($plugins)) {

                    foreach ($plugins as $k => $plugin) {

                        $wp_plugin_slug = dirname($plugin);

                        foreach ($installer_settings['repositories'] as $repository_id => $repository) {

                            if( $this->is_win_paths_exception($repository_id) ){

                                foreach ($repository['data']['packages'] as $package) {

                                    foreach ($package['products'] as $product) {

                                        foreach ($product['plugins'] as $plugin_slug) {

                                            $download = $installer_settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                                            if ($download['slug'] == $wp_plugin_slug && empty($download['free-on-wporg']) ) {

                                                echo '<div class="updated error"><p>' . $this->win_paths_exception_message() .
                                                        ' <strong>(' . $download['name'] . ')</strong>' . '</p></div>';
                                                unset($plugins[$k]);

                                                break(3);

                                            }

                                        }

                                    }

                                }


                            }

                        }

                    }

                }

            }


            if( 'upgrade-plugin' == $action || 'update-plugin' == $action ) {

                $plugin = isset($_REQUEST['plugin']) ? trim( sanitize_text_field ( $_REQUEST['plugin'] ) ) : '';

                $wp_plugin_slug = dirname($plugin);

                foreach($installer_settings['repositories'] as $repository_id => $repository){

                    if( $this->is_win_paths_exception( $repository_id ) ) {
                        foreach ($repository['data']['packages'] as $package) {

                            foreach($package['products'] as $product) {

                                foreach($product['plugins'] as $plugin_slug) {
                                    $download = $installer_settings['repositories'][$repository_id]['data']['downloads']['plugins'][$plugin_slug];

                                    //match by folder, will change to match by name and folder
                                    if ( $download['slug'] == $wp_plugin_slug && empty ($download['free-on-wporg'] ) ) {

                                        echo '<div class="updated error"><p>' . $this->win_paths_exception_message() . '</p></div>';

                                        echo '<div class="wrap">';
                                        echo '<h2>' . __('Update Plugin') . '</h2>';
                                        echo '<a href="' . admin_url('update-core.php') . '">' . __('Return to the updates page', 'installer') . '</a>';
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
        }

    }


}



