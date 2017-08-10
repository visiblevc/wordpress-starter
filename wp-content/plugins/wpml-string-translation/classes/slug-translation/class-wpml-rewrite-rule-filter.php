<?php

class WPML_Rewrite_Rule_Filter extends WPML_WPDB_And_SP_User {

	function rewrite_rules_filter( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}
		
		$current_language               = $this->sitepress->get_current_language();
		$queryable_post_types           = get_post_types( array( 'publicly_queryable' => true ) );
		$post_slug_translation_settings = $this->sitepress->get_setting( 'posts_slug_translation', array() );

		foreach ( $queryable_post_types as $type ) {
			if ( ! isset( $post_slug_translation_settings['types'][ $type ] ) || ! $post_slug_translation_settings['types'][ $type ] || ! $this->sitepress->is_translated_post_type( $type ) ) {
				continue;
			}
			$slug = $this->get_slug_by_type( $type );
			if ( $slug === false ) {
				continue;
			}

			$slug_translation = $this->wpdb->get_var( $this->wpdb->prepare( "
						SELECT t.value
						FROM {$this->wpdb->prefix}icl_string_translations t
							JOIN {$this->wpdb->prefix}icl_strings s ON t.string_id = s.id
						WHERE t.language = %s AND s.name = %s AND t.status = %d
					",
				$current_language,
				'URL slug: ' . $type,
				ICL_TM_COMPLETE ) );
			if ( ! $slug_translation ) {
				// check original
				$slug_translation = $this->wpdb->get_var( $this->wpdb->prepare( "
						SELECT value
						FROM {$this->wpdb->prefix}icl_strings
						WHERE language = %s AND name = %s
					",
					$current_language,
					'URL slug: ' . $type
				) );

			}
			$using_tags = false;
			/* case of slug using %tags% - PART 1 of 2 - START */
			if ( preg_match( '#%([^/]+)%#', $slug ) ) {
				$slug       = preg_replace( '#%[^/]+%#', '.+?', $slug );
				$using_tags = true;
			}
			if ( preg_match( '#%([^/]+)%#', $slug_translation ) ) {
				$slug_translation = preg_replace( '#%[^/]+%#', '.+?', $slug_translation );
				$using_tags       = true;
			}
			/* case of slug using %tags% - PART 1 of 2 - END */

			$buff_value = array();
			foreach ( (array) $value as $k => $v ) {

				if ( $slug && $slug != $slug_translation ) {
					$k = $this->adjust_key( $k, $slug_translation, $slug );
				}
				$buff_value[ $k ] = $v;
			}

			$value = $buff_value;
			unset( $buff_value );

			/* case of slug using %tags% - PART 2 of 2 - START */
			if ( $using_tags ) {
				if ( preg_match( '#\.\+\?#', $slug ) ) {
					$slug = preg_replace( '#\.\+\?#', '(.+?)', $slug );
				}
				if ( preg_match( '#\.\+\?#', $slug_translation ) ) {
					$slug_translation = preg_replace( '#\.\+\?#', '(.+?)', $slug_translation );
				}
				$buff_value = array();
				foreach ( $value as $k => $v ) {
					if ( trim( $slug ) && trim( $slug_translation ) && $slug != $slug_translation ) {
						$k = $this->adjust_key( $k, $slug_translation, $slug );
					}
					$buff_value[ $k ] = $v;
				}

				$value = $buff_value;
				unset( $buff_value );
			}
			/* case of slug using %tags% - PART 2 of 2 - END */
		}

		return $value;
	}

	function get_slug_by_type( $type ) {
		$slug_translation = $this->wpdb->get_var( $this->wpdb->prepare( "
														SELECT value
														FROM {$this->wpdb->prefix}icl_strings
														WHERE name = %s ",
			'URL slug: ' . $type
		) );

		return $slug_translation;
	}

	private function adjust_key( $k, $slug_translation, $slug ) {
		if ( (bool) $slug_translation === true && preg_match( '#^[^/]*/?' . preg_quote( $slug ) . '/#',
				$k ) && $slug != $slug_translation
		) {
			$k = preg_replace( '#^' . addslashes($slug) . '/#', $slug_translation . '/', $k );
		}

		return $k;
	}
}