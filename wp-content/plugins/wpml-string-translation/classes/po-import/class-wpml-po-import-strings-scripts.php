<?php

class WPML_PO_Import_Strings_Scripts {

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts( $page_hook ) {
		if ( WPML_ST_FOLDER . '/menu/string-translation.php' === $page_hook ) {
			wp_enqueue_script( 'wpml-st-strings-json-import-po', WPML_ST_URL . '/res/js/strings_json_import_po.js', array( 'jquery' ), WPML_ST_VERSION );
		}
	}
}