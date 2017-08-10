<?php

/**
 * @package    wpml-core
 * @subpackage wpml-user-language
 */
class WPML_User_Language_Switcher_UI {

	/**
	 * WPML_User_Language_Switcher_UI constructor.
	 *
	 * @param WPML_User_Language_Switcher           $WPML_User_Language_Switcher
	 * @param WPML_User_Language_Switcher_Resources $WPML_User_Language_Switcher_Resources
	 */
	public function __construct( &$WPML_User_Language_Switcher, &$WPML_User_Language_Switcher_Resources ) {
		$this->user_language_switcher = &$WPML_User_Language_Switcher;
		$this->resources              = &$WPML_User_Language_Switcher_Resources;
	}

	public function language_switcher( $args, $model ) {
		$this->resources->enqueue_scripts( $args );

		return $this->get_view( $model );
	}

	/**
	 * @param array $model
	 *
	 * @return string
	 */
	protected function get_view( $model ) {
		$template_paths = array(
			ICL_PLUGIN_PATH . '/templates/user-language/',
		);

		$template = 'language-switcher.twig';

		$loader           = new Twig_Loader_Filesystem( $template_paths );
		$environment_args = array();
		if ( WP_DEBUG ) {
			$environment_args['debug'] = true;
		}

		$twig = new Twig_Environment( $loader, $environment_args );

		return $twig->render( $template, $model );
	}

}
