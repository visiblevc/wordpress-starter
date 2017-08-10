/*globals labels */

(function () {
	TaxonomyTranslation.views.TableView = Backbone.View.extend({

		template: WPML_core[ 'templates/taxonomy-translation/table.html' ],
		tag: 'div',
		termsView: {},

		model: TaxonomyTranslation.models.Taxonomy,

		initialize: function ( data, options ) {
			this.type = options.type;
		},

		render: function () {

			if ( ! TaxonomyTranslation.classes.taxonomy.get( "taxonomy" ) ) {
				return false;
			}

			var self = this,
				langs = TaxonomyTranslation.data.activeLanguages,
				count = self.isTermTable() ? TaxonomyTranslation.data.termRowsCollection.length : 1,
				tax = self.model.get( 'taxonomy' ),
				firstColumnHeading = self.isTermTable() ? labels.firstColumnHeading.replace( '%taxonomy%', TaxonomyTranslation.data.taxonomies[ tax ].singularLabel ) : '';

			this.$el.html(self.template({
				langs: langs,
				tableType: self.type,
				count: count,
				firstColumnHeading: firstColumnHeading,
				mode: TaxonomyTranslation.mainView.mode
			}));

			return self;
		},
		isTermTable: function () {
			return this.type === 'terms';
		},
		clear: function () {

		}

	});
})(TaxonomyTranslation);


