/*globals iclSaveForm*/
jQuery(document).ready(function () {
	jQuery('#icl_page_sync_options').submit(iclSaveForm);
	jQuery('form[name="icl_custom_tax_sync_options"]').submit(iclSaveForm);
	jQuery('form[name="icl_custom_posts_sync_options"]').submit(iclSaveForm);
});