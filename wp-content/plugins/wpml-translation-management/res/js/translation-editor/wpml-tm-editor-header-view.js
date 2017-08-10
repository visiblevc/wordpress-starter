/*global Backbone, jQuery*/

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorHeaderView = Backbone.View.extend({
		tagName: 'span',
	
		render: function () {
			var self = this;
			self.$el.html(WPML_TM['templates/translation-editor/header.html'](self.model));
		}
	});
}());
	