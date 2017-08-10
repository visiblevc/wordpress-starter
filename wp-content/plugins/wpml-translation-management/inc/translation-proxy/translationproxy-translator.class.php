<?php

class TranslationProxy_Translator {
	/**
	 * Get information about translators from current project. Works only for ICL as a Translation Service
	 *
	 * @param bool $force
	 *
	 * @return array|bool
	 */
	public static function get_icl_translator_status( $force = false ) {
		/** @var SitePress $sitepress */
		/** @var WPML_Pro_Translation $ICL_Pro_Translation */
		global $sitepress, $ICL_Pro_Translation;

		if ( ! $ICL_Pro_Translation ) {
			$job_factory         = wpml_tm_load_job_factory();
			$ICL_Pro_Translation = new WPML_Pro_Translation( $job_factory );
		}

		if ( ! TranslationProxy::translator_selection_available() ) {
			return array();
		}

		$project = TranslationProxy::get_current_project();

		if ( ! $project ) {
			return array();
		}

		$cache_key   = md5( serialize( $project ) );
		$cache_group = 'get_icl_translator_status';

		$found  = false;
		$result = wp_cache_get( $cache_key, $cache_group, false, $found );

		if ( $found ) {
			return $result;
		}

		$translator_status = array();
		$website_details   = self::get_website_details( new TranslationProxy_Project( TranslationProxy::get_current_service() ), $force );

		if ( false === (bool) $website_details ) {
			return array();
		}

		$language_pairs = array();
		if ( isset( $website_details['translation_languages']['translation_language'] ) ) {

			$translation_languages = $website_details['translation_languages']['translation_language'];
			if ( ! isset( $translation_languages[0] ) ) {
				$buf                   = $translation_languages;
				$translation_languages = array( 0 => $buf );
			}

			foreach ( $translation_languages as $lang ) {
				$translators = $_tr = array();
				$max_rate    = false;
				if ( isset( $lang['translators'], $lang['translators']['translator'] ) && ! empty( $lang['translators'] ) ) {
					if ( ! isset( $lang['translators']['translator'][0] ) ) {
						$_tr[0] = $lang['translators']['translator'];
					} else {
						$_tr = $lang['translators']['translator'];
					}
					foreach ( $_tr as $t ) {
						if ( false === $max_rate || $t['attr']['amount'] > $max_rate ) {
							$max_rate = $t['attr']['amount'];
						}
						$translators[] = array(
							'id'          => $t['attr']['id'],
							'nickname'    => $t['attr']['nickname'],
							'contract_id' => $t['attr']['contract_id'],
						);
					}
				}
				$language_pairs[] = array(
					'from'                  => $sitepress->get_language_code( $ICL_Pro_Translation->server_languages_map( $lang['attr']['from_language_name'], true ) ),
					'to'                    => $sitepress->get_language_code( $ICL_Pro_Translation->server_languages_map( $lang['attr']['to_language_name'], true ) ),
					'have_translators'      => $lang['attr']['have_translators'],
					'available_translators' => $lang['attr']['available_translators'],
					'applications'          => $lang['attr']['applications'],
					'contract_id'           => $lang['attr']['contract_id'],
					'id'                    => $lang['attr']['id'],
					'translators'           => $translators,
					'max_rate'              => $max_rate,
				);
			}
		}

		$translator_status['icl_lang_status'] = $language_pairs;
		if ( isset( $res['client']['attr'] ) ) {
			$translator_status['icl_balance']        = $res['client']['attr']['balance'];
			$translator_status['icl_anonymous_user'] = $res['client']['attr']['anon'];
		}
		if ( isset( $res['html_status']['value'] ) ) {
			$translator_status['icl_html_status'] = html_entity_decode( $res['html_status']['value'] );
			$translator_status['icl_html_status'] = preg_replace_callback(
				'#<a([^>]*)href="([^"]+)"([^>]*)>#i',
				create_function( '$matches', 'global $sitepress; return TranslationProxy_Popup::get_link($matches[2]);' ),
				$translator_status['icl_html_status']
			);
		}

		if ( isset( $res['translators_management_info']['value'] ) ) {
			$translator_status['translators_management_info'] = html_entity_decode( $res['translators_management_info']['value'] );
			$translator_status['translators_management_info'] = preg_replace_callback(
				'#<a([^>]*)href="([^"]+)"([^>]*)>#i',
				create_function( '$matches', 'global $sitepress; return TranslationProxy_Popup::get_link($matches[2]);' ),
				$translator_status['translators_management_info']
			);
		}

		$translator_status['icl_support_ticket_id'] = null;
		if ( isset( $res['attr']['support_ticket_id'] ) ) {
			$translator_status['icl_support_ticket_id'] = (int) $res['attr']['support_ticket_id'];
		}
		wp_cache_set( $cache_key, $translator_status, $cache_group );

		return $translator_status;
	}

	/**
	 *
	 * Get information about language pairs (including translators). Works only for ICL as a Translation Service
	 *
	 * @return array
	 */
	public static function get_language_pairs() {
		global $sitepress;

		$icl_lang_status = $sitepress->get_setting( 'icl_lang_status', array() );
		if ( ! empty( $icl_lang_status ) ) {
			$missing_translators = false;
			foreach ( $icl_lang_status as $lang ) {
				if ( empty( $lang['translators'] ) ) {
					$missing_translators = true;
					break;
				}
			}
			if ( ! $missing_translators ) {
				$icl_lang_sub_status = $icl_lang_status;
			}
		}

		if ( ! isset( $icl_lang_sub_status ) ) {
			$translator_status   = self::get_icl_translator_status();
			$icl_lang_sub_status = isset( $translator_status['icl_lang_status'] )
				? $translator_status['icl_lang_status'] : array();
		}
		foreach ( $icl_lang_sub_status as $key => $status ) {
			if ( ! isset( $status['from'] ) ) {
				unset( $icl_lang_sub_status[ $key ] );
			}
		}
		array_filter( $icl_lang_sub_status );

		return $icl_lang_sub_status;
	}

	/**
	 * Sends request to ICL to get website details (including language pairs)
	 *
	 * @param TranslationProxy_Project $project
	 * @param bool                     $force
	 *
	 * @return array
	 */
	private static function get_website_details( $project, $force = false ) {

		require_once ICL_PLUGIN_PATH . '/inc/utilities/xml2array.php';
		require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';

		$site_id    = $project->ts_id;
		$access_key = $project->ts_access_key;

		$default = array();

		if ( ! $site_id ) {
			return $default;
		}

		$icl_query = new ICanLocalizeQuery( $site_id, $access_key );

		return $icl_query->get_website_details( $force );
	}

	/**
	 * Returns array of remote translators. Works only with ICL as a Translation Service
	 *
	 * @return array
	 */
	public static function translation_service_translators_list() {

		$translators = array();

		if ( ! TranslationProxy::translator_selection_available() ) {
			return $translators;
		}

		$project = TranslationProxy::get_current_project();

		if ( ! $project ) {
			return $translators;
		}

		$lang_status = TranslationProxy_Translator::get_language_pairs();

		if ( ! $lang_status ) {
			return $translators;
		}

		$action_link_args = array(
			'title'     => __( 'Contact translator', 'sitepress' ),
			'unload_cb' => 'icl_thickbox_refresh',
			'ar'        => 1
		);

		foreach ( $lang_status as $language_pair ) {

			$language_from = $language_pair['from'];

			$language_pair_translators = $language_pair['translators'];

			if ( $language_pair_translators ) {
				foreach ( $language_pair_translators as $translator ) {
					$translator_item = array();
					if ( isset( $translators[ $translator['id'] ] ) ) {
						$translator_item                              = $translators[ $translator['id'] ];
						$translator_item['langs'][ $language_from ][] = $language_pair['to'];
					} else {
						$translator_item['name']                      = $translator['nickname'];
						$translator_item['langs'][ $language_from ][] = $language_pair['to'];
						$translator_item['type']                      = $project->service->name;
						$url                                          = $project->translator_contact_iframe_url( $translator['id'] );
						$action_link                                  = '';
						if ( $url ) {
							$action_link = TranslationProxy_Popup::get_link( $url, $action_link_args ) . __( 'Contact translator', 'sitepress' ) . '</a>';
						}
						$translator_item['action'] = $action_link;
					}
					$translators[ $translator['id'] ] = $translator_item;
				}
			}
		}

		return $translators;
	}

	/**
	 * @param $translator_id
	 *
	 * @return string|false
	 */
	public static function get_translator_name( $translator_id ) {
		if ( TranslationProxy::translator_selection_available() ) {
			$lang_status = self::get_language_pairs();
			if ( $lang_status ) {
				foreach ( $lang_status as $lp ) {
					$lp_trans = ! empty( $lp['translators'] ) ? $lp['translators'] : array();
					foreach ( $lp_trans as $tr ) {
						$translators[ $tr['id'] ] = $tr['nickname'];
					}
				}
			}
		}

		return isset( $translators[ $translator_id ] ) ? $translators[ $translator_id ] : false;
	}

	/**
	 * Synchronizes language pairs with ICL
	 *
	 * @global object $sitepress
	 *
	 * @param $project
	 * @param $language_pairs
	 *
	 */
	public static function update_language_pairs( $project, $language_pairs ) {
		/** @var WPML_Pro_Translation $ICL_Pro_Translation */
		global $sitepress, $ICL_Pro_Translation;

		$params = array(
				'site_id'        => $project->ts_id,
				'accesskey'      => $project->ts_access_key,
				'create_account' => 0
		);

		$lang_server = array();
		foreach ( $sitepress->get_active_languages() as $lang ) {
			$lang_server[ $lang['code'] ] = $ICL_Pro_Translation->server_languages_map( $lang['english_name'] );
		}

		// update account - add language pair
		$incr = 0;
		foreach ( $language_pairs as $k => $v ) {
			if ( ! array_key_exists( $k, $lang_server ) ) {
				unset( $language_pairs[ $k ] );
				continue;
			}
			foreach ( $v as $k2 => $v2 ) {
				if ( ! array_key_exists( $k2, $lang_server ) ) {
					unset( $language_pairs[ $k ][ $k2 ] );
					if ( (bool) $language_pairs[ $k ] === false ) {
						unset( $language_pairs[ $k ] );
					}
					continue;
				}
				$incr ++;
				$params[ 'from_language' . $incr ] = $lang_server[ $k ];
				$params[ 'to_language' . $incr ]   = $lang_server[ $k2 ];
			}
		}

		require_once ICL_PLUGIN_PATH . '/inc/utilities/xml2array.php';
		require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
		$icl_query = new ICanLocalizeQuery();
		$icl_query->updateAccount( $params );
	}

	public static function flush_website_details_cache() {
		delete_transient( WEBSITE_DETAILS_TRANSIENT_KEY );
	}

	public static function flush_website_details_cache_action() {
		$nonce          = array_key_exists( 'nonce', $_POST ) ? $_POST['nonce'] : null;
		$action         = array_key_exists( 'action', $_POST ) ? $_POST['action'] : null;
		$nonce_is_valid = wp_verify_nonce( $nonce, $action );

		if ( $nonce_is_valid ) {
			self::flush_website_details_cache();
			$query_args = array(
				'page' => urlencode( 'wpml-translation-management/menu/main.php' ),
				'sm'   => urlencode( 'translators' ),
			);
			$link_url   = add_query_arg( $query_args, get_admin_url( null, 'admin.php' ) );
			wp_send_json_success( array( 'redirectTo' => $link_url ) );
		} else {
			wp_send_json_error( 'Nonce is not valid.' );
		}
	}
}
