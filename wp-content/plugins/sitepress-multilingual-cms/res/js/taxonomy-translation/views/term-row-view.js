/*globals _, TaxonomyTranslation, document, Backbone, jQuery */

(function () {
	"use strict";

	TaxonomyTranslation.views.TermRowView = Backbone.View.extend({

		tagName: "tr",
		model: TaxonomyTranslation.models.TermRow,
		termViews: {},
		className: '',
		events: {
			'click .js-copy-to-all-langs': 'copyToAllLangs'
		},

		initialize: function () {
			var self = this;

			self.listenTo(TaxonomyTranslation.classes.taxonomy, 'syncDataLoaded', self.maybeHide);
		},
		maybeHide: function () {
			var self = this;
			var visible = false;
			var terms = self.model.get("terms");
			_.each(TaxonomyTranslation.data.syncData, function (correction) {
				_.each(terms, function (term) {
					if (correction.translated_id == term.get("term_taxonomy_id")) {
						visible = true;
					}
				});
			});
			if (visible) {
				self.$el.show();
			} else {
				self.$el.hide();
			}
		},

		render: function () {
			var termsFragments = {};
			var self  = this;
			var langs = TaxonomyTranslation.util.langCodes;
			var terms = self.model.get("terms");
			var originalTerm = null;
			
			_.each(langs, function (lang) {
				var term = terms[lang];
				if (term === undefined) {
					term = new TaxonomyTranslation.models.Term({language_code: lang, trid: self.model.get("trid")});
					terms[lang] = term;
					self.model.set("terms", terms, {silent: true});
				}
				if ( term.isOriginal() ) {
					originalTerm = term;
				}
				var newView = new TaxonomyTranslation.views.TermView({model: term});
				self.termViews[lang] = newView;
				if (TaxonomyTranslation.mainView.mode === 'sync') {
					termsFragments[lang] = newView.loadSyncData().el;
				} else {
					termsFragments[lang] = newView.render().el;
				}
			});

			if ( originalTerm ) {
				var newRowFragment = document.createDocumentFragment();
	
				if ( TaxonomyTranslation.mainView.mode !== 'sync' ) {
					var originalView = new TaxonomyTranslation.views.TermOriginalView({model: originalTerm });
					newRowFragment.appendChild( originalView.render().el );
				
					var newRowLangs = document.createElement( 'td' );
					jQuery( newRowLangs ).addClass( 'wpml-col-languages' );
					_.each(langs, function(lang){
						newRowLangs.appendChild(termsFragments[lang]);
					});
		
					newRowFragment.appendChild( newRowLangs );
				} else {
					_.each(langs, function(lang){
						var newRowTD = document.createElement( 'td' );
						newRowTD.appendChild(termsFragments[lang]);
						newRowFragment.appendChild( newRowTD );
					});
				}
				self.$el.html(newRowFragment);
			}
			
			return self;

		},
		copyToAllLangs: function () {
			var self = this;
			TaxonomyTranslation.classes.copyAllPopUpView = new TaxonomyTranslation.views.CopyAllPopUpView( { model: self.model } );
			TaxonomyTranslation.classes.copyAllPopUpView.open( );
			
		}
	});
}(TaxonomyTranslation));

