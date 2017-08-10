<?php
/**
 * @package    wpml-core
 * @subpackage wpml-core
 */
require_once WPML_TM_PATH . '/inc/translation-proxy/functions.php';
require_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy-basket.class.php';
require_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy-api.class.php';
require_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy-project.class.php';
require_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy-service.class.php';
require_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy-popup.class.php';
require_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy-translator.class.php';

define( 'CUSTOM_TEXT_MAX_LENGTH', 1000 );

class TranslationProxy {

	static $errors = array();

	public static function services( $reload = true ) {
		$services = get_transient( 'wpml_translation_service_list' );
		$services = $services ? $services : array();
		if ( $reload || empty( $services ) ) {
			$services = TranslationProxy_Service::list_services();
			set_transient( 'wpml_translation_service_list', $services );
		}

		return apply_filters( 'otgs_translation_get_services', $services );
	}

	public static function get_tp_default_suid() {
		$tp_default_suid = false;

		$preferred_translation_service = self::get_preferred_translation_service();
		if ( $preferred_translation_service ) {
			$tp_default_suid = $preferred_translation_service;
		} elseif ( defined( 'WPML_TP_DEFAULT_SUID' ) ) {
			$tp_default_suid = WPML_TP_DEFAULT_SUID;
		}

		return $tp_default_suid;
	}

	public static function has_preferred_translation_service() {
		return self::get_preferred_translation_service() !== false;
	}

	public static function clear_preferred_translation_service() {
		WP_Installer_API::set_preferred_ts( 'clear' );
	}


	/**
	 * @param int $service_id
	 *
	 * @return TranslationProxy_Project|WP_Error
	 */
	public static function select_service( $service_id ) {
		global $sitepress;

		$service_selected = false;
		$error            = false;

		/** @var $service TranslationProxy_Service */
		$service = TranslationProxy_Api::proxy_request( "/services/{$service_id}.json" );

		if ( $service ) {
			self::deselect_active_service();

			//set language map
			$service->languages_map = self::languages_map( $service );

			//set information about custom fields
			$service->custom_fields      = self::get_custom_fields( $service_id,
				true );
			$service->custom_fields_data = false;

			$sitepress->set_setting( 'translation_service', $service );
			$result           = $service;
			$service_selected = true;

			//Force authentication if no user input is needed
			if ( ! TranslationProxy::service_requires_authentication( $service ) ) {
				$networking      = wpml_tm_load_tp_networking();
				$project_factory = new WPML_TP_Project_Factory();
				$auth            = new WPML_TP_Service_Authentication( $sitepress,
					$networking, $project_factory, new stdClass() );
				$auth->run();
			}
		} else {
			$result = new WP_Error( '0', 'No service selected',
				array( 'service_id' => $service_id ) );
		}

		//Do not store selected service if this operation failed;
		if ( $error || ! $service_selected ) {
			$sitepress->set_setting( 'translation_service', false );
		}
		$sitepress->save_settings();

		return $result;
	}

	public static function deselect_active_service() {
		global $sitepress;

		$sitepress->set_setting( 'translation_service', false );
		$sitepress->set_setting( 'translator_choice', false );
		$sitepress->set_setting( 'icl_lang_status', false );
		$sitepress->set_setting( 'icl_html_status', false );
		$sitepress->set_setting( 'icl_current_session', false );
		$sitepress->set_setting( 'last_icl_reminder_fetch', false );
		$sitepress->set_setting( 'translators_management_info', false );
		$sitepress->set_setting( 'language_pairs', false );
		$sitepress->save_settings();
	}

	/**
	 * @return bool|TranslationProxy_Project
	 */
	public static function get_current_project() {
		$translation_service = apply_filters( 'wpml_get_setting', false, 'translation_service' );

		return $translation_service ? new TranslationProxy_Project( $translation_service ) : false;
	}

	public static function get_current_service_info( $info = array() ) {
		global $sitepress;
		if ( ! $sitepress->get_setting( 'translation_service' ) ) {
			$sitepress->set_setting( 'translation_service', false );
			$sitepress->save_settings();
		}
		$service = self::get_current_service();
		if ( $service ) {
			$service_info = array();
			if ( icl_do_not_promote() ) {
				$service_info['name']        = __( 'Translation Service', 'sitepress' );
				$service_info['logo']        = false;
				$service_info['header']      = __( 'Translation Service', 'sitepress' );
				$service_info['description'] = false;
				$service_info['contact_url'] = false;
			} else {
				$service_info['name']        = $service->name;
				$service_info['logo']        = $service->logo_url;
				$service_info['header']      = $service->name;
				$service_info['description'] = $service->description;
				$service_info['contact_url'] = $service->url;
			}
			$service_info['setup_url']                = TranslationProxy_Popup::get_link( '@select-translators;from_replace;to_replace@', array( 'ar' => 1 ), true );
			$service_info['has_quote']                = $service->quote_iframe_url != '';
			$service_info['has_translator_selection'] = $service->has_translator_selection;

			$info[ $service->id ] = $service_info;
		}

		return $info;
	}

	public static function get_service_promo() {
		global $sitepress;

		if ( icl_do_not_promote() ) {
			return '';
		}

		$cache_key   = 'get_service_promo';
		$cache_found = false;

		$output = wp_cache_get( $cache_key, '', false, $cache_found );

		if ( $cache_found ) {
			return $output;
		}

		$icl_translation_services = apply_filters( 'icl_translation_services', array() );
		$icl_translation_services = array_merge( $icl_translation_services, self::get_current_service_info() );

		$current_language = $sitepress->get_current_language();

		$output = '';

		if ( ! empty( $icl_translation_services ) ) {

			$sitepress_settings     = $sitepress->get_settings();
			$icl_dashboard_settings = isset( $sitepress_settings['dashboard'] ) ? $sitepress_settings['dashboard'] : array();

			if ( empty( $icl_dashboard_settings['hide_icl_promo'] ) ) {
				$exp_hidden = '';
				$col_hidden = ' hidden';
			} else {
				$exp_hidden = ' hidden';
				$col_hidden = '';
			}

			$output .= '<div class="icl-translation-services' . $exp_hidden . '">';
			foreach ( $icl_translation_services as $service ) {
				$output .= '<div class="icl-translation-services-inner">';
				$output .= '<p class="icl-translation-services-logo"><span><img src="' . $service['logo'] . '" alt="' . $service['name'] . '" /></span></p>';
				$output .= '<h3 class="icl-translation-services-header">  ' . $service['header'] . '</h3>';
				$output .= '<div class="icl-translation-desc"> ' . $service['description'] . '</div>';
				$output .= '</div>';
				$output .= '<p class="icl-translation-buttons">';
				if ( $service['has_translator_selection'] ) {
					$output .=
						'<a href="admin.php?page='
						. WPML_TM_FOLDER
						. '/menu/main.php&sm=translators&icl_lng='
						. $current_language
						. '&service=icanlocalize" class="button-secondary"><span>'
						. __( 'Add translators', 'wpml-translation-management' )
						. '</span></a>';
				}
				$output .= '</p>';
				$output .= '<p class="icl-translation-links">';
				$output .= '<a class="icl-mail-ico" href="' . $service['contact_url'] . '" target="_blank">' . __( 'Contact', 'wpml-translation-management' ) . " {$service['name']}</a>";
				$output .= '<a id="icl_hide_promo" href="#">' . __( 'Hide this', 'wpml-translation-management' ) . '</a>';
				$output .= '</p>';
			}
			$output .= '</div>';

			$output .= '<a class="' . $col_hidden . '" id="icl_show_promo" href="#">' . __( 'Need translators?', 'wpml-translation-management' ) . '</a>';
		}

		wp_cache_set( $cache_key, $output );

		return $output;
	}

	public static function get_service_dashboard_info() {
		global $sitepress;

		return TranslationProxy::get_custom_html( 'dashboard', $sitepress->get_current_language(), array(
			'TranslationProxy_Popup',
			'get_link'
		) );
	}

	public static function get_service_translators_info() {
		global $sitepress;

		return TranslationProxy::get_custom_html( 'translators', $sitepress->get_current_language(), array(
			'TranslationProxy_Popup',
			'get_link'
		) );
	}

	/**
	 * @param string $location
	 * @param string $locale
	 * @param string $popup_link_callback
	 * @param int $max_count
	 * @param bool $paragraph
	 *
	 * @return string
	 */
	public static function get_custom_html(
		$location,
		$locale,
		$popup_link_callback,
		$max_count = 1000,
		$paragraph = true
	) {
		/** @var $project TranslationProxy_Project */
		$project = self::get_current_project();

		if ( ! $project ) {
			return '';
		}

		$cache_key   = $project->id . ':' . md5( serialize( array(
				$location,
				$locale,
				serialize( $popup_link_callback ),
				$max_count,
				$paragraph
			) ) );
		$cache_group = 'get_custom_html';
		$cache_found = false;

		$output = wp_cache_get( $cache_key, $cache_group, false, $cache_found );

		if ( $cache_found ) {
			return $output;
		}

		try {
			$text = $project->custom_text( $location, $locale );
		} catch ( Exception $e ) {

			return 'Error getting custom text from Translation Service: ' . $e->getMessage();
		}

		$count = 0;
		if ( $text ) {
			foreach ( $text as $string ) {
				$format_string = self::sanitize_custom_text( $string->format_string );

				if ( $paragraph ) {
					$format = '<p class="icl_status_jobs">' . $format_string . '</p>';
				} else {
					$format = '<div>' . $format_string . '</div>';
				}
				$links = array();
				foreach ( $string->links as $link ) {
					$url  = self::sanitize_custom_text( $link->url );
					$text = self::sanitize_custom_text( $link->text );
					if ( isset( $link->dismiss ) and $link->dismiss == 1 ) {
						$links[] = '<a href="' . $url . '" class="wpml_tp_custom_dismiss_able">' . $text . '</a>';
					} else {
						$links[] = call_user_func( $popup_link_callback,
								$url ) . $text . '</a>';
					}
				}

				$output .= vsprintf( $format, $links );

				$count ++;
				if ( $count >= $max_count ) {
					break;
				}
			}
		}

		return $output;
	}

	public static function get_current_service_name() {

		if ( icl_do_not_promote() ) {
			return __( 'Translation Service', 'sitepress' );
		}

		$translation_service = self::get_current_service();

		if ( $translation_service ) {
			return $translation_service->name;
		}

		return false;
	}

	public static function get_current_service_id() {

		$translation_service = self::get_current_service();

		if ( $translation_service ) {
			return $translation_service->id;
		}

		return false;
	}

	public static function get_current_service_batch_name_max_length() {
		$translation_service = self::get_current_service();

		if ( $translation_service && isset( $translation_service->batch_name_max_length ) ) {
			return $translation_service->batch_name_max_length;
		}

		return 40;
	}

	public static function get_batch_id_from_name( $batch_name ) {
		$cache_key   = $batch_name;
		$cache_group = 'get_batch_id_from_name';
		$cache_found = false;

		$batch_id = wp_cache_get( $cache_key, $cache_group, false, $cache_found );

		if ( $cache_found ) {
			return $batch_id;
		}

		global $wpdb;
		$batch_id_sql      = "SELECT id FROM {$wpdb->prefix}icl_translation_batches WHERE batch_name=%s";
		$batch_id_prepared = $wpdb->prepare( $batch_id_sql, array( $batch_name ) );
		$batch_id          = $wpdb->get_var( $batch_id_prepared );

		if ( $batch_id ) {
			//Cache only if there is a result
			wp_cache_set( $cache_key, $batch_id, $cache_group );
		}

		return $batch_id;
	}

	/**
	 * @param bool|TranslationProxy_Service $service
	 *
	 * @return bool
	 */
	public static function service_requires_authentication( $service = false ) {
		if ( ! $service ) {
			$service = self::get_current_service();
		}

		$custom_fields = false;
		if ( $service != false ) {
			$custom_fields = TranslationProxy::get_custom_fields( $service->id );
		}

		return $custom_fields && isset( $custom_fields->custom_fields ) && count( $custom_fields->custom_fields ) > 0;
	}

	/**
	 * Return true if $service has been successfully authenticated
	 * Services that do not require authentication are by default authenticated
	 *
	 * @param bool $service
	 *
	 * @return bool
	 */
	public static function is_service_authenticated( $service = false ) {
		if ( ! $service ) {
			$service = self::get_current_service();
		}

		if ( ! $service ) {
			return false;
		}

		if ( ! TranslationProxy::service_requires_authentication( $service ) ) {
			return true;
		}

		$has_custom_fields  = TranslationProxy::has_custom_fields();
		$custom_fields_data = TranslationProxy::get_custom_fields_data();

		if ( $has_custom_fields && $custom_fields_data ) {
			return true;
		}

		return false;

	}

	/**
	 * @return bool|TranslationProxy_Service|WP_Error
	 */
	public static function get_current_service() {
		global $sitepress;

		//Todo: if array, cast to object
		/** @var $ts TranslationProxy_Service */
		$ts = $sitepress->get_setting( 'translation_service' );

		if ( is_array( $ts ) ) {
			$error = new WP_Error( 'translation-proxy-service-misconfiguration', 'translation_service is stored as array!', $ts );

			return $error;
		}

		return $ts;
	}

	/**
	 *
	 * @return bool
	 */
	public static function is_current_service_active_and_authenticated() {
		$active_service = self::get_current_service();

		return $active_service && ( ! TranslationProxy::service_requires_authentication() || TranslationProxy_Service::is_authenticated( $active_service ) );
	}

	/**
	 * @return mixed
	 */
	public static function get_translation_projects() {
		global $sitepress;

		return $sitepress->get_setting( 'icl_translation_projects' );
	}

	public static function get_service_name( $service_id = false ) {
		if ( $service_id ) {
			$name     = false;
			$services = self::services( false );

			foreach ( $services as $service ) {
				if ( $service->id == $service_id ) {
					$name = $service->name;
				}
			}
		} else {
			$name = self::get_current_service_name();
		}

		return $name;
	}

	public static function has_custom_fields( $service_id = false ) {
		$custom_fields = self::get_custom_fields( $service_id );

		if ( $custom_fields ) {
			return isset( $custom_fields->custom_fields ) && is_array( $custom_fields->custom_fields ) && count( $custom_fields->custom_fields );
		}

		return false;
	}

	/**
	 * @param int|bool $service_id If not given, will use the current service ID (if any)
	 * @param bool $force_reload Force reload custom fields from Translation Service
	 *
	 * @throws TranslationProxy_Api_Error
	 * @throws InvalidArgumentException
	 * @return array|mixed|null|string
	 */
	public static function get_custom_fields( $service_id = false, $force_reload = false ) {

		if ( ! $service_id ) {
			$service_id = self::get_current_service_id();
		}
		if ( ! $service_id ) {
			return false;
		}

		$translation_service = self::get_current_service();
		if ( $translation_service && ! $force_reload ) {
			return isset( $translation_service->custom_fields ) ? $translation_service->custom_fields : false;
		}

		$params = array(
			'service_id' => $service_id,
		);

		return TranslationProxy_Api::proxy_request( '/services/{service_id}/custom_fields.json', $params );
	}

	/**
	 * @return array
	 */
	public static function get_extra_fields_local() {
		global $sitepress;
		$service                  = TranslationProxy::get_current_service();
		$icl_translation_projects = $sitepress->get_setting( 'icl_translation_projects' );

		if ( isset( $icl_translation_projects[ TranslationProxy_Project::generate_service_index( $service ) ]['extra_fields'] ) && ! empty( $icl_translation_projects[ TranslationProxy_Project::generate_service_index( $service ) ]['extra_fields'] ) ) {
			return $icl_translation_projects[ TranslationProxy_Project::generate_service_index( $service ) ]['extra_fields'];
		}

		return array();
	}

	public static function maybe_convert_extra_fields( $extra_fields ) {
		$extra_fields_typed = array();

		if ( $extra_fields && is_array( $extra_fields ) ) {
			/** @var array $extra_fields */
			/** @var stdClass $extra_field */
			foreach ( $extra_fields as $extra_field ) {
				if ( $extra_field instanceof WPML_TP_Extra_Field ) {
					$extra_field_typed = $extra_field;
				} else {
					$extra_field_typed = new WPML_TP_Extra_Field();
					if ( isset( $extra_field->type ) ) {
						$extra_field_typed->type = $extra_field->type;
					}
					if ( isset( $extra_field->label ) ) {
						$extra_field_typed->label = $extra_field->label;
					}
					if ( isset( $extra_field->name ) ) {
						$extra_field_typed->name = $extra_field->name;
					}
					if ( isset( $extra_field->items ) ) {
						$extra_field_typed->items = $extra_field->items;
					}
				}
				$extra_fields_typed[] = $extra_field_typed;
			}
		}

		return $extra_fields_typed;
	}

	public static function get_custom_fields_data() {
		$service = self::get_current_service();

		return isset( $service->custom_fields_data ) ? $service->custom_fields_data : false;
	}

	/**
	 * @return bool true if the current translation service allows selection of specific translators
	 */
	public static function translator_selection_available() {
		$res = false;

		$translation_service = self::get_current_service();
		if ( $translation_service && $translation_service->has_translator_selection && self::is_service_authenticated() ) {
			$res = true;
		}

		return $res;
	}

	private static function sanitize_custom_text( $text ) {
		$text = substr( $text, 0, CUSTOM_TEXT_MAX_LENGTH );
		$text = esc_html( $text );

		// Service sends html tags as [tag]
		$text = str_replace( '[', '<', $text );
		$text = str_replace( ']', '>', $text );

		return $text;
	}

	private static function languages_map( $service ) {
		$languages_map = array();
		$languages     = TranslationProxy_Api::proxy_request( "/services/{$service->id}/language_identifiers.json" );
		if ( ! empty( $languages ) ) {
			foreach ( $languages as $language ) {
				$languages_map[ $language->iso_code ] = $language->value;
			}
		}

		return $languages_map;
	}

	private static function get_preferred_translation_service() {
		$tp_default_suid                              = false;
		$preferred_translation_service_from_installer = self::get_preferred_translation_service_from_installer();
		if ( $preferred_translation_service_from_installer != 'clear' ) {
			$tp_default_suid = $preferred_translation_service_from_installer;
		}

		return $tp_default_suid;
	}

	private static function get_preferred_translation_service_from_installer() {

		return WP_Installer_API::get_preferred_ts();
	}
}