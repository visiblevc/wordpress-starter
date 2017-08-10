<?php

class WPML_Translations_Queue extends WPML_SP_User {

	/* @var WPML_UI_Screen_Options_Pagination */
	private $screen_options;

	/**
	 * WPML_Translations_Queue constructor.
	 *
	 * @param SitePress                      $sitepress
	 * @param WPML_UI_Screen_Options_Factory $screen_options_factory
	 */
	public function __construct( &$sitepress, $screen_options_factory ) {
		parent::__construct( $sitepress );
		$this->screen_options = $screen_options_factory->create_pagination( 'tm_translations_queue_per_page',
			ICL_TM_DOCS_PER_PAGE );
	}

	/**
	 * @param array     $icl_translation_filter
	 */
	public function display( $icl_translation_filter = array() ) {
		/**
		 * @var TranslationManagement $iclTranslationManagement
		 * @var WPML_Translation_Job_Factory $wpml_translation_job_factory
		 */
		global $iclTranslationManagement, $current_user, $wpml_translation_job_factory, $wpdb;
		if ( ( isset( $_GET['job_id'] ) && $_GET['job_id'] > 0 )
				  || ( isset( $_GET['trid'] ) && $_GET['trid'] > 0 ) 
		) {
			$job_id     = $this->get_job_id_from_request();
			$job_object = $wpml_translation_job_factory->get_translation_job( $job_id, false, 0, true );
			if ( $job_object && $job_object->user_can_translate( $current_user ) ) {
				$translation_editor_ui = new WPML_Translation_Editor_UI( $wpdb,
																		 $this->sitepress,
																		 $iclTranslationManagement,
																		 $job_object,
																		 new WPML_TM_Job_Action_Factory( $wpml_translation_job_factory ),
																		 new WPML_TM_Job_Layout( $wpdb, $this->sitepress->get_wp_api() ) );
				$translation_editor_ui->render();

				return;
			}
		}
		if ( ! empty( $_GET[ 'resigned' ] ) ) {
			$iclTranslationManagement->add_message( array(
														'type' => 'updated',
														'text' => __( "You've resigned from this job.",
																	  'wpml-translation-management' )
													) );
		}
		if ( isset( $_SESSION[ 'translation_ujobs_filter' ] ) ) {
			$icl_translation_filter = $_SESSION[ 'translation_ujobs_filter' ];
		}
		$current_translator = $iclTranslationManagement->get_current_translator();
		$can_translate      = $current_translator && $current_translator->ID > 0 && $current_translator->language_pairs;
		$post_link_factory  = new WPML_TM_Post_Link_Factory( $this->sitepress );
		if( $can_translate ) {
			$icl_translation_filter['translator_id']      = $current_translator->ID;
			$icl_translation_filter['include_unassigned'] = true;

			$element_type_prefix = isset( $_GET['element_type'] ) ? $_GET['element_type'] : 'post';
			if ( isset( $_GET['updated'] ) && $_GET['updated'] ) {
				$tm_post_link_updated = $post_link_factory->view_link( $_GET['updated'] );
				if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
					$tm_post_link_updated = apply_filters( 'wpml_external_item_link', $tm_post_link_updated, $_GET['updated'], false );
				}
				$user_message = __( 'Translation updated: ', 'wpml-translation-management' ) . $tm_post_link_updated;
				$iclTranslationManagement->add_message( array( 'type' => 'updated', 'text' => $user_message ) );
			} elseif ( isset( $_GET['added'] ) && $_GET['added'] ) {
				$tm_post_link_added = $post_link_factory->view_link( $_GET['added'] );
				if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
					$tm_post_link_added = apply_filters( 'wpml_external_item_link', $tm_post_link_added, $_GET['added'], false );
				}
				$user_message = __( 'Translation added: ', 'wpml-translation-management' ) . $tm_post_link_added;
				$iclTranslationManagement->add_message( array( 'type' => 'updated', 'text' => $user_message ) );
			} elseif ( isset( $_GET['job-cancelled'] ) ) {
				$user_message = __( 'Translation has been removed by admin', 'wpml-translation-management' );
				$iclTranslationManagement->add_message( array( 'type' => 'error', 'text' => $user_message ) );
			}

			$translation_jobs = array();

			if ( ! empty( $current_translator->language_pairs ) ) {
				$_langs_to = array();
				if ( 1 < count( $current_translator->language_pairs ) ) {
					foreach ( $current_translator->language_pairs as $lang => $to ) {
						$langs_from[] = $this->sitepress->get_language_details( $lang );
						$_langs_to    = array_merge( (array) $_langs_to, array_keys( $to ) );
					}
					$_langs_to = array_unique( $_langs_to );
				} else {
					$_langs_to                      = array_keys( current( $current_translator->language_pairs ) );
					$lang_from                      = $this->sitepress->get_language_details( key( $current_translator->language_pairs ) );
					$icl_translation_filter['from'] = $lang_from['code'];
				}

				if ( 1 < count( $_langs_to ) ) {
					foreach ( $_langs_to as $lang ) {
						$langs_to[] = $this->sitepress->get_language_details( $lang );
					}
				} else {
					$lang_to                      = $this->sitepress->get_language_details( current( $_langs_to ) );
					$icl_translation_filter['to'] = $lang_to['code'];
				}
				$job_types = $wpml_translation_job_factory->get_translation_job_types_filter( array(),
					array(
						'translator_id'      => $current_translator->ID,
						'include_unassigned' => true
					) );
				$translation_jobs = $wpml_translation_job_factory->get_translation_jobs( (array) $icl_translation_filter );
			}
		}
		?>
		<div class="wrap">
			<h2><?php echo __('Translations queue', 'wpml-translation-management') ?></h2>

			<?php if(empty($current_translator->language_pairs)): ?>
			<div class="error below-h2"><p><?php _e("No translation languages configured for this user.", 'wpml-translation-management'); ?></p></div>
			<?php endif; ?>
			<?php do_action('icl_tm_messages'); ?>
			
			<?php if(!empty($current_translator->language_pairs)): ?>

			<div class="alignright">
				<form method="post" name="translation-jobs-filter" id="tm-queue-filter" action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/translations-queue.php">
				<input type="hidden" name="icl_tm_action" value="ujobs_filter" />
				<table class="">
					<tbody>
						<tr valign="top">
							<td>
								<select name="filter[type]">
									<option value=""><?php _e('All types', 'wpml-translation-management')?></option>
									<?php foreach($job_types as $job_type => $job_type_name):?>
									<option value="<?php echo $job_type?>" <?php
									if(!empty($icl_translation_filter['type']) && $icl_translation_filter['type'] == $job_type):?>selected="selected"<?php endif ;?>><?php echo $job_type_name?></option>
									<?php endforeach; ?>
								</select>&nbsp;
								<label>
									<strong><?php _e('From', 'wpml-translation-management');?></strong>
										<?php if(1 < count($current_translator->language_pairs)) {

											$from_select = new WPML_Simple_Language_Selector( $this->sitepress );
											echo $from_select->render( array(
																			'name' => 'filter[from]',
																			'please_select_text' => __('Any language', 'wpml-translation-management'),
																			'style' => '',
																			'languages' => $langs_from,
																			'selected' => isset( $icl_translation_filter['from'] ) ? $icl_translation_filter['from'] : ''
																			)
																	  );

										} else { ?>
											<input type="hidden" name="filter[from]" value="<?php echo esc_attr($lang_from[ 'code' ]) ?>" />
											<?php echo $this->sitepress->get_flag_img( $lang_from[ 'code' ] ) . ' ' . $lang_from[ 'display_name' ]; ?>
										<?php } ?>
								</label>&nbsp;
								<label>
									<strong><?php _e('To', 'wpml-translation-management');?></strong>
										<?php if(1 < @count($langs_to)) {
											$to_select = new WPML_Simple_Language_Selector( $this->sitepress );
											echo $to_select->render( array(
																		'name' => 'filter[to]',
																		'please_select_text' => __('Any language', 'wpml-translation-management'),
																		'style' => '',
																		'languages' => $langs_to,
																		'selected' => isset( $icl_translation_filter['to'] ) ? $icl_translation_filter['to'] : ''
																		)
																  );
										} else { ?>
											<input type="hidden" name="filter[to]" value="<?php echo esc_attr($lang_to[ 'code' ]) ?>" />
											<?php echo $this->sitepress->get_flag_img( $lang_to[ 'code' ] ) . ' ' . $lang_to[ 'display_name' ]; ?>
										<?php } ?>
								</label>
								&nbsp;
								<select name="filter[status]">
									<option value=""><?php _e('All statuses', 'wpml-translation-management')?></option>
									<option value="<?php echo ICL_TM_COMPLETE ?>" <?php
										if(@intval($icl_translation_filter['status'])==ICL_TM_COMPLETE):?>selected="selected"<?php endif ;?>><?php
											echo TranslationManagement::status2text(ICL_TM_COMPLETE); ?></option>
									<option value="<?php echo ICL_TM_IN_PROGRESS ?>" <?php
										if(@intval($icl_translation_filter['status'])==ICL_TM_IN_PROGRESS):?>selected="selected"<?php endif ;?>><?php
											echo TranslationManagement::status2text(ICL_TM_IN_PROGRESS); ?></option>
									<option value="<?php echo ICL_TM_WAITING_FOR_TRANSLATOR ?>" <?php
										if(@intval($icl_translation_filter['status'])
											&& $icl_translation_filter['status']== ICL_TM_WAITING_FOR_TRANSLATOR):?>selected="selected"<?php endif ;?>><?php
											_e('Available to translate', 'wpml-translation-management') ?></option>
								</select>
								&nbsp;
								<input class="button-secondary" type="submit" value="<?php _e('Filter', 'wpml-translation-management')?>" />
							</td>
						</tr>
					</tbody>
				</table>
				</form>
			</div>
			<?php
			$actions = apply_filters( 'wpml_translation_queue_actions', array() );

			/**
			 * @deprecated Use 'wpml_translation_queue_actions' instead
			 */
			$actions = apply_filters( 'WPML_translation_queue_actions', $actions );
			?>
			<?php if ( sizeof( $actions ) > 0 ): ?>
			<form method="post" name="translation-jobs-action" action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/translations-queue.php">
			<?php endif; ?>

				<?php
				do_action( 'wpml_xliff_select_actions', $actions, 'action' );

				/**
				 * @deprecated Use 'wpml_xliff_select_actions' instead
				 */
				do_action( 'WPML_xliff_select_actions', $actions, 'action' );
				?>

				<?php

				$translation_queue_pagination = new WPML_Translations_Queue_Pagination_UI( $translation_jobs,
																						   $this->screen_options->get_items_per_page()
																						   );
				$translation_jobs = $translation_queue_pagination->get_paged_jobs( );

				?>
				<?php // pagination - end ?>

				<?php
				$blog_translators             = wpml_tm_load_blog_translators();
				$tm_api                       = new WPML_TM_API( $blog_translators, $iclTranslationManagement );

				$translation_queue_jobs_model = new WPML_Translations_Queue_Jobs_Model( $this->sitepress,
																						$iclTranslationManagement,
																						$tm_api,
																						$post_link_factory,
																						$translation_jobs
																						);
				$translation_jobs             = $translation_queue_jobs_model->get();

				$this->show_table( $translation_jobs, sizeof( $actions ) > 0 );
				?>

				<div id="tm-queue-pagination" class="tablenav">
					<?php $translation_queue_pagination->show() ?>

					<?php
					do_action( 'wpml_xliff_select_actions', $actions, 'action2' );

					/**
					 * @deprecated Use 'wpml_xliff_select_actions' instead
					 */
					do_action( 'WPML_xliff_select_actions', $actions, 'action2' );
					?>
				</div>
				<?php // pagination - end ?>

			<?php if(sizeof($actions)>0): ?>
			</form>
			<?php endif; ?>

			<?php do_action( 'wpml_translation_queue_after_display' ); ?>

		<?php endif; ?>
		</div>

		<?php
		// Check for any bulk actions
		if ( isset( $_POST[ 'action' ] ) || isset( $_POST[ "action2" ] ) ) {
			$xliff_version = isset( $_POST[ 'doaction' ] ) ? $_POST[ 'action' ] : $_POST[ 'action2' ];
			do_action( 'wpml_translation_queue_do_actions_export_xliff', $_POST, $xliff_version );

			/**
			 * @deprecated Use 'wpml_translation_queue_do_actions_export_xliff' instead
			 */
			do_action( 'WPML_translation_queue_do_actions_export_xliff', $_POST, $xliff_version );
		}
	}

	public function show_table( $translation_jobs, $has_actions ) {
		?>
			<table class="widefat fixed" id="icl-translation-jobs" cellspacing="0">
				<?php foreach( array( 'thead', 'tfoot' ) as $element_type ) { ?>
					<<?php echo $element_type; ?>>
						<tr>
							<?php if ( $has_actions ) { ?>
								<td class="manage-column column-cb check-column js-check-all" scope="col">
									<input title="<?php echo $translation_jobs[ 'strings' ][ 'check_all' ]; ?>" type="checkbox" />
								</td>
							<?php } ?>

							<th scope="col" width="60"><?php echo $translation_jobs[ 'strings' ][ 'job_id' ]; ?></th>
							<th scope="col"><?php echo $translation_jobs[ 'strings' ][ 'title' ]; ?></th>
							<th scope="col"><?php echo $translation_jobs[ 'strings' ][ 'type' ]; ?></th>
							<th scope="col" class="column-language"><?php echo $translation_jobs[ 'strings' ][ 'language' ]; ?></th>
							<th scope="col" class="column-status"><?php echo $translation_jobs[ 'strings' ][ 'status' ]; ?></th>
							<th scope="col" class="manage-column">&nbsp;</th>
							<th scope="col" class="manage-column column-date column-resign">&nbsp;</th>
						</tr>
					</<?php echo $element_type; ?>>
				<?php } ?>

				<tbody>
				<?php if ( empty( $translation_jobs[ 'jobs' ] ) ) { ?>
					<tr>
						<td colspan="7" align="center"><?php _e( 'No translation jobs found', 'wpml-translation-management' ) ?></td>
					</tr>
				<?php } else { foreach ( $translation_jobs[ 'jobs' ] as $index => $job ) { ?>
					<tr <?php if( $index % 2) { echo 'class="alternate"'; } ?>>
						<?php if ( $has_actions ) { ?>
							<td>
								<label><input type="checkbox" name="job[<?php echo $job->job_id ?>]" value="1" />&nbsp;</label>
							</td>
						<?php } ?>

						<td width="60"><?php echo $job->job_id; ?></td>
						<td><?php echo esc_html( $job->post_title ); ?>
							<div class="row-actions">
								<span class="view"><?php echo $job->tm_post_link; ?></span>
							</div>
						</td>
						<td><?php echo esc_html( $job->post_type ); ?></td>
						<td><?php echo $job->lang_text_with_flags ?></td>
						<td><i class="<?php echo $job->icon; ?>"></i> <?php echo $job->status_text; ?></td>
						<td>
							<?php if ( $job->original_doc_id ) { ?>
								<a class="button-secondary translation-queue-edit" href="<?php echo esc_attr( $job->edit_url ); ?>">
									<?php echo $job->button_text; ?>
								</a>
							<?php } ?>
						</td>
						<td align="right">
							<?php if ( $job->is_doing_job ) { ?>
								<a href="<?php echo esc_attr( $job->resign_url ); ?>" onclick="if(!confirm('<?php echo esc_js( $translation_jobs[ 'strings' ][ 'confirm' ] ) ?>')) {return false;}"><?php echo $job->resign_text ?></a>
							<?php } else { ?>
								&nbsp;
							<?php } ?>
						</td>
					</tr>
				<?php } } ?>
			</tbody>
		</table>
	<?php
	}
	
	private function get_job_id_from_request() {
		/**
		 * @var TranslationManagement $iclTranslationManagement
		 * @var WPML_Post_Translation $wpml_post_translations
		 * @var WPML_Translation_Job_Factory $wpml_translation_job_factory
		 */
		global $iclTranslationManagement, $wpml_post_translations, $wpml_translation_job_factory, $sitepress;


		$job_id               = filter_var( isset( $_GET[ 'job_id' ] ) ? $_GET[ 'job_id' ] : '', FILTER_SANITIZE_NUMBER_INT );
		$trid                 = filter_var( isset( $_GET[ 'trid' ] ) ? $_GET[ 'trid' ] : '', FILTER_SANITIZE_NUMBER_INT );
		$language_code        = filter_var( isset( $_GET[ 'language_code' ] ) ? $_GET[ 'language_code' ] : '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$source_language_code = filter_var( isset( $_GET[ 'source_language_code' ] ) ? $_GET[ 'source_language_code' ] : '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$update_needed        = filter_var( isset( $_GET[ 'update_needed' ] ) ? $_GET[ 'update_needed' ] : '', FILTER_SANITIZE_NUMBER_INT );

		if ( $trid && $language_code ) {
			if ( ! $job_id ) {
				$job_id = $iclTranslationManagement->get_translation_job_id( $trid,
					$language_code );
				if ( ! $job_id ) {
					if ( ! $source_language_code ) {
						$post_id = SitePress::get_original_element_id_by_trid( $trid );
					} else {
						$posts_in_trid = $wpml_post_translations->get_element_translations( false,
							$trid );
						$post_id       = isset( $posts_in_trid[ $source_language_code ] ) ? $posts_in_trid[ $source_language_code ] : false;
					}
					$blog_translators = wpml_tm_load_blog_translators();
					$args             = array(
						'lang_from' => $source_language_code,
						'lang_to'   => $language_code,
						'job_id'    => $job_id
					);
					if ( $post_id && $blog_translators->is_translator( $sitepress->get_current_user()->ID,
							$args )
					) {
						$job_id = $wpml_translation_job_factory->create_local_post_job( $post_id,
							$language_code );
					}
				}
			} else if ( $update_needed ) {
				$post_id = SitePress::get_original_element_id_by_trid( $trid );
				$job_id = $wpml_translation_job_factory->create_local_post_job( $post_id, $language_code );
			}
		}


		return $job_id;
	}

	
}
