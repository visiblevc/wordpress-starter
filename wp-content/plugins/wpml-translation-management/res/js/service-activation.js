/*jshint browser:true, devel:true */
/*global _, jQuery, ajaxurl, wpml_tm_service_activation_strings */

/** @namespace wpml_tm_service_activation_strings.alertTitle */
/** @namespace wpml_tm_service_activation_strings.cancelledJobs */
/** @namespace wpml_tm_service_activation_strings.openJobs */
/** @namespace wpml_tm_service_activation_strings.keepLocalJobs */
/** @namespace wpml_tm_service_activation_strings.errorCancellingJobs */
/** @namespace wpml_tm_service_activation_strings.errorGeneric */
/** @namespace wpml_tm_service_activation_strings.confirm */
/** @namespace wpml_tm_service_activation_strings.yes */
/** @namespace wpml_tm_service_activation_strings.no */
/** @namespace data.opens */
/** @namespace data.cancelled */

var ServiceActivation = function () {
    "use strict";

    var self = this;

    self.initClickAction = function (item, ajaxAction) {
        var elementName;
        elementName = item.attr('name');
        jQuery.ajax(
          {
              url:     ajaxurl,
              data:    {
                  'action': ajaxAction,
                  'nonce':  elementName + '_nonce'
              },
              success: function (response) {
                  var callback = ajaxAction + '_success';
                  self[callback](response);
              },
              error:   function (xhr, ajaxOptions, thrownError) {
                  var callback = ajaxAction + '_error';
                  self[callback](xhr, ajaxOptions, thrownError);
              }
          }
        );
    };
    self.displayResponseDialog = function (message) {
        self.responseDialog.find('p').empty();
        self.responseDialog.find('p').append(message);

        self.responseDialog.dialog('open');
    };

    self.displayConfirmationDialog = function (callback) {
        var message = wpml_tm_service_activation_strings.confirm;
        self.confirmationDialog.find('p').empty();
        self.confirmationDialog.find('p').append(message);

        self.confirmationDialog.dialog(
          'option', 'buttons', [
              {
                  text:  wpml_tm_service_activation_strings.yes,
                  click: function () {
                      jQuery(this).dialog("close");
                      callback(true);
                  }
              }, {
                  text:  wpml_tm_service_activation_strings.no,
                  click: function () {
                      jQuery(this).dialog("close");
                      callback(false);
                  }
              }
          ]
        );

        self.confirmationDialog.dialog('open');
    };

    self.wpml_cancel_open_local_translators_jobs_error = function (xhr, ajaxOptions, thrownError) {
        var message = wpml_tm_service_activation_strings.errorCancellingJobs;
        alert(message);
        console.log(xhr);
        console.log(ajaxOptions);
        console.log(thrownError);
    };

    self.wpml_keep_open_local_translators_jobs_error = function (xhr, ajaxOptions, thrownError) {
        var message = wpml_tm_service_activation_strings.errorGeneric;
        alert(message);
        console.log(xhr);
        console.log(ajaxOptions);
        console.log(thrownError);
    };

    self.wpml_keep_open_local_translators_jobs_success = function (response) {
        var message;
        var success = response.success;
        if (success) {
            message = wpml_tm_service_activation_strings.keepLocalJobs;
        } else {
            message = wpml_tm_service_activation_strings.errorGeneric;
        }
        self.displayResponseDialog(message);
    };

    self.wpml_cancel_open_local_translators_jobs_success = function (response) {
        var data = response.data;
        var success = response.success;

        var message;
        if (success) {
            message = wpml_tm_service_activation_strings.cancelledJobs + ' ' + data.cancelled + '<br>';
            if (data.open) {
                message += wpml_tm_service_activation_strings.errorCancellingJobs + '<br>';
                message += wpml_tm_service_activation_strings.openJobs + ' ' + data.opens;
            }
        } else {
            message = wpml_tm_service_activation_strings.errorCancellingJobs;
        }
        self.displayResponseDialog(message);
    };

    self.init = function () {
        var dialogHtml;

        self.notice = jQuery('.wpml-service-activation-notice').first();
        self.actions = self.notice.find('.wpml-action');

        dialogHtml = '<div title="' + wpml_tm_service_activation_strings.alertTitle + '">';
        dialogHtml += '<p></p>';
        dialogHtml += '</div>';

        self.confirmationDialog = jQuery(dialogHtml);
        self.responseDialog = jQuery(dialogHtml);

        self.confirmationDialog.dialog(
          {
              autoOpen:      false,
              resizable:     false,
              modal:         true,
              width:         'auto',
              closeOnEscape: false,
              buttons:       [
                  {
                      text:  wpml_tm_service_activation_strings.yes,
                      click: function () {
                          jQuery(this).dialog("close");
                      }
                  }, {
                      text:  wpml_tm_service_activation_strings.no,
                      click: function () {
                          jQuery(this).dialog("close");
                      }
                  }
              ]
          }
        );

        self.responseDialog.dialog(
          {
              autoOpen:      false,
              resizable:     false,
              modal:         true,
              width:         'auto',
              closeText:     wpml_tm_service_activation_strings.closeButton,
              closeOnEscape: true,
              buttons:       [
                  {
                      text:  wpml_tm_service_activation_strings.closeButton,
                      click: function () {
                          jQuery(this).dialog("close");
                      }
                  }
              ],
              close:         function () {
                  window.location.reload(true);
              }
          }
        );

        self.initElements();
    };
    self.initClick = function (item) {
        var ajaxAction = item.data('action');
        if (ajaxAction) {
            item.on(
              'click', function (event) {
                  var callback;
                  event.preventDefault();
                  callback = function (proceed) {
                      if (true === proceed) {
                          self.initClickAction(item, ajaxAction);
                      }
                  };
                  self.displayConfirmationDialog(callback);
              }
            );
        }
    };
    self.initElements = function () {
        if (self.notice) {
            _.each(
              self.actions, function (value) {
                  var element = jQuery(value);
                  self.initClick(element);
              }
            );
        }
    };

    jQuery(document).ready(
      function () {
          serviceActivation.init();
      }
    );
};

var serviceActivation = new ServiceActivation();