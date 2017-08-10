Translation_Jobs.listing.models.ListingGroups = Backbone.Collection.extend({

	model: Translation_Jobs.listing.models.ListingGroup,

	parse:function(data) {
		return data;
	}
});
