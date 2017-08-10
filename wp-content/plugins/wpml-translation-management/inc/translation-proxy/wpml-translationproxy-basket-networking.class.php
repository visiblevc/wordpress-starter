<?php

/**
 * Class WPML_Translation_Proxy_Basket_Networking
 */
class WPML_Translation_Proxy_Basket_Networking {

	/** @var  WPML_Translation_Basket $basket */
	private $basket;

	/** @var  TranslationManagement $tm_instance */
	private $tm_instance;

	/**
	 * @param WPML_Translation_Basket $basket
	 * @param TranslationManagement   $tm_instance
	 */
	function __construct( $basket, &$tm_instance ) {
		$this->basket      = $basket;
		$this->tm_instance = $tm_instance;
	}

	/**
	 * @param array  $batch contains element types and ids of the items that are to be committed
	 * @param string $basket_name name of the current translation basket
	 * @param array  $translators translators to be used for this chunk of elements
	 *
	 * @uses \WPML_Translation_Basket::get_basket Gets the array representation of the translation basket
	 * @uses \WPML_Translation_Proxy_Basket_Networking::generate_batch generates the batch in case no chunk was given for the commit from the basket
	 * @uses \WPML_Translation_Proxy_Basket_Networking::get_batch_name
	 * @uses \WPML_Translation_Proxy_Basket_Networking::send_all_jobs
	 * @uses \WPML_Translation_Proxy_Basket_Networking::rollback_basket_commit rolls back the whole commit action in case of an error.
	 *
	 * @return array
	 */
	function commit_basket_chunk( array $batch, $basket_name, array $translators ) {
		$basket_data = $this->basket->get_basket();
		$batch  = (bool) $batch === true ? $batch : $this->generate_batch( $basket_data );
		if ( (bool) $batch === false ) {
			return array( true, false, array( __( 'Batch is empty', 'sitepress' ) ) );
		}

		foreach ( $batch as $batch_item ) {
			if ( (bool) $basket_name === true ) {
				break;
			}
			$element_type = $batch_item['type'];
			$post_id      = $batch_item['post_id'];
			if ( ! isset( $basket_data[ $element_type ][ $post_id ] ) ) {
				continue;
			}
			$basket_name = $this->get_batch_name( $post_id );
		}

		$result         = $this->send_all_jobs( $batch, $translators, $basket_name );
		$error_messages = $this->tm_instance->messages_by_type( 'error' );
		if ( ( $has_error = (bool) $error_messages ) === true ) {
			$this->rollback_basket_commit( $basket_name );
			$result['message']             = "";
			$result['additional_messages'] = $error_messages;
		}

		return array( $has_error, $result, $error_messages );
	}

	/**
	 * Cancels all remote jobs in the requested batch.
	 *
	 * @param string $posted_basket_name basket name in the currently handled request
	 *
	 * @uses \WPML_Translation_Batch::cancel_all_remote_jobs
	 */
	function rollback_basket_commit( $posted_basket_name ) {
		$this->basket->get_basket( true );
		$basket_name = $this->basket->get_name();
		$basket_name = $basket_name ? $basket_name : $posted_basket_name;
		$batch       = $this->basket->get_basket_batch( $basket_name );
		$batch->cancel_all_remote_jobs();
		$batch->clear_batch_data();
	}

	/**
	 * Checks if an array of translators has any remote translators in it.
	 *
	 * @param array $translators
	 *
	 * @return bool
	 */
	function contains_remote_translators( array $translators ) {

		return count( array_filter( $translators, 'is_numeric' ) ) < count( $translators );
	}

	/**
	 * Sends all jobs from basket in batch mode to translation proxy
	 *
	 * @param string $basket_name
	 * @param array  $batch
	 * @param array  $translators
	 *
	 * @return bool false in case of errors (read from TranslationManagement::get_messages('error') to get errors details)
	 */
	private function send_all_jobs( array $batch, array $translators, $basket_name ) {
		$basket_name_saved = $this->basket->get_name();
		$basket_name       = $basket_name_saved ? $basket_name_saved : $basket_name;
		if ( $basket_name ) {
			$this->basket->set_name( $basket_name );
		}

		$valid_jobs = $this->get_valid_jobs_from_basket( $batch );
		$this->basket->set_remote_target_languages( $this->generate_remote_target_langs( $translators, $valid_jobs ) );
		$basket_items_types = $this->basket->get_item_types();
		foreach ( $basket_items_types as $item_type_name => $item_type ) {
			$type_basket_items = isset( $valid_jobs[ $item_type_name ] ) ? $valid_jobs[ $item_type_name ] : array();
			do_action( 'wpml_tm_send_' . $item_type_name . '_jobs',
			           $item_type_name,
			           $item_type,
			           $type_basket_items,
			           $translators,
			           $basket_name );
		}

		// check if there were no errors
		return ! $this->tm_instance->messages_by_type( 'error' );
	}

	/**
	 * @param array $batch
	 *
	 * @return array
	 */
	private function get_valid_jobs_from_basket( array $batch ) {
		$translation_jobs_basket = array();
		$translation_jobs_basket_full    = $this->basket->get_basket();
		$translation_jobs_basket['name'] = $translation_jobs_basket_full['name'];
		foreach ( $batch as $batch_item ) {
			$element_type = $batch_item['type'];
			$element_id   = $batch_item['post_id'];
			if ( isset( $translation_jobs_basket_full[ $element_type ][ $element_id ] ) ) {
				$translation_jobs_basket[ $element_type ][ $element_id ] = $translation_jobs_basket_full[ $element_type ][ $element_id ];
			}
		}

		return $translation_jobs_basket;
	}

	/**
	 * @param array $translators
	 * @param array $valid_jobs
	 *
	 * @return array
	 */
	private function generate_remote_target_langs( array $translators, array $valid_jobs ) {
		$remote_target_languages = array();
		$basket_items_types = $this->basket->get_item_types();
		foreach ( $basket_items_types as $item_type_name => $item_type ) {
			// check target languages for strings
			if ( ! empty( $valid_jobs[ $item_type_name ] ) ) {
				foreach ( $valid_jobs[ $item_type_name ] as $value ) {
					foreach ( $value['to_langs'] as $target_language => $target_language_selected ) {
						//for remote strings
						if ( array_key_exists( $target_language, $translators ) && $value['from_lang'] != $target_language
						     && ! is_numeric( $translators[ $target_language ] )
						     && $target_language_selected
						     && ! in_array( $target_language, $remote_target_languages, true )
						) {
							$remote_target_languages[] = $target_language;
						}
					}
				}
			}
		}

		return $remote_target_languages;
	}

	/**
	 * Generates the batch array for posts in the basket.
	 *
	 * @param array $basket
	 *
	 * @return array
	 */
	private function generate_batch( array $basket ) {
		$batch = array();

		$posts = isset( $basket['post'] ) ? $basket['post'] : array();
		foreach ( $posts as $post_id => $post ) {
			$batch[] = array( 'type' => 'post', 'post_id' => $post_id );
		}

		return $batch;
	}

	/**
	 * Returns the name of the batch that contains the given post_id.
	 *
	 * @param int $post_id
	 *
	 * @return null|string
	 */
	private function get_batch_name( $post_id ) {
		global $wpdb;

		$name = $wpdb->get_var( $wpdb->prepare(
			"	SELECT b.batch_name
				FROM {$wpdb->prefix}icl_translation_batches b
				JOIN {$wpdb->prefix}icl_translation_status s
					ON s.batch_id = b.id
				JOIN {$wpdb->prefix}icl_translations t
					ON t.translation_id = s.translation_id
				JOIN {$wpdb->prefix}icl_translations o
					ON o.trid = t.trid
						AND o.language_code = t.source_language_code
				JOIN {$wpdb->posts} p
					ON o.element_id = p.ID
						AND o.element_type = CONCAT('post_', p.post_type)
				WHERE o.element_id = %d
				ORDER BY b.id
				LIMIT 1",
			$post_id ) );
		$this->basket->set_name( $name );

		return $name;
	}
}