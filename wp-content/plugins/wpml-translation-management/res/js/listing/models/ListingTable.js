/*jshint browser:true, devel:true */
/*global jQuery, Translation_Jobs, Backbone, ajaxurl, _ */

(function () {
	"use strict";

	Translation_Jobs.listing.models.ListingTable = Backbone.Model.extend(
		{
			url:                  ajaxurl,
			cache: {},
			Groups:               null,
			initialize:           function () {
				var self = this;

				self.set('loaded', false);

				if (!self.get('Navigator')) {
					self.set('Navigator', new Translation_Jobs.listing.models.ListingNavigator(), {silent: true});
				}

				if (!self.get('Filter')) {
					self.set(
						'Filter', new Translation_Jobs.listing.models.ListingFilter(
							{}, {
								parse: true
							}
						), {silent: true}
					);
				}
				self.fetch();
			},
			fetch:                function () {
				var self = this;

				var filter_object = {};
				self.group_data(self.get('Navigator').get('page'), self.get('Navigator').get('per_page'), filter_object);

				return self;
			},
			group_data:           function (page, page_size, filter_object) {

				var self = this;

				filter_object = filter_object || {};

				self.ajaxCall(
					{
						interval: {
							page:      page,
							page_size: page_size
						},
						filter:   {
							translator_id: filter_object.translator_id || "",
							status:        filter_object.job_status || "",
							lang_from:     filter_object.job_lang_from || "",
							lang_to:       filter_object.job_lang_to || ""
						}
					}
				);

				return self;
			},
			parse_data:           function (data) {
				var self = this;

				var current_groups = self.get('Groups');
				if (typeof  current_groups !== 'undefined') {
					current_groups.reset();
				}
				current_groups = new Translation_Jobs.listing.models.ListingGroups(data, {parse: true});
				self.set('Groups', current_groups, {silent: true});
				self.trigger('groups_ready');

				return self;
			},
			ajaxCall: function (options) {
				var self = this;
				var pagination_page = options.interval.page;
				var pagination_page_size = options.interval.page_size;
				var filter_lang_from = options.filter.lang_from;
				var filter_lang_to = options.filter.lang_to;
				var filter_translator_id = options.filter.translator_id;
				var filter_job_status = options.filter.status;
				var nonce = jQuery('#icl_get_jobs_table_data_nonce').val();
				var requestData =  {
							action: 'icl_get_jobs_table',
							icl_get_jobs_table_data_nonce: nonce,
							pagination_page: pagination_page,
							pagination_page_size: pagination_page_size,
							filter_lang_from: filter_lang_from,
							filter_lang_to: filter_lang_to,
							filter_translator_id: filter_translator_id,
							filter_job_status: filter_job_status
				};
				var hash = self.hash(JSON.stringify(requestData));
				var cache = self.get('cache');
				cache = typeof cache === 'undefined' ? {} : cache;
				if(cache.hasOwnProperty(hash) && typeof cache[hash] !== 'undefined'){
					self.successfulResponse(cache[hash], self);
                    cache = {};
					self.set('cache', cache, {silent: true});
				}else {
                    return jQuery.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: requestData,
						success: function (json) {
                            cache = {};
                            cache[hash] = json;
                            self.set('cache', cache, {silent: true});
                            self.successfulResponse(json, self);
                        }
                    });
                }
			},
			successfulResponse: function(json, self){

				var grouped_data;
				var item_count;
				var batch_metrics;
				/** @namespace json.data.Flat_Data */
				if (typeof json.data !== 'undefined') {
					grouped_data = json.data.Flat_Data;
					item_count = json.data.metrics.item_count;
					batch_metrics = json.data.metrics.batch_metrics;
				} else {
					grouped_data = [];
					item_count = 0;
					batch_metrics = [];
				}

				if (self.get('Navigator').get('items') !== item_count) {
					(self.get('Navigator')).item_count(item_count);
				}

				if (parseInt(item_count, 10) > 0) {
					self.set('loaded', true);
				} else {
					self.set('loaded', false);
				}

				var groups = [];

				/** @namespace json.data.metrics */
				/** @namespace json.data.metrics.batch_metrics */

				_.each(
					grouped_data, function (group, index) {
						if (group.length) {
							var data_item = {};
							var metrics = batch_metrics [index];
							data_item.display_from = metrics.display_from;
							data_item.overall_count = metrics.item_count;
							data_item.display_to = metrics.display_to;
							data_item.batch_name = metrics.batch_name;
							if (metrics.batch_url) {
								data_item.batch_url = metrics.batch_url;
							}
							if (metrics.batch_id) {
								data_item.tp_batch_id = metrics.batch_id;
							}
							data_item.in_active_ts = metrics.in_active_ts;
							data_item.notifications = metrics.notifications;
							data_item.batch_id = group[0].batch_id;
							data_item.last_update = metrics.last_update;
							data_item.kind = 'Group';
							data_item.items = group;
							/** @namespace metrics.status_array */
							data_item.statuses = metrics.status_array;
							data_item.languages = _.groupBy(group, 'lang_text');
							groups[index] = data_item;
						}
					}
				);
				self.parse_data(groups);

				return self;
			},
			cancelJobs: function (jobIDs) {
				var self = this;
				var nonce = jQuery('#icl_cancel_translation_jobs_nonce').val();
				jQuery.ajax(
					{
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'icl_cancel_translation_jobs',
							job_ids: jobIDs,
							_icl_nonce: nonce
						},
						success: function (response) {
							self.fetch();
						},
						error: function () {
						}
					}
				);
			},
			hash:  function(s){
				return s.split("").reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a;},0);
			}
		}
	);
}());
