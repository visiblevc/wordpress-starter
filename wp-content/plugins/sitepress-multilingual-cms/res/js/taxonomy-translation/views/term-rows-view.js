(function () {
	TaxonomyTranslation.views.TermRowsView = Backbone.View.extend({

		tagName: 'tbody',
		collection: TaxonomyTranslation.data.termRowsCollection,
		rowViews: [],
		start: 0,
		end: 10,
		count: -1,
		initialize: function (data, options) {
			var self = this;
			self.end = options.end;
			self.start = options.start;
		},
		getDisplayedRows: function () {
			var self = this;
			var displayedRows = self.collection;

			if (!displayedRows) {
				self.count = -1;
				return false;
			}

			if (TaxonomyTranslation.mainView.mode === 'sync') {
				displayedRows = displayedRows.filter(function (row) {
					"use strict";
					return row.unSyncFilter();
				});
			}

			var parentFilter = TaxonomyTranslation.mainView.filterView.parent ? TaxonomyTranslation.mainView.filterView.parent : false;

			if (parentFilter) {
				displayedRows = displayedRows.filter(function (row) {
					return row.parentOf(parentFilter);
				});
			}

			var untranslatedFilter = TaxonomyTranslation.mainView.filterView.untranslated ? TaxonomyTranslation.mainView.filterView.untranslated : false;

			if (untranslatedFilter) {
				displayedRows = displayedRows.filter(function (row) {
					return !row.allTermsTranslated();
				});
			}

			var langFilter = TaxonomyTranslation.mainView.filterView.lang && TaxonomyTranslation.mainView.filterView.lang !== 'all' ? TaxonomyTranslation.mainView.filterView.lang : false;

			if (langFilter && langFilter != 'all' && (untranslatedFilter || parentFilter)) {
				displayedRows = displayedRows.filter(function (row) {
					return !row.translatedIn(langFilter);
				});
			}

			var searchFilter = false;

			if (TaxonomyTranslation.mainView.filterView.search && TaxonomyTranslation.mainView.filterView.search !== '') {
				searchFilter = TaxonomyTranslation.mainView.filterView.search;
			}

			if (searchFilter) {
				displayedRows = displayedRows.filter(function (row) {
					if (langFilter && langFilter !== 'all') {
						return row.matchesInLang(searchFilter, langFilter);
					} else {
						return row.matches(searchFilter);
					}
				});
			}

			self.count = displayedRows.length;

			return displayedRows;
		},
		getDisplayCount: function(){
			return this.count;
		},
		render: function () {

			var self = this,
				output = document.createDocumentFragment(),
				displayedRows = self.getDisplayedRows();
				
			self.rowViews = [];

			if ( displayedRows && displayedRows.length > 0 ) {
				displayedRows = displayedRows.slice(self.start, self.end);

				displayedRows.forEach(function (row) {
					var newView = new TaxonomyTranslation.views.TermRowView({model: row });
					self.rowViews.push(newView);
					output.appendChild(newView.render().el);
					newView.delegateEvents();
					
				});
				self.$el.html(output);
			} else {
				var taxonomy = TaxonomyTranslation.classes.taxonomy.get("taxonomy"),
					taxonomyPluralLabel = TaxonomyTranslation.data.taxonomies[taxonomy].label,
					message = labels.noTermsFound.replace( '%taxonomy%', taxonomyPluralLabel );
				
				self.$el.html(
					WPML_core[ 'templates/taxonomy-translation/no-terms-found.html' ] ({
						message: message
					})
					);
			}

			return self;

		}
	});
})(TaxonomyTranslation);
