/*globals labels, TaxonomyTranslation, _, jQuery, WPML_core */

(function () {

	TaxonomyTranslation.views.TermView = Backbone.View.extend({

		tagName: 'span',
		template: WPML_core[ "templates/taxonomy-translation/term-translated.html" ],
		model: TaxonomyTranslation.models.Term,
		popUpView: false,
		needsCorrection: false,
		events: {
			"click .icl_tt_term_name": "openPopUpTerm"
		},

		initialize: function () {
			var self = this;
			self.listenTo(self.model, 'translationSaved', self.render);
			self.listenTo(self.model, 'translationSaved', function () {
				jQuery('#tax-apply').prop('disabled', false);
			});
		},
		loadSyncData: function () {
			"use strict";
			var self = this;

			var syncData = TaxonomyTranslation.data.syncData;
			var ttid = self.model.get('term_taxonomy_id');
			var parent = self.model.get('parent');
			var found = false;
			var needsCorrection = false;
			var correctParentText = false;
			var parentName = TaxonomyTranslation.classes.taxonomy.getTermName(parent);
			_.each(syncData, function (correction) {
				if (correction.translated_id == ttid) {
					found = true;

					var oldParent = '';
					if (parent !== 0) {
						oldParent = '<span style="background-color:#F55959;">-' + TaxonomyTranslation.classes.taxonomy.getTermName(parent) + '</span>';
						jQuery('.wpml-parent-removed').show();
					}
					var newParent = '';
					if (correction.correct_parent !== 0) {
						newParent = '<span style="background-color:#CCFF99;">+' + TaxonomyTranslation.classes.taxonomy.getTermName(correction.correct_parent) + '</span>';
						jQuery('.wpml-parent-added').show();
					}
					parentName = oldParent + '   ' + newParent;
					needsCorrection = true;
				}
			});

			if (needsCorrection === true) {
				self.template = WPML_core[ 'templates/taxonomy-translation/term-not-synced.html' ];
			} else {
				self.template = WPML_core[ 'templates/taxonomy-translation/term-synced.html' ];
			}

			self.$el.html(
				self.template({
					trid: self.model.get("trid"),
					lang: self.model.get("language_code"),
					name: self.model.get("name"),
					level: self.model.get("level"),
					correctedLevel: self.model.get("level"),
					correctParent: correctParentText,
					parent: parentName
				})
			);

			self.needsCorrection = needsCorrection;

			return self;
		},

		render: function () {
			var self = this;

			self.needsCorrection = false;
			if ( ! self.model.get( "name" ) ) {
				self.template = WPML_core[ "templates/taxonomy-translation/term-not-translated.html" ];
			} else if ( self.model.isOriginal() ) {
				self.template = WPML_core[ "templates/taxonomy-translation/term-original-disabled.html" ];
			} else {
				self.template = WPML_core[ "templates/taxonomy-translation/term-translated.html" ];
			}

			var html = self.template({
					trid: self.model.get("trid"),
					lang: self.model.get("language_code"),
					name: self.model.get("name"),
					level: self.model.get("level"),
					correctedLevel: self.model.get("level"),
					langs: TaxonomyTranslation.data.activeLanguages
				});
			self.$el.html( html );

			return self;
		},
		openPopUpTerm: function (e) {
			var self = this;

			e.preventDefault();
			var trid = self.model.get("trid");
			var lang = self.model.get("language_code");
			if (trid && lang) {
				if (TaxonomyTranslation.classes.termPopUpView && typeof TaxonomyTranslation.classes.termPopUpView !== 'undefined') {
					TaxonomyTranslation.classes.termPopUpView.close();
				}
				if ( self.model.isOriginal() ) {
					TaxonomyTranslation.classes.termPopUpView = new TaxonomyTranslation.views.OriginalTermPopUpView( { model: self.model } );
				} else {
					TaxonomyTranslation.classes.termPopUpView = new TaxonomyTranslation.views.TermPopUpView( { model: self.model } );
				}
				TaxonomyTranslation.classes.termPopUpView.open( trid, lang );
			}
		}
	});
})(TaxonomyTranslation);
