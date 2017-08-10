/*global jQuery, ajaxurl, wp */
var WPML_TP_API = WPML_TP_API || {};

(function () {
	'use strict';

	WPML_TP_API.refreshLanguagePairs = function () {

		var refreshLanguagePairs = function (event) {
			event.preventDefault();

			var self = this;

			var spinner = jQuery('<span class="spinner is-active" style="float:none;"></span>');
			jQuery(self).after(spinner);
			wp.ajax.send({
										 data:     {
											 action: 'wpml-tp-refresh-language-pairs',
											 nonce:  jQuery(self).data('nonce')
										 },
										 success:  function (msg) {
											 // jQuery(self).attr('disabled', 'disabled');
											 jQuery(self).after('<p>' + msg + '</p>');
										 },
										 complete: function () {
											 spinner.remove();
										 }
									 });
		};

		jQuery('.js-refresh-language-pairs').on('click', refreshLanguagePairs);

	};

	jQuery(document).ready(function () {
		WPML_TP_API.refreshLanguagePairs();
	});

}());