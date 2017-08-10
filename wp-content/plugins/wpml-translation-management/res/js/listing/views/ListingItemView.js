/*jshint browser:true, devel:true */
/*global jQuery, Backbone, Translation_Jobs, head, ajaxurl, _ */
(function () {
    "use strict";

    Translation_Jobs.listing.views.ListingItemView = Backbone.View.extend(
        {
            tagName: 'tr',
            initialize: function (options) {
                var self = this;

                _.bindAll(self, 'render');

                self.render = _.wrap(
                    self.render, function (render, args) {
                        render(args);
                        return self;
                    }
                );

                self.options = options;
                self.$el.data('view', self);

                self.render(options);
            },
            render: function (option) {
                var self = this, options = option || {};

                var name = self.model.get_type().toLowerCase();
                self.template = _.template(jQuery('#table-listing-' + name).html());

				self.$el.hide();
				self.$el.addClass('item');
				self.$el.html(self.template(_.extend(self.model.toJSON(), options)));

				return self;
			},
			afterRender:    function () {
				var self = this;
				self.manage_tooltip();
				self.highlight();
			},
			manage_tooltip: function () {

			},

			manageSelection: function () {

			},
			highlight:       function () {
			}
		}
	);
}());
