<?php
global $sitepress, $sitepress_settings;
?>
<div class="wpml-section" id="ml-content-setup-sec-2">

	<div class="wpml-section-header">
		<h3><?php esc_html_e( 'Posts and pages synchronization', 'sitepress' ); ?></h3>
	</div>

	<div class="wpml-section-content">

		<form id="icl_page_sync_options" name="icl_page_sync_options" action="">
			<?php wp_nonce_field( 'icl_page_sync_options_nonce', '_icl_nonce' ); ?>

			<div class="wpml-section-content-inner">
				<p>
					<label><input type="checkbox" id="icl_sync_page_ordering" name="icl_sync_page_ordering" <?php checked( $sitepress_settings['sync_page_ordering'] ) ?> value="1" />
					<?php esc_html_e( 'Synchronize page order for translations', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" id="icl_sync_page_parent" name="icl_sync_page_parent" <?php checked( $sitepress_settings['sync_page_parent'] ) ?> value="1" />
					<?php esc_html_e( 'Set page parent for translation according to page parent of the original language', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_page_template" <?php checked( $sitepress_settings['sync_page_template'] ) ?> value="1" />
					<?php esc_html_e( 'Synchronize page template', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_comment_status" <?php checked( $sitepress_settings['sync_comment_status'] ) ?> value="1" />
					<?php esc_html_e( 'Synchronize comment status', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_ping_status" <?php checked( $sitepress_settings['sync_ping_status'] ) ?> value="1" />
					<?php esc_html_e( 'Synchronize ping status', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_sticky_flag" <?php checked( $sitepress_settings['sync_sticky_flag'] ) ?> value="1" />
					<?php esc_html_e( 'Synchronize sticky flag', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_password" <?php checked( $sitepress_settings['sync_password'] ) ?> value="1" />
					<?php esc_html_e( 'Synchronize password for password protected posts', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_private_flag" <?php checked( $sitepress_settings['sync_private_flag'] ) ?> value="1" />
					<?php esc_html_e( 'Synchronize private flag', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_post_format" <?php checked( $sitepress_settings['sync_post_format'] ) ?> value="1" />
					<?php esc_html_e( 'Synchronize posts format', 'sitepress' ) ?></label>
				</p>
			</div>

			<div class="wpml-section-content-inner">
				<p>
					<label><input type="checkbox" name="icl_sync_delete" <?php checked( $sitepress_settings['sync_delete'] ) ?> value="1" />
					<?php esc_html_e( 'When deleting a post, delete translations as well', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_delete_tax" <?php checked( $sitepress_settings['sync_delete_tax'] ) ?> value="1" />
					<?php esc_html_e( 'When deleting a taxonomy (category, tag or custom), delete translations as well', 'sitepress' ) ?></label>
				</p>
			</div>

			<div class="wpml-section-content-inner">
				<p>
					<label><input type="checkbox" name="icl_sync_post_taxonomies" <?php checked( $sitepress_settings['sync_post_taxonomies'] ) ?> value="1" />
					<?php esc_html_e( 'Copy taxonomy to translations', 'sitepress' ) ?></label>
				</p>
				<p>
					<label><input type="checkbox" name="icl_sync_post_date" <?php checked( $sitepress_settings['sync_post_date'] ) ?> value="1" />
					<?php esc_html_e( 'Copy publishing date to translations', 'sitepress' ) ?></label>
				</p>
			</div>

			<?php if ( defined( 'WPML_TM_VERSION' ) ): ?>
			<div class="wpml-section-content-inner">
				<p>
					<label><input type="checkbox" name="icl_sync_comments_on_duplicates" <?php checked( $sitepress->get_setting( 'sync_comments_on_duplicates' ) ) ?> value="1" />
					<?php esc_html_e( 'Synchronize comments on duplicate content', 'sitepress' ) ?></label>
				</p>
			</div>
			<?php endif; ?>

			<div class="wpml-section-content-inner">
				<p class="buttons-wrap">
					<span class="icl_ajx_response" id="icl_ajx_response_mo"></span>
					<input class="button button-primary" name="save" value="<?php esc_attr_e( 'Save', 'sitepress' ) ?>" type="submit" />
				</p>
			</div>

		</form>

	</div> <!-- wpml-section-content -->

</div> <!-- .wpml-section -->