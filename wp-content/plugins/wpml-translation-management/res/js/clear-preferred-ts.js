/*jshint browser:true, devel:true */
/*global _, jQuery, ajaxurl, wpml_clear_ts_strings */

/** @namespace wpml_clear_ts_strings.placeHolder */
/** @namespace wpml_clear_ts_strings.action */
/** @namespace wpml_clear_ts_strings.nonce */

var ClearPreferredTS = function () {
	"use strict";

	var self = this;

	self.init = function () {
		var box = jQuery('#' + wpml_clear_ts_strings.placeHolder);
		var button = box.find('.button-primary');
		var spinner = box.find('.spinner');

		button.on('click', function (e) {
			e.preventDefault();

			spinner.addClass('is-active');

			jQuery.ajax({
				type:     "POST",
				url:      ajaxurl,
				data:     {
					'action': wpml_clear_ts_strings.action,
					'nonce':  wpml_clear_ts_strings.nonce
				},
				dataType: 'json',
				success:  function (response) {
					if (response.success) {
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
					spinner.removeClass('is-active');
				}
			});
		});
	};

	jQuery(document).ready(function () {
		clearPreferredTS.init();
	});
};

var clearPreferredTS = new ClearPreferredTS();