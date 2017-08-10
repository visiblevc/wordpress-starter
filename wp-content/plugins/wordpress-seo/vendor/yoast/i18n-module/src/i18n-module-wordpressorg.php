<?php

/**
 * The Yoast i18n module with a connection to WordPress.org.
 */
class Yoast_I18n_WordPressOrg_v2 {

	/**
	 * The i18n object that presents the user with the notification.
	 *
	 * @var yoast_i18n_v2
	 */
	protected $i18n;

	/**
	 * Constructs the i18n module for wordpress.org. Required fields are the 'textdomain', 'plugin_name' and 'hook'
	 *
	 * @param array $args The settings for the i18n module.
	 */
	public function __construct( $args ) {
		$args = $this->set_defaults( $args );

		$this->i18n = new Yoast_I18n_v2( $args );
		$this->set_api_url( $args['textdomain'] );
	}

	/**
	 * Sets the default values for wordpress.org
	 *
	 * @param array $args The arguments to set defaults for.
	 *
	 * @return array The arguments with the arguments set.
	 */
	private function set_defaults( $args ) {

		if ( ! isset( $args['glotpress_logo'] ) ) {
			$args['glotpress_logo'] = 'https://plugins.svn.wordpress.org/' . $args['textdomain'] . '/assets/icon-128x128.png';
		}

		if ( ! isset( $args['register_url'] ) ) {
			$args['register_url'] = 'https://translate.wordpress.org/projects/wp-plugins/' . $args['textdomain'] . '/';
		}

		if ( ! isset( $args['glotpress_name'] ) ) {
			$args['glotpress_name'] = 'Translating WordPress';
		}

		if ( ! isset( $args['project_slug'] ) ) {
			$args['project_slug'] = $args['textdomain'];
		}

		return $args;
	}

	/**
	 * Set the API URL on the i18n object.
	 *
	 * @param string $textdomain The textdomain to use for the API URL.
	 */
	private function set_api_url( $textdomain ) {
		$this->i18n->set_api_url( 'https://translate.wordpress.org/api/projects/wp-plugins/' . $textdomain . '/stable/' );
	}
}
