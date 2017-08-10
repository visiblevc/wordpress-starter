<?php

class WPML_String_Translation_Job_Email_Notification implements IWPML_String_Translation_Job_Notification {
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var WPML_TM_Blog_Translators
	 */
	private $tm_blog_translators;

	/**
	 * @var WP_User[]
	 */
	private $site_translators = null;

	/**
	 * @param SitePress $sitepress
	 * @param wpdb $wpdb
	 * @param WPML_TM_Blog_Translators $tm_blog_translators
	 */
	public function __construct( SitePress $sitepress, wpdb $wpdb, WPML_TM_Blog_Translators $tm_blog_translators ) {
		$this->sitepress = $sitepress;
		$this->wpdb = $wpdb;
		$this->tm_blog_translators = $tm_blog_translators;
	}

	/**
	 * @param string $source_lang
	 * @param string $target_lang
	 * @param null $translator_id
	 */
	public function notify( $source_lang, $target_lang, $translator_id = null ) {
		if ( $translator_id ) {
			$translators = array( $this->get_user_by_id( $translator_id ) );
		} else {
			$translators = $this->get_site_translators();
		}

		foreach ( $translators as $translator ) {
			if ( $this->translator_has_language_pair( $translator, $source_lang, $target_lang ) ) {
				$this->notify_user( $translator, $source_lang, $target_lang );
			}
		}
	}

	/**
	 * @param int $user_id
	 *
	 * @return WP_User
	 */
	protected function get_user_by_id( $user_id ) {
		return new WP_User( $user_id );
	}

	/**
	 * @param WP_User $user
	 * @param string $source_lang
	 * @param string $target_lang
	 */
	private function notify_user( WP_User $user, $source_lang, $target_lang ) {
		$lang_details = $this->sitepress->get_language_details( $source_lang );
		$source_en = $lang_details['english_name'];

		$lang_details = $this->sitepress->get_language_details( $target_lang );
		$target_en = $lang_details['english_name'];

		$recipient      = $user->user_email;
		$subject = $this->prepare_subject();
		$body    = $this->prepare_body( $source_en, $target_en );

		wp_mail( $recipient, $subject, $body );
	}

	private function prepare_subject() {
		return sprintf(
			__( 'You have been assigned to a new translation job on %s.', 'wpml-translation-management' ),
			get_bloginfo( 'name' )
		);
	}

	private function prepare_body( $source_en, $target_en ) {
		$message = __(
			'
You have been assigned to a new translation job from %s to %s.

Start editing: %s

You can view your other translation jobs here: %s

This message was automatically sent by Translation Management running on WPML. To stop receiving these notifications contact the system administrator at %s.

This email is not monitored for replies.
			',
			'wpml-translation-management'
		);

		return sprintf( $message,
			$source_en,
			$target_en,
			admin_url( 'admin.php?page=' . $this->sitepress->get_wp_api()->constant( 'WPML_ST_FOLDER' ) . '/menu/string-translation.php' ),
			admin_url( 'admin.php?page=' . $this->sitepress->get_wp_api()->constant( 'WPML_TM_FOLDER' ) . '/menu/translations-queue.php' ),
			home_url()
		);
	}

	/**
	 * @return WP_User[]
	 */
	private function get_site_translators() {
		if ( null === $this->site_translators ) {
			$this->site_translators = $this->tm_blog_translators->get_raw_blog_translators();
			$this->site_translators = array_filter( $this->site_translators, array( $this, 'validate_site_translator' ) );
		}

		return $this->site_translators;
	}

	/**
	 * @param mixed $translator
	 *
	 * @return bool
	 */
	private function validate_site_translator( $translator ) {
		return $translator instanceof WP_User;
	}

	/**
	 * @param WP_User $translator
	 * @param string $source_lang
	 * @param string $target_lang
	 *
	 * @return bool
	 */
	private function translator_has_language_pair( $translator, $source_lang, $target_lang ) {
		$lang_pairs_key  = $this->wpdb->prefix . 'language_pairs';
		$language_pairs = isset( $translator->$lang_pairs_key ) ? $translator->$lang_pairs_key : false;

		return isset( $language_pairs[ $source_lang ][ $target_lang ] );
	}
}