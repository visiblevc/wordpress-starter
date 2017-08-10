/*jshint devel:true */
/*global jQuery */

var WPML_TM = WPML_TM || {};

WPML_TM.editorEditIndependentlyDialog = function (editor) {
	"use strict";

	var dialog;
	var endingDuplicate;

	var init = function (editor) {
		endingDuplicate = false;

		dialog = jQuery("#wpml-translation-editor-edit-independently-dialog");
		dialog.dialog({
			autoOpen: true,
			modal: true,
			minWidth: 500,
			resizable: false,
			draggable: false,
			dialogClass: 'dialog-fixed otgs-ui-dialog',
			beforeClose: function (event, ui) {
				if (!endingDuplicate) {
					dialogCancel();
				}
			}
		});

		jQuery(dialog).find('.js-edit-independently-cancel').off('click');
		jQuery(dialog).find('.js-edit-independently-cancel').on('click', dialogCancel);

		jQuery(dialog).find('.js-edit-independently').off('click');
		jQuery(dialog).find('.js-edit-independently').on('click', function () {
			editor.editIndependently();
			endingDuplicate = true;
			dialog.dialog('close');
		});
	};

	var dialogCancel = function () {
		editor.cancel();
	};

	init(editor);
};

