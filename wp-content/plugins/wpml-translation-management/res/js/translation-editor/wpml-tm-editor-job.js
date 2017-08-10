/*global _, Backbone, jQuery, WpmlTmEditorModel, ajaxurl */

var WPML_TM = WPML_TM || {};

(function () {
	"use strict";

	WPML_TM.editorJob = Backbone.Model.extend({
		fetch: function (successCallback) {
			var self = this;

			_.each(WpmlTmEditorModel.fields, function (field) {
				field.field_finished = parseInt(field.field_finished, 10);
				self.set(field.field_type, field.field_data);
				self.set(field.field_type + '_raw', field);
			});
			self.set('layout', WpmlTmEditorModel.layout);
			successCallback();
		},
		save: function (data) {
			var self = this;
			jQuery.ajax(
				{
					type: "POST",
					url: self.url(),
					dataType: 'json',
					data: {
						data: data,
						action: 'wpml_save_job_ajax',
						_icl_nonce: self.get('nonce')
					},
					success: function (response) {
						if (response.success) {
							self.trigger('saveJobSuccess');
						} else {
							self.trigger('saveJobFailed');
						}
					}
				});
		},
		progressPercentage: function () {

			return jQuery('.icl_tm_finished:checked:visible').length / jQuery('.icl_tm_finished:visible').length * 100;
		},
		/**
		 * Overrides the BackBone url method to use the WordPress ajax endpoint
		 *
		 * @returns {String}
		 */
		url: function () {

			return ajaxurl;
		}
	});
}());
	