<?php

/**
 * WP SEO by Yoast sitemap filter class
 *
 * @version 1.0.2
 */
class WPML_WPSEO_XML_Sitemaps_Filter extends WPML_SP_User {

	/**
	 * WPML_URL_Converter object.
	 *
	 * @var WPML_URL_Converter
	 */
	private $wpml_url_converter;

	/**
	 * @var WPML_Debug_BackTrace
	 */
	private $back_trace;

	/**
	 * WPSEO_XML_Sitemaps_Filter constructor.
	 *
	 * @param SitePress            $sitepress
	 * @param object               $wpml_url_converter
	 * @param WPML_Debug_BackTrace $back_trace
	 */
	public function __construct( $sitepress, $wpml_url_converter, WPML_Debug_BackTrace $back_trace = null ) {
		$this->sitepress          = $sitepress;
		$this->wpml_url_converter = $wpml_url_converter;
		$this->back_trace         = $back_trace;
	}

	public function init_hooks() {
		global $wpml_query_filter;

		if ( $this->is_per_domain() ) {
			add_filter( 'wpml_get_home_url', array( $this, 'get_home_url_filter' ), 10, 1 );
			add_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
			add_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
			add_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
			add_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
		} else {
			add_filter( 'wpseo_sitemap_page_content', array( $this, 'add_languages_to_sitemap' ) );
			// Remove posts under hidden language.
			add_filter( 'wpseo_xml_sitemap_post_url', array( $this, 'exclude_hidden_language_posts' ), 10, 2 );
		}

		if ( $this->is_per_directory() ) {
			add_filter( 'wpml_get_home_url', array( $this, 'maybe_return_original_url_in_get_home_url_filter' ), 10, 2 );
		}

		add_filter( 'wpseo_enable_xml_sitemap_transient_caching', array( $this, 'transient_cache_filter' ), 10, 0 );
		add_filter( 'wpseo_build_sitemap_post_type', array( $this, 'wpseo_build_sitemap_post_type_filter' ) );
		add_action( 'wpseo_xmlsitemaps_config', array( $this, 'list_domains' ) );
		add_filter( 'wpseo_sitemap_entry', array( $this, 'exclude_translations_of_static_home_page' ), 10, 3 );
	}

	/**
	 * Add home page urls for languages to sitemap.
	 * Do this only if configuration language per domain option is not used.
	 */
	public function add_languages_to_sitemap() {
		$output = '';
		$default_lang = $this->sitepress->get_default_language();
		$active_langs = $this->sitepress->get_active_languages();
		unset( $active_langs[ $default_lang ] );

		foreach ( $active_langs as $lang_code => $lang_data ) {
			$output .= $this->sitemap_url_filter( $this->wpml_url_converter->convert_url( home_url(), $lang_code ) );
		}
		return $output;
	}

	/**
	 * Update home_url for language per-domain configuration to return correct URL in sitemap.
	 */
	public function get_home_url_filter( $home_url ) {
		return $this->wpml_url_converter->convert_url( $home_url, $this->sitepress->get_current_language() );
	}

	public function list_domains() {
		if ( $this->is_per_domain() || $this->has_root_page() ) {

			echo '<h3>' . esc_html__( 'WPML', 'sitepress' ) . '</h3>';
			echo esc_html__( 'Sitemaps for each language can be accessed below. You need to submit all these sitemaps to Google.', 'sitepress' );
			echo '<table class="wpml-sitemap-translations" style="margin-left: 1em; margin-top: 1em;">';

			$base_style = "style=\"
			background-image:url('%s');
			background-repeat: no-repeat;
			background-position: 2px center;
			background-size: 16px;
			padding-left: 20px;
			width: 100%%;
			\"
			";

			foreach ( $this->sitepress->get_ls_languages() as $lang ) {
				$url = $lang['url'] . 'sitemap_index.xml';
				echo '<tr>';
				echo '<td>';
				echo '<a ';
				echo 'href="' . esc_url( $url ) . '" ';
				echo 'target="_blank" ';
				echo 'class="button-secondary" ';
				echo sprintf( $base_style, esc_url( $lang['country_flag_url'] ) );
				echo '>';
				echo esc_html( $lang['translated_name'] );
				echo '</a>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
	}

	/**
	 * @return bool
	 */
	public function is_per_domain() {
		return WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $this->sitepress->get_setting( 'language_negotiation_type', false );
	}

	/**
	 * @return bool
	 */
	private function is_per_directory() {
		return WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $this->sitepress->get_setting( 'language_negotiation_type', false );
	}

	public function transient_cache_filter() {
		return false;
	}

	public function wpseo_build_sitemap_post_type_filter( $type ) {
		global $sitepress_settings;
		// Before to build the sitemap and as we are on front-end
		// just make sure the links won't be translated
		// The setting should not be updated in DB
		$sitepress_settings['auto_adjust_ids'] = 0;

		if ( !$this->is_per_domain() && !$this->has_root_page() ) {
			remove_filter( 'terms_clauses', array( $this->sitepress, 'terms_clauses' ), 10 );
		}

		return $type;
	}

	private function has_root_page() {
		return (bool) $this->sitepress->get_root_page_utils()->get_root_page_id();
	}

	/**
	 * Exclude posts under hidden language.
	 *
	 * @param  string $url   Post URL.
	 * @param  object $post  Object with some post information.
	 *
	 * @return string
	 */
	public function exclude_hidden_language_posts( $url, $post ) {
		// Check that at least ID is set in post object.
		if ( ! isset( $post->ID ) ) {
			return $url;
		}

		// Get list of hidden languages.
		$hidden_languages = $this->sitepress->get_setting( 'hidden_languages', array() );

		// If there are no hidden languages return original URL.
		if ( empty( $hidden_languages ) ) {
			return $url;
		}

		// Get language information for post.
		$language_info = $this->sitepress->post_translations()->get_element_lang_code( $post->ID );

		// If language code is one of the hidden languages return empty string to skip the post.
		if ( in_array( $language_info, $hidden_languages ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Convert URL to sitemap entry format.
	 *
	 * @param string $url URl to prepare for sitemap.
	 *
	 * @return string
	 */
	public function sitemap_url_filter( $url ) {
		$url = htmlspecialchars( $url );

		$output = "\t<url>\n";
		$output .= "\t\t<loc>" . $url . "</loc>\n";
		$output .= '';
		$output .= "\t\t<changefreq>daily</changefreq>\n";
		$output .= "\t\t<priority>1.0</priority>\n";
		$output .= "\t</url>\n";

		return $output;
	}

	/**
	 * @param $url
	 * @param $type
	 * @param $post_object
	 *
	 * @return string|bool
	 */
	public function exclude_translations_of_static_home_page( $url, $type, $post_object ) {
		if ( 'post' !== $type || $this->is_per_domain() ) {
			return $url;
		}
		$page_on_front = (int) get_option( 'page_on_front' );
		if ( $page_on_front ) {
			$translations = $this->sitepress->post_translations()->get_element_translations( $page_on_front );
			unset( $translations[ $this->sitepress->get_default_language() ] );
			if ( in_array( $post_object->ID, $translations ) ) {
				$url = false;
			}
		}
		return $url;
	}

	/**
	 * @param string $home_url
	 * @param string $original_url
	 *
	 * @return string
	 */
	public function maybe_return_original_url_in_get_home_url_filter( $home_url, $original_url ) {
		$places = array(
			array( 'WPSEO_Post_Type_Sitemap_Provider', 'get_home_url' ),
			array( 'WPSEO_Sitemaps_Router', 'get_base_url' ),
			array( 'WPSEO_Sitemaps_Renderer', '__construct' ),
		);

		foreach ( $places as $place ) {
			if ( $this->get_back_trace()->is_class_function_in_call_stack( $place[0], $place[1] ) ) {
				return $original_url;
			}
		}

		return $home_url;
	}

	/**
	 * @return WPML_Debug_BackTrace
	 */
	private function get_back_trace() {
		if ( null === $this->back_trace ) {
			$this->back_trace = new WPML_Debug_BackTrace( phpversion() );
		}

		return $this->back_trace;
	}
}
