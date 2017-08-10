<?php

class WPML_ST_Upgrade_Migrate_Originals extends WPML_WPDB_And_SP_User implements IWPML_St_Upgrade_Command {
	

	private $translations = array();
	private $not_translated = array();
	private $active_languages;
	
	public function __construct( &$wpdb, &$sitepress ) {
		parent::__construct( $wpdb, $sitepress );
		$active_languages = $this->sitepress->get_active_languages();
		foreach( $active_languages as $lang ) {
			$this->active_languages[] = $lang['code'];
		}
	}

	public static function get_command_id() {
		return __CLASS__;
	}
	
	public function run() {
		if ( $this->is_migration_required() ) {
			if ( current_user_can( 'manage_options' ) ) {
				$this->sitepress->get_wp_api()->add_action( 'admin_notices', array( $this, 'update_message' ) );
			}
			return false;
		} else {
			return true;
		}
	}
	
	function update_message(){
		?>
			<div id="wpml-st-upgrade-migrate-originals" class="update-nag" style="display:block">
				<p>
					<?php printf(__('WPML needs to update the database. This update will help improve WPML\'s performance when fetching translated strings.', 'wpml-string-translation') ); ?>
					<br /><br />
					<button class="wpml-st-upgrade-migrate-originals"><?php esc_html_e( 'Update Now', 'wpml-string-translation' ); ?></button> <span class="spinner" style="float: none"></span>
				</p>
                <?php wp_nonce_field( 'wpml-st-upgrade-migrate-originals-nonce', 'wpml-st-upgrade-migrate-originals-nonce' ); ?>
			</div>
			<div id="wpml-st-upgrade-migrate-originals-complete" class="update-nag" style="display:none">
				<p>
					<?php printf(__('The database has been updated.', 'wpml-string-translation') ); ?>
					<br /><br />
					<button class="wpml-st-upgrade-migrate-originals-close"><?php esc_html_e( 'Close', 'wpml-string-translation' ); ?></button>
				</p>
                <?php wp_nonce_field( 'wpml-st-upgrade-migrate-originals-nonce', 'wpml-st-upgrade-migrate-originals-nonce' ); ?>
			</div>
			<script type="text/javascript">
				jQuery( function( $ ) {
					jQuery( '.wpml-st-upgrade-migrate-originals' ).click( function() {
						jQuery( this ).prop( 'disabled', true );
						jQuery( this ).parent().find( '.spinner' ).css( 'visibility', 'visible' );
						jQuery.ajax({
							url: ajaxurl,
							type: "POST",
							data: {
								action: 'wpml-st-upgrade-migrate-originals',
								nonce: jQuery( '#wpml-st-upgrade-migrate-originals-nonce' ).val()
							},
							success: function ( response ) {
								jQuery( '#wpml-st-upgrade-migrate-originals' ).hide();
								jQuery( '#wpml-st-upgrade-migrate-originals-complete' ).css( 'display', 'block' );
							}
						});
					});
					jQuery( '.wpml-st-upgrade-migrate-originals-close' ).click( function() {
						jQuery( '#wpml-st-upgrade-migrate-originals-complete' ).hide();
					});
				});
			</script>
		<?php
	}
	
	public function run_ajax() {
		
		if ( $this->is_migration_required() ) {
			$this->get_strings_without_translations();
			$this->get_originals_with_translations();
			$this->migrate_translations();
		}

		return true;
	}

	public function run_frontend() {}


	private function is_migration_required() {
		$query = "
					SELECT id
					FROM {$this->wpdb->prefix}icl_strings
					WHERE context LIKE 'plugin %' OR context LIKE 'theme %'
					LIMIT 1";
		$found = $this->wpdb->get_var( $query );
		return $found > 0;
	}
	
	private function get_strings_without_translations() {
		
		foreach( $this->active_languages as $lang ) {
			$res_args    = array( $lang, $lang );
		
			$res_query                     = "
								SELECT
									s.value,
									s.id
								FROM {$this->wpdb->prefix}icl_strings s
								WHERE s.id NOT IN (
									SELECT st.string_id FROM {$this->wpdb->prefix}icl_string_translations st
									WHERE st.language=%s
									)
								AND s.language!=%s
								";
			$res_prepare                   = $this->wpdb->prepare( $res_query, $res_args );
			$this->not_translated[ $lang ] = $this->wpdb->get_results( $res_prepare, ARRAY_A );
		}
		
	}
	
	private function get_originals_with_translations() {
		
		foreach( $this->active_languages as $lang ) {
			$res_args    = array( ICL_TM_COMPLETE, $lang );
		
			$res_query                   = "
								SELECT
									st.value AS tra,
									s.value AS org
								FROM {$this->wpdb->prefix}icl_strings s
								LEFT JOIN {$this->wpdb->prefix}icl_string_translations st
									ON s.id=st.string_id
								WHERE st.status=%d AND st.language=%s
								";
			$res_prepare                 = $this->wpdb->prepare( $res_query, $res_args );
			$result = $this->wpdb->get_results( $res_prepare, ARRAY_A );
			$strings = array();
			foreach ( $result as $string ) {
				$strings[ $string['org'] ] = $string['tra'];
			}
			$this->translations[ $lang ] = $strings;
		}
	}
	
	private function migrate_translations() {
		
		foreach( $this->active_languages as $lang ) {
			foreach( $this->not_translated[ $lang ] as $not_translated ) {
				
				if ( isset( $this->translations[ $lang ][ $not_translated['value'] ] ) ) {
					icl_add_string_translation( $not_translated['id'], $lang, $this->translations[ $lang ][ $not_translated['value'] ], ICL_TM_COMPLETE );
					break;
				}
			}
		}		
	}
	
}

