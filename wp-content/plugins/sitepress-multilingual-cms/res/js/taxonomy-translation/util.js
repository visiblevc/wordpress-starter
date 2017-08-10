(function () {
	TaxonomyTranslation.util = {
		langCodes: [],
		init: function () {
			_.each(TaxonomyTranslation.data.activeLanguages, function (lang, code) {
				TaxonomyTranslation.util.langCodes.push(code);
			});

		}

	};
})(TaxonomyTranslation);