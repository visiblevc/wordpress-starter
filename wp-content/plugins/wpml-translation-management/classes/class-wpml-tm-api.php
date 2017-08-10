<?php

class WPML_TM_API {

	/** @var TranslationManagement */
	private $TranslationManagement;

	/** @var WPML_TM_Blog_Translators $blog_translators */
	private $blog_translators;

	/**
	 * WPML_TM_API constructor.
	 *
	 * @param WPML_TM_Blog_Translators $blog_translators
	 * @param TranslationManagement    $TranslationManagement
	 */
	public function __construct( &$blog_translators, &$TranslationManagement ) {
		$this->blog_translators      = &$blog_translators;
		$this->TranslationManagement = &$TranslationManagement;

		$this->translation_statuses = array(
			ICL_TM_NOT_TRANSLATED         => array(
				'label'         => __( 'Not translated', 'wpml-translation-management' ),
				'css-class'     => 'icon otgs-ico-not-translated',
			),
			ICL_TM_WAITING_FOR_TRANSLATOR => array(
				'label'         => __( 'Waiting for translator', 'wpml-translation-management' ),
				'css-class'     => 'icon otgs-ico-waiting',
				'default_color' => '#0000'
			),
			ICL_TM_IN_BASKET              => array(
				'label'         => __( 'In basket', 'wpml-translation-management' ),
				'css-class'     => 'icon otgs-ico-basket',
			),
			ICL_TM_IN_PROGRESS            => array(
				'label'         => __( 'In progress', 'wpml-translation-management' ),
				'css-class'     => 'icon otgs-ico-in-progress',
			),
			ICL_TM_DUPLICATE              => array(
				'label'         => __( 'Duplicate', 'wpml-translation-management' ),
				'css-class'     => 'icon otgs-ico-duplicate',
			),
			ICL_TM_COMPLETE               => array(
				'label'         => __( 'Complete', 'wpml-translation-management' ),
				'css-class'     => 'icon otgs-ico-translated',
			),
			ICL_TM_NEEDS_UPDATE           => array(
				'label'         => ' - ' . __( 'needs update', 'wpml-translation-management' ),
				'css-class'     => 'icon otgs-ico-needs-update',
			),
		);
	}

	public function get_translation_status_label( $value ) {
		return isset( $this->translation_statuses[ $value ] ) ? $this->translation_statuses[ $value ][ 'label' ] : null;
	}

	public function init_hooks() {
		add_filter( 'wpml_is_translator', array( $this, 'is_translator_filter' ), 10, 3 );
		add_filter( 'wpml_translator_languages_pairs', array( $this, 'translator_languages_pairs_filter' ), 10, 2 );
		add_action( 'wpml_edit_translator', array( $this, 'edit_translator_action' ), 10, 2 );
	}

	/**
	 * @param bool        $default
	 * @param int|WP_User $user
	 * @param array       $args
	 *
	 * @return bool
	 */
	public function is_translator_filter( $default, $user, $args ) {
		$result  = $default;
		$user_id = $this->get_user_id( $user );
		if ( is_numeric( $user_id ) ) {
			$result = $this->blog_translators->is_translator( $user_id, $args );
		}

		return $result;
	}

	public function edit_translator_action( $user, $language_pairs ) {
		$user_id = $this->get_user_id( $user );
		if ( is_numeric( $user_id ) ) {
			$this->TranslationManagement->edit_translator( $user_id, $language_pairs );
		}
	}
	
	public function translator_languages_pairs_filter( $default, $user ) {
		$result  = $default;
		$user_id = $this->get_user_id( $user );
		if ( is_numeric( $user_id ) ) {
			if ( $this->blog_translators->is_translator( $user_id ) ) {
				$result = $this->blog_translators->get_language_pairs( $user_id );
			}
		}

		return $result;
	}

	/**
	 * @param $user
	 *
	 * @return int
	 */
	private function get_user_id( $user ) {
		$user_id = $user;

		if ( is_a( $user, 'WP_User' ) ) {
			$user_id = $user->ID;

			return $user_id;
		}

		return $user_id;
	}

}