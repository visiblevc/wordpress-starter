<?php

class WPML_Basket_Tab_Ajax {

	/** @var  TranslationProxy_Project $project */
	private $project;

	/** @var  WPML_Translation_Proxy_Basket_Networking $networking */
	private $networking;

	/** @var  WPML_Translation_Basket $basket */
	private $basket;

	/**
	 * @param TranslationProxy_Project                 $project
	 * @param WPML_Translation_Proxy_Basket_Networking $networking
	 * @param WPML_Translation_Basket                  $basket
	 */
	function __construct( $project, $networking, $basket ) {
		$this->project    = $project;
		$this->networking = $networking;
		$this->basket     = $basket;
	}

	function init() {
		$request = filter_input( INPUT_POST, 'action' );
		$nonce   = filter_input( INPUT_POST, '_icl_nonce' );
		if ( $request && $nonce && wp_verify_nonce( $nonce, $request . '_nonce' ) ) {
			add_action( 'wp_ajax_send_basket_items', array( $this, 'begin_basket_commit' ) );
			add_action( 'wp_ajax_send_basket_item', array( $this, 'send_basket_chunk' ) );
			add_action( 'wp_ajax_send_basket_commit', array( $this, 'send_basket_commit' ) );
			add_action( 'wp_ajax_check_basket_name', array( $this, 'check_basket_name' ) );
		}
	}

	/**
	 * Handler for the ajax call to commit a chunk of the items in a batch provided in the request.
	 *
	 * @uses \WPML_Translation_Proxy_Basket_Networking::commit_basket_chunk
	 *
	 */
	function send_basket_chunk() {
		/**
		 * The items to be committed in this request
		 * @var array $batch
		 */
		$batch = isset( $_POST['batch'] ) ? $_POST['batch'] : array();
		/**
		 * The translators to be used for these items
		 * @var array $translators
		 */
		$translators = isset( $_POST['translators'] ) ? $_POST['translators'] : array();
		/** @var string $basket_name */
		$basket_name = isset( $_POST['basket_name'] ) ? $_POST['basket_name'] : '';

		list( $has_error, $data, $error ) = $this->networking->commit_basket_chunk( $batch, $basket_name, $translators );

		if ( $has_error === true ) {
			wp_send_json_error( $data );
		} else {
			wp_send_json_success( $data );
		}
	}

	/**
	 * Ajax handler for the first ajax request call in the basket commit workflow, responding with an message
	 * containing information about the basket's contents.
	 *
	 * @uses \WPML_Basket_Tab_Ajax::create_remote_batch_message
	 */
	function begin_basket_commit() {
		$basket_name = filter_input( INPUT_POST, 'basket_name', FILTER_SANITIZE_STRING );

		wp_send_json_success( $this->create_remote_batch_message( $basket_name ) );
	}

	/**
	 * Last ajax call in the multiple ajax calls made during the commit of a batch.
	 * Empties the basket in case the commit worked error free responds to the ajax call.
	 *
	 */
	function send_basket_commit() {
		$errors = array();
		try {
			$translators            = isset( $_POST['translators'] ) ? $_POST['translators'] : array();
			$has_remote_translators = $this->networking->contains_remote_translators( $translators );
			$response               = $this->project && $has_remote_translators ? $this->project->commit_batch_job() : true;
			$response               = ! empty( $this->project->errors ) ? false : $response;
			if ( $response !== false && is_object( $response ) ) {
				$response->call_to_action = '<strong>' . sprintf(
						__(
							'You have sent items to %s. Please check if additional steps are required on their end',
							'wpml-translation-management'
						),
						$this->project->current_service_name()
					) . '</strong>';
			}

			$errors = $response === false && $this->project ? $this->project->errors : $errors;
		} catch ( Exception $e ) {
			$response = false;
			$errors[] = $e->getMessage();
		}

		$this->send_json_response( $response, $errors );
	}

	/**
	 * Ajax handler for checking if a current basket/batch name is valid for use with the currently used translation
	 * service.
	 *
	 * @uses \WPML_Translation_Basket::check_basket_name
	 */
	function check_basket_name() {
		$basket_name            = filter_input( INPUT_POST, 'basket_name', FILTER_SANITIZE_STRING );
		$basket_name_max_length = TranslationProxy::get_current_service_batch_name_max_length();

		wp_send_json_success( $this->basket->check_basket_name( $basket_name, $basket_name_max_length ) );
	}

	private static function sanitize_errors( $source ) {
		if ( is_array( $source ) ) {
			if ( $source && array_key_exists( 'errors', $source ) ) {
				foreach ( $source['errors'] as &$error ) {
					if ( is_array( $error ) ) {
						$error = self::sanitize_errors( $error );
					} else {
						$error = ICL_AdminNotifier::sanitize_and_format_message( $error );
					}
				}
				unset( $error );
			}
		} else {
			$source = ICL_AdminNotifier::sanitize_and_format_message( $source );
		}

		return $source;
	}

	/**
	 * Sends the response to the ajax for \WPML_Basket_Tab_Ajax::send_basket_commit and rolls back the commit
	 * in case of any errors.
	 *
	 * @see  \WPML_Basket_Tab_Ajax::send_basket_commit
	 * @uses \WPML_Translation_Proxy_Basket_Networking::rollback_basket_commit
	 * @uses \WPML_Translation_Basket::delete_all_items
	 *
	 * @param object|bool $response
	 * @param array       $errors
	 */
	private function send_json_response( $response, $errors ) {
		$result = array(
			'result'   => $response,
			'is_error' => ! ( $response && empty( $errors ) ),
			'errors'   => $errors
		);
		if ( ! empty( $errors ) ) {
			$this->networking->rollback_basket_commit( filter_input( INPUT_POST,
			                                                         'basket_name',
			                                                         FILTER_SANITIZE_STRING ) );
			wp_send_json_error( self::sanitize_errors( $result ) );
		} else {
			$this->basket->delete_all_items();

			wp_send_json_success( $result );
		}
	}

	/**
	 * Creates the message that is shown before committing a batch.
	 *
	 * @see \WPML_Basket_Tab_Ajax::begin_basket_commit
	 *
	 * @param string $basket_name
	 *
	 * @return array
	 */
	private function create_remote_batch_message( $basket_name ) {
		if ( $basket_name ) {
			$this->basket->set_name( $basket_name );
		}
		$basket             = $this->basket->get_basket();
		$basket_items_types = $this->basket->get_item_types();
		if ( ! $basket ) {
			$message_content = __( 'No items found in basket', 'sitepress' );
		} else {
			$total_count             = 0;
			$message_content_details = '';
			foreach ( $basket_items_types as $item_type_name => $item_type ) {
				if ( isset( $basket[ $item_type_name ] ) ) {
					$count_item_type = count( $basket[ $item_type_name ] );
					$total_count += $count_item_type;
					$message_content_details .= '<br/>';
					$message_content_details .= '- ' . $item_type_name . '(s): ' . $count_item_type;
				}
			}
			$message_content = sprintf( __( '%s items in basket:', 'sitepress' ), $total_count );
			$message_content .= $message_content_details;
		}
		$container = $message_content;

		return array(
			'message'            => $container,
			'basket'             => $basket,
			'allowed_item_types' => array_keys( $basket_items_types )
		);
	}
}