<?php
/**
 * @package wpml-core
 * @subpackage wpml-core
 */

require_once dirname( __FILE__ ) . '/translationproxy-api.class.php';

class TranslationProxy_Service {

	public $id;
	public $name;
	public $description;
	public $default_service;
	public $has_translator_selection = true;    //Todo: read this from service properties
	public $delivery_method;
	public $project_details_url;
	public $custom_text_url;
	public $has_language_pairs;
	public $languages_map;
	public $url;
	public $logo_url;
	public $create_project_url;
	public $add_language_pair_url;
	public $new_job_url;
	public $custom_fields;
	public $custom_fields_data;
	public $select_translator_iframe_url;
	public $translator_contact_iframe_url;
	public $quote_iframe_url;

	public static function is_authenticated( $service ) {

		//for services that do not require authentication return true by default
		if ( ! TranslationProxy::service_requires_authentication( $service ) ) {
			return true;
		}

		return isset( $service->custom_fields_data ) && $service->custom_fields_data ? true : false;
	}

	public static function list_services() {
		$services = TranslationProxy_Api::proxy_request( '/services.json' );

		return $services;
	}

	public static function get_service( $service_id ) {
		$service                = TranslationProxy_Api::proxy_request( "/services/$service_id.json" );
		$service->languages_map = self::languages_map( $service );

		return $service;
	}

	public static function get_service_by_suid( $suid ) {
		$service                = TranslationProxy_Api::proxy_request( "/services/$suid.json" );
		$service->languages_map = self::languages_map( $service );

		return $service;
	}

	public static function languages_map( $service ) {
		$languages_map = array();
		$languages     = TranslationProxy_Api::proxy_request( "/services/{$service->id}/language_identifiers.json" );
		foreach ( $languages as $language ) {
			$languages_map[ $language->iso_code ] = $language->value;
		}

		return $languages_map;
	}

	public static function get_language( $service, $language ) {
		if ( ! empty( $service->languages_map ) and array_key_exists( $language, $service->languages_map ) ) {
			$language = $service->languages_map[ $language ];
		}

		return $language;
	}

	/**
	 * Returns a WPML readable string that allows to tell translation service and translator id
	 * (typically used for translators dropdowns)
	 *
	 * @param int|bool $translation_service_id
	 * @param int|bool $translator_id
	 *
	 * @return string
	 */
	public static function get_wpml_translator_id( $translation_service_id = false, $translator_id = false ) {
		if ( $translation_service_id === false ) {
			$translation_service_id = TranslationProxy::get_current_service_id();
		}
		$result = 'ts-' . $translation_service_id;
		if ( $translator_id !== false ) {
			$result .= '-' . $translator_id;
		}

		return $result;
	}

	/**
	 * @param string $translator_id
	 *
	 * @return array Returns a two elements array, respectively containing translation_service and translator_id
	 */
	public static function get_translator_data_from_wpml( $translator_id ) {
		$result = array();
		if ( is_numeric( $translator_id ) ) {
			$result['translation_service'] = 'local';
			$result['translator_id']       = $translator_id;
		} else {
			$translator_data = explode( '-', $translator_id );
			$result                        = array();
			$result['translation_service'] = $translator_data[1];
			$result['translator_id']       = isset( $translator_data[2] ) ? $translator_data[2] : 0;
		}

		return $result;
	}
}