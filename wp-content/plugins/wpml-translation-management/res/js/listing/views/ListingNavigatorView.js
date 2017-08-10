/*globals jQuery, Backbone, Translation_Jobs, _ */
(function () {
    "use strict";
    Translation_Jobs.listing.views.ListingNavigatorView = Backbone.View.extend(
        {
            el: '.navigator',
            initialize: function (options) {
                var self = this;
                self.template = _.template(jQuery('#table-listing-' + self.model.get_type().toLowerCase()).html());

                self.options = options;
                self.$el.data('view', self);
            },
            events: {
                "click #icl_jobs_show_all": "update_pagination",
                "click .js-nav-prev-page": "prev_page",
                "click .js-nav-next-page": "next_page",
                "click .js-nav-next-page-arrow": "next_page",
                "click .js-nav-prev-page-arrow": "prev_page",
                "click .js-nav-before-prev-page": "before_prev_page",
                "click .js-nav-after-next-page": "after_next_page",
                "click .js-nav-first-page": "first_page",
                "click .js-nav-last-page": "last_page"
            },
            render: function (option) {

                var self = this, options = option || {};
                var html = jQuery(self.template(_.extend(self.model.toJSON(), options)));

                var items = self.model.get('items');
                var page = self.model.get('page');
                var pages = self.model.get('pages');
                var per_page = self.model.get('per_page');

                var icl_jobs_show_all = html.find("#icl_jobs_show_all");
                var nav_before_prev_page = html.find(".js-nav-before-prev-page");
                var nav_prev_page = html.find(".js-nav-prev-page");
                var nav_after_next_page = html.find(".js-nav-after-next-page");
                var nav_next_page = html.find(".js-nav-next-page");
                var nav_last_page = html.find(".js-nav-last-page");
                var nextPageArrow = html.find(".js-nav-next-page-arrow");
                var prevPageArrow = html.find(".js-nav-prev-page-arrow");
                var navFirstPage = html.find(".js-nav-first-page");
                var navRightDots = html.find(".js-nav-right-dots");
                var navLeftDots = html.find(".js-nav-left-dots");
                var elementsToHide = [];

                if (items <= self.model.defaults.per_page) {
                    elementsToHide = elementsToHide.concat([icl_jobs_show_all]);
                } else {
                    icl_jobs_show_all.text(icl_jobs_show_all.text().replace('%s', (self.model.get('show_all') ? self.model.defaults.per_page : items )));
                }

                if (pages <= 1 || items === 0) {
                    elementsToHide = elementsToHide.concat([
                        nav_prev_page,
                        nav_next_page,
                        prevPageArrow,
                        nextPageArrow,
                        nav_before_prev_page,
                        nav_after_next_page,
                        navFirstPage,
                        nav_last_page,
                        navRightDots,
                        navLeftDots
                    ]).concat(pages < 1 ? [html.find(".displaying-num")] : []);
                } else {
                    nav_before_prev_page.text(page - 2);
                    nav_prev_page.text(page - 1);
                    nav_after_next_page.text(page + 2);
                    nav_next_page.text(page + 1);
                    nav_last_page.text(pages);
                    html.find(".page-numbers-current").text(page);
                    if (page === pages) {
                        elementsToHide = elementsToHide.concat([nav_next_page, nextPageArrow]);
                    } else if (page === 1) {
                        elementsToHide = elementsToHide.concat([nav_prev_page, prevPageArrow]);
                    }
                    if (page + 1 >= pages) {
                        elementsToHide = elementsToHide.concat([nav_after_next_page]);
                    }
                    if (page < 3) {
                        elementsToHide = elementsToHide.concat([nav_before_prev_page]);
                    }
                    if (page + 3 >= pages) {
                        elementsToHide = elementsToHide.concat([navRightDots]);
                        if (page + 2 >= pages) {
                            elementsToHide = elementsToHide.concat([nav_last_page]);
                        }
                    }
                    if (page - 3 <= 1) {
                        elementsToHide = elementsToHide.concat([navLeftDots]);
                        if (page - 2 <= 1) {
                            elementsToHide = elementsToHide.concat([navFirstPage]);
                        }
                    }
                }

                elementsToHide.forEach(function (element) {
                    element.hide();
                });

                self.$el.html(html.html());

                return self;
            },
            update_pagination: function (e) {
                if (typeof e.preventDefault !== 'undefined') {
                    e.preventDefault();
                }
                this.model.display_count();
                return this;
            },
            next_page: function (e) {
                if (typeof e.preventDefault !== 'undefined') {
                    e.preventDefault();
                }

                this.model.to_page(this.model.get('page') + 1);

                return false;
            },
            prev_page: function (e) {
                if (typeof e.preventDefault !== 'undefined') {
                    e.preventDefault();
                }

                this.model.to_page(this.model.get('page') - 1);

                return false;
            },
            after_next_page: function (e) {
                if (typeof e.preventDefault !== 'undefined') {
                    e.preventDefault();
                }

                this.model.to_page(this.model.get('page') + 2);

                return false;
            },
            before_prev_page: function (e) {
                if (typeof e.preventDefault !== 'undefined') {
                    e.preventDefault();
                }

                this.model.to_page(this.model.get('page') - 2);

                return false;
            },
            first_page: function (e) {
                if (typeof e.preventDefault !== 'undefined') {
                    e.preventDefault();
                }
                this.model.to_page(1);

                return false;
            },
            last_page: function (e) {
                if (typeof e.preventDefault !== 'undefined') {
                    e.preventDefault();
                }
                this.model.to_page(this.model.get('pages'));

                return false;
            }
        }
    );
}());
