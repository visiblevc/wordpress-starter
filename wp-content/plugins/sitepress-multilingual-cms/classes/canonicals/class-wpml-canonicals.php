<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Canonicals {
	const CANONICAL_FOR_DUPLICATED_POST       = 'duplicate';
	const CANONICAL_FOR_NON_TRANSLATABLE_POST = 'non-translatable';
	/** @var SitePress */
	private $sitepress;
	/** @var WPML_Translations */
	private $wpml_translations;

	/**
	 * WPML_Canonicals constructor.
	 *
	 * @param SitePress         $sitepress
	 * @param WPML_Translations $wpml_translations
	 */
	public function __construct( SitePress $sitepress, WPML_Translations $wpml_translations = null ) {
		$this->sitepress         = $sitepress;
		$this->wpml_translations = $wpml_translations;
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool|string
	 * @throws \InvalidArgumentException
	 */
	private function must_filter_permalink( $post_id ) {
		$this->init_wpml_translations();
		$post_element           = new WPML_Post_Element( $post_id, $this->sitepress );
		$must_handle_canonicals = $this->must_handle_a_canonical_url();

		if ( $post_element->is_translatable() ) {
			if ( $must_handle_canonicals && $this->wpml_translations->is_a_duplicate_of( $post_element ) && $this->is_permalink_filter_from_rel_canonical() ) {
				return self::CANONICAL_FOR_DUPLICATED_POST;
			}
		} elseif ( $must_handle_canonicals ) {
			return self::CANONICAL_FOR_NON_TRANSLATABLE_POST;
		}

		return false;
	}

	/**
	 * @param string $link
	 * @param int    $post_id
	 *
	 * @return null|string
	 * @throws \InvalidArgumentException
	 */
	public function permalink_filter( $link, $post_id ) {
		switch ( $this->must_filter_permalink( $post_id ) ) {
			case self::CANONICAL_FOR_DUPLICATED_POST:
				$post_element = new WPML_Post_Element( $post_id, $this->sitepress );

				return $this->get_canonical_of_duplicate( $post_element );

			case self::CANONICAL_FOR_NON_TRANSLATABLE_POST:
				return $this->get_url_in_default_language_if_rel_canonical( $link );

			default:
				return null;
		}
	}

	/**
	 * @param string|bool $canonical_url
	 * @param WP_Post     $post
	 *
	 * @return string|bool
	 * @throws \InvalidArgumentException
	 */
	public function get_canonical_url( $canonical_url, $post ) {
		if ( $post && $this->sitepress->get_wp_api()->is_front_end() ) {
			try {
				$post_element = new WPML_Post_Element( $post->ID, $this->sitepress );

				if ( ! $post_element->is_translatable() ) {
					global $wpml_url_filters;
					$wpml_url_filters->remove_global_hooks();
					$canonical_url = $this->sitepress->convert_url_string( $canonical_url, $this->sitepress->get_default_language() );
					$wpml_url_filters->add_global_hooks();
				} else {
					$this->init_wpml_translations();
					if ( $this->wpml_translations->is_a_duplicate_of( $post_element ) ) {
						$canonical_url = (string) $this->get_canonical_of_duplicate( $post_element );
					}
				}
			} catch ( InvalidArgumentException $e ) {
			}
		}

		return $canonical_url;
	}

	private function has_wp_get_canonical_url() {
		return $this->sitepress->get_wp_api()->function_exists( 'wp_get_canonical_url' );
	}

	/**
	 * @return bool
	 */
	private function is_permalink_filter_from_rel_canonical() {
		$back_trace_stack = $this->sitepress->get_wp_api()->get_backtrace( 20 );
		$keywords         = array( 'rel_canonical', 'canonical', 'generate_canonical' );

		$result = false;
		if ( $back_trace_stack ) {
			foreach ( $back_trace_stack as $key => $value ) {
				foreach ( $keywords as $keyword ) {
					if ( 'function' === $key && $keyword === $value ) {
						$result = true;
						break 2;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $link
	 *
	 * @return bool|string
	 */
	private function get_url_in_default_language_if_rel_canonical( $link ) {
		if ( $this->is_permalink_filter_from_rel_canonical() ) {
			$default_language = $this->sitepress->get_default_language();
			$link             = (string) $this->sitepress->convert_url( $link, $default_language );
		}

		return $link;
	}

	/**
	 * @param WPML_Post_Element $post_element
	 *
	 * @return false|string
	 */
	private function get_canonical_of_duplicate( $post_element ) {
		$source_element = $post_element->get_source_element();
		if ( $source_element ) {
			$source_element_id    = $source_element->get_id();
			$source_language_code = $source_element->get_language_code();
			$current_language     = $this->sitepress->get_current_language();
			$this->sitepress->switch_lang( $source_language_code );
			$new_link = get_permalink( $source_element_id );
			$this->sitepress->switch_lang( $current_language );
		} else {
			$new_link = get_permalink( $post_element->get_id() );
		}

		return $new_link;
	}

	/**
	 * @return bool
	 */
	private function must_handle_a_canonical_url() {
		return ! $this->has_wp_get_canonical_url() && $this->sitepress->get_wp_api()->is_front_end();
	}

	private function init_wpml_translations() {
		if ( ! $this->wpml_translations ) {
			$this->wpml_translations = new WPML_Translations( $this->sitepress );
		}
	}
}
