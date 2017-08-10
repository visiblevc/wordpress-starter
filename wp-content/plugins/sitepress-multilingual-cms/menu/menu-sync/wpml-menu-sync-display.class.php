<?php

class WPML_Menu_Sync_Display {
	private $menu_id;
	/** @var ICLMenusSync $icl_ms*/
	private $icl_ms;
	private $labels;

	public function __construct( $menu_id, $icl_ms ) {
		$this->menu_id = $menu_id;
		$this->icl_ms = $icl_ms;

		$this->labels = array(
			'del' => array( esc_html__( 'Remove %s', 'sitepress' ), '' ),
			'label_changed' => array( esc_html__( 'Rename label to %s', 'sitepress' ), '' ),
			'url_changed' => array( esc_html__( 'Update URL to %s', 'sitepress' ), '' ),
			'url_missing' => array( esc_html__( 'Untranslated URL %s', 'sitepress' ), '' ),
			'mov' => array( esc_html__( 'Change menu order for %s', 'sitepress' ), '' ),
			'add' => array( esc_html__( 'Add %s', 'sitepress' ), '' ),
			'options_changed' => array( esc_html__( 'Update %s menu option to %s', 'sitepress' ), '' ),
		);

		if ( defined( 'WPML_ST_FOLDER' ) ) {
			$this->labels['label_missing'] = array(
				esc_html__( 'Untranslated string %s', 'sitepress' ),
				$this->print_label_missing_text( $icl_ms, $menu_id ),
			);
		}

	}

	private function print_label_missing_text( $icl_menus_sync, $menu_id ) {
		$context_menu_name = $icl_menus_sync->menus[ $menu_id ]['name'] . ' menu';
		$res = '&nbsp;' . sprintf(
			esc_html__(
				'The selected strings can now be translated using the %s string translation %s screen',
				'wpml-string-translation'
			),
			'<a href="admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . $context_menu_name . '"',
			'</a>'
		);

		return $res;
	}

	public function print_sync_field( $index ) {
		global $sitepress;

		$icl_menus_sync = $this->icl_ms;
		$menu_id = $this->menu_id;
		// items translations / del
		if ( isset( $icl_menus_sync->sync_data[ $index ][ $menu_id ] ) ) {
			foreach ( $icl_menus_sync->sync_data[ $index ][ $menu_id ] as $item_id => $languages ) {
				foreach ( $languages as $lang_code => $name ) {
					$additional_data = $this->get_additional_data( $index, $name );
					$item_name       = $this->get_item_name( $index, $name );
					$lang_details    = $sitepress->get_language_details( $lang_code );
					$input_name = esc_attr( sprintf( 'sync[%s][%s][%s][%s]%s', $index, $menu_id, $lang_code, $item_id, $additional_data ) );
					?>
					<tr>
						<th scope="row" class="check-column">
							<input type="checkbox"
								   name="<?php echo $input_name ?>"
								   value="<?php echo esc_attr( $item_name ) ?>"/>
						</th>
						<td><?php echo esc_html( $lang_details['display_name'] ) ?></td>
						<td><?php echo $this->get_action_label( $index, $item_name, $item_id ) ?> </td>
					</tr>
				<?php
				}
			}
		}
	}

	private function get_action_label( $index, $item_name, $item_id ) {
		$labels = $this->labels;
		if ( 'options_changed' !== $index ) {
			$argument = sprintf( $labels[ $index ][0], '<strong>' . $item_name . '</strong>' );
		} else {
			$argument = sprintf(
				$labels[ $index ][0],
				'<strong>' . $item_id . '</strong>',
				'<strong>' . ( $item_name ? $item_name : '0') . '</strong>'
			);
		}

		return $this->hierarchical_prefix( $index, $item_id ) . $argument . $labels[ $index ][1];
	}

	private function get_additional_data( $index, $name ) {
		return 'mov' === $index ? '[' . key( $name ) . ']' : '';
	}

	private function get_item_name( $index, $name ) {
		return 'mov' === $index ? current( $name ) : $name;
	}

	private function hierarchical_prefix( $index, $item_id ) {
		$prefix = '';
		if ( in_array( $index, array( 'mov', 'add' ), true ) ) {
			$prefix = str_repeat( ' - ', $this->icl_ms->get_item_depth( $this->menu_id, $item_id ) );
		}

		return $prefix;
	}
}
