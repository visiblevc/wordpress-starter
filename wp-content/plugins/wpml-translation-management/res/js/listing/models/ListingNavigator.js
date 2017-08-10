/*globals Backbone, Translation_Jobs, _ */
(function () {
	"use strict";

	Translation_Jobs.listing.models.ListingNavigator = Backbone.Model.extend(
		{
            initialize: function () {
                var self = this;

                _.bindAll(self, 'setup_pagination');
                self.bind('per_page_change', this.setup_pagination);
                self.bind('item_count_change', this.setup_pagination);
                var pageFromGet = self.pageFromGet();
                self.set('page', pageFromGet, {silent: true});

                return self;
            },
			defaults:         {
				show_all: false,
				per_page: 10,
				page:     1,
				items:    0
			},
			get_type:         function () {
				return 'Navigator';
			},
            pageFromGet: function () {
                var queryString = window.location.search.substring(1);
                var match = queryString.match(/&pagination=(\d+)/);

                return (match !== null && match.length > 1 && match[1] > 0) ? parseInt(match[1], 10) : 1;
            },
            to_page:          function (page) {
                if (page >= Math.min(this.get('pages'), 1) && page <= this.get('pages')) {
                    this.set('page', page, {silent: true});
                    var newPagination = '&pagination=' + page.toString();
                    var newURL = window.location.href.indexOf('&pagination=') === -1 ? window.location.href + newPagination : window.location.href.replace(/&pagination=(.+)/, newPagination);
                    window.history.pushState({}, '', newURL);
                    this.trigger('page_change');
                }
			},
			display_count:    function () {
				if (this.get('per_page') > this.defaults.per_page) {
					this.set('per_page', this.defaults.per_page, {silent: true});
					this.set('show_all', false, {silent: true});
					this.trigger('per_page_change');
					return;
				} else {
					this.set('per_page', this.get('items'), {silent: true});
					this.set('show_all', true, {silent: true});
				}
				this.trigger('per_page_change');
			},
			item_count:       function (item_count) {
				this.set('items', item_count, {silent: true});
				this.trigger('item_count_change');
			},
			setup_pagination: function () {
				this.set('pages', Math.ceil(this.get('items') / this.get('per_page')), {silent: true});
				if (this.get('pages') < this.get('page')) {
					this.set('page', this.get('pages'), {silent: true});
					this.trigger('pagination_changed');
				} else if (this.get('page') < 1 && this.get('pages') > 0) {
					this.set('page', 1, {silent: true});
					this.trigger('pagination_changed');
				}
			}
		}
	);
}());
