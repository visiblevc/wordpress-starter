<?php

class WPML_Taxonomy_Element_Language_Dropdown {

	function add_language_selector_to_page( $active_languages, $selected_language, $translations, $element_id, $type ) {
		?>
		<div id="icl_tax_menu" style="display:none">

		<div id="dashboard-widgets" class="metabox-holder">
		<div class="postbox-container" style="width: 99%;line-height:normal;">

	<div id="icl_<?php echo $type ?>_lang" class="postbox" style="line-height:normal;">
		<h3 class="hndle">
			<span><?php echo __ ( 'Language', 'sitepress' ) ?></span>
		</h3>
		<div class="inside" style="padding: 10px;">



		<?php
		$active_languages = $this->filter_allowed_languages ( $active_languages, $selected_language );
		$disabled         = count ( $active_languages ) === 1 ? ' disabled="disabled" ' : '';
		?>

		<select name="icl_<?php echo $type ?>_language" <?php echo $disabled ?>>

			<?php
			echo $this->add_options ( $active_languages, $selected_language );
			?>

			<?php foreach ( $active_languages as $lang ): ?>
				<?php if ( $lang[ 'code' ] === $selected_language || ( isset( $translations[ $lang[ 'code' ] ]->element_id ) && $translations[ $lang[ 'code' ] ]->element_id != $element_id ) ) {
					continue;
				} ?>
				<option
					value="<?php echo $lang[ 'code' ] ?>"<?php if ( $selected_language === $lang[ 'code' ] ): ?> selected="selected"<?php endif; ?>><?php echo $lang[ 'display_name' ] ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	private function filter_allowed_languages( $active_languages, $selected_language ) {
		global $sitepress;

		$wp_api = new WPML_WP_API();
		$show_all = $wp_api->is_term_edit_page() || $sitepress->get_current_language() === 'all';
		return $show_all ? $active_languages :  array( $active_languages[ $selected_language ] );
	}

	private function add_options( $active_languages, $selected_language ) {
		$html = '';

		foreach ( $active_languages as $lang ) {
			if ( $lang[ 'code' ] === $selected_language ) {
				$html .= '<option value="' . $selected_language . '" selected="selected">' . $lang[ 'display_name' ] . '</option>';
			}
		}

		return $html;
	}
}