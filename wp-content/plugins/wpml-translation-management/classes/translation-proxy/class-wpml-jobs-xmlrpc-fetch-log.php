<?php

class WPML_Jobs_XMLRPC_Fetch_Log extends WPML_Jobs_Fetch_Log {
	public function __construct( &$pro_translation, &$fetch_log_settings, &$fetch_log_job ) {
		$this->request_type = 'XML-RPC';
		parent::__construct( $pro_translation, $fetch_log_settings, $fetch_log_job );
	}

}