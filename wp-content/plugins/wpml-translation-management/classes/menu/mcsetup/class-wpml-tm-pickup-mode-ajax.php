<?php

class WPML_TM_Pickup_Mode_Ajax {

	const NONCE_PICKUP_MODE = 'wpml_save_translation_pickup_mode';

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var WPML_Update_PickUp_Method
	 */
	private $update_pickup_mode;

	/**
	 * @var WPML_Pro_Translation
	 */
	private $icl_pro_translation;

	public function __construct( SitePress $sitepress, WPML_Pro_Translation $icl_pro_translation ) {
		$this->sitepress = $sitepress;
		$this->icl_pro_translation = $icl_pro_translation;
		$this->update_pickup_mode = new WPML_Update_PickUp_Method( $this->sitepress );
	}

	public function ajax_hooks() {
		add_action( 'wp_ajax_wpml_save_translation_pickup_mode', array( $this, 'wpml_save_translation_pickup_mode' ) );
	}

	public function wpml_save_translation_pickup_mode() {
		try {
			if ( ! $this->is_valid_request() ) {
				throw new InvalidArgumentException('Request is not valid');
			}

			if ( ! array_key_exists('pickup_mode', $_POST ) ) {
				throw new InvalidArgumentException();
			}

			$available_pickup_modes = array( 0, 1 );
			$pickup_mode = filter_var( $_POST['pickup_mode'], FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );

			if ( ! in_array( (int) $pickup_mode, $available_pickup_modes, true ) ) {
				throw new InvalidArgumentException();
			}

			$data['icl_translation_pickup_method'] = $pickup_mode;
			$this->update_pickup_mode->update_pickup_method( $data, $this->icl_pro_translation->get_current_project() );

			wp_send_json_success();
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error();
		}
	}

	private function is_valid_request() {
		$valid_request = true;
		if ( ! array_key_exists( 'nonce', $_POST ) ) {
			$valid_request = false;
		}
		if ( $valid_request ) {
			$nonce = $_POST['nonce'];
			$nonce_is_valid = wp_verify_nonce( $nonce, self::NONCE_PICKUP_MODE );
			if ( ! $nonce_is_valid ) {
				$valid_request = false;
			}
		}
		return $valid_request;
	}
}