<script type="text/html" id="table-listing-filter">
	<?php
	global $iclTranslationManagement, $sitepress, $wpdb;

	$translation_services = array( 'local', TranslationProxy::get_current_service_id() );

	$args = array(
		'name'         => 'filter[translator_id]',
		'default_name' => __( 'All', 'wpml-translation-management' ),
		'selected'     => isset( $icl_translation_filter[ 'translator_id' ] ) ? $icl_translation_filter[ 'translator_id' ] : '',
		'services'     => $translation_services,
		'add_label'    => true,
	);
	$blog_translators     = wpml_tm_load_blog_translators();
	$translators_dropdown = new WPML_TM_Translators_Dropdown( $blog_translators );
	$translators_dropdown->render( $args );
	?>
	&nbsp;	<label for="filter-job-status">
		<?php _e( 'Status', 'wpml-translation-management' ) ?></label>&nbsp;		<select id="filter-job-status" name="filter[status]">
		<option value=""><?php _e( 'All translation jobs', 'wpml-translation-management' ) ?></option>
		<option value="<?php echo ICL_TM_WAITING_FOR_TRANSLATOR ?>"><?php echo TranslationManagement::status2text( ICL_TM_WAITING_FOR_TRANSLATOR ); ?></option>
		<option value="<?php echo ICL_TM_IN_PROGRESS ?>"><?php echo TranslationManagement::status2text( ICL_TM_IN_PROGRESS ); ?></option>
		<option value="<?php echo ICL_TM_COMPLETE ?>"><?php echo TranslationManagement::status2text( ICL_TM_COMPLETE ); ?></option>
		<option value="<?php echo ICL_TM_DUPLICATE ?>"><?php _e( 'Content duplication', 'wpml-translation-management' ) ?></option>
	</select>	&nbsp;	<label for="filter-job-lang-from">
		<?php _e( 'From', 'wpml-translation-management' ); ?></label>		<select id="filter-job-lang-from" name="filter[from]">
		<option value=""><?php _e( 'Any language', 'wpml-translation-management' ) ?></option>
		<?php foreach ( $sitepress->get_active_languages() as $lang ): ?>
			<option value="<?php echo $lang[ 'code' ] ?>"><?php echo $lang[ 'display_name' ] ?></option>
		<?php endforeach; ?>
	</select>	&nbsp;	<label for="filter-job-lang-to">
		<?php _e( 'To', 'wpml-translation-management' ); ?></label>		<select id="filter-job-lang-to" name="filter[to]">
		<option value=""><?php _e( 'Any language', 'wpml-translation-management' ) ?></option>
		<?php foreach ( $sitepress->get_active_languages() as $lang ): ?>
			<option value="<?php echo $lang[ 'code' ] ?>"><?php echo $lang[ 'display_name' ] ?></option>
		<?php endforeach; ?>
	</select>
</script>
