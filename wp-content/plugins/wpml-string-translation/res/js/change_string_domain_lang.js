/*jshint devel:true */
/*global jQuery, ajaxurl */

var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.ChangeDomainLanguage = function () {
	"use strict";

	var privateData = {};

	var init = function () {
		jQuery(document).ready(function () {

			privateData.summary_div = jQuery('#wpml-change-domain-language-dialog').find('.js-summary');
			privateData.lang_select = jQuery(privateData.summary_div).find('select');
			privateData.apply_button = jQuery('#wpml-change-domain-language-dialog-apply-button');
			privateData.table_body = privateData.summary_div.find('table').find('tbody');
			privateData.domain_select = jQuery('#wpml-domain-select');
			privateData.check_all = jQuery(privateData.summary_div).find('.js-all-check');
			privateData.lang_area = jQuery(privateData.summary_div).find('.js-lang-select-area');

			setupDialog();

			jQuery('#wpml-language-of-domains-link').on('click', showDialog);
			privateData.domain_select.on('change', showSummary);

			privateData.apply_button.prop('disabled', true).addClass('button-primary');

			privateData.check_all.on('click', checkAllClick);

			privateData.lang_select.on('change', changeLanguage);

			jQuery(privateData.summary_div).find('.js-default').on('click', enableApplyButton);

		});
	};

	var setupDialog = function () {
		privateData.change_lang_dialog = jQuery('#wpml-change-domain-language-dialog');
		privateData.change_lang_dialog.dialog({
				autoOpen:      false,
				resizable:     false,
				modal:         true,
				minWidth:      600,
				closeText:     privateData.change_lang_dialog.data('cancel-text'),
				closeOnEscape: true,
				buttons:       [
					{
						id:    'wpml-change-domain-language-dialog-apply-button',
						text:  privateData.change_lang_dialog.data('button-text'),
						click: applyChanges
					}
				],
				close:         function () {
					var languageSelector = jQuery('.js-simple-lang-selector-flags');
					if (languageSelector) {
						languageSelector.select2("close");
					}
				}
			});

		privateData.apply_button = jQuery('#wpml-change-domain-language-dialog-apply-button');
		privateData.spinner = privateData.change_lang_dialog.find('.spinner');
		privateData.spinner.css( 'float', 'none' ).detach().insertBefore( privateData.apply_button );
		enableApplyButton();

	};

	var showDialog = function () {
		privateData.change_lang_dialog.dialog('open');
	};

	var showSummary = function () {
		var domainLang;
		var languages;
		var domain = jQuery(this).val();
		if (domain) {
			languages = jQuery(this).find('option:selected').data('langs');
			buildTable(languages);

			domainLang = jQuery(this).find('option:selected').data('domain_lang');
			jQuery(privateData.lang_select).select2('val', domainLang);

			privateData.summary_div.show();

		} else {
			privateData.summary_div.hide();
		}
	};

	var buildTable = function (data) {
		var i;
		var tr;
		jQuery(privateData.summary_div).find('.js-lang').off('click');

		privateData.table_body.empty();

		for (i = 0; i < data.length; i++) {
			tr = '';
			if (i % 2) {
				tr += '<tr class="alternate">';
			} else {
				tr += '<tr>';
			}
			tr += '<td>';
			tr += '<input class="js-lang" type="checkbox" value="' + data[i].language + '" />';
			tr += '</td>';
			tr += '<td>';
			tr += data[i].display_name;
			tr += '</td>';
			tr += '<td class="num">';
			tr += data[i].count;
			tr += '</td>';
			tr += '</tr>';
			privateData.table_body.append(tr);
		}

		jQuery(privateData.summary_div).find('.js-lang').on('click', langClick);

		if (1 === data.length) {
			privateData.check_all.hide();
		} else {
			privateData.check_all.show();
		}

	};

	var applyChanges = function () {

		var data;
		var languages;
		privateData.apply_button.prop('disabled', true);
		privateData.spinner.addClass( 'is-active' );

		languages = [];
		privateData.summary_div.find('.js-lang:checked').each(function () {
			var itemSourceLanguage = jQuery(this).val();
			languages.push(itemSourceLanguage);
		});

		data = {
			action:      'wpml_change_string_lang_of_domain',
			wpnonce:     jQuery('#wpml_change_string_domain_language_nonce').val(),
			domain:      privateData.domain_select.val(),
			langs:       languages,
			use_default: 0 < privateData.summary_div.find('.js-default:checked').length,
			language:    privateData.lang_select.val()
		};

		jQuery.ajax({
				url:      ajaxurl,
				type:     'post',
				data:     data,
				dataType: 'json',
				success:  function (response) {
					if (response.success) {
						window.location.reload(true);
					}
					if (response.error) {
						privateData.spinner.removeClass( 'is-active' );
						alert(response.error);
						privateData.apply_button.prop('disabled', false);
					}
				}
			});
	};

	var checkAllClick = function () {
		var selected = jQuery(this).prop('checked');
		jQuery(privateData.summary_div).find('.js-lang').prop('checked', selected);
		enableApplyButton();
	};

	var langClick = function () {
		var allLanguagesChecked = jQuery(privateData.summary_div).find('.js-lang').length === jQuery(privateData.summary_div).find('.js-lang:checked').length;
		jQuery(privateData.summary_div).find('.js-all-check').prop('checked', allLanguagesChecked);
		enableApplyButton();
	};

	var changeLanguage = function () {
		enableApplyButton();
	};

	var enableApplyButton = function () {
		var lang = privateData.lang_select.val();
		if (lang && jQuery(privateData.summary_div).find('.js-lang:checked').length) {
			privateData.apply_button.prop('disabled', false);
			privateData.lang_area.show();
		} else if (jQuery(privateData.summary_div).find('.js-lang:checked').length) {
			privateData.apply_button.prop('disabled', true);
			privateData.lang_area.show();
		} else {
			privateData.apply_button.prop('disabled', true);
			privateData.lang_area.hide();
		}

	};

	init();

};

WPML_String_Translation.change_domain_language = new WPML_String_Translation.ChangeDomainLanguage();

