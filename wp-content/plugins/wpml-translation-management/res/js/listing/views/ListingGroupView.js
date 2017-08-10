/*jshint browser:true, devel:true */
/*global jQuery, Backbone, Translation_Jobs, head, ajaxurl, _ */
(function () {
	"use strict";

	Translation_Jobs.listing.views.ListingGroupView = Backbone.View.extend(
		{
			tagName          : 'tbody',
			initialize       : function (options) {
				var self = this;

				_.bindAll(self, 'render', 'afterRender');

				self.render = _.wrap(
					self.render, function (render, args) {
						render(args);
						_.defer(self.afterRender, _.bind(self.afterRender, self));
						return self;
					}
				);

				self.options = options;
				self.$el.data('view', self);
				self.$el.addClass('listing-page-table-list');

			},
			render           : function (option) {
				var self = this, options = option, groups_view = null, doc = document.createDocumentFragment();

				self.template = _.template(jQuery('#table-listing-group').html());
                var items = self.model.get('items');
                items = items ? items : [];
                options.how_many = items.length;
                var from = self.model.get('display_from');
                var to = from + options.how_many - 1;
				options.how_many_overall = self.model.get('overall_count');
				options.show_out_of = 'none';

				if (options.how_many < options.how_many_overall) {
					options.how_many = to === from ? ' 1 ' : from.toString() + '-' + to.toString();
					options.show_out_of = '';
				}

				options.show_remaining = to < options.how_many_overall ? '' : 'none';
				options.show_previous = from > 1 ? '' : 'none';
				var url = self.model.get('batch_url');
				if (url) {
					var batch_name_link = _.template(jQuery('#batch-name-link-template').html());
					self.model.set('batch_name', batch_name_link({name: self.model.get('batch_name'), url : url}));
				}

				self.$el.html(self.template(_.extend(options, self.model.toJSON())));

				if (items) {
					items.each(
						function (item_model) {
							var fragment = document.createDocumentFragment();
							groups_view = new Translation_Jobs.listing.views.ListingItemView(_.extend({}, {model: item_model, group: self.model.get('id')}));
							fragment.appendChild(groups_view.el);
							doc.appendChild(fragment);
						}
					);
					self.$el.append(doc);
					self.open_group();
				}

				return self;
			},
			afterRender      : function () {

			},
			events           : {
				"click .group-expand"  : "open_group_click",
				"click .group-collapse": "close_group_click",
				'click .group-check': "groupCheck"
			},
			open_group_click : function (e) {
				var self = this;
				e.preventDefault();
				self.open_group();
			},
			close_group_click: function (e) {
				e.preventDefault();
				var self = this;
				self.close_group();
			},
			open_group       : function () {
				var self = this;
				self.$el.find(".group-collapse").show();
				self.$el.find(".group-expand").hide();
				self.$el.find('tr').not(':first').show(
					300, function () {
						self.open = true;
					}
				);
				self.$el.find(".listing-heading-summary").hide();
			},
			close_group      : function () {
				var self = this;
				self.$el.find(".group-collapse").hide();
				self.$el.find(".group-expand").show();
				self.$el.find('tr').not(':first').hide(
					300, function () {
						self.open = false;
					}
				);
				self.$el.find(".listing-heading-summary").show();
			},
			groupCheck: function (e) {
				var self = this;
				var btn = self.$el.find('.group-check');
				var batchID = btn.siblings('input.group-check-batch-id');
				var batchGroup = btn.closest('.listing-heading-inner-wrap');
				var ajaxLoader = jQuery(icl_ajxloaderimg).insertBefore(btn);
				var ajaxAction = btn.data('action');
				var ajaxNonce = btn.data('nonce');
				var syncSentText = btn.data('message-sent');
				var requestSendingText = btn.data('message-request-sending');
				var requestSentText = btn.data('message-request-sent');
				var messageWrap = batchGroup.find(".wpml_tp_sync_status");

				e.preventDefault();

				btn.prop({
					disabled: true,
					value: requestSendingText
				});

				jQuery.ajax({
					type:     "POST",
					url:      ajaxurl,
					data:     {
						'action':   ajaxAction,
						'nonce':    ajaxNonce,
						'batch_id': batchID.val()
					},
					success:  function (response) {
						if (response.success) {
							messageWrap.text(syncSentText);
						} else {
							messageWrap.text(response.data);
						}
					},
					complete: function () {
						ajaxLoader.remove();
						messageWrap.show().delay(5000).fadeOut('slow');
						btn.prop({value: requestSentText});
					}
				});
			}
		}
	);
}());
