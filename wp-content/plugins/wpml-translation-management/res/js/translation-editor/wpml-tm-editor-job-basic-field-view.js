/*global Backbone, jQuery, _, tmEditor */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";
	
	WPML_TM.editorBasicFieldView = WPML_TM.editorJobFieldView.extend({
		events: _.extend({
			'input .js-translated-value': 'onChange'
		}, WPML_TM.editorJobFieldView.prototype.events),
	
		onChange: function () {
			tmEditor.model.trigger('translationUpdated', true);
			this.updateUI();
		},
	
		getOriginal: function () {
			return this.$el.find('.js-original-value').val();
		},
	
		getTranslation: function () {
			return this.$el.find('.js-translated-value').val();
		},
	
		setTranslation: function (value) {
			this.$el.find('.js-translated-value').val(value);
			this.updateUI();
		}
	
	});
}());

