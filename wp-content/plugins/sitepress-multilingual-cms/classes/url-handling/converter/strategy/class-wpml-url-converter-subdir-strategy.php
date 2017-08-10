<?php

class WPML_URL_Converter_Subdir_Strategy extends WPML_URL_Converter_Abstract_Strategy {
	/** @var string */
	private $dir_default;

	/** @var array copy of $sitepress->get_settings( 'urls' ) */
	private $urls_settings;

	/** @var string */
	private $root_url;

	/** @var array map of wpml codes to custom codes*/
	private $language_codes_map = array();
	private $language_codes_reverse_map = array();

	/**
	 * @param string $dir_default
	 * @param string $default_language
	 * @param array  $active_languages
	 * @param array  $urls_settings
	 */
	public function __construct(
		$dir_default,
		$default_language,
		$active_languages,
		$urls_settings
	) {
		parent::__construct( $default_language, $active_languages );
		$this->dir_default   = $dir_default;
		$this->urls_settings = $urls_settings;

		$this->language_codes_map = array_combine( $active_languages, $active_languages );
		$this->language_codes_map = apply_filters( 'wpml_language_codes_map', $this->language_codes_map );

		$this->language_codes_reverse_map = array_flip( $this->language_codes_map );
	}

	public function get_lang_from_url_string( $url ) {

		$url = wpml_strip_subdir_from_url( $url );

		if ( strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 ) {
			$url_path = wpml_parse_url( $url, PHP_URL_PATH );
		} else {
			$pathparts = array_filter( explode( '/', $url ) );
			if ( count( $pathparts ) > 1 ) {
				unset( $pathparts[0] );
				$url_path = implode( '/', $pathparts );
			} else {
				$url_path = $url;
			}
		}

		$fragments = array_filter( (array) explode( '/', $url_path ) );
		$lang      = array_shift( $fragments );

		$lang_get_parts = explode( '?', $lang );
		$lang = $lang_get_parts[0];

		$lang           = isset( $this->language_codes_reverse_map[ $lang ] ) ? $this->language_codes_reverse_map[ $lang ] : $lang;

		if ( $lang && in_array( $lang, $this->active_languages, true ) ) {
			return $lang;
		}
		return $this->dir_default ? null : $this->default_language;
	}

	public function validate_language( $language, $url ) {
		if ( ! ( null === $language && $this->dir_default && ! $this->get_url_helper()->is_url_admin( $url ) ) ) {
			$language = parent::validate_language( $language, $url );
		}

		return $language;
	}

	public function convert_url_string( $source_url, $code ) {
		if ( ! $this->is_root_url( $source_url ) ) {
			$source_url = $this->filter_source_url( $source_url );

			$absolute_home_url = trailingslashit( preg_replace( '#^(http|https)://#', '', $this->get_url_helper()->get_abs_home() ) );
			$absolute_home_url = strpos( $source_url, $absolute_home_url ) === false ? trailingslashit( get_option( 'home' ) ) : $absolute_home_url;

			$code              = ! $this->dir_default && $code === $this->default_language ? '' : $code;
			$current_language  = $this->get_lang_from_url_string( $source_url );
			$current_language  = ! $this->dir_default && $current_language === $this->default_language ? '' : $current_language;

			$code             = isset( $this->language_codes_map[ $code ] ) ? $this->language_codes_map[ $code ] : $code;
			$current_language = isset( $this->language_codes_map[ $current_language ] ) ? $this->language_codes_map[ $current_language ] : $current_language;

			$redirector = new WPML_WPSEO_Redirection();

			if ( ! $redirector->is_redirection() ) {
				$source_url = str_replace(
					array(
						trailingslashit( $absolute_home_url . $current_language ),
						'/' . $code . '//',
					),
					array(
						$code ? ( $absolute_home_url . $code . '/' ) : trailingslashit( $absolute_home_url ),
						'/' . $code . '/',
					),
					$source_url
				);
			}
		}

		return $this->slash_helper->maybe_user_trailingslashit( $source_url, 'untrailingslashit' );
	}

	/**
	 * Will return true if root URL or child of root URL
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function is_root_url( $url ) {
		$result  = false;

		if ( isset( $this->urls_settings['root_page'], $this->urls_settings['show_on_root'] ) &&
		     'page' === $this->urls_settings['show_on_root'] &&
			! empty( $this->urls_settings['directory_for_default_language'] )
		) {

			if ( ! $this->root_url ) {
				$root_post = get_post( $this->urls_settings['root_page'] );

				if ( $root_post ) {
					$this->root_url = trailingslashit( $this->get_url_helper()->get_abs_home() ) . $root_post->post_name;
					$this->root_url = trailingslashit( $this->root_url );
				} else {
					$this->root_url = false;
				}
			}

			$result = strpos( trailingslashit( $url ), $this->root_url ) === 0;
		}

		return $result;
	}

	/**
	 * @param string $source_url
	 *
	 * @return string
	 */
	private function filter_source_url( $source_url ) {
		if ( false === strpos( $source_url, '?' ) ) {
			$source_url = trailingslashit( $source_url );
		} elseif ( false !== strpos( $source_url, '?' ) && false === strpos( $source_url, '/?' ) ) {
			$source_url = str_replace( '?', '/?', $source_url );
		}

		return $source_url;
	}
}