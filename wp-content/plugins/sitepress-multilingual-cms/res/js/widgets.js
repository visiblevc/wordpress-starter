/*jshint browser:true, devel:true */
/*global jQuery */
var WPML_core = WPML_core || {};

WPML_core.widgets = (function( $ ) {
	"use strict";

	var init = function() {
		$(document).on('widget-added', function (e, widget) {
			var button = widget.find('.js-wpml-ls-slot-management-link');

			if (button.length > 0) {
				var sidebar_slug = widget.closest('.widgets-sortables').attr('id'),
					link         = button.attr('href');

				if ('#sidebars/' === link.slice(-10)) {
					button.attr('href', link + sidebar_slug);
				}
			}
		});
	};

	return {
		'init': init
	};

})( jQuery );

jQuery(document).ready(function () {
	"use strict";

	WPML_core.widgets.init();
});