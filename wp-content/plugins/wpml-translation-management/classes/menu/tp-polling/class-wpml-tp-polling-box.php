<?php

class WPML_TP_Polling_Box {

	/**
	 * Renders the html for the TP polling pickup box
	 *
	 * @return string
	 */
	public function render() {
		$logger_settings = new WPML_Jobs_Fetch_Log_Settings();

		ob_start();
		?>
		<div id="icl_tm_pickup_wrap">
			<div class="icl_cyan_box">
				<div id="icl_tm_pickup_wrap_errors" class="icl_tm_pickup_wrap"
				     style="display:none"><p></p></div>
				<div id="icl_tm_pickup_wrap_completed"
				     class="icl_tm_pickup_wrap" style="display:none"><p></p>
				</div>
				<div id="icl_tm_pickup_wrap_cancelled"
				     class="icl_tm_pickup_wrap" style="display:none"><p></p>
				</div>
				<div id="icl_tm_pickup_wrap_error_submitting"
				     class="icl_tm_pickup_wrap" style="display:none"><p></p>
				</div>
				<p id="icl_pickup_nof_jobs"></p>
				<p><input type="button" class="button-secondary"
				          data-reloading-text="<?php _e( 'Reloading:',
					          'wpml-translation-management' ) ?>" value=""
				          id="icl_tm_get_translations"/></p>
				<p>
					<a href="<?php echo esc_attr( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=' . $logger_settings->get_ui_key() );?>" class="button-secondary">
						<?php _e( 'Open the content updates log', 'sitepress' ); ?>
					</a>
				</p>
				<p id="icl_pickup_last_pickup"></p>
				<?php
				$translation_service = TranslationProxy::get_current_service();
				if ( $translation_service && property_exists( $translation_service, 'has_language_pairs' ) && $translation_service->has_language_pairs ) {
					?>
					<p>
						<a href="#" class="button-secondary js-refresh-language-pairs" data-nonce="<?php echo wp_create_nonce( 'wpml-tp-refresh-language-pairs' ) ?>">
							<?php _e( 'Refresh language pairs', 'wpml-translation-management' ); ?>
						</a>
					</p>

					<?php
				}
				?>
			</div>
			<div id="tp_polling_job" style="display:none"></div>
		</div>
		<br clear="all"/>
		<?php
		wp_nonce_field( 'icl_pickup_translations_nonce',
			'_icl_nonce_pickup_t' );
		wp_nonce_field( 'icl_populate_translations_pickup_box_nonce',
			'_icl_nonce_populate_t' );
		wp_enqueue_script( 'wpml-tp-polling-setup' );

		return ob_get_clean();
	}
}