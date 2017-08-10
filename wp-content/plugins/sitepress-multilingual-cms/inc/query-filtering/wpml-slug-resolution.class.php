<?php

/**
 * Class WPML_Slug_Resolution
 *
 * @package    wpml-core
 * @subpackage post-translation
 */
abstract class WPML_Slug_Resolution extends WPML_WPDB_And_SP_User {

	/**
	 * Returns all active language codes ordered by the language order, but having the current language
	 * at the beginning.
	 *
	 * @return string[]
	 *
	 * @uses \SitePress::get_setting to get the languages order from the sitepress settings
	 */
	protected function get_ordered_langs() {
		$lang_order   = $this->sitepress->get_setting( 'languages_order' );
		$lang_order   = $lang_order ? $lang_order : array_keys( $this->sitepress->get_active_languages() );
		array_unshift( $lang_order, $this->sitepress->get_current_language() );

		return array_unique( $lang_order );
	}
}