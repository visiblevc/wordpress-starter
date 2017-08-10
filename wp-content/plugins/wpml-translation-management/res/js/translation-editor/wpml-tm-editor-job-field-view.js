/*global Backbone, WpmlTmEditorModel, document, jQuery, _ */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorJobFieldView = Backbone.View.extend({
		tagName: 'div',
		className: 'wpml-form-row',
		events: {
			'click .icl_tm_copy_link': 'copyField'
		},
		copyField: function () {
			var self = this;
			self.setTranslation(self.getOriginal());
			return false;
		},
		updateUI: function () {
			var self = this;
			if (self.$el.is(':visible')) {
				var original = self.getOriginal();
				var translation = self.getTranslation();

				self.$el.find('.icl_tm_copy_link').prop('disabled', translation !== '' || original === '');
				self.translationCompleteCheckbox.prop('disabled', translation === '');
				if ('' === translation) {
					self.translationCompleteCheckbox.prop('checked', false);
					self.translationCompleteCheckbox.trigger('change');
				}
				self.sendMessageToGroupView();
				jQuery(document).trigger('WPML_TM.editor.field_update_ui', self);
			}
		},
		render: function (field, labels) {
			var self = this;
			self.field = field;
			if (typeof self.field.title === 'undefined' || '' === self.field.title) {
				self.$el.removeClass('wpml-form-row').addClass('wpml-form-row-nolabel');
			}
			self.$el.html(WPML_TM[self.getTemplate()]({
				field: self.field,
				labels: labels
			}));
			self.translationCompleteCheckbox = self.$el.find('.js-field-translation-complete');
			_.defer(_.bind(self.updateUI, self));
			if (WpmlTmEditorModel.hide_empty_fields && field.field_data === '') {
				self.$el.hide();
				self.translationCompleteCheckbox.prop('checked', true);
				self.translationCompleteCheckbox.prop('disabled', false);
			}
			if (!WpmlTmEditorModel.requires_translation_complete_for_each_field) {
				self.translationCompleteCheckbox.hide();
				self.translationCompleteCheckbox.parent().hide();
			}

			jQuery(document).trigger('WPML_TM.editor.field_view_ready', self);
		},

		setup: function () {
		},

		sendMessageToGroupView: function () {
			var group = this.$el.closest('.wpml-field-group');
			if (group.length) {
				group.find('.js-button-copy-group').trigger('update_button_state');
			}
		},

		getFieldType: function () {
			return this.field.field_type;
		}


	});

}());
