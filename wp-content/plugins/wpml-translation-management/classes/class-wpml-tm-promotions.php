<?php

class WPML_TM_Promotions {

	/**
	 * WPML_TM_Promotions constructor.
	 *
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( &$wpml_wp_api ) {
		$this->wpml_wp_api = &$wpml_wp_api;
		$this->promote_wcml_message();
	}


	public function promote_wcml_message() {
		$promote = false;

		if ( class_exists( 'WooCommerce' ) && ! class_exists( 'woocommerce_wpml' ) ) {
			global $pagenow;

			$promote = $this->wpml_wp_api->is_tm_page();

			if ( isset( $_GET['post_type'] ) && !empty( $_GET['post_type'] ) ) {
				$promote = ( 'product' === $_GET['post_type'] && 'edit.php' === $pagenow );
			}
		}

		if ( $promote ) {
			$message   = '';
			$wcml_link = '<a href="https://wordpress.org/plugins/woocommerce-multilingual" target="_blank">WooCommerce Multilingual.</a>';
			$message .= sprintf( __( 'Looks like you are running a multilingual WooCommerce site. To easily translate WooCommerce products and categories, you should use %s', 'wpml-translation-management' ), $wcml_link );
			$args = array(
					'id'           => 'promote-wcml',
					'group'        => 'promote-wcml',
					'msg'          => $message,
					'type'         => 'notice',
					'admin_notice' => true,
					'hide'         => true,
			);

			ICL_AdminNotifier::add_message( $args );
		} else {
			ICL_AdminNotifier::remove_message_group( 'promote-wcml' );
		}
	}
}