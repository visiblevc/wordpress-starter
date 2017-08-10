<?php

class WPML_Records extends WPML_WPDB_User {

	public function icl_languages_by_code( $lang_code ) {

		return new WPML_ICL_Languages( $this->wpdb, $lang_code, 'code' );
	}

	public function icl_languages_by_default_locale( $default_locale ) {

		return new WPML_ICL_Languages( $this->wpdb, $default_locale, 'default_locale' );
	}
}