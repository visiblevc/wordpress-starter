/*globals labels */

(function () {
	TaxonomyTranslation.views.OriginalTermPopUpView = TaxonomyTranslation.views.TermPopUpView.extend({

		template: WPML_core[ 'templates/taxonomy-translation/original-term-popup.html' ],
		getMinDialogWidth: function ( ) {
			return 400;
		}
	});
})(TaxonomyTranslation);