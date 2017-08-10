<?php

/**
 * Class WPML_Compatibility_2017
 *
 * # Compatbility class for 2017 theme
 *
 * ## Why is this needed?
 *
 * When configuring 2017 to use a static page, you can define sections in these pages.
 * Each section is another page and the value is stored with the ID of that page.
 * In order to display the sections in the current language, WPML needs to know the IDs of the translated pages.
 *
 * ## How this works?
 *
 * WPML tries to retrieve the number of Frontpage panels and, for each of them, will add a filter to translate the ID with the one in the current language, if any.
 *
 * This class is loaded and instantiated by `plugins-integration.php` only if the `twentyseventeen_panel_count` function exists and the `twentyseventeen_translate_panel_id` does not.
 */
class WPML_Compatibility_2017 {
	function init_hooks() {
		$num_sections = twentyseventeen_panel_count();

		for ( $i = 1; $i <= $num_sections; $i ++ ) {
			/**
			 * @see  `get_theme_mod` documentation
			 * @link https://codex.wordpress.org/Function_Reference/get_theme_mod
			 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/theme_mod_$name
			 */
			add_filter( 'theme_mod_panel_' . $i, array( $this, 'get_translated_panel_id' ) );
		}
	}
	
	function get_translated_panel_id( $id ) {
		/**
		 * Get the translated ID of the given page using the `wpml_object_id` filter and returns the original if the translation is missing
		 * @see https://wpml.org/wpml-hook/wpml_object_id/
		 */
		return apply_filters( 'wpml_object_id', $id, 'page', true );
	}
}
