/*globals jQuery, ajaxurl */
jQuery('document').ready(function ($) {
	"use strict";

	$('[data-js-callback="js-set-translation-editor"]').click(function () {

		var enable_button = $(this);
		var notice = enable_button.closest('.otgs-notice');
		var nonce = notice.find('input[name="wpml_set_translation_editor_nonce"]').val();
		var success = notice.find('.js-done');
		var error = notice.find('.js-error');

		$.ajax({
						 url:     ajaxurl,
						 type:    "POST",
						 data:    {
							 action: 'wpml_set_translation_editor',
							 nonce:  nonce
						 },
						 success: function (response) {
							 error.hide();
							 success.hide();
							 notice.removeClass('notice-error error notice-info info');

							 if (response.success) {
								 notice.addClass('notice-success');
								 notice.addClass('success');

								 enable_button.hide();
								 success.show();

								 setTimeout(function () {
									 notice.fadeOut('slow');
								 }, 2500);
							 } else {
								 notice.addClass('notice-error');
								 notice.addClass('error');
								 error.show();

								 if (null !== response.data) {
									 error.find('strong').text(response.data);
								 }
							 }
						 }
					 });
	});
});