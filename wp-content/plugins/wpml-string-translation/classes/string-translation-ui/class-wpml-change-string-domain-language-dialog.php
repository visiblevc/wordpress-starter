<?php

class WPML_Change_String_Domain_Language_Dialog extends WPML_WPDB_And_SP_User {

	/** @var  WPML_Language_Of_Domain $language_of_domain */
	private $language_of_domain;

	/** @var  WPML_ST_String_Factory $string_factory */
	private $string_factory;

	public function __construct( &$wpdb, &$sitepress, &$string_factory ) {
		parent::__construct( $wpdb, $sitepress );

		$this->string_factory     = &$string_factory;
		$this->language_of_domain = new WPML_Language_Of_Domain( $sitepress );
	}

	public function render( $domains ) {
		$all_languages = $this->sitepress->get_languages( $this->sitepress->get_admin_language() );
		
		?>
			<div id="wpml-change-domain-language-dialog"
				 class="wpml-change-language-dialog"
				 title="<?php _e( 'Language of domains', 'wpml-string-translation' ); ?>"
				 style="display:none"
				 data-button-text="<?php _e( 'Apply', 'wpml-string-translation' ); ?>" 
				 data-cancel-text="<?php _e( 'Cancel', 'wpml-string-translation' ); ?>" >
				<label for="wpml-domain-select">
					<?php _e( 'Select for which domain to set the language: ', 'wpml-string-translation' ); ?>
				</label>
				<select id="wpml-domain-select">
					<option value="" selected="selected"><?php _e( '-- Please select --', 'wpml-string-translation' ); ?></option>
					<?php
						foreach( $domains as $domain ) {
							$results = $this->wpdb->get_results( $this->wpdb->prepare( "
								SELECT language, COUNT(language) AS count
								FROM {$this->wpdb->prefix}icl_strings s
								WHERE context = %s
									AND language IN (" . wpml_prepare_in( array_keys( $all_languages ) ) . ")
								GROUP BY language
								", $domain->context ), ARRAY_A );
							foreach( $results as &$result ) {
								$result[ 'display_name' ] = $all_languages[ $result[ 'language' ] ][ 'display_name' ];
							}
							$domain_lang = $this->language_of_domain->get_language( $domain->context );
							if ( $domain_lang ) {
								$domain_data = 'data-domain_lang="' . $domain_lang . '" ';
							} else {
								$domain_data = 'data-domain_lang="" ';
							}
							echo '<option value="' . $domain->context .
										'" data-langs="' . esc_attr( wp_json_encode( $results ) ) .
										'"' . $domain_data . '>' . $domain->context. '</option>';
						}
					?>				
				</select>
				<div class="js-summary wpml-cdl-summary" style="display:none" >
					<p class="wpml-cdl-info">
						<?php _e( 'This domain currently has the following strings:', 'wpml-string-translation'); ?>
					</p>
					<table class="widefat striped wpml-cdl-table">
						<thead>
							<tr>
								<td class="manage-column column-cb check-column"><input class="js-all-check" type="checkbox" value="all" /></td>
								<th><?php _e( 'Current source language', 'wpml-string-translation' ); ?></th>
								<th class="num"><?php _e( 'Number of strings', 'wpml-string-translation' ); ?></th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
					<div class="js-lang-select-area wpml-cdl-info">
						<label for="wpml-source-domain-language-change"><?php _e( 'Set the source language of these strings to:', 'wpml-string-translation'); ?></label>
						<?php
							$lang_selector = new WPML_Simple_Language_Selector( $this->sitepress );
							echo $lang_selector->render(array('id' => 'wpml-source-domain-language-change'));
						?>
						<label for="wpml-cdl-set-default">
							<input id="wpml-cdl-set-default" type="checkbox" class="js-default" value="use-as-default" checked="checked" />
							<?php _e( 'Use this language as the default language for new strings in this domain', 'wpml-string-translation'); ?>
						</label>
					</div>
				</div>
				<span class="spinner"></span>
				<?php wp_nonce_field( 'wpml_change_string_domain_language_nonce', 'wpml_change_string_domain_language_nonce' ); ?>
			</div>
		<?php
	}

	public function change_language_of_strings( $domain, $langs, $to_lang, $set_as_default ) {
		$package_translation = new WPML_Package_Helper();
		$package_translation->change_language_of_strings_in_domain( $domain, $langs, $to_lang );

		if ( ! empty( $langs ) ) {
			foreach ( $langs as &$lang ) {
				$lang = "'" . $lang . "'";
			}
			$langs = implode( ',', $langs );
			$string_ids = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE context=%s AND language IN ($langs)", $domain ) );
			foreach ( $string_ids as $str_id ) {
				$this->string_factory->find_by_id( $str_id )->set_language( $to_lang );
			}
		}
		if ( $set_as_default ) {
			$lang_of_domain = new WPML_Language_Of_Domain( $this->sitepress );
			$lang_of_domain->set_language( $domain, $to_lang );
		}

		$string_ids = $this->wpdb->get_col(
			$this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE context = %s", $domain )
		);
		foreach ( $string_ids as $strid ) {
			$this->string_factory->find_by_id( $strid )->update_status();
		}

		return array( 'success' => true );
	}
}