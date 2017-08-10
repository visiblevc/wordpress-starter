<?php

class WPML_Simple_Language_Selector extends WPML_SP_User {

	function __construct( &$sitepress ) {
		parent::__construct( $sitepress );

		self::enqueue_scripts();
	}

	function render( $options = array() ) {

		$options = array_merge( array(
															'id'                 => '',
															'name'               => '',
															'show_please_select' => true,
															'please_select_text' => __( '-- Please select --', 'wpml-string-translation' ),
															'selected'           => '',
															'echo'               => false,
															'class'              => '',
															'data'               => array(),
															'show_flags'         => true,
															'languages'          => null,
															'disabled'           => false,
															'style'              => 'width:80%',
														), $options );

		if ( $options['languages'] ) {
			$languages = $options['languages'];
		} else {
			$languages = $this->sitepress->get_languages( $this->sitepress->get_admin_language() );
		}
		$active_languages = $this->sitepress->get_active_languages();

		$data = '';
		foreach ( $options['data'] as $key => $value ) {
			$data .= ' data-' . $key . '="' . $value . '"';
		}

		if ( $options['show_flags'] ) {
			$options['class'] .= ' js-simple-lang-selector-flags';
		}

		if ( $options['disabled'] ) {
			$disabled = ' disabled="disabled" ';
		} else {
			$disabled = '';
		}

		if ( ! $options['echo'] ) {
			ob_start();
		}

		?>
		<select
			title="wpml-simple-language-selector"
			<?php
			if ( $options['id'] ) {
				echo ' id="' . esc_attr( $options['id'] ) . '"';
			}

			if ( $options['name'] ) {
				echo ' name="' . esc_attr( $options['name'] ) . '"';
			}
			?>
			class="wpml-simple-lang-selector js-simple-lang-selector <?php echo esc_attr( $options['class'] ); ?>"
			<?php echo esc_attr( $data ); ?>
			<?php echo esc_attr( $disabled ); ?>
			style="<?php echo esc_attr( $options['style'] ); ?>">
			<?php
			if ( $options['show_please_select'] ) {
				?>
				<option value="" <?php
				if ( '' == $options['selected'] ) {
					echo 'selected="selected"';
				}
				?>
					>
					<?php echo esc_html( $options['please_select_text'] ); ?>
				</option>
				<?php
			}
			foreach ( $languages as $lang ) {
				?>
				<option value="<?php echo esc_attr( $lang['code'] ); ?>" <?php
				if ( $options['selected'] == $lang['code'] ) {
					echo 'selected="selected"';
				}
				?>
								data-flag_url="<?php echo esc_url( $this->sitepress->get_flag_url( $lang['code'] ) ); ?>" data-status="<?php echo in_array( $lang['code'], array_keys( $active_languages ) ) ? 'active' : ''; ?>">
					<?php echo esc_html( $lang['display_name'] ); ?>
				</option>
				<?php
			}
			?>
		</select>
		<?php

		if ( ! $options['echo'] ) {
			return ob_get_clean();
		}
		return null;
	}

	static public function enqueue_scripts() {
		if ( ! wp_script_is( 'wpml-select-2' ) ) {
			// Enqueue in the footer because this is usually called late.
			wp_enqueue_script( 'wpml-select-2', ICL_PLUGIN_URL . '/lib/select2/select2.min.js', array( 'jquery' ), ICL_SITEPRESS_VERSION, true );
			wp_enqueue_script( 'wpml-simple_language-selector', ICL_PLUGIN_URL . '/res/js/wpml-simple-language-selector.js', array( 'jquery' ), ICL_SITEPRESS_VERSION, true );
		}
	}
}
