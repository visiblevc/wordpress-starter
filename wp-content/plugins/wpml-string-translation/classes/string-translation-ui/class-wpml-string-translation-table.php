<?php

class WPML_String_Translation_Table {
	/** @var array */
	private $active_languages;

	public function __construct( $strings ) {
		global $WPML_String_Translation, $sitepress;
		$this->string_settings = $WPML_String_Translation->get_strings_settings();

		$this->strings = $strings;
		if ( ! empty( $strings ) ) {
			$this->strings_in_page = icl_get_strings_tracked_in_pages( $strings );
		}

		$this->active_languages = $sitepress->get_active_languages();

	}

	public function render() {
		?>
		<table id="icl_string_translations" class="widefat" cellspacing="0">
			<?php
			$this->render_table_header_or_footer( 'thead' );
			$this->render_table_header_or_footer( 'tfoot' );
			?>
			<tbody>
			<?php if ( empty( $this->strings ) ) {
				?>
				<tr>
					<td colspan="6" align="center">
						<?php esc_html_e( 'No strings found', 'wpml-string-translation' ) ?>
					</td>
				</tr>
				<?php
			} else {
				foreach ( $this->strings as $string_id => $icl_string ) {
					$this->render_string_row( $string_id, $icl_string );
				}
			}
			?>
			</tbody>
		</table>

		<?php
	}

	private function render_table_header_or_footer( $tag ) {

		?>
		<<?php echo $tag; ?>>
		<tr>
			<th scope="col" class="manage-column column-cb check-column"><input type="checkbox"/></th>
			<th scope="col"><?php esc_html_e( 'Domain', 'wpml-string-translation' ) ?></th>
			<th scope="col"><?php esc_html_e( 'Context', 'wpml-string-translation' ) ?></th>
			<th scope="col"><?php esc_html_e( 'Name', 'wpml-string-translation' ) ?></th>
			<th scope="col"><?php esc_html_e( 'View', 'wpml-string-translation' ) ?></th>
			<th scope="col"><?php esc_html_e( 'String', 'wpml-string-translation' ) ?></th>
			<th scope="col"><?php esc_html_e( 'Status', 'wpml-string-translation' ) ?></th>
		</tr>
		<<?php echo $tag; ?>>
		<?php
	}

	public function render_string_row( $string_id, $icl_string ) {
		global $wpdb, $sitepress, $WPML_String_Translation;

		if ( isset( $icl_string['string_language'] ) && ! isset( $this->active_languages[ $icl_string['string_language'] ] ) ) {
			$this->active_languages[ $icl_string['string_language'] ] = $sitepress->get_language_details( $icl_string['string_language'] );
		}

		if ( isset( $icl_string['translations'] ) ) {
			foreach ( $icl_string['translations'] as $target_lang_code => $data ) {
				if ( ! isset( $this->active_languages[ $target_lang_code ] ) ) {
					$this->active_languages[ $target_lang_code ] = $sitepress->get_language_details( $target_lang_code );
				}
			}
		}

		?>
		<tr valign="top">
			<?php echo $this->render_checkbox_cell( $icl_string ) ?>
			<td class="wpml-st-col-domain"><?php echo esc_html( $icl_string['context'] ) ?></td>
			<td><?php echo esc_html( $icl_string['gettext_context'] ) ?></td>
			<td class="wpml-st-col-name"><?php echo esc_html( $this->hide_if_md5( $icl_string['name'] ) ); ?></td>
			<td nowrap="nowrap">
				<?php $this->render_view_column( $string_id ) ?>
			</td>
			<td class="wpml-st-col-string">
				<div class="icl-st-original"<?php _icl_string_translation_rtl_div( $this->string_settings['strings_language'] ) ?>>
					<img src="<?php echo esc_url( $sitepress->get_flag_url( $icl_string['string_language'] ) ) ?>"> <?php echo esc_html( $icl_string['value'] ) ?>
				</div>
				<div style="float:right;">
					<a href="#icl-st-toggle-translations"><?php esc_html_e( 'translations', 'wpml-string-translation' ) ?></a>
				</div>
				<br clear="all"/>
				<div class="icl-st-inline">
					<?php foreach ( $this->active_languages as $lang ): if ( $lang['code'] === $icl_string['string_language'] ) {
						continue;
					} ?>

						<?php
						if ( isset( $icl_string['translations'][ $lang['code'] ] ) && ICL_TM_COMPLETE == $icl_string['translations'][ $lang['code'] ]['status'] ) {
							$tr_complete_checked = 'checked="checked"';
						} else {
							if ( icl_st_is_translator() ) {
								$user_lang_pairs = get_user_meta( get_current_user_id(), $wpdb->prefix . 'language_pairs', true );
								if ( empty( $user_lang_pairs[ $this->string_settings['strings_language'] ][ $lang['code'] ] ) ) {
									continue;
								}
							}
							$tr_complete_checked = '';
						}

						list( $form_disabled, $form_disabled_reason ) = $this->get_translation_form_status( $icl_string, $lang );
						?>

						<form class="icl_st_form"
							  name="icl_st_form_<?php echo esc_attr( $lang['code'] . '_' . $string_id ) ?>" action="">
							<?php wp_nonce_field( 'icl_st_save_translation_nonce', '_icl_nonce' ) ?>
							<input type="hidden" name="icl_st_language"
								   value="<?php echo esc_attr( $lang['code'] ) ?>"/>
							<input type="hidden" name="icl_st_string_id" value="<?php echo esc_attr( $string_id ) ?>"/>

							<table class="icl-st-table">
								<tr>
									<td style="border:none">
										<?php echo esc_html( $lang['display_name'] ) ?>
										<br/>
										<img class="icl_ajx_loader"
											 src="<?php echo WPML_ST_URL ?>/res/img/ajax-loader.gif"
											 style="float:left;display:none;position:absolute;margin:5px" alt=""/>
										<?php
										$rows            = ceil( strlen( $icl_string['value'] ) / 80 );
										$temp_line_array = preg_split( '/\n|\r/', $icl_string['value'] );
										$temp_num_lines  = count( $temp_line_array );
										$rows += $temp_num_lines;
										if ( isset( $icl_string['translations'][ $lang['code'] ] ) && null !== $icl_string['translations'][ $lang['code'] ]['value'] ) {
											$string_value = $icl_string['translations'][ $lang['code'] ]['value'];
										} else {
											$string_value = $icl_string['value'];
										}

										?>
										<textarea<?php echo $form_disabled;
										_icl_string_translation_rtl_textarea( $lang['code'] ); ?>
												rows="<?php echo esc_attr( $rows ) ?>" cols="40"
												name="icl_st_translation"
												<?php if ( isset( $icl_string['translations'][ $lang['code'] ] ) ): ?>id="icl_st_ta_<?php echo esc_attr( $icl_string['translations'][ $lang['code'] ]['id'] ) ?>"<?php endif; ?>
										><?php echo esc_html( $string_value ) ?></textarea>
									</td>
								</tr>
								<tr>
									<td align="right" style="border:none">
										<?php

										?>
										<?php if ( isset( $icl_string['translations'][ $lang['code'] ]['value'] ) && preg_match( '#<([^>]*)>#im', $icl_string['translations'][ $lang['code'] ]['value'] ) ): ?>
											<br clear="all"/>
											<div style="text-align:left;display:none" class="icl_html_preview"></div>
											<a href="#" class="alignleft icl_htmlpreview_link">HTML preview</a>
										<?php endif; ?>
										<label>
											<input<?php echo $form_disabled ?> type="checkbox"
																			   name="icl_st_translation_complete"
																			   value="1"
												<?php echo $tr_complete_checked ?>
																			   <?php if ( isset( $icl_string['translations'][ $lang['code'] ] ) ): ?>id="icl_st_cb_<?php echo esc_attr( $icl_string['translations'][ $lang['code'] ]['id'] ) ?>"<?php endif; ?>
											/>
											<?php esc_html_e( 'Translation is complete', 'wpml-string-translation' ) ?>
										</label>&nbsp;
										<input<?php echo $form_disabled ?> type="submit" class="button-secondary action"
																		   value="<?php esc_attr_e( 'Save', 'wpml-string-translation' ) ?>"/>
										<?php if ( $form_disabled_reason ): ?>
											<br clear="all"/>
											<p><?php echo esc_html( $form_disabled_reason ) ?></p>
										<?php endif; ?>
									</td>
								</tr>
							</table>
						</form>

					<?php endforeach; ?>

				</div>
			</td>
			<td nowrap="nowrap" id="icl_st_string_status_<?php echo esc_attr( $string_id ) ?>">
				<span>
				<?php
				echo esc_html( apply_filters( 'wpml_string_status_text', WPML_ST_String_Statuses::get_status( (int) $icl_string['status'] ), $string_id ) );
				?>
				</span>
				<input type="hidden" id="icl_st_wc_<?php echo esc_attr( $string_id ) ?>" value="<?php
				echo $WPML_String_Translation->estimate_word_count( $icl_string['value'], $this->string_settings['strings_language'] ) ?>"/>
			</td>
		</tr>
		<?php
	}

	/**
	 * @param array $string
	 *
	 * @return string html for the checkbox and the table cell it resides in
	 */
	private function render_checkbox_cell( $string ) {
		$class = 'icl_st_row_cb' . ( ! empty( $string['string_package_id'] ) ? ' icl_st_row_package' : '' );

		return '<td><input class="' . esc_attr( $class ) . '" type="checkbox" value="' . esc_attr( $string['string_id'] )
		       . '" data-language="' . esc_attr( $string['string_language'] ) . '" /></td>';
	}

	private function render_view_column( $string_id ) {
		if ( isset( $this->strings_in_page[ ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE ][ $string_id ] ) ) {
			?>
			<a class="thickbox" title="<?php esc_attr_e( 'view in source', 'wpml-string-translation' ) ?>"
			   href="admin.php?page=<?php echo WPML_ST_FOLDER ?>%2Fmenu%2Fstring-translation.php&amp;icl_action=view_string_in_source&amp;string_id=<?php
			   echo $string_id ?>&amp;width=810&amp;height=600"><img
						src="<?php echo WPML_ST_URL ?>/res/img/view-in-source.png" width="16" height="16"
						alt="<?php esc_attr_e( 'view in page', 'wpml-string-translation' ) ?>"/></a>
			<?php
		}

		if ( isset( $this->strings_in_page[ ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE ][ $string_id ] ) ) {
			?>
			<a class="thickbox" title="<?php esc_attr_e( 'view in page', 'wpml-string-translation' ) ?>"
			   href="admin.php?page=<?php echo WPML_ST_FOLDER ?>%2Fmenu%2Fstring-translation.php&icl_action=view_string_in_page&string_id=<?php
			   echo $string_id ?>&width=810&height=600"><img src="<?php echo WPML_ST_URL ?>/res/img/view-in-page.png"
															 width="16" height="16"
															 alt="<?php esc_attr_e( 'view in page', 'wpml-string-translation' ) ?>"/></a>
			<?php
		}
	}

	private function get_translation_form_status( $icl_string, $lang ) {
		global $wpdb;

		$form_disabled        = '';
		$form_disabled_reason = '';

		if ( icl_st_is_translator() ) {

			// Determine if string is being translated via Translation Proxy
			$translation_proxy_status = $wpdb->get_var( $wpdb->prepare( "
					SELECT c.status FROM {$wpdb->prefix}icl_core_status c 
						JOIN {$wpdb->prefix}icl_string_status s ON s.rid = c.rid
						WHERE s.string_translation_id = (SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d AND language=%s) AND c.target=%s AND c.status = %d
						ORDER BY s.id DESC LIMIT 1
				", $icl_string['string_id'], $lang['code'], $lang['code'], ICL_TM_WAITING_FOR_TRANSLATOR ) );

			$can_translate = isset( $icl_string['translations'][ $lang['code'] ] );
			if ( ! $can_translate ) {
				$form_disabled_reason = __( "You can't translate this string because it hasn't been sent for translation in this language", 'wpml-string-translation' );
			}
			$translator_id = $can_translate ? $icl_string['translations'][ $lang['code'] ]['translator_id'] : null;

			if ( $can_translate && 0 != $translator_id && get_current_user_id() != $translator_id ) {
				$can_translate        = false;
				$form_disabled_reason = __( "You can't translate this string because it's assigned to another translator", 'wpml-string-translation' );
			}

			if ( $can_translate &&
				 0 == $translator_id &&
				 ICL_TM_WAITING_FOR_TRANSLATOR === (int) $icl_string['translations'][ $lang['code'] ]['status'] &&
				 $translation_proxy_status
			) {
				$can_translate        = false;
				$form_disabled_reason = __( "You can't translate this string because it's assigned to another translator", 'wpml-string-translation' );
			}

			if ( ! $can_translate ) {
				$form_disabled = ' disabled="disabled" ';
			}
		}

		return array( $form_disabled, $form_disabled_reason );
	}

	private function hide_if_md5( $str ) {
		return preg_replace( '#^((.+)( - ))?([a-z0-9]{32})$#', '$2', $str );
	}
}

