/*jshint browser:true, devel:true */
/*global jQuery, tm_tt_data, ajaxurl, document, icl_ajx_url, icl_ajxloaderimg, Translation_Translators_settings, _ */

(function () {
	"use strict";

	jQuery(document).ready(
		function ($) {

			var user_name_input_field = $('#icl_quick_src_users');
			var translator_add_button = $('#icl_add_translator_submit');
			var local_translation_radio = $('#radio-local');
			var edit_from = $('#edit-from');
			var edit_to = $('#edit-to');

			user_name_input_field.attr('disabled', 'disabled');
			translator_add_button.attr('disabled', 'disabled');

			function check_any_selected() {
				return !!$("input[name='services']").is(":checked");
			}

			function check_languages_valid() {
				var from = edit_from.val(), to = edit_to.val();
				return !!(to !== from && to !== "0" && from !== "0");
			}

			var nonce = jQuery('#get_users_not_trans_nonce').val();

			$.ajax(
				{
					type: "POST",
					url: ajaxurl,
					dataType: 'json',
					data: {
						action: 'icl_get_blog_users_not_translators',
						get_users_not_trans_nonce: nonce
					},
					success: function (response) {
						var available_users = response.data, user_found_text = $('#icl_user_src_nf'), selected_user_hidden_field = $('#icl_tm_selected_user');

						var check_manual_input = function () {
							var found = false;
							_.each(
								available_users, function (user) {
									if (user.value === user_name_input_field.val()) {
										selected_user_hidden_field.val(user.id);
										found = true;
									}
								}
							);
							if (found === false && local_translation_radio.is(':checked')) {
								/** @namespace tm_tt_data.no_matches */
								user_found_text.html(tm_tt_data.no_matches);
								translator_add_button.attr('disabled', 'disabled');
							} else {
								if (local_translation_radio.is(':checked')) {
									/** @namespace tm_tt_data.found */
									user_found_text.html(tm_tt_data.found);
								}

								if (check_languages_valid() && check_any_selected()) {
									translator_add_button.removeAttr('disabled');
								} else {
									translator_add_button.attr('disabled', 'disabled');
								}
							}
						};

						local_translation_radio.on('change', check_manual_input);
						user_name_input_field.on('keyup', check_manual_input);
						edit_from.on('change', check_manual_input);
						edit_to.on('change', check_manual_input);

						var autocomplete_dropdown = user_name_input_field;

						autocomplete_dropdown.autocomplete(
							{
								autoSelect: true,
								autoFocus:  true,
								source:     available_users
							}
						);
						autocomplete_dropdown.autocomplete("option", "minLength", 0);
						autocomplete_dropdown.autocomplete("enable");
						autocomplete_dropdown.on(
							"autocompleteselect", function (ui, selected) {
								var selected_user = selected.item;
								_.each(
									available_users, function (user) {
										if (user.value === selected_user.value) {
											selected_user_hidden_field.val(user.id);
											user_name_input_field.val(user.value);
										}
									}
								);
								check_manual_input();
							}
						);
						user_name_input_field.removeAttr('disabled');
					},
					error: function () {
						//if we don't get any data here, we can still check if remote translators are chosen correctly
						if (check_any_selected() && !local_translation_radio.is(':checked') && check_languages_valid()) {
							user_name_input_field.removeAttr('disabled');
						}
					}
				}
			);
		}
	);

}());