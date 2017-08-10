<?php

/**
 * Class SitePressLanguageSwitcher
 *
 * @deprecated since 3.6.0
 */
class SitePressLanguageSwitcher {

	/**
	 * @deprecated since 3.6.0
	 *
	 * @return string
	 */
	static function get_language_selector_footer() {
		ob_start();
		do_action( 'wpml_footer_language_selector' );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * @deprecated since 3.6.0
	 */
	function language_selector_footer() {
		do_action( 'wpml_footer_language_selector' );
	}

	/**
	 * @deprecated since 3.6.0
	 *
	 * @param $native_name
	 * @param bool $translated_name
	 * @param bool $show_native_name
	 * @param bool $show_translate_name
	 * @param bool $include_html
	 *
	 * @return string
	 */
	public function language_display( $native_name, $translated_name = false, $show_native_name = false, $show_translate_name = false, $include_html = true ) {
		$result = '';

		if ( ! $show_native_name ) {
			$native_name = '';
		}
		
		if ( ! $show_translate_name ) {
			$translated_name = '';
		}
		
		if ( $native_name && $translated_name ) {
			if ( $native_name != $translated_name ) {
				if ( $show_native_name ) {
					if($include_html) {
						$result .= '<span class="icl_lang_sel_native">';
					}
					$result .= '%1$s';
					if($include_html) {
						$result .= '</span>';
					}
					if($show_translate_name) {
						$result .= ' ';
						if($include_html) {
							$result .= '<span class="icl_lang_sel_translated">';
						}
						$result .= $show_native_name
							? '<span class="icl_lang_sel_bracket">(</span>%2$s<span class="icl_lang_sel_bracket">)</span>'
							: '%2$s';
						if($include_html) {
							$result .= '</span>';
						}
					}
				}elseif($show_translate_name) {
					if($include_html) {
						$result .= '<span class="icl_lang_sel_translated">';
					}
					$result .= '%2$s';
					if($include_html) {
						$result .= '</span>';
					}
				}
			} else {
				if($include_html) {
					$result .= '<span class="icl_lang_sel_current icl_lang_sel_native">';
				}
				$result .= '%1$s';
				if($include_html) {
					$result .= '</span>';
				}
			}
		} elseif ( $native_name ) {
			$result = '%1$s';
		} elseif ( $translated_name ) {
			$result = '%2$s';
		}

		return sprintf($result, $native_name, $translated_name);
	}
} // end class

global $icl_language_switcher;
$icl_language_switcher = new SitePressLanguageSwitcher;
