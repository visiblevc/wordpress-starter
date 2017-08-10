<?php

/**
 * Created by OnTheGo Systems
 */
class WPML_TM_Words_Count_Box_UI extends WPML_Templates_Factory {
	/**
	 * @var WPML_WP_API
	 */
	private $wpml_wp_api;

	/**
	 * @param WPML_WP_API $wpml_wp_api
	 */
	function __construct( &$wpml_wp_api ) {
		parent::__construct();
		$this->wpml_wp_api = &$wpml_wp_api;

		add_action( 'wpml_tm_dashboard_promo', array(
			$this,
			'render_box_action'
		) );
	}

	public function init_twig_functions() {
		$function = new Twig_SimpleFunction( 'box' );
		$this->get_twig()->addFunction( $function, array( $this, 'get_box' ) );
	}

	public function get_model() {
		$model = array(
			'strings' => array(
				'title'               => __( 'Translation Cost Estimate', 'wpml-translation-management' ),
				'messages'            => array(
					__( 'To estimate the total cost of translating your site, use the word count tool.', 'wpml-translation-management' ),
					__( 'Get the total volume of text to translate and multiply it by the cost per word.', 'wpml-translation-management' ),
				),
				'openDialogButton'    => __( 'Word count tool', 'wpml-translation-management' ),
				'openDialogButtonURL' => '#',
				'callToAction'        => array(
					'Title'    => _x( 'Need help translating?', 'Need help translating?: 00 Title', 'wpml-translation-management' ),
					'Text'     => _x( "Find the service that's right for you, among", 'Need help translating?: 01 Sentence begins', 'wpml-translation-management' ),
					'linkText' => _x( 'WPML-friendly translation services', 'Need help translating?: 02 Link text and sentence ends', 'wpml-translation-management' ),
					'linkURL'  => 'https://wpml.org/translation-service/',
				),
			),
			'wc_chunk_size' => WPML_TM_WC_CHUNK,
			'dialog'  => array(
				'strings'        => array(
					'title'   => __( 'Translation Cost Estimate', 'wpml-translation-management' ),
					'close'   => __( 'Close', 'wpml-translation-management' ),
					'refresh' => __( 'Refresh', 'wpml-translation-management' ),
				),
				'sourceLanguage' => array(
					'strings'         => array(
						'emptyLanguageOptionLabel'    => __( '(select a value)', 'wpml-translation-management' ),
						'sourceLanguageSelectorLabel' => __( 'I need translation from this language', 'wpml-translation-management' )
					),
					'activeLanguages' => apply_filters( 'wpml_active_languages', null ),
					'defaultLanguage' => apply_filters( 'wpml_default_language', null ),
					'currentLanguage' => apply_filters( 'wpml_current_language', null ),
					'nonces'          => array(
						'wpml_words_count_source_language_nonce' => wp_create_nonce( 'wpml_words_count_summary' ),
					),
				),
			),
		);

		return $model;
	}


	public function render_box_action() {
		$this->show();
	}

	protected function init_template_base_dir() {
		$this->template_paths = array(
			WPML_TM_PATH . '/templates/words-count/',
		);
	}

	public function get_template() {
		return 'box.twig';
	}
}