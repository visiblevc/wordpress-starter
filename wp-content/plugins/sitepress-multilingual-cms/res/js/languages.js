/*jslint browser: true, nomen: true, laxbreak: true*/
/*global ajaxurl, iclSaveForm, iclSaveForm_success_cb, jQuery, alert, confirm, icl_ajx_url, icl_ajx_saved, icl_ajxloaderimg, icl_default_mark, icl_ajx_error, fadeInAjxResp */

(function () {
	"use strict";

jQuery(document).ready(function(){
    var icl_hide_languages;

    jQuery('.toggle:checkbox').click(iclHandleToggle);
    jQuery('#icl_change_default_button').click(editingDefaultLanguage);
    jQuery('#icl_save_default_button').click(saveDefaultLanguage);
    jQuery('#icl_cancel_default_button').click(doneEditingDefaultLanguage);
    jQuery('#icl_add_remove_button').click(showLanguagePicker);
    jQuery('#icl_cancel_language_selection').click(hideLanguagePicker);
    jQuery('#icl_save_language_selection').click(saveLanguageSelection);
    jQuery('#icl_enabled_languages').find('input').attr('disabled', 'disabled');
    jQuery('#icl_save_language_negotiation_type').submit(iclSaveLanguageNegotiationType);
    jQuery('#icl_admin_language_options').submit(iclSaveForm);
    jQuery('#icl_lang_more_options').submit(iclSaveForm);
    jQuery('#icl_blog_posts').submit(iclSaveForm);
    icl_hide_languages = jQuery('#icl_hide_languages');
    icl_hide_languages.submit(iclHideLanguagesCallback);
    icl_hide_languages.submit(iclSaveForm);
    jQuery('#icl_adjust_ids').submit(iclSaveForm);
    jQuery('#icl_automatic_redirect').submit(iclSaveForm);
    jQuery('#icl_automatic_redirect input[name="icl_automatic_redirect"]').on('click', function() {
        var $redirect_warn = jQuery(this).parents('#icl_automatic_redirect').find('.js-redirect-warning');
        if (0 != jQuery(this).val()) {
            $redirect_warn.fadeIn();
        } else {
            $redirect_warn.fadeOut();
        }
    });
    jQuery('input[name="icl_language_negotiation_type"]').change(iclLntDomains);
    jQuery('#icl_use_directory').change(iclUseDirectoryToggle);

    jQuery('input[name="show_on_root"]').change(iclToggleShowOnRoot);
    jQuery('#wpml_show_page_on_root_details').find('a').click(function () {
        if (!jQuery('#wpml_show_on_root_page').hasClass('active')) {
            alert(jQuery('#wpml_show_page_on_root_x').html());
            return false;
        }
    });

    jQuery('#icl_seo_options').submit(iclSaveForm);
	jQuery('#icl_seo_head_langs').on('click', update_seo_head_langs_priority);
    jQuery('#icl_setup_back_1').click({step: "1"}, iclSetupStep);
    jQuery('#icl_setup_back_2').click({step: "2"}, iclSetupStep);

    function iclSetupStep(event) {
        var step = event.data.step;
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=setup_got_to_step" + step + "&_icl_nonce=" + jQuery('#_icl_nonce_gts' + step).val(),
            success: function () {
                location.href = location.href.replace(/#[\w\W]*/, '');
            }
        });

        return false;
    }

    jQuery('#icl_setup_next_1').click(saveLanguageSelection);

    jQuery('#icl_avail_languages_picker').find('li input:checkbox').click(function () {
        if (jQuery('#icl_avail_languages_picker').find('li input:checkbox:checked').length > 1) {
            jQuery('#icl_setup_next_1').removeAttr('disabled');
        } else {
            jQuery('#icl_setup_next_1').attr('disabled', 'disabled');
        }
    });

    jQuery('#icl_promote_form').submit(iclSaveForm);

    jQuery('#icl_reset_languages').click(icl_reset_languages);

    jQuery(':radio[name=icl_translation_option]').change(function () {
        jQuery('#icl_enable_content_translation').removeAttr('disabled');
    });
    jQuery('#icl_enable_content_translation, .icl_noenable_content_translation').click(iclEnableContentTranslation);

    jQuery(document).on('submit', '#installer_registration_form', installer_registration_form_submit);
    jQuery(document).on('click', '#installer_registration_form :submit', function(){
        jQuery('#installer_registration_form').find('input[name=button_action]').val(jQuery(this).attr('name'));
    });

    manageWizardButtonStatesSpinner();

	jQuery(document).on('click', '#sso_information', function (e) {
		e.preventDefault();
		jQuery('#language_per_domain_sso_description').dialog({
			modal: true,
			width: 'auto',
			height: 'auto'
		});
	});
	if ( jQuery('#icl_setup_wizard_wrap').length) {
		manageWizardHeader();
	}
});

function manageWizardHeader() {
	var wizardHeader = jQuery('#icl_setup_wizard_wrap');
	var wizardHeaderInner = jQuery('.wpml-section-wizard-steps-inner');
	var wizardHeaderInnerTop = wizardHeader.offset().top;
	var adminbarHeight = jQuery('#wpadminbar').height();

	jQuery(wizardHeader).css('height', wizardHeaderInner.outerHeight() );

	jQuery(window).scroll(function() {

		if (jQuery(window).scrollTop() >= wizardHeaderInnerTop - adminbarHeight) {
			//Numeber is .wrap top margin
			wizardHeaderInner.addClass('fixed').css('top', jQuery(window).scrollTop() - 10 );
		} else {
			wizardHeaderInner.removeClass('fixed').css('top', 0);
		}
	});
}

function manageWizardButtonStatesSpinner(){
    var buttons = jQuery( '#icl_setup_back_1, #icl_setup_next_1, #icl_setup_back_2' );
    var submit_buttons = jQuery( '#icl_initial_language .buttons-wrap .button-primary, #icl_setup_back_2, #icl_setup_nav_3 .button-primary, #installer_registration_form div .button-primary' );
    var forms = jQuery( '#icl_initial_language, #wpml-ls-settings-form, #installer_registration_form' );
    var spinner = jQuery( '<span class="spinner"></span>' );
    var spinner_location = '#icl_initial_language .buttons-wrap input, #icl_setup_back_1, #icl_setup_back_2, #icl_save_language_switcher_options, #installer_registration_form div .button-primary';

    spinner.insertBefore( spinner_location );

    jQuery( forms ).submit(function(){
        spinner.addClass( 'is-active' );
        jQuery( submit_buttons ).attr( 'disabled', 'disabled' );
    });

    jQuery( buttons ).click(function(){
        spinner.addClass( 'is-active' );
        buttons.attr( 'disabled', 'disabled');
    });
}

function iclHandleToggle() {
    /* jshint validthis: true */
    var self = this;
    var toggleElement = jQuery(self);
    var toggle_value_name = toggleElement.data('toggle_value_name');
    var toggle_value_checked = toggleElement.data('toggle_checked_value');
    var toggle_value_unchecked = toggleElement.data('toggle_unchecked_value');
    var toggle_value = jQuery('[name="' + toggle_value_name + '"]');
    if (toggle_value.length === 0) {
        toggle_value = jQuery('<input type="hidden" name="' + toggle_value_name + '">');
        toggle_value.insertAfter(self);
    }
    if (toggleElement.is(':checked')) {
        toggle_value.val(toggle_value_checked);
    } else {
        toggle_value.val(toggle_value_unchecked);
    }
}

function editingDefaultLanguage() {
    jQuery('#icl_change_default_button').hide();
    jQuery('#icl_save_default_button').show();
    jQuery('#icl_cancel_default_button').show();
    var enabled_languages = jQuery('#icl_enabled_languages').find('input');
    enabled_languages.show();
    enabled_languages.prop('disabled', false);
    jQuery('#icl_add_remove_button').hide();

}
function doneEditingDefaultLanguage() {
    jQuery('#icl_change_default_button').show();
    jQuery('#icl_save_default_button').hide();
    jQuery('#icl_cancel_default_button').hide();
    var enabled_languages = jQuery('#icl_enabled_languages').find('input');
    enabled_languages.hide();
    enabled_languages.prop('disabled', true);
    jQuery('#icl_add_remove_button').show();
}

function saveDefaultLanguage() {
    var enabled_languages, arr, def_lang;
    enabled_languages = jQuery('#icl_enabled_languages');
    arr = enabled_languages.find('input[type="radio"]');
    def_lang = '';
    jQuery.each(arr, function () {
        if (this.checked) {
            def_lang = this.value;
        }
    });
	jQuery.ajax({
								type:    "POST",
								url:     ajaxurl,
								data:    {
									'action':   'wpml_set_default_language',
									'nonce':    jQuery('#set_default_language_nonce').val(),
									'language': def_lang
								},
								success: function (response) {
									if (response.success) {
										var enabled_languages_items, spl, selected_language, avail_languages_picker, selected_language_item;
										selected_language = enabled_languages.find('li input[value="' + def_lang + '"]');

										fadeInAjxResp(icl_ajx_saved);
										avail_languages_picker = jQuery('#icl_avail_languages_picker');
										avail_languages_picker.find('input[value="' + response.data.previousLanguage + '"]').prop('disabled', false);
										avail_languages_picker.find('input[value="' + def_lang + '"]').prop('disabled', true);
										enabled_languages_items = jQuery('#icl_enabled_languages').find('li');
										enabled_languages_items.removeClass('selected');
										selected_language_item = selected_language.closest('li');
										selected_language_item.addClass('selected');
										selected_language_item.find('label').append(' (' + icl_default_mark + ')');
										enabled_languages_items.find('input').removeAttr('checked');
										selected_language.attr('checked', 'checked');
										enabled_languages.find('input[value="' + response.data.previousLanguage + '"]').parent().html(enabled_languages.find('input[value="' + response.data.previousLanguage + '"]').parent().html().replace('(' + icl_default_mark + ')', ''));
										doneEditingDefaultLanguage();
										fadeInAjxResp('#icl_ajx_response', icl_ajx_saved);
										location.href = location.href.replace(/#[\w\W]*/, '') + '&setup=2';
									} else {
										fadeInAjxResp('#icl_ajx_response', icl_ajx_error);
									}
								}
							});
}
function showLanguagePicker() {
    jQuery('#icl_avail_languages_picker').slideDown();
    jQuery('#icl_add_remove_button').hide();
    jQuery('#icl_change_default_button').hide();
}
function hideLanguagePicker() {
    jQuery('#icl_avail_languages_picker').slideUp();
    jQuery('#icl_add_remove_button').fadeIn();
    jQuery('#icl_change_default_button').fadeIn();
}
function saveLanguageSelection() {
    fadeInAjxResp('#icl_ajx_response', icl_ajxloaderimg);
    var arr = jQuery('#icl_avail_languages_picker').find('ul input[type="checkbox"]'), sel_lang = [];
    jQuery.each(arr, function () {
        if (this.checked) {
            sel_lang.push(this.value);
        }
    });
	jQuery.ajax({
								type:    "POST",
								url:     ajaxurl,
								data:    {
									'action':    'wpml_set_active_languages',
									'nonce':     jQuery('#set_active_languages_nonce').val(),
									'languages': sel_lang
								},
								success: function (response) {
									if (response.success) {
										if (!response.data.noLanguages) {
											fadeInAjxResp('#icl_ajx_response', icl_ajx_saved);
											jQuery('#icl_enabled_languages').html(response.data.enabledLanguages);
											location.href = location.href.replace(/#[\w\W]*/, '');
										} else {
											location.href = location.href.replace(/(#|&)[\w\W]*/, '');
										}
									} else {
										fadeInAjxResp('#icl_ajx_response', icl_ajx_error, true);
										location.href = location.href.replace(/(#|&)[\w\W]*/, '');
									}
								}
							});
    hideLanguagePicker();
}

function iclLntDomains() {
    var language_negotiation_type, icl_lnt_domains_box, icl_lnt_domains_options, icl_lnt_xdomain_options;
    icl_lnt_domains_box = jQuery('#icl_lnt_domains_box');
	icl_lnt_domains_options = jQuery('#icl_lnt_domains');
    icl_lnt_xdomain_options = jQuery('#language_domain_xdomain_options');

    if (icl_lnt_domains_options.attr('checked')) {
        icl_lnt_domains_box.html(icl_ajxloaderimg);
        icl_lnt_domains_box.show();
        language_negotiation_type = jQuery('#icl_save_language_negotiation_type').find('input[type="submit"]');
        language_negotiation_type.prop('disabled', true);
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: 'icl_ajx_action=language_domains' + '&_icl_nonce=' + jQuery('#_icl_nonce_ldom').val(),
            success: function (resp) {
                icl_lnt_domains_box.html(resp);
                language_negotiation_type.prop('disabled', false);
                icl_lnt_xdomain_options.show();
            }
        });
    } else if (icl_lnt_domains_box.length) {
        icl_lnt_domains_box.fadeOut('fast');
        icl_lnt_xdomain_options.fadeOut('fast');
    }
    /*jshint validthis: true */
    if (jQuery(this).val() !== "1") {
        jQuery('#icl_use_directory_wrap').hide();
    } else {
        jQuery('#icl_use_directory_wrap').fadeIn();
    }


}

function iclToggleShowOnRoot() {
    /*jshint validthis: true */
    if (jQuery(this).val() === 'page') {
        jQuery('#wpml_show_page_on_root_details').fadeIn();
        jQuery('#icl_hide_language_switchers').fadeIn();
    } else {
        jQuery('#wpml_show_page_on_root_details').fadeOut();
        jQuery('#icl_hide_language_switchers').fadeOut();
    }
}

function iclUseDirectoryToggle() {
    if (jQuery(this).attr('checked')) {
        jQuery('#icl_use_directory_details').fadeIn();
    } else {
        jQuery('#icl_use_directory_details').fadeOut();
    }
}

	function iclSaveLanguageNegotiationType() {
		var validSettings = true;
		var ajaxResponse;
		var usedUrls;
		var formErrors;
		var formName;

		var languageNegotiationType;
		var rootHtmlFile;
		var showOnRoot;
		var useDirectories;
		var validatedDomains;
		var domainsToValidateCount;
		var domainsToValidate;
		var validDomains;

		var form = jQuery('#icl_save_language_negotiation_type');

		var useDirectoryWrapper = jQuery('#icl_use_directory_wrap');
		languageNegotiationType = parseInt(form.find('input[name=icl_language_negotiation_type]:checked').val());
		useDirectoryWrapper.find('.icl_error_text').hide();

		formName = form.attr('name');
		formErrors = false;
		usedUrls = [jQuery('#icl_ln_home').html()];
		jQuery('form[name="' + formName + '"] .icl_form_errors').html('').hide();
		ajaxResponse = jQuery('form[name="' + formName + '"] .icl_ajx_response').attr('id');
		fadeInAjxResp('#' + ajaxResponse, icl_ajxloaderimg);

		if (1 === languageNegotiationType) {
			useDirectories = form.find('[name=use_directory]').is(':checked');
			showOnRoot = form.find('[name=show_on_root]:checked').val();
			rootHtmlFile = form.find('[name=root_html_file_path]').val();

			if (useDirectories) {
				if ('html' === showOnRoot && !rootHtmlFile) {
					validSettings = false;
					useDirectoryWrapper.find('.icl_error_text.icl_error_1').fadeIn();
				}
			}

			if(true === validSettings) {
				saveLanguageForm();
			}
		}

		if (3 === languageNegotiationType) {
			saveLanguageForm();
		}

		if (2 === languageNegotiationType) {
			domainsToValidate = jQuery('.validate_language_domain');
			domainsToValidateCount = domainsToValidate.length;
			validatedDomains = 0;
			validDomains = 0;

			if (0 < domainsToValidateCount) {
				domainsToValidate.filter(':visible').each(function (index, element) {
					var languageDomainURL;
					var domainValidationCheckbox = jQuery(element);
					var langDomainInput, lang, languageDomain;
					lang = domainValidationCheckbox.attr('value');
					languageDomain = jQuery('.spinner.spinner-' + lang);
					langDomainInput = jQuery('#language_domain_' + lang);
                    var validation = new WpmlDomainValidation(langDomainInput, domainValidationCheckbox);
                    validation.run();
                    var subdirMatches = langDomainInput.parent().html().match(/<code>\/(.+)<\/code>/);
                    languageDomainURL = langDomainInput.parent().html().match(/<code>(.+)<\/code>/)[1] + langDomainInput.val()  + '/' + ( subdirMatches !== null ? subdirMatches[1] : '' );
					if (domainValidationCheckbox.prop('checked')) {
						languageDomain.addClass('is-active');
						if (-1 !== usedUrls.indexOf(languageDomainURL)) {
							languageDomain.empty();
							formErrors = true;
						} else {
							usedUrls.push(languageDomainURL);
							langDomainInput.css('color', '#000');
							jQuery.ajax({
								method:   "POST",
								url:      ajaxurl,
								data:     {
									url:    languageDomainURL,
									action: 'validate_language_domain',
									nonce:  jQuery('#validate_language_domain_nonce').val()
								},
								success:  function (resp) {
									var ajaxLanguagePlaceholder = jQuery('#ajx_ld_' + lang);
									ajaxLanguagePlaceholder.html(resp.data);
									ajaxLanguagePlaceholder.removeClass('icl_error_text');
									ajaxLanguagePlaceholder.removeClass('icl_valid_text');
									if (resp.success) {
										ajaxLanguagePlaceholder.addClass('icl_valid_text');
										validDomains++;
									} else {
										ajaxLanguagePlaceholder.addClass('icl_error_text');
									}
									validatedDomains++;
								},
								error:    function (jqXHR, textStatus) {
									jQuery('#ajx_ld_' + lang).html('');
									if ('0' === jqXHR) {
										fadeInAjxResp('#' + textStatus, icl_ajx_error, true);
									}
								},
								complete: function () {
									languageDomain.removeClass('is-active');
									if (domainsToValidateCount === validDomains) {
										saveLanguageForm();
									}
								}
							});
						}
					} else {
						saveLanguageForm();
					}
				});
			}
		}

		return false;
	}

	function saveLanguageForm() {
		var domains;
		var xdomain = 0;
		var useDirectory = false;
		var hideSwitcher = false;
		var data;
		var form = jQuery('#icl_save_language_negotiation_type');
		var formName = jQuery(form).attr('name');
		var ajxResponse = jQuery(form).find('.icl_ajx_response').attr('id');
		var sso_enabled = jQuery('#sso_enabled').is(':checked');
		var sso_notice  = jQuery('#sso_enabled_notice');

		if (form.find('input[name=use_directory]').is(':checked')) {
			useDirectory = 1;
		}
		if (form.find('input[name=hide_language_switchers]').is(':checked')) {
			hideSwitcher = 1;
		}
		if (form.find('input[name=icl_xdomain_data]:checked').val()) {
			xdomain = parseInt(form.find('input[name=icl_xdomain_data]:checked').val());
		}
		domains = {};
		form.find('input[name^=language_domains]').each(function () {
			var item = jQuery(this);
			domains[item.data('language')] = item.val();
		});

		data = {
			action:                        'save_language_negotiation_type',
			nonce:                         jQuery('#save_language_negotiation_type_nonce').val(),
			icl_language_negotiation_type: form.find('input[name=icl_language_negotiation_type]:checked').val(),
			language_domains:              domains,
			use_directory:                 useDirectory,
			show_on_root:                  form.find('input[name=show_on_root]:checked').val(),
			root_html_file_path:           form.find('input[name=root_html_file_path]').val(),
			hide_language_switchers:       hideSwitcher,
			xdomain:                       xdomain,
			sso_enabled:                   sso_enabled
		};

		jQuery.ajax({

			method:  "POST",
			url:     ajaxurl,
			data:    data,
			success: function (response) {
				var formErrors, rootHtmlFile, rootPage, spl;
				if (response.success) {
					fadeInAjxResp('#' + ajxResponse, icl_ajx_saved);
					if ( sso_enabled ) {
						sso_notice.addClass('updated').fadeIn();
					} else {
						sso_notice.removeClass('updated').fadeOut();
					}

                    if(response.data) {
                        var formMessage = jQuery('form[name="' + formName + '"]').find('.wpml-form-message');
                        formMessage.addClass('updated');
                        formMessage.html(response.data);
                        formMessage.fadeIn();
                    }

                    if (jQuery('input[name=show_on_root]').length) {
						rootHtmlFile = jQuery('#wpml_show_on_root_html_file');
						rootPage = jQuery('#wpml_show_on_root_page');
						if (rootHtmlFile.prop('checked')) {
							rootHtmlFile.addClass('active');
							rootPage.removeClass('active');
						}
						if (rootPage.prop('checked')) {
							rootPage.addClass('active');
							rootHtmlFile.removeClass('active');
						}
					}
				} else {
					formErrors = jQuery('form[name="' + formName + '"] .icl_form_errors');
					formErrors.html(response.data);
					formErrors.fadeIn();
					fadeInAjxResp('#' + ajxResponse, icl_ajx_error, true);
				}
			}
		});
	}

function iclHideLanguagesCallback() {
    iclSaveForm_success_cb.push(function (frm, res) {
        jQuery('#icl_hidden_languages_status').html(res[1]);
    });
}

function icl_reset_languages() {
    /* jshint validthis: true */
    var this_b = jQuery(this);
    if (confirm(this_b.next().html())) {
        this_b.attr('disabled', 'disabled').next().html(icl_ajxloaderimg).fadeIn();
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=reset_languages&_icl_nonce=" + jQuery('#_icl_nonce_rl').val(),
            success: function () {
                location.href = location.pathname + location.search;
            }
        });
    }
}

function iclEnableContentTranslation() {
    var val = jQuery(':radio[name=icl_translation_option]:checked').val();
    /* jshint validthis:true */
    jQuery(this).attr('disabled', 'disabled');
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&wizard=1&new_val=" + val,
        success: function (msg) {
            var spl = msg.split('|');
            if (spl[1]) {
                location.href = spl[1];
            } else {
                location.href = location.href.replace(/#[\w\W]*/, '');
            }
        }
    });
    return false;
}

function installer_registration_form_submit(){
    /* jshint validthis:true */
    var thisf = jQuery(this);
    var action = jQuery('#installer_registration_form').find('input[name=button_action]').val();
    thisf.find('.status_msg').html('');
    thisf.find(':submit').attr('disabled', 'disabled');
    jQuery('<span class="spinner"></span>').css({display: 'inline-block', float: 'none'}).prependTo(thisf.find(':submit:first').parent());        

    if(action === 'later'){
        thisf.find('input[name=installer_site_key]').parent().remove();            
    }

    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: "icl_ajx_action=registration_form_submit&" + thisf.serialize(),
        success: function (msg) {
            if(action === 'register' || action === 'later'){
                thisf.find('.spinner').remove();
                if(msg.error){
                    thisf.find('.status_msg').html(msg.error).addClass('icl_error_text');
                }else{
                    thisf.find('.status_msg').html(msg.success).addClass('icl_valid_text');
                    thisf.find(':submit:visible').hide();
                    thisf.find(':submit[name=finish]').show();
                }
                thisf.find(':submit').removeAttr('disabled', 'disabled');
            }else{ // action = finish
                location.href = location.href.replace(/#[\w\W]*/, '');                    
            }
        }
    });

    return false;
}

	function update_seo_head_langs_priority(event) {
		var element = jQuery(this);
		if (element.attr('checked')) {
			jQuery('#wpml-seo-head-langs-priority').removeAttr('disabled');
		} else {
			jQuery('#wpml-seo-head-langs-priority').attr('disabled', 'disabled');
		}
	}
}());
