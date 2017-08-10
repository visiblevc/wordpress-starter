var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.CustomPostUI = function () {
	var self         = this;
	var private_data = {};

	var init = function () {
		jQuery(document).ready(function() {
			jQuery('.js-translate-slug-original').on('change', change_original_lang);
		});
	}
	
	var change_original_lang = function () {
		var new_lang = jQuery(this).val();
		var slug = jQuery(this).data('slug');
		
		jQuery(this).closest('.js-cpt-slugs').find('input').each( function() {
			var input_lang = jQuery(this).data('lang');
			if (input_lang == new_lang) {
				jQuery(this).closest('tr').hide();
			} else {
				jQuery(this).closest('tr').show();
			}
		})
	}
	
	
	init();
	
};

WPML_String_Translation.custom_post_ui = new WPML_String_Translation.CustomPostUI();

