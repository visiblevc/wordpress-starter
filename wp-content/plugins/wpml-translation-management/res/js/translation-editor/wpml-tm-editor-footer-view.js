/*global jQuery, ajaxurl, Backbone, WpmlTmEditorModel, window, tmEditorStrings, _ */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorFooterView = Backbone.View.extend({
		tagName: 'footer',
		className: 'wpml-translation-action-buttons',

		events: {
			'click .js-save': 'save',
			'click .js-resign': 'resign',
			'click .js-dialog-cancel': 'cancel',
			'click .js-save-and-close': 'save_and_close'
		},
		initialize: function () {
			var self = this;
			self.listenTo(self.model, 'translationUpdated', self.setDirty);
		},
		save: function () {
			var self = this;
			self.doSave();
		},
		editIndependently: function () {
			WpmlTmEditorModel.is_duplicate = false;
		},
		doSave: function () {
			var self = this;
			var saveMessage = jQuery('.js-saving-message');
			saveMessage.show();
			self.disableButtons(true);
			self.listenToOnce(self.model, 'saveJobSuccess', function () {
				saveMessage.hide();
				self.disableButtons(false);
			});
			self.model.save(jQuery('#icl_tm_editor').serialize());

			self.setDirty(false);
			return self;
		},
		resign: function () {
			if (window.confirm(tmEditorStrings.resign_translation)) {
				window.location.href = tmEditorStrings.resign_url;
			}
		},
		cancel: function () {
			this.redirect_to_return_url('wpml_tm_cancel=1');
		},
		save_and_close: function () {
			var self = this;
			self.listenToOnce(self.model, 'saveJobSuccess', function () {
				self.setDirty(false);
				this.redirect_to_return_url('wpml_tm_saved=1');
			});

			self.save();
		},
		redirect_to_return_url: function (param_string) {
			var url = WpmlTmEditorModel.return_url;
			if (url.indexOf('?') < 0) {
				url += '?' + param_string;
			} else {
				url += '&' + param_string;
			}
			window.location = url;
		},
		render: function () {
			var self = this;
			self.$el.html(WPML_TM['templates/translation-editor/footer.html'](tmEditorStrings));
			self.progressBar = self.$el.find('.js-progress-bar');
			self.translationComplete = self.$el.find(':checkbox[name=complete]');
			self.showProgressBar();
			self.maybeShowTranslationComplete();

			window.onbeforeunload = function (e) {
				if (self.isDirty()) {
					return tmEditorStrings.confirmNavigate;
				}
			};
			_.defer(_.bind(self.maybeShowDuplicateDialog, self));
		},
		maybeShowDuplicateDialog: function () {
			var self = this;
			if (WpmlTmEditorModel.is_duplicate) {
				self.dialog = new WPML_TM.editorEditIndependentlyDialog(self);
			}
		},
		showProgressBar: function () {

			var self = this;
			self.progressBar.css('display', WpmlTmEditorModel.requires_translation_complete_for_each_field ? 'inline-block' : 'none');
			self.progressBar.find('.ui-progressbar-value').height(self.progressBar.find('.progress-bar-text').height());
			self.progressBar.progressbar({});
			var value = parseInt(self.model.progressPercentage(), 10);
			self.progressBar.find('.progress-bar-text').html(value + '% Complete');
			self.progressBar.progressbar({value: value});
			self.progressBar.find('.ui-progressbar-value').height(self.progressBar.find('.progress-bar-text').height());

			return self;
		},
		setCompleteCheckBox: function () {
			var self = this;
			if (WpmlTmEditorModel.requires_translation_complete_for_each_field) {
				self.translationComplete.prop('checked', self.model.progressPercentage() === 100);
			}
			return self;
		},
		maybeShowTranslationComplete: function () {
			var self = this;
			if (WpmlTmEditorModel.requires_translation_complete_for_each_field) {
				self.translationComplete.parent().hide();
			} else {
				self.translationComplete.prop('checked', WpmlTmEditorModel.translation_is_complete);
			}
		},
		progressBar: function () {
			return this.$el.find('.js-progress-bar');
		},
		setDirty: function (value) {
			this.model.set('is_dirty', value);
		},
		isDirty: function () {
			return this.model.get('is_dirty');
		},
		disableButtons: function (state) {
			this.$el.find('.js-save, .js-resign, .js-dialog-cancel, .js-save-and-close').prop('disabled', state);
		},
		disableSaveButtons: function (state) {
			this.$el.find('.js-save, .js-save-and-close').prop('disabled', state);
		},
		hideResignButton: function (state) {
			if (state) {
				this.$el.find('.js-resign').hide();
			} else {
				this.$el.find('.js-resign').show();
			}
		}
	});
}());
	