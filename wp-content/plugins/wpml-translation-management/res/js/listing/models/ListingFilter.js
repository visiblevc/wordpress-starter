Translation_Jobs.listing.models.ListingFilter = Backbone.Model.extend({
	initialize: function (options) {

	},
	defaults: {

	},
	get_type:   function () {
		return 'Filter';
	},
	set_filter: function(job_for, job_status, from_lang, to_lang){
		var self = this;
		self.set('translator_id', job_for, {silent: true});
		self.set('job_status', job_status, {silent: true});
		self.set('lang_from', from_lang, {silent: true});
		self.set('lang_to', to_lang, {silent: true});
		return self;
	}
});
