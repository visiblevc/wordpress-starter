<?php

class WPML_TP_Pickup_Box_Ajax_Action extends WPML_SP_User {

	/** @var WPML_TP_Polling_Status_Factory $polling_pickup_factory */
	private $polling_pickup_factory;

	/** @var  TranslationProxy_Project $project */
	private $project;

	/**
	 * WPML_TP_Pickup_Box_Ajax constructor.
	 *
	 * @param SitePress                      $sitepress
	 * @param WPML_TP_Polling_Status_Factory $polling_pickup_factory
	 * @param TranslationProxy_Project       $project
	 */
	public function __construct(
		&$sitepress,
		&$polling_pickup_factory,
		$project
	) {
		parent::__construct( $sitepress );
		$this->polling_pickup_factory = &$polling_pickup_factory;
		$this->project                = $project;
	}

	/**
	 * @return array index 0 contains the callback to be invoked, index 1 contains an array of strings used to display the output of the polling status box.
	 */
	public function run() {
		$translation_offset = strtotime( current_time( 'mysql', 1 ) ) - (int) $this->sitepress->get_setting( 'last_picked_up' ) - 5 * 60;
		if ( $this->sitepress->get_wp_api()->constant( 'WP_DEBUG' ) === false && $translation_offset < 0 ) {
			$translation_offset = abs( $translation_offset );
			$time_left          = floor( $translation_offset / 60 );
			if ( $time_left == 0 ) {
				$wait_text = '<p><i>' . sprintf( __( 'You can check again in %s seconds.',
						'sitepress' ),
						'<span id="icl_sec_tic">' . $translation_offset . '</span>' ) . '</i></p>';
			} else {
				$wait_text = sprintf( __( 'You can check again in %s minutes.',
						'sitepress' ),
						'<span id="icl_sec_tic">' . $time_left . '</span>' ) . '</i></p>';
			}
			$result = array(
				'wait_text' => $wait_text,
			);
		} else {
			try {
				$result = $this->polling_pickup_factory
					->polling_status( $this->project )
					->get_status_array();
			} catch ( Exception $e ) {
				$result   = array(
					'error' => __( 'The below exception has occurred while communicating with Translation Proxy, please try again later or contact support if the problem persists:',
							'sitepress' ) . "\n" . $e->getMessage()
				);
				$callback = 'wp_send_json_error';
			}
		}

		return array(
			isset( $callback ) ? $callback : 'wp_send_json_success',
			$result
		);
	}
}