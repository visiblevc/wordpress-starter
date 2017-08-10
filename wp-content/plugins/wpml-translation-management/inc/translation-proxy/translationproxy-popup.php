<?php

define( 'ICL_LANGUAGE_NOT_SUPPORTED', 3 );
global $wpdb, $sitepress;

$target         = filter_input( INPUT_GET, 'target', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$auto_resize    = filter_input( INPUT_GET, 'auto_resize', FILTER_VALIDATE_BOOLEAN | FILTER_NULL_ON_FAILURE  );
$unload_cb      = filter_input( INPUT_GET, 'unload_cb', FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_NULL_ON_FAILURE  );

// Adding a translator
if ( preg_match( '|^@select-translators;([^;]+);([^;]+)@|', $target, $matches ) ) {
	$source_language = $matches[1];
	$target_language = $matches[2];
	$project         = TranslationProxy::get_current_project();
	try {
		$lp_setting_index = 'language_pairs';
		$language_pairs   = $sitepress->get_setting( $lp_setting_index, array() );
		if ( ! isset( $language_pairs[ $source_language ][ $target_language ] ) || $language_pairs[ $source_language ][ $target_language ] == 0 ) {
			$language_pairs[ $source_language ][ $target_language ] = 1;
			TranslationProxy_Translator::update_language_pairs( $project, $language_pairs );
			$sitepress->set_setting( $lp_setting_index, $language_pairs, true );
		}
		$target = $project->select_translator_iframe_url( $source_language, $target_language );
	} catch ( Exception $e ) {
		if ( $e->getCode() == ICL_LANGUAGE_NOT_SUPPORTED ) {
			printf( __( '<p>Requested languages are not supported by the translation service (%s). Please <a%s>contact us</a> for support. </p>', 'sitepress' ), $e->getMessage(), ' target="_blank" href="http://wpml.org/?page_id=5255"' );
		} else {
			printf( __( '<p>Could not add the requested languages. Please <a%s>contact us</a> for support. </p><p>Show <a%s>debug information</a>.</p>', 'sitepress' ), ' target="_blank" href="http://wpml.org/?page_id=5255"',
				' a href="admin.php?page=' .
				ICL_PLUGIN_FOLDER .
				'/menu/troubleshooting.php&icl_action=icl-connection-test' .
				'#icl-connection-test"' );
		}
		exit;
	}
}

$target .= ( strpos( $target, '?' ) === false ) ? '?' : '&';
$target .= "lc=" . $sitepress->get_admin_language();
?>

<iframe src="<?php echo $target; ?>" style="width:100%; height:92%" onload="    var TB_window = jQuery('#TB_window');
<?php if ( $auto_resize ): ?>
	TB_window.css('width','90%').css('margin-left', '-45%');
<?php endif; ?>
<?php if ( $unload_cb ){
	$unload_cb = esc_js($unload_cb);
	?>
	TB_window.unbind('unload').bind('tb_unload', function(){<?php echo $unload_cb; ?>});
<?php } ?>
	">