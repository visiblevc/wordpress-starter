<?php
require dirname( __FILE__ ) . '/wpml-menu-sync-display.class.php';

/** @var $sitepress SitePress */
/** @var $icl_menus_sync ICLMenusSync */
$active_languages = $sitepress->get_active_languages();
$def_lang_code = $sitepress->get_default_language();
$def_lang = $sitepress->get_language_details( $def_lang_code );
$secondary_languages = array();

foreach ( $active_languages as $code => $lang ) {
	if ( $code !== $def_lang_code ) {
		$secondary_languages[ ] = $lang;
	}
}
?>
<!--suppress HtmlFormInputWithoutLabel --><!--suppress HtmlUnknownAttribute -->
<div class="wrap">
<h2><?php echo __( 'WP Menus Sync', 'sitepress' ) ?></h2>
<p><?php echo sprintf( __( 'Menu synchronization will sync the menu structure from the default language of %s to the secondary languages.', 'sitepress' ), $def_lang[ 'display_name' ] ); ?></p>

<br/>
<?php
if ( $icl_menus_sync->is_preview ) {
	?>

	<form id="icl_msync_confirm_form" method="post">
	<input type="hidden" name="action" value="icl_msync_confirm"/>

	<table id="icl_msync_confirm" class="widefat icl_msync">
	<thead>
	<tr>
		<th scope="row" class="menu-check-all"><input type="checkbox"/></th>
		<th><?php _e( 'Language', 'sitepress' ) ?></th>
		<th><?php _e( 'Action', 'sitepress' ) ?></th>
	</tr>
	</thead>
	<tbody>

	<?php
	if ( empty( $icl_menus_sync->sync_data ) ) {
		?>
		<tr>
			<td align="center" colspan="3"><?php _e( 'Nothing to sync.', 'sitepress' ) ?></td>
		</tr>
	<?php
	} else {
		//Menus
		foreach ( $icl_menus_sync->menus as $menu_id => $menu ) {
            $menu_sync_display = new WPML_Menu_Sync_Display( $menu_id, $icl_menus_sync );
            ?>
            <tr class="icl_msync_menu_title">
                <td colspan="3"><?php echo $menu[ 'name' ] ?></td>
            </tr>

            <?php
            // Display actions per menu
            // menu translations
            if ( isset( $icl_menus_sync->sync_data[ 'menu_translations' ] ) && isset( $icl_menus_sync->sync_data[ 'menu_translations' ][ $menu_id ] ) ) {
                foreach ( $icl_menus_sync->sync_data[ 'menu_translations' ][ $menu_id ] as $language => $name ) {
                    $lang_details = $sitepress->get_language_details( $language );
                    ?>
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="sync[menu_translation][<?php echo $menu_id ?>][<?php echo $language ?>]" value="<?php echo esc_attr( $name ) ?>"/></th>
                        <td><?php echo $lang_details[ 'display_name' ]; ?></td>
                        <td><?php printf( __( 'Add menu translation:  %s', 'sitepress' ), '<strong>' . $name . '</strong>' ); ?> </td>
                    </tr>
                <?php
                }
            }

            foreach (
                array(
                    'add',
                    'mov',
                    'del',
                    'label_changed',
                    'url_changed',
                    'label_missing',
                    'url_missing',
                    'options_changed'
                ) as $sync_type
            ) {
                $menu_sync_display->print_sync_field ( $sync_type );
            }
		}
	}
	?>
	</tbody>
	</table>
	<p class="submit">
		<?php
		$icl_menu_sync_submit_disabled = '';
		if ( empty( $icl_menus_sync->sync_data ) || ( empty( $icl_menus_sync->sync_data[ 'mov' ] ) && empty( $icl_menus_sync->sync_data[ 'mov' ][ $menu_id ] ) ) ) {
			$icl_menu_sync_submit_disabled = 'disabled="disabled"';
		}
		?>
		<input id="icl_msync_submit"
			   class="button-primary"
			   type="button"
			   value="<?php _e( 'Apply changes' ) ?>"
			   data-message="<?php _e( 'Syncing menus %1 of %2', 'sitepress' ) ?>"
			   data-message-complete="<?php _e( 'The selected menus have been synchonized.', 'sitepress' ) ?>"
			   <?php echo $icl_menu_sync_submit_disabled; ?> />&nbsp;
		<input id="icl_msync_cancel" class="button-secondary" type="button" value="<?php _e( 'Cancel' ) ?>"/>
		<span id="icl_msync_message"></span>
	</p>
        <?php wp_nonce_field( '_icl_nonce_menu_sync', '_icl_nonce_menu_sync' ); ?>
	</form>
<?php
} else {
	$need_sync = 0;
	?>
	<form method="post" action="">
		<input type="hidden" name="action" value="icl_msync_preview"/>
		<table class="widefat icl_msync">
			<thead>
			<tr>
				<th><?php echo $def_lang[ 'display_name' ]; ?></th>
				<?php
				if ( ! empty( $secondary_languages ) ) {
					foreach ( $secondary_languages as $lang ) {
						?>
						<th><?php echo $lang[ 'display_name' ]; ?></th>
					<?php
					}
				}
				?>
			</tr>
			</thead>
			<tbody>
			<?php
			if ( empty( $icl_menus_sync->menus ) ) {
				?>
				<tr>
					<td align="center" colspan="<?php echo count( $active_languages ) ?>"><?php _e( 'No menus found', 'sitepress' ) ?></td>
				</tr>
			<?php
			} else {
				foreach ( $icl_menus_sync->menus as $menu_id => $menu ) {
					?>

					<tr class="icl_msync_menu_title">
						<td><strong><?php echo $menu[ 'name' ]; ?></strong></td>
						<?php
						foreach ( $secondary_languages as $l ) {
							?>
							<td>
								<?php
								if ( isset( $menu[ 'translations' ][ $l[ 'code' ] ][ 'name' ] ) ) {
									echo $menu[ 'translations' ][ $l[ 'code' ] ][ 'name' ];
								} else { // menu is translated in $l[code]
									$need_sync++;
									?>
									<input type="text" name="sync[menu_translations][<?php echo $menu_id ?>][<?php echo $l[ 'code' ] ?>]" class="icl_msync_add" value="<?php
									echo esc_attr( $menu[ 'name' ] ) . ' - ' . $l[ 'display_name' ] ?>"/>
									<small><?php _e( 'Auto-generated title. Click to edit.', 'sitepress' ) ?></small>
									<input type="hidden" name="sync[menu_options][<?php echo $menu_id ?>][<?php echo $l[ 'code' ] ?>][auto_add]"
																				value=""/>
								<?php
								}
								if ( isset( $menu[ 'translations' ][ $l[ 'code' ] ][ 'auto_add' ] ) ) {
									?>
									<input type="hidden" name="sync[menu_options][<?php echo $menu_id ?>][<?php echo $l[ 'code' ] ?>][auto_add]" value="<?php echo esc_attr( $menu[ 'translations' ][ $l[ 'code' ] ][ 'auto_add' ] ); ?>"/>
								<?php
								}
								?>
							</td>
						<?php
						} //foreach($secondary_languages as $l):
						?>
					</tr>
					<?php
					$need_sync += $icl_menus_sync->render_items_tree_default( $menu_id );

				} //foreach( $icl_menus_sync->menus as  $menu_id => $menu):
			}
			?>
			</tbody>
		</table>
		<p class="submit">
			<?php
			if ( $need_sync ) {
				?>
				<input id="icl_msync_sync" type="submit" class="button-primary" value="<?php _e( 'Sync', 'sitepress' ); ?>"<?php if ( !$need_sync ): ?> disabled="disabled"<?php endif; ?> />
				&nbsp;&nbsp;
				<span id="icl_msync_max_input_vars"
					  style="display:none"
					  class="icl-admin-message-warning"
					  data-max_input_vars="<?php echo ini_get( 'max_input_vars' ); ?>">
					<?php _e( 'The menus on this page may not sync because it requires more input variables. Please modify the <strong>max_input_vars</strong> setting in your php.ini or .htaccess files to <strong>!NUM!</strong> or more.', 'sitepress'); ?>
				</span>
			<?php
			} else {
				?>
				<input id="icl_msync_sync" type="submit" class="button-primary" value="<?php _e( 'Nothing Sync', 'sitepress' ); ?>"<?php if ( !$need_sync ): ?> disabled="disabled"<?php endif; ?> />
			<?php
			}
			?>
		</p>
        <?php wp_nonce_field( '_icl_nonce_menu_sync', '_icl_nonce_menu_sync' ); ?>
	</form>

	<?php
	if ( !empty( $icl_menus_sync->operations ) ) {
		$show_string_translation_link = false;
		foreach ( $icl_menus_sync->operations as $op => $c ) {
			if ( $op == 'add' ) {
				?>
				<span class="icl_msync_item icl_msync_add"><?php _e( 'Item will be added', 'sitepress' ); ?></span>
			<?php
			} elseif ( $op == 'del' ) {
				?>
				<span class="icl_msync_item icl_msync_del"><?php _e( 'Item will be removed', 'sitepress' ); ?></span>
			<?php
			} elseif ( $op == 'not' ) {
				?>
				<span class="icl_msync_item icl_msync_not"><?php _e( 'Item cannot be added (parent not translated)', 'sitepress' ); ?></span>
			<?php
			} elseif ( $op == 'mov' ) {
				?>
				<span class="icl_msync_item icl_msync_mov"><?php _e( 'Item changed position', 'sitepress' ); ?></span>
			<?php
			} elseif ( $op == 'copy' ) {
				?>
				<span class="icl_msync_item icl_msync_copy"><?php _e( 'Item will be copied', 'sitepress' ); ?></span>
			<?php
			} elseif ( $op == 'label_changed' ) {
				?>
				<span class="icl_msync_item icl_msync_label_changed"><?php _e( 'Strings for menus will be updated', 'sitepress' ); ?></span>
			<?php
			} elseif ( $op == 'url_changed' ) {
				?>
				<span class="icl_msync_item icl_msync_url_changed"><?php _e( 'URLs for menus will be updated', 'sitepress' ); ?></span>
			<?php
			} elseif ( $op == 'options_changed' ) {
                ?>
                <span class="icl_msync_item icl_msync_options_changed"><?php _e( 'Menu Options will be updated', 'sitepress' ); ?></span>
            <?php
			} elseif ( $op == 'label_missing' ) {
				?>
				<span class="icl_msync_item icl_msync_label_missing">
					<?php _e( 'Untranslated strings for menus', 'sitepress' ); ?>
				</span>
			<?php
			} elseif ( $op == 'url_missing' ) {
				?>
				<span class="icl_msync_item icl_msync_url_missing">
					<?php _e( 'Untranslated URLs for menus', 'sitepress' ); ?>
				</span>
			<?php
			}
		}
	}

	$icl_menus_sync->display_menu_links_to_string_translation();
}
do_action( 'icl_menu_footer' );
?>
</div>
