<?php


class WPML_Custom_Post_Slug_UI extends WPML_WPDB_And_SP_User {

	private $settings;
	private $post_sync_setting;

	public function __construct( &$wpdb, &$sitepress ) {
		parent::__construct( $wpdb, $sitepress );

		$this->settings          = new WPML_post_slug_translation_settings( $sitepress );
		$this->post_sync_setting = new WPML_custom_post_sync_settings( $sitepress );

		wp_enqueue_script( 'wpml-custom-post-ui', WPML_ST_URL . '/res/js/wpml_custom_post_ui.js', array( 'jquery' ), WPML_ST_VERSION, true );
	}

	public function render( $post_type, $custom_post ) {

		$_has_slug  = isset( $custom_post->rewrite['slug'] ) && $custom_post->rewrite['slug'];
		$_on        = $this->settings->is_on() &&
		              $_has_slug &&
		              $this->post_sync_setting->is_sync( $post_type );
		$is_hidden  = $_on ? '' : 'hidden';
		$_translate = $this->settings->is_translate( $post_type );
		$string_id  = null;
		if ( $_has_slug ) {
			list( $string_id, $_slug_translations ) = WPML_Slug_Translation::get_translations( $post_type );

			if ( $this->settings->is_on() && $_translate && ! $string_id ) {
				$message = sprintf( esc_html__( '%s slugs are set to be translated, but they are missing their translation', 'wpml-string-translation' ), $custom_post->labels->name );
				ICL_AdminNotifier::displayInstantMessage( $message, 'error', 'below-h2', false );
			}
		} else {
			$_slug_translations = false;
		}
		if ( $_has_slug && $this->settings->is_on() ) {
			?>
            <div class="icl_slug_translation_choice <?php echo $is_hidden; ?>">
                <p>
                    <label>
                        <input name="translate_slugs[<?php echo esc_attr( $post_type ) ?>][on]"
                               type="checkbox" value="1" <?php checked( 1, $_translate, true ) ?> />
						<?php printf( esc_html__( 'Use different slugs in different languages for %s.', 'wpml-string-translation' ), $custom_post->labels->name ) ?>
                    </label>
                </p>

                <table class="js-cpt-slugs <?php if ( empty( $_translate ) ): ?>hidden<?php endif; ?>">

					<?php

					if ( $string_id ) {
						$string      = new WPML_ST_String( $string_id, $this->wpdb );
						$string_lang = $string->get_language();
					} else {
						$string_lang = '';
					}
					$string_lang = $string_lang ? $string_lang : $this->sitepress->get_default_language();

					$languages = $this->sitepress->get_active_languages();
					if ( ! in_array( $string_lang, array_keys( $languages ) ) ) {
						$all_languages             = $this->sitepress->get_languages();
						$languages[ $string_lang ] = $all_languages[ $string_lang ];
					}

					$original_slug = WPML_Slug_Translation::get_slug_by_type( $post_type );
					if ( ! $original_slug ) {
						$original_slug = isset( $custom_post->slug ) ? $custom_post->slug : $custom_post->rewrite['slug'];
					}

					foreach ( $languages as $language ) {
						$slug_translation_value  = ! empty( $_slug_translations[ $language['code'] ]['value'] ) ? $_slug_translations[ $language['code'] ]['value'] : '';
						$slug_translation_sample = $original_slug . ' @' . $language['code'];
						$dom_element_id          = esc_attr( sprintf( 'translate_slugs[%s][langs][%s]', $post_type, $language['code'] ) );
						?>
                        <tr<?php if ( $language['code'] == $string_lang ) {
							echo ' style="display:none"';
						} ?>>
                            <td>
                                <label for="<?php echo $dom_element_id ?>">
									<?php echo $this->sitepress->get_flag_img( $language['code'] ) . ' ' . esc_html( $language['display_name'] ) ?>
                                </label>
                            </td>
                            <td>
                                <input
                                        id="<?php echo $dom_element_id ?>"
                                        class="js-translate-slug"
                                        name="<?php echo $dom_element_id ?>"
                                        type="text"
                                        value="<?php echo esc_attr( $slug_translation_value ) ?>"
                                        placeholder="<?php echo esc_attr( $slug_translation_sample ) ?>"
                                        data-lang="<?php echo esc_attr( $language['code'] ) ?>"
                                />
								<?php
								if ( isset( $_slug_translations[ $language['code'] ] ) && ICL_TM_COMPLETE != $_slug_translations[ $language['code'] ]['status'] ) {
									?>
                                    <em class="icl_st_slug_tr_warn"><?php esc_html_e( "Not marked as 'complete'. Press 'Save' to enable.", 'wpml-string-translation' ) ?></em>
									<?php
								}
								?>
                            </td>
                        </tr>
						<?php
						if ( $language['code'] == $string_lang ) {
							?>
                            <tr>
                                <td>
									<?php
									$lang_selector = new WPML_Simple_Language_Selector( $this->sitepress );
									$lang_selector->render( array(
										'name'               => 'translate_slugs[' . $post_type . '][original]',
										'selected'           => $language['code'],
										'show_please_select' => false,
										'echo'               => true,
										'class'              => 'js-translate-slug-original',
										'data'               => array( 'slug' => $post_type ),
									) );
									?>
                                    <label for="<?php echo $dom_element_id ?>">
                                        <em><?php esc_html_e( '(original)', 'wpml-string-translation' ) ?></em>
                                    </label>
                                </td>
                                <td>
                                    <input disabled="disabled"
                                           class="disabled"
                                           id="<?php echo $dom_element_id ?>"
                                           name="<?php echo $dom_element_id ?>"
                                           type="text"
                                           value="<?php echo esc_attr( $original_slug ) ?>"/>
                                </td>
                            </tr>
							<?php
						}
					}
					?>

                </table>
            </div>
			<?php
		}
	}
}