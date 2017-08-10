Translation_Jobs.listing.models.ListingGroup = Backbone.Model.extend({
	parse: function (data) {
		data.items = new Translation_Jobs.listing.models.ListingItems(data.items, {parse:true});

		return data;
	}
});