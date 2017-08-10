<?php

class SitePress_EditLanguages {
	public  $active_languages;
	public  $upload_dir;
	public  $is_writable        = false;
	public  $required_fields    = array( 'code' => '', 'english_name' => '', 'translations' => 'array', 'flag' => '', 'default_locale' => '', 'tag' => '' );
	private $mode               = 'edit';
	private $validation_action  = null;
	public  $validation_failed  = false;
    private $built_in_languages = array();
	private $error              = '';
	private $message            = '';
	private $max_file_size;

	private $max_locale_length = 35;

	private $allowed_flag_mime_types;

	function __construct() {
		$this->allowed_flag_mime_types = array(
			'gif'  => 'image/gif',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'svg'  => 'image/svg+xml'
		);

		wp_enqueue_script(
            'edit-languages',
            ICL_PLUGIN_URL . '/res/js/languages/edit-languages.js',
            array( 'jquery', 'sitepress-scripts' ),
            ICL_SITEPRESS_VERSION,
            true
        );

		$this->max_file_size = 100000;

		$lang_codes = icl_get_languages_codes();
        $this->built_in_languages = array_values($lang_codes);

		if ( $this->is_delete_language_action() ) {
            $lang_id = (int)$_GET['id'];
            $this->delete_language($lang_id);
        }

		// Set upload dir
		$wp_upload_dir = wp_upload_dir();
		$this->upload_dir = $wp_upload_dir['basedir'] . '/flags';

		if ( ! is_dir( $this->upload_dir ) ) {
			$this->is_writable = is_writable( $wp_upload_dir['basedir'] );
			if ( $this->is_writable ) {
				try {
					mkdir( $this->upload_dir );
				} catch ( Exception $ex ) {
					$this->set_errors( __( 'Upload directory cannot be created. Check your permissions.', 'sitepress' ) );
				}
			} else {
				$this->set_errors( __( 'Upload dir is not writable', 'sitepress' ) );
			}
		}
		$this->is_writable = is_writable( $this->upload_dir );

		$this->migrate();
		
		$this->get_active_languages();
		
			// Trigger save.
		if (isset($_POST['icl_edit_languages_action']) && $_POST['icl_edit_languages_action'] === 'update') {
            if(wp_verify_nonce($_POST['_wpnonce'], 'icl_edit_languages')){
                $this->update();
            }
		}
	}

	function render() {
?>
<div class="wrap">
    <h2><?php _e('Edit Languages', 'sitepress') ?></h2>
	<div id="icl_edit_languages_info">
<?php
	_e('This table allows you to edit languages for your site. Each row represents a language.
<br /><br />
For each language, you need to enter the following information:
<ul>
    <li><strong>Code:</strong> a unique value that identifies the language. Once entered, the language code cannot be changed.</li>
    <li><strong>Translations:</strong> the way the language name will be displayed in different languages.</li>
    <li><strong>Flag:</strong> the flag to display next to the language (optional). You can either upload your own flag or use one of WPML\'s built in flag images.</li>
    <li><strong>Default locale:</strong> This determines the locale value for this language. You should check the name of WordPress localization file to set this correctly.</li>
</ul>', 'sitepress'); ?>

	</div>
<?php
	if ($this->error) {
		echo '	<div class="below-h2 error"><p>'.$this->error.'</p></div>'; 
	}
    
    if ($this->message) {
        echo '    <div class="below-h2 updated"><p>'.$this->message.'</p></div>'; 
    }
    
?>
	<br />
	<?php $this->edit_table(); ?>
</div>
<?php
	}

	function edit_table() {
?>
	<form enctype="multipart/form-data" action="<?php echo admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/languages.php&amp;trop=1') ?>" method="post" id="icl_edit_languages_form">
	<input type="hidden" name="icl_edit_languages_action" value="update" />
		<input type="hidden" name="icl_edit_languages_ignore_add" id="icl_edit_languages_ignore_add" value="<?php echo ( $this->is_new_data_and_invalid() ) ? 'false' : 'true'; ?>"/>
    <?php wp_nonce_field('icl_edit_languages'); ?>
	<table id="icl_edit_languages_table" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th><?php _e('Language name', 'sitepress'); ?></th>
					<th><?php _e('Code', 'sitepress'); ?></th>
	                <th <?php if ( $this->must_display_new_language_translation_column() ) {
		                echo 'style="display:none;" ';
	                } ?>class="icl_edit_languages_show"><?php _e( 'Translation (new)', 'sitepress' ); ?></th>
					<?php foreach ($this->active_languages as $lang) { ?>
					<th><?php _e('Translation', 'sitepress'); ?> (<?php echo $lang['english_name']; ?>)</th>
					<?php } ?>
					<th><?php _e('Flag', 'sitepress'); ?></th>
					<th><?php _e('Default locale', 'sitepress'); ?></th>
                    <th><?php _e('Encode URLs', 'sitepress'); ?></th>
                    <th><?php _e('Language tag', 'sitepress'); ?></th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th><?php _e('Language name', 'sitepress'); ?></th>
					<th><?php _e('Code', 'sitepress'); ?></th>
	                <th <?php if ( $this->must_display_new_language_translation_column() ) {
		                echo 'style="display:none;" ';
	                } ?>class="icl_edit_languages_show"><?php _e( 'Translation (new)', 'sitepress' ); ?></th>
					<?php foreach ($this->active_languages as $lang) { ?>
					<th><?php _e('Translation', 'sitepress'); ?> (<?php echo $lang['english_name']; ?>)</th>
					<?php } ?>
					<th><?php _e('Flag', 'sitepress'); ?></th>
                    <th><?php _e('Default locale', 'sitepress'); ?></th>
					<th><?php _e('Encode URLs', 'sitepress'); ?></th>
                    <th><?php _e('Language tag', 'sitepress'); ?></th>
                    <th>&nbsp;</th>
                </tr>
            </tfoot>        
            <tbody>
<?php
		foreach ($this->active_languages as $lang) {
			$this->table_row($lang);
		}
		if ( $this->is_new_data_and_invalid()) {
			$_POST['icl_edit_languages']['add']['id'] = 'add';
			$new_lang = $_POST['icl_edit_languages']['add'];
		} else {
			$new_lang = array('id'=>'add');
		}
		$this->table_row($new_lang,true,true);
?>
			</tbody>
	</table>
	<span class="icl_error_text icl_edit_languages_show" style="display: none; margin:10px;"><p><?php _e('Please note: language codes cannot be changed after adding languages. Make sure you enter the correct code.', 'sitepress'); ?></p></span>
	<p class="submit"><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php">&laquo;&nbsp;<?php _e('Back to languages', 'sitepress'); ?></a></p>

	<p class="submit alignright">
		<input type="button" name="icl_edit_languages_add_language_button" id="icl_edit_languages_add_language_button"
		       value="<?php _e( 'Add Language', 'sitepress' ); ?>" class="button-secondary"<?php if ( $this->is_new_data_and_invalid() ) { ?> style="display:none;"<?php } ?> />
		&nbsp;
		<input type="button" name="icl_edit_languages_cancel_button" id="icl_edit_languages_cancel_button"
		       value="<?php _e( 'Cancel', 'sitepress' ); ?>" class="button-secondary icl_edit_languages_show"<?php if ( ! $this->validation_failed ) { ?> style="display:none;"<?php } ?> />
		&nbsp;
		<input disabled="disabled" type="submit" class="button-primary" value="<?php _e( 'Save', 'sitepress' ); ?>"/>
	</p>
    <br />
	</form>

    <p>
        <?php wp_nonce_field('reset_languages_nonce', '_icl_nonce_rl'); ?>
        <input class="button-primary" type="button" id="icl_reset_languages" value="<?php _e('Reset languages', 'sitepress'); ?>" />
        <span class="hidden"><?php _e('WPML will reset all language information to its default values. Any languages that you added or edited will be lost.','sitepress')?></span>
    </p>

<?php
	}

	function table_row( $lang, $echo = true, $add = false ){
		if ('add' === $lang['id']) {
			$keys = array( 'english_name', 'code', 'default_locale', 'tag' );
			foreach ( $keys as $key ) {
				if (isset( $_POST['icl_edit_languages']['add'][ $key ] )) {
					$lang[ $key ] = filter_var( $_POST['icl_edit_languages']['add'][ $key ], FILTER_SANITIZE_STRING );
				} else {
					$lang[ $key ] = '';
				}
			}

			$lang['flag'] = '';
			$lang['from_template'] = true;

		}
        global $sitepress;


		$styles = array();
		$classes = array();

		if ( $add ) {
			if ( ($this->is_new_data_and_valid() ) || 'add' !== $this->validation_action  ) {
				$styles[] = 'display:none';
			}

			$styles[] = 'background-color:yellow';
		}

		if ( $add ) {
			$classes[] = 'icl_edit_languages_show';
		}

		$style = 'style="' . implode(';', $styles) . '"';
		$class = 'class="' . implode(' ', $classes) . '"';
        ?>

		<tr <?php echo $style; ?> <?php echo $class; ?>>
			<td>
				<?php
				if ( $add ) {
					?>
					<input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][english_name]" value="<?php echo $lang['english_name']; ?>"/>
					<?php
				} else {
					?>
					<div class="read-only" id="icl_edit_languages[<?php echo $lang['id']; ?>][english_name]"><?php echo $lang['english_name']; ?> <input type="hidden"
					                                                                                                                                     name="icl_edit_languages[<?php echo $lang['id']; ?>][english_name]"
					                                                                                                                                     value="<?php echo $lang['english_name']; ?>"/>
					</div>
					<?php
				}
				?>
			</td>
			<td>
				<?php
				if ( $add ) {
					?>
					<input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][code]" value="<?php echo $lang['code']; ?>" maxlength="7" style="width:30px; max-width: 7em"/>
					<?php
				} else {
					?>
					<div class="read-only" id="icl_edit_languages[<?php echo $lang['id']; ?>][code]"><?php echo $lang['code']; ?> <input type="hidden"
					                                                                                                                     name="icl_edit_languages[<?php echo $lang['id']; ?>][code]"
					                                                                                                                     value="<?php echo $lang['code']; ?>"
					                                                                                                                     style="width:30px;"/>
					</div>
					<?php
				}
				?>
			</td>
			<td
                <?php if ( $this->must_display_new_language_translation_column() ) {
				    echo 'style="display:none;" ';
			    }
			    ?>class="icl_edit_languages_show"><input type="text"
			                                           name="icl_edit_languages[<?php echo $lang['id']; ?>][translations][add]"
			                                           value="<?php echo $this->get_add_language_from_post_data( $lang['id'] ); ?>"/>
			</td>
			<?php
			foreach ( $this->active_languages as $translation ) {
					?>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][translations][<?php echo $translation['code']; ?>]" value="<?php echo $this->get_translations_data( $lang, $translation ); ?>" /></td>
				<?php
			}
			?>
			<td>
				<?php
				if ( $this->is_writable ) {
					$allowed_types = array_keys( $this->allowed_flag_mime_types );
					?>
					<div style="float:left;">
						<ul>
							<li>
								<input type="radio"
								       id="wpm-edit-languages-<?php echo $lang['id']; ?>-flag-upload"
								       name="icl_edit_languages[<?php echo $lang['id']; ?>][flag_upload]"
								       value="true"
								       class="radio icl_edit_languages_use_upload"<?php if ( $lang['from_template'] ) { ?> checked="checked"<?php } ?> />
								&nbsp;
								<label for="wpm-edit-languages-<?php echo $lang['id']; ?>-flag-upload">
									<?php _e( 'Upload flag', 'sitepress' ); ?>
								</label>
								<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $this->max_file_size; ?>"/>

										<div class="wpml-edit-languages-flag-upload-wrapper" <?php if ( ! $lang['from_template'] ) { ?>style="display: none;"<?php } ?>>
											<input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][flag]" value="<?php echo $lang['flag']; ?>" class="icl_edit_languages_flag_enter_field" style="width: auto;"/>
											<br/>
											<input type="file" name="icl_edit_languages[<?php echo $lang['id']; ?>][flag_file]" class="icl_edit_languages_flag_upload_field file" style="width: 200px;"/>
											<br/>
											<?php echo sprintf( __( '(allowed: %s)', 'sitepress' ), implode( ', ', $allowed_types ) ); ?>
										</div>

									</li>
									<li>
										<label>
											<input type="radio"
											       name="icl_edit_languages[<?php echo $lang['id']; ?>][flag_upload]"
											       value="false"
											       class="radio icl_edit_languages_use_field"<?php if ( ! $lang['from_template'] ) { ?> checked="checked"<?php } ?> />
											&nbsp;
											<?php _e( 'Use flag from WPML', 'sitepress' ); ?>
										</label>

									</li>
								</ul>
							</div>
							<?php
						}
						?>
					</td>
                    <td>
	                    <div class="wpml-edit-languages-flag-use-field">
		                    <input type="text"
		                           name="icl_edit_languages[<?php echo $lang['id']; ?>][default_locale]"
		                           value="<?php echo $lang['default_locale']; ?>"
		                           maxlength="<?php echo $this->max_locale_length; ?>"
		                           style="width: auto; max-width: 15em;"/>
	                    </div>
                    </td>
                    <td>
                        <select name="icl_edit_languages[<?php echo $lang['id']; ?>][encode_url]">
                            <option value="0" <?php if(empty($lang['encode_url'])): ?>selected="selected"<?php endif;?>><?php _e('No', 'sitepress') ?></option>
                            <option value="1" <?php if(!empty($lang['encode_url'])): ?>selected="selected"<?php endif;?>><?php _e('Yes', 'sitepress') ?></option>
                        </select>
                    </td>

			<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][tag]" maxlength="<?php echo $this->max_locale_length; ?>" value="<?php echo $lang['tag']; ?>" style="width: auto; max-width: 15em;"/></td>
                    
                    <td>
                        <?php
                        if (
                            !$add
                            && !in_array( $lang[ 'code' ], $this->built_in_languages )
                            && $lang[ 'code' ] != $sitepress->get_default_language()
                            && count( $this->active_languages ) > 1
                            ):
                        ?>
                            <a href="<?php echo admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/languages.php&amp;trop=1&amp;action=delete-language&amp;id=' .
                            $lang['id'] . '&amp;icl_nonce=' . wp_create_nonce('delete-language' . $lang['id'])) ?>" title="<?php esc_attr_e('Delete', 'sitepress') 
                            ?>" onclick="if(!confirm('<?php echo esc_js(sprintf(__('Are you sure you want to delete this language?%sALL the data associated with this language will be ERASED!', 'sitepress'), "\n")) 
                            ?>')) return false;"><img src="<?php echo ICL_PLUGIN_URL ?>/res/img/close.png" alt="<?php esc_attr_e('Delete', 'sitepress') 
                            ?>" width="16" height="16" /></a>
                        <?php endif; ?>
                    </td>
                    
				</tr>
<?php
	}

	function get_active_languages() {
		global $sitepress, $wpdb;
		$this->active_languages = $sitepress->get_active_languages(true);        
        
		foreach ($this->active_languages as $lang) {
			foreach ($this->active_languages as $lang_translation) {
				$this->active_languages[$lang['code']]['translation'][$lang_translation['id']] = $sitepress->get_display_language_name($lang['code'], $lang_translation['code']);
			}
			$flag = $sitepress->get_flag($lang['code']);
			$this->active_languages[$lang['code']]['flag'] = $flag->flag;
			$this->active_languages[$lang['code']]['from_template'] = $flag->from_template;
			$this->active_languages[$lang['code']]['default_locale'] = $wpdb->get_var( $wpdb->prepare( "SELECT default_locale FROM {$wpdb->prefix}icl_languages WHERE code=%s", $lang['code'] ) );
            $this->active_languages[$lang['code']]['encode_url'] = $lang['encode_url'];
            $this->active_languages[$lang['code']]['tag'] = $lang['tag'];
		}
        
        
	}

	function insert_main_table($code, $english_name, $default_locale, $major = 0, $active = 0, $encode_url = 0, $tag = '') {
		global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'icl_languages', array(
            'code'          => $code,
            'english_name'  => $english_name,
            'default_locale'=> $default_locale,
            'major'         => $major,
            'active'        => $active,
            'encode_url'    => $encode_url,
            'tag'           => $tag
        ));
	}

	function update_main_table($id, $code, $default_locale, $encode_url, $tag){
		global $wpdb;
    $wpdb->update($wpdb->prefix . 'icl_languages', array('code' => $code, 'default_locale' => $default_locale, 'encode_url'=>$encode_url, 'tag' => $tag), array('ID' => $id));
	}

	function insert_translation($name, $language_code, $display_language_code) {
		global $wpdb;
		$insert_sql       = "INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES(%s, %s, %s)";
		$insert_prepared = $wpdb->prepare( $insert_sql, array($name, $language_code, $display_language_code) );
		return $wpdb->query( $insert_prepared );
	}

	function update_translation($name, $language_code, $display_language_code) {
		global $wpdb;
		$update_sql      = "UPDATE {$wpdb->prefix}icl_languages_translations SET name=%s WHERE language_code = %s AND display_language_code = %s";
		$update_prepared = $wpdb->prepare( $update_sql, array($name, $language_code, $display_language_code) );
		$wpdb->query( $update_prepared );
	}

	function insert_flag($lang_code, $flag, $from_template) {
		global $wpdb;
		$insert_sql      = "INSERT INTO {$wpdb->prefix}icl_flags (lang_code, flag, from_template) VALUES(%s, %s, %s)";
		$insert_prepared = $wpdb->prepare( $insert_sql, array($lang_code, $flag, $from_template) );
		return $wpdb->query( $insert_prepared );
	}

	function update_flag($lang_code, $flag, $from_template) {
		global $wpdb;
		$update_sql      = "UPDATE {$wpdb->prefix}icl_flags SET flag= %s,from_template=%s WHERE lang_code = %s";
		$update_prepared = $wpdb->prepare( $update_sql, array($flag, $from_template, $lang_code) );
		$wpdb->query( $update_prepared );
	}
	
	function update() {
		$this->mode = 'save';

		// Basic check.
		if (!isset($_POST['icl_edit_languages']) || !is_array($_POST['icl_edit_languages'])){
			$this->set_errors(__('Please, enter valid data.','sitepress'));
			return;
		}

		global $sitepress,$wpdb;

		// First check if add and validate it.
		if (isset($_POST['icl_edit_languages']['add']) && $_POST['icl_edit_languages_ignore_add'] == 'false') {
			if ($this->validate_one('add', $_POST['icl_edit_languages']['add'])) {
				$this->insert_one($this->sanitize($_POST['icl_edit_languages']['add']));
			}
			// Reset flag upload field.
			$_POST['icl_edit_languages']['add']['flag_upload'] = 'false';
		}
		
		foreach ($_POST['icl_edit_languages'] as $id => $data){
			// Ignore insert.
			if ($id == 'add') { continue; }
			
			// Validate and sanitize data.
			if (!$this->validate_one($id, $data)) continue;
			$data = stripslashes_deep($data);
			
			// Update main table.
			$this->update_main_table($id, $data['code'], $data['default_locale'], $data['encode_url'], $data['tag']);
            
            if (
                $wpdb->get_var(
                    $wpdb->prepare( "SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE code = %s", $data[ 'code' ] )
                )
            ) {
                $wpdb->update($wpdb->prefix.'icl_locale_map', array('locale'=>$data['default_locale']), array('code'=>$data['code']));
            }else{
                $wpdb->insert($wpdb->prefix.'icl_locale_map', array('code'=>$data['code'], 'locale'=>$data['default_locale']));
            }
            
			// Update translations table.
			foreach ($data['translations'] as $translation_code => $translation_value) {
				
				// If new (add language) translations are submitted.
				if ($translation_code == 'add' ) {
					if ( ( $this->is_new_data_and_invalid() ) || $_POST['icl_edit_languages_ignore_add'] == 'true') {
						continue;
					}
					if (empty($translation_value)) {
						$translation_value = $data['english_name'];
					}
					$translation_code = $_POST['icl_edit_languages']['add']['code'];
				}
				
				// Check if update.
                if ( $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}icl_languages_translations WHERE language_code = %s AND display_language_code=%s",
                        $data[ 'code' ],
                        $translation_code
                    ) )
                ) {
					$this->update_translation($translation_value, $data['code'], $translation_code);
				} else {
					if (!$this->insert_translation($translation_value, $data['code'], $translation_code)) {
						$this->set_errors(sprintf(__('Error adding translation %s for %s.', 'sitepress'), $data['code'], $translation_code));
					}
				}
			}

			// Handle flag.
			if ($data['flag_upload'] == 'true' && !empty($_FILES['icl_edit_languages']['name'][$id]['flag_file'])) {
				if ( $filename = $this->upload_flag( $id ) ) {
					$data['flag'] = $filename;
					$from_template = 1;
				} else {
					$data['flag'] = $data['code'] . '.png';
					$this->set_errors(__('Error uploading flag file.', 'sitepress'));
					$from_template = 0;
				}
			} else {
				if (empty($data['flag']) || $data['flag_upload'] == 'false') {
					$data['flag'] = $data['code'] . '.png';
					$from_template = 0;
				} else {
                    $from_template = $data['flag_upload'] == 'true' ? 1 : 0;
				}
			}
			// Update flag table.
			$this->update_flag($data['code'], $data['flag'], $from_template);
			// Reset flag upload field.
			$_POST['icl_edit_languages'][$id]['flag_upload'] = 'false';
		}
		// Refresh cache.
		$sitepress->get_language_name_cache()->clear();
		$sitepress->clear_flags_cache();
		delete_option('_icl_cache');
		
		// Unset ADD fields.
		if ( $this->is_new_data_and_valid()) {
			unset($_POST['icl_edit_languages']['add']);
		}

		// Reset active languages.
		$this->get_active_languages();
		$this->update_language_packs( $sitepress );
	}

	function insert_one($data) {
		global $sitepress, $wpdb;
		
		$data = stripslashes_deep(stripslashes_deep($data));
		// Insert main table.
		if (!$this->insert_main_table($data['code'], $data['english_name'], $data['default_locale'], 0, 1, $data['encode_url'], $data['tag'])) {
			$this->set_errors(__('Adding language failed.', 'sitepress'));
			return false;
		}

		// add locale map
        $locale_exists = $wpdb->get_var($wpdb->prepare("SELECT code
                                                        FROM {$wpdb->prefix}icl_locale_map
                                                        WHERE code=%s", $data['code']));
        if($locale_exists){
            $wpdb->update($wpdb->prefix.'icl_locale_map', array('locale'=>$data['default_locale']), array('code'=>$data['code']));
        }else{
            $wpdb->insert($wpdb->prefix.'icl_locale_map', array('code'=>$data['code'], 'locale'=>$data['default_locale']));
        }
		
			// Insert translations.
        $all_languages = $sitepress->get_languages();
        foreach ( $all_languages as $key => $lang ) {

            // If submitted.
            if ( array_key_exists( $lang[ 'code' ], $data[ 'translations' ] ) ) {
                if ( empty( $data[ 'translations' ][ $lang[ 'code' ] ] ) ) {
                    $data[ 'translations' ][ $lang[ 'code' ] ] = $data[ 'english_name' ];
                }
                if ( !$this->insert_translation(
                    $data[ 'translations' ][ $lang[ 'code' ] ],
                    $data[ 'code' ],
                    $lang[ 'code' ]
                )
                ) {
                    $this->set_errors(
                        sprintf(
                            __( 'Error adding translation %s for %s.', 'sitepress' ),
                            $data[ 'code' ],
                            $lang[ 'code' ]
                        )
                    );
                }
            } else {
                if ( !$this->insert_translation( $data[ 'english_name' ], $data[ 'code' ], $lang[ 'code' ] ) ) {
                    $this->set_errors(
                        sprintf(
                            __( 'Error adding translation %s for %s.', 'sitepress' ),
                            $data[ 'code' ],
                            $lang[ 'code' ]
                        )
                    );
                }
            }
        }
		
		// Insert native name.
		if (!isset($data['translations']['add']) || empty($data['translations']['add'])) {
			$data['translations']['add'] = $data['english_name'];
		}
		if (!$this->insert_translation($data['translations']['add'], $data['code'], $data['code'])) {
			$this->set_errors(__('Error adding native name.', 'sitepress'));
		}

		// Handle flag.
		if ( array_key_exists( 'flag_upload', $data ) && $data['flag_upload'] === 'true' && ! empty( $_FILES['icl_edit_languages']['name']['add']['flag_file'] ) ) {
			if ( $filename = $this->upload_flag( 'add' ) ) {
				$data['flag'] = $filename;
				$from_template = 1;
			} else {
				$data['flag'] = $data['code'] . '.png';
				$from_template = 0;
			}
		} else {
			if ( empty($data['flag'] ) ) {
				$data['flag'] = $data['code'] . '.png';
			}
			$from_template = 0;
		}
		
		// Insert flag table.
		if (!$this->insert_flag($data['code'], $data['flag'], $from_template)) {
			$this->set_errors(__('Error adding flag.', 'sitepress'));
		}
        SitePress_Setup::insert_default_category ( $data[ 'code' ] );
	}

	function validate_one($id, $data) {
	
		global $wpdb;

		$new_record = 'add' === $id;

		$unique_columns = array(
			'code'           => __( 'The Language code already exists.', 'sitepress' ),
			'english_name'   => __( 'The Language name already exists.', 'sitepress' ),
			'default_locale' => __( 'The default locale already exists.', 'sitepress' ),
			'tag'            => __( 'The tag already exists.', 'sitepress' ),
		);

		foreach ( $unique_columns as $column => $message ) {
			$exists_args = array( $data[ $column ] );

			$exists_query = 'SELECT ' . esc_sql( $column ) . ' FROM ' . $wpdb->prefix . 'icl_languages WHERE ' . esc_sql( $column ) . '=%s ';

			if ( ! $new_record ) {
				$exists_query .= 'AND id!=%d ';
				$exists_args[] = $id;
			}

			$exists_query .= 'LIMIT 1';

			$exists = $wpdb->get_var( $wpdb->prepare( $exists_query, $exists_args ) );

			if ( $exists ) {
				$this->error             = $message;
				$this->set_validation_failed( $id );

				return false;
			}
		}

		foreach ($this->required_fields as $name => $type ) {
			if ( 'flag' === $name ) {
				if ( isset( $data['flag_upload'] ) && 'true' === $data['flag_upload'] ) {
					$check =  $_FILES['icl_edit_languages']['name'][$id]['flag_file'];
					if (empty($check)) continue;
					if (!$this->check_extension($check ) ) {
						if ( 'add' === $id ) {
							$this->set_validation_failed($id);
						}
						return false;
					}
				}
				continue;
			}
			if (!isset($_POST['icl_edit_languages'][$id][$name]) || empty($_POST['icl_edit_languages'][$id][$name ] ) ) {
				if ( 'true' === $_POST['icl_edit_languages_ignore_add'] ) {
					return false;
				}
				$this->set_errors(__('Please, enter required data.','sitepress' ) );
				if ( 'add' === $id ) {
					$this->set_validation_failed($id);
				}
				return false;
			}
			if ( 'array' === $type && ! is_array( $_POST['icl_edit_languages'][ $id ][ $name ] ) ) {
				if ( 'add' === $id ) {
					$this->set_validation_failed($id);
				}
				$this->set_errors(__('Please, enter valid data.','sitepress')); return false;
			}
		}
		return true;
	}

	/**
	 * @return bool
	 */
	private function is_delete_language_action() {
		return isset( $_GET['action'] ) && 'delete-language' === $_GET['action'] && wp_create_nonce( 'delete-language' . (int) $_GET['id'] ) == $_GET['icl_nonce'];
	}

	/**
	 * @return bool
	 */
	private function must_display_new_language_translation_column() {
		return $this->is_edit_mode() || $this->is_new_data_and_valid();
	}

	/**
	 * @return bool
	 */
	private function is_new_data_and_invalid(){
		return $this->validation_action && $this->validation_failed && 'add' === $this->validation_action;
	}

	/**
	 * @return bool
	 */
	private function is_new_data_and_valid(){
		return $this->validation_action && ! $this->validation_failed && 'add' === $this->validation_action;
	}

	/**
	 * @return bool
	 */
	private function is_edit_mode() {
		return 'edit' === $this->mode;
	}

	private function set_validation_failed($id) {
		$this->validation_action = ('add'===$id) ? 'add' : 'update';
		$this->validation_failed = true;
	}
    
    function delete_language($lang_id){
        global $wpdb, $sitepress;
        $lang = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_languages WHERE id=%d", $lang_id));
        if($lang ) {
	        if ( in_array( $lang->code, $this->built_in_languages, true ) ) {
                $error = __("Error: This is a built in language. You can't delete it.", 'sitepress');
            }else{
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_languages WHERE id=%d", $lang_id));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_languages_translations WHERE language_code=%s", $lang->code));
                
                $translation_ids = $wpdb->get_col($wpdb->prepare("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE language_code=%s", $lang->code));
                if($translation_ids){
                    $rids = $wpdb->get_col("SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id IN (" . wpml_prepare_in($translation_ids, '%d' ) . ")");
                    if($rids){
                        $job_ids = $wpdb->get_col("SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid IN (" . wpml_prepare_in($rids, '%d' ) . ")");
                        if($job_ids){
                            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id IN (" . wpml_prepare_in($job_ids, '%d' ) . ")");
                        }
                    }    
                }
                
                // delete posts
                $post_ids = $wpdb->get_col(
												$wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type LIKE %s AND language_code=%s", 
																array( wpml_like_escape('post_') . '%', $lang->code ) )
																);
                remove_action('delete_post', array($sitepress,'delete_post_actions'));
                foreach($post_ids as $post_id){
                    wp_delete_post($post_id, true);
                }
                add_action('delete_post', array($sitepress,'delete_post_actions'));
                
                // delete terms
                remove_action('delete_term',  array($sitepress, 'delete_term'),1,3);
                $tax_ids = $wpdb->get_col(
												$wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type LIKE %s AND language_code=%s", 
																array( wpml_like_escape('tax_') . '%', $lang->code ) )
																);
                foreach($tax_ids as $tax_id){
                    $row = $wpdb->get_row($wpdb->prepare("SELECT term_id, taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d", $tax_id));
                    if($row){
                        wp_delete_term($row->term_id, $row->taxonomy);    
                    }
                }
                add_action('delete_term',  array($sitepress, 'delete_term'),1,3);
                
                // delete comments
                global $IclCommentsTranslation;
                remove_action('delete_comment', array($IclCommentsTranslation, 'delete_comment_actions'));
                foreach($post_ids as $post_id){
                    wp_delete_post($post_id, true);
                }
                add_action('delete_comment', array($IclCommentsTranslation, 'delete_comment_actions'));

		        do_action(
		        	'wpml_translation_update',
			        array(
			            'type' => 'before_language_delete',
				        'language' => $lang->code
			        )
		        );
                
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translations WHERE language_code=%s", $lang->code));

                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_strings WHERE language=%s", $lang->code));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_string_translations WHERE language=%s", $lang->code));
                
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_locale_map WHERE code=%s", $lang->code));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_flags WHERE lang_code=%s", $lang->code));
                
                icl_cache_clear(false);
                
                $sitepress->get_translations_cache()->clear();
                $sitepress->clear_flags_cache();
                $sitepress->get_language_name_cache()->clear();
                
                $this->set_messages(sprintf(__("The language %s was deleted.", 'sitepress'), '<strong>' . $lang->code . '</strong>'));

		        $this->update_language_packs( $sitepress );
            }                
        }else{
            $error = __('Error: Language not found.', 'sitepress');
        }
        if(!empty($error)){
            $this->set_errors($error);
        }            
    }
		
	function sanitize($data) {
		global $wpdb;
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $k => $v) {
					$data[$key][$k] = esc_sql($v);
				}
			}
			$data[$key] = esc_sql($value);
		}
		return $data;
	}

	function check_extension($file) {        
		$extension = substr($file, strrpos($file, '.') + 1);
		if ( ! in_array( strtolower( $extension ), array( 'png', 'gif', 'jpg', 'svg' ), true ) ) {
			$this->set_errors(__('File extension not allowed.','sitepress'));
			return false;
		}
		return true;
	}

	function get_errors() {
		return $this->error;
	}
	
	function set_errors($str = false) {
		$this->error .= $str . '<br />';
	}
	
	function get_messages() {
		return $this->message;
	}
    
    function set_messages($str = false) {
        $this->message .= $str . '<br />';
    }

	function upload_flag( $id ) {
		$result        = false;
		$uploaded_file = false;

		if ( isset( $_FILES['icl_edit_languages']['tmp_name'][ $id ]['flag_file'] ) ) {
			$uploaded_file = filter_var( $_FILES['icl_edit_languages']['tmp_name'][ $id ]['flag_file'], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		if ( $uploaded_file ) {
			$filename    = basename( $uploaded_file );
			$target_path = $this->upload_dir . '/' . $filename;

			$wpml_wp_api = new WPML_WP_API();

			$mime               = $wpml_wp_api->get_file_mime_type( $_FILES['icl_edit_languages']['tmp_name'][ $id ]['flag_file'] );
			$allowed_mime_types = array_values( $this->allowed_flag_mime_types );
			$validated          = in_array( $mime, $allowed_mime_types, true );

			if ( $validated && move_uploaded_file( $uploaded_file, $target_path ) ) {

				if ( function_exists( 'wp_get_image_editor' ) && 'image/svg+xml' !== $mime ) {
					$image = wp_get_image_editor( $target_path );
					if ( ! is_wp_error( $image ) ) {
						$image->resize( 18, 12, true );
						$image->save( $target_path );
					}
				}

				$result = $filename;
			}
		} else {
			$error_message = __( 'There was an error uploading the file, please try again!', 'sitepress' );
			if ( ! empty( $_FILES['icl_edit_languages']['error'][ $id ]['flag_file'] ) ) {
				switch ( $_FILES['icl_edit_languages']['error'][ $id ]['flag_file'] ) {
					case UPLOAD_ERR_INI_SIZE;
						$error_message = __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'sitepress' );
						break;
					case UPLOAD_ERR_FORM_SIZE;
						$error_message = sprintf( __( 'The uploaded file exceeds %s bytes.', 'sitepress' ), $this->max_file_size );
						break;
					case UPLOAD_ERR_PARTIAL;
						$error_message = __( 'The uploaded file was only partially uploaded.', 'sitepress' );
						break;
					case UPLOAD_ERR_NO_FILE;
						$error_message = __( 'No file was uploaded.', 'sitepress' );
						break;
					case UPLOAD_ERR_NO_TMP_DIR;
						$error_message = __( 'Missing a temporary folder.', 'sitepress' );
						break;
					case UPLOAD_ERR_CANT_WRITE;
						$error_message = __( 'Failed to write file to disk.', 'sitepress' );
						break;
					case UPLOAD_ERR_EXTENSION;
						$error_message = __( 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.', 'sitepress' );
						break;
				}
			}
			$this->set_errors( $error_message );
		}

		return $result;
	}

	function migrate() {
		global $sitepress, $sitepress_settings;
		if (!isset($sitepress_settings['edit_languages_flag_migration'])) {
			foreach( glob(get_stylesheet_directory().'/flags/*') as $filename ){
				rename($filename, $this->upload_dir . '/' . basename($filename));
			}
			$sitepress->save_settings(array('edit_languages_flag_migration' => 1));
		}
	}

	/**
	 * @param $sitepress
	 */
	private function update_language_packs( SitePress $sitepress ) {
		$wpml_localization = new WPML_Download_Localization( $sitepress->get_active_languages(), $sitepress->get_default_language() );
		$wpml_localization->download_language_packs();
		$wpml_languages_notices = new WPML_Languages_Notices( wpml_get_admin_notices() );
		$wpml_languages_notices->missing_languages( $wpml_localization->get_not_founds() );
	}

	/**
	 * @param $id
	 *
	 * @return string
	 */
	private function get_add_language_from_post_data( $id ) {
		$value = isset( $_POST['icl_edit_languages'][ $id ]['translations']['add'] ) ? stripslashes_deep( $_POST['icl_edit_languages'][ $id ]['translations']['add'] ) : '';
		$value = filter_var( $value, FILTER_SANITIZE_STRING );

		return $value;
	}

	/**
	 * @param $lang
	 * @param $translation
	 *
	 * @return string
	 */
	private function get_translations_data( $lang, $translation ) {
		if ( $lang['id'] == 'add' ) {
			$value = isset( $_POST['icl_edit_languages']['add']['translations'][ $translation['code'] ] ) ? $_POST['icl_edit_languages']['add']['translations'][ $translation['code'] ] : '';
			$value = filter_var( $value, FILTER_SANITIZE_STRING );
		} else {
			$value = isset( $lang['translation'][ $translation['id'] ] ) ? $lang['translation'][ $translation['id'] ] : '';
		}
		$value = stripslashes_deep( $value );

		return $value;
	}

}