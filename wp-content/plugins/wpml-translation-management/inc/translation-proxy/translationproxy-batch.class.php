<?php

class TranslationProxy_Batch {

	public static function update_translation_batch(
		$batch_name = false,
		$tp_id = false
	) {
		global $wpdb;

		$batch_name = $batch_name
			? $batch_name
			: ( ( (bool) $tp_id === false || $tp_id === 'local' )
				? self::get_generic_batch_name() : TranslationProxy_Basket::get_basket_name() );
		if ( ! $batch_name ) {
			return null;
		}

		$cache_key   = md5( $batch_name );
		$cache_group = 'update_translation_batch';
		$cache_found = false;

		$batch_id = wp_cache_get( $cache_key, $cache_group, false,
			$cache_found );

		if ( $cache_found && $batch_id ) {
			return $batch_id;
		}

		$batch_id_sql      = "SELECT id FROM {$wpdb->prefix}icl_translation_batches WHERE batch_name=%s";
		$batch_id_prepared = $wpdb->prepare( $batch_id_sql,
			array( $batch_name ) );
		$batch_id          = $wpdb->get_var( $batch_id_prepared );

		if ( ! $batch_id ) {
			$data = array(
				'batch_name'  => $batch_name,
				'last_update' => date( 'Y-m-d H:i:s' )
			);
			if ( $tp_id ) {
				if ( $tp_id === 'local' ) {
					$tp_id = 0;
				}
				$data['tp_id'] = $tp_id;
			}
			$wpdb->insert( $wpdb->prefix . 'icl_translation_batches', $data );
			$batch_id = $wpdb->insert_id;

			wp_cache_set( $cache_key, $batch_id, $cache_group );
		}

		return $batch_id;
	}

	/**
	 * returns the name of a generic batch
	 * name is built based on the current's date
	 *
	 * @return string
	 */
	public static function get_generic_batch_name() {
		$batch_name = 'Manual Translations from ' . date( 'F \t\h\e jS\, Y' );

		return $batch_name;
	}

	/**
	 * returns the id of a generic batch
	 *
	 * @return int
	 */
	private static function create_generic_batch() {
		$batch_name = self::get_generic_batch_name();
		$batch_id   = TranslationProxy_Batch::update_translation_batch( $batch_name );

		return $batch_id;
	}

	public static function maybe_assign_generic_batch( $data ) {
		global $wpdb;

		$batch_id = $wpdb->get_var( $wpdb->prepare( "SELECT batch_id
														 FROM {$wpdb->prefix}icl_translation_status
														 WHERE translation_id=%d",
			$data['translation_id'] ) );

		//if the batch id is smaller than 1 we assign the translation to the generic manual translations batch for today if the translation_service is local
		if ( ( $batch_id < 1 ) && isset( $data ['translation_service'] ) && $data ['translation_service'] == "local" ) {
			//first we retrieve the batch id for today's generic translation batch
			$batch_id = self::create_generic_batch();
			//then we update the entry in the icl_translation_status table accordingly
			$data_where = array( 'rid' => $data['rid'] );
			$wpdb->update( $wpdb->prefix . 'icl_translation_status',
				array( 'batch_id' => $batch_id ),
				$data_where );
		}
	}
}