<?php

abstract class WPML_TM_MCS_Custom_Field_Settings_Menu {

	/** @var  WPML_Custom_Field_Setting_Factory $settings_factory */
	protected $settings_factory;

	/**
	 * WPML_TM_Post_Edit_Custom_Field_Settings_Menu constructor.
	 *
	 * @param WPML_Custom_Field_Setting_Factory $settings_factory
	 */
	public function __construct( &$settings_factory ) {
		$this->settings_factory = &$settings_factory;
	}

	/**
	 * @return string
	 */
	public function render() {
		$custom_fields_keys = $this->get_meta_keys();

		if ( $custom_fields_keys ) {
			natcasesort( $custom_fields_keys );
		}
		ob_start();
		?>
		<div
			class="wpml-section wpml-section-<?php echo $this->kind_shorthand(); ?>-translation" id="ml-content-setup-sec-<?php echo $this->kind_shorthand(); ?>">
			<div class="wpml-section-header">
				<h3><?php echo $this->get_title() ?></h3>
				<p>
					<?php
					$toggle_system_fields= array(
						'url' => add_query_arg(array('show_system_fields' => !$this->settings_factory->show_system_fields)),
						'text' => $this->settings_factory->show_system_fields ? __('Hide system fields', 'wpml-translation-management') : __('Show system fields', 'wpml-translation-management'),
					);
					?>
					<a href="<?php echo $toggle_system_fields['url']?>"><?php echo $toggle_system_fields['text'];?></a>
				</p>

			</div>
			<div class="wpml-section-content">
				<form id="icl_<?php echo $this->kind_shorthand() ?>_translation"
				      name="icl_<?php echo $this->kind_shorthand() ?>_translation"
				      action="">
					<?php wp_nonce_field( 'icl_' . $this->kind_shorthand() . '_translation_nonce', '_icl_nonce' ); ?>
					<?php
					if ( empty( $custom_fields_keys ) ) {
						?>
						<p class="no-data-found">
							<?php echo $this->get_no_data_message(); ?>
						</p>
						<?php
					} else {
						?>
						<table class="widefat fixed">
							<thead>
							<?php echo $this->render_heading() ?>
							</thead>
							<tfoot>
							<?php echo $this->render_heading() ?>
							</tfoot>
							<tbody>
							<?php foreach ( $custom_fields_keys as $cf_key ): ?><?php
								$setting = $this->get_setting( $cf_key );
								if ( $setting->excluded() ) {
									continue;
								}
								$status        = $setting->status();
								$html_disabled = $setting->is_read_only() ? 'disabled="disabled"' : '';
								?>
								<tr>
									<td><?php echo esc_html( $cf_key ); ?></td>
									<?php
									foreach (
										array(
											WPML_IGNORE_CUSTOM_FIELD    => __( "Don't translate", 'wpml-translation-management' ),
											WPML_COPY_CUSTOM_FIELD      => __( "Copy from original to translation", 'wpml-translation-management' ),
											WPML_TRANSLATE_CUSTOM_FIELD => __( "Translate", 'wpml-translation-management' )
										) as $ref_status => $title
									) {
										?>
										<td title="<?php echo $title ?>">
											<?php echo $this->render_radio( $cf_key, $html_disabled, $status, $ref_status ) ?>
										</td>
										<?php
									}
									?>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						<p class="buttons-wrap">
								<span class="icl_ajx_response" id="icl_ajx_response_<?php echo $this->kind_shorthand() ?>"></span>
							<input type="submit" class="button-primary" value="<?php _e( 'Save', 'wpml-translation-management' ) ?>"/>
						</p>
						<?php
					}
					?>
				</form>
			</div>
			<!-- .wpml-section-content -->
		</div> <!-- .wpml-section -->
		<?php

		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	protected abstract function kind_shorthand();

	/**
	 * @return string
	 */
	protected abstract function get_title();

	/**
	 * @return string[]
	 */
	protected abstract function get_meta_keys();

	/**
	 * @param string $key
	 *
	 * @return WPML_Custom_Field_Setting
	 */
	protected abstract function get_setting( $key );

	private function render_radio( $cf_key, $html_disabled, $status, $ref_status ) {
		ob_start();
		?>
		<input type="radio"
		       name="cf[<?php echo base64_encode( $cf_key ) ?>]"
		       value="<?php echo $ref_status ?>" <?php echo $html_disabled ?>
		       <?php if ( $status == $ref_status ): ?>checked<?php endif; ?> />
		<?php

		return ob_get_clean();
	}

	/**
	 * @return string header and footer of the setting table
	 */
	private function render_heading() {
		ob_start();
		?>
		<tr>
			<th>
				<?php echo $this->get_column_header('name') ?>
			</th>
			<th>
				<?php _e( "Don't translate", 'wpml-translation-management' ) ?>
			</th>
			<th>
				<?php _e( "Copy from original to translation", 'wpml-translation-management' ) ?>
			</th>
			<th>
				<?php _e( "Translate", 'wpml-translation-management' ) ?>
			</th>
		</tr>
		<?php

		return ob_get_clean();
	}

	public abstract function get_no_data_message();
	public abstract function get_column_header($id);
}