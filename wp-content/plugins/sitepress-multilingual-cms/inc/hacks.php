<?php
//using this file to handle particular situations that would involve more ellaborate solutions

add_action('init', 'icl_load_hacks');  

function icl_load_hacks(){    
    if(file_exists(ICL_PLUGIN_PATH . '/inc/hacks/misc-constants.php')){
        include ICL_PLUGIN_PATH . '/inc/hacks/misc-constants.php';           
    }    
    include ICL_PLUGIN_PATH . '/inc/hacks/language-canonical-redirects.php';            
    
    
    if(is_admin() && !defined('ICL_PRODUCTION_MODE')){
        add_action('admin_notices', 'icl_dev_mode_warning');    
        function icl_dev_mode_warning(){
            ?>
            <div class="error message">
                <p>This is a development version of WPML, provided for evaluation purposes only. The code you are using did not go through any testing or QA. Do not use it in production sites.</strong></p>
                <p>To obtain production versions of WPML, visit: <a href="https://wpml.org">wpml.org</a>.</p>
            </div>
            <?php
        }
    }
    
}


include ICL_PLUGIN_PATH . '/inc/hacks/missing-php-functions.php';