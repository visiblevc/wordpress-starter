/*jshint browser:true, devel:true */
/*global document */

var WPMLLanguageSwitcherDropdown = (function() {
	"use strict";

	var toggleSelector = '.js-wpml-ls-legacy-dropdown a.js-wpml-ls-item-toggle';

	var preventDefault = function(e) {
		var evt = e ? e : window.event;

		if (evt.preventDefault) {
			evt.preventDefault();
		}

		evt.returnValue = false;
	};

	var init = function() {
		var links = document.querySelectorAll(toggleSelector);
		for(var i=0; i < links.length; i++) {
			links[i].addEventListener('click', preventDefault );
		}
	};

	return {
		'init': init
	};

})();

document.addEventListener('DOMContentLoaded', function(){
	"use strict";
	WPMLLanguageSwitcherDropdown.init();
});