/*global  */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorSingleLineFieldView = WPML_TM.editorBasicFieldView.extend({
	
		getTemplate: function () {
			return 'templates/translation-editor/single-line.html';
		}
	});

}());
