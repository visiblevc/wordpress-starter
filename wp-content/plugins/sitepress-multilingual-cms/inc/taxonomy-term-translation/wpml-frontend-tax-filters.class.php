<?php

class WPML_Frontend_Tax_Filters{

	public function __construct() {
		add_filter ( 'taxonomy_template', array( $this, 'slug_template' ) );
		add_filter ( 'category_template', array( $this, 'slug_template' ) );
		add_filter ( 'tag_template', array( $this, 'slug_template' ) );
	}

	/**
	 * Adjust template (taxonomy-)$taxonomy-$term.php for translated term slugs and IDs
	 *
	 * @since 3.1
	 *
	 * @param string $template
	 *
	 * @return string The template filename if found.
	 */
	function slug_template($template){
		global $sitepress;

		if ( ( $term = $this->get_queried_tax_term () ) === false ) {
			return $template;
		}

		$taxonomy = $term->taxonomy;
		$templates = array();

		$has_filter = remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );

		$current_language = $sitepress->get_current_language();
		$default_language = $sitepress->get_default_language();

		if ( is_taxonomy_translated( $taxonomy ) ) {
			$templates = $this->add_term_templates($term, $current_language, $templates);
			$templates = $this->add_original_term_templates($term,$default_language, $current_language, $templates);
		}

		if ( !in_array ( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			$templates[ ] = 'taxonomy-' . $current_language . '.php';
			$templates[ ] = 'taxonomy.php';
		}

		if ( $has_filter ) {
			add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
		}

		$new_template = locate_template( array_unique($templates) );
		$template = $new_template ? $new_template : $template;

		return $template;
	}

	private function get_template_prefix($taxonomy){

		$prefix = in_array ( $taxonomy, array( 'category', 'post_tag' ), true ) ? '' : 'taxonomy-';
		$prefix .= $taxonomy == 'post_tag' ? 'tag' : $taxonomy;

		return $prefix;
	}

	private function add_term_templates( $term, $current_language, $templates ) {
		$prefix       = $this->get_template_prefix ( $term->taxonomy );
		$templates[ ] = "$prefix-{$current_language}-{$term->slug}.php";
		$templates[ ] = "$prefix-{$current_language}-{$term->term_id}.php";
		$templates[ ] = "$prefix-{$current_language}.php";
		$templates[ ] = "$prefix-{$term->slug}.php";
		$templates[ ] = "$prefix-{$term->term_id}.php";

		return $templates;
	}

	private function add_original_term_templates( $term, $default_language, $current_language, $templates ) {
		$taxonomy         = $term->taxonomy;
		$prefix           = $this->get_template_prefix ( $taxonomy );
		$original_term_id = icl_object_id ( $term->term_id, $taxonomy, true, $default_language );
		$original_term    = get_term_by ( "id", $original_term_id, $taxonomy );
		if ( $original_term ) {
			$templates[ ] = "$prefix-{$current_language}-{$original_term->slug}.php";
			$templates[ ] = "$prefix-{$current_language}-{$original_term_id}.php";
			$templates[ ] = "$prefix-{$original_term->slug}.php";
			$templates[ ] = "$prefix-{$original_term_id}.php";
			$templates[ ] = "$prefix-{$current_language}.php";
			$templates[ ] = "$prefix.php";
		}

		return $templates;
	}

	private function get_queried_tax_term(){
		global $wp_query;
		/** @var WP_Query $wp_query */
		$term = $wp_query->get_queried_object();
		$res = false;

		if((bool)$term === true && isset($term->taxonomy) && $term->taxonomy){
			$res = $term;
		}

		return $res;
	}

}