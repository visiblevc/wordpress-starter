<?php

class WPML_Upgrade_Admin_Users_Languages {

	private $sitepress;

	const ICL_ADMIN_LANGUAGE_MIGRATED_TO_WP_47 = 'icl_admin_language_migrated_to_wp47';

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
		$this->add_hooks();
	}

	public function add_hooks() {
		add_action( 'admin_init', array( $this, 'run' ) );
	}

	public function run() {
		$user_id                 = get_current_user_id();
		$wpml_user_lang          = get_user_meta( $user_id, 'icl_admin_language', true );
		$wpml_user_lang_migrated = get_user_meta( $user_id, self::ICL_ADMIN_LANGUAGE_MIGRATED_TO_WP_47, false );
		$wpml_lang_details       = $this->sitepress->get_language_details( $wpml_user_lang );
		$wpml_user_locale        = $wpml_lang_details['default_locale'];
		$wp_user_locale          = get_user_meta( $user_id, 'locale', true );

		if ( ! $wpml_user_lang_migrated ) {
			if ( $wpml_user_locale && $wpml_user_locale !== $wp_user_locale ) {
				update_user_meta( $user_id, 'locale', $wpml_user_locale );
			}

			update_user_meta( $user_id, self::ICL_ADMIN_LANGUAGE_MIGRATED_TO_WP_47, true );
		}
	}
}