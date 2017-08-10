/*globals labels */

(function () {
	TaxonomyTranslation.views.TermPopUpView = Backbone.View.extend({

		tagName: 'div',
		template: WPML_core[ 'templates/taxonomy-translation/term-popup.html' ],
		model: TaxonomyTranslation.models.Term,

		events: {
			'click .cancel': 'close',
			'click .term-save': 'saveTerm',
			'click .js-button-copy': 'copyOriginal',
			'keydown': 'handleEnter',
			'input #term-name': 'updateUI'
		},
		initialize: function () {
			var self = this;
			self.listenTo(self.model, 'translationSaved', self.close);
			self.listenTo(self.model, 'saveFailed', self.render);
			self.dialog = null;
			return self;
		},

		render: function () {

			var self = this,
				trid = self.model.get('trid'),
				term = self.model.getNameSlugAndDescription(),
				term_meta = self.model.getMetaData(),
				original_term_meta = TaxonomyTranslation.classes.taxonomy.getOriginalTermMeta( trid ),
				original_term = TaxonomyTranslation.classes.taxonomy.getOriginalTerm( trid );

			self.$el.html(
				this.template({
					trid: trid,
					lang: self.model.get('language_code'),
					source_lang: original_term.get( 'language_code' ),
					langs: TaxonomyTranslation.data.activeLanguages,
					ttid: self.model.get('term_taxonomy_id'),
					term: term,
					original_term: original_term.getNameSlugAndDescription(),
					term_meta: term_meta,
					original_term_meta: original_term_meta
				})
			);

			self.delegateEvents();
			return self;
		},
		handleEnter: function (e) {
			var self = this;
			if (self.$el.find('input:focus').length !== 0 && e.keyCode == 13) {
				self.saveTerm(e);
			}
			return self;
		},
		saveTerm: function (e) {
			var self = this,
				meta_data = {};

			self.undelegateEvents();

			e.preventDefault();
			var name = self.$el.find('#term-name').val(),
				slug = self.$el.find('#term-slug').val(),
				description = self.$el.find('#term-description').val();


			var term_metas = self.$el.find('.term-meta');
			_.each( term_metas, function ( meta_object ) {
				meta_data[ meta_object.dataset.metaKey ] = meta_object.value;
			});

			if (name) {
				self.$el.find('.spinner').show();
				self.$el.find('.term-save').prop( 'disabled', true );
				self.$el.find('.cancel').prop( 'disabled', true );
				self.model.save(name, slug, description, meta_data);
			}

			return self;
		},
		open: function ( trid, lang ) {
			var self = this;
			self.render();
			var popUpDomEl = jQuery('#' + trid + '-popup-' + lang);
			popUpDomEl.append( self.$el );
				
			self.dialog = jQuery( '#icl_tt_form_' + trid + '_' + lang );
			self.dialog.dialog({
				autoOpen: true,
				modal: true,
				minWidth: self.getMinDialogWidth(),
				resizable: false,
				draggable: false,
				dialogClass: 'dialog-fixed otgs-ui-dialog'
			});
			self.setElement( self.dialog );
			self.delegateEvents();
			self.updateUI();

		},
		getMinDialogWidth: function ( ) {
			return 800;
		},
		close: function () {
			if ( this.dialog ) {
				this.dialog.dialog( 'close' );
				this.undelegateEvents();
				this.remove();
				this.dialog = null;
			}
		},
		copyOriginal: function ( e ) {
			var self = this,
				original = jQuery( e.currentTarget ).prev().val();
			jQuery( e.currentTarget ).next().val( original );
			self.updateUI();
		},
		updateUI: function ( ) {
			var self = this;
			self.$el.find( '.term-save' ).prop( 'disabled', self.$el.find( '#term-name').val() === '' );
		}

	});
})(TaxonomyTranslation);