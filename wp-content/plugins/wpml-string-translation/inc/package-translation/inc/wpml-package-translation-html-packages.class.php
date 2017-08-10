<?php
class WPML_Package_Translation_HTML_Packages {
	var $load_priority = 101;

	public function __construct() {
		add_action( 'WPML_PT_HTML', array( $this, 'loaded' ), $this->load_priority );
	}

	public function loaded() {
		$this->set_admin_hooks();
	}

	private function set_admin_hooks() {
		if(is_admin()) {
			add_action( 'wpml_pt_package_table_columns', array( $this, 'render_package_table_columns' ), 10, 1 );
			add_action( 'wpml_pt_package_table_options', array( $this, 'package_translation_menu_options' ), 10, 1 );
			add_action( 'wpml_pt_package_table_body', array( $this, 'package_translation_menu_body' ), 10, 1 );
			add_action( 'wpml_pt_package_status', array( $this, 'render_string_package_status' ), 10, 3 );
		}
	}

	public static function package_translation_menu() {
		$packages      = apply_filters( 'wpml_pt_all_packages', array() );
		$package_kinds = array();

		if ( $packages ) {
			foreach ( $packages as $package ) {
				$kind_slugs = array_keys( $package_kinds );
				if ( ! in_array( $package->kind_slug, $kind_slugs ) ) {
					$package_kinds[ $package->kind_slug ] = $package->kind;
				}
			}
		}

		$table_title                 = __( 'Package Management', 'wpml-string-translation' );
		$package_kind_label          = __( 'Display packages for this kind:', 'wpml-string-translation' );
		$package_kind_options[ - 1 ] = __( 'Display packages for this kind:', 'wpml-string-translation' );
		foreach ( $package_kinds as $kind_slug => $kind ) {
			$package_kind_options[ $kind_slug ] = $kind;
		}
		?>
		<h2><?php echo $table_title; ?></h2>

		<p>
			<label for="package_kind_filter"><?php echo $package_kind_label; ?></label>
			<select id="package_kind_filter">
				<?php do_action( 'wpml_pt_package_table_options', $package_kind_options ); ?>
			</select>
		</p>

		<table id="icl_package_translations" class="widefat" cellspacing="0">
			<thead>
			<?php do_action( 'wpml_pt_package_table_columns', 'thead' ); ?>
			</thead>
			<tfoot>
			<?php do_action( 'wpml_pt_package_table_columns', 'tfoot' ); ?>
			</tfoot>
			<tbody>
			<?php do_action( 'wpml_pt_package_table_body', $packages ); ?>
			</tbody>
		</table>

		<br/>

		<input id="delete_packages" type="button" class="button-primary" value="<?php echo __( 'Delete Selected Packages', 'wpml-string-translation' ) ?>" disabled="disabled"/>
		&nbsp;
		<span class="spinner"></span>
		<span style="display:none" class="js-delete-confirm-message"><?php echo __( "Are you sure you want to delete these packages?\nTheir strings and translations will be deleted too.", 'wpml-string-translation' ) ?></span>

		<?php
		wp_nonce_field( 'wpml_package_nonce', 'wpml_package_nonce' );
	}

	public function package_translation_menu_options( $package_kind_options ) {
		foreach ( $package_kind_options as $option_value => $option_label ) {
			?>
			<option value="<?php echo esc_attr($option_value); ?>"><?php echo $option_label; ?></option>
		<?php
		}
	}

	/**
	 * @param $packages
	 */
	public function package_translation_menu_body( $packages ) {
		if ( ! $packages ) {
			$this->package_translation_menu_no_packages();
		} else {
			$this->package_translation_menu_items( $packages );
		}
	}

	public function render_package_table_columns( $position ) {
		?>
		<tr>
			<th scope="col" class="manage-column column-cb check-column">
				<label for="select_all_package_<?php echo $position; ?>" style="display: none;">
					<?php _e( 'Select All', 'wpml-string-translation' ) ?>
				</label>
				<input id="select_all_package_<?php echo $position; ?>" class="js_package_all_cb" type="checkbox"/>
			</th>
			<th scope="col"><?php echo __( 'Kind', 'wpml-string-translation' ) ?></th>
			<th scope="col"><?php echo __( 'Name', 'wpml-string-translation' ) ?></th>
			<th scope="col"><?php echo __( 'Info', 'wpml-string-translation' ) ?></th>
		</tr>
	<?php
	}

	public function render_string_package_status( $string_count, $translation_in_progress, $default_package_language ) {
		$package_statuses[ ] = sprintf( __( 'Contains %s strings', 'wpml-string-translation' ), $string_count );
		if ( $translation_in_progress ) {
			$package_statuses[ ] = '(' . __( 'Translation is in progress', 'wpml-string-translation' ) . ')';
		}
		$package_statuses[ ] = __( 'Default package language', 'wpml-string-translation' ) . ': ' . $default_package_language;

		echo implode( ' - ', $package_statuses );
	}

	private function package_translation_menu_no_packages() {
		?>
		<tr>
			<td colspan="6" align="center">
				<strong><?php echo __( 'No packages found', 'wpml-string-translation' ) ?></strong>
			</td>
		</tr>
	<?php
	}

	private function package_translation_menu_items( $packages ) {
		global $wpdb, $sitepress;

		/** @var WPML_Package $package */
		foreach ( $packages as $package ) {

			$package_language_code = $package->get_package_language();
			$package_language      = $sitepress->get_display_language_name( $package_language_code );

			$tm = new WPML_Package_TM( $package );

			$translation_in_progress = $tm->is_translation_in_progress();
			$package_id              = $package->ID;
			$string_count            = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}icl_strings WHERE string_package_id=%d", $package_id ) );

			$disabled = disabled( $translation_in_progress, true, false );
			?>
			<tr id="row_<?php echo $package_id ?>" class="js_package js_package_<?php echo esc_attr($package->kind_slug);?>">
				<td>
					<input id="package_<?php echo $package_id ?>" class="js_package_row_cb" type="checkbox" value="<?php echo $package_id ?>" <?php echo $disabled; ?>/>
				</td>
				<td class="js-package-kind">
					<?php echo $package->kind; ?>
				</td>
				<td>
					<label for="package_<?php echo $package_id ?>"><?php echo esc_html($package->title); ?></label>
				</td>

				<td>
					<?php
					do_action( 'wpml_pt_package_status', $string_count, $translation_in_progress, $package_language );
					?>
				</td>
			</tr>
		<?php
		}
	}
}