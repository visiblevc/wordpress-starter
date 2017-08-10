<?php

class WPML_TM_Requirements {
	private $missing;
	private $missing_one;

	public function __construct() {
		$this->missing     = array();
		$this->missing_one = false;
		add_action( 'admin_notices', array( $this, 'missing_plugins_warning' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded_action' ), 999999 );
		add_action( 'wpml_loaded', array( $this, 'missing_php_extensions' ) );
	}

	private function check_required_plugins() {
		$this->missing     = array();
		$this->missing_one = false;

		if ( ! defined( 'ICL_SITEPRESS_VERSION' )
				 || ICL_PLUGIN_INACTIVE
				 || version_compare( ICL_SITEPRESS_VERSION, '2.0.5', '<' )
		) {
			$this->missing['WPML'] = array( 'url' => 'http://wpml.org', 'slug' => 'sitepress-multilingual-cms' );
			$this->missing_one     = true;
		}
	}

	public function plugins_loaded_action() {
		$this->check_required_plugins();

		if ( ! $this->missing_one ) {
			do_action( 'wpml_tm_has_requirements' );
		}
	}

	public function missing_php_extensions() {
		$extensions = array();
		if ( ini_get( 'allow_url_fopen' ) !== '1' ) {
			$extensions['allow_url_fopen'] = array(
					'type'             => 'setting',
					'type_description' => __( 'PHP Setting', 'wpml-translation-management' ),
					'value'            => '1',
			);
		}
		if ( ! extension_loaded( 'openssl' ) ) {
			$extensions['openssl'] = array(
					'type'             => 'extension',
					'type_description' => __( 'PHP Extension', 'wpml-translation-management' ),
			);
		}

		if ( ! defined( 'ICL_HIDE_TRANSLATION_SERVICES' ) || ! ICL_HIDE_TRANSLATION_SERVICES ) {
			
			$wpml_wp_api_check = new WPML_WP_API();
			
			if ( count($extensions) > 0 && $wpml_wp_api_check->is_tm_page() ) {
				$message = '';
				$message .= '<p>';
				$message .= __('WPML Translation Management requires the following PHP extensions and settings:', 'wpml-translation-management');
				$message .= '</p>';
				$message .= '<ul>';

				foreach ($extensions as $id => $data) {
					$message .= '<li>';
					if ('setting' === $data['type']) {
						$message .= $data['type_description'] . ': <code>' . $id . '=' . $data['value'] . '</code>';
					}
					if ('extension' === $data['type']) {
						$message .= $data['type_description'] . ': <strong>' . $id . '</strong>';
					}
					$message .= '</li>';
				}
				$message .= '</ul>';

				$args = array(
						'id' => 'wpml-tm-missing-extensions',
						'group' => 'wpml-tm-requirements',
						'msg' => $message,
						'type' => 'error',
						'admin_notice' => true,
						'hide' => true,

				);

				ICL_AdminNotifier::add_message($args);

			} else {
				ICL_AdminNotifier::remove_message_group('wpml-tm-requirements');
			}
		}
	}

	/**
	 * Missing plugins warning.
	 */
	public function missing_plugins_warning() {
		if ( $this->missing ) {
			$missing = '';
			$missing_slugs = array();
			$counter = 0;
			foreach ( $this->missing as $title => $data ) {
				$url = $data['url'];
				$missing_slugs[] = 'wpml-missing-' . sanitize_title_with_dashes( $data['slug'] );
				$counter ++;
				$sep = ', ';
				if ( count( $this->missing ) === $counter ) {
					$sep = '';
				} elseif ( count( $this->missing ) - 1 === $counter ) {
					$sep = ' ' . __( 'and', 'wpml-translation-management' ) . ' ';
				}
				$missing .= '<a href="' . $url . '">' . $title . '</a>' . $sep;
			}

			$missing_slugs_classes = implode( ' ', $missing_slugs );
			?>
			<div class="message error wpml-admin-notice wpml-tm-inactive <?php echo $missing_slugs_classes; ?>"><p><?php printf( __( 'WPML Translation Management is enabled but not effective. It requires %s in order to work.', 'wpml-translation-management' ), $missing ); ?></p></div>
			<?php
		}
	}
}