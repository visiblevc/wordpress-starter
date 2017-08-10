<?php

abstract class WPML_TM_Xliff_Shared extends WPML_TM_Job_Factory_User {
	/** @var  WP_Error $error */
	protected $error;

	/**
	 * @param SimpleXMLElement $xliff
	 *
	 * @return string
	 */
	protected function identifier_from_xliff( $xliff ) {
		$file_attributes = $xliff->{'file'}->attributes();

		return (string) $file_attributes['original'];
	}

	/**
	 * @param SimpleXMLElement $xliff
	 *
	 * @return stdClass|void|WP_Error
	 */
	protected function get_job_for_xliff( $xliff ) {
		$identifier           = $this->identifier_from_xliff( $xliff );
		$job_identifier_parts = explode( '-', (string) $identifier );
		if ( sizeof( $job_identifier_parts ) == 2 && is_numeric( $job_identifier_parts[0] ) ) {
			$job_id = $job_identifier_parts[0];
			$job_id = apply_filters( 'wpml_job_id', $job_id );
			$md5    = $job_identifier_parts[1];
			/** @var stdClass $job */
			$job = $this->job_factory->get_translation_job( (int) $job_id, false, 1, false );
			if ( ! $job || $md5 != md5( $job_id . $job->original_doc_id ) ) {
				$job = $this->does_not_belong_error();
			}
		} else {
			$job = $this->invalid_xliff_error();
		}

		return $job;
	}

	/**
	 * @param $xliff_node
	 *
	 * @return string
	 */
	protected function get_xliff_node_target( $xliff_node ) {
		$target = '';
		if ( isset( $xliff_node->target->mrk ) ) {
			$target = (string) $xliff_node->target->mrk;
		} elseif ( isset( $xliff_node->target ) ) {
			$target = (string) $xliff_node->target;
		}

		return $target;
	}

	protected function generate_job_data( $xliff, $job ) {
		$data = array(
			'job_id'   => $job->job_id,
			'fields'   => array(),
			'complete' => 1
		);
		foreach ( $xliff->file->body->children() as $node ) {
			$attr   = $node->attributes();
			$type   = (string) $attr['id'];
			$target = $this->get_xliff_node_target( $node );

			if ( ! $this->is_valid_unit_content( $target ) ) {
				return $this->invalid_xliff_error( array( 'target' ) );
			}

			foreach ( $job->elements as $element ) {
				if ( strpos($type, $element->field_type ) === 0 || strpos($element->field_type, $type ) === 0) {
					$target              = str_replace( '<br class="xliff-newline" />', "\n", $target );
					$field               = array();
					$field['data']       = $target;
					$field['finished']   = 1;
					$field['tid']        = $element->tid;
					$field['field_type'] = $element->field_type;
					$field['format']     = $element->field_format;

					$data['fields'][] = $field;
					break;
				}
			}
		}

		return $data;
	}

	protected function validate_file( $name, $content, $current_user ) {
		$xml = $this->check_xml_file( $name, $content );

		$this->error = null;
		if ( is_wp_error( $xml ) ) {
			$this->error = $xml;

			return null;
		}

		$job = $this->get_job_for_xliff( $xml );
		if ( is_wp_error( $job ) ) {
			$this->error = $job;

			return null;
		}
		if ( ! $this->is_user_the_job_owner( $current_user, $job ) ) {
			$this->error = $this->not_the_job_owner_error( $job );

			return null;
		}
		$job_data = $this->generate_job_data( $xml, $job );
		if ( is_wp_error( $job_data ) ) {
			$this->error = $job_data;

			return null;
		}

		return array( $job, $job_data );
	}

	/**
	 * @param string $filename 
	 * @return bool
	 */
	function validate_file_name( $filename ) {
		$ignored_files = apply_filters( 'wpml_xliff_ignored_files', array( '__MACOSX' ) );
		return !( preg_match( '/(\/)/', $filename ) || in_array( $filename, $ignored_files, false ) );
	}

	protected function is_user_the_job_owner( $current_user, $job ) {
		return (int) $current_user->ID === (int) $job->translator_id;
	}

	protected function not_the_job_owner_error( $job ) {
		$message = sprintf( __( 'The translation job (%s) doesn\'t belong to you.', 'wpml-translation-management' ), $job->job_id );

		return new WP_Error( 'not_your_job', $message );
	}

	/**
	 * @param string $name
	 * @param string $content
	 *
	 * @return bool|SimpleXMLElement|WP_Error
	 */
	protected function check_xml_file( $name, $content ) {
		$new_error_handler = create_function( '$errno, $errstr, $errfile, $errline', 'throw new ErrorException( $errstr, $errno, 1, $errfile, $errline );' );
		set_error_handler( $new_error_handler );
		try {
			$xml = simplexml_load_string( $content );
		} catch ( Exception $e ) {
			$xml = false;
		}
		restore_error_handler();
		if ( ! $xml || ! isset( $xml->file ) ) {
			$xml = $this->not_xml_file_error( $name );
		}

		return $xml;
	}

	/**
	 * @return WP_Error
	 */
	protected function not_xml_file_error( $name ) {
		$message = sprintf( __( '"%s" is not a valid XLIFF file.', 'wpml-translation-management' ), $name );

		return new WP_Error( 'not_xml_file', $message );
	}

	/**
	 * @param array $missing_data
	 *
	 * @return WP_Error
	 */
	protected function invalid_xliff_error( $missing_data = array() ) {
		$message = __( 'The uploaded xliff file does not seem to be properly formed.', 'wpml-translation-management' );

		if ( $missing_data ) {
			$message .= '<br>' . __( 'Missing or wrong data:', 'wpml-translation-management' );
			if ( count( $missing_data ) > 1 ) {
				$message .= '<ol>';
				$message .= '<li><strong>' . implode( '</strong></li><li><strong>', $missing_data ) . '</strong></li>';
				$message .= '</ol>';
			} else {
				$message .= ' <strong>' . $missing_data[0] . '</strong>';
			}
		}

		return new WP_Error( 'xliff_invalid', $message );
	}

	/**
	 * @return WP_Error
	 */
	protected function does_not_belong_error() {

		return new WP_Error( 'xliff_does_not_match', __( "The uploaded xliff file doesn't belong to this system.", 'wpml-translation-management' ) );
	}
}