<?php

class WPML_Change_String_Language_Dialog {
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}


	public function render( ) {
		$all_languages = $this->sitepress->get_languages();
		
		?>
			<div id="wpml-change-language-dialog"
				 class="wpml-change-language-dialog"
				 title="<?php _e( 'Change the language of selected strings', 'wpml-string-translation' ); ?>"
				 style="display:none"
				 data-button-text="<?php _e( 'Apply', 'wpml-string-translation' ); ?>" 
				 data-cancel-text="<?php _e( 'Cancel', 'wpml-string-translation' ); ?>" >
				
				<h2 class="summary js-summary" data-text="<?php _e( 'The selected strings are currently in %LANG%', 'wpml-string-translation' ); ?>"></h2>
				
				<?php _e('Change the language to: ', 'wpml-string-translation'); ?>
				
				<?php
					$lang_selector = new WPML_Simple_Language_Selector( $this->sitepress );
					echo $lang_selector->render();
				?>
				
				<br />
				<span class="spinner"></span>

				<?php wp_nonce_field( 'wpml_change_string_language_nonce', 'wpml_change_string_language_nonce' ); ?>

			</div>
		
		<?php
	}

	/**
	 * @param int[] $strings
	 * @param string $lang
	 *
	 * @return array
	 */
	public function change_language_of_strings( $strings, $lang ) {
		$package_translation = new WPML_Package_Helper();
		$response = $package_translation->change_language_of_strings( $strings, $lang );
		
		if ( $response[ 'success' ] ) {
			$strings_in = implode(',', $strings);
			$update_query   = "UPDATE {$this->wpdb->prefix}icl_strings SET language=%s WHERE id IN ($strings_in)";
			$update_prepare = $this->wpdb->prepare( $update_query, $lang );
			$this->wpdb->query( $update_prepare );
		
			$response[ 'success' ] = true;
			
			foreach( $strings as $string ) {
				icl_update_string_status( $string );
			}
		}
		
		return $response;
	}
}

