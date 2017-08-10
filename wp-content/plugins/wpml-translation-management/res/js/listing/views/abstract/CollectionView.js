Translation_Jobs.listing.views.abstract.CollectionView = Backbone.View.extend({
    el: ".groups",
    appendModelElement: function () {
        var self = this, view, el, options;

        self.models = typeof self.models !== 'undefined' ? self.models : [];
        self.models.each(function (model) {
            options = {model: model};
            view = new Translation_Jobs.listing.views.ListingGroupView(options);
            el = view.render(options).el;
            jQuery(el).undelegate('#group-previous-jobs', 'click');
            jQuery(el).undelegate('#group-remaining-jobs', 'click');
            self.fragment.appendChild(el);
        });

        return self;
    },
    /*
     ** remove all the children view to clean event queue
     */
    _cleanBeforeRender: function (el) {
        var self = this;

        el.find('tr', 'tbody').each(function (i, v) {
            if (jQuery(v).data('view')) {
                self._cleanBeforeRender(jQuery(v));
                jQuery(v).data('view').remove();
            }
        });
    }
});
