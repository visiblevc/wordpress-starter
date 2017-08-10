var icl_language_pairs_updated = false;

addLoadEvent(function () {
    var iclLangPairTrForm = jQuery('.icl_language_pairs .icl_tr_from');
    iclLangPairTrForm.change(toggleTranslationPairsSub);
    iclLangPairTrForm.change(iclShowNextButtonStep1);
    jQuery('.icl_tr_to').change(iclShowNextButtonStep1);
    var iclMoreOptions = jQuery('form[name="icl_more_options"]');
    iclMoreOptions.submit(iclSaveForm);
    iclMoreOptions.submit(iclSaveMoreOptions);
    jQuery('form[name="icl_editor_account"]').submit(iclSaveForm);
    jQuery('#icl_enable_content_translation,#icl_disable_content_translation').click(iclToggleContentTranslation);
    jQuery('a[href="#icl-ct-advanced-options"]').click(iclToggleAdvancedOptions);
    jQuery('a[href="#icl-show_disabled_langs"]').click(iclToggleMoreLanguages);
    jQuery('input[name="icl_content_trans_setup_cancel"]').click(iclWizardCancel);

    jQuery('.handlediv').click(function () {
        if (jQuery(this).parent().hasClass('closed')) {
            jQuery(this).parent().removeClass('closed');
        } else {
            jQuery(this).parent().addClass('closed');
        }
    });

    if (jQuery('input[name="icl_content_trans_setup_next_1"]').length > 0) {
        iclShowNextButtonStep1();
    }

    jQuery('#icl_save_language_pairs').click(function () {
        icl_language_pairs_updated = true
    });
    jQuery('.icl_cost_estimate_toggle').click(function () {
        jQuery('#icl_cost_estimate').slideToggle()
    });
    jQuery('.icl_account_setup_toggle').click(icl_toggle_account_setup);

    if (location.href.indexOf("show_config=1") != -1) {
        icl_toggle_account_setup();
        location.href = location.href.replace("&show_config=1", "");
        location.href = location.href.replace("?show_config=1&", "&");
        location.href = location.href.replace("?show_config=1", "");
        location.href = location.href + '#icl_account_setup';
    }
});

function icl_toggle_account_setup() {
    var iclAcctStats = jQuery('#icl_languages_translators_stats');
    if (iclAcctStats.is(':visible')) {
        iclAcctStats.slideUp();
    } else {
        if (icl_language_pairs_updated) {
            iclAcctStats.html('<div align="left" style="margin-bottom:5px;">' + icl_ajxloaderimg + "</div>").fadeIn();
            location.href = location.href.replace(/#(.*)$/g, '');
        } else {
            iclAcctStats.slideDown();
        }
    }
    jQuery('#icl_account_setup').slideToggle();
    jQuery('.icl_account_setup_toggle_main').toggle();
    return false;
}

function iclSaveMoreOptions() {
    jQuery('input[name="icl_translator_choice"]:checked').each(function () {
        jQuery('#icl_own_translators_message').css("display", ( this.value == '1' ? "" : "none" ));
    });
}

function iclWizardCancel() {
    if (!confirm(jQuery('#icl_toggle_ct_confirm_message').html())) {
        return false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&new_val=0",
        success: function (msg) {
        }
    });

}

function iclShowNextButtonStep1() {
    // See if we have a language pair selected and enable the button if we have.
    var found = false;

    jQuery('.icl_tr_from:checked').each(function () {
        var from = this.id.substring(13);
        jQuery('.icl_tr_to:checked').each(function () {
            if (this.id.substr(13, 2) == from) {
                found = true;
            }
        })
    });

    if (found) {
        jQuery('input[name="icl_content_trans_setup_next_1"]').removeAttr("disabled");
    } else {
        jQuery('input[name="icl_content_trans_setup_next_1"]').attr("disabled", "disabled");
    }
}

function toggleTranslationPairsSub() {
    var code = jQuery(this).attr('name').split('_').pop();
    if (jQuery(this).attr('checked')) {
        jQuery('#icl_tr_pair_sub_' + code).slideDown();
    } else {
        jQuery('#icl_tr_pair_sub_' + code).css("display", "none");
    }
}

function iclToggleContentTranslation() {
    var val = jQuery(this).attr('id') == 'icl_enable_content_translation' ? 1 : 0;
    if (!val && !confirm(jQuery('#icl_toggle_ct_confirm_message').html())) {
        return false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&new_val=" + val,
        success: function (msg) {
            location.href = location.href.replace(/#.*/, '');
        }
    });
}

function iclToggleAdvancedOptions() {
    var self = jQuery(this);
    var iclContentAdv = jQuery('#icl-content-translation-advanced-options');
    if (iclContentAdv.css('display') === 'none') {
        iclContentAdv.fadeIn('fast', function () {
            self.children().toggle();
        });
    } else {
        iclContentAdv.fadeOut('fast', function () {
            self.children().toggle();
        });
    }
}

function iclToggleMoreLanguages() {
    var self = jQuery(this);
    var iclLangsDisabled = jQuery('#icl_languages_disabled');
    if (iclLangsDisabled.css('display') === 'none') {
        iclLangsDisabled.fadeIn('fast', function () {
            self.children().toggle();
        });
    } else {
        iclLangsDisabled.css('display', 'none');
        self.children().toggle();
    }
}

