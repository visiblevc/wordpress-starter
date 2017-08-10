/*jshint browser:true, devel:true */
/*globals jQuery, ajaxurl, tm_ts_data*/
var WPMLTranslationServicesDialog = function () {
	"use strict";

	var self = this;

	self.preventEventDefault = function (event) {
		if ('undefined' !== event && 'undefined' !== typeof(event.preventDefault)) {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}
	};

	self.enterKey = 13;
	self.ajaxSpinner = jQuery('<span class="spinner"></span>');

	self.init = function () {
		/** @namespace tm_ts_data.nonce.translation_service_authentication */
		/** @namespace tm_ts_data.nonce.translation_service_toggle */

		var invalidateServiceLink;
		var authenticateServiceLink;
		var deactivateServiceLink;
		var activateServiceLink;
		var activateServiceImage;
		var flushWebsiteDetailsCacheLink;

		var header = tm_ts_data.strings.header;
		var tip = tm_ts_data.strings.tip;
		self.serviceDialog = jQuery('<div id="service_dialog"><h4>' + header + '</h4><div class="custom_fields_wrapper"></div><i>' + tip + '</i></div>');
		self.customFieldsSerialized = jQuery('#custom_fields_serialized');

		self.ajaxSpinner.addClass('is-active');

		activateServiceImage = jQuery('.js-activate-service');
		activateServiceLink = jQuery('.js-activate-service-id');
		deactivateServiceLink = jQuery('.js-deactivate-service');
		authenticateServiceLink = jQuery('.js-authenticate-service');
		invalidateServiceLink = jQuery('.js-invalidate-service');
		flushWebsiteDetailsCacheLink = jQuery('.js-flush-website-details-cache');

		activateServiceImage.bind('click', function (event) {
			var link;
			self.preventEventDefault(event);

			link = jQuery(this).closest('li').find('.js-activate-service-id');
			link.trigger('click');
			return false;
		});

		activateServiceLink.bind('click', function (event) {
			var serviceId;
			var button;
			self.preventEventDefault(event);

			button = jQuery(this);
			serviceId = jQuery(this).data('id');
			self.toggleService(serviceId, button, 1);

			return false;
		});

		deactivateServiceLink.bind('click', function (event) {
			var serviceId;
			var button;
			self.preventEventDefault(event);

			button = jQuery(this);
			serviceId = jQuery(this).data('id');
			self.toggleService(serviceId, button, 0);

			return false;
		});

		invalidateServiceLink.bind('click', function (event) {
			var serviceId;
			var button;
			self.preventEventDefault(event);

			button = jQuery(this);
			serviceId = jQuery(this).data('id');
			self.translationServiceAuthentication(serviceId, button, 1);

			return false;
		});

		flushWebsiteDetailsCacheLink.on('click', function (event) {
			var anchor = jQuery(this);
			self.preventEventDefault(event);

			self.flushWebsiteDetailsCache(anchor);

			return false;
		});

		authenticateServiceLink.bind('click', function (event) {
			var customFields;
			var serviceId;
			self.preventEventDefault(event);

			serviceId = jQuery(this).data('id');
			customFields = jQuery(this).data('custom-fields');

			self.serviceAuthenticationDialog(customFields, serviceId);

			return false;
		});
	};

	self.toggleService = function (serviceId, button, enableService) {
		var ajaxData;
		var id;
		var enable = enableService;
		if ('undefined' === typeof enableService) {
			enable = 0;
		}

		button.attr('disabled', 'disabled');
		button.after(self.ajaxSpinner);

		id = button.data('id');

		ajaxData = {
			'action':     'translation_service_toggle',
			'nonce':      tm_ts_data.nonce.translation_service_toggle,
			'service_id': serviceId,
			'enable':     enable
		};

		jQuery.ajax({
			type:     "POST",
			url:      ajaxurl,
			data:     ajaxData,
			dataType: 'json',
			success:  function (msg) {
				if ('undefined' !== msg.message && '' !== msg.message.trim()) {
					alert(msg.message);
				}
				if (msg.reload) {
					location.reload(true);
				} else {
					if (button) {
						button.removeAttr('disabled');
						button.next().fadeOut();
					}
				}
			},
			error:    function (jqXHR, status, error) {
				var parsedResponse = jqXHR.statusText || status || error;
				alert(parsedResponse);
			}
		});
	};

	self.serviceAuthenticationDialog = function (customFields, serviceId) {
		self.serviceDialog.dialog({
			dialogClass: 'wpml-dialog otgs-ui-dialog',
			width:       'auto',
			title:       "Translation Services",
			modal:       true,
			open:        function () {

				var customFieldsList;
				var customFieldsForm;
				var customFieldsWrapper = self.serviceDialog.find('.custom_fields_wrapper');
				var firstInput = false;

				customFieldsWrapper.empty();

				customFieldsForm = jQuery('<div></div>');
				customFieldsForm.appendTo(customFieldsWrapper);

				customFieldsList = jQuery('<ul></ul>');
				customFieldsList.appendTo(customFieldsForm);

				jQuery.each(customFields.custom_fields, function (i, item) {
					var itemLabel, itemInput;
					var itemId;
					var customFieldsListItem = jQuery('<li></li>');
					customFieldsListItem.appendTo(customFieldsList);

					itemId = 'custom_field_' + item.name;
					if ('hidden' !== item.type) {
						itemLabel = jQuery('<label for="' + itemId + '">' + item.label + ':</label>');
						itemLabel.appendTo(customFieldsListItem);
						itemLabel.append('&nbsp;');
					}
					switch (item.type) {
						case 'text':
							itemInput = jQuery('<input type="text" id="' + itemId + '" class="custom_fields" name="' + item.name + '" />');
							break;
						case 'checkbox':
							itemInput = jQuery('<input type="checkbox" id="' + itemId + '" class="custom_fields" name="' + item.name + '" />');
							break;
						default:
							itemInput = jQuery('<input type="hidden" id="' + itemId + '" class="custom_fields" name="' + item.name + '" />');
							break;
					}
					itemInput.appendTo(customFieldsListItem);
					if (!firstInput) {
						itemInput.focus();
					}
				});

				jQuery(':input', this).keyup(function (event) {
					if (self.enterKey === event.keyCode) {
						jQuery(this).closest('.ui-dialog').find('.ui-dialog-buttonpane').find('button:first').click();
					}
				});

			},
			buttons:     [
				{
					text:    "Submit",
					click:   function () {
						var customFieldsDataStringify;
						var customFieldsData;
						var customFieldsInput;
						self.hideButtons();

						customFieldsInput = jQuery('.custom_fields');
						customFieldsData = {};
						jQuery.each(customFieldsInput, function (i, item) {
							customFieldsData[jQuery(item).attr('name')] = jQuery(item).val();
						});
						customFieldsDataStringify = JSON.stringify(customFieldsData, null, ' ');
						self.customFieldsSerialized.val(customFieldsDataStringify);
						self.translationServiceAuthentication(serviceId, false, 0, null, self.showButtons);
					},
					'class': 'button-primary'
				}, {
					text:    "Cancel",
					click:   function () {
						jQuery(this).dialog("close");
					},
					'class': 'button-secondary'
				}
			]
		});
	};

	self.hideButtons = function () {
		self.ajaxSpinner.appendTo(self.serviceDialog);
		self.serviceDialog.parent().find('.ui-dialog-buttonpane').fadeOut();
	};

	self.showButtons = function () {
		self.serviceDialog.find(self.ajaxSpinner).remove();
		self.serviceDialog.parent().find('.ui-dialog-buttonpane').fadeIn();
	};

	self.translationServiceAuthentication = function (serviceId, button, invalidateService) {
		var ajaxData;
		var invalidate;
		invalidate = invalidateService;
		if ('undefined' === typeof invalidateService) {
			invalidate = 0;
		}

		if (isNaN(serviceId)) {
			alert('service_id isNAN');
		} else if (isNaN(invalidate)) {
			alert('invalidate isNAN');
		}

		if (button) {
			button.attr('disabled', 'disabled');
			button.after(self.ajaxSpinner);
		}

		jQuery.ajax({
			type:     "POST",
			url:      ajaxurl,
			data:     {
				'action':        'translation_service_authentication',
				'nonce':         tm_ts_data.nonce.translation_service_authentication,
				'service_id':    serviceId,
				'invalidate':    invalidate,
				'custom_fields': self.customFieldsSerialized.val()
			},
			dataType: 'json',
			success: function (msg) {
				if (msg.success) {
					msg = msg.data;
					if ('undefined' !== msg.message && '' !== msg.message.trim()) {
						alert(msg.message);
					}
					if (msg.reload) {
						location.reload(true);
					} else {
						if (button) {
							button.removeAttr('disabled');
							button.next().fadeOut();
						}
					}
				}
			},
			error:    function (jqXHR, status, error) {
				var parsedResponse = jqXHR.statusText || status || error;
				alert(parsedResponse);
			},
			complete: function() {
				self.showButtons();
			}
		});
	};

	self.flushWebsiteDetailsCache = function (anchor) {
		var nonce = anchor.data('nonce');

		self.ajaxSpinner.appendTo(anchor);
		self.ajaxSpinner.addClass('is-activve');

		if (nonce) {
			jQuery.ajax({
										type:     "POST",
										url:      ajaxurl,
										data:     {
											'action': 'wpml-flush-website-details-cache',
											'nonce':  nonce
										},
										dataType: 'json',
										success:  function (response) {
											self.ajaxSpinner.removeClass('is-activve');
											if (response.success) {
												/** @namespace response.redirectTo */
												location.reload(response.data.redirectTo);
											}
										}
									});
		}
	};
};

jQuery(document).ready(function () {
	"use strict";

	var wpmlTranslationServicesDialog = new WPMLTranslationServicesDialog();
	wpmlTranslationServicesDialog.init();

});

