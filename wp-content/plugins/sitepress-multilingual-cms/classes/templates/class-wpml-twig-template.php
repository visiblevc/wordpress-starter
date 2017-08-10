<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Twig_Template implements IWPML_Template_Service {
	private $twig;

	/**
	 * WPML_Twig_Template constructor.
	 *
	 * @param Twig_Environment $twig
	 */
	public function __construct( Twig_Environment $twig ) {
		$this->twig = $twig;
	}

	public function show( $model, $template ) {
		return $this->twig->render( $template, $model );
	}
}