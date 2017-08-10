<div class="wpml-section" id="ml-content-setup-sec-4">

    <div class="wpml-section-header">
        <h3><?php _e('Custom posts slug translation options', 'wpml-string-translation') ?></h3>
    </div>

    <div class="wpml-section-content">

        <form name="icl_slug_translation" id="icl_slug_translation" action="">
            <?php wp_nonce_field('icl_slug_translation_nonce', '_icl_nonce'); ?>
            <p>
                <label>
                    <input type="checkbox" name="icl_slug_translation_on" value="1" <?php checked(1,$sitepress_settings['posts_slug_translation']['on'],true) ?>  />&nbsp;
                    <?php _e("Translate custom posts slugs (via WPML String Translation).", 'wpml-string-translation') ?>
                </label>
            </p>

            <p class="buttons-wrap">
                <span class="icl_ajx_response" id="icl_ajx_response_sgtr"></span>
                <input type="submit" class="button-primary" value="<?php _e('Save', 'wpml-string-translation')?>" />
            </p>
        </form>
    </div> <!-- .wpml-section-content -->

</div> <!-- .wpml-section -->
