<?php

/**
 * @package wpml-core
 * @subpackage wpml-user-language
 */
class WPML_User_Language {
	/** @var  SitePress $sitepress */
	protected $sitepress;

	private $language_changes_history       = array();
	private $admin_language_changes_history = array();
	private $language_switched              = false;

	/**
	 * WPML_User_Language constructor.
	 *
	 * @param SitePress $sitepress
	 * @param wpdb|null $wpdb
	 */
	public function __construct( SitePress $sitepress, wpdb $wpdb = null ) {
		$this->sitepress = $sitepress;

		if ( ! $wpdb ) {
			global $wpdb;
		}
		$this->wpdb = $wpdb;

		$this->language_changes_history[] = $sitepress->get_current_language();
		$this->admin_language_changes_history[] = $this->sitepress->get_admin_language();

		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'wpml_switch_language_for_email', array( $this, 'switch_language_for_email_action' ), 10, 1 );
		add_action( 'wpml_restore_language_from_email', array( $this, 'restore_language_from_email_action' ), 10, 0 );
		add_action( 'profile_update', array( $this, 'sync_admin_user_language_action' ), 10, 2 );
		add_action( 'wpml_language_cookie_added', array( $this, 'update_user_lang_on_cookie_update' ) );

		if ( $this->is_editing_current_profile() || $this->is_editing_other_profile() ) {
			add_filter( 'get_available_languages', array( $this, 'intersect_wpml_wp_languages' ) );
		}

		register_activation_hook( WP_PLUGIN_DIR . '/' . ICL_PLUGIN_FOLDER . '/sitepress.php',  array( $this, 'update_user_lang_on_site_setup' ) );
	}

	/**
	 * @param array $wp_languages
	 *
	 * @return array
	 */
	public function intersect_wpml_wp_languages( $wp_languages ) {
		$active_wpml_languages = wp_list_pluck( $this->sitepress->get_active_languages(), 'default_locale' );

		return array_intersect( $active_wpml_languages, $wp_languages );
	}

	/**
	 * @param string $email
	 */
	public function switch_language_for_email_action( $email ) {
		$this->switch_language_for_email( $email );
	}

	/**
	 * @param string $email
	 */
	private function switch_language_for_email( $email ) {
		$language = apply_filters( 'wpml_user_language', null, $email );

		if ( $language ) {
			$this->language_switched                = true;
			$this->language_changes_history[]       = $language;
			$this->admin_language_changes_history[] = $language;

			$this->sitepress->switch_lang( $language, true );

			$this->sitepress->set_admin_language( $language );
		}
	}

	public function restore_language_from_email_action() {
		$this->wpml_restore_language_from_email();
	}

	private function wpml_restore_language_from_email() {
		if ( $this->language_switched ) {
			$this->language_switched = false;

			$this->sitepress->switch_lang( $this->language_changes_history[0], true );

			$this->sitepress->set_admin_language( $this->admin_language_changes_history[0] );
		}
	}

	/**
	 * @param int $user_id
	 */
	public function sync_admin_user_language_action( $user_id ) {
		if ( $this->user_needs_sync_admin_lang() ) {
			$this->sync_admin_user_language( $user_id );
		}
	}

	public function sync_default_admin_user_languages() {
		$sql_users   = 'SELECT user_id FROM ' . $this->wpdb->usermeta . ' WHERE meta_key = %s AND meta_value = %s';
		$query_users = $this->wpdb->prepare( $sql_users, array( 'locale', '' ) );
		$user_ids    = $this->wpdb->get_col( $query_users );

		if ( $user_ids ) {
			$language = $this->sitepress->get_default_language();

			$sql   = 'UPDATE ' . $this->wpdb->usermeta . ' SET meta_value = %s WHERE meta_key = %s and user_id IN (' . wpml_prepare_in( $user_ids ) . ')';
			$query = $this->wpdb->prepare( $sql, array( $language, 'icl_admin_language' ) );

			$this->wpdb->query( $query );
		}
	}

	/**
	 * @param int $user_id
	 */
	private function sync_admin_user_language( $user_id ) {
		$wp_language = get_user_meta( $user_id, 'locale', true );

		if ( $wp_language ) {
			$user_language = $this->sitepress->get_language_code_from_locale( $wp_language );
		} else {
			$user_language = $this->sitepress->get_default_language();
		}
		update_user_meta( $user_id, 'icl_admin_language', $user_language );

		if( $this->user_admin_language_for_edit( $user_id ) && $this->is_editing_current_profile() ) {
			$this->set_language_cookie( $user_language );
		}
	}

	private function user_needs_sync_admin_lang() {
		$wp_api = $this->sitepress->get_wp_api();
		return $wp_api->version_compare_naked( get_bloginfo( 'version' ), '4.7', '>=' );
	}

	private function set_language_cookie( $user_language ) {
		global $wpml_request_handler;

		if( is_object( $wpml_request_handler ) ) {
			$wpml_request_handler->set_language_cookie( $user_language );
		}
	}

	/**
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	private function user_admin_language_for_edit( $user_id ) {
		return get_user_meta( $user_id, 'icl_admin_language_for_edit', true );
	}

	/**
	 * @param string $lang
	 */
	public function update_user_lang_on_cookie_update( $lang ) {
		$user_id = get_current_user_id();
		$wp_lang = $this->sitepress->get_language_details( $lang );

		if( $this->user_needs_sync_admin_lang() && $user_id && $this->user_admin_language_for_edit( $user_id ) ) {
			update_user_meta( $user_id, 'icl_admin_language', $lang );
			update_user_meta( $user_id, 'locale', $wp_lang['default_locale'] );
		}
	}

	private function is_editing_current_profile() {
		global $pagenow;
		return isset( $pagenow ) && 'profile.php' === $pagenow;
	}

	private function is_editing_other_profile() {
		global $pagenow;
		return isset( $pagenow ) && 'user-edit.php' === $pagenow;
	}

	public function update_user_lang_on_site_setup() {
		$current_user_id = get_current_user_id();
		$site_locale = get_locale();
		$wpml_lang = $this->sitepress->get_language_code_from_locale( $site_locale );

		$wp_user_lang = get_user_meta( $current_user_id, 'locale', true );
		$wpml_user_lang = get_user_meta( $current_user_id, 'icl_admin_language', true );

		if ( $site_locale && $current_user_id && ! $wp_user_lang && ! $wpml_user_lang ) {
			update_user_meta( $current_user_id, 'locale', $site_locale );
			update_user_meta( $current_user_id, 'icl_admin_language', $wpml_lang );
		}
	}
}
