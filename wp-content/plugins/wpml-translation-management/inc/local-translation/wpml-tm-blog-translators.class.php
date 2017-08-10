<?php

class WPML_TM_Blog_Translators {

	/** @var WPML_TM_Records $tm_records */
	private $tm_records;

	/**
	 * @var SitePress;
	 */
	private $sitepress;

	/**
	 * @param SitePress       $sitepress
	 * @param WPML_TM_Records $tm_records
	 */
	public function __construct( $sitepress, $tm_records ) {
		$this->sitepress = $sitepress;
		$this->tm_records = $tm_records;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function get_blog_translators( $args = array() ) {
		$translators = TranslationManagement::get_blog_translators( $args );
		foreach ( $translators as $key => $user ) {
			$translators[ $key ] = isset( $user->data ) ? $user->data : $user;
		}

		return $translators;
	}

	/**
	 * @return array
	 */
	public function get_raw_blog_translators() {
		return TranslationManagement::get_blog_translators();
	}

	/**
	 * @param int   $user_id
	 * @param array $args
	 *
	 * @return bool
	 */
	function is_translator( $user_id, $args = array() ) {
		$admin_override = true;
		extract( $args, EXTR_OVERWRITE );
		$is_translator = $this->sitepress->get_wp_api()
		                                 ->user_can( $user_id, 'translate' );
		// check if user is administrator and return true if he is
		if ( $admin_override && $this->sitepress->get_wp_api()
		                                        ->user_can( $user_id, 'manage_options' )
		) {
			$is_translator = true;
		} else {
			if ( isset( $lang_from ) && isset( $lang_to ) ) {
				$user_language_pairs            = $this->get_language_pairs( $user_id );
				if ( ! empty( $user_language_pairs ) ) {
					foreach ( $user_language_pairs as $user_lang_from => $user_lang_to ) {
						if ( array_key_exists( $lang_to, $user_lang_to ) ) {
							$is_translator = true;
							break;
						}
					}
				} else {
					$is_translator = false;
				}
			}
			if ( isset( $job_id ) ) {
				$job_record    = $this->tm_records->icl_translate_job_by_job_id( $job_id );
				$translator_id = in_array( $job_record->service(), array(
					'local',
					0
				) ) ? $job_record->translator_id() : - 1;
				$is_translator = $translator_id == $user_id
				                 || ( $is_translator && empty( $translator_id ) );
			}
		}

		return apply_filters( 'wpml_override_is_translator', $is_translator, $user_id, $args );
	}

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function get_language_pairs( $user_id ) {

		return $this->sitepress->get_wp_api()
		                       ->get_user_meta(
			                       $user_id,
			                       $this->sitepress->wpdb()->prefix . 'language_pairs',
			                       true );
	}
}