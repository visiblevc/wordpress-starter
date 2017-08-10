<?php

/**
 * Class WPML_Setup_Step_One_Menu
 */
class WPML_Setup_Step_One_Menu extends WPML_SP_User {

	/**
	 * @return string
	 */
	public function render() {
		ob_start();
		?>
		<div class="wpml-section">
			<div class="wpml-section-header">
				<h3><?php esc_html_e( 'Current content language', 'sitepress' ) ?></h3>
			</div>

			<div class="wpml-section-content">
				<form id="icl_initial_language" method="post">
					<div class="wpml-section-content-inner">
					<?php wp_nonce_field( 'icl_initial_language', 'icl_initial_languagenonce' ) ?>
					<p class="wpml-wizard-instruction">
						<label for="icl_initial_language_code"><?php esc_html_e( 'Before adding other languages, please select the language existing contents are written in:', 'sitepress' ) ?></label>
					</p>
					<?php
					$def_lang = $this->default_lang_preset();
					?>
					<p>
						<select id="icl_initial_language_code"
						        name="icl_initial_language_code">
							<?php $languages = $this->sitepress->get_languages( $def_lang ); ?>
							<?php foreach ( $languages as $lang ): ?>
								<option <?php if ( $def_lang === $lang['code'] ): ?>selected="selected"<?php endif; ?>
								        value="<?php echo esc_attr( $lang['code'] ) ?>"><?php echo esc_html( $lang['display_name'] ) ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					</div>
					<footer class="clearfix text-right">
						<input class="button-primary alignright" name="save" value="<?php esc_html_e( 'Next', 'sitepress' ) ?>"
						       type="submit"/>
					</footer>
				</form>
			</div>
		</div> <!-- .wpml-section -->
		<?php

		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	private function default_lang_preset() {

		return $this->sitepress
			->get_setting( 'existing_content_language_verified' )
			? $this->sitepress->get_default_language()
			: $this->def_lang_from_fallback();
	}

	/**
	 * @return string
	 */
	private function def_lang_from_fallback() {
		$blog_current_lang = 0;
		$wp_api            = $this->sitepress->get_wp_api();
		$wp_records        = $this->sitepress->get_records();
		if ( $blog_lang = $wp_api->get_option( 'WPLANG' ) ) {
			$blog_current_lang = $this->locale_to_lang_code( $blog_lang );
		}
		if ( ! $blog_current_lang && ( $wp_lang_const = $wp_api->constant( 'WP_LANG' ) ) ) {
			$blog_current_lang = $wp_records->icl_languages_by_default_locale( $wp_lang_const )->code();
			$blog_current_lang = $blog_current_lang
				? $blog_current_lang : $this->locale_to_lang_code( $wp_lang_const );

		}

		return $blog_current_lang ? $blog_current_lang : 'en';
	}

	/**
	 * @param string $locale
	 *
	 * @return bool|string
	 */
	private function locale_to_lang_code( $locale ) {
		$exp = explode( '_', $locale );

		return $exp[0]
		       && $this->sitepress->get_records()
		                          ->icl_languages_by_code( $exp[0] )->exists()
			? $exp[0] : false;
	}
}