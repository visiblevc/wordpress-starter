<?php

class WPML_TM_Troubleshooting_Clear_TS_UI extends WPML_Templates_Factory {

	public function get_model() {
		$ts_name = TranslationProxy::get_current_service_name();
		$model   = array(
			'strings'     => array(
				'title'   => __( 'Show other professional translation options', 'wpml-translation-management' ),
				'button'  => __( 'Enable other translation services', 'wpml-translation-management' ),
				'message' => sprintf( __( 'Your site is currently configured to use only %s as its professional translation service.', 'wpml-translation-management' ), $ts_name ),
			),
			'placeHolder' => 'wpml_clear_ts',
		);

		return $model;
	}

	public function get_template() {
		return 'clear-preferred-ts.twig';
	}

	protected function init_template_base_dir() {
		$this->template_paths = array(
			dirname( __FILE__ ) . '/../templates/troubleshooting/',
		);
	}
}