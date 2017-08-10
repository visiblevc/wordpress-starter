/*jshint devel:true */
/*global jQuery */

var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.TranslationBasket = function () {
	"use strict";

	var self         = this;
	var private_data = {};

	var init = function () {
		jQuery(document).ready(function() {
			private_data.button = jQuery('#icl_send_strings');
		});
	};
	
	self.maybe_enable_button = function () {
		if ( self.is_not_active_lang_selected() ) {
			private_data.button.prop('disabled', true);
			jQuery('.js-translation-message').html(private_data.button.data('lang-not-active-message')).show();
		} else if ( self.is_more_than_on_lang_selected() ) {
			private_data.button.prop('disabled', true);
			jQuery('.js-translation-message').html(private_data.button.data('more-than-one-lang-message')).show();
		} else if ( self.basket_has_strings_in_different_lang() ) {
			private_data.button.prop('disabled', true);
			jQuery('.js-translation-message').html(private_data.button.data('translation-basket-lang-message')).show();
		} else {
			private_data.button.prop('disabled', false);
			self.clear_message();
		}
		
		return !private_data.button.prop('disabled');
	};
	
	self.basket_has_strings_in_different_lang = function () {
		var different = false;
		
		var basket_lang = jQuery('input[name="icl-basket-language"]').val();
		
		if (basket_lang) {
			var checked = jQuery('.icl_st_row_cb:checked:first');
			var lang = checked.data('language');
			different = basket_lang !== lang;
		}
		
		return different;
		
	};
	
	self.is_not_active_lang_selected = function () {
		return jQuery('.js-lang-not-active:checked').length;
	};

	self.is_more_than_on_lang_selected = function () {
		var checked = jQuery('.icl_st_row_cb:checked');
		var OK = true;
		
		if (checked.length > 1) {
			
			var langs = [];
			
			jQuery(checked).each( function () {
				var lang = jQuery(this).data('language');
				if ( langs.indexOf(lang) === -1) {
					langs.push(lang);
				}
			});
			
			if (langs.length > 1) {
				OK = false;
			}
		}
		
		return !OK;
	};
	
	self.show_target_languages = function () {
		if (!self.is_more_than_on_lang_selected()) {
			var checked = jQuery('.icl_st_row_cb:checked:first');
			var lang = checked.data('language');
			
			jQuery('#icl_tm_languages').find('input').each(function() {
				if (lang === jQuery(this).data('language')) {
					jQuery(this).parent().hide();
				} else {
					jQuery(this).parent().show();
				}
			});
			
			jQuery('input[name="icl-tr-from"]').val(lang);
			
		}
	};
	
	self.clear_message = function () {
		jQuery('.js-translation-message').html('').hide();
	};
	
	init();
	
};

WPML_String_Translation.translation_basket = new WPML_String_Translation.TranslationBasket();

