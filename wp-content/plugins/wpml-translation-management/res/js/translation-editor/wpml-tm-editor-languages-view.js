/*global Backbone */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorLanguagesView = Backbone.View.extend({
		tagName: 'header',
		className: 'wpml-translation-header',
		events: {
			'click .js-button-copy-all': 'copyAll'
		},
		initialize: function (options) {
			this.mainView = options.mainView;
		},
		render: function () {
			var self = this;
			self.$el.html(WPML_TM['templates/translation-editor/languages.html'](self.model));
		},
		copyAll: function () {
			var copyAllTask = new WPML_TM.editorCopyAll(this.mainView);
			copyAllTask.copy();
		}
	});
}());
