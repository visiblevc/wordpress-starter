<?php

/**
 * Class WPML_ST_WPSEO_Filters
 *
 * Compatibility class for WordPress SEO plugin
 */
class WPML_WPSEO_Filters {

	/* @var WPML_Canonicals $canonicals */
	private $canonicals;

	/**
	 * @var array
	 */
	private $user_meta_fields = array(
		'wpseo_title',
		'wpseo_metadesc',
	);

	/**
	 * WPML_WPSEO_Filters constructor.
	 *
	 * @param WPML_Canonicals $canonicals
	 */
	public function __construct( WPML_Canonicals $canonicals ) {
		$this->canonicals = $canonicals;
	}

	public function init_hooks() {
		add_filter( 'wpml_translatable_user_meta_fields', array( $this, 'translatable_user_meta_fields_filter' ) );
		add_action( 'wpml_before_make_duplicate',         array( $this, 'before_make_duplicate_action' ) );
		add_filter( 'wpseo_canonical',                    array( $this, 'canonical_filter' ) );
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public function translatable_user_meta_fields_filter( $fields ) {
		return array_merge( $this->user_meta_fields, $fields );
	}

	/**
	 * @return array
	 */
	public function get_user_meta_fields() {
		return $this->user_meta_fields;
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2701
	 */
	public function before_make_duplicate_action() {
		add_filter( 'wpseo_premium_post_redirect_slug_change', '__return_true' );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-3694
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function canonical_filter( $url ) {
		$obj = get_queried_object();

		if ( $obj instanceof WP_Post ) {
			/* @var WP_Post $obj */
			$url = $this->canonicals->get_canonical_url( $url, $obj );
		}

		return $url;
	}
}