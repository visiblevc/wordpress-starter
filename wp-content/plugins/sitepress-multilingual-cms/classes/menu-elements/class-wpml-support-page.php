<?php

class WPML_Support_Page {

	/**
	 * WPML_Support_Page constructor.
	 *
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( &$wpml_wp_api ) {
		$this->wpml_wp_api = &$wpml_wp_api;
		$this->init_hooks();
	}

	public function display_compatibility_issues() {
		$message = $this->get_message();
		$this->render_message( $message );
	}

	/**
	 * @return string
	 */
	private function get_message() {
		$message = '';
		if ( ! $this->wpml_wp_api->extension_loaded( 'libxml' ) ) {
			$message .= $this->missing_extension_message();
			if ( $this->wpml_wp_api->version_compare_naked( $this->wpml_wp_api->phpversion(), '7.0.0', '>=' ) ) {
				$message .= $this->missing_extension_message_for_php7();
			}
			$message .= $this->contact_the_admin();

			return $message;
		}

		return $message;
	}

	private function init_hooks() {
		add_action( 'wpml_support_page_after', array( $this, 'display_compatibility_issues' ) );
	}

	/**
	 * @return string
	 */
	private function missing_extension_message() {
		return '<p class="missing-extension-message">'
		       . esc_html__( 'It looks like the %1$s extension, which is required by WPML, is not installed. Please refer to this link to know how to install this extension: %2$s.', 'sitepress' )
		       . '</p>';
	}

	/**
	 * @return string
	 */
	private function missing_extension_message_for_php7() {
		return '<p class="missing-extension-message-for-php7">' . esc_html__( 'You are using PHP 7: in some cases, the extension might have been removed during a system update. In this case, please see %3$s.', 'sitepress' ) . '</p>';
	}

	/**
	 * @return string
	 */
	private function contact_the_admin() {
		return '<p class="contact-the-admin">' . esc_html__( 'You may need to contact your server administrator or your hosting company to install this extension.', 'sitepress' ) . '</p>';
	}

	/**
	 * @param $message
	 */
	private function render_message( $message ) {
		if ( $message ) {
			$libxml_text      = '<strong>libxml</strong>';
			$libxml_link      = '<a href="http://php.net/manual/en/book.libxml.php" target="_blank">http://php.net/manual/en/book.libxml.php</a>';
			$libxml_php7_link = '<a href="https://wpml.org/errata/php-7-possible-issues-simplexml/" target="_blank">PHP 7: possible issues with simplexml</a>';
			echo '<div class="icl-admin-message icl-admin-message-icl-admin-message-warning icl-admin-message-warning error">';
			echo sprintf( $message, $libxml_text, $libxml_link, $libxml_php7_link );
			echo '</div>';
		}
	}
}
