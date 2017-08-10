<?php

/**
 * Class WPML_TM_Translators_Dropdown
 */
class WPML_TM_Translators_Dropdown {

	/**
	 * @var WPML_TM_Blog_Translators @translators
	 */
	private $translators;

	/**
	 * @param WPML_TM_Blog_Translators $blog_translators
	 */
	public function __construct( $blog_translators ) {
		$this->translators = $blog_translators;
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function render( $args = array() ) {
		$dropdown = '';

		/** @var $from string|false */
		/** @var $to string|false */
		/** @var $classes string|false */
		/** @var $id string|false */
		/** @var $name string|false */
		/** @var $selected bool */
		/** @var $echo bool */
		/** @var $add_label bool */
		/** @var $services array */
		/** @var $show_service bool */
		/** @var $disabled bool */
		/** @var $default_name bool|string */
		/** @var $local_only bool */

		//set default value for variables
		$from         = false;
		$to           = false;
		$id           = 'translator_id';
		$name         = 'translator_id';
		$selected     = 0;
		$echo         = true;
		$add_label    = false;
		$services     = array( 'local' );
		$show_service = true;
		$disabled     = false;
		$default_name = false;
		$local_only   = false;

		extract( $args, EXTR_OVERWRITE );

		$translators = array();

		$id .= $from ? '_' . $from . ( $to ? '_' . $to : '' ) : '';

		try {

			$translation_service      = TranslationProxy::get_current_service();
			$translation_service_id   = TranslationProxy::get_current_service_id();
			$translation_service_name = TranslationProxy::get_current_service_name();
			$is_service_authenticated = TranslationProxy::is_service_authenticated();

			//if translation service does not support translators choice, always shows first available
			if ( isset( $translation_service->id ) && ! TranslationProxy::translator_selection_available() && $is_service_authenticated ) {
				$translators[] = (object) array(
					'ID'           => TranslationProxy_Service::get_wpml_translator_id( $translation_service->id ),
					'display_name' => __( 'First available', 'wpml-translation-management' ),
					'service'      => $translation_service_name
				);
			} elseif ( in_array( $translation_service_id, $services ) && $is_service_authenticated ) {
				$lang_status = TranslationProxy_Translator::get_language_pairs();
				if ( empty( $lang_status ) ) {
					$lang_status = array();
				}
				foreach ( (array) $lang_status as $language_pair ) {
					if ( $from && $from != $language_pair['from'] ) {
						continue;
					}
					if ( $to && $to != $language_pair['to'] ) {
						continue;
					}

					if ( ! empty( $language_pair['translators'] ) ) {
						if ( 1 < count( $language_pair['translators'] ) ) {
							$translators[] = (object) array(
								'ID'           => TranslationProxy_Service::get_wpml_translator_id( $translation_service->id ),
								'display_name' => __( 'First available', 'wpml-translation-management' ),
								'service'      => $translation_service_name
							);
						}
						foreach ( $language_pair['translators'] as $tr ) {
							if ( ! isset( $_icl_translators[ $tr['id'] ] ) ) {
								$translators[] = $_icl_translators[ $tr['id'] ] = (object) array(
									'ID'           => TranslationProxy_Service::get_wpml_translator_id( $translation_service->id, $tr['id'] ),
									'display_name' => $tr['nickname'],
									'service'      => $translation_service_name
								);
							}
						}
					}
				}
			}

			if ( in_array( 'local', $services ) ) {
				$translators[] = (object) array(
					'ID'           => 0,
					'display_name' => __( 'First available', 'wpml-translation-management' ),
				);
				$translators   = array_merge( $translators, TranslationManagement::get_blog_translators( array(
					'from' => $from,
					'to'   => $to
				) ) );
			}
			$translators = apply_filters( 'wpml_tm_translators_list', $translators );

			$dropdown .= '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" ' . ( $disabled ? 'disabled="disabled"' : '' ) . '>';

			if ( $default_name ) {
				$dropdown_selected = selected( $selected, false, false );
				$dropdown .= '<option value="" ' . $dropdown_selected . '>';
				$dropdown .= esc_html( $default_name );
				$dropdown .= '</option>';
			}

			foreach ( $translators as $t ) {
				if ( $local_only && isset( $t->service ) ) {
					continue;
				}
				$current_translator = $t->ID;

				$dropdown_selected = selected( $selected, $current_translator, false );
				$dropdown .= '<option value="' . $current_translator . '" ' . $dropdown_selected . '>';
				$dropdown .= esc_html( $t->display_name );
				if ( $show_service ) {
					$dropdown .= ' (';
					$dropdown .= isset( $t->service ) ? $t->service : __( 'Local', 'wpml-translation-management' );
					$dropdown .= ')';
				}
				$dropdown .= '</option>';
			}
			$dropdown .= '</select>';
		} catch ( TranslationProxy_Api_Error $ex ) {
			$dropdown .= __( 'Translation Proxy error', 'wpml-translation-management' ) . ': ' . $ex->getMessage();
		} catch ( Exception $ex ) {
			$dropdown .= __( 'Error', 'wpml-translation-management' ) . ': ' . $ex->getMessage();
		}

		if ( $add_label ) {
			$dropdown = '<label for="' . esc_attr( $id ) . '">' . __( 'Translation jobs for:', 'wpml-translation-management' ) . '</label>&nbsp;' . $dropdown;
		}

		if ( $echo ) {
			echo $dropdown;
		}

		return $dropdown;
	}
}