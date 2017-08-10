<?php

class WPML_Languages_Notices {
	const NOTICE_ID_MISSING_MENU_ITEMS           = 'wpml-missing-menu-items';
	const NOTICE_GROUP                           = 'wpml-core';
	const NOTICE_ID_MISSING_DOWNLOADED_LANGUAGES = 'wpml-missing-downloaded-languages';
	/** @var WPML_Notices */
	private $admin_notices;
	private $translations = array();

	/**
	 * WPML_Languages_Notices constructor.
	 *
	 * @param WPML_Notices $admin_notices
	 */
	public function __construct( WPML_Notices $admin_notices ) {
		$this->admin_notices = $admin_notices;
	}

	function maybe_create_notice_missing_menu_items( $languages_count ) {
		if ( 1 === $languages_count ) {
			$text   = __( 'You need to configure at least one more language in order to access "Theme and plugins localization" and "Media translation".', 'sitepress' );
			$notice = new WPML_Notice( self::NOTICE_ID_MISSING_MENU_ITEMS, $text, self::NOTICE_GROUP );
			$notice->set_css_class_types( 'info' );
			$notice->set_dismissible( true );
			$this->admin_notices->add_notice( $notice );
		} else {
			$this->admin_notices->remove_notice( self::NOTICE_GROUP, self::NOTICE_ID_MISSING_MENU_ITEMS );
		}
	}

	public function missing_languages( $not_found_languages ) {
		if ( $not_found_languages ) {
			$text = '';

			$text .= '<p>';
			$text .= __( 'WordPress cannot automatically download translations for the following languages:', 'sitepress' );
			$text .= '</p>';

			$list_items        = array();
			$list_item_pattern = __( '%s (current locale: %s) - suggested locale(s): %s', 'sitepress' );

			foreach ( (array) $not_found_languages as $not_found_language ) {
				$suggestions = '<strong>' . implode( '</strong>, <strong>', $this->get_suggestions( $not_found_language ) ) . '</strong>';
				$current     = $not_found_language['code'];
				if ( $not_found_language['default_locale'] ) {
					$current = $not_found_language['default_locale'];
				}
				$list_items[] = sprintf( $list_item_pattern, $not_found_language['display_name'], $current, $suggestions );
			}

			$text .= '<ul>';
			$text .= '<li>';
			$text .= implode( '</li><li>', $list_items );
			$text .= '</li>';
			$text .= '</ul>';

			$languages_edit_url  = admin_url( '?page=sitepress-multilingual-cms/menu/languages.php&trop=1' );
			$languages_edit_link = '<a href="' . $languages_edit_url . '">';
			$languages_edit_link .= __( 'Edit Languages', 'sitepress' );
			$languages_edit_link .= '</a>';

			$text .= '<p>';
			$text .= sprintf( __( 'To fix, open "%s" and set the "default locale" values as shown above.', 'sitepress' ), $languages_edit_link );
			$text .= '</p>';

			$notice = new WPML_Notice( self::NOTICE_ID_MISSING_DOWNLOADED_LANGUAGES, $text, self::NOTICE_GROUP );
			$notice->set_css_class_types( 'warning' );
			$notice->add_display_callback( array( $this, 'is_languages_edit_page' ) );
			$this->admin_notices->add_notice( $notice, true );
		} else {
			$this->admin_notices->remove_notice( self::NOTICE_GROUP, self::NOTICE_ID_MISSING_DOWNLOADED_LANGUAGES );
		}
	}

	public function is_languages_edit_page() {
		$result = isset( $_GET['page'], $_GET['trop'] ) && 'sitepress-multilingual-cms/menu/languages.php' === $_GET['page'] && 1 === (int) $_GET['trop'];

		return ! $result;
	}

	private function get_suggestions( array $language ) {
		$suggestions = array();
		if ( function_exists( 'translations_api' ) ) {
			if ( ! $this->translations ) {
				$api = translations_api( 'core', array( 'version' => $GLOBALS['wp_version'] ) );

				if ( ! is_wp_error( $api ) ) {
					$this->translations = $api['translations'];
				}
			}
		}

		if ( $this->translations ) {
			foreach ( $this->translations as $translation ) {
				$default_locale = $this->get_matching_language( $language, $translation );
				if ( $default_locale ) {
					$suggestions[] = $default_locale;
				}
			}
		}

		if ( ! $suggestions ) {
			$suggestions[] = _x( 'None', 'Suggested default locale', 'sitepress' );
		}

		return $suggestions;
	}

	/**
	 * @param string $language_attribute
	 * @param array  $language
	 * @param array  $translation
	 *
	 * @return string|null
	 */
	private function find_matching_attribute( $language_attribute, array $language, array $translation ) {
		if ( $translation && $language[ $language_attribute ] ) {
			$attribute_value = $language[ $language_attribute ];
			$attribute_value = str_replace( '-', '_', $attribute_value );
			$attribute_value = strtolower( $attribute_value );
			$iso_1 = $iso_2 = '';

			if ( array_key_exists( 1, $translation['iso'] ) ) {
				$iso_1 = strtolower( $translation['iso'][1] );
			}
			if ( array_key_exists( 2, $translation['iso'] ) ) {
				$iso_2 = strtolower( $translation['iso'][2] );
			}

			if ( $iso_1 === $attribute_value ) {
				return $translation['language'];
			}
			if ( $iso_2 === $attribute_value ) {
				return $translation['language'];
			}
			if ( $iso_1 . '_' . $iso_2 === $attribute_value ) {
				return $translation['language'];
			}
			if ( $iso_2 . '_' . $iso_1 === $attribute_value ) {
				return $translation['language'];
			}
		}

		return null;
	}

	/**
	 * @param array $language
	 * @param array $translation
	 *
	 * @return null|string
	 */
	private function get_matching_language( array $language, array $translation ) {
		$default_locale = $this->find_matching_attribute( 'default_locale', $language, $translation );
		if ( ! $default_locale ) {
			$default_locale = $this->find_matching_attribute( 'tag', $language, $translation );
			if ( ! $default_locale ) {
				$default_locale = $this->find_matching_attribute( 'code', $language, $translation );
			}
		}

		return $default_locale;
	}
}
