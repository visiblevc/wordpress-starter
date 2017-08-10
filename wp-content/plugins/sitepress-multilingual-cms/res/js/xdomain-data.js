/*globals jQuery, icl_vars, wpml_xdomain_data */

(function () {
	"use strict";

	jQuery(document).ready(function () {
		jQuery('.' + wpml_xdomain_data.css_selector + ' a').on('click', function (event) {

			var originalUrl;
			var currentUrl = window.location.href;
			var targetUrl = jQuery(this).attr('href');

			if ('#' !== targetUrl && currentUrl !== targetUrl) {
				event.preventDefault();

				originalUrl = jQuery(this).attr('href');
				// Filter out xdomain_data if already in the url
				originalUrl = originalUrl.replace(/&xdomain_data(=[^&]*)?(?=&|$)|xdomain_data(=[^&]*)?(&|$)/, '');
				originalUrl = originalUrl.replace(/\?$/, '');

				/** @namespace icl_vars.current_language */
				jQuery.ajax({
					url:      icl_vars.ajax_url,
					type:     'post',
					dataType: 'json',
					data:     {
						action:        'switching_language',
						from_language: icl_vars.current_language
					},
					success:  function (response) {
						var argsGlue;
						var url;
						var hash;
						var urlSplit;
						var xdomain;
						var form;

						if (response.data.xdomain_data) {
							if (response.success) {
								if ('post' === response.data.method) {

									// POST
									form = jQuery('<form method="post" action="' + originalUrl + '" >');
									xdomain = jQuery('<input type="hidden" name="xdomain_data" value="' + response.data.xdomain_data + '">');

									form.append(xdomain);
									jQuery('body').append(form);

									form.submit();

								} else {
									// GET
									urlSplit = originalUrl.split('#');
									hash = '';
									if (1 < urlSplit.length) {
										hash = '#' + urlSplit[1];
									}
									url = urlSplit[0];
									if (url.indexOf('?') === -1) {argsGlue = '?';} else {argsGlue = '&';}
									/** @namespace response.data.xdomain_data */
									url = originalUrl + argsGlue + 'xdomain_data=' + response.data.xdomain_data + hash;
									location.href = url;
								}

							} else {
								url = originalUrl;
								location.href = url;
							}
						} else {
							location.href = originalUrl;
						}
					},
					error:    function () {
						location.href = originalUrl;
					}
				});
			}
		});
	});
}());