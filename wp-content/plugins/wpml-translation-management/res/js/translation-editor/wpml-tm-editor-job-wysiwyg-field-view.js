/*global jQuery, tinymce, tinyMCE, _, quicktags, tmEditor, WpmlTmEditorModel*/

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorWysiwygFieldView = WPML_TM.editorJobFieldView.extend({
		getTemplate: function () {
			return 'templates/translation-editor/wysiwyg.html';
		},

		getOriginal: function () {
			return this.$el.find('textarea.original_value').text();
		},

		getTranslation: function () {
			var self = this;
			var editor = tinymce.get(self.field.field_type);
			if (editor && editor instanceof tinyMCE.Editor) {
				return editor.getContent();
			} else {
				return self.getTextAreaElement().val();
			}
		},

		setTranslation: function (value) {
			var self = this;
			var editor = tinymce.get(self.field.field_type);
			if (editor && editor instanceof tinyMCE.Editor) {
				editor.setContent(value);
			}
			self.getTextAreaElement().val(value);
			this.updateUI();
		},

		setup: function () {
			var self = this;
			self.replacePlaceHolder('original');
			self.replacePlaceHolder('translated');

			self.$el.find('textarea.original_value').prop('readonly', true);
			self.getTextAreaElement().on('input', _.bind(self.updateUI, self));
			self.getTextAreaElement().on('input', function () {
				tmEditor.model.trigger('translationUpdated', true);
			});

			if (!WpmlTmEditorModel.show_media_button) {
				self.$el.find('.wp-media-buttons').hide();
			}

			_.delay(_.bind(self.waitForEditorAndThenInstallHooks, self), 1000);
		},

		getTextAreaElement: function () {
			return this.$el.find('textarea#' + this.field.field_type);
		},

		waitForEditorAndThenInstallHooks: function () {
			var self = this;
			var editor = tinymce.get(self.field.field_type);
			if (editor && editor instanceof tinyMCE.Editor) {
				editor.on('nodechange keyup', function (e) {
					var lazyOnChange = _.debounce(_.bind(self.updateUI, self), 1000);
					lazyOnChange(editor);
					editor.save();
				});

				editor.on('change', function (e) {
					tmEditor.model.trigger('translationUpdated', true);
				});
				_.delay(function () {
					self.$el.find('.mce_editor_origin .mce-toolbar-grp').height(self.$el.find('.mce_editor .mce-toolbar-grp').height());
				}, 1000);
				self.setRtlAttributes(editor);
				self.setOriginalBackgroundGray( editor );

			} else {
				_.delay(_.bind(self.waitForEditorAndThenInstallHooks, self), 1000);
			}
		},

		replacePlaceHolder: function (type) {
			var self = this;
			var $placeholder = self.$el.find('#' + type + '_' + self.field.field_type + '_placeholder');
			jQuery('#' + self.field.field_type + '_' + type + '_editor').detach().insertAfter($placeholder);
			$placeholder.remove();
		},
		setRtlAttributes: function (editor) {
			var self = this, dir, body, html;

			dir = WpmlTmEditorModel.rtl_translation ? 'rtl' : 'ltr';
			html = jQuery(editor.iframeElement).contents().find('html');
			html.attr('dir', dir);
			body = html.find('body');
			if (body.length) {
				body.attr('dir', dir);
			}
			self.getTextAreaElement().attr('dir', dir);

			dir = WpmlTmEditorModel.rtl_original ? 'rtl' : 'ltr';
			html = self.$el.find('.mce_editor_origin').find('iframe').contents().find('html');
			html.attr('dir', dir);
			body = html.find('body');
			if (body.length) {
				body.attr('dir', dir);
			}
			self.$el.find('textarea.original_value').attr('dir', dir);
		},
		setOriginalBackgroundGray: function ( editor ) {
			var self = this,
				html = self.$el.find( '.mce_editor_origin' ).find( 'iframe' ).contents().find( 'html' ),
				body = html.find('body'),
				sizer = self.$el.find( '.mce_editor_origin' ).find( '.mce-statusbar' ).find( '.mce-flow-layout' );

			body.css( 'background-color', '#eee' );
			sizer.css( 'background-color', '#eee' );
		}

	});
}());
