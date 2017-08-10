<?php

/**
 * @package wpml-core
 * @subpackage wpml-user-language
 */
class WPML_Users_Languages_Dependencies {
	public $WPML_User_Language_Switcher_Hooks;
	private $WPML_User_Language_Switcher_Resources;
	private $WPML_User_Language_Switcher_UI;
	public $WPML_Users_Languages;
	public $WPML_User_Language;
	private $WPML_User_Language_Switcher;
	private $WPML_Language_Code;
	private $WPML_WP_API;
	private $WPML_Upgrade_Admin_Users_Languages;

	function __construct( &$sitepress ) {

		$this->WPML_WP_API                           = new WPML_WP_API();
		$this->WPML_Language_Code                    = new WPML_Language_Code( $sitepress );
		$this->WPML_Users_Languages                  = new WPML_Users_Languages( $this->WPML_Language_Code, $this->WPML_WP_API );
		$this->WPML_User_Language                    = new WPML_User_Language( $sitepress );
		$this->WPML_User_Language_Switcher           = new WPML_User_Language_Switcher( $this->WPML_Language_Code );
		$this->WPML_User_Language_Switcher_Resources = new WPML_User_Language_Switcher_Resources();
		$this->WPML_User_Language_Switcher_UI        = new WPML_User_Language_Switcher_UI( $this->WPML_User_Language_Switcher, $this->WPML_User_Language_Switcher_Resources );
		$this->WPML_User_Language_Switcher_Hooks     = new WPML_User_Language_Switcher_Hooks( $this->WPML_User_Language_Switcher, $this->WPML_User_Language_Switcher_UI );
		$this->WPML_Upgrade_Admin_Users_Languages    = new WPML_Upgrade_Admin_Users_Languages( $sitepress );
	}
}
