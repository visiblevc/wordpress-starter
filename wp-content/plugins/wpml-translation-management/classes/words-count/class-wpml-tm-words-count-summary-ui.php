<?php

/**
 * Created by OnTheGo Systems
 */
class WPML_TM_Words_Count_Summary_UI extends WPML_Templates_Factory {
	/**
	 * @var WPML_TM_Words_Count
	 */
	private $wpml_tm_words_count;
	/**
	 * @var WPML_WP_API
	 */
	private $wpml_wp_api;

	public $rows;

	/**
	 * @param WPML_TM_Words_Count $wpml_tm_words_count
	 * @param WPML_WP_API         $wpml_wp_api
	 */
	function __construct( &$wpml_tm_words_count, &$wpml_wp_api ) {
		parent::__construct();
		$this->rows                = array();
		$this->wpml_wp_api         = &$wpml_wp_api;
		$this->wpml_tm_words_count = &$wpml_tm_words_count;
	}

	public function get_model() {
		$model = array(
			'strings' => array(
				'noResults' => __( 'No results', 'wpml-translation-management' ),
				'columns'   => array(
					'type'     => __( 'Content type', 'wpml-translation-management' ),
					'count'    => array(
						'total'        => __( 'Items', 'wpml-translation-management' ),
						'untranslated' => __( 'Items - untranslated (total)', 'wpml-translation-management' ),
					),
					'words'    => array(
						'total'        => __( 'Words', 'wpml-translation-management' ),
						'untranslated' => __( 'Words - untranslated (total)', 'wpml-translation-management' ),
					),
					'sumTotal' => __( 'Totals', 'wpml-translation-management' ),
				),
			),
			'rows'    => $this->rows,
		);

		return $model;
	}

	protected function init_template_base_dir() {
		$this->template_paths = array(
			WPML_TM_PATH . '/templates/words-count/',
		);
	}

	public function get_template() {
		return 'summary.twig';
	}
}