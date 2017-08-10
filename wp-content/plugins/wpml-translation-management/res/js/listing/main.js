/*jshint browser:true, devel:true */
/*global jQuery, Backbone, Translation_Jobs_settings, head, ajaxurl, _ */
var Translation_Jobs = Translation_Jobs || {};

Translation_Jobs.listing = {};
Translation_Jobs.listing.views = {};
Translation_Jobs.listing.models = {};
Translation_Jobs.listing.views.abstract = {};

/** @namespace Translation_Jobs_settings.TJ_JS */
/** @namespace Translation_Jobs_settings.TJ_JS.listing_lib_path */

Translation_Jobs_settings.TJ_JS.ns = head;
Translation_Jobs_settings.TJ_JS.listing_open = {
	1: true,
	2: true,
	3: true
};

Translation_Jobs_settings.TJ_JS.ns.js(
	"//cdnjs.cloudflare.com/ajax/libs/prototype/1.7.1.0/prototype.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "models/ListingItem.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "models/ListingItems.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "models/ListingGroup.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "models/ListingGroups.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "models/ListingTable.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "models/ListingNavigator.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "models/ListingFilter.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "views/abstract/CollectionView.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "views/ListingItemView.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "views/ListingGroupView.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "views/ListingGroupsView.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "views/ListingTableView.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "views/ListingFilterView.js",
	Translation_Jobs_settings.TJ_JS.listing_lib_path + "views/ListingNavigatorView.js"
);

(function () {
	"use strict";

	Translation_Jobs_settings.TJ_JS.ns.ready(
		function () {
			_.templateSettings.variable = "TJ";

			Translation_Jobs.listing_table = new Translation_Jobs.listing.models.ListingTable();
			Translation_Jobs.listing_table_view = new Translation_Jobs.listing.views.ListingTableView({model: Translation_Jobs.listing_table});

		}
	);
}(Translation_Jobs));