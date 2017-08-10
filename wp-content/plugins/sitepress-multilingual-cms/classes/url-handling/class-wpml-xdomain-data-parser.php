<?php

class WPML_XDomain_Data_Parser {

	const SCRIPT_HANDLER = 'wpml-xdomain-data';

	/**
	 * @var array $settings
	 */
	private $settings;

	/**
	 * WPML_XDomain_Data_Parser constructor.
	 *
	 * @param array $settings
	 */
	public function __construct( &$settings ) {
		$this->settings = &$settings;
	}

	public function init_hooks() {
		if ( ! isset( $this->settings['xdomain_data'] ) || $this->settings['xdomain_data'] != WPML_XDOMAIN_DATA_OFF ) {
			add_action( 'init', array( $this, 'init' ) );
			add_filter( 'wpml_get_cross_domain_language_data', array( $this, 'get_xdomain_data' ) );
		}
	}

	public function init() {
		add_action( 'wp_ajax_switching_language', array( $this, 'send_xdomain_language_data' ) );
		add_action( 'wp_ajax_nopriv_switching_language', array( $this, 'send_xdomain_language_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts_action' ), 100 );
	}

	public function register_scripts_action() {
		if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) {

			$ls_parameters = WPML_Language_Switcher::parameters();

			$js_xdomain_data = array(
				'css_selector' => $ls_parameters['css_prefix'] . 'item',
			);

			wp_enqueue_script( self::SCRIPT_HANDLER, ICL_PLUGIN_URL . '/res/js/xdomain-data.js', array( 'jquery', 'sitepress' ), ICL_SITEPRESS_VERSION );
			wp_localize_script( self::SCRIPT_HANDLER, 'wpml_xdomain_data', $js_xdomain_data );
		}
	}

	public function set_up_xdomain_language_data(){
		$ret = array();

		$data = apply_filters( 'WPML_cross_domain_language_data', array() );
		$data = apply_filters( 'wpml_cross_domain_language_data', $data );

		if ( ! empty( $data ) ) {

			$encoded_data = json_encode( $data );

			if ( function_exists( 'mcrypt_encrypt' ) && function_exists( 'mcrypt_decrypt' ) ) {
				$key             = substr( NONCE_KEY, 0, 24 );
				$mcrypt_iv_size  = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
				$mcrypt_iv       = mcrypt_create_iv( $mcrypt_iv_size, MCRYPT_RAND );
				$encoded_data = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $key, $encoded_data, MCRYPT_MODE_ECB, $mcrypt_iv );
				$encoded_data = preg_replace('/\x00/', '', $encoded_data); // strip padding added to match the block size
			}

			$base64_encoded_data = base64_encode( $encoded_data );
			$ret['xdomain_data'] = urlencode( $base64_encoded_data );

			$ret['method'] = WPML_XDOMAIN_DATA_POST == $this->settings['xdomain_data'] ? 'post' : 'get';

		}

		return $ret;

	}

	public function send_xdomain_language_data(){

		$data = $this->set_up_xdomain_language_data();

		wp_send_json_success( $data );

	}

	public function get_xdomain_data() {
		$xdomain_data = array();

		if ( isset( $_GET['xdomain_data'] ) || isset( $_POST['xdomain_data'] ) ) {

			$xdomain_data_request = false;

			if ( WPML_XDOMAIN_DATA_GET == $this->settings['xdomain_data'] ) {
				$xdomain_data_request = isset( $_GET['xdomain_data'] ) ? $_GET['xdomain_data'] : false;
			} elseif ( WPML_XDOMAIN_DATA_POST == $this->settings['xdomain_data'] ) {
				$xdomain_data_request = isset( $_POST['xdomain_data'] ) ? urldecode( $_POST['xdomain_data'] ) : false;
			}

			if ( $xdomain_data_request ) {
				$data = base64_decode( $xdomain_data_request );
				if ( function_exists( 'mcrypt_encrypt' ) && function_exists( 'mcrypt_decrypt' ) ) {
					$key             = substr( NONCE_KEY, 0, 24 );
					$mcrypt_iv_size  = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
					$mcrypt_iv       = mcrypt_create_iv( $mcrypt_iv_size, MCRYPT_RAND );
					$data = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB, $mcrypt_iv );
					$data = preg_replace('/\x00/', '', $data);
				}
				$xdomain_data = (array) json_decode( $data, JSON_OBJECT_AS_ARRAY );
			}
		}
		return $xdomain_data;
	}
}
