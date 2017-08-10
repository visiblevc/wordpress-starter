/**
 *
 * @constructor
 */
function WpmlTpPollingPickupPopulateAction(jQuery, TranslationProxyPolling) {
    /*
     * Before doing anything here, check whether the box, to write
     * data about translations ready for pickup , even exists.
     */
    var tmPickupBox = jQuery('#icl_tm_pickup_wrap');
    var icl_tm_pickup_wrap_button = jQuery("#icl_tm_get_translations");
    var pickup_nof_jobs = jQuery("#icl_pickup_nof_jobs");
    var pickup_last_pickup = jQuery("#icl_pickup_last_pickup");
    var nonce = jQuery("#_icl_nonce_populate_t").val();

    return {
        run: function () {
            if (tmPickupBox.length === 0) {
                return;
            }
            icl_tm_pickup_wrap_button.val('...Fetching translation job data ...');
            icl_tm_pickup_wrap_button.attr('disabled', 'disabled');
            jQuery.ajax(
                {
                    type: "POST",
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'icl_populate_translations_pickup_box',
                        _icl_nonce: nonce
                    },
                    success: function (response) {
                        /** @namespace response.data.wait_text */
                        /** @namespace response.data.polling_data */
                        /** @namespace response.data.jobs_in_progress_text */
                        /** @namespace response.data.last_pickup_text */
                        if (!response.data.wait_text) {
                            icl_tm_pickup_wrap_button.removeAttr('disabled');
                            icl_tm_pickup_wrap_button.val(response.data.button_text);
                            pickup_nof_jobs.text(response.data.jobs_in_progress_text);
                            pickup_last_pickup.text(response.data.last_pickup_text);
                            jQuery('#tp_polling_job').text(JSON.stringify(response.data.polling_data));
                            TranslationProxyPolling.init(icl_tm_pickup_wrap_button, icl_ajxloaderimg);
                        } else {
                            pickup_nof_jobs.html(response.data.wait_text);
                            icl_tm_pickup_wrap_button.hide();
                        }
                    },
                    error: function (response) {
                        if (response.data && response.data.error) {
                            jQuery("#icl_pickup_nof_jobs").text(response.data.error);
                        }
                        icl_tm_pickup_wrap_button.hide();
                    }
                }
            );
        }
    };
}