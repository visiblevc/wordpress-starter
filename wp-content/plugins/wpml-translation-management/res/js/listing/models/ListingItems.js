Translation_Jobs.listing.models.ListingItems = Backbone.Collection.extend({
    model: Translation_Jobs.listing.models.ListingItem,
    reverseSortDirection: false,
    initialize: function () {
        var self = this;

        self.sortKey = 'id';
        self.reverseSortDirection = false;
    },
    parse: function (data) {
        if (_.isObject(data)) {
            data = _.toArray(data);
        }
        return data;
    }
});