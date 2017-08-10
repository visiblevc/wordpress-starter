/*globals WPML_core, TaxonomyTranslation, Backbone, labels, jQuery, document */

(function () {
	"use strict";

	TaxonomyTranslation.views.TaxonomyView = Backbone.View.extend({

		el: "#taxonomy-translation",
		model: TaxonomyTranslation.models.Taxonomy,
		tag: "div",
		termRowsView: {},
		mode: 'translate',
		initialMode: 'translate',
		perPage: 10,
		events: {
			"click #term-table-sync-header": "setToSync",
			"click #term-table-header": "setToTranslate",
			"click #tax-apply": "doSync"
		},
		syncedLabel: labels.hieraAlreadySynced,
		initialize: function (model, options) {
			var self = this;
			self.perPage = jQuery( '#wpml_tt_taxonomy_translation_wrap' ).data( 'items_per_page' );
			self.initialMode = options.sync === true ? 'sync' : 'translate';
			self.navView = new TaxonomyTranslation.views.NavView({model: self.model}, {perPage: self.perPage});
			self.filterView = new TaxonomyTranslation.views.FilterView({model: self.model});
			self.listenTo(self.filterView, 'updatedFilter', function () {
				self.navView.page = 1;
				self.renderRows();
			});
			self.termTableView = new TaxonomyTranslation.views.TableView({model: self.model}, {type: "terms"});
			self.labelTableView = new TaxonomyTranslation.views.TableView({model: self.model}, {type: "labels"});
			self.termRowsView = new TaxonomyTranslation.views.TermRowsView({collection: TaxonomyTranslation.data.termRowsCollection}, {
				start: 0,
				end: self.perPage
			});
			self.listenTo(self.model, 'newTaxonomySet', self.renderNewTaxonomy);
			self.listenTo(self.model, 'syncDataLoaded', self.renderNewTaxonomy);

			return self;
		},
		changeMode: function (mode) {
			var self = this;
			self.mode = mode === 'translate' || ( mode === 'sync' && self.model.isHierarchical() ) ? mode : 'translate';
			self.navView.off();
			self.navView = new TaxonomyTranslation.views.NavView({model: self.model}, {perPage: self.perPage});
			self.listenTo(self.navView, 'newPage', self.render);

			if (self.mode === "sync") {
				self.model.loadSyncData(self.filterView.selectedLang());
			} else {
				self.renderRows();
				self.render();
			}
			self.model.trigger('modeChanged');

			self.syncedLabel = labels.hieraAlreadySynced;
			
			return self;
		},
		setToTranslate: function () {
			var self = this;
			if (self.mode !== 'translate') {
				self.changeMode('translate');
			}

			return self;
		},
		setToSync: function () {
			var self = this;
			if (self.mode !== 'sync') {
				self.changeMode('sync');
				TaxonomyTranslation.mainView.showLoadingSpinner();
			}

			return self;
		},
		setLabels: function () {
			var self = this,
				tax = self.model.get("taxonomy"),
				taxonomyPluralLabel = TaxonomyTranslation.data.taxonomies[tax].label,
				taxonomySingularLabel = TaxonomyTranslation.data.taxonomies[tax].singularLabel;
				
			self.headerTerms = labels.translate.replace( '%taxonomy%', taxonomySingularLabel );
			self.summaryTerms = labels.summaryTerms.replace( '%taxonomy%', '<strong>' + taxonomyPluralLabel + '</strong>' );
			self.labelSummary = labels.summaryLabels.replace( '%taxonomy%', '<strong>' + taxonomySingularLabel + '</strong>' );

			return self;
		},
		renderRows: function () {
			var self = this;
			if (TaxonomyTranslation.data.termRowsCollection.length > 0) {
				self.termRowsView.start = (self.navView.page - 1 ) * self.perPage;
				self.termRowsView.end = self.termRowsView.start + self.perPage;
				var termRowsFragment = self.termRowsView.render().el;
				jQuery("#tax-table-terms").first('tbody').append(termRowsFragment);
			}
			self.navView.render();

			return self;
		},
		renderNewTaxonomy: function(){
			var self = this;
			self.navView.off();
			self.navView = undefined;
			self.navView = new TaxonomyTranslation.views.NavView({model: self.model}, {perPage: self.perPage});
			this.listenTo(this.navView, 'newPage', this.render);
			self.renderRows();
			self.render();
			if (self.initialMode === 'sync') {
				self.initialMode = false;
				self.setToSync();
			}
			return self;
		},
		getMainFragment: function () {
			var self = this;

			var mainFragment = document.createElement("div"),
				mainTemplate = WPML_core[ 'templates/taxonomy-translation/taxonomy-main-wrap.html' ],
				tabsTemplate = WPML_core[ 'templates/taxonomy-translation/tabs.html' ],
				taxonomy = TaxonomyTranslation.data.taxonomies[ self.model.get( "taxonomy" ) ],
				htmlTabs = tabsTemplate( {
					taxonomy: taxonomy,
					headerTerms: self.headerTerms,
					syncLabel: labels.Synchronize,
					mode: self.mode
				}),
				hasContent = self.termRowsView && self.termRowsView.getDisplayCount() !== 0,
				htmlMain = mainTemplate({
					taxonomy: taxonomy,
					langs: TaxonomyTranslation.data.activeLanguages,
					summaryTerms: self.summaryTerms,
					labelSummary: self.labelSummary,
					mode: self.mode,
					hasContent: hasContent,
					labelSynced: self.syncedLabel
				});

			mainFragment.innerHTML = htmlTabs + htmlMain;

			mainFragment = self.addMainElements( mainFragment, hasContent );

			return mainFragment;
		},
		addMainElements: function ( mainFragment, hasContent ) {
			var self = this;
			self.filterFragment = self.filterFragment ? self.filterFragment : self.filterView.render().el;
			mainFragment.querySelector("#wpml-taxonomy-translation-filters").appendChild(self.filterFragment);
			if ( hasContent || self.mode === 'translate' ) {
				var termTableFragment = self.termTableView.render().el;
				mainFragment.querySelector("#wpml-taxonomy-translation-terms-table").appendChild(termTableFragment);
				mainFragment = self.addTableRows(mainFragment);
			}

			var bottomContent = self.model.get( "bottomContent" );
			if( typeof bottomContent != 'undefined' ){
				mainFragment.appendChild( jQuery( bottomContent )[0] );
			}

			return mainFragment;
		},
		render: function () {
			var self = this;

			self.setLabels();
			var renderedFragment = document.createDocumentFragment();
			var mainFragment = self.getMainFragment();
			renderedFragment.appendChild(mainFragment);

			if (TaxonomyTranslation.data.termRowsCollection.length > self.perPage &&
					renderedFragment.querySelector("#wpml-taxonomy-translation-terms-nav")) {
				var navFragment = self.navView.render().el;
				renderedFragment.querySelector("#wpml-taxonomy-translation-terms-nav").appendChild(navFragment);
			}
			self.addLabelTranslation(mainFragment, renderedFragment);
			self.$el.html(renderedFragment);
			jQuery(".icl_tt_label").on("click", self.openPopUPLabel);
			self.showLoadingSpinner( false );
			self.isRendered = true;
			self.filterView.delegateEvents();
			self.delegateEvents();
			self.maybeHideHeader();
			jQuery('.icl_tt_main_bottom').show();

			var loading = jQuery( '.wpml-loading-taxonomy' );
			if ( loading.length ) {
				loading.hide();
				var loaded = jQuery( '.wpml_taxonomy_loaded' );
				if ( loaded.length ) {
					loaded.show();
					loaded.parent().children( '.wpml-loading-taxonomy' ).remove();
				}
				
			}
			
			return self;
		},
		addLabelTranslation: function (mainFragment, renderedFragment) {
			var self = this;

			if (TaxonomyTranslation.data.translatedTaxonomyLabels && self.mode !== 'sync') {
				var labelTableFragment = self.labelTableView.render().el;
				mainFragment.querySelector("#wpml-taxonomy-translation-labels-table").appendChild(labelTableFragment);
				if (renderedFragment.querySelector("#tax-table-labels")) {
					var labelRowFragment = new TaxonomyTranslation.views.LabelRowView(({model: self.model})).render().el;
					mainFragment.querySelector("#tax-table-labels").appendChild(labelRowFragment);
				}
			}

			return mainFragment;
		},
		addTableRows: function (mainFragment) {
			var self = this;
			var termRowsFragment;

			self.termRowsView.start = (self.navView.page - 1 ) * self.perPage;
			self.termRowsView.end = self.termRowsView.start + self.perPage;
			termRowsFragment = self.termRowsView.render().el;
			mainFragment.querySelector("#tax-table-terms").appendChild(termRowsFragment);

			return mainFragment;
		},
		/**
		 * Used by WCML to hide the controls for changing the taxonomy
		 */
		maybeHideHeader: function () {
			var taxonomySwitcher = jQuery("#icl_tt_tax_switch");
			var potentialHiddenSelectInput = jQuery('#tax-selector-hidden');
			var potentialHiddenTaxInput = jQuery('#tax-preselected');
			if (potentialHiddenSelectInput.length !== 0 &&
					potentialHiddenSelectInput.val() &&
					potentialHiddenTaxInput.length !== 0 &&
					potentialHiddenTaxInput.val()) {
				taxonomySwitcher.closest('label').hide();
				jQuery('[id="term-table-summary"]').hide();
			}
		},
		selectTaxonomy: function () {
			var self = this,
				tax = jQuery("#icl_tt_tax_switch").val();
				
			if (tax !== undefined && tax !== self.model.get("taxonomy")) {
				self.mode = 'translate';
				self.model.setTaxonomy(tax);
			}
		},
		doSync: function () {
			var self = this;
			
			self.$el.find( '#tax-apply' ).prop( 'disabled', true );
			TaxonomyTranslation.mainView.model.doSync( self.$el.find("#in-lang").val() );
			self.syncedLabel = labels.hieraSynced;
		},
		showLoadingSpinner: function( state ) {
			if ( state === undefined || state ) {
				jQuery('.wpml-loading-taxonomy').show();
			} else {
				jQuery('.wpml-loading-taxonomy').hide();
			}
		}

	});
})(TaxonomyTranslation);
