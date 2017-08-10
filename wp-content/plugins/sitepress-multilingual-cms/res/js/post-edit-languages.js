/*globals post_edit_languages_data, icl_ajx_url */

function build_how_to_button(data, $, container) {
	if (data.hasOwnProperty('how_to_link')) {
		var howto = $('<span></span>');
		container.append(howto);
		howto.attr('id', 'icl_how_to_translate_link');
		howto.addClass('button');
		howto.css('padding-right', '3px');

		var howto_image = $('<img>');
		howto_image.appendTo(howto);
		howto_image.attr('align', 'baseline');
		howto_image.attr('src', data['how_to_link']['src']);
		howto_image.attr('width', '16');
		howto_image.attr('height', '16');
		howto_image.css('margin-bottom', '-4px');

		var howto_link = $('<a></a>');
		howto_link.appendTo(howto);
		howto_link.attr('href', data['how_to_link']['href']);
		howto_link.attr('title', data['how_to_link']['label']);
		howto_link.append(data['how_to_link']['label']);

		var howto_close = $('<a></a>');
		howto_close.appendTo(howto_link);
		howto_close.attr('href', '#');
		howto_close.attr('title', data['how_to_link']['hilde_label_tooltip']);
		howto_close.appendTo(howto);
		howto_close.css('outline', 'none');
		howto_close.on(
			'click', function () {
				if (confirm(data['how_to_link']['hide_confirm'])) {
					jQuery.ajax(
						{
							url    : icl_ajx_url,
							type   : 'POST',
							data   : {
								icl_ajx_action: 'update_option',
								option        : 'hide_how_to_translate',
								value         : 1,
								_icl_nonce    : data['how_to_link']['hide_nonce']
							},
							success: function () {
								jQuery('#icl_how_to_translate_link').fadeOut()
							}
						}
					);
					return false;
				}
			}
		);

		var howto_close_image = $('<img>');
		howto_close_image.appendTo(howto_close);
		howto_close_image.attr('src', data['how_to_link']['hide_src']);
		howto_close_image.attr('width', '10');
		howto_close_image.attr('height', '10');
		howto_close_image.css('border', 'none');

	}
}
function build_language_links(data, $, container) {
	var queryString;
	var urlData;
	if (data.hasOwnProperty('language_links')) {
		var languages_container = $('<ul></ul>');
		languages_container.appendTo(container);

		for (var i = 0; i < data['language_links'].length; i++) {
			var item = data['language_links'][i];
			var is_current = item['current'];
			var language_code = item['code'];
			var language_count = item['count'];
			var language_name = item['name'];
			var statuses = item['statuses'];
			var type = item['type'];

			var language_item = $('<li></li>');
			language_item.addClass('language_' + language_code);
			if (i > 0) {
				language_item.append('&nbsp;|&nbsp;');
			}

			var language_summary = ' <span class="count ' + language_code + '">(' + ( language_count < 0 ? "0" : language_count ) + ')</span>';

			var current;
			if (is_current) {
				current = $('<strong></strong>');
			} else if (language_count >= 0) {
				current = $('<a></a>');
				urlData = {
					post_type: type,
					lang:      language_code
				};

				if (statuses && statuses.length) {
					urlData.post_status = statuses.join(',');
				}
				queryString = $.param(urlData);
				current.attr('href', '?' + queryString);
			} else {
				current = $('<span></span>');
			}

			current.append(language_name);
			current.appendTo(language_item);
			current.append(language_summary);

			language_item.appendTo(languages_container);
		}

		$(document).trigger( 'wpml_language_links_added', [ languages_container ] );
	}
}

jQuery(document).ready(
	function ($) {

		var data = post_edit_languages_data;
		var how_to_link = data['how_to_link'];
		var container = $('<div></div>');
		container.addClass('icl_subsubsub');
		container.css('clear', 'both');

		build_language_links(data, $, container);
		build_how_to_button(data, $, container);
		$(".subsubsub").append(container);
	}
);