<?php

/**
 * Class WPML_TM_XmlRpc_Job_Update
 */
class WPML_TM_Job_Update extends WPML_TP_Project_User {
	private $last_cms_id;
	private $last_job_data;
	private $last_signature;
	private $last_status;
	private $last_translation_proxy_job_id;

	/** @var WPML_Pro_Translation $pro_translation */
	protected $pro_translation;

	const CMS_FAILED  = 0;
	const CMS_SUCCESS = 1;

	/**
	 * WPML_TM_XmlRpc_Job_Update constructor.
	 *
	 * @param WPML_Pro_Translation $pro_translation
	 */
	public function __construct( &$pro_translation, &$project ) {
		$this->pro_translation = &$pro_translation;
		parent::__construct( $project );
	}

	public function get_last_job_data() {
		return $this->last_job_data;
	}

	public function updated_job_status_with_log( $args, $bypass_auth = false ) {
		WPML_TranslationProxy_Com_Log::log_xml_rpc( array(
			                                            'tp_job_id' => $args[0],
			                                            'cms_id'    => $args[1],
			                                            'status'    => $args[2],
			                                            'signature' => 'UNDISCLOSED'
		                                            ) );

		$args[3] = $bypass_auth ? true : $args[3];

		$ret = 'Project does not exist';
		if ( $this->project ) {
			$ret = $this->update_status( $args, $bypass_auth );
		}

		WPML_TranslationProxy_Com_Log::log_xml_rpc( array( 'result' => $ret ) );

		return $ret;
	}

	/**
	 * Handle job update notifications from TP
	 *
	 * @param array $args
	 * @param bool  $bypass_auth if true forces ignoring the signature check when used together with polling
	 *
	 * @throws InvalidArgumentException
	 * @return int|string
	 */
	function update_status( $args, $bypass_auth = false ) {
		if ( ! ( isset( $args[0], $args[1], $args[2], $args[3] ) ) ) {
			throw new InvalidArgumentException( 'This method requires an array of 4 input parameters!' );
		}
		$this->last_translation_proxy_job_id = $args[0];
		$this->last_cms_id                   = $args[1];
		$this->last_status                   = $args[2];
		$this->last_signature                = $args[3];

		if ( ! $bypass_auth && ! $this->authenticate_request() ) {
			return 'Wrong signature';
		}

		$job_data                    = array();
		$job_data['id']              = $this->last_translation_proxy_job_id;
		$job_data['cms_id']          = $this->last_cms_id;
		$job_data['job_state']       = $this->last_status;
		$job_data['source_language'] = false;
		$job_data['target_language'] = false;

		switch ( $this->last_status ) {
			case 'translation_ready' :
				$ret = $this->pro_translation->download_and_process_translation( $this->last_translation_proxy_job_id, $this->last_cms_id );
				break;
			case 'cancelled' :
				$ret = $this->pro_translation->cancel_translation( $this->last_translation_proxy_job_id, $this->last_cms_id );
				break;
			default :
				return "Not supported status: {$this->last_status}";
		}

		$this->last_job_data = $job_data;

		if ( $this->pro_translation->errors ) {
			$result = implode( '', $this->pro_translation->errors );
		} elseif ( (bool) $ret === true ) {
			$result = self::CMS_SUCCESS;
		} else {
			$result = self::CMS_FAILED;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	private function authenticate_request() {

		return sha1( $this->project->id . $this->project->access_key . $this->last_translation_proxy_job_id . $this->last_cms_id . $this->last_status ) === $this->last_signature;
	}
}