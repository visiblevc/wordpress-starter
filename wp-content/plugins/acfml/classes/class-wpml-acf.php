<?php

/**
 * Class WPML_ACF
 */
class WPML_ACF {

	private $WPML_ACF_Requirements;

	/**
	 * @return WPML_ACF_Worker
	 */
	public function init_worker() {
		global $wpdb;
		add_action( 'wpml_loaded', array( $this, 'init_acf_xliff' ) );

		$this->WPML_ACF_Requirements = new WPML_ACF_Requirements();

		return $this->init_duplicated_post( $wpdb );
	}

	private function init_duplicated_post( $wpdb ) {
		$duplicated_post = new WPML_ACF_Duplicated_Post( $wpdb );

		return new WPML_ACF_Worker( $duplicated_post );
	}

	public function init_acf_xliff() {
		if ( defined( 'WPML_ACF_XLIFF_SUPPORT' ) && WPML_ACF_XLIFF_SUPPORT ) {
			if ( is_admin() ) {
				if ( class_exists( 'acf' ) ) {
					global $wpdb, $sitepress;
					$WPML_ACF_Xliff = new WPML_ACF_Xliff( $wpdb, $sitepress );
					$WPML_ACF_Xliff->init_hooks();
				}
			}
		}
	}
}
