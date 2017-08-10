<?php

class WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI extends WPML_Templates_Factory {

	protected function init_template_base_dir() {
		$this->template_paths = array(
			dirname( __FILE__ ) . '/../templates/troubleshooting',
		);
	}

	public function get_model() {
		$translation_service_name = TranslationProxy::get_current_service_name();

		$alert_1 = 'Use this feature when you want to reset your translation process. All your existing translations will remain unchanged. Any translation work that is currently in progress will be stopped.';
		$alert_2 = '';

		if ( ! $translation_service_name ) {
			$translation_service_name = 'PRO';
			$alert_2                  = 'Only select this option if you have no pending jobs or you are sure of what you are doing.';
		} else {
			if ( ! TranslationProxy::has_preferred_translation_service() ) {
				$alert_2 = 'If you have sent content to %1$s, you should cancel the projects in %1$s system.';
			}
			$alert_2 .= 'Any work that completes after you do this reset cannot be received by your site.';
		}
		$model = array(
			'strings'     => array(
				'title'         => __( 'Reset professional translation state', 'wpml-translation-management' ),
				'alert1'        => sprintf( __( $alert_1, 'wpml-translation-management' ), $translation_service_name ),
				'alert2'        => sprintf( __( $alert_2, 'wpml-translation-management' ), $translation_service_name ),
				'checkBoxLabel' => sprintf( __( 'I am about to stop any ongoing work done by %1$s.', 'wpml-translation-management' ), $translation_service_name ),
				'button'        => __( 'Reset professional translation state', 'wpml-translation-management' ),
			),
			'placeHolder' => 'icl_reset_pro',
		);

		return $model;
	}

	public function get_template() {
		return 'reset-pro-trans-config.twig';
	}
}