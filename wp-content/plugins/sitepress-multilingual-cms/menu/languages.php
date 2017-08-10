<?php
	/* var WPML_Language_Switcher $wpml_language_switcher */
	global $sitepress, $sitepress_settings, $wpdb, $wpml_language_switcher;

    if(!is_plugin_active(basename(dirname(dirname(__FILE__))) . "/sitepress.php")){
        ?>
        <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>
        <div class="updated fade">
            <p style="line-height:1.5"><?php _e('The WPML Multilingual CMS plugin is not currently enabled.', 'sitepress');?></p>
            <p style="line-height:1.5"><?php printf(__('Please go to the <a href="%s">Plugins</a> page and enable the WPML Multilingual CMS plugin before trying to configure the plugin.', 'sitepress'), 'plugins.php');?></p>
        </div>
        <?php
        return;
    }

	if ( isset( $_GET[ 'trop' ] ) ) {
		require_once dirname( __FILE__ ) . '/edit-languages.php';
		global $icl_edit_languages;
		$icl_edit_languages = new SitePress_EditLanguages();
		$icl_edit_languages->render();
		return;
	}

	$sitepress_settings                 = get_option( 'icl_sitepress_settings' );
	$setup_complete                     = $sitepress->get_setting( 'setup_complete' );
	$active_languages                   = $sitepress->get_active_languages();
	$hidden_languages                   = $sitepress->get_setting( 'hidden_languages' );
	$show_untranslated_blog_posts       = $sitepress->get_setting( 'show_untranslated_blog_posts' );
	$automatic_redirect                 = $sitepress->get_setting( 'automatic_redirect' );
	$setting_urls                       = $sitepress->get_setting( 'urls' );
	$existing_content_language_verified = $sitepress->get_setting( 'existing_content_language_verified' );
	$setup_wizard_step                  = $sitepress->get_setting( 'setup_wizard_step' );
	$language_negotiation_type          = $sitepress->get_setting( 'language_negotiation_type' );
	$seo                                = $sitepress->get_setting( 'seo' );
	$default_language                   = $sitepress->get_default_language();
	$all_languages                      = $sitepress->get_languages( $sitepress->get_admin_language() );
	$sample_lang                        = false;
	$default_language_details           = false;
	$wp_api                             = $sitepress->get_wp_api();
	$should_hide_admin_language         = $wp_api->version_compare_naked( get_bloginfo( 'version' ), '4.7', '>=' );

	if(!$existing_content_language_verified ){
        // try to determine the blog language
        $blog_current_lang = 0;
        if($blog_lang = get_option('WPLANG')){
            $exp = explode('_',$blog_lang);
            $blog_current_lang = $wpdb->get_var(
                                    $wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code= %s",
                                                   $exp[0]));
        }
        if(!$blog_current_lang && defined('WPLANG') && WPLANG != ''){
            $blog_current_lang = $wpdb->get_var($wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE default_locale=%s", WPLANG));
            if(!$blog_current_lang){
                $blog_lang = WPLANG;
                $exp = explode('_',$blog_lang);
                $blog_current_lang = $wpdb->get_var(
                    $wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code= %s",
                                   $exp[0]));
            }
        }
        if(!$blog_current_lang){
            $blog_current_lang = 'en';
        }
		$languages = $sitepress->get_languages( $blog_current_lang, false, true, false, 'display_name' );
    }else{
		$languages = $sitepress->get_languages( $sitepress->get_admin_language(), false, true, false, 'display_name' );
        foreach($active_languages as $lang){
            if($lang['code'] != $default_language ){
                $sample_lang = $lang;
                break;
            }
        }
        $default_language_details = $sitepress->get_language_details( $default_language );
        $inactive_content = $sitepress->get_inactive_content();
    }
global $language_switcher_defaults, $language_switcher_defaults_alt;

$theme_wpml_config_file = WPML_Config::get_theme_wpml_config_file();


?>
<?php $sitepress->noscript_notice() ?>

<?php if($setup_complete || SitePress_Setup::languages_table_is_complete()) { ?>
<div class="wrap <?php if( empty( $setup_complete ) ): ?>wpml-wizard<?php endif; ?>">
    <h2><?php _e('Setup WPML', 'sitepress') ?></h2>

    <?php

	if( empty( $setup_complete ) ){ /* setup wizard */

            if(!$existing_content_language_verified ){
                $sw_width = 25;
            }elseif(count($sitepress->get_active_languages()) < 2 || $setup_wizard_step == 2){
                $sw_width = 50;
            }elseif($setup_wizard_step == 3){
                $sw_width = 75;
            }else{
                $sw_width = 90;
            }

			include ICL_PLUGIN_PATH . '/menu/setup/setup_001.php';

	} /* setup wizard */

    if ( ! $existing_content_language_verified || $setup_wizard_step <= 1 ): ?>
	    <?php
	    $setup_step = new WPML_Setup_Step_One_Menu( $sitepress );
	    echo $setup_step->render();
	    ?>
    <?php else: ?>
        <?php
		if(!empty( $setup_complete ) || $setup_wizard_step == 2): ?>
            <?php if(!empty( $setup_complete ) && (count($active_languages) > 1)): ?>
                <p>
                    <strong><?php _e('This screen contains the language settings for your site.','sitepress'); ?></strong>
                </p>
                <ul class="wpml-navigation-links js-wpml-navigation-links">
	                <?php
	                $navigation_items = array(
		                '#lang-sec-1'   =>  __('Site Languages','sitepress'),
		                '#lang-sec-2'   =>  __('Language URL format','sitepress'),
		                '#lang-sec-4'   =>  __('Admin language','sitepress'),
		                '#lang-sec-6'   =>  __('Blog posts to display','sitepress'),
		                '#lang-sec-7'   =>  __('Hide languages','sitepress'),
		                '#lang-sec-8'   =>  __('Make themes work multilingual','sitepress'),
		                '#lang-sec-9'   =>  __('Browser language redirect','sitepress'),
		                '#lang-sec-9-5' =>  __('SEO Options','sitepress'),
		                '#lang-sec-10'  =>  __('WPML love','sitepress'),
	                );

	                if( $should_hide_admin_language && array_key_exists( '#lang-sec-4', $navigation_items ) ) {
	                	unset( $navigation_items['#lang-sec-4'] );
	                }

	                /**
	                 * @param array $navigation_items
	                 */
	                $navigation_items = apply_filters( 'wpml_admin_languages_navigation_items', $navigation_items );

	                foreach ( $navigation_items  as $link => $text ) {
		                echo '<li><a href="' . $link . '">' . $text . '</a></li>';
	                }
	                ?>
                </ul>
            <?php endif; ?>

            <div id="lang-sec-1" class="wpml-section wpml-section-languages">
                <div class="wpml-section-header">
                    <h3><?php _e('Site Languages', 'sitepress') ?></h3>
                </div>

                <div class="wpml-section-content">
                    <div class="wpml-section-content-inner">
                        <?php if(!empty( $setup_complete )): ?>
                            <h4><?php _e('These languages are enabled for this site:','sitepress'); ?></h4>
                            <ul id="icl_enabled_languages" class="enabled-languages">
                                    <?php foreach($active_languages as $lang): $is_default = ( $default_language ==$lang['code']); ?>
                                    <?php
                                    if(!empty( $hidden_languages ) && in_array($lang['code'], $hidden_languages )){
                                        $hidden = '&nbsp<strong style="color:#f00">('.__('hidden', 'sitepress').')</strong>';
                                    }else{
                                        $hidden = '';
                                    }
                                    ?>

                                <li <?php if($is_default):?>class="selected"<?php endif;?>>
                                    <input id="default_language_<?php echo $lang['code'] ?>" name="default_language" type="radio" value="<?php echo $lang['code'] ?>" <?php if($is_default):?>checked="checked"<?php endif;?> />
                                    <label for="default_language_<?php echo $lang['code'] ?>"><?php echo $lang['display_name'] . $hidden ?> <?php if($is_default):?>(<?php echo __('default', 'sitepress') ?>)<?php endif?></label>
                                </li>
                                <?php endforeach ?>
                            </ul>
                        <?php else: ?>
                            <p class="wpml-wizard-instruction"><?php _e('Select the languages to enable for your site (you can also add and remove languages later).','sitepress'); ?></p>
                        <?php endif; ?>
                        <?php wp_nonce_field('wpml_set_default_language', 'set_default_language_nonce'); ?>
                        <p class="buttons-wrap">
                            <button id="icl_cancel_default_button" class="button-secondary action"><?php _e('Cancel', 'sitepress') ?></button>
                            <button id="icl_save_default_button" class="button-primary action"><?php _e('Save', 'sitepress') ?></button>
                        </p>
                        <?php if(!empty( $setup_complete )): ?>
                            <p>
                                <button id="icl_change_default_button" class="button-secondary action <?php if(count($active_languages) < 2): ?>hidden<?php endif ?>"><?php _e('Change default language', 'sitepress') ?></button>
                                <button id="icl_add_remove_button" class="button-secondary action"><?php _e('Add / Remove languages', 'sitepress') ?></button>
                            </p>
                            <p class="icl_ajx_response" id="icl_ajx_response"></p>
                        <?php endif; ?>
                        <div id="icl_avail_languages_picker" class="<?php if( !empty( $setup_complete ) ) echo 'hidden'; ?>">
                            <ul class="available-languages">
                                <?php
                                foreach ( $languages as $lang ) {
                                    $checked  = checked( '1', $lang['active'], false );
                                    $disabled = disabled( $default_language, $lang['code'], false );

	                                $language_item_classes = array();
	                                if ( (bool) $lang['active'] ) {
		                                $language_item_classes[] = 'wpml-selected';
	                                }
                                    ?>
	                                <li class="<?php echo implode( ' ', $language_item_classes ); ?>">
		                                <label for="wpml-language-<?php echo $lang['code'] ?>">
			                                <input type="checkbox" id="wpml-language-<?php echo $lang['code'] ?>" value="<?php echo $lang['code'] ?>" <?php echo $checked . ' ' . $disabled; ?>/>
			                                <img src="<?php echo $sitepress->get_flag_url( $lang['code'] ) ?>" width=18" height="12">
			                                <?php echo $lang['display_name'] ?>
		                                </label>
	                                </li>
                                    <?php
                                }
                                ?>
                            </ul>
                            <?php if(!empty( $setup_complete )): ?>
                            <p class="buttons-wrap">
                                <input id="icl_cancel_language_selection" type="button" class="button-secondary action" value="<?php _e('Cancel', 'sitepress') ?>" />
                                <input id="icl_save_language_selection" type="button" class="button-primary action" value="<?php _e('Save', 'sitepress') ?>" />
                            </p>
                            <?php endif; ?>
                            <?php wp_nonce_field('wpml_set_active_languages', 'set_active_languages_nonce'); ?>
                        </div>

                        <?php if (!empty( $setup_complete )): ?>
                            <p>
                                <a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php&amp;trop=1"><?php _e('Edit Languages','sitepress'); ?></a>
                            </p>
                        <?php endif; ?>
                    </div> <!-- wpml-section-content-inner -->


                    <?php if( !empty($inactive_content) ): ?>
                        <div class="wpml-section-content-inner">
                            <?php
                            $inactive_content_data   = array();
                            $inactive_content_totals = array(
	                            'post'     => 0,
	                            'page'     => 0,
	                            'category' => 0,
	                            'post_tag' => 0,
                            );
                            $t_posts                 = $t_pages = $t_cats = $t_tags = 0;
                            foreach ( $inactive_content as $language => $ic ) {
	                            $inactive_content_data[ $language ] = array(
		                            'post'     => 0,
		                            'page'     => 0,
		                            'category' => 0,
		                            'post_tag' => 0,
	                            );

	                            if ( array_key_exists( 'post', $ic ) ) {
		                            $inactive_content_data[ $language ]['post'] += (int) $ic['post'];
		                            $inactive_content_totals['post'] += (int) $ic['post'];
	                            }
	                            if ( array_key_exists( 'page', $ic ) ) {
		                            $inactive_content_data[ $language ]['page'] += (int) $ic['page'];
		                            $inactive_content_totals['page'] += (int) $ic['page'];
	                            }
	                            if ( array_key_exists( 'category', $ic ) ) {
		                            $inactive_content_data[ $language ]['category'] += (int) $ic['category'];
		                            $inactive_content_totals['category'] += (int) $ic['category'];
	                            }
	                            if ( array_key_exists( 'post_tag', $ic ) ) {
		                            $inactive_content_data[ $language ]['post_tag'] += (int) $ic['post_tag'];
		                            $inactive_content_totals['post_tag'] += (int) $ic['post_tag'];
	                            }
                            }
                            ?>
                            <h4><?php _e('Inactive content', 'sitepress') ?></h4>
                            <p class="explanation-text"><?php _e('In order to edit or delete these you need to activate the corresponding language first', 'sitepress') ?></p>
                            <table class="widefat inactive-content-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Language', 'sitepress') ?></th>
                                        <th><?php _e('Posts', 'sitepress') ?></th>
                                        <th><?php _e('Pages', 'sitepress') ?></th>
                                        <th><?php _e('Categories', 'sitepress') ?></th>
                                        <th><?php _e('Tags', 'sitepress') ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th><?php _e('Total', 'sitepress') ?></th>
	                                    <?php
	                                    foreach ( $inactive_content_totals as $count ) {
		                                    ?>
		                                    <th><?php echo $count ?></th>
		                                    <?php
	                                    }
	                                    ?>
                                    </tr>
                                </tfoot>
                                <tbody>
                                <?php foreach ( $inactive_content_data as $language => $inactive_content_counts ): ?>
                                        <tr>
	                                        <th><?php echo $language ?></th>
	                                        <?php
	                                        foreach ( $inactive_content_counts as $count ) {
		                                        ?>
		                                        <th><?php echo $count ?></th>
		                                        <?php
	                                        }
	                                        ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div> <!-- wpml-section-content-inner -->
                    <?php endif; ?>

	                <?php if ( 2 === (int) $setup_wizard_step ): ?>
                        <footer class="clearfix text-right">
                            <input id="icl_setup_back_1" class="button-secondary alignleft" name="save" value="<?php echo __('Back', 'sitepress') ?>" type="button" />
                            <?php wp_nonce_field('setup_got_to_step1_nonce', '_icl_nonce_gts1'); ?>
                            <input id="icl_setup_next_1" class="button-primary alignright" name="save" value="<?php echo __('Next', 'sitepress') ?>" type="button" <?php if(count($active_languages) < 2):?>disabled="disabled"<?php endif;?> />
                        </footer>
                    <?php endif; ?>

                </div> <!-- .wcml-section-content -->
            </div> <!-- .wpml-section-languages -->

        <?php
        elseif($setup_wizard_step == 4): ?>
        <?php $site_key = WP_Installer()->get_site_key('wpml'); ?>
        <div class="wpml-section" id="lang-sec-0">
            <div class="wpml-section-header">
                <h3><?php _e('Registration', 'sitepress'); ?></h3>
            </div>
            <div class="wpml-section-content">

                <?php if(is_multisite() && !empty($site_key)): ?>

                <p class="wpml-wizard-instruction"><?php _e('WPML is already registered network-wide.', 'sitepress') ?></p>
                <footer class="clearfix text-right">
                    <form id="installer_registration_form">
                        <input type="hidden" name="action" value="installer_save_key" />
                        <input type="hidden" name="button_action" value="installer_save_key" />
                        <input <?php if(empty($site_key)): ?>style="display: none;"<?php endif; ?> class="button-primary button-large" name="finish" value="<?php echo __('Finish', 'sitepress') ?>" type="submit" />
                        <?php wp_nonce_field('registration_form_submit_nonce', '_icl_nonce'); ?>
                    </form>
                </footer>

                <?php else: ?>

                <p class="wpml-wizard-instruction"><?php _e('Enter the site key, from your wpml.org account, to receive automatic updates for WPML on this site.', 'sitepress'); ?></p>
                <form id="installer_registration_form">
                    <input type="hidden" name="action" value="installer_save_key" />
                    <input type="hidden" name="button_action" value="installer_save_key" />
                    <label for="installer_site_key">
                        <?php _e('Site key:', 'sitepress'); ?>
                        <input type="text" name="installer_site_key" value="<?php echo $site_key ?>" <?php if(!empty($site_key)): ?>disabled="disabled"<?php endif; ?> />
                    </label>


                    <div class="status_msg<?php if(!empty($site_key)): ?> icl_valid_text<?php endif; ?>">
                        <?php if($site_key) _e('Thank you for registering WPML on this site. You will receive automatic updates when new versions are available.', 'sitepress'); ?>
                    </div>

                    <div class="text-right">
                        <?php if(empty($site_key)): ?>
                        <input class="button-primary" name="register" value="<?php echo __('Register', 'sitepress') ?>" type="submit" />
                        <input class="button-secondary" name="later" value="<?php echo __('Remind me later', 'sitepress') ?>" type="submit" />
                        <?php endif; ?>
                        <input <?php if(empty($site_key)): ?>style="display: none;"<?php endif; ?> class="button-primary button-large button" name="finish" value="<?php echo __('Finish', 'sitepress') ?>" type="submit" />

                        <?php wp_nonce_field('registration_form_submit_nonce', '_icl_nonce'); ?>
                    </div>

                </form>

                <?php endif; ?>

                <?php if(empty($site_key)): ?>
                    <hr class="wpml-margin-top-base">
                <p><?php printf(__("Don't have a key for this site? %sGenerate a key for this site%s", 'sitepress'), '<a class="button-primary" href="https://wpml.org/my-account/sites/?add='.urlencode(get_site_url()).'" target="_blank">', '</a>') ?></p>
                <p><?php printf(__("If you don't have a WPML.org account or a valid subscription, you can %spurchase%s one and get later upgrades, full support and 30 days money-back guarantee." , 'sitepress'), '<a href="http://wpml.org/purchase/" target="_blank">', '</a>') ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>


        <?php if(!empty( $setup_complete )): ?>
            <?php
            if ( !class_exists ( 'WP_Http' ) ) {
                include_once ABSPATH . WPINC . '/class-http.php';
            }
            /**
             * @var WPML_URL_Converter $wpml_url_converter
             * @var WPML_Request $wpml_request_handler
             */
            global $wpml_url_converter, $wpml_request_handler;

            $validator = wpml_get_langs_in_dirs_val ( new WP_Http(), $wpml_url_converter );
            ?>
            <?php if(count($active_languages) > 1): ?>
                <div class="wpml-section wpml-section-url-format" id="lang-sec-2">
                    <div class="wpml-section-header">
                        <h3><?php _e('Language URL format', 'sitepress'); ?></h3>
                    </div>
                    <div class="wpml-section-content">
                        <h4><?php _e('Choose how to determine which language visitors see contents in', 'sitepress'); ?></h4>
                        <form id="icl_save_language_negotiation_type" name="icl_save_language_negotiation_type" action="">
                            <?php wp_nonce_field('save_language_negotiation_type', 'save_language_negotiation_type_nonce') ?>
                            <ul>
                                <?php

                                $abs_home = $wpml_url_converter->get_abs_home();
                                $icl_folder_url_disabled = $validator->validate_langs_in_dirs ( $sample_lang['code'] );
                                ?>
                                <li>
                                    <label>
                                        <input type="radio" name="icl_language_negotiation_type" value="1" <?php if($language_negotiation_type ==1):?>checked<?php endif?> />
                                        <?php _e('Different languages in directories', 'sitepress'); ?>
                                        <span class="explanation-text">
                                        (<?php
	                                        $root = !empty( $setting_urls['directory_for_default_language']);
                                            echo $validator->print_explanation( $sample_lang['code'], $root );?>)
                                        </span>
                                    </label>

                                    <div id="icl_use_directory_wrap" style="<?php if( $language_negotiation_type != 1): ?>display:none;<?php endif; ?>" >
                                        <p class="sub-section">
                                            <label>
                                                <input type="checkbox" name="use_directory" id="icl_use_directory" value="1" <?php if(!empty( $setting_urls['directory_for_default_language'])):?>checked<?php endif; ?> />
                                                <?php _e('Use directory for default language', 'sitepress') ?>
                                            </label>
                                        </p>

                                        <div id="icl_use_directory_details" class="sub-section" <?php if(empty( $setting_urls['directory_for_default_language'])) echo ' style="display:none"'  ?> >

                                            <p><?php _e('What to show for the root url:', 'sitepress') ?></p>

                                            <ul>
                                                <li>
                                                    <label for="wpml_show_on_root_html_file">
                                                        <input id="wpml_show_on_root_html_file" type="radio" name="show_on_root" value="html_file" <?php if($setting_urls['show_on_root'] == 'html_file'):
                                                        ?>checked="checked"<?php endif; ?> />
                                                        <?php _e('HTML file', 'sitepress') ?> &ndash; <span class="explanation-text"><?php _e('please enter path: absolute or relative to the WordPress installation folder','sitepress'); ?></span>
                                                    </label>
                                                    <p>
                                                        <input type="text" id="root_html_file_path" name="root_html_file_path" value="<?php echo $setting_urls['root_html_file_path']?>" />
                                                        <label class="icl_error_text icl_error_1" for="root_html_file_path" style="display: none;"><?php _e('Please select what to show for the root url.', 'sitepress') ?></label>
                                                    </p>
                                                </li>

                                                <li>
                                                    <label>
                                                        <input id="wpml_show_on_root_page" type="radio" name="show_on_root" value="page" <?php if($setting_urls['show_on_root'] == 'page'): ?>checked<?php endif; ?>  <?php if($setting_urls['show_on_root'] == 'page'):?>class="active"<?php endif; ?> />
                                                        <?php _e('A page', 'sitepress') ?>

                                                        <span style="display: none;" id="wpml_show_page_on_root_x"><?php echo esc_js(__("Please save the settings first by clicking Save.", 'sitepress')); ?></span>

                                                        <span id="wpml_show_page_on_root_details" <?php if($setting_urls['show_on_root'] != 'page'):
                                                        ?>style="display:none"<?php endif; ?>>
                                                        <?php
                                                        $rp_exists = false;
                                                        if(!empty( $setting_urls['root_page'])){
                                                            $rp = get_post( $setting_urls['root_page']);
                                                            if($rp && $rp->post_status != 'trash'){
                                                                $rp_exists = true;
                                                            }
                                                        }
                                                        ?>
                                                        <?php if($rp_exists): ?>
                                                            <a href="<?php echo get_edit_post_link( $setting_urls['root_page']) ?>"><?php _e('Edit root page.', 'sitepress') ?></a>
                                                        <?php else: ?>
                                                            <a href="<?php echo admin_url('post-new.php?post_type=page&wpml_root_page=1') ?>"><?php _e('Create root page.', 'sitepress') ?></a>
                                                        <?php endif; ?>
                                                        </span>
														<p id="icl_hide_language_switchers" class="sub-section" <?php if($setting_urls['show_on_root'] != 'page'): ?>style="display:none"<?php endif; ?>>
														  <label>
															  <input type="checkbox" name="hide_language_switchers" id="icl_hide_language_switchers" value="1" <?php checked( $setting_urls['hide_language_switchers']) ?> />
															  <?php _e('Hide language switchers on the root page', 'sitepress') ?>
														  </label>
													  </p>

                                                    </label>
                                                </li>


                                            </ul>

                                        </div>
                                    </div>

	                                <?php if ( $icl_folder_url_disabled ): ?>
                                    <div class="icl_error_text" style="margin:10px;">
                                        <p>
                                            <?php _e('It looks like languages per directories will not function.', 'sitepress'); ?>
                                            <a href="#" onClick="jQuery(this).parent().parent().next().toggle();return false"><?php _e("Details", 'sitepress') ?></a>
                                        </p>
                                    </div>
                                    <div class="icl_error_text" style="display:none;margin:10px;">
                    					<p><?php _e("This can be a result of either:", 'sitepress') ?></p>
                    					<ul>
                        					<li><?php _e("WordPress is installed in a directory (not root) and you're using default links.",'sitepress') ?></li>
                        					<li><?php _e("URL rewriting is not enabled in your web server.",'sitepress') ?></li>
                                            <li><?php _e("The web server cannot write to the .htaccess file",'sitepress') ?></li>
                    					</ul>
                                        <a href="https://wpml.org/?page_id=1010"><?php _e('How to fix','sitepress') ?></a>
                                            <p>
                                                <?php printf(__('When WPML accesses <a target="_blank" href="%s">%s</a> it gets:', 'sitepress'), $__url = $validator->get_validation_url($sample_lang['code']), $__url); ?>
	                                            <br/>
	                                            <?php
	                                            echo $validator->print_error_response ();
	                                            ?>
                                            </p>
                                            <p>
                                                <?php printf(__('The expected value is: %s', 'sitepress'), '<br /><strong>&lt;!--'.get_home_url().'--&gt;</strong>'); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </li>
                                <?php
                                global $wpmu_version;
                                if ( isset( $wpmu_version ) || ( function_exists( 'is_multisite' ) && is_multisite() && ( ! defined( 'WPML_SUNRISE_MULTISITE_DOMAINS' ) || ! WPML_SUNRISE_MULTISITE_DOMAINS ) ) ) {
                                    $icl_lnt_disabled = 'disabled="disabled" ';
                                }else{
                                    $icl_lnt_disabled = '';
                                }
                                ?>
                                <li>
                                    <label>
                                        <input <?php echo $icl_lnt_disabled ?>id="icl_lnt_domains" type="radio" name="icl_language_negotiation_type" value="2" <?php if($language_negotiation_type ==2):?>checked<?php endif?> />
                                        <?php _e('A different domain per language', 'sitepress') ?>
                                        <?php if($icl_lnt_disabled): ?>
                                            <span class="icl_error_text"><?php _e('This option is not yet available for Multisite installs', 'sitepress')?></span>
                                        <?php endif; ?>
                                        <?php if(defined('WPML_SUNRISE_MULTISITE_DOMAINS') && WPML_SUNRISE_MULTISITE_DOMAINS): ?>
                                            <span class="icl_error_text"><?php _e('Experimental', 'sitepress')?></span>
                                        <?php endif; ?>
                                    </label>
                                    <?php wp_nonce_field('language_domains_nonce', '_icl_nonce_ldom', false); ?>
                                    <?php wp_nonce_field('validate_language_domain', 'validate_language_domain_nonce', false); ?>
                                    <div id="icl_lnt_domains_box">
		                                <?php if ( (int) $language_negotiation_type === 2 ): ?>
			                                <?php
			                                $domains_box = new WPML_Lang_Domains_Box( $sitepress );
			                                echo $domains_box->render();
			                                ?>
                                                <?php endif; ?>
                                    </div>
                                    <div id="language_domain_xdomain_options" class="sub-section" <?php if( $language_negotiation_type != 2 ) echo ' style="display:none"'  ?>>
                                        <p><?php _e('Pass session arguments between domains through the language switcher', 'sitepress'); ?></p>
                                        <p>
                                            <label>
                                                <input type="radio" name="icl_xdomain_data"
                                                       value="<?php echo WPML_XDOMAIN_DATA_GET ?>"
                                                       <?php if ($sitepress_settings['xdomain_data'] == WPML_XDOMAIN_DATA_GET): ?>checked="checked"<?php endif ?>/>
                                                <?php echo __('Pass arguments via GET (the url)', 'sitepress'); ?>
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                <input type="radio" name="icl_xdomain_data"
                                                       value="<?php echo WPML_XDOMAIN_DATA_POST ?>"
                                                       <?php if ($sitepress_settings['xdomain_data'] == WPML_XDOMAIN_DATA_POST): ?>checked="checked"<?php endif ?>/>
                                                <?php echo __('Pass arguments via POST', 'sitepress'); ?>
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                <input type="radio" name="icl_xdomain_data"
                                                       value="<?php echo WPML_XDOMAIN_DATA_OFF ?>"
                                                       <?php if ($sitepress_settings['xdomain_data'] == WPML_XDOMAIN_DATA_OFF): ?>checked="checked"<?php endif ?>/>
                                                <?php echo __('Disable this feature', 'sitepress'); ?>
                                            </label>
                                        </p>

                                        <?php if( function_exists( 'mcrypt_encrypt' ) && function_exists( 'mcrypt_decrypt' ) ): ?>
                                            <p><?php _e('The data will be encrypted with the MCRYPT_RIJNDAEL_256 algorithm.' , 'sitepress'); ?></p>
                                        <?php else: ?>
                                            <p><?php _e('Because encryption is not supported on your host, the data will only have a basic encoding with the bse64 algorithm.' , 'sitepress'); ?></p>
                                        <?php endif; ?>

                                        <p><a href="https://wpml.org/?page_id=693147" target="_blank"><?php _e('Learn more about passing data between domains', 'sitepress'); ?></a></p>


                                    </div>

                                </li>
                                <li>
                                    <label>
                                        <input type="radio" name="icl_language_negotiation_type" value="3" <?php if($language_negotiation_type ==3):?>checked<?php endif?> />
                                        <?php _e('Language name added as a parameter', 'sitepress') ?>
                                        <span class="explanation-text"><?php echo sprintf('(%s?lang=%s - %s)',get_home_url(),$sample_lang['code'],$sample_lang['display_name']) ?></span>
                                    </label>
                                </li>
                            </ul>
	                        <div class="wpml-form-message" style="display:none;"></div>
                            <p class="buttons-wrap">
                                <span class="icl_ajx_response" id="icl_ajx_response2"></span>
                                <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                            </p>
                        </form>
                    </div>
                </div> <!-- .wpml-section-url-format -->
            <?php endif; ?>
        <?php endif; ?>

	    <?php do_action( 'wpml_admin_after_languages_url_format' ); ?>

        <?php if(!empty( $setup_complete ) && count($all_languages) > 1 && ! $should_hide_admin_language): ?>
            <div class="wpml-section wpml-section-admin-language" id="lang-sec-4">
                <div class="wpml-section-header">
                    <h3><?php _e('Admin language', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
                    <form id="icl_admin_language_options" name="icl_admin_language_options" action="">
                        <?php wp_nonce_field('icl_admin_language_options_nonce', '_icl_nonce'); ?>
                        <?php if(is_admin()): ?>
                        <p>
                            <label>
                                <?php _e('Default admin language: ', 'sitepress'); ?>
                                <?php $default_language_details = $sitepress->get_language_details( $default_language ); ?>
                                <select name="icl_admin_default_language">
									<option value="_default_"><?php printf(__('Default language (currently %s)', 'sitepress'),  $default_language_details['display_name']); ?></option>
									<?php foreach($all_languages as $al):?>
										<?php if($al['active']): ?>
											<option value="<?php echo $al['code'] ?>"<?php if($sitepress->get_setting('admin_default_language')==$al['code']) echo ' selected="selected"'?>><?php echo $al['display_name']; if($sitepress->get_admin_language() != $al['code']) echo ' ('. $al['native_name'] .')' ?>&nbsp;</option>
										<?php endif; ?>
									<?php endforeach; ?>
									<?php foreach($all_languages as $al):?>
										<?php if(!$al['active']): ?>
											<option value="<?php echo $al['code'] ?>"<?php if($sitepress->get_setting('admin_default_language')==$al['code']) echo ' selected="selected"'?>><?php echo $al['display_name']; if($sitepress->get_admin_language() != $al['code']) echo ' ('. $al['native_name'] .')' ?>&nbsp;</option>
										<?php endif; ?>
									<?php endforeach; ?>
                                </select>
                            </label>
                        </p>
                        <?php endif; ?>
                        <p><?php printf(__('Each user can choose the admin language. You can edit your language preferences by visiting your <a href="%s">profile page</a>.','sitepress'),'profile.php#wpml')?></p>
                        <p class="buttons-wrap">
                            <span class="icl_ajx_response" id="icl_ajx_response_al"></span>
                            <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                        </p>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if(!empty( $setup_complete ) && count($active_languages) > 1): ?>
            <div class="wpml-section wpml-section-blog-posts" id="lang-sec-6">
                <div class="wpml-section-header">
                    <h3><?php _e('Blog posts to display', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
	                <form id="icl_blog_posts" name="icl_blog_posts" action="">
		                <?php wp_nonce_field( 'icl_blog_posts_nonce', '_icl_nonce' ); ?>
		                <p>
			                <?php
			                $icl_only_translated_posts_checked = checked( 0, icl_get_setting( 'show_untranslated_blog_posts', 0 ), false )
			                ?>
			                <label>
				                <input type="radio" name="icl_untranslated_blog_posts" <?php echo $icl_only_translated_posts_checked; ?> value="0"/>
				                <?php _e( 'Only translated posts.', 'sitepress' ); ?>
			                </label>
		                </p>

		                <p>
			                <label>
				                <?php
				                $icl_all_posts_checked = checked( 1, icl_get_setting( 'show_untranslated_blog_posts', 0 ), false )
				                ?>
				                <input type="radio" name="icl_untranslated_blog_posts" <?php echo $icl_all_posts_checked; ?> value="1"/>
				                <?php _e( 'All posts (display translation if it exists or posts in default language otherwise).', 'sitepress' ); ?>
			                </label>

		                </p>

		                <div id="icl_untranslated_blog_posts_help" style="display: none">
			                <?php _e( "Please note that this setting affects only blog posts queried by the main loop in a theme's index.php template.", "sitepress" ); ?>
		                </div>
		                <p class="buttons-wrap">
			                <span class="icl_ajx_response" id="icl_ajx_response_bp"></span>
			                <input class="button button-primary" name="save" value="<?php _e( 'Save', 'sitepress' ) ?>" type="submit"/>
		                </p>
	                </form>
                </div>
            </div>

            <div class="wpml-section wpml-section-hide-languages" id="lang-sec-7">
                <div class="wpml-section-header">
                    <h3><?php _e('Hide languages', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
                    <p><?php _e("You can completely hide content in specific languages from visitors and search engines, but still view it yourself. This allows reviewing translations that are in progress.", 'sitepress') ?></p>
                    <form id="icl_hide_languages" name="icl_hide_languages" action="">
                        <?php wp_nonce_field('icl_hide_languages_nonce', '_icl_nonce') ?>
                        <?php foreach($active_languages as $l): ?>
                        <?php if($l['code'] == $default_language_details['code']) continue; ?>
                            <p>
                                <label>
                                    <input type="checkbox" name="icl_hidden_languages[]" <?php if(!empty( $hidden_languages ) && in_array($l['code'], $hidden_languages )) echo 'checked="checked"' ?> value="<?php echo $l['code']?>" /> <?php echo $l['display_name'] ?>
                                </label>
                            </p>
                        <?php endforeach; ?>
                        <p id="icl_hidden_languages_status">
                            <?php
                                if (!empty( $hidden_languages )){
																		//While checking for hidden languages, it cleans any possible leftover from inactive or deleted languages
																		if ( 1 == count( $hidden_languages ) ) {
																			if ( isset( $active_languages[ $hidden_languages[ 0 ] ] ) ) {
																				printf( __( '%s is currently hidden to visitors.', 'sitepress' ), $active_languages[ $hidden_languages[ 0 ] ][ 'display_name' ] );
																				$hidden_languages[] = $hidden_languages[ 0 ];
																			}
																		} else {
																			$_hlngs = array();
																			foreach ( $hidden_languages as $l ) {
																				if ( isset( $active_languages[ $l ] ) ) {
																					$_hlngs[ ] = $active_languages[ $l ][ 'display_name' ];
																				}
																			}
																			$hlangs = join( ', ', $_hlngs );
																			printf( __( '%s are currently hidden to visitors.', 'sitepress' ), $hlangs );
																		}

																		$hidden_languages = array_unique($hidden_languages);
																		$sitepress->set_setting('hidden_languages', $hidden_languages);

                                     echo '<p>';
                                        printf(__('You can enable its/their display for yourself, in your <a href="%s">profile page</a>.', 'sitepress'),'profile.php#wpml');
                                     echo '</p>';
                                }
                                else {
                                    _e('All languages are currently displayed.', 'sitepress');
                                }
                            ?>
                        </p>
                        <p class="buttons-wrap">
                            <span class="icl_ajx_response" id="icl_ajx_response_hl"></span>
                            <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                        </p>
                    </form>
                </div>
            </div>

            <div class="wpml-section wpml-section-ml-themes" id="lang-sec-8">
                <div class="wpml-section-header">
                    <h3><?php _e('Make themes work multilingual', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
                        <form id="icl_adjust_ids" name="icl_adjust_ids" action="">
                            <?php wp_nonce_field('icl_adjust_ids_nonce', '_icl_nonce'); ?>
                            <p><?php _e('This feature turns themes into multilingual, without having to edit their PHP files.', 'sitepress')?></p>
                            <p>
                                <label>
                                    <input type="checkbox" value="1" name="icl_adjust_ids" <?php if($sitepress->get_setting('auto_adjust_ids')) echo 'checked="checked"' ?> />
                                    <?php _e('Adjust IDs for multilingual functionality', 'sitepress')?>
                                </label>
                            </p>
                            <p class="explanation-text"><?php _e('Note: auto-adjust IDs will increase the number of database queries for your site.', 'sitepress')?></p>
                            <p class="buttons-wrap">
                                <span class="icl_ajx_response" id="icl_ajx_response_ai"></span>
                                <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                            </p>
                        </form>
                </div>
            </div>

            <div class="wpml-section wpml-section-redirect" id="lang-sec-9">
                <div class="wpml-section-header">
                    <h3><?php _e('Browser language redirect', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
                    <p><?php _e('WPML can automatically redirect visitors according to browser language.', 'sitepress')?></p>
                    <p class="explanation-text"><?php _e("This feature uses Javascript. Make sure that your site doesn't have JS errors.", 'sitepress'); ?></p>
                    <form id="icl_automatic_redirect" name="icl_automatic_redirect" action="">
                        <?php wp_nonce_field('icl_automatic_redirect_nonce', '_icl_nonce') ?>
                        <ul>
                            <li><label>
                                <input type="radio" value="0" name="icl_automatic_redirect" <?php if(empty( $automatic_redirect )) echo 'checked="checked"' ?> />
                                <?php _e('Disable browser language redirect', 'sitepress')?>
                            </label></li>
                            <li><label>
		                            <input type="radio" value="1" name="icl_automatic_redirect" <?php if ( 1 === (int) $automatic_redirect )
			                            echo 'checked="checked"' ?> />
                                <?php _e('Redirect visitors based on browser language only if translations exist', 'sitepress')?>
                            </label></li>
                            <li><label>
		                            <input type="radio" value="2" name="icl_automatic_redirect" <?php if ( 2 === (int) $automatic_redirect )
			                            echo 'checked="checked"' ?> />
                                <?php _e('Always redirect visitors based on browser language (redirect to home page if translations are missing)', 'sitepress')?>
                            </label></li>
                        </ul>
                        <ul>
                            <li>
	                            <label><?php printf( __( "Remember visitors' language preference for %s hours (please enter 24 or multiples of it).", 'sitepress' ), '<input size="2" type="number" min="24" value="'
	                                                                                                                                                                 . (int) $sitepress->get_setting( 'remember_language' )
	                                                                                                                                                                 . '" name="icl_remember_language" /> ' );
                                ?>
                                <?php if(!$wpml_request_handler->get_cookie_lang()): ?>
                                <span class="icl_error_text"><?php _e("Your browser doesn't seem to be allowing cookies to be set.", 'sitepress'); ?></span>
                                <?php endif; ?>
                            </label></li>
                        </ul>
                        <div class="wpml-form-message update-nag js-redirect-warning"<?php if( empty( $automatic_redirect ) ) : ?> style="display: none;"<?php endif; ?>>
                            <?php
                                $redirect_warning_1 = __( "Browser language redirect may affect your site's indexing", 'sitepress');
                                $redirect_warning_2 = __( "learn more", 'sitepress');
                                $url = 'https://wpml.org/documentation/getting-started-guide/language-setup/automatic-redirect-based-on-browser-language/how-browser-language-redirect-affects-google-indexing/';
                                echo $redirect_warning_1 . '- <a href="' . $url . '" target="_blank">' . $redirect_warning_2 . '</a>';
                            ?>
                        </div>
                        <p class="buttons-wrap">
                            <span class="icl_ajx_response" id="icl_ajx_response_ar"></span>
                            <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                        </p>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>


    <?php if(!empty( $setup_complete )): ?>

        <?php
				$request_get_page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE);
				do_action('icl_extra_options_' . $request_get_page);

	    $seo_ui = new WPML_SEO_HeadLangs($sitepress);
	    $seo_ui->render_menu();
	    ?>
        <div class="wpml-section wpml-section-wpml-love" id="lang-sec-10">
            <div class="wpml-section-header">
                <h3><?php _e('WPML love', 'sitepress') ?></h3>
            </div>
            <div class="wpml-section-content">
                <form id="icl_promote_form" name="icl_promote_form" action="">
                    <?php wp_nonce_field('icl_promote_form_nonce', '_icl_nonce'); ?>
                    <p>
                        <label><input type="checkbox" name="icl_promote" <?php if($sitepress->get_setting('promote_wpml')) echo 'checked="checked"' ?> value="1" />
                        <?php printf(__("Tell the world your site is running multilingual with WPML (places a message in your site's footer) - <a href=\"%s\">read more</a>", 'sitepress'),'https://wpml.org/?page_id=4560'); ?></label>
                    </p>
                    <p class="buttons-wrap">
                        <span class="icl_ajx_response" id="icl_ajx_response_lv"></span>
                        <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                    </p>
                </form>
            </div>
        </div>

    <?php endif; ?>

	<?php do_action( 'wpml_admin_after_wpml_love', $theme_wpml_config_file ); ?>
    <?php do_action( 'icl_menu_footer' ); ?>

</div> <!-- .wrap -->
<?php
}
//Save any changed setting
$sitepress->save_settings();
