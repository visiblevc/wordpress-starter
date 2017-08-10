/*global  */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorTextareaFieldView = WPML_TM.editorBasicFieldView.extend({
	
		getTemplate: function () {
			return 'templates/translation-editor/textarea.html';
		}
	});

}());
