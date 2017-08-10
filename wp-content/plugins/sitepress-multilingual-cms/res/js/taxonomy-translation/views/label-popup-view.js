(function () {
	TaxonomyTranslation.views.LabelPopUpView = Backbone.View.extend({

		tagName: "div",
		template: WPML_core[ 'templates/taxonomy-translation/label-popup.html' ],
		model: TaxonomyTranslation.models.Taxonomy,

		events: {
			"click .cancel": "close",
			"click .js-label-save": "saveLabel",
			"click .js-button-copy": "copyOriginal",
			"keydown" : "handleEnter",
			"input .js-translation": "updateUI"
		},
		initialize: function (data, options) {
			var self = this;
			self.lang = options.lang;
			self.defLang = options.defLang;
			self.listenTo(self.model, 'labelTranslationSaved', self.close);
			self.listenTo(self.model, 'saveFailed', self.render);
			return self;
		},

		open: function ( lang ) {
			var self = this;
			self.render();
			var popUpDomEl = jQuery( '#popup-' + lang );
			popUpDomEl.append( self.$el );
				
			self.dialog = jQuery( "#icl_tt_form_" + self.model.get( "taxonomy" ) );
			self.dialog.dialog({
				autoOpen: true,
				modal: true,
				minWidth: 800,
				resizable: false,
				draggable: false,
				dialogClass: 'dialog-fixed otgs-ui-dialog'
			});
			self.setElement( self.dialog );
			self.delegateEvents();
			self.updateUI();
		},
		close: function () {
			if ( this.dialog ) {
				this.dialog.dialog( 'close' );
				this.undelegateEvents();
				this.remove();
				this.dialog = null;
			}
		},
		render: function () {
			var self = this;
			var taxonomy = self.model.get("taxonomy");
			var labels = TaxonomyTranslation.data.translatedTaxonomyLabels[self.lang];
			var originalLabels = TaxonomyTranslation.data.translatedTaxonomyLabels[self.model.get('stDefaultLang')];

			if (!labels) {
				labels = {
					singular: undefined,
					general: undefined
				};
			}

			this.$el.html(
				self.template({
					langs: TaxonomyTranslation.data.allLanguages,
					lang: self.lang,
					source_lang: self.model.get('stDefaultLang'),
					originalLabels: originalLabels,
					translatedLabels: labels,
					taxonomy: taxonomy

				})
			);

			self.delegateEvents();
			
			return self;
		},

		handleEnter: function(e){
			var self = this;
			if(self.$el.find('input:focus').length !== 0 && e.keyCode == 13){
				self.saveLabel(e);
			}
			return self;
		},
		
		updateUI: function ( e ) {
			var self = this,
				translationsEntered = true;
				
			self.$el.find( '.js-translation' ).each( function () {
				if ( jQuery( this ).val() === '' ) {
					translationsEntered = false;
				}
			});
			
			self.$el.find( '.js-label-save' ).prop( 'disabled', !translationsEntered );
		},

		saveLabel: function (e) {
			var singularValueField, pluralValueField, singularValue, pluralValue, self, inputPrefix;
			self = this;

			e.preventDefault();

			inputPrefix = '#' + self.model.get("taxonomy") + '-';
			singularValueField = self.$el.find(inputPrefix + 'singular');
			pluralValueField = self.$el.find(inputPrefix + 'plural');

			if (singularValueField.length > 0 && pluralValueField.length > 0) {
				singularValue = singularValueField.val();
				pluralValue = pluralValueField.val();
			}

			if (singularValue && pluralValue) {
				self.undelegateEvents();

				self.$el.find(".spinner").show();
				self.$el.find(".js-label-save").prop( 'disabled', true );
				self.$el.find(".cancel").prop( 'disabled', true );

				self.model.saveLabel(singularValue, pluralValue, self.lang);

			}

			return self;

		},
		
		copyOriginal: function ( e ) {
			var self = this,
				original = jQuery( e.currentTarget ).prev().val();
			
			jQuery( e.currentTarget ).next().val( original );
			
			self.updateUI();
		}
		
	});
})(TaxonomyTranslation);