<?php
global $sitepress;
?>
<div class="wpml-section" id="ml-content-setup-sec-5-1">

    <div class="wpml-section-header">
        <h3><?php _e('XLIFF file options', 'wpml-translation-management');?></h3>
    </div>
	
	<div class="wpml-section-content">

        <form name="icl_xliff_options_form" id="icl_xliff_options_form" action="">
            <?php wp_nonce_field('icl_xliff_options_form_nonce', '_icl_nonce'); ?>

	        <div class="wpml-section-content-inner">

		        <h4><?php _e('XLIFF version', 'wpml-translation-management') ?></h4>

		        <p>
			        <?php _e('Choose default format for XLIFF file:', 'wpml-translation-management'); ?>

			        <select name="icl_xliff_version">
				        <option value="false"><?php echo __("Please choose", "wpml-xliff"); ?></option>
				        <?php
				        $xliff_instance = setup_xliff_frontend();
				        $available_xliff_versions = $xliff_instance->get_available_xliff_versions();
				        foreach ($available_xliff_versions as $value => $version) {
					        $selected = "";
					        if ($sitepress->get_setting("tm_xliff_version") == $value) {
						        $selected = "selected";
					        }
					        printf ( "<option value='".$value."' ".$selected.">" . __('XLIFF %s', 'wpml-translation-management') . "</option>", $version );
				        }
				        ?>
			        </select>
		        </p>
	        </div>

	        <div class="wpml-section-content-inner">

		        <?php
		        $xliff_newlines = $sitepress->get_setting('xliff_newlines') ? intval($sitepress->get_setting('xliff_newlines')) : WPML_XLIFF_TM_NEWLINES_REPLACE;
		        ?>
		        <h4><?php _e('New lines character', 'wpml-translation-management') ?></h4>
				<p>
	                <?php _e('How new lines characters in XLIFF files should be handled?', 'wpml-translation-management'); ?>
	            </p>

							<p>
	                <label>
	                    <input type="radio" name="icl_xliff_newlines" value="<?php echo WPML_XLIFF_TM_NEWLINES_REPLACE ?>"<?php if ( $xliff_newlines == WPML_XLIFF_TM_NEWLINES_REPLACE ): ?>checked<?php endif ?>/>
	                    <?php printf(
															__('All new lines should be replaced by HTML element %s. Use this option if translation tool used by translator does not support new lines characters (for example Virtaal software)', 'wpml-translation-management')
															, '&lt;br class="xliff-newline" />'); ?>
	                </label>
	            </p>
						
				<p>
                <label>
                    <input type="radio" name="icl_xliff_newlines" value="<?php echo WPML_XLIFF_TM_NEWLINES_ORIGINAL ?>"<?php if ( $xliff_newlines == WPML_XLIFF_TM_NEWLINES_ORIGINAL ): ?>checked<?php endif ?>/>
                    <?php _e('Do nothing. If you will select this, all new line characters will stay untouched.', 'wpml-translation-management'); ?>
                </label>
            </p>

			</div>
            <p class="buttons-wrap">
                <span class="icl_ajx_response" id="icl_ajx_response"></span>
                <input type="submit" class="button-primary" value="<?php _e('Save', 'wpml-translation-management')?>" />
            </p>
        </form>
    </div> <!-- .wpml-section-content -->
	
</div>


