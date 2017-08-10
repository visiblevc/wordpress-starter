jQuery(document).ready(
	function () {
		"use strict";
		jQuery('.editinline').on(
			'click', function () {
				var lang, parentDiv, editButton, postLink;

				parentDiv = jQuery(this).closest('div');
				editButton = parentDiv.find('.edit').find('a');
				postLink = editButton.attr('href');
				lang = postLink.match(/(?=lang=).*.$/).pop().replace('lang=', '');
				parseJSONTerms(lang);
			}
		);
	}
);

/**
 * This is only used for hierarchical Taxonomies
 *
 * @param lang String
 */

function parseJSONTerms(lang) {
	"use strict";
	var JSONString, allTerms, termsInCorrectLang, taxonomy;
	JSONString = jQuery('#icl-terms-by-lang').html();
	allTerms = jQuery.parseJSON(JSONString);
	if (allTerms.hasOwnProperty(lang)) {
		termsInCorrectLang = allTerms[lang];
		for (taxonomy in termsInCorrectLang) {
			if (termsInCorrectLang.hasOwnProperty(taxonomy)) {
				removeWrongLanguageTerms(termsInCorrectLang[taxonomy], taxonomy);
			}
		}
	}

}

function removeWrongLanguageTerms(termsList, taxonomy) {
	"use strict";
	var termsUL, termsListElements;

	termsUL = jQuery('.' + taxonomy + '-checklist');
	termsListElements = termsUL.children('li[id^="' + taxonomy + '"]');

	jQuery.each(
		termsListElements, function (index, liElement) {
			var termId, domElementID;
			domElementID = liElement.id;
			termId = domElementID.replace(taxonomy + '-', '');
			if (termsList.indexOf(termId) === -1) {
				jQuery(liElement).hide();
			} else {
				jQuery(liElement).show();
			}
		}
	);
}
