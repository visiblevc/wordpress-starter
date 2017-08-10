<?php

/**
 * Class WPML_Media_Attachments_Query
 */
class WPML_Media_Attachments_Query {

	/**
	 * WPML_Media_Attachments_Query constructor.
	 */
	public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'adjust_attachment_query' ), 10 );
	}

	/**
	 * Set `suppress_filters` to false if attachment is displayed.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function adjust_attachment_query( $query ) {
		if ( isset( $query->query['post_type'] ) && 'attachment' === $query->query['post_type'] ) {
			$query->set( 'suppress_filters', false );
		}
		return $query;
	}
}
