<?php
if ( !function_exists( 'is_translationproxy_api_error' ) ) {
	function is_translationproxy_api_error( $thing ) {
		if ( is_object( $thing ) && is_a( $thing, 'TranslationProxy_Api_Error' ) ) {
			return true;
		}

		return false;
	}
}

if ( !function_exists( 'is_exception' ) ) {
	function is_exception( $thing ) {
		if ( is_object( $thing ) && is_a( $thing, 'TranslationProxy_Api_Error' ) ) {
			return true;
		}

		return false;
	}
}

/**
 * If the given $source is an error type, it will display an instant message
 *
 * @param $source WP_Error|TranslationProxy_Api_Error|Exception
 */
function icl_handle_error( $source ) {
	$error = false;
	if ( is_translationproxy_api_error( $source ) ) {
		$error = array(
				'message' => $source->getMessage(),
				'code'    => $source->getCode(),
		);
	} elseif ( is_exception( $source ) ) {
		$error = array(
				'message' => $source->getMessage(),
				'code'    => $source->getCode(),
		);
	} elseif ( is_wp_error( $source ) ) {
		$error = array(
				'message' => $source->get_error_message(),
				'data'    => $source->get_error_data(),
				'code'    => $source->get_error_code(),
		);
	}

	if ( $error ) {
		$message = '';

		$message .= '<strong>';
		if ( isset( $error[ 'code' ] ) && $error[ 'code' ] ) {
			$message .= '#' . $error[ 'code' ] . ' ';
		}
		$message .= $error[ 'message' ];
		$message .= '</strong>';
		if ( isset( $error[ 'data' ] ) ) {
			foreach ( $error[ 'data' ] as $data_key => $data_item ) {
				if ( $data_key == 'details' ) {
					$message .= '<br/>Details: ' . $data_item;
				} elseif ( $data_key == 'service_id' ) {
					$message .= '<br/>Service ID: ' . $data_item;
				} else {
					$message .= '<br/><pre>' . print_r( $data_item, true ) . '</pre>';
				}
			}
		}
		ICL_AdminNotifier::displayInstantMessage( $message, 'error' );
	}
	return $error;
}

function translation_service_details( $service, $show_project = false ) {
	$service_details = '';
	if (defined( 'OTG_SANDBOX_DEBUG' ) && OTG_SANDBOX_DEBUG ) {
		$service_details .= '<h3>Service details:</h3>' . PHP_EOL;
		$service_details .= '<pre>' . PHP_EOL;
		$service_details .= print_r( $service, true );
		$service_details .= '</pre>' . PHP_EOL;

		if($show_project) {
			$project = TranslationProxy::get_current_project();
			echo '<pre>$project' . PHP_EOL;
			echo print_r( $project, true );
			echo '</pre>';
		}
	}

	return $service_details;
}

if ( !function_exists( 'object_to_array' ) ) {
	function object_to_array( $obj ) {
		if ( is_object( $obj ) ) {
			$obj = (array) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = array();
			foreach ( $obj as $key => $val ) {
				$new[ $key ] = object_to_array( $val );
			}
		} else {
			$new = $obj;
		}

		return $new;
	}
}
