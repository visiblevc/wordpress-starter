<?php

class WPML_Temporary_Switch_Language extends WPML_SP_User {

	private $old_lang = false;
	
	/**
	 * @param SitePress $sitepress
	 * @param string $target_lang
	 */
	public function __construct( &$sitepress, $target_lang ) {
		parent::__construct( $sitepress );
		$this->old_lang = $sitepress->get_current_language();
		$sitepress->switch_lang( $target_lang );
	}
	
	public function __destruct() {
		$this->restore_lang();
	}
	
	public function restore_lang () {
		if ( $this->old_lang ) {
			$this->sitepress->switch_lang( $this->old_lang );
			$this->old_lang = false;
		}
	}
}
