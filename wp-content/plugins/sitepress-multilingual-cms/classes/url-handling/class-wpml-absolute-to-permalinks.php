<?php

class WPML_Absolute_To_Permalinks extends WPML_SP_User{

	private $taxonomies_query;
	private $wp_api;
	private $lang;
	
	public function __construct( &$sitepress ) {
		parent::__construct( $sitepress );
		$this->wp_api = $sitepress->get_wp_api();
	}
	
	public function convert_text( $text ) {

		$this->lang = $this->sitepress->get_current_language();

		if ( ! $this->taxonomies_query ) {
			$this->taxonomies_query = new WPML_WP_Taxonomy_Query( $this->wp_api );
		}

		$home    = rtrim( $this->wp_api->get_option( 'home' ), '/' );
		$parts   = parse_url( $home );
		$abshome = $parts[ 'scheme' ] . '://' . $parts[ 'host' ];
		$path    = isset( $parts[ 'path' ] ) ? ltrim( $parts[ 'path' ], '/' ) : '';
		$tx_qvs  = join( '|', $this->taxonomies_query->get_query_vars() );
		$reg_ex  = '@<a([^>]+)?href="((' . $abshome . ')?/' . $path . '/?\?(p|page_id|cat_ID|' . $tx_qvs . ')=([0-9a-z-]+))(#?[^"]*)"([^>]+)?>@i';
		$text    = preg_replace_callback( $reg_ex, array( $this, 'show_permalinks_cb' ), $text );
		
		return $text;
	}
	
	function show_permalinks_cb( $matches ) {
		
		$parts = $this->get_found_parts( $matches );

		$url   = $this->get_url( $parts );

		if ( $this->wp_api->is_wp_error( $url ) || empty( $url ) ) {
			return $parts->whole;
		}

		$fragment = $this->get_fragment( $url, $parts );

		if ( 'widget_text' == $this->wp_api->current_filter() ) {
			$url = $this->sitepress->convert_url( $url );
		}

		return '<a' . $parts->pre_href . 'href="' . $url . $fragment . '"' . $parts->trail . '>';
	}
	
	private function get_found_parts( $matches ) {
		return (object) array( 'whole'        => $matches[0],
							   'pre_href'     => $matches[1],
							   'content_type' => $matches[4],
							   'id'           => $matches[5],
							   'fragment'     => $matches[6],
							   'trail'        => isset( $matches[7] ) ?  $matches[7] : ''
							   );
	}
	
	private function get_url( $parts ) {
		$tax = $this->taxonomies_query->find( $parts->content_type );

		$auto_adjust_ids_origin = $this->sitepress->get_setting( 'auto_adjust_ids', false );
		$this->sitepress->set_setting( 'auto_adjust_ids', true );

		if ( $parts->content_type == 'cat_ID' ) {
			$url = $this->wp_api->get_category_link( $parts->id );
		} elseif ( $tax ) {
			$url = $this->wp_api->get_term_link( $parts->id, $tax );
		} else {
			$url = $this->wp_api->get_permalink( $parts->id );
		}

		$this->sitepress->set_setting( 'auto_adjust_ids', $auto_adjust_ids_origin );

		return $url;		
	}
	
	private function get_fragment( $url, $parts ) {
		$fragment = $parts->fragment;
		$fragment = $this->remove_query_in_wrong_lang( $fragment );
		if ( $fragment != '' ) {
			$fragment = str_replace( '&#038;', '&', $fragment );
			$fragment = str_replace( '&amp;', '&', $fragment );
			if ( $fragment[ 0 ] == '&' ) {
				if ( strpos( $fragment, '?' ) === false && strpos( $url, '?' ) === false ) {
					$fragment[ 0 ] = '?';
				}
			}

			if ( strpos( $url, '?' ) ) {
				$fragment = $this->check_for_duplicate_lang_query( $fragment, $url );
			}
		}

		return $fragment;		
	}

	private function remove_query_in_wrong_lang( $fragment ) {
		if ( $fragment != '' ) {
			$fragment = str_replace( '&#038;', '&', $fragment );
			$fragment = str_replace( '&amp;', '&', $fragment );
			$start = $fragment[0];
			parse_str( substr( $fragment, 1 ), $fragment_query );
			if ( isset( $fragment_query['lang' ] ) ) {
				if ( $fragment_query['lang'] != $this->lang ) {
					unset( $fragment_query['lang'] );

					$fragment = build_query( $fragment_query );
					if ( strlen( $fragment ) ) {
						$fragment = $start . $fragment;
					}

				}
			}

		}
		return $fragment;
	}

	private function check_for_duplicate_lang_query( $fragment , $url ) {
		$url_parts = explode( '?', $url );
		parse_str( $url_parts[1], $url_query );

		if ( isset( $url_query['lang'] ) ) {
			parse_str( substr( $fragment, 1 ), $fragment_query );
			if ( isset( $fragment_query['lang' ] ) ) {
				unset( $fragment_query['lang'] );
				$fragment = build_query( $fragment_query );
				if ( strlen( $fragment ) ) {
					$fragment = '&' . $fragment;
				}
			}
		}
		return $fragment;
	}
}