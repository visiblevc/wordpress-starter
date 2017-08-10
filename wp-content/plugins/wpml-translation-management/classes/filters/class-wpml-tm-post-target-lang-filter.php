<?php

class WPML_TM_Post_Target_Lang_Filter extends WPML_TM_Record_User {

	/** @var  WPML_TM_Translation_Status */
	private $tm_status;

	/** @var  WPML_Post_Translation $post_translations */
	private $post_translations;

	public function __construct(
		&$tm_records,
		&$tm_status,
		&$post_translations
	) {
		parent::__construct( $tm_records );
		$this->tm_status         = &$tm_status;
		$this->post_translations = &$post_translations;
	}

	/**
	 * @param string[] $allowed_langs
	 * @param int      $element_id
	 * @param string   $element_type_prefix
	 *
	 * @return string[]
	 */
	public function filter_target_langs(
		$allowed_langs,
		$element_id,
		$element_type_prefix
	) {
		if ( TranslationProxy_Basket::anywhere_in_basket( $element_id,
			$element_type_prefix )
		) {
			$allowed_langs = array();
		} elseif ( $element_type_prefix === 'post' ) {
			if ( (bool) ( $this->post_translations->get_element_lang_code( $element_id ) ) === true ) {
				$allowed_langs = array_fill_keys( $allowed_langs, 1 );
				$translations  = $this
					->tm_records
					->icl_translations_by_element_id_and_type_prefix(
						$element_id, 'post' )
					->translations();
				foreach ( $translations as $lang_code => $element ) {
					if ( isset( $allowed_langs[ $lang_code ] )
					     && ( ( $element->element_id() && ! $element->source_language_code() )
					          || $this->tm_status->is_in_active_job( $element_id,
								$lang_code,
								'post' ) )
					) {
						unset( $allowed_langs[ $lang_code ] );
					}
				}
				$allowed_langs = array_keys( $allowed_langs );
			}
		}

		return $allowed_langs;
	}
}