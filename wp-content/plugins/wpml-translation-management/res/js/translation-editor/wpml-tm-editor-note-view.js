/*global Backbone */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorNoteView = Backbone.View.extend({
		tagName: 'div',
		className: 'wpml-form-row wpml-note',
	
		render: function () {
			var self = this;
			self.$el.html(WPML_TM['templates/translation-editor/note.html'](self.model));
		}
	});
}());
