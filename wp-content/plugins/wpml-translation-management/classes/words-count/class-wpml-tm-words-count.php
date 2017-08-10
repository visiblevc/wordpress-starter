<?php

class WPML_TM_Words_Count extends WPML_WPDB_And_SP_User {

	/**
	 * @var array
	 */
	private $nonPreSelectedTypes =  array( 'attachment' );
	/**
	 * @var array
	 */
	private $report;

	public function init() {
		if ( $this->sitepress->get_wp_api()->is_back_end() ) {
			add_filter( 'wpml_words_count_url', array(
				$this,
				'words_count_url_filter'
			) );
		}
	}

	public function words_count_url_filter( $default_url ) {
		return $this->get_words_count_url();
	}

	public function get_summary( $source_language, $offset ) {
		$this->report = array();
		$this->get_posts_summary( $source_language, $offset );
		if ( $offset == 0 ) {
			$this->get_strings_summary( $source_language );
		}

		return array_values( $this->report );
	}

	private function get_words_count_url() {
		return $this->sitepress->get_wp_api()->get_tm_url( 'dashboard', '#words-count' );
	}

	private function get_posts_summary( $source_language, $offset ) {
		$posts_query    = "
		SELECT  SQL_CALC_FOUND_ROWS *,
				p.post_type,
				(SELECT count(tt.trid) FROM {$this->wpdb->prefix}icl_translations tt WHERE tt.trid = t.trid) as translations
		FROM {$this->wpdb->prefix}icl_translations t
		  INNER JOIN {$this->wpdb->prefix}posts p
		    ON t.element_id = p.ID AND t.element_type = CONCAT('post_', p.post_type)
		WHERE t.language_code = %s
		GROUP BY p.ID, p.post_type
		ORDER BY p.post_type
		LIMIT %d, %d
		";
		$posts_prepared = $this->wpdb->prepare(
			$posts_query,
			$source_language,
			$offset,
			WPML_TM_WC_CHUNK
		);
		$posts          = $this->wpdb->get_results( $posts_prepared );
		$active_languages_count = $this->get_active_lang_count();
		$overall_count = $this->wpdb->get_var( "SELECT FOUND_ROWS()" );

		foreach ( $posts as $post ) {
			$post_type              = $post->post_type;
			$wpml_post              = new WPML_TM_Post( $post->ID, $this->sitepress, $this->wpdb );
			$untranslated_languages = $active_languages_count - $post->translations;
			if ( true == $this->sitepress->is_translated_post_type( $post_type ) ) {
				if ( ! isset( $this->report[ $post_type ] ) ) {
					$this->init_post_type_report( $post_type, $wpml_post );
				}
				$this->report[ $post_type ]['count']['total'] ++;
				if ( $post->translations < $active_languages_count ) {
					$this->report[ $post_type ]['count']['untranslated'] += $untranslated_languages;
				}
				$words_count = $wpml_post->get_words_count();
				$words_count = apply_filters( 'wpml_element_words_count', $words_count, array(
					'element_id'   => $post->ID,
					'element_type' => 'post_' . $post->post_type,
					'post_type'    => $post->post_type,
				) );
				$this->report[ $post_type ]['words']['total'] += $words_count;
				$this->report[ $post_type ]['words']['untranslated'] += $words_count * $untranslated_languages;
			}
		}
		array_unshift( $this->report,
			$overall_count ? ( $offset + count( $posts ) ) / $overall_count : 0 );
	}

	private function get_strings_summary( $source_language ) {
		$strings_query          = "
				SELECT
				  s.id, s.context as domain, s.gettext_context as context, s.name, s.value,
				  (SELECT count(*)
				  	FROM {$this->wpdb->prefix}icl_string_translations t
				  	WHERE t.string_id = s.id AND t.language <> s.language) as translations
				FROM {$this->wpdb->prefix}icl_strings s
				WHERE s.language = %s
				ORDER BY s.context, s.domain_name_context_md5
				";
		$strings_prepared       = $this->wpdb->prepare( $strings_query, $source_language );
		$strings                = $this->wpdb->get_results( $strings_prepared );
		$active_languages_count = $this->get_active_lang_count();

		foreach ( $strings as $string ) {
			$wpml_string = new WPML_TM_String( $string->id, $this->sitepress, $this->wpdb );
			if ( ! isset( $this->report['strings'] ) ) {
				$this->init_strings_report( $wpml_string );
			}
			$type = 'strings';
			$this->report[ $type ]['count']['total'] ++;
			$untranslated_langs_count = ( $active_languages_count - $string->translations - 1 );
			if ( $string->translations < $active_languages_count ) {
				$this->report[ $type ]['count']['untranslated'] += $untranslated_langs_count;
			}
			$element_attributes = array(
				'element_id'   => $string->id,
				'element_type' => 'string',
				'post_type'    => 'string',
			);

			$words_count = $wpml_string->get_words_count();
			$words_count = apply_filters( 'wpml_element_words_count', $words_count, $element_attributes );
			$this->report[ $type ]['words']['total'] += $words_count;
			$this->report[ $type ]['words']['untranslated'] += $words_count * $untranslated_langs_count;
		}
	}

	/**
	 * @return int number of active languages
	 */
	private function get_active_lang_count() {

			return count( $this->sitepress->get_active_languages() );
	}

	/**
	 * @param string    $post_type
	 * @param WPML_TM_Post $wpml_post
	 */
	private function init_post_type_report( $post_type, $wpml_post ) {
		$this->report[ $post_type ] = array(
			'selected' => ! in_array( $post_type, $this->nonPreSelectedTypes ),
			'type'     => $wpml_post->get_type_name( 'name' ),
			'count'    => array(
				'total'        => 0,
				'untranslated' => 0,
			),
			'words'    => array(
				'total'        => 0,
				'untranslated' => 0,
			),
		);
	}

	/**
	 * @param WPML_TM_String $wpml_string
	 */
	private function init_strings_report( $wpml_string ) {
		$this->report[ 'strings' ] = array(
			'selected' => true,
			'type'     => $wpml_string->get_type_name(),
			'count'    => array(
				'total'        => 0,
				'untranslated' => 0,
			),
			'words'    => array(
				'total'        => 0,
				'untranslated' => 0,
			),
		);
	}
}