/*globals ajaxurl, jQuery, document, window, WPML_core, wpml_taxonomies */

var TaxonomyTranslation = TaxonomyTranslation || {};
TaxonomyTranslation.classes = {
	instantiatedTermModels : {}
};
TaxonomyTranslation.models = {};
TaxonomyTranslation.collections = {};
TaxonomyTranslation.views = {};
TaxonomyTranslation.mainView = {};
TaxonomyTranslation.mainView.filterView = {};
TaxonomyTranslation.data = {};
TaxonomyTranslation.data.translatedTaxonomyLabels = {};
TaxonomyTranslation.data.compiledTemplates = {};
TaxonomyTranslation.data.syncData = {};

/* WCML compatibility */
WPML_Translate_taxonomy = {};
WPML_Translate_taxonomy.callbacks = jQuery.Callbacks();

(function () {
	"use strict";
	
	jQuery(document).ready(function () {
		
		var loading = jQuery( '.wpml_taxonomy_loading .spinner' );
		if ( loading.length ) {
			loading.css( {
				'visibility': 'visible',
				'float': 'left'
				} );
			loading.show();
		}
		jQuery('.icl_tt_main_bottom').hide();

		TaxonomyTranslation.data.activeLanguages = wpml_taxonomies.activeLanguages;
		TaxonomyTranslation.data.allLanguages = wpml_taxonomies.allLanguages;
		TaxonomyTranslation.data.taxonomies = wpml_taxonomies.taxonomies;
		TaxonomyTranslation.util.init();

		var headerHTML = WPML_core[ 'templates/taxonomy-translation/main.html' ]({taxonomies: TaxonomyTranslation.data.taxonomies});
		jQuery("#wpml_tt_taxonomy_translation_wrap").html(headerHTML);

		// WCML compatibility
		var taxonomySwitcher = jQuery("#icl_tt_tax_switch");
		var potentialHiddenSelectInput = jQuery('#tax-selector-hidden');
		var potentialHiddenTaxInput = jQuery('#tax-preselected');
		var taxonomy;
		
		if (potentialHiddenSelectInput.length !== 0 && potentialHiddenSelectInput.val() && potentialHiddenTaxInput.length !== 0 && potentialHiddenTaxInput.val()) {
			taxonomy = potentialHiddenTaxInput.val();
			taxonomySwitcher.closest('label').hide();
			jQuery('[id="term-table-header"]').hide();
			jQuery('[id="term-table-summary"]').hide();
			taxonomySwitcher.val(taxonomy);
			loadModelAndView(taxonomy);
			TaxonomyTranslation.mainView.showLoadingSpinner();
		} else if ((taxonomy = taxonomyFromLocation()) !== false) {
			taxonomySwitcher.val(taxonomy);
			switchToTaxonomy(taxonomy);
		} else {
			taxonomySwitcher.one("change", function () {
				switchToTaxonomy(jQuery(this).val());
			});
		}

		function switchToTaxonomy(taxonomy){
			
			loadModelAndView(taxonomy);
			TaxonomyTranslation.mainView.showLoadingSpinner();

			jQuery("#icl_tt_tax_switch").on("change", function () {
				TaxonomyTranslation.mainView.showLoadingSpinner();
				jQuery('.icl_tt_main_bottom').hide();
				jQuery('#taxonomy-translation').html('');
				TaxonomyTranslation.mainView.selectTaxonomy();
			});
		}

		function isSyncTab(){
			return  window.location.search.substring(1).indexOf('&sync=1') > -1;
		}

		function loadModelAndView(taxonomy){
			TaxonomyTranslation.classes.taxonomy = new TaxonomyTranslation.models.Taxonomy({taxonomy: taxonomy});
			TaxonomyTranslation.mainView = new TaxonomyTranslation.views.TaxonomyView({model: TaxonomyTranslation.classes.taxonomy}, {sync: isSyncTab()});
		}

		function taxonomyFromLocation() {
			var queryString = window.location.search.substring(1);
			var taxonomy = false;
			Object.getOwnPropertyNames(TaxonomyTranslation.data.taxonomies).forEach(function (tax) {
				if (queryString.indexOf('taxonomy=' + tax) > -1) {
					taxonomy = tax;
				}
			});

			return taxonomy;
		}
	});
})(TaxonomyTranslation);