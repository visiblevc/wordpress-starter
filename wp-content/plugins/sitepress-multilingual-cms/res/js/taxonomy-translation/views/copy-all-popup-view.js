/*globals labels, TaxonomyTranslation, Backbone, WPML_core, jQuery, _ */

(function () {
	"use strict";
	
	TaxonomyTranslation.views.CopyAllPopUpView = Backbone.View.extend({

		tagName: 'div',
		template: WPML_core[ 'templates/taxonomy-translation/copy-all-popup.html' ],
		model: TaxonomyTranslation.models.TermRow,

		events: {
			'click .cancel': 'close',
			'click .js-copy-all-ok': 'copyAll'
		},
		initialize: function () {
			var self = this;
			self.dialog = null;
			return self;
		},

		render: function () {

			var self = this,
				trid = self.model.get('trid'),
				originalLang = self.getOriginalLanguage(),
				flagUrl = TaxonomyTranslation.data.activeLanguages[ originalLang ].flag,
				langLabel = TaxonomyTranslation.data.activeLanguages[ originalLang ].label,
				copyMessage = labels.copyToAllMessage.replace( '%language%', '<img src="' + flagUrl + '"> <strong>' + langLabel + '</strong>' );

			self.$el.html(
				this.template({
					trid: trid,
					lang: originalLang,
					labels: labels,
					copyMessage: copyMessage
				})
			);

			self.delegateEvents();
			return self;
		},
		open: function ( ) {
			var self = this,
				popUpDomEl,
				trid = self.model.get( 'trid' ),
				lang = self.getOriginalLanguage();
				
			self.render();
			popUpDomEl = jQuery('#' + trid + '-popup-' + lang);
			popUpDomEl.append( self.$el );
				
			self.dialog = jQuery( '#icl_tt_form_' + trid + '_' + lang );
			self.dialog.dialog({
				autoOpen: true,
				modal: true,
				minWidth: 600,
				resizable: false,
				draggable: false,
				dialogClass: 'dialog-fixed otgs-ui-dialog'
			});
			self.setElement( self.dialog );
			self.delegateEvents();

		},
		close: function () {
			if ( this.dialog ) {
				this.dialog.dialog( 'close' );
				this.undelegateEvents();
				this.remove();
				this.dialog = null;
			}
		},
		getOriginalLanguage: function () {
			var trid = this.model.get( 'trid' );
			return TaxonomyTranslation.classes.taxonomy.getOriginalTerm( trid ).get( 'language_code' );
		},
		copyAll: function () {

			var self = this,
				trid = self.model.get( 'trid' ),
				originalTerm = TaxonomyTranslation.classes.taxonomy.getOriginalTerm( trid ),
				name = originalTerm.get( 'name' ),
				slug = originalTerm.get( 'slug' ),
				description = originalTerm.get( 'description' ),
				overwrite = self.$el.find( 'input[name="overwrite"]:checked' ).length > 0;
				
			self.$el.find('.js-copy-all-ok').prop( 'disabled', true );
			self.$el.find('.cancel').prop( 'disabled', true );

			
			var terms = self.model.get("terms");
			_.each( terms, function ( term ) {
				if ( overwrite || term.get( 'name' ) === false ) {
					term.save( name, slug, description );
				}
			});

			self.close();			
			
		}
		

	});
})(TaxonomyTranslation);