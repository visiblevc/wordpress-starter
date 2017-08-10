<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Requirements_Notification {

	/**
	 * WPML_Requirements_Notification constructor.
	 *
	 * @param IWPML_Template_Service $template_service
	 */
	public function __construct( IWPML_Template_Service $template_service ) {
		$this->template_service = $template_service;
	}

	public function get_message( $issues, $limit = 0 ) {
		if ( $issues ) {
			$model = array(
				'strings' => array(
					'title'    => sprintf( __( 'To easily translate %s, you need to add the following WPML components:', 'sitepress' ), $this->get_product_names( $issues ) ),
					'download' => __( 'Download', 'sitepress' ),
					'install'  => __( 'Install', 'sitepress' ),
				),
				'shared'  => array(
					'install_link' => get_admin_url( null, 'plugin-install.php?tab=commercial' ),
				),
				'options' => array(
					'limit' => $limit,
				),
				'data'    => $issues,
			);

			return $this->template_service->show( $model, 'plugins-status.twig' );
		}

		return null;
	}

	public function get_settings( $integrations ) {
		if ( $integrations ) {
			$model = array(
				'strings' => array(
					'title'   => sprintf( __( 'One more step before you can translate %s', 'sitepress' ), $this->build_items_in_sentence( $integrations ) ),
					'message' => __( "You need to enable WPML's Translation Editor, to translate conveniently.", 'sitepress' ),
					'enable_done' => __( 'Done.', 'sitepress' ),
					'enable_error' => __( 'Something went wrong. Please try again or contact the support.', 'sitepress' ),
				),
				'nonces'  => array(
					'enable' => wp_create_nonce( 'wpml_set_translation_editor' ),
				),
			);

			return $this->template_service->show( $model, 'integrations-tm-settings.twig' );
		}

		return null;
	}

	/**
	 * @param array $issues
	 *
	 * @return string
	 */
	private function get_product_names( $issues ) {
		$products = wp_list_pluck( $issues['causes'], 'name' );

		return $this->build_items_in_sentence( $products );
	}

	/**
	 * @param $items
	 *
	 * @return string
	 */
	private function build_items_in_sentence( $items ) {
		if ( count( $items ) <= 2 ) {
			$product_names = implode( _x( ' and ', 'between two elements of a list', 'sitepress' ) . ' ', $items );

			return $product_names;
		} else {
			$last          = array_slice( $items, - 1 );
			$first         = implode( ', ', array_slice( $items, 0, - 1 ) );
			$both          = array_filter( array_merge( array( $first ), $last ), 'strlen' );
			$product_names = implode( _x( ', and ', 'before the element of a list', 'sitepress' ) . ' ', $both );

			return $product_names;
		}
	}
}
