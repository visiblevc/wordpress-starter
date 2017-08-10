/*global jQuery, ajaxurl, JSON */
"use strict";

var TranslationProxyPolling = {
    init: function (button, icl_ajxloaderimg) {
        var self = this;
        var JSONtext = jQuery('#tp_polling_job').text();
        self.jobs = JSONtext ? JSON.parse(JSONtext) : [];
        jQuery(button).click(self.handleOne);
        self.button = jQuery(button);
        self.icl_ajxloaderimg = jQuery(icl_ajxloaderimg);
        self.icl_ajxloaderimg.hide();
        self.button.after(TranslationProxyPolling.icl_ajxloaderimg);
    },
    jobs: [],
    completed_count: 0,
    cancel_count: 0,
    error_data: [],
    showSpinner: function () {
        if (!TranslationProxyPolling.spinner) {
            TranslationProxyPolling.button.attr('disabled', 'disabled');
            TranslationProxyPolling.icl_ajxloaderimg.show();
            TranslationProxyPolling.spinner = true;
        }
    },
    hideSpinner: function () {
        TranslationProxyPolling.button.removeAttr('disabled');
        TranslationProxyPolling.icl_ajxloaderimg.hide();
        TranslationProxyPolling.spinner = false;
    },
    handleOne: function () {
        var nonce = jQuery("#_icl_nonce_pickup_t").val();
        var currentJob = TranslationProxyPolling.jobs.length > 0 ? TranslationProxyPolling.jobs.pop() : false;
        if (currentJob) {
            TranslationProxyPolling.showSpinner();
            jQuery.ajax(
                {
                    type: "POST",
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'icl_pickup_translations',
                        _icl_nonce: nonce,
                        job_polled: currentJob,
                        completed_jobs: TranslationProxyPolling.completed_count,
                        cancelled_jobs: TranslationProxyPolling.cancel_count
                    },
                    success: function (response) {
                        if (currentJob && !response.data.job_error) {
                            TranslationProxyPolling.updateCounts(currentJob, response);
                        }
                        var icl_message_div;
                        /** @namespace response.data.completed */
                        if (response.data.completed) {
													icl_message_div = jQuery("#icl_tm_pickup_wrap_completed");
                            icl_message_div.text(response.data.completed);
                            icl_message_div.show();
                        }
                        /** @namespace response.data.cancelled */
                        if (response.data.cancelled) {
                            icl_message_div = jQuery("#icl_tm_pickup_wrap_cancelled");
                            icl_message_div.text(response.data.cancelled);
                            icl_message_div.show();
                        }
                        /** @namespace response.data.submitting */
                        if (response.data.submitting) {
                            icl_message_div = jQuery("#icl_tm_pickup_wrap_submitting");
                            icl_message_div.text(response.data.submitting);
                            icl_message_div.show();
                        }
                        if (TranslationProxyPolling.jobs.length > 0) {
                            TranslationProxyPolling.handleOne();
                        } else {
													TranslationProxyPolling.hideSpinner();
													TranslationProxyPolling.button.attr('disabled', 'disabled');
													TranslationProxyPolling.startReloading(10);
													jQuery.ajax({
																				type:     "POST",
																				url:      ajaxurl,
																				dataType: 'json',
																				data:     {
																					action: 'icl_pickup_translations_complete'
																				}
																			});
												}
                    },
                    error: function () {
                        TranslationProxyPolling.hideSpinner();
                    }
                }
            );
        } else {
            TranslationProxyPolling.hideSpinner();
        }
    },
    updateCounts: function (currentJob, response) {
        if (currentJob.job_state === 'translation_ready' && response.data.completed) {
            TranslationProxyPolling.completed_count += 1;
        }
        if (currentJob.job_state === 'cancelled' && response.data.cancelled) {
            TranslationProxyPolling.completed_count += 1;
        }
    },
    startReloading: function (remaining) {
        if (0 >= remaining) {
            location.reload(true);
        } else {
            TranslationProxyPolling.updatePickupButtonTo('reloading', remaining);
            setTimeout(function () {
                TranslationProxyPolling.startReloading(remaining - 1);
            }, 1000);
        }
    },
    updatePickupButtonTo: function (state, value) {
        var dataAttributePrefix, stateData, sanitizedValue;

        dataAttributePrefix = state;
        if ('undefined' === typeof state) {
            dataAttributePrefix = 'default';
        }

        sanitizedValue = value;
        if ('undefined' === typeof value) {
            sanitizedValue = '';
        }

        stateData = TranslationProxyPolling.button.data(dataAttributePrefix + '-text');

        if ('reloading' === dataAttributePrefix) {
            stateData = TranslationProxyPolling.button.data('reloading-text') + ' ' + sanitizedValue;
        }

        if ('undefined' !== typeof stateData) {
            if (stateData) {
                TranslationProxyPolling.button.data('current-text', stateData);
                TranslationProxyPolling.button.val(stateData);
            } else {
                self.updatePickupButtonTo('default');
            }
        }
    }
};
