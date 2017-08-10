<?php 
global $sitepress, $WPML_ST_MO_Downloader;

$language = isset( $_GET['download_mo'] ) ? filter_var( $_GET['download_mo'], FILTER_SANITIZE_STRING ) : false;
$active_languages = $sitepress->get_active_languages();
$version = isset( $_GET['version'] ) ? filter_var( $_GET['version'], FILTER_SANITIZE_STRING ) : false;

$translations = array();
if ( isset( $active_languages[ $language ] ) ) {
	try {
		$WPML_ST_MO_Downloader->load_xml();
		$WPML_ST_MO_Downloader->get_translation_files();
		$version_projects = explode( ';', $version );
		$types            = array();
		foreach ( $version_projects as $project ) {
			$exp     = explode( '|', $project );
			$types[] = $exp[0];
		}
		$translations = $WPML_ST_MO_Downloader->get_translations( $language, array( 'types' => $types ) );

	} catch ( Exception $error ) {
		$user_errors[] = $error->getMessage();
	}
}

if ( isset( $_POST['action'] ) && $_POST['action'] == 'icl_admo_add_translations' && wp_verify_nonce( $_POST['_wpnonce'], 'icl_adm_save_translations' ) ) {
	$translations_add = array();
	if ( ! empty( $_POST['add_new'] ) && array_key_exists( 'new', $translations ) ) {
	    $new_translations = $translations['new'];
	    foreach ( $new_translations as $tr ) {
		    $translations_add[] = array(
			    'string'          => filter_var( $tr['string'], FILTER_SANITIZE_STRING ),
			    'translation'     => filter_var( $tr['new'], FILTER_SANITIZE_STRING ),
			    'name'            => filter_var( $tr['name'], FILTER_SANITIZE_STRING ),
			    'gettext_context' => filter_var( $tr['gettext_context'], FILTER_SANITIZE_STRING ),
		    );
	    }
		if ( ! empty( $translations_add ) ) {
			$user_messages[] = sprintf( _n( '%d new translation was added.', '%d new translations were added.', count( $translations_add ), 'wpml-string-translation' ), count( $translations_add ) );
		}
	}
	if ( ! empty( $_POST['selected'] ) ) {
		$translations_updated = 0;
		foreach ( $_POST['selected'] as $idx => $v ) {
			if ( ! empty( $v ) ) {
				$translations_add[] = array(
					'string'          => filter_var( base64_decode( $_POST['string'][ $idx ] ), FILTER_SANITIZE_STRING ),
					'translation'     => filter_var( base64_decode( $_POST['translation'][ $idx ] ), FILTER_SANITIZE_STRING ),
					'name'            => filter_var( base64_decode( $_POST['name'] [ $idx ] ), FILTER_SANITIZE_STRING ),
					'gettext_context' => filter_var( base64_decode( $_POST['gettext_context'][ $idx ] ), FILTER_SANITIZE_STRING ),
				);
				$translations_updated ++;
			}
		}
		if ( $translations_updated ) {
			$user_messages[] = sprintf( _n( '%d translation was updated.', '%d translations were updated.', $translations_updated, 'wpml-string-translation' ), $translations_updated );

		}
	}
    if($translations_add){
        $WPML_ST_MO_Downloader->save_translations($translations_add, $_POST['language'], $_POST['version']);    
    }else{
        $user_errors[] = __('No action performed. Please select the strings that you need to update or add.', 'wpml-string-translation');
    }

}



?>

<div class="wrap">
    <h2><?php _e('Auto-download WordPress translations', 'wpml-string-translation') ?></h2>    
    
    <?php if(!empty($translations_updated) || !empty($translations_add)):?>
    
        <p><strong><?php _e('Success!', 'wpml-string-translation') ?></strong></p>
        <?php foreach($user_messages as $umessage): ?>
        <p><?php echo $umessage ?></p>
        <?php endforeach; ?>    
        <a href="<?php echo admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/theme-localization.php'); ?>" class="button-secondary"><?php _e('Check other languages', 'wpml-string-translation') ?></a>
        
    <?php elseif(!$version): ?>
        <div class="error">
            <p><?php _e('Missing version number for translation.', 'wpml-string-translation') ?></p>
        </div>    
    <?php elseif(!isset($active_languages[$language])): ?>
        <div class="error">
            <p><?php printf(__('Invalid language: %s', 'wpml-string-translation'), $language) ?></p>
        </div>
    
    <?php elseif(!empty($user_errors)): ?>
        <div class="error below-h2">
        <?php foreach($user_errors as $uerror): ?>
        <p><?php echo $uerror ?></p>
        <?php endforeach; ?>
        </div>
    
    <?php elseif(!empty($translations)): ?>
        <br />
        <div class="icl_cyan_box">
        <?php printf(__('This update includes %d new strings and %d updated strings. You can review the strings below. Then, go to the <a%s>bottom of this page</a> and click on the Proceed button.', 'wpml-string-translation'), 
            isset($translations['new']) ? count($translations['new']) : 0, 
            isset($translations['updated']) ? count($translations['updated']) : 0,
            ' href="#adm-proceed"'); 
        ?>
        </div>
    
        <form id="icl_admo_list" method="post" action="">
        <input type="hidden" name="action" value="icl_admo_add_translations" />
        <input type="hidden" name="language" value="<?php echo $language ?>" />
        <input type="hidden" name="version" value="<?php echo $version ?>" />
        <?php wp_nonce_field('icl_adm_save_translations'); ?>
        
        <?php if(!empty($translations['updated'])): ?>
        <h3><?php printf(__('Updated translations (%d)', 'wpml-string-translation'), count($translations['updated'])) ?></h3>
        
        <table id="icl_admo_list_table" class="widefat">
        <thead>
            <tr>
                <th class="manage-column column-cb check-column" scope="col"><input type="checkbox" name="" value="" checked="checked" /></th>
                <th><?php _e('String', 'wpml-string-translation') ?></th>
                <th style="text-align:center;"><?php _e('Existing translation', 'wpml-string-translation') ?></th>
                <th style="text-align:center;"><?php _e('New translation', 'wpml-string-translation') ?></th>
            </tr>
        </thead>
        
        <tbody>
        
        <?php foreach($translations['updated'] as $idx => $translation): ?>            
            <tr>
                <td class="column-cb">
                    <input type="hidden" name="selected[<?php echo $idx ?>]" value="0" />
                    <input type="checkbox" name="selected[<?php echo $idx ?>]" value="1"  checked="checked" />
                </td>
                <td>
                    <?php echo esc_html($translation['string']) ?>
                    <input type="hidden" name="string[<?php echo $idx ?>]" value="<?php echo base64_encode($translation['string']); ?>" />
                    <input type="hidden" name="name[<?php echo $idx ?>]" value="<?php echo base64_encode($translation['name']); ?>" />
                    <input type="hidden" name="gettext_context[<?php echo $idx ?>]" value="<?php echo base64_encode($translation['gettext_context']); ?>" />
                </td>
                <td colspan="2">
                    <?php echo wp_text_diff($translation['translation'], $translation['new']); ?>
                    <input type="hidden" name="translation[<?php echo $idx ?>]" value="<?php echo base64_encode($translation['new']); ?>" />
                </td>
            </tr>      
            <?php $idx++; ?>  
        <?php endforeach; ?>
        
        </tbody>
        
        <tfoot>
            <tr>
                <th class="manage-column column-cb check-column" scope="col"><input type="checkbox" name="" value="" checked="checked" /></th>
                <th><?php _e('String', 'wpml-string-translation') ?></th>
                <th style="text-align:center;"><?php _e('Existing translation', 'wpml-string-translation') ?></th>
                <th style="text-align:center;"><?php _e('New translation', 'wpml-string-translation') ?></th>
            </tr>
        </tfoot>        
        </table>
        <?php endif; ?>
        
        
        <?php if(!empty($translations['new'])): ?>
        <h3><?php printf(__('New translations (%d)', 'wpml-string-translation'), count($translations['new'])) ?></h3>
        <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('String', 'wpml-string-translation') ?></th>
                <th><?php _e('Translation', 'wpml-string-translation') ?></th>
            </tr>
        </thead>
        
        <tbody>
        <?php foreach($translations['new'] as $idx => $translation): ?>            
            <tr>
                <td>
                    <?php echo esc_html($translation['string']) ?>
                </td>
                <td><?php echo esc_html($translation['new']) ?>&nbsp;</td>
            </tr>      
        <?php endforeach; ?>
        </tbody>
        
        <tfoot>
            <tr>
                <th><?php _e('String', 'wpml-string-translation') ?></th>
                <th><?php _e('Translation', 'wpml-string-translation') ?></th>
            </tr>
        </tfoot>        
        </table>
        
        <p>        
            <label><input type="checkbox" name="add_new" value="1" checked="checked" />&nbsp;<?php _e('Add the new translations.', 'wpml-string-translation'); ?></label>
        </p>
        <?php endif; ?>
        
        <a name="adm-proceed"></a>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php esc_attr_e('Proceed', 'wpml-string-translation') ?>" />&nbsp;
            <a class="button-secondary" href="<?php echo admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/theme-localization.php') ?>"><?php _e('Cancel', 'wpml-string-translation') ?></a>
        </p>  
        
        </form>              
    <?php else: ?>
    
        <p><?php _e('There is nothing to be updated or to be added.', 'wpml-string-translation') ?></p>
        <p><a href="<?php echo admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/theme-localization.php'); ?>" class="button-secondary"><?php _e('Check other languages', 'wpml-string-translation') ?></a></p>
    
    <?php endif; ?>
    
    
</div>