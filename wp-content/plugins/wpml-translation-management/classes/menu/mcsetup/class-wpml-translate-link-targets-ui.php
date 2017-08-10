<?php

class WPML_Translate_Link_Targets_UI extends WPML_TM_MCS_Section_UI {

	/** @var WPDB $wpdb */
	private $wpdb;
	/** @var WPML_Pro_Translation $pro_translation */
	private $pro_translation;
	/** @var  WPML_WP_API $wp_api */
	private $wp_api;
	/** @var  SitePress $sitepress */
	private $sitepress;

	public function __construct( $id, $title, $wpdb, $sitepress, $pro_translation ) {
		parent::__construct( $id, $title );
		$this->wpdb            = $wpdb;
		$this->pro_translation = $pro_translation;
		$this->sitepress       = $sitepress;
	}

	protected function render_content() {

		$main_message = __( 'Adjust links in posts so they point to the translated content', 'wpml-translation-management' );
		$complete_message = __( 'All posts have been processed. %s links were changed to point to the translated content.', 'wpml-translation-management' );
		$string_count = 0;

		$posts        = new WPML_Translate_Link_Targets_In_Posts_Global( new WPML_Translate_Link_Target_Global_State( $this->sitepress ), $this->wpdb, $this->pro_translation );
		$post_count   = $posts->get_number_to_be_fixed();

		if ( defined( 'WPML_ST_VERSION') ) {
			$strings      = new WPML_Translate_Link_Targets_In_Strings_Global(  new WPML_Translate_Link_Target_Global_State( $this->sitepress ), $this->wpdb, $this->wp_api, $this->pro_translation );
			$string_count = $strings->get_number_to_be_fixed();
			$main_message = __( 'Adjust links in posts and strings so they point to the translated content', 'wpml-translation-management' );
			$complete_message = __( 'All posts and strings have been processed. %s links were changed to point to the translated content.', 'wpml-translation-management' );
		}

		?>
		<p><?php echo $main_message ?></p>
		<button id="wpml-scan-link-targets"
		        class="button-secondary"
		        data-post-message="<?php _e( 'Processing posts... %1$s of %2$s done.', 'wpml-translation-management' ); ?>"
		        data-post-count="<?php echo $post_count; ?>"
		        data-string-message="<?php _e( 'Processing strings... %1$s of %2$s done.', 'wpml-translation-management' ); ?>"
		        data-string-count="<?php echo $string_count; ?>"
		        data-complete-message="<?php echo $complete_message; ?>"
		><?php _e( 'Scan now and adjust links', 'wpml-translation-management' ); ?></button>
		<span class="spinner"></span>
		<p class="results"></p>

		<?php

		wp_nonce_field( 'WPML_Ajax_Update_Link_Targets', 'wpml-translate-link-targets' );

	}
}

