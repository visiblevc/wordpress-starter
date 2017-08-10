/*global Backbone, tmEditorStrings */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorSectionView = Backbone.View.extend({
		tagName: 'div',
		className: 'postbox',
		events: {
			'click .handlediv': 'showHideSection',
			'click .hndle': 'showHideSection'
		},
	
		render: function (field) {
			var self = this;
			self.$el.html(WPML_TM['templates/translation-editor/section.html']({
				section: field,
				labels: tmEditorStrings
			}));
		},
		showHideSection: function () {
			this.$el.toggleClass('closed');
		}
	});

}());
