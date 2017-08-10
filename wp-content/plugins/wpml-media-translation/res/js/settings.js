/*global jQuery, ajaxurl, addLoadEvent */
(function () {
	'use strict';

	addLoadEvent(
		function () {
			var form = jQuery('#wpml_media_options_form');
			var form_action = jQuery('#wpml_media_options_action', form);

			jQuery('.wpml_media_options_language :checkbox', form).change(
				function () {
					var set_language_required_missing = !jQuery('input[name=set_language_info]', form).attr('disabled') && !jQuery('input[name=set_language_info]', form).attr('checked');
					if (!jQuery('.wpml_media_options_language :checkbox:checked', form).length || set_language_required_missing) {
						jQuery(':submit', form).attr('disabled', 'disabled');
					} else {
						jQuery(':submit', form).removeAttr('disabled');
					}
				}
			);

			jQuery(':submit', form).click(
				function () {
					form_action.val(jQuery(this).attr('name'));
				}
			);

			form.submit(
				function () {

					if (!jQuery(':submit', form).attr('disabled')) {

						switch (form_action.val()) {
							case 'start':
								wpml_media_options_form_working();
								wpml_media_options_form_scan_prepare();
								break;
							case 'set_defaults':
								wpml_media_set_content_prepare();
								break;
						}
					}

					form_action.val(0);
					return false;
				}
			);

			function wpml_update_status(message) {
				jQuery(form).find('.status').html(message);
				if (message.length > 0) {
					jQuery(form).find('.status').show();
				} else {
					jQuery(form).find('.status').fadeOut();
				}
			}

			function wpml_media_options_form_working() {
				wpml_update_status('');
				jQuery(':submit', form).attr('disabled', 'disabled');
				jQuery(form).find('.progress').fadeIn();
			}

			function wpml_media_options_form_finished(status) {
				jQuery(':submit', form).removeAttr('disabled');
				jQuery(form).find('.progress').fadeOut();
				wpml_update_status(status);
				window.setTimeout(
					function () {
						wpml_update_status('');
					}, 1000
				);
			}

			function wpml_media_options_form_scan_prepare() {

				jQuery.ajax(
					{
						url:      ajaxurl,
						type:     'POST',
						data:     {action: 'wpml_media_scan_prepare'},
						dataType: 'json',
						success:  function (ret) {
							wpml_update_status(ret.message);
							if (jQuery('#wpml_media_options_form').find('input[name=no_lang_attachments]').val() > 0) {
								// step 1
								wpml_media_set_initial_language();
							} else {
								// step 2
								wpml_media_translate_media();
							}
						},
						error:    function (jqXHR, textStatus) {
							jQuery('#icl-migrate-progress').find('.message').html(textStatus);
						}

					}
				);

			}

			function wpml_media_set_initial_language() {

				if (jQuery('#set_language_info', form).is(':checked')) {
					jQuery.ajax(
						{
							url:      ajaxurl,
							type:     'POST',
							data:     {action: 'wpml_media_set_initial_language'},
							dataType: 'json',
							success:  function (ret) {
								wpml_update_status(ret.message);
								if (ret.left > 0) {
									wpml_media_set_initial_language();
								} else {
									// step 2
									wpml_media_translate_media();
								}
							},
							error:    function (jqXHR, textStatus) {
								wpml_update_status('Set initial language: please try again (' + textStatus + ')');
							}

						}
					);
				} else {
					wpml_media_translate_media();
				}

			}

			function wpml_media_translate_media() {
				if (jQuery('#translate_media', form).is(':checked')) {
					jQuery.ajax(
						{
							url:      ajaxurl,
							type:     'POST',
							data:     {action: 'wpml_media_translate_media'},
							dataType: 'json',
							success:  function (ret) {
								wpml_update_status(ret.message);
								if (ret.left > 0) {
									wpml_media_translate_media();
								} else {
									// step 2
									wpml_media_duplicate_media();
								}
							},
							error:    function (jqXHR, textStatus) {
								wpml_update_status('Translate media: please try again (' + textStatus + ')');
							}

						}
					);
				} else {
					wpml_media_duplicate_media();
				}
			}

			function wpml_media_duplicate_media() {

				if (jQuery('#duplicate_media', form).is(':checked')) {
					jQuery.ajax(
						{
							url:      ajaxurl,
							type:     'POST',
							data:     {action: 'wpml_media_duplicate_media'},
							dataType: 'json',
							success:  function (ret) {
								wpml_update_status(ret.message);
								if (ret.left > 0) {
									wpml_media_duplicate_media();
								} else {
									// step 3
									wpml_media_duplicate_featured_images();
								}
							},
							error:    function (jqXHR, textStatus) {
								wpml_update_status('Duplicate media: please try again (' + textStatus + ')');
							}

						}
					);
				} else {
					wpml_media_duplicate_featured_images();
				}
			}

			function wpml_media_duplicate_featured_images() {

				if (jQuery('#duplicate_featured', form).is(':checked')) {
					jQuery.ajax(
						{
							url:      ajaxurl,
							type:     'POST',
							data:     {action: 'wpml_media_duplicate_featured_images'},
							dataType: 'json',
							success:  function (ret) {
								wpml_update_status(ret.message);
								if (ret.left > 0) {
									wpml_media_duplicate_featured_images();
								} else {
									wpml_media_mark_processed();
								}
							},
							error:    function (jqXHR, textStatus) {
								wpml_update_status('Duplicate featured images: Please try again (' + textStatus + ')');
							}

						}
					);
				} else {
					wpml_media_mark_processed();
				}
			}

			function wpml_media_mark_processed() {

				jQuery.ajax(
					{
						url:      ajaxurl,
						type:     'POST',
						data:     {action: 'wpml_media_mark_processed'},
						dataType: 'json',
						success:  function (ret) {
							wpml_media_options_form_finished(ret.message);
							jQuery('#wpml_media_all_done').fadeIn();
						},
						error:    function (jqXHR, textStatus) {
							wpml_update_status('Mark processed: Please try again (' + textStatus + ')');
						}

					}
				);

			}

			function wpml_media_set_content_prepare() {
				wpml_update_status('');
				jQuery(':submit', form).attr('disabled', 'disabled');
				jQuery(form).find('.content_default_progress').fadeIn();

				jQuery.ajax(
					{
						url:      ajaxurl,
						type:     'POST',
						data:     {
							action: 'wpml_media_set_content_prepare'
						},
						dataType: 'json',
						success:  function (ret) {
							jQuery(form).find('.content_default_status').html(ret.message);
							wpml_media_set_content_defaults();
						},
						error:    function (jqXHR, textStatus) {
							wpml_update_status('Set Content Prepare: Please try again (' + textStatus + ')');
						}
					}
				);

			}

			function wpml_media_set_content_defaults() {
				wpml_update_status('');
				jQuery(':submit', form).attr('disabled', 'disabled');
				jQuery(form).find('.content_default_progress').fadeIn();

				jQuery.ajax(
					{
						url:      ajaxurl,
						type:     'POST',
						data:     {
							action:                 'wpml_media_set_content_defaults',
							always_translate_media: jQuery('input[name=content_default_always_translate_media]', form).is(':checked'),
							duplicate_media:        jQuery('input[name=content_default_duplicate_media]', form).is(':checked'),
							duplicate_featured:     jQuery('input[name=content_default_duplicate_featured]', form).is(':checked')
						},
						dataType: 'json',
						success:  function (ret) {
							jQuery(form).find('.content_default_status').html(ret.message);
							wpml_media_set_content_defaults_finished();
						},
						error:    function (jqXHR, textStatus) {
							wpml_update_status('Set Content Defaults: Please try again (' + textStatus + ')');
						}
					}
				);

			}

			function wpml_media_set_content_defaults_finished(status) {
				jQuery(':submit', form).removeAttr('disabled');
				jQuery(form).find('.content_default_progress').fadeOut();
				jQuery(form).find('.content_default_status').html(status);
				window.setTimeout(
					function () {
						jQuery(form).find('.content_default_status').fadeOut();
					}, 1000
				);
			}

		}
	);
}());