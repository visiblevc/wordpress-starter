<?php

/**
 * Class WPML_404_Guess
 *
 * @package    wpml-core
 * @subpackage post-translation
 *
 * @since      3.2.3
 */
class WPML_404_Guess extends WPML_Slug_Resolution {

	/** @var  WPML_Query_Filter $query_filter */
	private $query_filter;

	/**
	 * @param wpdb              $wpdb
	 * @param SitePress         $sitepress
	 * @param WPML_Query_Filter $query_filter
	 */
	public function __construct( &$wpdb, &$sitepress, &$query_filter ) {
		parent::__construct( $wpdb, $sitepress );
		$this->query_filter = &$query_filter;
	}

	/**
	 * Attempts to guess the correct URL based on query vars
	 *
	 * @since 3.2.3
	 *
	 * @param string   $name
	 * @param WP_Query $query
	 *
	 * @return array containing most likely name, type and whether or not a match was found
	 */
	public function guess_cpt_by_name( $name, $query ) {
		$type  = $query->get( 'post_type' );
		$ret   = array( $name, $type, false );
		$types = (bool) $type === false
			? $this->sitepress->get_wp_api()->get_post_types( array( 'public' => true ) )
			: (array) $type;
		if ( (bool) $types === true ) {
			$where = $this->wpdb->prepare( "post_name = %s ", $name );
			$where .= " AND post_type IN ('" . implode( "', '", $types ) . "')";
			$date_snippet = $this->by_date_snippet( $query );
			$where .= $date_snippet;
			$res = $this->wpdb->get_row( "
									 SELECT post_type, post_name
									 FROM {$this->wpdb->posts} p
									 LEFT JOIN {$this->wpdb->prefix}icl_translations t
										ON t.element_id = p.ID
										 	AND CONCAT('post_', p.post_type) = t.element_type
									 		AND " . $this->query_filter->in_translated_types_snippet( false, 'p' ) . "
									 WHERE $where
									 	AND ( post_status = 'publish'
									 	    OR ( post_type = 'attachment'
									 	         AND post_status = 'inherit' ) )
									 	" . $this->order_by_language_snippet( (bool) $date_snippet) . "
								     LIMIT 1" );
			if ( (bool) $res === true ) {
				$ret = array( $res->post_name, $res->post_type, true );
			}
		}

		return $ret;
	}

	/**
	 * Retrieves year, month and day parameters from the query if they are set and builds the appropriate sql
	 * snippet to filter for them.
	 *
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	private function by_date_snippet( $query ) {
		$snippet = '';
		foreach ( array( 'year' => 'YEAR', 'monthnum' => 'MONTH', 'day' => 'DAY' ) as $index => $time_unit ) {
			if ( (bool) ( $value = $query->get( $index ) ) === true ) {
				$snippet .= $this->wpdb->prepare( " AND {$time_unit}(post_date) = %d ", $value );
			}
		}

		return $snippet;
	}

	/**
	 * @param bool $has_date
	 *
	 * @return string
	 */
	private function order_by_language_snippet( $has_date ) {
		$lang_order   = $this->get_ordered_langs();
		$current_lang = array_shift( $lang_order );
		$best_score   = count( $lang_order ) + 2;
		$order_by     = '';
		if ( $best_score > 2 ) {
			$order_by .= $this->wpdb->prepare( 'ORDER BY CASE t.language_code WHEN %s THEN %d ',
			                                   $current_lang,
			                                   $best_score,
			                                   $best_score - 1 );
			$score = $best_score - 2;
			foreach ( $lang_order as $lang_code ) {
				$order_by .= $this->wpdb->prepare( ' WHEN %s THEN %d ', $lang_code, $score );
				$score -= 1;
			}
			$order_by .= ' ELSE 0 END DESC ';
			if ( $has_date ) {
				$order_by .= ", CASE p.post_type WHEN 'post' THEN 0 ELSE 1 END ";
			}
			$order_by .= ' , ' . $this->order_by_post_type_snippet();
		}

		return $order_by;
	}

	/**
	 *
	 * @return string
	 */
	private function order_by_post_type_snippet() {
		$post_types = array( 'page' => 2, 'post' => 1 );
		$order_by   = ' CASE p.post_type ';
		foreach ( $post_types as $type => $score ) {
			$order_by .= $this->wpdb->prepare( ' WHEN %s THEN %d ', $type, $score );
		}
		$order_by .= ' ELSE 0 END DESC ';

		return $order_by;
	}
}
