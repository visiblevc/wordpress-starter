<?php
	$admin_texts = wpml_st_load_admin_texts();
    $troptions = $admin_texts->icl_st_scan_options_strings();
?>
<div class="wrap">
    <h2><?php echo __('String translation', 'wpml-string-translation') ?></h2>    
    
    <?php if(!empty($troptions)): ?>
    <div id="icl_st_option_writes">
    <p><?php _e('This table shows all the admin texts that WPML  found.', 'wpml-string-translation'); ?></p>
    <p><?php printf(__('The fields with <span%s>red</span> background are text fields and the fields with <span%s>cyan</span> background are numeric.', 'wpml-string-translation'),' class="icl_st_string"',' class="icl_st_numeric"'); ?></p>
    <p><?php printf(__("Choose the fields you'd like to translate and click on the 'Apply' button. Then, use WPML's <a%s>String translation</a> to translate them.", 'wpml-string-translation'),' href="admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php"'); ?></p>    
    
    <p>
        <input type="button" class="button" id="icl_st_ow_export" value="<?php _e('Export selected strings as a WPML config file that can be added to themes or plugins', 'wpml-string-translation'); ?>" />
        <input type="button" class="button-primary" id="icl_st_ow_export_close" value="<?php _e('Close', 'wpml-string-translation')?>" />
        <img class="ajax_loader" src="<?php echo WPML_ST_URL ?>/res/img/ajax-loader.gif" style="display:none" width="16" height="16" />
        <?php wp_nonce_field('icl_st_ow_export_nonce', '_icl_nonce_owe') ?>
    </p>
    <p id="icl_st_ow_export_out"></p>
    
    <form name="icl_st_option_writes_form" id="icl_st_option_write_form">    
		<?php wp_nonce_field('icl_st_option_writes_form_nonce', '_icl_nonce'); ?>    
		<?php foreach($troptions as $option_name=>$option_value): ?>
			<?php echo $admin_texts->icl_st_render_option_writes($option_name, $option_value); ?>
			<br clear="all" />
		<?php endforeach; ?>    
		<span id="icl_st_options_write_success" class="hidden updated message fade"><?php printf(__('The selected strings can now be translated using the <a%s>string translation</a> screen', 'wpml-string-translation'), ' href="admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php"');?></span>
		<span id="icl_st_options_write_confirm" class="hidden"><?php _e('You have removed some of the texts that are translated already. The translations will be lost.','wpml-string-translation')?></span>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Apply', 'wpml-string-translation');?>" />
			<span class="icl_ajx_response" id="icl_ajx_response"></span>
		</p>
    
    </form>
    </div>
    <?php else: ?>
    <div align="center"><?php _e('No options found. Make sure you saved your theme options at least once. <br />Some themes only add these to the wp_options table after the user explicitly saves over the theme defaults', 'wpml-string-translation') ?></div>
    <?php endif; ?>
    
</div>
