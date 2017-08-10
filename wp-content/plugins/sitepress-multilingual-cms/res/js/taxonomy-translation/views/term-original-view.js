/*globals labels */

(function () {

	TaxonomyTranslation.views.TermOriginalView = TaxonomyTranslation.views.TermView.extend({

		tagName: "td",
		className: "wpml-col-title wpml-col-title-flag",
		template: WPML_core[ 'templates/taxonomy-translation/original-term.html' ],
		render: function () {
			var self = this;

			self.needsCorrection = false;

			self.$el.html(
				self.template({
					trid: self.model.get("trid"),
					lang: self.model.get("language_code"),
					name: self.model.get("name"),
					level: self.model.get("level"),
					correctedLevel: self.model.get("level"),
					langs: TaxonomyTranslation.data.activeLanguages
				})
			);

			self.delegateEvents();
			return self;
		}
	});
})(TaxonomyTranslation);
