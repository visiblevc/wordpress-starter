/*globals labels, ajaxurl, TaxonomyTranslation, Backbone, jQuery, _, WPML_Translate_taxonomy */

(function () {
	"use strict";
	
	TaxonomyTranslation.models.Taxonomy = Backbone.Model.extend({

		defaults: function () {
			return {
				name: false,
				taxonomy: false,
				terms: {},
				parents: {},
				termNames: {}
			};
		},

		initialize: function () {
			TaxonomyTranslation.data.termRowsCollection = new TaxonomyTranslation.collections.TermRows();
			this.setTaxonomy(this.get("taxonomy"));
		},

		setTaxonomy: function (taxonomy) {
			this.set("taxonomy", taxonomy, {silent: true});
			TaxonomyTranslation.data.termRowsCollection.reset();

			if (taxonomy !== undefined) {
				this.getTaxonomyTerms(taxonomy);
			} else {
				this.trigger('newTaxonomySet');
			}
		},

		getTaxonomyTerms: function (taxonomy) {
			var self = this;

			jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				data: {action: 'wpml_get_terms_and_labels_for_taxonomy_table', taxonomy: taxonomy},
				success: function (response) {
					var termsData = response.terms;
					var labelsData = response.taxLabelTranslations;

					if (response.defaultLanguage) {
						self.set('defaultLang', response.defaultLanguage);
					}

					if (labelsData) {
						TaxonomyTranslation.data.translatedTaxonomyLabels = labelsData;
						if (labelsData.st_default_lang) {
							self.set('stDefaultLang', labelsData.st_default_lang);
						}
					} else {
						TaxonomyTranslation.data.translatedTaxonomyLabels = false;
					}

					if (response.bottomContent) {
						self.set('bottomContent', response.bottomContent);
					}

					if (termsData) {
						self.processData(termsData);
					} else {
						self.trigger('newTaxonomySet');
					}
				}
			});
		},

		processData: function (termsData) {

			var parentTermIDs = [],
				parents = {},
				termNames = {};

			_.each(termsData, function (tridGroup) {
				var termsObject = {};
				_.each(TaxonomyTranslation.data.activeLanguages, function (lang, code) {
					var term;
					if (tridGroup[code] !== undefined && tridGroup[code].term_taxonomy_id) {
						term = new TaxonomyTranslation.models.Term(tridGroup[code]);
						var parent = term.get("parent");
						if (parent > 0) {
							parentTermIDs.push(parent);
						}
						termsObject[code] = term;
						termNames[tridGroup[code].term_taxonomy_id] = tridGroup[code].name;
					}
				});
				TaxonomyTranslation.data.termRowsCollection.add(new TaxonomyTranslation.models.TermRow({
					trid: tridGroup.trid,
					terms: termsObject
				}));
			});

			_.each(termsData, function (tridGroup) {
				_.each(TaxonomyTranslation.data.activeLanguages, function (lang, code) {
					if (tridGroup[code] !== undefined && parentTermIDs.indexOf(tridGroup[code].term_id) !== -1) {
						parents[tridGroup[code].term_id] = tridGroup[code].name;
					}

				});
			});

			this.set("parents", parents, {silent: true});
			this.set("termNames", termNames, {silent: true});

			this.trigger('newTaxonomySet');
		},

		getOriginalTerm: function ( trid ) {
			var row = TaxonomyTranslation.data.termRowsCollection.get(trid);
			var originalTerm = null;
			var terms = row.get("terms");
			_.each( terms, function ( term ) {
				if ( term.get( "source_language_code") === null ) {
					originalTerm = term;
				}
			});
			return originalTerm;
		},
		getOriginalTermMeta: function( trid ) {
			var originalTerm = this.getOriginalTerm(trid),
				term_metas = [],
				original_meta_data;

			original_meta_data = originalTerm.get('meta_data');
			_.each( original_meta_data, function ( meta_data, meta_key ) {
				term_metas.push({
					'meta_key': meta_key,
					'meta_value': meta_data
				});
			});

			return term_metas;
		},
		getTermName: function (termID) {

			var res = "";
			if (termID > 0) {
				var termNames = this.get("termNames");
				res = termID in termNames ? termNames[termID] : "";
			}

			return res;
		},
		saveLabel: function (singular, plural, lang) {
			var self = this;

			jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				data: {
					action: 'wpml_tt_save_labels_translation',
					_icl_nonce: labels.wpml_tt_save_labels_translation_nonce,
					singular: singular,
					plural: plural,
					taxonomy_language_code: lang,
					taxonomy: self.get('taxonomy')
				},
				success: function (response) {
					if (response.data) {
						var newLabelData = response.data;
						if (newLabelData.singular && newLabelData.general && newLabelData.lang) {
							TaxonomyTranslation.data.translatedTaxonomyLabels[newLabelData.lang] = {
								singular: newLabelData.singular,
								general: newLabelData.general
							};
							WPML_Translate_taxonomy.callbacks.fire('wpml_tt_save_term_translation', self.get('taxonomy'));
							self.trigger("labelTranslationSaved");

							return self;
						}
					}
					self.trigger("saveFailed");
					return self;
				},
				error: function () {
					self.trigger("saveFailed");
					return self;
				}
			});
		},
		isHierarchical: function(){
			var self = this;

			return TaxonomyTranslation.data.taxonomies[self.get("taxonomy")].hierarchical;
		},
		loadSyncData: function (lang) {
			var self = this;

			jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				data: {
					action: 'wpml_tt_sync_hierarchy_preview',
					_icl_nonce: labels.wpml_tt_sync_hierarchy_nonce,
					taxonomy: self.get('taxonomy'),
					ref_lang: lang
				},
				success: function (response) {
					TaxonomyTranslation.data.syncData = response.data;
					self.trigger('syncDataLoaded');
				}
			});
		},
		doSync: function (lang) {
			var self = this;
			var tax = self.get('taxonomy');
			jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				data: {
					action: 'wpml_tt_sync_hierarchy_save',
					_icl_nonce: labels.wpml_tt_sync_hierarchy_nonce,
					taxonomy: tax,
					ref_lang: lang
				},
				success: function (response) {
					TaxonomyTranslation.data.syncData = response.data;
					self.setTaxonomy(tax);
				}
			});
		}
	});
})(TaxonomyTranslation);
