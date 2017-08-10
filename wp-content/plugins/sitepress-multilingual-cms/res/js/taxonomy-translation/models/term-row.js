/*globals TaxonomyTranslation, _, Backbone */

(function () {
	"use strict";

	TaxonomyTranslation.models.TermRow = Backbone.Model.extend({

		defaults: function () {
			return {
				terms: {},
				trid: false,
				allTranslated: false,
				parents: {}
			};
		},

		idAttribute: "trid",

		initialize: function (data, options) {
			var self = this;
			self.updateAllTranslated();
			var parents = {};
			_.each(data.terms, function (term, lang) {
				parents[lang] = term.get("parent");
			});

			self.set("parents", parents);
		},

		parentOf: function (parentID) {
			var self = this;
			var parents = self.get("parents");
			var res = false;
			_.each(parents, function (parent, lang) {
				if (parent == parentID) {
					res = true;
					return res;
				}
			});

			return res;
		},

		add: function (term) {

			if (!this.get("trid") && term.get("trid")) {
				this.set("trid", term.get("trid"), {silent: true});
			}

			if (term.get("trid") == this.get("trid") && term.get("language_code") && term.get("name")) {
				var terms = this.get("terms");
				terms[term.get("language_code")] = term;
				this.set("terms", terms, {silent: true});
			}
			this.updateAllTranslated();
		},

		updateAllTranslated: function () {
			var self = this;
			var terms = self.get("terms");
			self.set("allTranslated", true, {silent: true});
			_.each(TaxonomyTranslation.util.langCodes, function (lang) {
				if (terms === undefined || terms[lang] === undefined || !terms[lang].get("name")) {
					self.set("allTranslated", false, {silent: true});
				}
			});
			return self;
		},

		allTermsTranslated: function () {
			this.updateAllTranslated();
			return this.get("allTranslated");
		},
		translatedIn: function (lang) {
			var self = this;
			var terms = self.get("terms");
			var res = true;
			if (terms === undefined || terms[lang] === undefined || !terms[lang].get("name")) {
				res = false;
			}
			return res;
		},
		matches: function (search) {
			var self = this;
			var res = false;
			_.each(TaxonomyTranslation.util.langCodes, function (lang) {
				if (self.matchesInLang(search, lang) === true) {
					res = true;
					return true;
				}
			});
			return res;
		},
		matchesInLang: function (search, lang) {
			var self = this;
			var terms = self.get("terms");
			var res = false;
			if (
				terms !== undefined &&
				terms[lang] !== undefined &&
				terms[lang].get("name") &&
				terms[lang].get("name").toLowerCase().indexOf(search.toLowerCase()) > -1
			) {
				res = true;
			}
			return res;
		},
		unSyncFilter: function () {
			var self = this;
			var syncData = TaxonomyTranslation.data.syncData;
			var terms = self.get("terms");
			var res = false;
			_.each(syncData, function (correction) {
				_.each(TaxonomyTranslation.util.langCodes, function (lang) {
					if (terms[lang] !== undefined && correction.translated_id == terms[lang].get('term_taxonomy_id')) {
						res = true;
					}
				});
			});

			return res;
		}
	});
})(TaxonomyTranslation);
