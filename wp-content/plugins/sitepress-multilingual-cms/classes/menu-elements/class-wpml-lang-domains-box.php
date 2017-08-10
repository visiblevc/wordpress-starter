<?php

/**
 * Class WPML_Lang_Domains_Box
 *
 * Displays the table holding the language domains on languages.php
 */
class WPML_Lang_Domains_Box extends WPML_SP_User {

	public function render() {
		$active_languages = $this->sitepress->get_active_languages();
		$default_language = $this->sitepress->get_default_language();
		$language_domains = $this->sitepress->get_setting( 'language_domains', array() );
		$default_home     = $this->sitepress->convert_url( $this->sitepress->get_wp_api()->get_home_url(), $default_language );
		$home_schema      = wpml_parse_url( $default_home, PHP_URL_SCHEME ) . '://';
		$home_path        = wpml_parse_url( $default_home, PHP_URL_PATH );
		$is_per_domain    = WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $this->sitepress->get_setting( 'language_negotiation_type' );
		$is_sso_enabled   = (bool) $this->sitepress->get_setting( 'language_per_domain_sso_enabled', ! $is_per_domain );
		ob_start();
		?>
		<table class="language_domains sub-section">
			<?php foreach ( $active_languages as $code => $lang ) : ?>
				<?php $textbox_id = esc_attr( 'language_domain_' . $code ); ?>
				<tr>
					<td>
						<label
							for="<?php echo $textbox_id ?>">
							<?php echo esc_html( $lang['display_name'] ); ?>
						</label>
					</td>
					<?php if ( $code === $default_language ): ?>
						<td id="icl_ln_home">
							<code>
								<?php echo esc_url( $default_home ); ?>
							</code>
						</td>
						<td>&nbsp;</td>
					<?php else: ?>
						<td style="white-space: nowrap">
							<code><?php echo esc_html( $home_schema ); ?></code>
							<input
								type="text"
								id="<?php echo $textbox_id ?>"
								name="language_domains[<?php echo esc_attr($code) ?>]"
								value="<?php echo isset( $language_domains[ $code ] ) ? filter_var( preg_replace( array(
									'#^' . $home_schema . '#',
									'#' . $home_path . '$#'
								), '',
									$language_domains[ $code ] ), FILTER_SANITIZE_URL ) : filter_var( $this->render_suggested_url( $default_home,
									$code ), FILTER_SANITIZE_URL ); ?>"
								data-language="<?php echo esc_attr($code); ?>"
								size="30"/>
							<?php if ( isset( $home_path[1] ) ): ?>
								<code><?php echo esc_html( $home_path ); ?></code>
							<?php endif; ?>
						</td>
						<td>
							<p style="white-space: nowrap"><input
									class="validate_language_domain"
									type="checkbox"
									id="validate_language_domains_<?php echo esc_attr($code) ?>"
									name="validate_language_domains[]"
									value="<?php echo esc_attr($code) ?>"
									checked="checked"/>
								<label
									for="validate_language_domains_<?php echo esc_attr($code) ?>">
									<?php esc_html_e( 'Validate on save', 'sitepress' ) ?>
								</label>
							</p>
							<p style="white-space: nowrap">
								<span class="spinner spinner-<?php echo esc_attr($code) ?>"></span>
								<span id="ajx_ld_<?php echo esc_attr($code) ?>"></span>
							</p>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			<tr>
				<td colspan="2">
					<label for="sso_enabled">
						<input type="checkbox" id="sso_enabled" name="sso_enabled" value="1" <?php checked( $is_sso_enabled, true, true ); ?>>
						<?php esc_html_e( 'Auto sign-in and sign-out users from all domains', 'sitepress' ); ?>
					</label>
					<span id="sso_information"><i class="otgs-ico-help"></i></span>
					<div id="sso_enabled_notice" style="display: none;">
						<?php esc_html_e( 'Please log-out and login again in order to be able to access the admin features in all language domains.', 'sitepress' ); ?>
					</div>
				</td>
			</tr>
		</table>
		<div id="language_per_domain_sso_description" style="display:none;">
			<p>
				<?php esc_html_e( 'This feature allows the theme and plugins to work correctly when on sites that use languages in domains.<br/>It requires a call to each of the site\'s language domains on both log-in and log-out, so there\'s a small performance penalty to using it.' );?>
			</p>
		</div>
		<?php

		return ob_get_clean();
	}

	private function render_suggested_url( $home, $lang ) {
		$url_parts        = parse_url( $home );
		$exp              = explode( '.', $url_parts['host'] );
		$suggested_url    = $lang . '.';
		array_shift( $exp );
		$suggested_url .= count( $exp ) < 2 ? $url_parts['host'] : implode( '.', $exp );

		return $suggested_url;
	}
}