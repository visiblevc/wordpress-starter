/*jshint browser:true, devel:true */
/*global jQuery, Backbone, Translation_Jobs, head, ajaxurl, _ */
(function () {
    "use strict";

    Translation_Jobs.listing.views.ListingGroupsView = Translation_Jobs.listing.views.abstract.CollectionView.extend(
        {
            tagName: 'tbody',
            el: '#icl-translation-jobs',
            initialize: function (options) {
                var self = this;
                self.options = options;
                self.$el.data('view', self);
                Translation_Jobs.listing.views.abstract.CollectionView.prototype.initialize.call(self, options);
            },
            events: {
                "click #group-previous-jobs": "prev_page",
                "click #group-remaining-jobs": "next_page"
            },
            prev_page: function () {
                var navigator = Translation_Jobs.listing_table.get('Navigator');
                navigator.to_page(navigator.get('page') - 1 );
            },
            next_page: function () {
                var navigator = Translation_Jobs.listing_table.get('Navigator');
                navigator.to_page(navigator.get('page') + 1 );
            },
            render: function (option) {

                var self = this, options = _.extend({}, option);
                self.$el.find('tbody').remove();
                self._cleanBeforeRender(self.$el);
                self.options = options;
                self.$el.data('view', self);
                self.fragment = document.createDocumentFragment();
                self.appendModelElement();
                self.$el.append(self.fragment);

                return self;
            }
        }
    );
}(Translation_Jobs));
