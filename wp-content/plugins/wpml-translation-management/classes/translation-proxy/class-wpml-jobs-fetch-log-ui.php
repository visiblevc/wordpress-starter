<?php

class WPML_Jobs_Fetch_Log_UI extends WPML_Templates_Factory {
	private $action_url;
	private $advanced_mode          = false;
	private $fetch_log_settings;
	private $model                  = array();
	private $sanitize_data_for_html = false;
	private $td_style;
	private $wpml_wp_api;
	private $post_data_headers;

	/**
	 * WPML_Jobs_Pickup_Logger_UI constructor.
	 *
	 * @param WPML_Jobs_Fetch_Log_Settings $fetch_log_settings
	 * @param WPML_WP_API                  $wpml_wp_api
	 */
	public function __construct( &$fetch_log_settings, &$wpml_wp_api ) {
		$this->post_data_headers = array(
			'original_view_url',
			'original_edit_url',
			'translation_view_url',
			'translation_edit_url',
			'translation_status',
		);

		$this->wpml_wp_api = &$wpml_wp_api;

		$is_url_twig_function = new Twig_SimpleFunction( 'is_url', array( $this, 'is_url' ) );

		parent::__construct( array( $is_url_twig_function ) );

		$this->fetch_log_settings = &$fetch_log_settings;

		$advanced_mode_key = $this->fetch_log_settings->get_ui_key() . '-advanced-mode';
		if ( array_key_exists( $advanced_mode_key, $_GET ) ) {
			$this->advanced_mode = (bool) $_GET[ $advanced_mode_key ];
		}
		$clear_log_key = $this->fetch_log_settings->get_ui_key() . '-clear';
		if ( array_key_exists( $clear_log_key, $_POST ) ) {
			delete_option( '_WPML_TP_Pickup_Logger' );
		}

		$this->action_url = 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=' . $this->fetch_log_settings->get_ui_key();
		$this->td_style   = 'font-size:10px; overflow-wrap: break-word; word-wrap: break-word; -ms-word-break: break-all; word-break: break-all; word-break: break-word; -ms-hyphens: auto; -moz-hyphens: auto; -webkit-hyphens: auto; hyphens: auto;';

		add_action( 'wp_loaded', array( $this, 'export_csv_action' ) );
	}

	public function export_csv_action() {
		$export_csv = $this->fetch_log_settings->get_ui_key() . '-export-csv';
		if ( array_key_exists( $export_csv, $_POST ) ) {
			$advanced_mode_backup = $this->advanced_mode;
			$this->advanced_mode  = true;

			$this->sanitize_data_for_html = false;

			$this->init_log_model();

			$logger              = new WPML_Jobs_Fetch_Log_Utils( $this->wpml_wp_api, $this->fetch_log_settings );
			$this->advanced_mode = $advanced_mode_backup;

			$logger->stream_csv( $this->model['rows'] );
		}
	}

	/**
	 * @return array
	 */
	private function get_model_rows() {
		$rows          = array();
		$tp_pickup_log = $this->get_log();
		if ( count( $tp_pickup_log ) > 0 ) {
			foreach ( $tp_pickup_log as $log_item ) {
				$row = array();
				foreach ( $log_item as $header_key => $item_value ) {
					$header_label = $this->fetch_log_settings->get_column_label( $header_key );
					if ( $this->show_column( $header_key ) ) {

						if ( ! array_key_exists( $header_label, $this->model['headers'] ) ) {
							$this->model['headers'][ $header_key ] = $header_label;
						}

						if ( is_scalar( $item_value ) ) {
							$item_value = $this->fix_post_data( $log_item, $header_key, $item_value );
							$item_value = $this->fetch_log_settings->format_data( $header_key, $item_value );
							$item_value = $this->sanitize_data( $item_value );
						}

						$row[ $header_label ] = $item_value;
					}
				}
				if ( $row ) {
					$rows[] = $row;
				}
			}
		}

		return $rows;
	}

	private function init_log_model() {

		$this->model = array(
			'strings'       => array(
				'header'     => __( 'The following table lists the content which professional translation updated or created.', 'wpml-translation-management' ),
				'clear_log'  => __( 'Clear', 'wpml-translation-management' ),
				'empty_log'  => __( 'The log is empty.', 'wpml-translation-management' ),
				'export_csv' => __( 'Export full log in CSV', 'wpml-translation-management' ),
			),
			'urls'          => array(
				'main' => $this->action_url,
			),
			'misc'          => array(
				'ui_key' => $this->fetch_log_settings->get_ui_key(),
			),
			'advanced_mode' => $this->advanced_mode,
			'headers'       => array(),
			'rows'          => array(),
		);

		if ( ! $this->advanced_mode ) {
			$this->model['strings']['switch_mode'] = __( 'More information', 'wpml-translation-management' );
			$this->model['urls']['switch_mode']    = $this->action_url . '&' . $this->fetch_log_settings->get_ui_key() . '-advanced-mode=1';
		} else {
			$this->model['strings']['switch_mode'] = __( 'Basic information', 'wpml-translation-management' );
			$this->model['urls']['switch_mode']    = $this->action_url . '&' . $this->fetch_log_settings->get_ui_key() . '-advanced-mode=0';
		}

		$this->model['rows'] = $this->get_model_rows();
	}

	/**
	 * @return array
	 */
	private function get_log() {
		$tp_pickup_log = get_option( $this->fetch_log_settings->get_pickup_logger_option(), array() );

		usort( $tp_pickup_log, array( $this, 'tp_pickup_log_sorter' ) );

		return $tp_pickup_log;
	}

	private function sanitize_data( $item_value ) {
		if ( $this->sanitize_data_for_html ) {
			$item_value = str_replace( "\n", '<br>', $item_value );

			$allowed_html       = wp_kses_allowed_html();
			$allowed_html['br'] = array();
			$item_value         = wp_kses( $item_value, $allowed_html );
		} elseif ( is_array( $item_value ) ) {
			$item_value = implode( "\n", $item_value );
		}

		return $item_value;
	}

	/**
	 * @param $header_key
	 *
	 * @return bool
	 */
	private function show_column( $header_key ) {
		return ! $this->fetch_log_settings->is_hidden_column( $header_key ) && ( ! $this->fetch_log_settings->is_advanced_column( $header_key ) || $this->advanced_mode );
	}

	private function fix_post_data( $log_item, $header_key, $item_value ) {
		if ( ! $item_value
		     && array_key_exists( 'type', $log_item )
		     && 'Post' === $log_item['type']
		     && in_array( $header_key, $this->post_data_headers, true )
		) {
			$translated_id = apply_filters( 'wpml_object_id', $log_item['original_element_id'], $log_item['original_element_type'], false, $log_item['language_code'] );
			switch ( $header_key ) {
				case 'original_view_url':
					$item_value = get_permalink( $log_item['original_element_id'] );
					break;
				case 'original_edit_url':
					$item_value = get_edit_post_link( $log_item['original_element_id'], '' );
					break;
				case 'translation_view_url':
					$item_value = $translated_id ? get_permalink( $translated_id ) : $item_value;
					break;
				case 'translation_edit_url':
					$item_value = $translated_id ? get_edit_post_link( $translated_id, '' ) : $item_value;
					break;
				case 'translation_status':
					$item_value = $translated_id ? get_post_status( $translated_id ) : $item_value;
					break;
			}
		}

		return $item_value;
	}

	public function is_url( $string ) {
		return (bool) filter_var( $string, FILTER_VALIDATE_URL );
	}

	public function render() {
		$this->sanitize_data_for_html = true;
		echo $this->get_view();
	}

	public function tp_pickup_log_sorter( $a, $b ) {
		return $a['timestamp'] < $b['timestamp'];
	}

	protected function init_template_base_dir() {
		$this->template_paths = array(
			WPML_TM_PATH . '/templates/jobs-pickup-logger/',
		);
	}

	public function get_template() {
		return 'log.twig';
	}

	public function get_model() {
		$this->init_log_model();

		return $this->model;
	}
}