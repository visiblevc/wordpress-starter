/*jshint browser:true, devel:true */
/*global jQuery, Backbone, Translation_Jobs, head, ajaxurl, _ */
(function () {
	"use strict";

	Translation_Jobs.listing.views.ListingFilterView = Backbone.View.extend(
		{
			el:          '.filter-row',
			initialize:  function (options) {
				var self = this;
				_.bindAll(this, 'render');

				self.options = options;
				self.$el.data('view', self);

				self.render = _.wrap(
					self.render, function (render, args) {
						render(args);
						return self;
					}
				);
			},
			events:      {
				"change #translator_id":        "filter_data",
				"change #filter-job-status":    "filter_data",
				"change #filter-job-lang-from": "filter_data",
				"change #filter-job-lang-to":   "filter_data"
			},
			render:      function (option) {
				var self = this;
				var options = option || {};
				var name = self.model.get_type().toLowerCase();
				var template = _.template(jQuery('#table-listing-' + name).html());
				self.$el.html(template(_.extend(this.model.toJSON(), options)));

				jQuery("#filter-job-status").val(self.model.get('job_status'));
				jQuery("#translator_id").val(self.model.get('translator_id'));
				jQuery("#filter-job-lang-from").val(self.model.get('lang_from'));
				jQuery("#filter-job-lang-to").val(self.model.get('lang_to'));

				return self;
			},
			cleanup:     function () {
				var self = this;
				self.undelegateEvents();
				self.off();
				self.model.off(null, null, this);
				return self;
			},
			filter_data: function () {
				var self = this;
				var translator_id = jQuery("#translator_id").val() || "";
				var job_status = jQuery("#filter-job-status").val() || "";
				var lang_from = jQuery("#filter-job-lang-from").val() || "";
				var lang_to = jQuery("#filter-job-lang-to").val() || "";
				this.model.set_filter(translator_id, job_status, lang_from, lang_to);
				this.model.trigger('change');
				return self;
			}
		}
	);
}());