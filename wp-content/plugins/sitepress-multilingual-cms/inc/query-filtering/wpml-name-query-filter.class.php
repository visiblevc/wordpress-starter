<?php

/**
 * Class WPML_Name_Query_Filter
 *
 * @package    wpml-core
 * @subpackage post-translation
 *
 * @since      3.2.3
 */
abstract class WPML_Name_Query_Filter extends WPML_Slug_Resolution {

	/** @var string $post_type */
	protected $post_type;

	/** @var string[] $indexes */
	protected $indexes = array( 'name' );

	/** @var string $id_index */
	protected $id_index = 'p';

	/** @var string[] $active_languages */
	protected $active_languages = array();

	/** @var string $al_regexp */
	protected $al_regexp;

	/** @var  WPML_Post_Translation $post_translation */
	protected $post_translation;

	protected $is_translated;

	/**
	 * @param string                $post_type
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translations
	 * @param wpdb                  $wpdb
	 */
	public function __construct( $post_type, &$sitepress, &$post_translations, &$wpdb ) {
		parent::__construct( $wpdb, $sitepress );
		$this->post_type        = $post_type;
		$this->indexes[]        = $post_type;
		$this->is_translated    = $this->sitepress->is_translated_post_type( $post_type );
		$this->post_translation = &$post_translations;
	}

	/**
	 * Looks through the "name" and "pagename" query vars in a given query and identifies the correct page_id
	 * corresponding to either of these two and then adjusts the query page_id to point at this correct page_id.
	 *
	 * @param WP_Query $page_query
	 *
	 * @return WP_Query that uses the id index stored in \WPML_Name_Query_Filter::$id_index
	 *                  instead of "name" or "pagename" in case a match was found, otherwise
	 *                  returns the input query unaltered.
	 */
	public function filter_page_name( $page_query ) {
		$this->active_languages = $this->get_ordered_langs();
		$this->al_regexp        = $this->generate_al_regexp( $this->active_languages );
		foreach ( $this->indexes as $index ) {
			list( $pages_with_name, $page_name_for_query ) = $this->query_needs_adjustment( $page_query, $index );
			if ( (bool) $pages_with_name === true ) {
				$pid = $this->select_best_match( $pages_with_name );
				if ( isset( $pid ) ) {
					if ( ! isset( $page_query->queried_object_id ) || $pid != $page_query->queried_object_id ) {
						$page_query = $this->maybe_adjust_query_by_pid( $page_query, $pid, $index );
					} else {
						unset( $pid );
					}
				} elseif ( (bool) $page_name_for_query === true ) {
					$page_query->query_vars[ $index ]    = $page_name_for_query;
					$page_query->query_vars['post_type'] = $this->post_type;
				}
			}
		}

		return array($page_query, isset($pid) ? $pid : false);
	}

	protected abstract function select_best_match( $pages_with_name );

	/**
	 * @param WP_Query $page_query
	 * @param int      $pid
	 * @param string   $index
	 *
	 * @return WP_Query
	 */
	protected function maybe_adjust_query_by_pid( $page_query, $pid, $index ) {
		if ( ! ( isset( $page_query->queried_object )
		         && isset( $page_query->queried_object->ID )
		         && (int) $page_query->queried_object->ID === (int) $pid )
		) {
			if ( isset( $page_query->query[ 'page_id' ] ) ) {
				$page_query->query[ 'page_id' ]        = (int) $pid;
			}
			$page_query->query_vars[ 'page_id' ]       = null;
			$page_query->query_vars[ $this->id_index ] = (int) $pid;
			if ( isset( $page_query->query[ $this->id_index ] ) ) {
				$page_query->query[ $this->id_index ]  = (int) $pid;
			}
			$page_query->is_page                       = false;
			if ( isset( $page_query->query_vars[ $this->post_type ] )
				 && $this->post_type !== 'page'
			) {
				unset( $page_query->query_vars[ $this->post_type ] );
			}
			unset( $page_query->query_vars[ $index ] );
			if ( ! empty( $page_query->queried_object ) ) {
				$page_query->queried_object    = get_post( $pid );
				$page_query->queried_object_id = (int) $pid;
			}
			
			$page_query = $this->adjusting_id( $page_query );
		}

		return $page_query;
	}
	
	/**
	 * Called when the post id is being adjusted. Can be overridden.
	 * 
	 * @param WP_Query $page_query
	 *
	 * @return WP_Query
	 */
	
	protected function adjusting_id( $page_query ) {
		$page_query->is_single = true;
		
		return $page_query;
	}

	/**
	 * Returns a SQL snippet for joining the posts table with icl translations filtered for the post_type
	 * of this class.
	 *
	 * @return string
	 */
	protected abstract function get_from_join_snippet();

	/**
	 * Generates a regular expression matcher for matching language slugs in a URI
	 *
	 * @param string[] $active_language_codes
	 *
	 * @return string
	 */
	private function generate_al_regexp( $active_language_codes ) {

		return '/^(' . implode( '|', $active_language_codes ) . ')\//';
	}

	/**
	 * @param WP_Query $page_query
	 * @param string   $index
	 *
	 * @return array
	 */
	private function query_needs_adjustment( $page_query, $index ) {
		if ( empty( $page_query->query_vars[ $index ] ) ) {
			$pages_with_name     = false;
			$page_name_for_query = false;
		} else {
			$page_name_for_query = preg_replace( $this->al_regexp, '', $page_query->query_vars[ $index ] );
			$pages_with_name     = strpos( $page_name_for_query, '/' ) === false
				? $this->get_single_slug_adjusted_IDs( $page_name_for_query )
				: $this->get_multiple_slug_adjusted_IDs( explode( '/', $page_name_for_query ) );
		}

		return array( $pages_with_name, $page_name_for_query );
	}

	/**
	 * @param string $page_name_for_query
	 *
	 * @return array
	 */
	private function get_single_slug_adjusted_IDs( $page_name_for_query ) {
		$pages_with_name = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"
				SELECT ID
				" . $this->get_from_join_snippet()
				. $this->get_where_snippet() . " p.post_name = %s
				ORDER BY p.post_parent = 0 DESC
				",
				$page_name_for_query
			)
		);

		return $pages_with_name;
	}

	/**
	 * @param string[] $slugs slugs that were queried for
	 *
	 * @return int[] page_ids ordered by their likelihood of correctly matching the query target,
	 *               derived from checking all slugs against the sits pages slugs as well as their parent slugs.
	 *               Elements at the beginning of the array are more correct than later elements, but the results
	 *               are not yet filtered for the correct language.
	 *
	 * @used-by \WPML_Page_Name_Query_Filter::filter_page_name to find the correct page_id corresponding to a set of slugs,
	 *                                                         by filtering the results of this function by language of the
	 *                                                         returned page_ids.
	 *
	 */
	private function get_multiple_slug_adjusted_IDs( $slugs ) {
		$parent_slugs    = array_slice( $slugs, 0, - 1 );
		$pages_with_name = $this->wpdb->get_results(
			"   SELECT p.ID, p.post_name, p.post_parent, par.post_name as parent_name
				" . $this->get_from_join_snippet() . "
				LEFT JOIN {$this->wpdb->posts} par
					ON par.ID = p.post_parent
				" . $this->get_where_snippet() . " p.post_name IN (" . wpml_prepare_in( $slugs ) . ")
				ORDER BY par.post_name IN (" . wpml_prepare_in( $parent_slugs ) . ") DESC"
		);
		$query_scorer = new WPML_Score_Hierarchy( $pages_with_name, $slugs );

		return $query_scorer->get_possible_ids_ordered();
	}

	private function get_where_snippet() {

		return $this->wpdb->prepare( " WHERE p.post_type = %s AND ", $this->post_type );
	}
}