<?php

include_once ICL_PLUGIN_PATH . '/inc/functions-debug-information.php';
$debug_info = get_debug_info();
$debug_data = $debug_info->run();

/* DEBUG ACTION */
/**
 * @param $term_object
 *
 * @return callable
 */
?>
<div class="wrap">
	<h1><?php echo __( 'Debug information', 'sitepress' ) ?></h1>
	<?php

	$message = filter_input( INPUT_GET, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );

	if ( $message ) { ?>
		<div class="updated message fade"><p>
				<?php echo esc_html( $message ); ?>
			</p></div>
	<?php } ?>
	<div id="poststuff">
		<div id="wpml-debug-info" class="postbox">
			<div class="inside">
				<p><?php _e( 'This information allows our support team to see the versions of WordPress, plugins and theme on your site. Provide this information if requested in our support forum. No passwords or other confidential information is included.', 'sitepress' ) ?></p>
				<br/>
				<?php
				echo '<textarea style="font-size:10px;width:100%;height:150px;" rows="16" readonly="readonly">';
				echo esc_html( $debug_info->do_json_encode( $debug_data ) );
				echo '</textarea>';
				?>
			</div>
		</div>
	</div>

	<?php do_action( 'icl_menu_footer' ); ?>
</div>
