<?php
if ( !isset( $wpdb ) ) {
	global $wpdb;
}
if ( !isset( $sitepress_settings ) ) {
	global $sitepress_settings;
}
if ( !isset( $sitepress ) ) {
	global $sitepress;
}
if ( !isset( $iclTranslationManagement ) ) {
	global $iclTranslationManagement;
}
global $wp_taxonomies;

function prepare_synchronization_needed_warning( $elements, $type ) {
	$notice = '';
	if ( $elements ) {
		$msg = esc_html( __( "You haven't set your synchronization preferences for these %s: %s. Default value was selected.", 'sitepress' ) );
		$notice .= '<div class="updated below-h2"><p>';
		$notice .= sprintf( $msg, $type, '<i>' . implode( '</i>, <i>', $elements ) . '</i>' );
		$notice .= '</p></div>';
	}

	return $notice;
}

$default_language = $sitepress->get_default_language();

$wpml_post_types = new WPML_Post_Types( $sitepress );
$custom_posts = $wpml_post_types->get_translatable_and_readonly( true );

$custom_posts_sync_not_set = array();
foreach ( $custom_posts as $k => $custom_post ) {
	if ( !isset( $sitepress_settings[ 'custom_posts_sync_option' ][ $k ] ) ) {
		$custom_posts_sync_not_set[ ] = $custom_post->labels->name;
	}
}

$custom_taxonomies = array_diff( array_keys( (array) $wp_taxonomies ), array( 'post_tag', 'category', 'nav_menu', 'link_category', 'post_format' ) );

$tax_sync_not_set = array();
foreach ( $custom_taxonomies as $custom_tax ) {
	if ( !isset( $sitepress_settings[ 'taxonomies_sync_option' ][ $custom_tax ] ) ) {
		$tax_sync_not_set[ ] = $wp_taxonomies[ $custom_tax ]->label;
	}
}

if ( $custom_posts ) {
	$notice = prepare_synchronization_needed_warning( $custom_posts_sync_not_set, 'custom posts' );

	if ( class_exists( 'WPML_Custom_Post_Slug_UI' ) ) {
		$CPT_slug_UI = new WPML_Custom_Post_Slug_UI( $wpdb, $sitepress );
	} else {
		$CPT_slug_UI = null;
	}
	
	?>


    <div class="wpml-section" id="ml-content-setup-sec-7">

        <div class="wpml-section-header">
            <h3><?php esc_html_e( 'Custom posts', 'sitepress' );?></h3>
        </div>

        <div class="wpml-section-content">

            <?php
            	if ( isset( $notice ) ) {
            		echo $notice;
            	}

            ?>

            <form id="icl_custom_posts_sync_options" name="icl_custom_posts_sync_options" action="">
				<?php wp_nonce_field('icl_custom_posts_sync_options_nonce', '_icl_nonce') ?>

                <table class="widefat">
                    <thead>
                        <tr>
                            <th colspan="3">
                                <?php esc_html_e( 'Custom post types', 'sitepress' ); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($custom_posts as $k=>$custom_post): ?>
                            <?php
                                $rdisabled = isset($iclTranslationManagement->settings['custom-types_readonly_config'][$k]) ? 'disabled="disabled"':'';

		                        $is_translated = false;
		                        if ( isset( $sitepress_settings['custom_posts_sync_option'][ $k ] ) ) {
			                        $is_translated = ( (int) $sitepress_settings['custom_posts_sync_option'][ $k ] ) === 1;
		                        }
                            ?>
                            <tr>
                                <td>

                                    <p>
                                        <?php echo esc_html( $custom_post->labels->name ); ?>
                                    </p>
									
                                </td>
                                <td align="right">
                                    <p>
                                        <label>
	                                        <input class="icl_sync_custom_posts" type="radio" name="icl_sync_custom_posts[<?php echo esc_attr( $k ) ?>]" value="1" <?php echo $rdisabled; ?>
		                                        <?php checked( true, $is_translated ) ?> />
                                            <?php esc_html_e( 'Translate', 'sitepress' ) ?>
                                        </label>
                                    </p>
                                </td>
                                <td>
                                   <p>
                                        <label>
	                                        <input class="icl_sync_custom_posts" type="radio" name="icl_sync_custom_posts[<?php echo esc_attr( $k ) ?>]" value="0" <?php echo $rdisabled; ?>
		                                        <?php checked( false, $is_translated ) ?> />
                                            <?php esc_html_e( 'Do nothing', 'sitepress' ) ?>
                                        </label>
                                   </p>
                                    <?php if ($rdisabled): ?>
	                                    <input type="hidden" name="icl_sync_custom_posts[<?php echo esc_attr( $k ) ?>]" value="<?php echo $is_translated ? 1 : 0 ?>"/>
                                    <?php endif; ?>
                                </td>
                            </tr>
							<tr>
								<td colspan="3">
									<?php
										if ( $CPT_slug_UI ) {
											$CPT_slug_UI->render( $k, $custom_post );
										}
									?>
								</td>
							</tr>
							
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="buttons-wrap">
                    <span class="icl_ajx_response" id="icl_ajx_response_cp"></span>
                    <input type="submit"
						   id="js_custom_posts_sync_button"
						   class="button button-primary"
						   value="<?php esc_attr_e( 'Save', 'sitepress' ) ?>"
						   data-message="<?php esc_attr_e( "You haven't entered translations for all slugs. Are you sure you want to save these settings?", 'sitepress' );?>" />
                </p>

            </form>

        </div> <!-- .wpml-section-content -->

    </div> <!-- wpml-section -->

<?php
}

if ( $custom_taxonomies ) {
	$notice = prepare_synchronization_needed_warning( $tax_sync_not_set, 'taxonomies' );

?>
	<div class="wpml-section" id="ml-content-setup-sec-8">

	    <div class="wpml-section-header">
	        <h3><?php esc_html_e( 'Custom taxonomies', 'sitepress' ); ?></h3>
	    </div>

	    <div class="wpml-section-content">

		    <?php
		    if ( isset( $notice ) ) {
			    echo $notice;
		    }

		    ?>

		    <form id="icl_custom_tax_sync_options" name="icl_custom_tax_sync_options" action="">
	            <?php wp_nonce_field('icl_custom_tax_sync_options_nonce', '_icl_nonce') ?>
	            <table class="widefat">
	                <thead>
	                    <tr>
	                        <th colspan="3">
	                            <?php esc_html_e( 'Custom taxonomies', 'sitepress' ); ?>
	                        </th>
	                    </tr>
	                </thead>
	                <tbody>
	                    <?php foreach($custom_taxonomies as $ctax): ?>
	                    <?php
		                    $rdisabled = isset($iclTranslationManagement->settings['taxonomies_readonly_config'][$ctax]) ? 'disabled':'';

		                    $is_translated = false;
		                    if ( isset( $sitepress_settings['taxonomies_sync_option'][ $ctax ] ) ) {
			                    $is_translated = ( (int) $sitepress_settings['taxonomies_sync_option'][ $ctax ] ) === 1;
		                    }

	                    ?>
	                    <tr>
	                        <td>
	                            <p><?php echo esc_html( $wp_taxonomies[ $ctax ]->label ); ?> (<i><?php echo esc_html( $ctax ); ?></i>)</p>
	                        </td>
	                        <td align="right">
	                            <p>
	                                <label>
	                                    <input type="radio" name="icl_sync_tax[<?php echo esc_attr( $ctax ) ?>]" value="1" <?php echo $rdisabled; ?> <?php checked( true, $is_translated ) ?> />
	                                    <?php esc_html_e( 'Translate', 'sitepress' ) ?>
	                                </label>
	                            </p>
	                        </td>
	                        <td>
	                            <p>
	                                <label>
	                                    <input type="radio" name="icl_sync_tax[<?php echo esc_attr( $ctax ) ?>]" value="0" <?php echo $rdisabled ?> <?php checked( false, $is_translated ) ?> />
	                                    <?php esc_html_e( 'Do nothing', 'sitepress' ) ?>
	                                </label>
	                            </p>
	                        </td>
	                    </tr>
	                    <?php endforeach; ?>
	                </tbody>
	            </table>
	            <p class="buttons-wrap">
	                <span class="icl_ajx_response" id="icl_ajx_response_ct"></span>
	                <input type="submit" class="button-primary" value="<?php esc_html_e( 'Save', 'sitepress' ) ?>" />
	            </p>
	        </form>
	    </div> <!-- .wpml-section-content -->

	</div> <!-- wpml-section -->
<?php
}

