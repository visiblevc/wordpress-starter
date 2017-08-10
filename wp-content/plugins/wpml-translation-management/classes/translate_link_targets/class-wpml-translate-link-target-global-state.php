<?php

class WPML_Translate_Link_Target_Global_State extends WPML_SP_User {

	private $rescan_required;
	const OPTION_NAME = 'WPML_Translate_Link_Target_Global_State';
	const SHOULD_FIX_CONTENT_STATE = 'WPML_Translate_Link_Target_Global_State::should_fix_content';

	public function __construct( SitePress &$sitepress ) {
		parent::__construct( $sitepress );
		$this->rescan_required = $sitepress->get_setting( self::OPTION_NAME, false );
	}

	public function should_fix_content() {
		return $this->sitepress->get_current_request_data( self::SHOULD_FIX_CONTENT_STATE, true );
	}
	
	public function is_rescan_required() {
		return $this->rescan_required;
	}

	public function set_rescan_required() {
		$this->rescan_required = true;
		$this->sitepress->set_setting( self::OPTION_NAME, $this->rescan_required, true );
		$this->sitepress->set_current_request_data( self::SHOULD_FIX_CONTENT_STATE, false );
	}

	public function clear_rescan_required() {
		$this->rescan_required = false;
		$this->sitepress->set_setting( self::OPTION_NAME, $this->rescan_required, true );
	}
}