<?php

include_once ICL_PLUGIN_PATH . '/inc/functions-troubleshooting.php';

/* DEBUG ACTION */
/**
 * @param $term_object
 *
 * @return callable
 */
function get_term_taxonomy_id_from_term_object($term_object)
{
	return $term_object->term_taxonomy_id;
}
$action = filter_input(INPUT_GET, 'debug_action', FILTER_SANITIZE_STRING);
$nonce  = filter_input(INPUT_GET, 'nonce', FILTER_SANITIZE_STRING);
if ( ! $action ) {
	$action = filter_input( INPUT_POST, 'debug_action', FILTER_SANITIZE_STRING );
	$nonce  = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
}

$otgs_twig_cache_disable_key = '_otgs_twig_cache_disabled';
if ( defined( 'WPML_Templates_Factory::OTGS_TWIG_CACHE_DISABLED_KEY' ) ) {
	$otgs_twig_cache_disable_key = WPML_Templates_Factory::OTGS_TWIG_CACHE_DISABLED_KEY;
}

if ( isset( $action ) && wp_verify_nonce( $nonce, $action ) ) {
	ob_end_clean();
	global $wpdb;
	switch ( $action ) {
	  case 'otgs_twig_cache_enable':
		  $new_value = false;

		  if ( array_key_exists( 'new_value', $_POST ) ) {
			  $new_value = (bool) $_POST['new_value'];
		  }
	    update_option( $otgs_twig_cache_disable_key, $new_value, 'no' );
		  exit;
	  case 'fix_languages':
		  SitePress_Setup::fill_languages();
		  SitePress_Setup::fill_languages_translations();
		  icl_cache_clear();
		  exit;
		case 'icl_fix_collation':
			repair_el_type_collate();
			exit;

		case 'cache_clear':
			icl_cache_clear();
			$cache_directory = new WPML_Cache_Directory( new WPML_WP_API() );
			$cache_directory->remove();
			exit;

		case 'ghost_clean':

			// clean the icl_translations table
			$orphans = $wpdb->get_col( "
                SELECT t.translation_id, t.element_type 
                FROM {$wpdb->prefix}icl_translations t 
                LEFT JOIN {$wpdb->posts} p ON t.element_id = p.ID 
                WHERE t.element_id IS NOT NULL AND t.element_type LIKE 'post\\_%' AND p.ID IS NULL
            " );
			if ( !empty( $orphans ) ) {

				$upgrade_args_set = array();
				foreach( $orphans as $orphan ) {
					$upgrade_args = array(
						'translation_id' => $orphan,
						'context' => 'post'
					);

					$upgrade_args_set[] = $upgrade_args;

					do_action( 'wpml_translation_update', array_merge( $upgrade_args, array( 'type' => 'before_delete' ) ) );
				}

				$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translations
                               WHERE translation_id IN (" . wpml_prepare_in( $orphans, '%d' ) . ")" );

				foreach( $upgrade_args_set as $upgrade_args ) {
					do_action( 'wpml_translation_update', array_merge( $upgrade_args, array( 'type' => 'after_delete' ) ) );
				}
			}

			$orphans = $wpdb->get_col( "
                SELECT t.translation_id 
                FROM {$wpdb->prefix}icl_translations t 
                LEFT JOIN {$wpdb->comments} c ON t.element_id = c.comment_ID
                WHERE t.element_type = 'comment' AND c.comment_ID IS NULL " );
			if ( false === $orphans ) {
				echo $wpdb->last_result; 
			}
			if ( !empty( $orphans ) ) {

				$upgrade_args_set = array();
				foreach( $orphans as $orphan ) {
					$upgrade_args = array(
						'translation_id' => $orphan,
						'context' => 'comment'
					);

					$upgrade_args_set[] = $upgrade_args;

					do_action( 'wpml_translation_update', array_merge( $upgrade_args, array( 'type' => 'before_delete' ) ) );
				}

				$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translations
                               WHERE translation_id IN (" . wpml_prepare_in( $orphans, '%d' ) . ")" );

				foreach( $upgrade_args_set as $upgrade_args ) {
					do_action( 'wpml_translation_update', array_merge( $upgrade_args, array( 'type' => 'after_delete' ) ) );
				}
			}

			$orphans = $wpdb->get_col( "
                SELECT t.translation_id 
                FROM {$wpdb->prefix}icl_translations t 
                LEFT JOIN {$wpdb->term_taxonomy} p ON t.element_id = p.term_taxonomy_id 
                WHERE t.element_id IS NOT NULL AND t.element_type LIKE 'tax\\_%' AND p.term_taxonomy_id IS NULL" );
			if ( !empty( $orphans ) ) {

				$upgrade_args_set = array();
				foreach( $orphans as $orphan ) {
					$upgrade_args = array(
						'translation_id' => $orphan,
						'context' => 'tax'
					);

					$upgrade_args_set[] = $upgrade_args;
					do_action( 'wpml_translation_update', $upgrade_args );
				}

				$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translations
                               WHERE translation_id IN (" . wpml_prepare_in( $orphans, '%d' ) . ")" );

				foreach( $upgrade_args_set as $upgrade_args ) {
					do_action( 'wpml_translation_update', array_merge( $upgrade_args, array( 'type' => 'after_delete' ) ) );
				}
			}

			global $wp_taxonomies;
			if ( is_array( $wp_taxonomies ) ) {
				foreach ( $wp_taxonomies as $t => $v ) {
					$orphans = $wpdb->get_col( "
                SELECT t.translation_id 
                FROM {$wpdb->prefix}icl_translations t 
                LEFT JOIN {$wpdb->term_taxonomy} p 
                ON t.element_id = p.term_taxonomy_id 
                WHERE t.element_type = 'tax_{$t}' 
                AND p.taxonomy <> '{$t}'
                    " );
					if ( !empty( $orphans ) ) {

						$upgrade_args_set = array();
						foreach( $orphans as $orphan ) {

							$upgrade_args = array(
								'translation_id' => $orphan,
								'context' => 'tax'
							);

							$upgrade_args_set[] = $upgrade_args;
							do_action( 'wpml_translation_update', $upgrade_args );
						}

						$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translations
                                       WHERE translation_id IN (" . wpml_prepare_in( $orphans, '%d' ) . ")" );

						foreach( $upgrade_args_set as $upgrade_args ) {
							do_action( 'wpml_translation_update', array_merge( $upgrade_args, array( 'type' => 'after_delete' ) ) );
						}
					}
				}
			}

			// remove ghost translations
			// get unlinked rids
			$rids = $wpdb->get_col( "SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id NOT IN (SELECT translation_id FROM {$wpdb->prefix}icl_translations)" );
			if ( $rids ) {
				$jids = $wpdb->get_col( "SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid IN (" . wpml_prepare_in( $rids, '%d' ) . ")" );
				if ( $jids ) {
					$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id IN (" . wpml_prepare_in( $jids, '%d' ) . ")" );
					$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id IN (" . wpml_prepare_in( $jids, '%d' ) . ")" );
					$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translation_status WHERE rid IN (" . wpml_prepare_in( $rids, '%d' ) . ")" );
				}
			}

			// remove any duplicates in icl_translations
			$trs = $wpdb->get_results( "SELECT element_id, GROUP_CONCAT(translation_id) AS tids FROM {$wpdb->prefix}icl_translations
                WHERE element_id > 0 AND element_type LIKE 'post\\_%' GROUP BY element_id" );
			foreach ( $trs as $r ) {
				$exp = explode( ',', $r->tids );
				if ( count( $exp ) > 1 ) {
					$maxtid = max( $exp );
					foreach ( $exp as $e ) {
						if ( $e != $maxtid ) {
							$upgrade_args = array(
								'translation_id' => $e,
								'context' => 'post'
							);

							do_action( 'wpml_translation_update', array_merge( $upgrade_args, array( 'type' => 'before_delete' ) ) );

							$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $e ) );

							do_action( 'wpml_translation_update', array_merge( $upgrade_args, array( 'type' => 'after_delete' ) ) );
						}
					}
				}
			}


			exit;
			break;
		case 'assign_translation_status_to_duplicates':
			global $sitepress, $iclTranslationManagement;

			$active_languages     = $sitepress->get_active_languages();
			$duplicated_posts_sql = "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_icl_lang_duplicate_of' AND meta_value<>'' GROUP BY meta_value;";
			$duplicated_posts     = $wpdb->get_col( $duplicated_posts_sql );
			$updated_items        = 0;
			foreach ( $duplicated_posts as $original_post_id ) {
				$element_type             = 'post_' . get_post_type( $original_post_id );
				$trid                     = $sitepress->get_element_trid( $original_post_id, $element_type );
				$element_language_details = $sitepress->get_element_translations( $trid, $element_type );
				$item_updated             = false;
				foreach ( $active_languages as $code => $active_language ) {
					if ( ! isset( $element_language_details[ $code ] ) ) {
						continue;
					}
					$element_translation = $element_language_details[ $code ];
					if ( ! isset( $element_translation ) || $element_translation->original ) {
						continue;
					}
					$translation = $iclTranslationManagement->get_element_translation( $element_translation->element_id,
					                                                                   $code,
					                                                                   $element_type );
					if ( ! $translation ) {
						$status_helper = wpml_get_post_status_helper();
						$status_helper->set_status( $element_translation->element_id, ICL_TM_DUPLICATE );
						$item_updated = true;
					}
				}
				if ( $item_updated ) {
					$updated_items ++;
				}
				if ( $updated_items >= 20 ) {
					break;
				}
			}

			echo json_encode( array( 'updated' => $updated_items ) );
			exit;
		case 'icl_ts_add_missing_language':
			global $iclTranslationManagement;
			$iclTranslationManagement->add_missing_language_information();
			exit;
		case 'link_post_type':

			$old_el_type = 'post_' . sanitize_key ( filter_input ( INPUT_GET, 'old_value' ) );
			$new_el_type = 'post_' . sanitize_key ( filter_input ( INPUT_GET, 'new_value' ) );

			$wpdb->update (
				$wpdb->prefix . 'icl_translations',
				array( 'element_type' => $new_el_type ),
				array( 'element_type' => $old_el_type )
			);

			do_action(
				'wpml_translation_update',
				array(
					'type' => 'element_type_update',
					'element_type' => $new_el_type,
					'context' => 'post'
				)
			);

			exit;
		case 'link_taxonomy':

			$new_el_type = 'tax_' . $_GET[ 'new_value' ];
			$old_el_type = 'tax_' . $_GET[ 'old_value' ];

			$wpdb->update(
				$wpdb->prefix . 'icl_translations',
				array( 'element_type' => $new_el_type ),
				array( 'element_type' => $old_el_type ) );

			do_action(
				'wpml_translation_update',
				array(
					'type' => 'element_type_update',
					'element_type' => $new_el_type,
					'content' => 'tax'
				)
			);

			exit;
		case 'icl_fix_terms_count':
			global $sitepress;

			remove_filter('get_terms_args', array($sitepress, 'get_terms_args_filter'));
			$has_get_term_filter = remove_filter('get_term', array($sitepress,'get_term_adjust_id'), 1);
			remove_filter('terms_clauses', array($sitepress,'terms_clauses'));
			foreach ( get_taxonomies( array(), 'names' ) as $taxonomy ) {

				$terms_objects = get_terms( $taxonomy, 'hide_empty=0'  );
				if ( $terms_objects ) {
					$term_taxonomy_ids = array_map( 'get_term_taxonomy_id_from_term_object', $terms_objects );
					wp_update_term_count( $term_taxonomy_ids, $taxonomy, true );
				}

			}
			add_filter('terms_clauses', array($sitepress,'terms_clauses'));
			if ( $has_get_term_filter ) {
				add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
			}
			add_filter('get_terms_args', array($sitepress, 'get_terms_args_filter'), 10, 2);
			exit;
		case 'icl_remove_st_db_cache_logs' :
			delete_option( 'wpml-st-persist-errors' );
			exit;
	}
}
/* DEBUG ACTION */
global $sitepress;

if ( wp_verify_nonce(
	(string)filter_input( INPUT_POST, 'icl_reset_allnonce' ),
	'icl_reset_all'
) ) {
	if ( $_POST[ 'icl-reset-all' ] == 'on' ) {
		icl_reset_wpml();
		echo '<script type="text/javascript">location.href=\'' . admin_url(
				'plugins.php?deactivate=true'
			) . '\'</script>';
		exit();
	}
}


?>
<div class="wrap">
<h2><?php echo __( 'Troubleshooting', 'sitepress' ) ?></h2>
<?php if ( isset( $_GET[ 'message' ] ) ){ ?>
	<div class="updated message fade"><p>
			<?php echo esc_html( $_GET[ 'message' ] ); ?>
		</p></div>
<?php } ?>
<?php

echo '<a href="#wpml-settings">' . __( 'WPML Settings', 'sitepress' ) . '</a>';
echo '<br /><hr /><h3 id="wpml-settings"> ' . __( 'WPML settings', 'sitepress' ) . '</h3>';
echo '<textarea style="font-size:10px;width:100%" wrap="off" rows="16" readonly="readonly">';
ob_start();
print_r( $sitepress->get_settings() );
$ob = ob_get_contents();
ob_end_clean();
echo esc_html( $ob );
echo '</textarea>';

?>

<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery('#icl_troubleshooting_more_options').submit(iclSaveForm);
	})
</script>
<br clear="all"/><br/>

<br/>
<script type="text/javascript">
	function wpml_ts_link_post_type(select, old_value) {
		if (!select.val()) return;
		select.attr('disabled', 'disabled');
		select.after(icl_ajxloaderimg);
		jQuery.post(location.href + '&debug_action=link_post_type&nonce=<?php echo wp_create_nonce('link_post_type'); ?>&new_value=' + select.val() + '&old_value=' + old_value, function () {
			alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
			select.next().fadeOut();
			location.reload();
		});
	}

	function wpml_ts_link_taxonomy(select, old_value) {
		if (!select.val()) return;
		select.attr('disabled', 'disabled');
		select.after(icl_ajxloaderimg);
		jQuery.post(location.href + '&debug_action=link_taxonomy&nonce=<?php echo wp_create_nonce('link_taxonomy'); ?>&new_value=' + select.val() + '&old_value=' + old_value, function () {
			alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
			select.next().fadeOut();
			location.reload();
		});
	}

	function parse_xhr_error(xhr, status, error) {
		return xhr.statusText || status || error;
	}

	jQuery(document).ready(function ($) {
		$('#otgs_twig_cache_enable').find('.button-secondary').on('click', function () {
			jQuery(this).attr('disabled', 'disabled');
			jQuery(this).after(icl_ajxloaderimg);

			var value = jQuery(this).parent().find('.otgs_twig_cache_new_value').val();
			var key = '<?php echo $otgs_twig_cache_disable_key ?>';

			jQuery.ajax({
										type:    "POST",
										url:     location.href,
										data:    {
											'debug_action': 'otgs_twig_cache_enable',
											'nonce':        '<?php echo wp_create_nonce( 'otgs_twig_cache_enable' ); ?>',
											'new_value':    value
										},
										success: function () {
											alert('<?php echo esc_js( __( 'Done', 'sitepress' ) ) ?>');
											location.reload();
										}
									});

		});

		jQuery('#icl_fix_languages').click(function () {
			jQuery(this).attr('disabled', 'disabled');
			jQuery(this).after(icl_ajxloaderimg);


			var icl_fix_languages = jQuery('#icl_fix_languages');

			jQuery.ajax({
				type: 'POST',
				contentType: "application/json; charset=utf-8",
				url: location.href + '&debug_action=fix_languages&nonce=<?php echo wp_create_nonce('fix_languages'); ?>',
				timeout: 60000,
				success: function () {
					icl_fix_languages.removeAttr('disabled');
					alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
					icl_fix_languages.next().fadeOut();
					location.reload();
				},
				error: function (jqXHR, status, error) {
					var parsed_response = parse_xhr_error(jqXHR, status, error);

					<?php
					$timeout_message = 'The operation timed out, but languages may still get fixed in the background.\n';
					$timeout_message .= 'Please wait 5-10 minutes, then refresh or come back to this page.\n';
					$timeout_message .= 'If languages are still not fixed, please retry or contact the WPML support.'
					?>

					if(parsed_response=='timeout') {
						alert('<?php echo __($timeout_message, 'sitepress');?>');
					} else {
						alert(parsed_response);
					}
					icl_fix_languages.next().fadeOut();
				}
			});
		});

		jQuery('#icl_remove_ghost').click(function () {
            var self = jQuery(this);
            self.attr('disabled', 'disabled');
            self.after(icl_ajxloaderimg);
			jQuery.post(location.href + '&debug_action=ghost_clean&nonce=<?php echo wp_create_nonce('ghost_clean'); ?>', function () {
                self.removeAttr('disabled');
				alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
                self.next().fadeOut();

			});
		});
		// #assign_translation_status_to_duplicates_resp: BEGIN
		var assign_translation_status_to_duplicates_loader = jQuery(icl_ajxloaderimg);
		var assign_translation_status_to_duplicates_cycles = 0;
		var assign_translation_status_to_duplicates_updated = 0;
		var response_element = jQuery('#assign_translation_status_to_duplicates_resp');
		var assign_translation_status_to_duplicates_element = jQuery('#assign_translation_status_to_duplicates');
		assign_translation_status_to_duplicates_element.click(function () {
			assign_translation_status_to_duplicates();
		});

		function assign_translation_status_to_duplicates() {

			if (assign_translation_status_to_duplicates_cycles == 0) {
				assign_translation_status_to_duplicates_element.attr('disabled', 'disabled');
				response_element.text('');
				response_element.show();
				assign_translation_status_to_duplicates_element.after(assign_translation_status_to_duplicates_loader);

			}
			assign_translation_status_to_duplicates_cycles++;

			jQuery.ajax({
				type: 'POST',
				contentType: "application/json; charset=utf-8",
				url: location.href + '&debug_action=assign_translation_status_to_duplicates&nonce=<?php echo wp_create_nonce('assign_translation_status_to_duplicates'); ?>',
				dataType: 'json',
				success: function (msg) {
					assign_translation_status_to_duplicates_updated += msg.updated;
					var response_message;
					if (msg.updated > 0) {
						response_message = assign_translation_status_to_duplicates_updated + ' <?php echo esc_js(_x('translation jobs updated', 'Sets the translation status to DUPLICATE in the icl_translation_status table, for posts that are marked as duplicated', 'sitepress')); ?>';

						if (assign_translation_status_to_duplicates_cycles >= 50) {
							response_message += '. <?php echo esc_js(_x('Partially done.', 'Sets the translation status to DUPLICATE in the icl_translation_status table, for posts that are marked as duplicated','sitepress')) ?>';
							response_message += '. <?php echo esc_js(_x('There might be more content to fix: please repeat the process.', 'Sets the translation status to DUPLICATE in the icl_translation_status table, for posts that are marked as duplicated','sitepress')) ?>';
							response_element.text(response_message);
							alert('<?php echo esc_js(_x('Partially done', 'Sets the translation status to DUPLICATE in the icl_translation_status table, for posts that are marked as duplicated', 'sitepress')) ?>');
							response_element.fadeOut();
							assign_translation_status_to_duplicates_loader.fadeOut(function() {
								assign_translation_status_to_duplicates_element.remove(assign_translation_status_to_duplicates_loader);
							});
							assign_translation_status_to_duplicates_element.removeAttr('disabled');

							//Reset counters
							assign_translation_status_to_duplicates_cycles = 0;
							assign_translation_status_to_duplicates_updated = 0;
						} else {
							response_message += ' ...';
							response_element.text(response_message);
							assign_translation_status_to_duplicates();
						}
					} else {
						response_message = '';
						if (assign_translation_status_to_duplicates_updated != 0) {
							response_message += assign_translation_status_to_duplicates_updated + '.';
						}
						response_message += '<?php echo esc_js(__('Done', 'sitepress')) ?>';
						response_element.text(response_message);

						alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');

						response_element.fadeOut();
						assign_translation_status_to_duplicates_loader.fadeOut(function() {
							assign_translation_status_to_duplicates_element.remove(assign_translation_status_to_duplicates_loader);
						});
						assign_translation_status_to_duplicates_element.removeAttr('disabled');
					}
				},
				error: function (xhr, status, error) {
					var parsed_response = parse_xhr_error(xhr, status, error);
					response_element.text('');
					response_element.html(parsed_response);
					assign_translation_status_to_duplicates_loader.fadeOut(function() {
						assign_translation_status_to_duplicates_element.remove(assign_translation_status_to_duplicates_loader);
					});
					assign_translation_status_to_duplicates_element.attr('disabled', 'disabled');
				}
			});
		}

		jQuery('#icl_add_missing_lang').click(function () {
            var self = jQuery(this);
            self.attr('disabled', 'disabled');
            self.after(icl_ajxloaderimg);
			jQuery.post(location.href + '&debug_action=icl_ts_add_missing_language&nonce=<?php echo wp_create_nonce('icl_ts_add_missing_language'); ?>', function () {
                self.removeAttr('disabled');
				alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
                self.next().fadeOut();

			});
		});

		jQuery('#icl_fix_collation').click(function () {
			jQuery(this).attr('disabled', 'disabled');
			jQuery(this).after(icl_ajxloaderimg);
			jQuery.post(location.href + '&debug_action=icl_fix_collation&nonce=<?php echo wp_create_nonce('icl_fix_collation'); ?>', function () {
				jQuery('#icl_fix_collation').removeAttr('disabled');
				alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
				jQuery('#icl_fix_collation').next().fadeOut();

			});
		});

		jQuery('#icl_fix_terms_count').click(function () {
            var self = jQuery(this);
            self.attr('disabled', 'disabled');
            self.after(icl_ajxloaderimg);
			jQuery.post(location.href + '&debug_action=icl_fix_terms_count&nonce=<?php echo wp_create_nonce('icl_fix_terms_count'); ?>', function () {
                self.removeAttr('disabled');
				alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
                self.next().fadeOut();

			});
		});

		jQuery('#icl_remove_st_db_cache_logs').click(function () {
			var self = jQuery(this);
			self.attr('disabled', 'disabled');
			self.after(icl_ajxloaderimg);
			jQuery.post(location.href + '&debug_action=icl_remove_st_db_cache_logs&nonce=<?php echo wp_create_nonce('icl_remove_st_db_cache_logs'); ?>', function () {
				self.removeAttr('disabled');
				alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
				self.next().fadeOut();

			});
		});
	})
</script>
<div class="icl_cyan_box">
	<h3><?php _e( 'Advanced settings', 'sitepress' ) ?></h3>

	<p id="otgs_twig_cache_enable">
	  <?php
    $twig_cache_disabled  = (bool) get_option( $otgs_twig_cache_disable_key, false );
	  $twig_cache_new_value = ! $twig_cache_disabled;
	  $twig_cache_label     = __( 'Disable cache for language switcher templates' );
	  if ( $twig_cache_disabled ) {
		  $twig_cache_label = __( 'Enable cache for language switcher templates' );
	  }
	  ?>
		<input type="hidden" id="otgs_twig_cache_new_value" class="otgs_twig_cache_new_value" value="<?php echo $twig_cache_new_value; ?>"/>
		<input type="button" class="button-secondary" value="<?php echo $twig_cache_label; ?>"/><br/>
	</p>

</div><br clear="all"/>

<div class="icl_cyan_box">
	<h3><?php _e( 'Clean up', 'sitepress' ) ?></h3>

	<p class="icl_form_errors" style="padding:6px;"><?php _e( 'Please make backup of your database before using this.', 'sitepress' ) ?></p>

	<?php if ( !SitePress_Setup::languages_complete() ){ ?>
		<p>
            <br />
            <label><input type="checkbox" onchange="if(jQuery(this).prop('checked')){jQuery('#icl_fix_languages').prop('disabled', false);}else{jQuery('#icl_fix_languages').prop('disabled', true);}">
                &nbsp;<?php _e("This operation will reset WPML's language tables and reinstall it. Any custom languages that you added will be removed.", 'sitepress') ?></label><br /><br />
			<input disabled="disabled" id="icl_fix_languages" type="button" class="button-secondary" value="<?php _e( 'Clear language information and repopulate languages', 'sitepress' ) ?>"/><br/><br />
			<small style="margin-left:10px;"><?php _e( "This operation will remove WPML's language table and recreate it. You should use it if you just installed WPML and you're not seeing a complete list of available languages.", 'sitepress' ) ?></small>
            <br /><br />
		</p>
	<?php } ?>

	<?php if(SitePress_Setup::setup_complete()) { ?>
		<?php do_action('wpml_troubleshooting_after_setup_complete_cleanup_begin'); ?>
		<?php do_action('before_setup_complete_troubleshooting_functions'); ?>
	<p>
		<input id="icl_cache_clear" type="button" class="button-secondary" value="<?php _e( 'Clear the cache in WPML', 'sitepress' ) ?>"/><br/>
		<small style="margin-left:10px;"><?php _e( 'This may solve issues like missing languages in the language switcher.', 'sitepress' ) ?></small>
	</p>
	<p>
		<input id="icl_remove_ghost" type="button" class="button-secondary" value="<?php _e( 'Remove ghost entries from the translation tables', 'sitepress' ) ?>"/><br/>
		<small style="margin-left:10px;"><?php _e( 'Removes entries from the WPML tables that are not linked properly. Cleans the table off entries left over upgrades, bug fixes or undetermined factors.', 'sitepress' ) ?></small>
	</p>
	<p>
		<input id="icl_fix_collation" type="button" class="button-secondary" value="<?php _e( 'Fix element_type collation', 'sitepress' ) ?>"/><br/>
		<small style="margin-left:10px;"><?php _e( 'Fixes the collation of the element_type column in icl_translations in case this setting changed for your posts.post_type column.', 'sitepress' ) ?></small>
	</p>

	<?php if(class_exists('TranslationManagement')){ ?>
	<p>
		<input id="assign_translation_status_to_duplicates" type="button" class="button-secondary" value="<?php _e( 'Assign translation status to duplicated content', 'sitepress' ) ?>"/><span id="assign_translation_status_to_duplicates_resp"></span><br/>
		<small style="margin-left:10px;"><?php _e( 'Sets the translation status to DUPLICATE in the icl_translation_status table, for posts that are marked as duplicated.', 'sitepress' ) ?> </small>
	</p>
	<?php } ?>
	<p>
		<input id="icl_add_missing_lang" type="button" class="button-secondary" value="<?php _e( 'Set language information', 'sitepress' ) ?>"/><br/>
		<small style="margin-left:10px;"><?php _e( 'Adds language information to posts and taxonomies that are missing this information.', 'sitepress' ) ?></small>
	</p>
	<p>
		<input id="icl_fix_terms_count" type="button" class="button-secondary" value="<?php _e( 'Fix terms count', 'sitepress' ) ?>"/><br/>
		<small style="margin-left:10px;"><?php _e( 'Correct terms count in case something went wrong with translated contents.', 'sitepress' ) ?></small>
	</p>
	<p>
		<input id="icl_fix_post_types" type="button" class="button-secondary" value="<?php _e( 'Fix post type assignment for translations', 'sitepress' ) ?>"/><br/>
		<small style="margin-left:10px;"><?php _e( 'Correct post type assignment for translations of custom post types in case something went wrong.', 'sitepress' ) ?></small>
	</p>

	<?php if( get_option( 'wpml-st-persist-errors' ) ) { ?>
	<p>
		<input id="icl_remove_st_db_cache_logs" type="button" class="button-secondary" value="<?php _e( 'Remove debug logs of String Translation cache', 'sitepress' ) ?>"/><br/>
		<small style="margin-left:10px;"><?php _e( 'Removes invalid rows stored in option and hide admin notice.', 'sitepress' ) ?></small>
	</p>
	<?php } ?>

	<p>
		<br/>
		<?php _e( 'Translatable custom posts linking', 'sitepress' ); ?><br/>
		<small style="margin-left:10px;"><?php _e( 'Allows linking existing translations after changing custom posts definition (name) ', 'sitepress' ) ?></small>

		<?php
		$translatable_posts = $sitepress->get_translatable_documents();
		$res = $wpdb->get_col(
						$wpdb->prepare("SELECT DISTINCT element_type FROM {$wpdb->prefix}icl_translations WHERE element_type LIKE %s",
										array( wpml_like_escape('post_') . '%' ) ) );
		echo '<table class="widefat" style="width:300px;">';

		foreach ( $res as $row ) {

			$post_type = preg_replace( '#^post_#', '', $row );
			if ( $post_type == 'nav_menu_item' )
				continue;

			echo '<tr>';
			echo '<td>' . $post_type . '</td>';

			if ( isset( $translatable_posts[ $post_type ] ) ) {

				echo '<td>' . __( 'linked to: ', 'sitepress' ) . $translatable_posts[ $post_type ]->labels->name . '</td>';

			} else {
				echo '<td>';
				echo '<select onchange="wpml_ts_link_post_type(jQuery(this), \'' . $post_type . '\')">';
				echo '<option value="">' . __( '--select--', 'sitepress' ) . '</option>';
				foreach ( $translatable_posts as $name => $type ) {
					echo '<option value="' . $name . '">' . $type->labels->name . '(' . $name . ')' . '</option>';
				}
				echo '</select>';
				echo '</td>';
			}
			echo '</tr>';
		}

		echo '</table>';
		echo '<br />';
		echo __( 'Note: if you edited the custom post declaration you may need to re-configure WPML to mark it as translatable.', 'sitepress' );

		?>
	</p>

	<p>
		<br/>
		<?php _e( 'Translatable taxonomies linking', 'sitepress' ) ?><br/>
		<small style="margin-left:10px;"><?php _e( 'Allows linking existing translations after changing custom taxonomies definition (name) ', 'sitepress' ) ?></small>

		<?php
        global $wp_post_types, $wp_taxonomies;
		$translatable_taxs = array();
		foreach ( $wp_post_types as $name => $post_type ) {
			$translatable_taxs = array_merge( $translatable_taxs, $sitepress->get_translatable_taxonomies( true, $name ) );
		}
		$translatable_taxs = array_unique( $translatable_taxs );

		$res = $wpdb->get_col(
						$wpdb->prepare("SELECT DISTINCT element_type FROM {$wpdb->prefix}icl_translations WHERE element_type LIKE %s",
										array( wpml_like_escape('tax_') . '%' ) ) );
		echo '<table class="widefat" style="width:300px;">';

		foreach ( $res as $row ) {

			$tax = preg_replace( '#^tax_#', '', $row );
			if ( $tax == 'link_category' || $tax == 'nav_menu' )
				continue;

			echo '<tr>';

			echo '<td>' . $tax . '</td>';

			if ( in_array( $tax, $translatable_taxs ) ) {

				echo '<td>' . __( 'linked to: ', 'sitepress' ) . $wp_taxonomies[ $tax ]->labels->name . '</td>';

			} else {
				echo '<td>';
				echo '<select onchange="wpml_ts_link_taxonomy(jQuery(this), \'' . $tax . '\')">';
				echo '<option value="">' . __( '--select--', 'sitepress' ) . '</option>';
				foreach ( $translatable_taxs as $name ) {

					echo '<option value="' . $name . '">' . $wp_taxonomies[ $name ]->labels->name . '(' . $name . ')' . '</option>';
				}
				echo '</select>';
				echo '</td>';
			}
			echo '</tr>';
		}

		echo '</table>';
		echo '<br />';
		echo __( 'Note: if you edited the custom taxonomy declaration you may need to re-configure WPML to mark it as translatable.', 'sitepress' );

		?>
	</p>

	<?php do_action('wpml_troubleshooting_after_setup_complete_cleanup_end'); ?>
	<?php do_action('after_setup_complete_troubleshooting_functions'); ?>

	<?php } ?>

</div>

<br clear="all"/>
<?php
//TODO: [WPML 3.3] we should use the new hooks to add elements to the troubleshooting page
echo WPML_Troubleshooting_Terms_Menu::display_terms_with_suffix();
?>

<br clear="all"/>

	<div class="icl_cyan_box">

		<?php
		echo '<h3 id="wpml-settings"> ' . __( 'Reset', 'sitepress' ) . '</h3>';
		?>

		<?php if ( function_exists( 'is_multisite' ) && is_multisite() ) { ?>

			<p><?php _e( 'This function is available through the Network Admin section.', 'sitepress' ); ?></p>
			<?php if ( current_user_can( 'manage_sites' ) ) { ?>
				<a href="<?php echo esc_url(
					network_admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/network.php' )
				) ?>"><?php _e( 'Go to WPML Network settings.', 'sitepress' ) ?></a>
				<p><small><?php _e( 'Note: WPML must be activated on the primary site or must be network activated.', 'sitepress' ); ?></small></p>
			<?php } else { ?>
				<i><?php _e( 'You are not allowed to manage the WPML Network settings.', 'sitepress' ) ?></i>
			<?php } ?>

		<?php } else { ?>


			<?php
			echo '<form method="post" onsubmit="return confirm(\'' . __(
					'Are you sure you want to reset all translation and language data? This operation cannot be reversed!',
					'sitepress'
				) . '\')">';
			wp_nonce_field( 'icl_reset_all', 'icl_reset_allnonce' );
			echo '<p class="error" style="padding:6px;">';
			_e(	"The 'Reset' action will deactivate the WPML plugin after it deletes the WPML tables (tables with the 'icl_' prefix) from the database.
			The action will NOT delete any content (posts, taxonomy terms etc.).
			It only affects translation and language information that WPML associates with each content type.", 'sitepress' );
			echo '</p>';
			echo '<p class="error" style="padding:6px;">';
			_e(	"Please note that all translations you have sent to remote translation services will be lost if you reset WPML's data. They cannot be recovered later.", 'sitepress' );
			echo '</p>';
			echo '<label><input type="checkbox" name="icl-reset-all" ';
			if ( !function_exists( 'is_super_admin' ) || is_super_admin() ) {
				echo 'onchange="if(this.checked) jQuery(\'#reset-all-but\').removeAttr(\'disabled\'); else  jQuery(\'#reset-all-but\').attr(\'disabled\',\'disabled\');"';
			}
			echo ' /> ' . __( 'I am about to reset all translation and language data.', 'sitepress' ) . '</label><br /><br />';

			echo '<input id="reset-all-but" type="submit" disabled="disabled" class="button-primary" value="' . __(
					'Reset and deactivate WPML',
					'sitepress'
				) . '" />';
			echo '</form>';
			?>

		<?php } ?>

	</div>

<?php do_action( 'icl_menu_footer' ); ?>
</div>
