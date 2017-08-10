<?php

function fix_removed_contexts_from_3_2_upgrade( ) {
	
	// Contexts were renamed wrongly in the 3.2 upgrade.
	// This is a temporary patch to change them back.
	
	global $wpdb;
	
	$contexts = $wpdb->get_col( "SELECT DISTINCT context
							  FROM {$wpdb->prefix}icl_strings" );
	
	foreach ( $contexts as $context ) {
		
		if ( strpos( $context, ' (removed)') > 0 ) {
			$fix_context = 'theme ' . str_replace( ' (removed)', '', $context );
			
			$wpdb->query( $wpdb->prepare( "UPDATE
										 {$wpdb->prefix}icl_strings
										 SET context = %s
										 WHERE context = %s",
										 $fix_context,
										 $context
										 ) );
		}
		
	}
}

fix_removed_contexts_from_3_2_upgrade( );

function show_automatic_text_domain_checkbox( $for ) {
	global $sitepress_settings;
	
	?>
	<div class="wpml_st_theme_localization_type_wpml_extra">
		<?php $use_header_text_domains_when_missing_checked = checked( true, ! empty( $sitepress_settings[ 'st' ][ 'use_header_text_domains_when_missing' ] ), false ); ?>
		<input type="checkbox" id="wpml_st_theme_localization_type_<?php echo $for; ?>" name="wpml_st_theme_localization_type_wpml_td" value="1" <?php echo $use_header_text_domains_when_missing_checked; ?>/>
		<label for="wpml_st_theme_localization_type_<?php echo $for; ?>">
			<?php _e( 'Automatically use theme or plugin text domains when gettext calls do not use a string literal.', 'wpml-string-translation' ) ?>
		</label>
		<?php
		$doing_it_wrong_url = 'http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/';
		$doing_it_wrong = '<a href="' . $doing_it_wrong_url .'" target="_blank">';
		$doing_it_wrong .= __('Internationalization: You’re probably doing it wrong', 'wpml-string-translation');
		$doing_it_wrong .= '</a>';
		?>
		<p class="description">
			<?php echo sprintf(__( 'Note: It is not safe to use variables, constants or functions in gettext calls. Read "%s" for a detailed explanation.', 'wpml-string-translation' ), $doing_it_wrong); ?>
			<br />
			<?php _e( 'If your theme or plugins falls in this case, enabling this option, WPML will try to retrieve this from the plugin or theme "Text Domain" header, if set.', 'wpml-string-translation' ); ?>
		</p>
	</div>
	<?php
}

$local = new WPML_Localization();

$theme_localization_stats         = $local->get_theme_localization_stats();
$theme_requires_rescan            = $local->does_theme_require_rescan();
$plugin_localization_stats        = $local->get_plugin_localization_stats();
$plugin_wrong_localization_stats  = $local->get_wrong_plugin_localization_stats();
/** @var array $theme_localization_domains */
$theme_localization_domains = icl_get_sub_setting( 'st', 'theme_localization_domains' );
?>

<h3><?php _e( 'Strings in the theme', 'wpml-string-translation' ); ?></h3>

<div class="updated fade">
	<p>
		<i><?php _e( 'Re-scanning the plugins or the themes will reset the strings tracked in the code or the HTML source',
		             'wpml-string-translation' ) ?></i></p>
</div>

<div id="icl_strings_in_theme_wrap">

	<?php if ( $theme_localization_stats ): ?>
		<p><?php _e( 'The following strings were found in your theme.', 'wpml-string-translation' ); ?></p>
		<table id="icl_strings_in_theme" class="widefat" cellspacing="0">
			<thead>
			<tr>
				<th scope="col"><?php echo __( 'Domain', 'wpml-string-translation' ) ?></th>
				<th scope="col"><?php echo __( 'Translation status', 'wpml-string-translation' ) ?></th>
				<th scope="col" style="text-align:right"><?php echo __( 'Count', 'wpml-string-translation' ) ?></th>
				<th scope="col">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $theme_localization_domains as $tl_domain ): ?>
				<?php
				if ( empty( $tl_domain ) ) {
					continue;
				}
				$theme_localization_stats_data = isset( $theme_localization_stats[ $tl_domain ? $tl_domain : 'theme' ] ) ? $theme_localization_stats[ $tl_domain ? $tl_domain : 'theme' ] : false;
				if ( $theme_localization_stats_data ) {
					$_tmpcomp = $theme_localization_stats_data[ 'complete' ];
					$_tmpinco = $theme_localization_stats_data[ 'incomplete' ];
				} else {
					$_tmpcomp = $_tmpinco = $_tmptotal = __( 'n/a', 'wpml-string-translation' );
				}
				
				?>
				<tr<?php if ( $theme_requires_rescan ) { echo ' class="st_requires_update"'; } ?>>
					<td><?php echo $tl_domain ? $tl_domain : '<i>' . __( 'no domain',
					                                                                 'wpml-string-translation' ) . '</i>'; ?></td>
					<td><?php echo __( 'Fully translated', 'wpml-string-translation' ) ?></td>
					<td align="right"><?php echo $_tmpcomp; ?></td>
					<td rowspan="3" align="right" style="padding-top:10px;">
						<a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;context=<?php echo $tl_domain ? urlencode( $tl_domain ) : 'WordPress' ?>"
						   class="button-secondary"><?php echo __( "View all the theme's texts",
						                                           'wpml-string-translation' ) ?></a>
						<?php if ( $_tmpinco ): ?>
							<a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;context=<?php echo $tl_domain ? urlencode( $tl_domain ) : 'WordPress' ?>&amp;status=0"
							   class="button-primary"><?php echo __( "View strings that need translation",
							                                         'wpml-string-translation' ) ?></a>
						<?php endif; ?>
					</td>
				</tr>
				<tr<?php if ( $theme_requires_rescan ) { echo ' class="st_requires_update"'; } ?>>
					<td></td>
					<td><?php echo __( 'Not translated or needs update', 'wpml-string-translation' ) ?></td>
					<td align="right"><?php echo $_tmpinco ?></td>
				</tr>
				<tr<?php echo $theme_requires_rescan ? ' class="st_requires_update"' : ' style="background-color:#f9f9f9;"'; ?>>
					<td></td>
					<td><strong><?php echo __( 'Total', 'wpml-string-translation' ) ?></strong></td>
					<td align="right"><strong><?php echo $_tmpcomp + $_tmpinco;
							if ( 1 < count( $theme_localization_domains ) ) {
								if ( ! isset( $_tmpgt ) ) {
									$_tmpgt = 0;
								}
								$_tmpgt += $_tmpcomp + $_tmpinco;
							} ?></strong></td>
				</tr>
			<?php endforeach ?>

			<?php			
				if ( $theme_requires_rescan ) {
					?>
						<tr class="st_requires_update">
							<td colspan="4">
								<p class="update-message"> <?php _e('Update required - please rescan this theme', 'wpml-string-translation'); ?></p>
							</td>
						</tr>
					<?php
				}
			?>
			
			</tbody>
			<?php if ( 1 < count( $theme_localization_domains ) ): ?>
				<tfoot>
				<tr>
					<th scope="col"><?php echo __( 'Total', 'wpml-string-translation' ) ?></th>
					<th scope="col">&nbsp;</th>
					<th scope="col" style="text-align:right"><?php echo $_tmpgt ?></th>
					<th scope="col">&nbsp;</th>
				</tr>
				</tfoot>
			<?php endif; ?>
		</table>
	<?php else: ?>
		<p><?php echo __( "To translate your theme's texts, click on the button below. WPML will scan your theme for texts and let you enter translations.",
		                  'wpml-string-translation' ) ?></p>
	<?php endif; ?>

</div>

<p>
	<input type="checkbox" id="icl_load_mo_themes" value="1" checked="checked"/>
	<label for="icl_load_mo_themes">
		<?php _e( 'Load translations if found in the .mo files. (it will not override existing translations)', 'wpml-string-translation' ) ?>
	</label>
</p>

<?php show_automatic_text_domain_checkbox( 'theme' ); ?>

<p>
	<input id="st_theme_localization_rescan" type="button" class="button-primary"
	       value="<?php echo __( "Scan the theme for strings", 'wpml-string-translation' ) ?>"/>
	<img class="icl_ajx_loader" src="<?php echo WPML_ST_URL ?>/res/img/ajax-loader.gif" style="display:none;" alt=""/>
</p>
<div id="icl_tl_scan_stats"></div>

<br/>

<h3><?php _e( 'Strings in the plugins', 'wpml-string-translation' ); ?></h3>
<?php
$plugins        = get_plugins();
$active_plugins = get_option( 'active_plugins' );
$mu_plugins     = wp_get_mu_plugins();
foreach ( $mu_plugins as $p ) {
	$pfile                     = basename( $p );
	$plugins[ $pfile ]         = array( 'Name' => 'MU :: ' . $pfile );
	$mu_plugins_base[ $pfile ] = true;
}
$wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
?>
<form id="st_plugin_localization_rescan" action="">
	<div id="icl_strings_in_plugins_wrap">
		<table id="icl_strings_in_plugins" class="widefat" cellspacing="0">
			<thead>
			<tr>
				<th scope="col" class="column-cb check-column"><input type="checkbox"/></th>
				<th scope="col"><?php echo __( 'Plugin', 'wpml-string-translation' ) ?></th>
				<th scope="col"><?php echo __( 'Active', 'wpml-string-translation' ) ?></th>
				<th scope="col"><?php echo __( 'Translation status', 'wpml-string-translation' ) ?>
					<div style="float:right"><?php echo __( 'Count', 'wpml-string-translation' ) ?></div>
				</th>
				<th scope="col">&nbsp;</th>
				<th scope="col">&nbsp;</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th scope="col" class="column-cb check-column"><input type="checkbox"/></th>
				<th scope="col"><?php echo __( 'Plugin', 'wpml-string-translation' ) ?></th>
				<th scope="col"><?php echo __( 'Active', 'wpml-string-translation' ) ?></th>
				<th scope="col"><?php echo __( 'Translation status', 'wpml-string-translation' ) ?>
					<div style="float:right"><?php echo __( 'Count', 'wpml-string-translation' ) ?></div>
				</th>
				<th scope="col">&nbsp;</th>
				<th scope="col">&nbsp;</th>
			</tr>
			</tfoot>
			<tbody>
			<?php foreach ( $plugins as $file => $plugin ): ?>
				<?php
				$plugin_id = $file;

				$_tmpcomp = $_tmpinco = $_tmptotal = __( 'n/a', 'wpml-string-translation' );
				$_tmplink = false;
				if ( isset( $plugin_localization_stats[ $plugin_id ] ) ) {
					
					$domain_name = $local->get_most_popular_domain ( $plugin_id );

					if ( isset( $plugin_localization_stats[ $plugin_id ][ $domain_name ] ) ) {
						$_tmpcomp  = $plugin_localization_stats[ $plugin_id ][ $domain_name ][ 'complete' ];
						$_tmpinco  = $plugin_localization_stats[ $plugin_id ][ $domain_name ][ 'incomplete' ];
						$_tmptotal = $_tmpcomp + $_tmpinco;
						$_tmplink  = true;
					}
					//TODO: [WPML 3.2.1] If `isset( $plugin_localization_stats[ $plugin_id ][ $domain_name ] ) === false` we should probably remove the data from `'st' => 'plugin_localization_domains'`
				}
				$is_mu_plugin = false;
				if ( in_array( $file, $active_plugins ) ) {
					$plugin_active_status = __( 'Yes', 'wpml-string-translation' );
				} elseif ( isset( $wpmu_sitewide_plugins[ $file ] ) ) {
					$plugin_active_status = __( 'Network', 'wpml-string-translation' );
				} elseif ( isset( $mu_plugins_base[ $file ] ) ) {
					$plugin_active_status = __( 'MU', 'wpml-string-translation' );
					$is_mu_plugin         = true;
				} else {
					$plugin_active_status = __( 'No', 'wpml-string-translation' );
				}
				
				// check for plugins with wrong/old contexts
				$old_plugin_context = 'plugin ' . dirname( $plugin_id );
				$requires_update = isset( $plugin_wrong_localization_stats[ $old_plugin_context ] );
				if ( $requires_update ) {
					// Unset it so we are then left with the plugins that are no longer installed.
					unset( $plugin_wrong_localization_stats[ $old_plugin_context ] );
				}

				$item_check_box_name      = $is_mu_plugin ? 'mu-plugin[]' : 'plugin[]';
				$item_check_box_id        = ( $is_mu_plugin ? 'mu-plugin-' : 'plugin-' ) . str_replace( '/', '-', $file );

				$checked = '';
				if ( array_key_exists( 'plugin', $_GET ) && $_GET['plugin'] === $plugin['Name'] ) {
					$checked = 'checked="checked"';
				}

				?>
				<tr<?php echo $requires_update ? ' class="st_requires_update"' : ''; ?>>
					<td>
						<input type="checkbox" <?php echo $checked; ?> value="<?php echo $file ?>" id="<?php echo esc_attr( $item_check_box_id ); ?>" name="<?php echo esc_attr( $item_check_box_name ); ?>"/>
					</td>
					<td>
						<label for="<?php echo $item_check_box_id ?>">
							<?php echo $plugin['Name']; ?>
						</label>
						<?php
							if ( $requires_update ) {
								?>
									<p class="update-message"> <?php _e('Update required - please rescan this plugin', 'wpml-string-translation'); ?></p>
								<?php
							}
						?>
					</td>
					<td align="center"><?php echo $plugin_active_status ?></td>
					<td>
						<table width="100%" cellspacing="0">
							<tr>
								<td><?php echo __( 'Fully translated', 'wpml-string-translation' ) ?></td>
								<td align="right"><?php echo $_tmpcomp ?></td>
							</tr>
							<tr>
								<td><?php echo __( 'Not translated or needs update', 'wpml-string-translation' ) ?></td>
								<td align="right"><?php echo $_tmpinco ?></td>
							</tr>
							<tr style="background-color:#f9f9f9;">
								<td style="border:none"><strong><?php echo __( 'Total',
								                                               'wpml-string-translation' ) ?></strong>
								</td>
								<td style="border:none" align="right"><strong><?php echo $_tmptotal; ?></strong></td>
							</tr>
						</table>
					</td>
					<td align="right" style="padding:10px;">
						<?php if ( $_tmplink ): ?>
							<p>
								<a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;context=<?php echo $domain_name ?>"
								   class="button-secondary"><?php echo __( "View all the plugin's texts",
								                                           'wpml-string-translation' ) ?></a></p>
							<?php if ( $_tmpinco ): ?>
								<p>
									<a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;context=<?php echo $domain_name ?>&amp;status=0"
									   class="button-primary"><?php echo __( "View strings that need translation",
									                                         'wpml-string-translation' ) ?></a></p>
							<?php endif; ?>
						<?php endif; ?>
						<a class="wpml_st_pop_download button-secondary"
						   href="#<?php echo urlencode( $file ) ?>"><?php _e( 'create PO file',
						                                                      'wpml-string-translation' ); ?></a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>

	<p>
		<input type="checkbox" id="icl_load_mo" name="icl_load_mo" value="1" checked="checked"/>
		<label for="icl_load_mo">
			<?php _e( 'Load translations if found in the .mo files. (it will not override existing translations)', 'wpml-string-translation' ) ?>
		</label>
	</p>

	<?php show_automatic_text_domain_checkbox( 'plugins' ); ?>

	<p>
		<input type="submit" class="button-primary" value="<?php echo __( "Scan the selected plugins for strings", 'wpml-string-translation' ) ?>"/>
		<img class="icl_ajx_loader_p" src="<?php echo WPML_ST_URL ?>/res/img/ajax-loader.gif" style="display:none;" alt=""/>
	</p>


</form>

<div id="icl_tl_scan_stats_p"></div>

<?php

