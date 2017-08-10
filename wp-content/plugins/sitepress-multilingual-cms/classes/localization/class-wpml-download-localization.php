<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Download_Localization {
	private $active_languages;
	private $default_language;
	private $not_founds = array();
	private $errors     = array();

	/**
	 * WPML_Localization constructor.
	 *
	 * @param array  $active_languages
	 * @param string $default_language
	 */
	public function __construct( array $active_languages, $default_language ) {
		$this->active_languages = $active_languages;
		$this->default_language = $default_language;
	}

	public function download_language_packs() {
		$results = array();
		if ( $this->active_languages ) {
			if ( ! function_exists( 'wp_can_install_language_pack' ) ) {
				/** WordPress Translation Install API */
				require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			}
			if ( ! function_exists( 'request_filesystem_credentials' ) ) {
				/** WordPress Administration File API */
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			if ( ! function_exists( 'submit_button' ) ) {
				/** WordPress Administration File API */
				require_once ABSPATH . 'wp-admin/includes/template.php';
			}
			if ( ! wp_can_install_language_pack() ) {
				$this->errors[] = 'wp_can_install_language_pack';
			} else {
				foreach ( $this->active_languages as $active_language ) {
					if ( $active_language['code'] === $this->default_language ) {
						continue;
					}
					$result = $this->download_language_pack( $active_language );
					if ( $result ) {
						$results[] = $result;
					}
				}
			}
		}
		return $results;
	}

	public function get_not_founds() {
		return $this->not_founds;
	}

	public function get_errors() {
		return $this->errors;
	}

	private function download_language_pack( $language ) {
		$result = null;

		if ( 'en_US' !== $language['default_locale'] ) {
			if ( $language['default_locale'] ) {
				$result = wp_download_language_pack( $language['default_locale'] );
			}
			if ( ! $result && $language['tag'] ) {
				$result = wp_download_language_pack( $language['tag'] );
			}
			if ( ! $result && $language['code'] ) {
				$result = wp_download_language_pack( $language['code'] );
			}

			if ( ! $result ) {
				$result             = null;
				$this->not_founds[] = $language;
			}
		}

		return $result;
	}
}
