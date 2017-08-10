/*globals wpml_user_language_data, jQuery*/
jQuery( document ).ready(function() {
	"use strict";

	var selector = jQuery( '#wpml-user-language-switcher-form').find('select' );
	selector.change(function() {
		var data;
		selector.prop( 'disabled', true );
		data = {
			'action': 'wpml_user_language_switcher_form_ajax',
			'mail': wpml_user_language_data.mail,
			'language': selector.val(),
			'nonce': wpml_user_language_data.nonce
		};

		/** @namespace wpml_user_language_data.ajax_url */
		/**
		 * @namespace wpml_user_language_data.auto_refresh_page
		 * @type int
		 * */
		jQuery.post(wpml_user_language_data.ajax_url, data, function() {
			selector.prop( 'disabled', false );
			selector.css( 'color', 'green' );
            if (1 === parseInt(wpml_user_language_data.auto_refresh_page)) {
				location.reload();
			}
		});

	});

});
