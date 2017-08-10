/*global _, Backbone, WpmlTmEditorModel, tmEditorStrings, jQuery, tmEditor, document */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorMainView = Backbone.View.extend({
		events: {
			'change .icl_tm_finished': 'updateState'
		},
		updateState: function () {
			var self = this;
			self.footerView.showProgressBar().setCompleteCheckBox();
		},
		render: function () {
			var self = this;
			var job_type = jQuery('input[name="job_post_type"]').val();
			self.fieldViews = [];

			jQuery('#screen-meta-links').hide();

			jQuery(document).trigger('WPML_TM.editor.before_render', [job_type] );

			self.addHeaderView();
			self.addNoteView();
			self.addLanguagesView();

			self.model.fetch(function () {
				self.addFields(self.model.get('layout'), self.$el.find('#wpml-translation-editor-wrapper'));
				self.addFooterView();

				jQuery(document).trigger('WPML_TM.editor.ready', [job_type, self.fieldViews, self.footerView]);
			});
			self.updateState();

			return self;
		},
		addFields: function (fields, $location) {
			var self = this;
			_.each(fields, function (field) {
				if (typeof field == 'string') {
					self.fieldViews.push(self.createFieldView(field, $location));
				} else if (field.field_type == 'tm-section') {
					self.createSection(field, $location);
				} else if (field.field_type == 'tm-group') {
					self.createGroup(field, $location);
				} else if (field.field_type == 'wcml-image') {
					self.createImage(field, $location);
				}
			});

		},
		createFieldView: function (field, $location) {
			var self = this;
			field = self.model.get(field + '_raw');
			var view = WPML_TM.fieldViewFactory.create(field, {
				id: 'job_field_' + field.field_type,
				job_id: self.model.get('job_id')
			});
			view.render(self.model.get(field.field_type + '_raw'), tmEditorStrings);
			$location.last().append(view.$el);
			view.setup();
			return view;
		},
		createSection: function (field, $location) {
			var self = this;
			var view = new WPML_TM.editorSectionView({
				job_id: self.model.get('job_id')
			});
			view.render(field);
			$location.last().append(view.$el);
			self.addFields(field.fields, view.$el.find('.inside'));
		},
		createGroup: function (field, $location) {
			var self = this;
			var view = new WPML_TM.editorGroupView({
				job_id: self.model.get('job_id')
			});
			view.render(field);
			$location.last().append(view.$el);
			self.addFields(field.fields, view.$el.find('.inside'));
			view.setup();
		},
		createImage: function (field, $location) {
			var self = this;
			var view = new WPML_TM.editorImageView({
				job_id: self.model.get('job_id')
			});
			view.render(field);
			$location.last().append(view.$el);
			self.addFields(field.fields, view.$el.find('.inside'));
			view.setup();
		},
		addHeaderView: function () {
			var self = this;
			var headerView = new WPML_TM.editorHeaderView({
				model: WpmlTmEditorModel.header
			});
			headerView.render();
			self.appendToDom(headerView);
		},
		addNoteView: function () {
			var self = this;
			if (WpmlTmEditorModel.note) {
				var noteView = new WPML_TM.editorNoteView({
					model: WpmlTmEditorModel
				});
				noteView.render();
				self.appendToDom(noteView);
			}
		},
		addLanguagesView: function () {
			var self = this;
			self.languagesView = new WPML_TM.editorLanguagesView({
				model: {
					language: WpmlTmEditorModel.languages,
					labels: tmEditorStrings
				},
				mainView: self
			});
			self.languagesView.render();
			self.appendToDom(self.languagesView);
		},
		addFooterView: function () {
			var self = this;
			self.footerView = new WPML_TM.editorFooterView({
				model: tmEditor.model
			});
			self.footerView.render();
			self.appendToDom(self.footerView);
		},
		appendToDom: function (view) {
			var self = this;
			if (view instanceof WPML_TM.editorHeaderView) {
				self.$el.find('#wpml-translation-editor-header').last().append(view.$el);
			} else {
				self.$el.find('#wpml-translation-editor-wrapper').last().append(view.$el);
			}
		},
		hasTranslations: function () {
			var self = this;
			var hasTranslation = false;
			_.each( self.fieldViews, function ( view ) {
				if ( ! hasTranslation) {
					if ( view.getTranslation() !== '' && view.getTranslation() !== view.getOriginal() ) {
						hasTranslation = true;
					}
				}
			});
			return hasTranslation;
		},
		copyOriginalOverwrite: function () {
			var self = this;
			_.each(self.fieldViews, function (view) {
				view.copyField();
			});
		},
		copyOriginalDontOverwrite: function () {
			var self = this;
			_.each(self.fieldViews, function (view) {
				if (view.getTranslation() === '') {
					view.copyField();
				}
			});
		}
	});
}());
	