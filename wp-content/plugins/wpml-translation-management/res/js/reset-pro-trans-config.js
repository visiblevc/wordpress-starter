/*jshint browser:true, devel:true */
/*global _, jQuery, ajaxurl, wpml_reset_pro_trans_config_strings */

/** @namespace wpml_reset_pro_trans_config_strings.placeHolder */
/** @namespace wpml_reset_pro_trans_config_strings.confirmation */
/** @namespace wpml_reset_pro_trans_config_strings.reset */
/** @namespace wpml_reset_pro_trans_config_strings.action */
/** @namespace wpml_reset_pro_trans_config_strings.nonce */

var ResetProTransConfig = function () {
	"use strict";

	var self = this;

	self.init = function () {
		var checkBox = jQuery('#icl_reset_pro_check');
		var button = jQuery('#icl_reset_pro_but');
		checkBox.on('change', function () {
			if (checkBox.is(":checked")) {
				button.removeClass('button-primary-disabled');
			} else {
				button.addClass('button-primary-disabled');
			}
		});
		button.on('click', function (event) {
			var spinner;
			var canReset;
			var result = false;
			var checkBoxChecked;
			var userConfirms;
			var buttonDisabled;

			event.preventDefault();
			buttonDisabled = button.hasClass('button-primary-disabled');
			checkBoxChecked = checkBox.is(":checked");
			canReset = !buttonDisabled && checkBoxChecked;
			if (canReset) {
				userConfirms = confirm(wpml_reset_pro_trans_config_strings.confirmation);
				result = userConfirms;
			}

			if (result) {
				spinner = jQuery('#' + wpml_reset_pro_trans_config_strings.placeHolder).find('.spinner');
				button.attr('disabled', 'disabled');

				spinner.addClass('is-active');

				jQuery.ajax({
					type:     "POST",
					url:      ajaxurl,
					data:     {
						'action': wpml_reset_pro_trans_config_strings.action,
						'nonce':  wpml_reset_pro_trans_config_strings.nonce
					},
					dataType: 'json',
					success:  function (response) {
						if (response.success) {
							alert(response.data);
							document.location.reload(true);
						} else {
							alert(response.data);
						}
					},
					error:    function (jqXHR, status, error) {
						var parsedResponse = jqXHR.statusText || status || error;
						alert(parsedResponse);
					},
					complete: function () {
						button.removeAttr('disabled');
						button.next().fadeOut();
						spinner.removeClass('is-active');
					}
				});
			}
		});
	};

	jQuery(document).ready(function () {
		resetProTransConfig.init();
	});
};

var resetProTransConfig = new ResetProTransConfig();