<?php
require_once( ABSPATH . WPINC . '/class-wp-editor.php' );

class WPML_Translation_Editor extends WPML_WPDB_And_SP_User {

	/**
	 * @var WPML_Element_Translation_Job $job
	 */
	private $job;

	/**
	 * @param SitePress $sitepress
	 * @param wpdb $wpdb
	 * @param WPML_Element_Translation_Job $job
	 */
	public function __construct(
		&$sitepress,
		&$wpdb,
		$job
	) {
		parent::__construct( $wpdb, $sitepress );
		$this->job = $job;

		add_filter( 'tiny_mce_before_init', array( $this, 'filter_original_editor_buttons' ), 10, 2 );
		$this->enqueue_js();
	}

	/**
	 * Enqueues the JavaScript used by the TM editor.
	 */
	public function enqueue_js() {
		wp_enqueue_script( 'wpml-tm-editor-scripts' );
		wp_localize_script( 'wpml-tm-editor-scripts', 'tmEditorStrings', $this->get_translation_editor_strings() );
	}

	/**
	 * @return string[]
	 */
	private function get_translation_editor_strings() {

		return array(
			'dontShowAgain'        => __( "Don't show this again.",
				'wpml-translation-management' ),
			'learnMore'            => __( '<p>The administrator has disabled term translation from the translation editor. </p>
<p>If your access permissions allow you can change this under "Translation Management" - "Multilingual Content Setup" - "Block translating taxonomy terms that already got translated". </p>
<p>Please note that editing terms from the translation editor will affect all posts that have the respective terms associated.</p>',
				'wpml-translation-management' ),
			'warning'              => __( "Please be advised that editing this term's translation here will change the value of the term in general. The changes made here, will not only affect this post!",
				'wpml-translation-management' ),
			'title'                => __( "Terms translation is disabled",
				'wpml-translation-management' ),
			'confirm'              => __( 'You have unsaved work. Are you sure you want to close without saving?',
				'wpml-translation-management' ),
			'cancel'               => __( 'Cancel',
				'wpml-translation-management' ),
			'save'                 => __( 'Save',
				'wpml-translation-management' ),
			'save_and_close'       => __( 'Save & Close',
				'wpml-translation-management' ),
			'loading_url'          => ICL_PLUGIN_URL . '/res/img/ajax-loader.gif',
			'saving'               => __( 'Saving...',
				'wpml-translation-management' ),
			'translation_complete' => __( 'Translation is complete',
				'wpml-translation-management' ),
			'contentNonce'         => wp_create_nonce( 'wpml_save_job_nonce',
				'wpml_save_job_nonce' ),
			'source_lang'          => __( 'Original',
				'wpml-translation-management' ),
			'target_lang'          => __( 'Translation to',
				'wpml-translation-management' ),
			'copy_all'             => __( 'Copy all fields from original',
				'wpml-translation-management' ),
			'resign'               => __( 'Resign',
				'wpml-translation-management' ),
			'resign_translation'   => __( 'Are you sure you want to resign from this job?',
				'wpml-translation-management' ),
			'resign_url'           => admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&icl_tm_action=save_translation&resign=1&job_id=' . $this->job->get_id() ),
			'confirmNavigate'      => __( 'You have unsaved changes!',
				'wpml-translation-management' ),
			'copy_from_original'   => __( 'Copy from original',
				'wpml-translation-management' ),
		);
	}

	public function filter_original_editor_buttons( $config, $editor_id ) {
		if ( strpos( $editor_id, '_original' ) > 0 ) {
			$config['toolbar1'] = " ";
			$config['toolbar2'] = " ";
			$config['readonly'] = "1";
		}

		return $config;
	}

	public function output_editors( $field ) {
		echo '<div id="' . $field['field_type'] . '_original_editor" class="original_value mce_editor_origin">';
		wp_editor( $field['field_data'], $field['field_type'] . '_original', array(
			'textarea_rows' => 4,
			'editor_class'  => 'wpml_content_tr original_value mce_editor_origin',
			'media_buttons' => false,
			'quicktags'     => array( 'buttons' => 'empty' )
		) );
		echo '</div>';
		echo '<div id="' . $field['field_type'] . '_translated_editor" class="mce_editor translated_value">';
		wp_editor( $field['field_data_translated'], $field['field_type'], array(
			'textarea_rows' => 4,
			'editor_class'  => 'wpml_content_tr translated_value',
			'media_buttons' => true,
			'textarea_name' => 'fields[' . $field['field_type'] . '][data]'
		) );
		echo '</div>';
	}
}

