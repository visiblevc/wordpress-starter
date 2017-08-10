<?php

class WPML_Integrations_Requirements_Scripts {

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'wpml-integrations-requirements-scripts', ICL_PLUGIN_URL . '/res/js/requirements/integrations-requirements.js', array( 'jquery' ) );
	}
}
