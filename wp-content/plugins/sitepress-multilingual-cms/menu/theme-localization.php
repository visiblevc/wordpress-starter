<?php
global $WPML_ST_MO_Downloader;

if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
    return;
}
$active_languages = $sitepress->get_active_languages();

$mo_file_search = new WPML_MO_File_Search( $sitepress );
if ( ! $mo_file_search->has_mo_file_for_any_language( $active_languages ) ) {
	$mo_file_search->reload_theme_dirs();
}

$locales = $sitepress->get_locale_file_names();
$theme_localization_type = new WPML_Theme_Localization_Type( $sitepress );

?>

<div class="wrap">
    <h2><?php _e('Theme and plugins localization', 'sitepress') ?></h2>

    <h3><?php _e('Select how to translate strings in the theme and plugins','sitepress'); ?></h3>
    <p><?php _e("If your theme and plugins include .mo files with translations, these translations will always be used. This option allows you to provide new and alternative translations for texts in the theme and in plugins using WPML's String Translation.",'sitepress'); ?></p>
    <form name="icl_theme_localization_type" id="icl_theme_localization_type" method="post" action="">    
    <?php wp_nonce_field('icl_theme_localization_type_nonce', '_icl_nonce'); ?>
	    <ul>
		    <?php
		    if ( ! defined( 'WPML_ST_VERSION' ) ) {
			    $icl_st_note = __( "WPML's String Translation module lets you translate the theme, plugins and admin texts. To install it, go to your WPML account, click on Downloads and get WPML String Translation.", 'sitepress' );
			    $st_disabled = 'disabled="disabled" ';
		    } else {
			    $st_disabled = '';
		    }
		    $td_value = isset( $sitepress_settings['gettext_theme_domain_name'] ) ? $sitepress_settings['gettext_theme_domain_name'] : '';
		    if ( ! empty( $sitepress_settings['theme_localization_load_textdomain'] ) ) {
			    $ltd_checked = 'checked="checked" ';
		    } else {
			    $ltd_checked = '';
		    }
		    ?>

            <li>
                <label>
                    <input <?php echo $st_disabled; ?>type="radio" name="icl_theme_localization_type" value="<?php echo WPML_Theme_Localization_Type::USE_ST_AND_NO_MO_FILES ?>" <?php
				    if ( WPML_Theme_Localization_Type::USE_ST_AND_NO_MO_FILES == $sitepress_settings['theme_localization_type'] ): ?>checked="checked"<?php endif; ?> />&nbsp;
				    <?php _e( "Translate the theme and plugins using WPML's String Translation only (don't load .mo files)", 'sitepress' ) ?>
                </label>
			    <?php
			    if ( isset( $icl_st_note ) ) {
				    echo '<br><small><i>' . $icl_st_note . '</i></small>';
			    }
			    ?>
            </li>
		    <li>
			    <label>
				    <input <?php echo $st_disabled; ?>type="radio" name="icl_theme_localization_type" value="<?php echo WPML_Theme_Localization_Type::USE_ST ?>" <?php
				    if ( WPML_Theme_Localization_Type::USE_ST == $sitepress_settings['theme_localization_type'] ): ?>checked="checked"<?php endif; ?> />&nbsp;
                    <?php _e( "Translate the theme and plugins using WPML's String Translation and load .mo files as backup", 'sitepress' ) ?>
			    </label>
			    <?php
			    if ( isset( $icl_st_note ) ) {
				    echo '<br><small><i>' . $icl_st_note . '</i></small>';
			    }
			    ?>
		    </li>
		    <li>
			    <label>
				    <input type="radio" name="icl_theme_localization_type" value="2" <?php
				    if ( WPML_Theme_Localization_Type::USE_MO_FILES == $sitepress_settings['theme_localization_type'] ): ?>checked="checked"<?php endif; ?> />&nbsp;<?php _e( "Don't use String Translation to translate the theme and plugins", 'sitepress' ) ?>
			    </label>
			    <div id="icl_tt_type_extra" <?php if ( WPML_Theme_Localization_Type::USE_MO_FILES != $sitepress_settings['theme_localization_type'] ): ?>style="display:none"<?php endif; ?>>
				    <label>
					    <input type="checkbox" name="icl_theme_localization_load_td" value="1" <?php echo $ltd_checked ?>/>
					    &nbsp;<?php _e( "Automatically load the theme's .mo file using 'load_theme_textdomain'.", 'sitepress' ) ?>
				    </label>
				    <label id="icl_tt_type_extra_td" <?php if ( empty( $ltd_checked ) ): ?>style="display:none"<?php endif; ?>>
					    <?php _e( 'Enter textdomain value:', 'sitepress' ); ?>
					    <input type="text" name="textdomain_value" value="<?php echo esc_attr( $td_value ) ?>"/>
				    </label>
			    </div>
		    </li>
	    </ul>

        <p id="wpml_st_display_strings_scan_notices_box" <?php if ( ! $theme_localization_type->is_st_type() ) echo 'style="display: none;"'; ?> >
            <?php
            if ( class_exists( 'WPML_ST_Themes_And_Plugins_Settings' ) ) {
	            $themes_and_plugins_settings = new WPML_ST_Themes_And_Plugins_Settings();
	            $display_strings_scan_notices_checked = checked( true, $themes_and_plugins_settings->must_display_notices(), false );
            ?>
            <input type="checkbox" id="wpml_st_display_strings_scan_notices" name="wpml_st_display_strings_scan_notices" value="1" <?php echo $display_strings_scan_notices_checked; ?>>
            <label for="wpml_st_display_strings_scan_notices"><?php _e( 'Show an alert when activating plugins and themes, to scan for new strings', 'wpml-string-translation' ) ?></label>
            <?php } ?>
        </p>
    <p>
        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />        
        <span style="display:none" class="icl_form_errors icl_form_errors_1"><?php _e('Please enter a value for the textdomain.', 'sitepress'); ?></span>
    </p>
    <img src="<?php echo ICL_PLUGIN_URL ?>/res/img/question-green.png" width="29" height="29" alt="need help" align="left" /><p style="margin-top:14px;">&nbsp;<a href="https://wpml.org/?page_id=2717"><?php _e('Theme localization instructions', 'sitepress')?> &raquo;</a></p>
    </form>

    <?php if(defined('WPML_ST_VERSION') && version_compare(WPML_ST_VERSION, '1.4.0', '>') && $theme_localization_type->is_st_type()) include WPML_ST_PATH . '/menu/auto-download-mo-config.php'; ?>
    
    <?php if($sitepress_settings['theme_localization_type'] > 0):?>
    <br />
    <div id="icl_tl">
    <h3><?php _e('Language locale settings', 'sitepress') ?></h3>
    <p><?php _e('Select the locale to use for each language. The locale for the default language is set in your wp_config.php file.', 'sitepress') ?></p>
    <form id="icl_theme_localization" name="icl_theme_localization" method="post" action="">
    <input type="hidden" name="icl_post_action" value="save_theme_localization" />    
    <div id="icl_theme_localization_wrap"><div id="icl_theme_localization_subwrap">    
    <table id="icl_theme_localization_table" class="widefat" cellspacing="0">
    <thead>
    <tr>
    <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Code', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Locale file name', 'sitepress') ?></th>        
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), WP_LANG_DIR ) ?></th>        
    <?php if ( $sitepress_settings['theme_localization_type'] == WPML_Theme_Localization_Type::USE_MO_FILES ):?>

	    <?php
	        $dir_names = array();
	        foreach ($mo_file_search->get_dir_names() as $dir) {
		        $dir_names[] = sprintf(__('MO file in %s', 'sitepress'), $dir);
	        }
	    ?>
    <th scope="col"><?php echo implode('<br/>', $dir_names) ?></th>
    <?php endif; ?>
    <?php if ( isset( $WPML_ST_MO_Downloader ) && ! empty( $sitepress_settings[ 'st' ][ 'auto_download_mo' ] ) ): ?>
	    <?php
	    $wptranslations = $WPML_ST_MO_Downloader->get_option( 'translations' );
	    ?>
	    <th scope="col" align="right"><?php echo __( 'WP Translation', 'sitepress' ) ?></th>
	    <th scope="col">&nbsp;</th>
    <?php endif; ?>
    </tr>        
    </thead>        
    <tbody>
    <?php foreach($active_languages as $lang): ?>
    <tr>
    <td scope="col"><?php echo $lang['display_name'] ?></td>
    <td scope="col"><?php echo $lang['code'] ?></td>
    <td scope="col">
        <input type="text" size="10" name="locale_file_name_<?php echo $lang['code']?>" value="<?php echo isset($locales[$lang['code']]) ? $locales[$lang['code']] : ''; ?>" />.mo
    </td> 
    <td>
        <?php if(@is_readable( WP_LANG_DIR . '/' . $locales[$lang['code']] . '.mo')): ?>
        <span class="icl_valid_text"><?php echo __('File exists.', 'sitepress') ?></span>                
		<?php elseif($lang['code'] != 'en' ): ?>
        <span class="icl_error_text"><?php echo __('File not found!', 'sitepress') ?></span>
        <?php endif; ?>
    </td>
    <?php if($sitepress_settings['theme_localization_type'] == WPML_Theme_Localization_Type::USE_MO_FILES):?>
    <td>
        <?php
        $mofound = $mo_file_search->can_find_mo_file( $lang['code'] );
        ?>
        <?php if($mofound): ?>
        <span class="icl_valid_text"><?php echo __('File exists.', 'sitepress') ?></span>                
        <?php elseif($lang['code'] != 'en' ): ?>
        <span class="icl_error_text"><?php echo __('File not found!', 'sitepress') ?></span>
        <?php endif; ?>        
    </td>              
    <?php endif; ?> 
    <?php if(isset($WPML_ST_MO_Downloader) && !empty($sitepress_settings['st']['auto_download_mo'])):?>
    <td scope="col"><?php 
            
        $wpl_disabled = true;
        if($lang['code'] == 'en'){
            echo '&nbsp;';    
        }else{            
            if(empty($wptranslations[$lang['code']])){
                echo '<span class="icl_error_text" >' . __('not available', 'sitepress') . '</span>';
            }else{
                
                $update_available = array();
                foreach($wptranslations[$lang['code']] as $project => $info){
                    
                    // filter only core( & admin)
                    if($project != 'admin' && $project != 'core') continue;
                    
                    if(!empty($info['available']) && (empty($info['installed']) || $info['installed'] != $info['available'])){
                        $update_available[$project] = $info['available'];
                    }
                }
                if($update_available){
                    
                    $vkeys = array();
                    foreach($update_available as $project => $signature){
                        $vkeys[] = $project.'|'.$signature;
                    }                    
                    $updates_versions[$lang['code']] = join(';', $vkeys);
                    
                    echo '<strong class="icl_valid_text">' . __('Updates available', 'sitepress')  . '</strong>';                    
                    $wpl_disabled = false;
                }else{
                    echo '<span class="icl_valid_text" >' . __('Up to date', 'sitepress') . '</span>';
                }
                
            }
        }
    ?></td>
    <td scope="col" align="right">
        <?php if($lang['code'] == 'en'): ?>&nbsp;<?php else: ?>
        <a href="<?php if(!$wpl_disabled) echo admin_url('admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&amp;download_mo=' . $lang['code']. '&amp;version=' . $updates_versions[$lang['code']]); else echo '#'; ?>" class="button-secondary" <?php if($wpl_disabled): ?>disabled="disabled" onclick="return false;"<?php endif;?>><?php  _e('Review changes and update', 'wpml-string-translation') ?></a>
        <?php endif; ?>
    </td>
    <?php endif; ?>
    </tr>
    <?php endforeach; ?>                                                          
    </tbody>        
    </table>
    
    </div>
    </div>
    <p>
        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
        <span class="icl_ajx_response" id="icl_ajx_response_fn"></span>
    </p>
    </form>
	    <?php
	    if ( ! empty( $sitepress_settings[ 'st' ][ 'auto_download_mo' ] ) ) {
		    if ( isset( $WPML_ST_MO_Downloader ) ) {
			    if ( ! is_null( $WPML_ST_MO_Downloader->get_option( 'last_time_xml_check' ) ) ) {
				    if ( $WPML_ST_MO_Downloader->get_option( 'last_time_xml_check_trigger' ) == 'wp-update' ) {
					    printf( __( 'WPML last checked for WordPress translations %s when WordPress version updated. <a%s>Check now.</a>', 'sitepress' ), date( "F j, Y @H:i", $WPML_ST_MO_Downloader->get_option( 'last_time_xml_check' ) ), ' id="icl_adm_update_check" href="#"' );
				    } else {
					    printf( __( 'WPML last checked for WordPress translations %s (manual). <a%s>Check now.</a>', 'sitepress' ), date( "F j, Y @H:i", $WPML_ST_MO_Downloader->get_option( 'last_time_xml_check' ) ), ' id="icl_adm_update_check" href="#"' );
				    }
			    } else {
				    printf( __( 'WPML has never checked for WordPress translations. <a%s>Check now.</a>', 'sitepress' ), ' id="icl_adm_update_check" href="#"' );
			    }
		    }
	    }
	    ?>
    
    <br />    
    <div id="icl_adm_updates" class="icl_cyan_box" style="display:none"></div>    
    <br />
    </div> 
    <?php endif; ?>
    
    <?php do_action('icl_custom_localization_type'); ?>
    
    
    <?php do_action('icl_menu_footer'); ?>
               
</div>
