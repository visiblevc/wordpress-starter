(function () {
	TaxonomyTranslation.views.NavView = Backbone.View.extend({

		template: WPML_core[ 'templates/taxonomy-translation/nav.html' ],
		model: TaxonomyTranslation.models.taxonomy,
		events: {
			"change .current-page": 'goToPage',
			"click .next-page": 'nextPage',
			"click .prev-page": 'prevPage',
			"click .first-page": 'firstPage',
			"click .last-page": 'lastPage'
		},
		initialize: function (data, options) {
			this.page = 1;
			this.perPage = options.perPage;
		},
		goToPage: function () {
			var self = this;
			var currentPageField = jQuery(".current-page");
			var page = currentPageField.val();

			if (page > 0 && page <= self.pages) {
				self.page = parseInt(page);
				self.trigger("newPage");
			} else {
				currentPageField.val(self.page);
			}

			return self;
		},
		nextPage: function(){
			var self = this;
			self.$el.find('.current-page').val(self.page + 1).change();
		},
		prevPage: function(){
			var self = this;
			self.$el.find('.current-page').val(self.page - 1).change();
		},
		firstPage: function(){
			var self = this;
			self.$el.find('.current-page').val(1).change();
		},
		lastPage: function(){
			var self = this;
			self.$el.find('.current-page').val(self.pages).change();
		},
		setCounts: function(){
			var self = this;
			var rows = TaxonomyTranslation.data.termRowsCollection.length;
			var displayedCount = TaxonomyTranslation.mainView.termRowsView.getDisplayCount();
			rows = displayedCount >= 0 ? displayedCount : rows;
			rows = rows ? rows : 0;
			self.rows = rows;
			self.pages = Math.ceil(rows / self.perPage);
		},
		render: function () {
			var self = this;
			self.setCounts();
			if (self.pages > 1) {
				self.$el.html(self.template({
					page: self.page,
					pages: self.pages,
					items: self.rows
				}));
				self.$el.show();
			} else {
				self.$el.hide();
			}

			return self;
		}
	});
})(TaxonomyTranslation);
