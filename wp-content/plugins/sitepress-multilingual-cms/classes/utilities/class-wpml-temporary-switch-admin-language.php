<?php

class WPML_Temporary_Switch_Admin_Language extends WPML_SP_User {

	private $old_lang = false;
	
	/**
	 * @param SitePress $sitepress
	 * @param string $target_lang
	 */
	public function __construct( &$sitepress, $target_lang ) {
		parent::__construct( $sitepress );
		$this->old_lang = $sitepress->get_admin_language();
		$sitepress->set_admin_language( $target_lang );
	}
	
	public function __destruct() {
		$this->restore_lang();
	}
	
	public function restore_lang () {
		if ( $this->old_lang ) {
			$this->sitepress->set_admin_language( $this->old_lang );
			$this->old_lang = false;
		}
	}
}
