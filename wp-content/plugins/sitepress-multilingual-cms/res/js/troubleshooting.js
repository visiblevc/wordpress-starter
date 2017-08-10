/*globals jQuery, troubleshooting_data */

/** @namespace troubleshooting_data.nonce.icl_restore_notifications */
/** @namespace troubleshooting_data.nonce.icl_remove_notifications */

jQuery(document).ready(function () {

	var remove_notifications_button = jQuery('#icl_remove_notifications');
	var restore_notifications_button = jQuery('#icl_restore_notifications');
	var restore_notifications_all_users = jQuery('#icl_restore_notifications_all_users');
	remove_notifications_button.off('click');
	remove_notifications_button.bind('click', remove_all_notifications);
	restore_notifications_button.off('click');
	restore_notifications_button.bind('click', restore_notifications);

	function remove_all_notifications() {
		if (typeof(event.preventDefault) !== 'undefined') {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		jQuery(this).attr('disabled', 'disabled');
		jQuery(this).after(icl_ajxloaderimg);

		var ajax_data = {
			'action': 'icl_remove_notifications',
			'nonce':  troubleshooting_data.nonce.icl_remove_notifications
		};

		jQuery.ajax({
			type:     "POST",
			url:      ajaxurl,
			data:     ajax_data,
			dataType: 'json',
			success:  function (response) {
				remove_notifications_button.removeAttr('disabled');
				alert(troubleshooting_data.strings.done);
				remove_notifications_button.next().fadeOut();
				if(response.reload == 1) {
					location.reload();
				}
			},
			error:    function (jqXHR, status, error) {
				var parsed_response = jqXHR.statusText || status || error;
				alert(parsed_response);
			}
		});

		return false;
	}

	function restore_notifications() {
		if (typeof(event.preventDefault) !== 'undefined') {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		jQuery(this).attr('disabled', 'disabled');
		jQuery(this).after(icl_ajxloaderimg);

		var all_users = restore_notifications_all_users.is(':checked') ? 1 : 0;

		var ajax_data = {
			'action': 'icl_restore_notifications',
			'nonce':  troubleshooting_data.nonce.icl_restore_notifications,
			'all_users':  all_users
		};

		jQuery.ajax({
			type:     "POST",
			url:      ajaxurl,
			data:     ajax_data,
			dataType: 'json',
			success:  function (response) {
				restore_notifications_button.removeAttr('disabled');
				alert(troubleshooting_data.strings.done);
				restore_notifications_button.next().fadeOut();
				if(response.reload == 1) {
					location.reload();
				}
			},
			error:    function (jqXHR, status, error) {
				var parsed_response = jqXHR.statusText || status || error;
				alert(parsed_response);
			}
		});

		return false;
	}

	var fix_post_types_and_source_langs_button = jQuery("#icl_fix_post_types");
	var updateTermNamesButton = jQuery("#icl-update-term-names");

	updateTermNamesButton.click(iclUpdateTermNames);

	fix_post_types_and_source_langs_button.click(
		function () {
			jQuery(this).attr('disabled', 'disabled');
			icl_repair_broken_translations();
			jQuery(this).after(icl_ajxloaderimg);

		}
	);

	function icl_repair_broken_translations () {
		jQuery.ajax(
			{
				url: ajaxurl,
				data: {
					action: 'icl_repair_broken_type_and_language_assignments'
				},
				success: function (response) {
					var rows_fixed = response.data;
					fix_post_types_and_source_langs_button.removeAttr('disabled');
					fix_post_types_and_source_langs_button.next().fadeOut();
					var text = '';
					if (rows_fixed > 0) {
						text = troubleshooting_strings.success_1 + rows_fixed + troubleshooting_strings.success_2;
					} else {
						text = troubleshooting_strings.no_problems;
					}
					var type_term_popup_html = '<div id="icl_fix_languages_and_post_types"><p>' + text + '</p></div>';
					jQuery(type_term_popup_html).dialog(
						{
							dialogClass: 'wpml-dialog otgs-ui-dialog',
							width      : 'auto',
							modal      : true,
							buttons    : {
								Ok: function () {
									jQuery(this).dialog("close");
								}
							}
						}
					);
				}
			});
	}


	function iclUpdateTermNames() {

		var updatedTermNamesTable = jQuery('#icl-updated-term-names-table');

		/* First of all we get all selected rows and the displayed Term names. */

		var selectedTermRows = updatedTermNamesTable.find('input[type="checkbox"]');

		var selectedIDs = {};

		jQuery.each(selectedTermRows, function (index, selectedRow) {
			selectedRow = jQuery(selectedRow);
			if(selectedRow.is(':checked') && selectedRow.val() && selectedRow.attr('name') && jQuery.trim(selectedRow.attr('name')) !== ''){
				selectedIDs[selectedRow.val().toString()] = selectedRow.attr('name');
			}
		});

		var selectedIDsJSON = JSON.stringify(selectedIDs);

		jQuery.ajax(
			{
				url: ajaxurl,
				method: "POST",
				data: {
					action: 'wpml_update_term_names_troubleshoot',
					_icl_nonce: troubleshooting_strings.termNamesNonce,
					terms: selectedIDsJSON
				},
				success: function (response) {

					jQuery.each(response.data, function (index, id) {
						updatedTermNamesTable.find('input[type="checkbox"][value="'+ id +'"]').closest('tr').remove();
					});

					var remainingRows = jQuery('.icl-term-with-suffix-row');

					if (remainingRows.length === 0 ){
						updatedTermNamesTable.hide();
						jQuery('#icl-update-term-names').hide();
						jQuery('#icl-update-term-names-done').show();
					}

					var termSuffixUpdatedHTML = '<div id="icl_fix_term_suffixes"><p>' + troubleshooting_strings.suffixesRemoved + '</p></div>';
					jQuery(termSuffixUpdatedHTML).dialog(
						{
							dialogClass: 'wpml-dialog otgs-ui-dialog',
							width      : 'auto',
							modal      : true,
							buttons    : {
								Ok: function () {
									jQuery(this).dialog("close");
								}
							}

						}
					);
				}
			});
	}

	jQuery('#icl_cache_clear').click(function () {
		var self = jQuery(this);
		self.attr('disabled', 'disabled');
		self.after(icl_ajxloaderimg);
		jQuery.post(location.href + '&debug_action=cache_clear&nonce=' + troubleshooting_strings.cacheClearNonce, function () {
			self.removeAttr('disabled');
			alert( troubleshooting_strings.done );
			self.next().fadeOut();
		});
	});
});