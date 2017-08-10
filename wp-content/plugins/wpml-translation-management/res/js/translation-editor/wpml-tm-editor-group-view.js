/*global Backbone, jQuery*/

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorGroupView = Backbone.View.extend({
		tagName: 'div',
		className: 'wpml-form-row wpml-field-group',
		events: {
			'click .js-button-copy-group': 'onCopy',
			'update_button_state .js-button-copy-group': 'setCopyButtonState'
		},
		render: function (group) {
			var self = this;
			self.$el.html(WPML_TM['templates/translation-editor/group.html'](group));
		},
		setup: function () {
			this.$el.find('.js-button-copy').css({visibility: 'hidden'});
			this.initializeGroupCopyButtons();
			this.setCopyButtonState();
		},
		initializeGroupCopyButtons: function () {

			var self = this;

			// Poistion the copy button so it's between the original and translation

			self.$el.find('.js-button-copy:first').each(function () {

				// center the button in group
				var groupCopyButton = self.$el.find('.js-button-copy-group');
				var firstButton = jQuery(this);
				var lastButton = self.$el.find('.js-button-copy:last');
				if (lastButton.length) {
					var groupHeight = lastButton.position().top + lastButton.height() - firstButton.position().top;
					var newTop = groupHeight / 2 - groupCopyButton.height() / 2;
					groupCopyButton.css({
						'position': 'relative',
						'top': newTop
					});
				}

				groupCopyButton.insertAfter(firstButton);

				// hide the original copy button
				firstButton.hide();

			});

		},
		onCopy: function () {
			this.$el.find('.js-button-copy').trigger('click');
		},
		setCopyButtonState: function () {
			this.$el.find('.js-button-copy-group').prop('disabled', this.$el.find('.js-button-copy:disabled').length > 0);
		}

	});
}());
	
