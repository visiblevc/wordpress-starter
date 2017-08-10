Translation_Jobs.listing.models.ListingItem = Backbone.Model.extend({

    get_type: function () {

        return this.get('type');
    },
    is_string: function () {

        return this.get_type() === 'String';
    }
});