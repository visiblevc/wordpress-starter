<?php

/**
 * Class WPML_Languages
 *
 * @package wpml-core
 */
class WPML_Languages extends WPML_SP_And_PT_User {

	/** @var  WPML_Term_Translation $term_translation */
	private $term_translation;

	/** @var WPML_Query_Utils $query_utils */
	private $query_utils;

	/**
	 * @param WPML_Term_Translation $term_translation
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translation
	 */
	public function __construct( &$term_translation, &$sitepress, &$post_translation ) {
		parent::__construct( $post_translation, $sitepress );
		$this->term_translation = &$term_translation;
		$this->query_utils      = $sitepress->get_query_utils();
	}

	/**
	 * @param WP_Query $wp_query
	 * @param WP_Query $_wp_query_back
	 * @param WP_Query $saved_query
	 *
	 * @return array
	 */
	public function get_ls_translations( $wp_query, $_wp_query_back, $saved_query ) {
		list( $taxonomy, $term_id ) = $this->extract_tax_archive_data( $wp_query );
		if ( $taxonomy && $term_id ) {
			if ( $this->sitepress->is_translated_taxonomy( $taxonomy ) ) {
				$icl_taxonomy = 'tax_' . $taxonomy;
				$trid         = $this->term_translation->trid_from_tax_and_id( $term_id, $taxonomy );
				$translations = $this->sitepress->get_element_translations( $trid, $icl_taxonomy, false );
			} else {
				$translations[ $this->sitepress->get_current_language() ] = (object) array(
					'translation_id' => 0,
					'language_code'  => $this->sitepress->get_default_language(),
					'original'       => 1,
					'name'           => $taxonomy,
					'term_id'        => $term_id
				);
			}
		} elseif ( $wp_query->is_archive() && ! empty( $wp_query->posts ) ) {
			$translations = array();
		} elseif ( $wp_query->is_attachment() ) {
			$trid         = $this->post_translation->get_element_trid( $wp_query->get_queried_object_id() );
			$translations = $this->sitepress->get_element_translations( $trid, 'post_attachment' );
		} elseif ( $wp_query->is_page()
		           || ( 'page' === $this->sitepress->get_wp_api()->get_option( 'show_on_front' )
		                && ( isset( $saved_query->queried_object_id )
		                     && $saved_query->queried_object_id == $this->sitepress->get_wp_api()->get_option( 'page_on_front' )
		                     || ( isset( $saved_query->queried_object_id )
		                          && $saved_query->queried_object_id == $this->sitepress->get_wp_api()->get_option( 'page_for_posts' ) ) ) )
		) {
			$trid         = $this->sitepress->get_element_trid( $wp_query->get_queried_object_id(), 'post_page' );
			$translations = $this->sitepress->get_element_translations( $trid, 'post_page' );
		} elseif ( $wp_query->is_singular() && ! empty( $wp_query->posts )
		           || ( isset( $_wp_query_back->query['name'] ) && isset( $_wp_query_back->query['post_type'] ) )
		           || isset( $_wp_query_back->query['p'] )
		) {
			$pid          = ! empty( $saved_query->post->ID ) ? $saved_query->post->ID : ( ! empty( $saved_query->query['p'] ) ? $saved_query->query['p'] : 0 );
			$trid         = $this->post_translation->get_element_trid( $pid );
			$post_type    = get_post_type( $pid );
			$translations = $this->sitepress->get_element_translations( $trid, 'post_' . $post_type );
		} else {
			$wp_query->is_singular = false;
			$wp_query->is_archive  = false;
			$wp_query->is_category = false;
			$wp_query->is_404      = true;
			$translations          = null;
		}

		$translations = apply_filters( 'wpml_get_ls_translations', $translations, $wp_query );
		return array( $translations, $wp_query );
	}

	/**
	 * @param array  $lang
	 * @param array  $translations
	 * @param bool   $icl_lso_link_empty
	 * @param bool   $skip_lang
	 * @param string $link_empty_to
	 *
	 * @return array
	 */
	public function add_tax_url_to_ls_lang( $lang, $translations, $icl_lso_link_empty, $skip_lang, $link_empty_to ) {
		if ( isset( $translations[ $lang['code'] ] ) ) {
			// force  the taxonomy id adjustment to not modify this
			$queried_object = $this->sitepress->get_wp_api()->get_queried_object();
			$taxonomy       = isset( $queried_object->taxonomy ) ? $queried_object->taxonomy : null;

			if ( $taxonomy ) {
				$lang['translated_url'] = $this->sitepress->get_wp_api()
				                                          ->get_term_link( (int) $translations[ $lang['code'] ]->term_id, $taxonomy );
				$lang['missing']        = 0;
			}
		}

		if ( ! isset( $translations[ $lang['code'] ] ) || ! isset( $taxonomy ) ) {

			$args = array(
				'skip_lang'        => $skip_lang,
				'link_empty'       => $icl_lso_link_empty,
				'override_missing' => false,
				'link_empty_to'    => $link_empty_to,
			);

			list( $lang, $skip_lang ) = $this->maybe_mark_lang_missing( $lang, $args );
		}

		return array( $lang, $skip_lang );
	}

	/**
	 * @param array          $lang
	 * @param object|WP_User $author_data
	 * @param bool           $icl_lso_link_empty
	 * @param bool           $skip_lang
	 * @param bool           $link_empty_to
	 *
	 * @return array
	 */
	public function add_author_url_to_ls_lang( $lang, $author_data, $icl_lso_link_empty, $skip_lang, $link_empty_to ) {
		$post_type = get_query_var( 'post_type' ) ? get_query_var( 'post_type' ) : 'post';
		if ( $this->query_utils->author_query_has_posts( $post_type, $author_data, $lang['code'] ) ) {
			$lang['translated_url'] = $this->sitepress->convert_url( $this->sitepress->get_wp_api()->get_author_posts_url( $author_data->ID ), $lang['code'] );
			$lang['missing']        = 0;
		} else {

			$args = array(
				'skip_lang'        => $skip_lang,
				'link_empty'       => $icl_lso_link_empty,
				'override_missing' => false,
				'link_empty_to'    => $link_empty_to,
			);

			list( $lang, $skip_lang ) = $this->maybe_mark_lang_missing( $lang, $args );
		}

		return array( $lang, $skip_lang );
	}

	/**
	 * @param array    $lang
	 * @param WP_Query $current_query
	 * @param bool     $icl_lso_link_empty
	 * @param bool     $skip_lang
	 * @param string   $link_empty_to
	 *
	 * @return array
	 */
	public function add_date_or_cpt_url_to_ls_lang( $lang, $current_query, $icl_lso_link_empty, $skip_lang, $link_empty_to ) {
		list( $year, $month, $day ) = $this->extract_date_data_from_query( $current_query );
		$query_helper = new WPML_WP_Query_API( $current_query );
		$post_type    = ( $_type = $query_helper->get_post_type_if_single() ) ? $_type : 'post';
		$lang_code    = $lang['code'];
		$mark_missing = false;
		$override     = false;
		if ( $current_query->is_year() && $this->query_utils->archive_query_has_posts( $lang_code,
		                                                                            $year,
		                                                                            null,
		                                                                            null,
		                                                                            $post_type )
		) {
			$date_archive_url = $this->sitepress->get_wp_api()->get_year_link( $year );
		} elseif ( $current_query->is_month() && $this->query_utils->archive_query_has_posts( $lang_code,
		                                                                                   $year,
		                                                                                   $month,
		                                                                                   null,
		                                                                                   $post_type )
		) {
			$date_archive_url = $this->sitepress->get_wp_api()->get_month_link( $year, $month );
		} elseif ( $current_query->is_day() && $this->query_utils->archive_query_has_posts( $lang_code, $year, $month, $day, $post_type ) ) {
			$date_archive_url = $this->sitepress->get_wp_api()->get_day_link( $year, $month, $day );
		} else if ( ! empty( $current_query->query_vars['post_type'] ) ) {
			$override     = ! $this->sitepress->is_translated_post_type( $post_type );
			$mark_missing = true;
			if ( ! $override && $this->query_utils->archive_query_has_posts( $lang_code, null, null, null, $post_type ) ) {
				$url                    = $this->sitepress->convert_url( $this->sitepress->get_wp_api()->get_post_type_archive_link( $post_type ), $lang_code );
				$lang['translated_url'] = $this->sitepress->adjust_cpt_in_url( $url, $post_type, $lang_code );
				$mark_missing           = false;
			}
		} else {
			$mark_missing = true;
		}

		if ( $mark_missing ) {

			$args = array(
				'skip_lang'        => $skip_lang,
				'link_empty'       => $icl_lso_link_empty,
				'override_missing' => $override,
				'link_empty_to'    => $link_empty_to,
			);

			list( $lang, $skip_lang ) = $this->maybe_mark_lang_missing( $lang, $args );

		} elseif ( isset( $date_archive_url ) ) {
			$lang['translated_url'] = $this->sitepress->convert_url( $date_archive_url, $lang_code );
		}

		return array( $lang, $skip_lang );
	}

	public function get_ls_language( $lang_code, $current_language, $language_array = false ) {
		$ls_language = $language_array
			? $language_array : $this->sitepress->get_language_details( $lang_code );
		$native_name = $this->sitepress->get_display_language_name( $lang_code, $lang_code );
		if ( ! $native_name ) {
			$native_name = $ls_language['english_name'];
		}
		$ls_language['native_name'] = $native_name;
		$translated_name            = $this->sitepress->get_display_language_name( $lang_code, $current_language );
		if ( ! $translated_name ) {
			$translated_name = $ls_language['english_name'];
		}
		$ls_language['translated_name'] = $translated_name;
		if ( isset( $ls_language['translated_url'] ) ) {
			$ls_language['url'] = $ls_language['translated_url'];
			unset( $ls_language['translated_url'] );
		} else {
			$ls_language['url'] = $this->sitepress->language_url( $lang_code );
		}

		$flag_url = '';
		$flag = $this->sitepress->get_flag( $lang_code );
		if ( is_object( $flag ) ) {
			if ( $flag->from_template ) {
				$wp_upload_dir = wp_upload_dir();
				$flag_url      = $wp_upload_dir['baseurl'] . '/flags/' . $flag->flag;
			} else {
				$flag_url = ICL_PLUGIN_URL . '/res/flags/' . $flag->flag;
			}
		}
		$ls_language['country_flag_url'] = $flag_url;
		$ls_language['active']           = $current_language === $lang_code ? '1' : 0;
		$ls_language['language_code']    = $lang_code;

		unset( $ls_language['display_name'], $ls_language['english_name'] );

		return $ls_language;
	}

	public function sort_ls_languages( $w_active_languages, $template_args ) {
		// sort languages according to parameters
		$order_by = isset( $template_args['orderby'] ) ? $template_args['orderby'] : 'custom';
		$order    = isset( $template_args['order'] ) ? $template_args['order'] : 'asc';

		switch ( $order_by ) {
			case 'id':
				uasort( $w_active_languages, array( $this, 'sort_by_id' ) );
				break;
			case 'code':
				krsort( $w_active_languages );
				break;
			case 'name':
				uasort( $w_active_languages, array( $this, 'sort_by_name' ) );
				break;
			case 'custom':
			default:
				$w_active_languages = $this->sitepress->order_languages( $w_active_languages );
		}

		return $order !== 'asc' ? array_reverse( $w_active_languages, true ) : $w_active_languages;
	}

	/**
	 * @param array $lang
	 * @param array $args with keys below
	 * - `skip_lang`        bool|int
	 * - `link_empty`       bool|int
	 * - `link_empty_to`    string a URL possibly with a {%lang} placeholder (e.g. http://example.tld/{%lang}/notify-no-translation/)
	 * - `override_missing` bool if true language will always be shown ( Example: untranslated CPT archives)
	 *
	 * @return array
	 */
	private function maybe_mark_lang_missing( $lang, $args ) {

		$args = array_merge( array(
				'skip_lang'        => 0,
				'link_empty'       => 0,
				'link_empty_to'    => '',
				'override_missing' => false,
			),
			$args
		);

		if ( $args['link_empty'] ) {
			if ( ! empty( $args['link_empty_to'] ) ) {
				$lang['translated_url'] = str_replace( '{%lang}',
				                                       $lang['code'],
					                                   $args['link_empty_to'] );
			} else {
				$lang['translated_url'] = $this->sitepress->language_url( $lang['code'] );
			}
		} else {
			if ( $this->sitepress->get_current_language() != $lang['code'] ) {
				$args['skip_lang'] = true;
			}
		}
		$lang['missing'] = $args['override_missing'] ? 0 : 1;

		return array( $lang, $args['skip_lang'] );
	}

	/**
	 * @param WP_Query $query
	 *
	 * @return array()
	 */
	private function extract_date_data_from_query( $query ) {
		$year  = ! empty( $query->query_vars['year'] )
			? $query->query_vars['year']
			: ( ! empty( $query->query_vars['m'] )
				? substr( $query->query_vars['m'], 0, 4 ) : null );
		$month = ! empty( $query->query_vars['monthnum'] )
			? $query->query_vars['monthnum']
			: ( ! empty( $query->query_vars['m'] )
				? substr( $query->query_vars['m'], 4, 2 ) : null );
		$day   = ! empty( $query->query_vars['day'] )
			? $query->query_vars['day']
			: ( ! empty( $query->query_vars['m'] )
				? substr( $query->query_vars['m'], 6, 2 ) : null );

		return array( $year, $month, $day );
	}

	/**
	 * @param WP_Query $wp_query
	 *
	 * @return array()
	 */
	private function extract_tax_archive_data( $wp_query ) {
		$taxonomy = false;
		$term_id  = false;
		if ( $wp_query->is_category() ) {
			$taxonomy = 'category';
			$term_id  = $wp_query->get( 'cat' );
		} elseif ( $wp_query->is_tag() ) {
			$taxonomy = 'post_tag';
			$term_id  = $wp_query->get( 'tag_id' );
		} elseif ( $wp_query->is_tax() ) {
			$term     = $wp_query->get_queried_object();
			$taxonomy = $term->taxonomy;
			$term_id  = $term->term_id;
		}

		return array( $taxonomy, $term_id );
	}

	private function sort_by_id( $array_a, $array_b ) {

		return (int) $array_a['id'] > (int) $array_b['id'] ? - 1 : 1;
	}

	private function sort_by_name( $array_a, $array_b ) {

		return $array_a['translated_name'] > $array_b['translated_name'] ? 1 : - 1;
	}
}