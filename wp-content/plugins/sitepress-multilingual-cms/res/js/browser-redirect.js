/*globals wpml_browser_redirect_params, jQuery, ajaxurl, window */

/** @namespace wpml_browser_redirect_params.pageLanguage */
/** @namespace wpml_browser_redirect_params.expiration */
/** @namespace wpml_browser_redirect_params.languageUrls */
/** @namespace navigator.browserLanguage */

var WPMLBrowserRedirect = function () {
	"use strict";

	var self = this;

	self.getExpirationDate = function () {
		var date = new Date();
		var currentTime = date.getTime();
		date.setTime(currentTime + (wpml_browser_redirect_params.cookie.expiration * 60 * 60 * 1000));
		return date;
	};

	self.getRedirectUrl = function (browserLanguage) {
		var redirectUrl = false;
		var languageUrls = wpml_browser_redirect_params.languageUrls;
		var languageFirstPart = browserLanguage.substr(0, 2);
		var languageLastPart = browserLanguage.substr(3, 2);

		if (languageUrls[browserLanguage] === undefined) {
			if (languageUrls[languageFirstPart] !== undefined) {
				redirectUrl = languageUrls[languageFirstPart];
			} else if (languageUrls[languageLastPart] !== undefined) {
				redirectUrl = languageUrls[languageLastPart];
			}
		} else {
			redirectUrl = languageUrls[browserLanguage];
		}
		return redirectUrl;
	};

	self.init = function () {

		if (self.cookiesAreEnabled() && !self.cookieExists()) {
            self.getBrowserLanguage(function (browserLanguages) {
				var redirectUrl;
				var pageLanguage;
                var browserLanguage;

				pageLanguage = wpml_browser_redirect_params.pageLanguage.toLowerCase();

                var browserLanguagesLength = browserLanguages.length;
                for (var i = 0; i < browserLanguagesLength; i++) {
                    browserLanguage = browserLanguages[i];

					if ( pageLanguage === browserLanguage ) {
						self.setCookie(browserLanguage);
						break;
					} else {
						redirectUrl = self.getRedirectUrl(browserLanguage);
						if (false !== redirectUrl) {
							self.setCookie(browserLanguage);
							window.location = redirectUrl;
							break;
						}
					}
                }

            });
		}
	};

	self.cookieExists = function () {
		var cookieParams = wpml_browser_redirect_params.cookie;
		var cookieName = cookieParams.name;
		return jQuery.cookie(cookieName);
	};

	self.setCookie = function (browserLanguage) {
		var cookieOptions;
		var cookieParams = wpml_browser_redirect_params.cookie;
		var cookieName = cookieParams.name;
		var path = '/';
		var domain = '';

		if (cookieParams.path) {
			path = cookieParams.path;
		}

		if (cookieParams.domain) {
			domain = cookieParams.domain;
		}

		cookieOptions = {
			expires: self.getExpirationDate(),
			path:    path,
			domain:  domain
		};
		jQuery.cookie(cookieName, browserLanguage, cookieOptions);
	};

	self.getBrowserLanguage = function (success) {
        var browserLanguages = [];

        if (navigator.languages) {
            browserLanguages = navigator.languages;
        }
        if (0 === browserLanguages.length && (navigator.language || navigator.userLanguage)) {
            browserLanguages.push(navigator.language || navigator.userLanguage);
        }
        if (0 === browserLanguages.length && (navigator.browserLanguage || navigator.systemLanguage)) {
            browserLanguages.push(navigator.browserLanguage || navigator.systemLanguage);
		}

        if (0 === browserLanguages.length) {
            jQuery.ajax({
                data: {
                    icl_ajx_action: 'get_browser_language'
                },
                success: function (response) {
                    if (response.success) {
                        browserLanguages = response.data;
                        if (success && "function" === typeof success) {
							browserLanguages = browserLanguages.join('|').toLowerCase().split('|');
                            success(browserLanguages);
                        }
                    }
                }
            });
        } else {
			browserLanguages = browserLanguages.join('|').toLowerCase().split('|');
            success(browserLanguages);
        }
	};

	self.cookiesAreEnabled = function () {
		var result = (undefined !== jQuery.cookie);
		if (result) {
			jQuery.cookie('wpml_browser_redirect_test', 1);
			result = '1' === jQuery.cookie('wpml_browser_redirect_test');
			jQuery.cookie('wpml_browser_redirect_test', 0);
		}
		return result;
	};
};

jQuery(document).ready(function () {
	"use strict";

	var wpmlBrowserRedirect = new WPMLBrowserRedirect();
	wpmlBrowserRedirect.init();

});