/*jshint devel:true */
/*global jQuery, ajaxurl, get_checked_cbs */
var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.ChangeLanguage = function () {
	"use strict";

	var privateData = {};

	var init = function () {
		jQuery(document).ready(function () {
			var changeLangDialog;
			jQuery('#icl_st_change_lang_selected').on('click', showDialog);
			setupDialog();
			changeLangDialog = jQuery('#wpml-change-language-dialog');
			privateData.language_select = changeLangDialog.find('select');
			privateData.language_select.on('change', languageChanged);
			privateData.summary_text = changeLangDialog.find('.js-summary');
		});
	};

	var setupDialog = function () {
		privateData.change_lang_dialog = jQuery('#wpml-change-language-dialog');
		privateData.change_lang_dialog.dialog({
			autoOpen:      false,
			resizable:     false,
			modal:         true,
			width:         'auto',
			closeText:     privateData.change_lang_dialog.data('cancel-text'),
			closeOnEscape: true,
			buttons:       [
				{
					id:    'wpml-change-language-dialog-apply-button',
					text:  privateData.change_lang_dialog.data('button-text'),
					click: applyChanges
				}
			],
			close: function() {
				var languageSelector = jQuery('.js-simple-lang-selector-flags');
				if (languageSelector) {
					languageSelector.select2("close");
				}
			}
		});

		privateData.apply_button = jQuery('#wpml-change-language-dialog-apply-button');
		privateData.apply_button.prop('disabled', true).addClass('button-primary');
		privateData.spinner = privateData.change_lang_dialog.find('.spinner');
		privateData.spinner.css( 'float', 'none' ).detach().insertBefore( privateData.apply_button );
	};

	var showDialog = function () {
		var i;
		var langText;
		var summary;
		var langs = getLanguagesOfSelectedStrings();

		if (1 === langs.length) {
			privateData.language_select.val(langs[0]);
		}

		summary = privateData.summary_text.data('text');

		langText = '';
		for (i = 0; i < langs.length; i++) {
			if ('' !== langText) {
				langText += ', ';
			}
			langText += getLanguageName(langs[i]);
		}
		summary = summary.replace('%LANG%', langText);
		privateData.summary_text.text(summary);
		privateData.change_lang_dialog.dialog('open');
	};

	var languageChanged = function () {
		var lang = privateData.language_select.val();
		if (lang) {
			privateData.apply_button.prop('disabled', false);
		} else {
			privateData.apply_button.prop('disabled', true);
		}
	};

	var getLanguagesOfSelectedStrings = function () {
		var lang;
		var i;
		var languages = [];
		var checkboxes = get_checked_cbs();
		for (i = 0; i < checkboxes.length; i++) {
			lang = jQuery(checkboxes[i]).data('language');
			if (0 > languages.indexOf(lang)) {
				languages.push(lang);
			}
		}

		return languages;
	};

	var applyChanges = function () {
		var checkBoxValue;
		var data;
		var i;
		var checkboxes;
		var strings;
		privateData.apply_button.prop('disabled', true);
		privateData.spinner.addClass( 'is-active' );

		strings = [];
		checkboxes = get_checked_cbs();
		for (i = 0; i < checkboxes.length; i++) {
			checkBoxValue = jQuery(checkboxes[i]).val();
			strings.push(checkBoxValue);
		}

		data = {
			action:   'wpml_change_string_lang',
			wpnonce:  jQuery('#wpml_change_string_language_nonce').val(),
			strings:  strings,
			language: privateData.language_select.val()
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

	var getLanguageName = function (code) {
		var languageName;
		var option;
		var selectedCode = code || 'en';
		option = privateData.language_select.find('option[value="' + selectedCode + '"]');

		languageName = selectedCode;
		if (option) {
			languageName = option.text();
		}
		return languageName;
	};

	init();
};

WPML_String_Translation.change_language = new WPML_String_Translation.ChangeLanguage();