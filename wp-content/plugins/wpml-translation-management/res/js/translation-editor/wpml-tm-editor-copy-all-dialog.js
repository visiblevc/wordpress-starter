/*jshint devel:true */
/*global jQuery */

var WPML_TM = WPML_TM || {};

WPML_TM.editorCopyAll = function (mainView) {
	"use strict";

	var self = this;

	var init = function (mainView) {
		self.mainView = mainView;
	};

	self.copy = function () {
		var hasTranslations = self.mainView.hasTranslations();
		if (!hasTranslations) {
			self.mainView.copyOriginalOverwrite();
		} else {
			self.copyAllDialog = new WPML_TM.editorCopyAllDialog(this);
		}
	};

	self.copyAllNotTranslated = function () {
		self.mainView.copyOriginalDontOverwrite();
	};

	self.copyAllOverwrite = function () {
		self.mainView.copyOriginalOverwrite();
	};


	init(mainView);

};

WPML_TM.editorCopyAllDialog = function (editor) {
	"use strict";

	var dialog;

	var init = function (editor) {
		dialog = jQuery("#wpml-translation-editor-copy-all-dialog");
		dialog.dialog({
			autoOpen: true,
			modal: true,
			minWidth: 500,
			resizable: false,
			draggable: false,
			dialogClass: 'dialog-fixed otgs-ui-dialog'
		});

		jQuery(dialog).find('.js-copy-cancel').off('click');
		jQuery(dialog).find('.js-copy-cancel').on('click', dialogCancel);

		jQuery(dialog).find('.js-copy-not-translated').off('click');
		jQuery(dialog).find('.js-copy-not-translated').on('click', function () {
			editor.copyAllNotTranslated();
			dialog.dialog('close');
		});
		jQuery(dialog).find('.js-copy-overwrite').off('click');
		jQuery(dialog).find('.js-copy-overwrite').on('click', function () {
			editor.copyAllOverwrite();
			dialog.dialog('close');
		});

	};

	var dialogCancel = function () {
		dialog.dialog('close');
	};

	init(editor);
};

