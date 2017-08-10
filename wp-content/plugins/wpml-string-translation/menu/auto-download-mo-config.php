    <br />
    <form id="icl_auto_download_mo" name="icl_auto_download_mo" method="post" action="">
    <input type="hidden" name="action" value="icl_adm_save_preferences" />
    <h3><?php _e('Select how to get translations for WordPress core', 'wpml-string-translation') ?></h3>

    <?php wp_nonce_field('icl_auto_download_mo_nonce', '_icl_nonce'); ?>
    <ul style="display:inline-block;padding:0;margin:0;" id="icl_adm_options">
        <li>
            <label>
                <input type="radio" name="auto_download_mo" value="1" <?php if(!empty($sitepress_settings['st']['auto_download_mo'])):?>checked="checked"<?php endif; ?> />
                &nbsp;<?php _e('WPML will automatically download translations for WordPress', 'wpml-string-translation') ?>
            </label>
        </li>
        <li>
            <label>
                <input type="radio" name="auto_download_mo" value="0" <?php if(empty($sitepress_settings['st']['auto_download_mo'])):?>checked="checked"<?php endif; ?> />
                &nbsp;<?php _e('I will download translations for WordPress and save .mo files in wp-content/languages', 'wpml-string-translation') ?>
            </label>
        </li>
    </ul>
    
    <p>
    <input class="button-secondary" type="submit" value="<?php _e('Save', 'wpml-string-translation')?>" />
    <span class="icl_ajx_response" id="icl_ajx_response2" style="display:inline"></span>
    </p>
    
    </form>
        