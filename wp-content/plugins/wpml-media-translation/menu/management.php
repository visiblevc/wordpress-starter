<?php
// @use WPML_Media::menu_content
?>
<script type="text/javascript">
	var wpml_media_ajxloaderimg_src = '<?php echo WPML_MEDIA_URL ?>/res/img/ajax-loader.gif';
	var wpml_media_ajxloaderimg = '<img src="' + wpml_media_ajxloaderimg_src + '" alt="loading" width="16" height="16" />';
</script>

<div class="wrap">

	<h2><?php echo __('Media translation', 'wpml-media') ?></h2>

	<?php if ($orphan_attachments): ?>

		<p><?php _e("The Media Translation plugin needs to add languages to your site's media. Without this language information, existing media files will not be displayed in the WordPress admin.", 'wpml-media') ?></p>

	<?php else: ?>

		<p><?php _e('You can check if some attachments can be duplicated to translated content:', 'wpml-media') ?></p>

	<?php endif ?>

	<form id="wpml_media_options_form">
		<input type="hidden" name="no_lang_attachments" value="<?php echo $orphan_attachments ?>"/>
		<input type="hidden" id="wpml_media_options_action"/>
		<table>

			<tr>
				<td colspan="2">
					<ul class="wpml_media_options_language">
						<li><label><input type="checkbox" id="set_language_info" name="set_language_info" value="1" <?php if (!empty($orphan_attachments)): ?>checked="checked"<?php endif; ?>
										  <?php if (empty($orphan_attachments)): ?>disabled="disabled"<?php endif ?> />&nbsp;<?php _e('Set language information for existing media', 'wpml-media') ?></label></li>
						<li><label><input type="checkbox" id="translate_media" name="translate_media" value="1" checked="checked"/>&nbsp;<?php _e('Translate existing media in all languages', 'wpml-media') ?></label></li>
						<li><label><input type="checkbox" id="duplicate_media" name="duplicate_media" value="1" checked="checked"/>&nbsp;<?php _e('Duplicate existing media for translated content', 'wpml-media') ?></label></li>
						<li><label><input type="checkbox" id="duplicate_featured" name="duplicate_featured" value="1" checked="checked"/>&nbsp;<?php _e('Duplicate the featured images for translated content', 'wpml-media') ?></label></li>
					</ul>
				</td>
			</tr>

			<tr>
				<td><a href="https://wpml.org/documentation/getting-started-guide/media-translation/" target="_blank"><?php _e('Media Translation Documentation', 'wpml-media') ?></a></td>
				<td align="right">
					<input class="button-primary" name="start" type="submit" value="<?php esc_attr_e('Start', 'wpml-media'); ?> &raquo;"/>
				</td>

			</tr>

			<tr>
				<td colspan="2">
					<img class="progress" src="<?php echo WPML_MEDIA_URL ?>/res/img/ajax-loader.gif" width="16" height="16" alt="loading" style="display: none;"/>
					&nbsp;<span class="status"></span>
				</td>
			</tr>


			<tr>
				<td colspan="2">
					<h3><?php _e('How to handle media for new content:', 'wpml-media'); ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<ul class="wpml_media_options_language">
						<?php
						$content_defaults = self::get_setting('new_content_settings');

						$always_translate_media_html_checked = $content_defaults['always_translate_media'] ? 'checked="checked"' : '';
						$duplicate_media_html_checked = $content_defaults['duplicate_media'] ? 'checked="checked"' : '';
						$duplicate_featured_html_checked = $content_defaults['duplicate_featured'] ? 'checked="checked"' : '';
						?>
						<li>
							<label><input type="checkbox" name="content_default_always_translate_media"
										  value="1" <?php echo $always_translate_media_html_checked; ?> />&nbsp;<?php _e('When uploading media to the Media library, make it available in all languages', 'wpml-media') ?></label>
						</li>
						<li>
							<label><input type="checkbox" name="content_default_duplicate_media"
										  value="1" <?php echo $duplicate_media_html_checked; ?> />&nbsp;<?php _e('Duplicate media attachments for translations', 'wpml-media') ?></label>
						</li>
						<li>
							<label><input type="checkbox" name="content_default_duplicate_featured"
										  value="1"  <?php echo $duplicate_featured_html_checked; ?> />&nbsp;<?php _e('Duplicate featured images for translations', 'wpml-media') ?></label>
						</li>
					</ul>
				</td>
			</tr>

			<tr>
				<td colspan="2" align="right">
					<input class="button-secondary" name="set_defaults" type="submit" value="<?php esc_attr_e('Apply', 'wpml-media'); ?>"/>
				</td>
			</tr>

			<tr>
				<td colspan="2">
					<img class="content_default_progress" src="<?php echo WPML_MEDIA_URL ?>/res/img/ajax-loader.gif" width="16" height="16" alt="loading" style="display: none;"/>
					&nbsp;<span class="content_default_status"></span>
				</td>
			</tr>

		</table>

		<div id="wpml_media_all_done" class="hidden updated">
			<p><?php _e("You're all done. Now that the Media Translation plugin is running, all new media files that you upload to content will receive a language. You can automatically duplicate them to translations from the post-edit screen.", 'wpml-media') ?></p>
		</div>

	</form>


</div>