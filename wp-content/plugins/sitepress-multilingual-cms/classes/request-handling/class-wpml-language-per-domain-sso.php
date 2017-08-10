<?php

/**
 * Class WPML_Language_Per_Domain_SSO
 */
class WPML_Language_Per_Domain_SSO {

	const WPML_LANGUAGE_PER_DOMAIN_SSO_NONCE_ACTION = '_wpml_gen_iframe_content';
	const WPML_LANGUAGE_PER_DOMAIN_SSO_CACHE_KEY = '_wpml_user_signed_nonce';
	const WPML_LANGUAGE_PER_DOMAIN_SSO_TIMEOUT = MINUTE_IN_SECONDS;
	private $site_url;
	private $domains;
	private $sitepress;

	/**
	 * WPML_Language_Per_Domain_SSO constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( $sitepress ) {
		$this->sitepress        = $sitepress;
		$this->site_url         = $this->sitepress->convert_url( $this->sitepress->get_wp_api()->get_home_url(), $this->sitepress->get_default_language() );
		$this->domains          = $this->get_domains();
	}

	public function init_hooks() {

		// Add iframe
		add_action( 'wp_footer',                         array( $this, 'add_to_footer' ) );
		add_action( 'admin_footer',                      array( $this, 'add_to_footer' ) );
		add_action( 'login_footer',                      array( $this, 'add_to_footer' ) );
		add_action( 'init',                              array( $this, 'create_iframe_content' ) );

		// Enqueue scripts
		add_action( 'wp_enqueue_scripts',                array( $this, 'enqueue_sso_script' ) );
		add_action( 'admin_enqueue_scripts',             array( $this, 'enqueue_sso_script' ) );
		add_action( 'login_enqueue_scripts',             array( $this, 'enqueue_sso_script' ) );

		// Add AJAX actions
		add_action( 'wp_ajax_wpml_sign_in_user',             array( $this, 'sign_in_user' ) );
		add_action( 'wp_ajax_nopriv_wpml_sign_in_user',      array( $this, 'sign_in_user' ) );

		add_action( 'wp_ajax_wpml_sign_out_user',            array( $this, 'sign_out_user' ) );
		add_action( 'wp_ajax_nopriv_wpml_sign_out_user',     array( $this, 'sign_out_user' ) );

		// Add and remove hash keys.
		add_action( 'wp_login',                              array( $this, 'store_hash_key_in_db' ) );
		add_action( 'wp_logout',                             array( $this, 'store_hash_key_in_db' ) );

		add_action( 'wpml_delete_sso_logged_in_time_option', array( $this, 'wpml_delete_sso_logged_in_time_option' ) );
	}

	/**
	 * Delete expired key for SSO.
	 */
	public function wpml_delete_sso_logged_in_time_option() {
	    delete_option( 'wpml_sso_logged_in_time' );
	}

	/**
	 * Add content of iframe.
	 * This function is hooked very early and exits to avoid loadind all sites.
	 */
	public function create_iframe_content() {
		if ( $this->should_add_content_to_iframe() ) {
			$nonce = wp_create_nonce( self::WPML_LANGUAGE_PER_DOMAIN_SSO_NONCE_ACTION );
			?>
			<script>
				function sendXHRHttpRequest( params ) {
					var xhr = new XMLHttpRequest();
					xhr.open( 'POST', "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>", true );
					xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					xhr.send(params);
				}
				window.onmessage = function(e) {
					var payload = JSON.parse(e.data),
						params = '',
						domains = '<?php echo json_encode( $this->domains ); ?>';

					domains = Object.values( JSON.parse( domains ) );
					if ( -1 === domains.indexOf(e.origin) ) {
						return;
					}

					if ( 'wpml_is_user_signed_in' === payload.key ) {
						params = 'action=wpml_sign_in_user&nonce=' + "<?php echo esc_attr( $nonce ); ?>" + '&user_id=' + parseInt(payload.data);
					} else if ( 'wpml_is_user_signed_out' === payload.key ) {
						params = 'action=wpml_sign_out_user&nonce=' + "<?php echo esc_attr( $nonce ); ?>";
					}
					sendXHRHttpRequest( params );
				};
			</script>
			<?php
			exit();
		}
	}

	/**
	 * Add iframe to footer.
	 */
	public function add_to_footer() {
		foreach ( $this->domains as $domain ) {
			$nonce = $this->get_url_hash( $domain );
			if ( $nonce !== $this->get_url_hash() && get_option( 'wpml_sso_logged_in_time' ) ) {
				?>
				<iframe class="wpml_iframe" style="display:none" src="<?php echo esc_url( $domain ); ?>/?gen_iframe=true&_wpml_gen_iframe_nonce=<?php echo esc_attr( $nonce ); ?>"></iframe>
				<?php
			}
		}
	}

	public function enqueue_sso_script() {
		wp_enqueue_script(
			'wpml_lang_per_domain_sso',
			plugins_url( '../../res/js/wpml-language-per-domain-sso.js', __FILE__ ),
			array( 'jquery' )
		);

		$nonce = wp_create_nonce( self::WPML_LANGUAGE_PER_DOMAIN_SSO_NONCE_ACTION );
		wp_localize_script(
			'wpml_lang_per_domain_sso',
			'wpml_sso',
			array(
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
				'is_user_logged_in' => is_user_logged_in(),
				'current_user_id'   => get_current_user_id(),
				'nonce'             => $nonce,
				'is_expired'        => ! get_option( 'wpml_sso_logged_in_time' ),
			)
		);
	}

	/**
	 * Handle AJAX request and sign in user only if key in DB is present.
	 */
	public function sign_in_user() {
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : false;
		$url_hash = $this->get_url_hash();
		$sso_nonce_from_origin = get_option( $url_hash );
		if ( $user_id
		     && $sso_nonce_from_origin
		     && $this->is_valid_ajax()
		     && ! is_user_logged_in()
		     && $sso_nonce_from_origin === $url_hash ) {

			wp_set_auth_cookie( $user_id );
			delete_option( $url_hash );
			wp_schedule_single_event( time() + self::WPML_LANGUAGE_PER_DOMAIN_SSO_TIMEOUT, 'wpml_delete_sso_logged_in_time_option' );
		}
	}

	/**
	 * Handle AJAX sign out.
	 */
	public function sign_out_user() {
		if ( $this->is_valid_ajax() && is_user_logged_in() ) {
			wp_clear_auth_cookie();
			$url_hash = $this->get_url_hash();
			delete_option( $url_hash );
			wp_schedule_single_event( time() + self::WPML_LANGUAGE_PER_DOMAIN_SSO_TIMEOUT, 'wpml_delete_sso_logged_in_time_option' );
		}
	}

	/**
	 * Clear our hash key from db
	 */
	public function remove_hash_key_from_db() {
		foreach ( $this->domains as $domain ) {
			delete_option( $this->get_url_hash( $domain ) );
		}
	}

	/**
	 * Store specific key to DB, to check later in other domains.
	 */
	public function store_hash_key_in_db() {
		update_option( 'wpml_sso_logged_in_time', time(), false );
		foreach ( $this->domains as $domain ) {
			$hash_url = $this->get_url_hash( $domain );
			update_option( $hash_url, $hash_url, false );
		}
	}

	/**
	 * Create URL hash.
	 *
	 * @param null $url
	 *
	 * @return string
	 */
	private function get_url_hash( $url = null ) {
		if ( ! $url ) {
			$protocol = 'http://';
			$host     = '';

			if ( is_ssl() ) {
				$protocol = 'https://';
			}
			if ( array_key_exists( 'HTTP_HOST', $_SERVER ) ) {
				$host = (string) $_SERVER['HTTP_HOST'];
			}
			$url = $protocol . $host;
		}

		return hash( 'sha256', self::WPML_LANGUAGE_PER_DOMAIN_SSO_NONCE_ACTION . $url . get_option( 'wpml_sso_logged_in_time' ) );
	}

	/**
	 * @return array
	 */
	private function get_domains() {
		$domains = $this->sitepress->get_setting( 'language_domains', array() );
		$scheme = 'http://';
		if ( is_ssl() ) {
			$scheme = 'https://';
		}

		foreach ( $domains as &$domain ) {
			$domain = $scheme . $domain;
		}

		$domains[] = $this->site_url;
		return $domains;
	}

	private function is_valid_ajax() {
		return $this->sitepress->get_wp_api()->is_ajax()
		       && isset( $_POST['nonce'] )
		       && wp_verify_nonce( $_POST['nonce'], self::WPML_LANGUAGE_PER_DOMAIN_SSO_NONCE_ACTION );
	}

	private function should_add_content_to_iframe() {
		return isset( $_GET['gen_iframe'], $_GET['_wpml_gen_iframe_nonce'] )
		       && $_GET['gen_iframe']
		       && ! $this->is_valid_ajax()
		       && $this->get_url_hash() === $_GET['_wpml_gen_iframe_nonce'];
	}
}
