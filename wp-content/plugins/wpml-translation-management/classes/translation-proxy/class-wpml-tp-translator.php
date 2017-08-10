<?php

/**
 * Class WPML_TP_Translator
 */
class WPML_TP_Translator {

	/**
	 * Return translator status array.
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	public function get_icl_translator_status( $force = false ) {
		return TranslationProxy_Translator::get_icl_translator_status( $force );
	}
}
