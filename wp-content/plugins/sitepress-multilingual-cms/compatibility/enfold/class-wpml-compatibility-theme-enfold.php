<?php

/**
 * Class WPML_Compatibility_Theme_Enfold
 */
class WPML_Compatibility_Theme_Enfold {

	public function init_hooks() {
		add_action( 'wp_insert_post', array( $this, 'wp_insert_post_action' ), 10, 2 );
	}

	/**
	 * Enfold's page builder is keeping the content in the custom field "_aviaLayoutBuilderCleanData" (maybe to prevent the content
	 * from being altered by another plugin). The standard post content will be displayed only if the field
	 * "_aviaLayoutBuilder_active" or "_avia_builder_shortcode_tree" does not exist.
	 *
	 * "_aviaLayoutBuilder_active" and "_avia_builder_shortcode_tree" fields should be set to "copy" in wpml-config.xml.
	 *
	 * @param int     $post_ID
	 * @param WP_Post $post
	 */
	public function wp_insert_post_action( $post_ID, $post ) {
		$page_builder_active         = get_post_meta( $post_ID, '_aviaLayoutBuilder_active', true );
		$page_builder_shortcode_tree = get_post_meta( $post_ID, '_avia_builder_shortcode_tree', true );

		if ( $page_builder_active && $page_builder_shortcode_tree ) {
			update_post_meta( $post_ID, '_aviaLayoutBuilderCleanData', $post->post_content );
		}
	}
}