/*global Backbone, jQuery*/

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorImageView = Backbone.View.extend({
		tagName: 'div',
		className: 'wpml-field-image',
		render: function (image) {
			var self = this;
			self.$el.html(WPML_TM['templates/translation-editor/image.html'](image));
		},
		setup: function () {

		}
	});
}());
