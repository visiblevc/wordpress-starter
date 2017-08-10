<?php

class WPML_SEO_HeadLangs {
	private $sitepress;

	/**
	 * WPML_SEO_HeadLangs constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( &$sitepress ) {
		$this->sitepress = &$sitepress;
	}

	private function get_seo_settings() {
		$seo_settings = $this->sitepress->get_setting( 'seo', array() );
		if ( ! array_key_exists( 'head_langs', $seo_settings ) ) {
			$seo_settings['head_langs'] = 1;
		}
		if ( ! array_key_exists( 'head_langs_priority', $seo_settings ) ) {
			$seo_settings['head_langs_priority'] = 1;
		}

		return $seo_settings;
	}

	public function init_hooks() {
		if ( $this->sitepress->get_wp_api()->is_front_end() ) {
			$seo_settings = $this->get_seo_settings();
			$head_langs = $seo_settings['head_langs'];
			if ( $head_langs ) {
				$priority = $seo_settings['head_langs_priority'];
				add_action( 'wp_head', array( $this, 'head_langs' ), $priority );
			}
		}
	}

	function head_langs() {
		$languages = $this->sitepress->get_ls_languages( array( 'skip_missing' => true ) );
		$languages = apply_filters( 'wpml_head_langs', $languages );

		if ( $this->must_render( $languages ) ) {
			$hreflang_items = array();
			foreach ( $languages as $code => $lang ) {
				$alternate_hreflang               = apply_filters( 'wpml_alternate_hreflang', $lang['url'], $code );
				$hreflang_code                    = $this->sitepress->get_language_tag( $code );
				if($hreflang_code) {
					$hreflang_items[ $hreflang_code ] = str_replace( '&amp;', '&', $alternate_hreflang );
				}
			}
			$hreflang_items = apply_filters( 'wpml_hreflangs', $hreflang_items );

			$hreflang = '';
			if ( is_array( $hreflang_items ) ) {
				foreach ( $hreflang_items as $hreflang_code => $hreflang_url ) {
					$hreflang .= '<link rel="alternate" hreflang="' . esc_attr( $hreflang_code ) . '" href="' . esc_url( $hreflang_url ) . '" />' . PHP_EOL;
				}
				echo apply_filters( 'wpml_hreflangs_html', $hreflang );
			}
		}
	}

	function render_menu() {
		$seo     = $this->get_seo_settings();
		$options = array();
		foreach ( array( 1, 10 ) as $priority ) {
			$label    = __( 'As early as possible', 'sitepress' );
			if ( $priority > 1 ) {
				$label = sprintf( esc_html__( 'Later in the head section (priority %d)', 'sitepress' ), $priority );
			}
			$options[ $priority ] = array(
				'selected' => ( $priority == $seo['head_langs_priority'] ),
				'label'    => $label,
			);
		}

		?>
		<div class="wpml-section wpml-section-seo-options" id="lang-sec-9-5">
			<div class="wpml-section-header">
				<h3><?php esc_html_e( 'SEO Options', 'sitepress' ) ?></h3>
			</div>
			<div class="wpml-section-content">
				<form id="icl_seo_options" name="icl_seo_options" action="">
					<?php wp_nonce_field( 'icl_seo_options_nonce', '_icl_nonce' ); ?>
					<p>
						<input type="checkbox" id="icl_seo_head_langs" name="icl_seo_head_langs" <?php if ( $seo['head_langs'] )
							echo 'checked="checked"' ?> value="1"/>
						<label for="icl_seo_head_langs"><?php esc_html_e( "Display alternative languages in the HEAD section.", 'sitepress' ); ?></label>
					</p>
					<p>
						<label for="wpml-seo-head-langs-priority"><?php esc_html_e( 'Position of hreflang links', 'sitepress' ); ?></label>
						<select name="wpml_seo_head_langs_priority" id="wpml-seo-head-langs-priority" <?php if ( ! $seo['head_langs'] ) echo 'disabled="disabled"' ?>>
							<?php
							foreach ($options as $priority => $option ) {
								?>
								<option value="<?php echo esc_html( $priority ); ?>" <?php echo $option['selected'] ? 'selected="selected"' :''; ?>><?php echo esc_html( $option['label'] ); ?></option>
								<?php
							}
							?>
						</select>
					</p>
					<p class="buttons-wrap">
						<span class="icl_ajx_response" id="icl_ajx_response_seo"></span>
						<input class="button button-primary" name="save" value="<?php esc_attr_e( 'Save', 'sitepress' ) ?>" type="submit"/>
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	private function must_render( $languages ) {
		$has_languages = false;
		$is_published  = false;
		$post          = $this->sitepress->get_wp_api()->get_post();
		if ( $post ) {
			$post_id = $post ? $post->ID : false;

			$has_languages     = is_array( $languages ) && count( $languages ) > 0 && $this->sitepress->is_translated_post_type( $post->post_type );
			$is_single_or_page = $this->sitepress->get_wp_api()->is_single() || $this->sitepress->get_wp_api()->is_page();
			$is_published      = $is_single_or_page
			                     && $post_id
			                     && $this->sitepress->get_wp_api()->get_post_status( $post_id ) === 'publish';
		}
		return $has_languages && ! $this->sitepress->get_wp_api()->is_paged()
		       && ( $is_published
		            || ( $this->sitepress->get_wp_api()->is_home()
		                 || $this->sitepress->get_wp_api()->is_front_page()
		                 || $this->sitepress->get_wp_api()->is_archive() ) );
	}
}