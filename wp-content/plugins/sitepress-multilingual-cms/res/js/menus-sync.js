var WPML_core = WPML_core || {};

(function () {
	"use strict";

	jQuery(document).ready(function () {

		jQuery("#icl_msync_cancel").click(function () {
			location.href = location.href.replace(/#(.)$/, '');
		});

		var icl_msync_confirm = jQuery('#icl_msync_confirm');
		var check_all = icl_msync_confirm.find('thead :checkbox');

		//Remove already assigned events: that's what makes that this slow!
		check_all.off('click');
		check_all.off('change');

		check_all.on('change', function () {
			var on = jQuery(this).attr('checked');
			var checkboxes = icl_msync_confirm.find('tbody :checkbox');

			if (on) {
				checkboxes.each( function( i, el ) {
					jQuery( el ).prop( 'checked', 'checked' );
				});
				jQuery( '#icl_msync_submit' ).prop( 'disabled', false );
			} else {
				checkboxes.each( function( i, el ) {
					jQuery( el ).removeProp( 'checked' );
				});
				jQuery( '#icl_msync_submit' ).prop( 'disabled', true );
			}
		});

		icl_msync_confirm.find('tbody :checkbox').on('change', function () {

			if (jQuery(this).attr('readonly') == 'readonly') {
				jQuery(this).prop('checked', !jQuery(this).prop('checked'));
			}

			var checked_items = icl_msync_confirm.find('tbody :checkbox:checked');
			var checked_count = checked_items.length;

			jQuery('#icl_msync_submit').prop('disabled', !checked_count);

			if (checked_count && checked_items.length == icl_msync_confirm.find('tbody :checkbox').length) {
				jQuery('#icl_msync_confirm').find('thead :checkbox').prop('checked', true);
			} else {
				jQuery('#icl_msync').find('thead :checkbox').prop('checked', false);
			}

			WPML_core.icl_msync_validation();

		});

		jQuery('#icl_msync_submit').on('click', function () {
			jQuery(this).prop('disabled', true);

			var total_menus = jQuery('input[name^=sync]:checked').length;

			var spinner = jQuery('<span class="spinner"></span>');
			jQuery('#icl_msync_message').before(spinner);
			spinner.css({display:       'inline-block',
										float:        'none',
										'visibility': 'visible'
									});

			WPML_core.sync_menus(total_menus);

		});

		var max_vars_warning = jQuery('#icl_msync_max_input_vars');
		if (max_vars_warning.length) {
			var menu_sync_check_box_count = jQuery('input[name^=sync]').length;
			var max_vars_extra = 10; // Allow for a few other items as well. eg. nonce, etc
			if (menu_sync_check_box_count + max_vars_extra > max_vars_warning.data('max_input_vars')) {
				var warning_text = max_vars_warning.html();
				warning_text = warning_text.replace('!NUM!', menu_sync_check_box_count + max_vars_extra);
				max_vars_warning.html(warning_text);
				max_vars_warning.show();
			}
		}
	});

	WPML_core.icl_msync_validation = function () {

		jQuery('#icl_msync_confirm').find('tbody :checkbox').each(function () {
			var mnthis = jQuery(this);

			mnthis.prop('readonly', false);

			if (jQuery(this).attr('name') == 'menu_translation[]') {
				var spl = jQuery(this).val().split('#');
				var menu_id = spl[0];

				jQuery('#icl_msync_confirm').find('tbody :checkbox').each(function () {

					if (jQuery(this).val().search('newfrom-' + menu_id + '-') == 0 && jQuery(this).attr('checked')) {
						mnthis.prop('checked', true);
						mnthis.prop('readonly', true);
					}
				});
			}
		});
	};

	WPML_core.sync_menus = function (total_menus) {

		var message;
		var data = 'action=icl_msync_confirm';
		data += '&_icl_nonce_menu_sync=' + jQuery('#_icl_nonce_menu_sync').val();

		var number_to_send = 50;

		var menus = jQuery('input[name^=sync]:checked:not(:disabled)');
		var icl_msync_message = jQuery('#icl_msync_message');
		if (menus.length) {

			for (var i = 0; i < Math.min(number_to_send, menus.length); i++) {

				data += '&' + jQuery(menus[i]).serialize();

				jQuery(menus[i]).prop('disabled', true);
			}

			message = jQuery('#icl_msync_submit').data('message');
			message = message.replace('%1', total_menus - menus.length);
			message = message.replace('%2', total_menus);

			icl_msync_message.text(message);

			jQuery.ajax({
										url:     ajaxurl,
										type:    "POST",
										data:    data,
										success: function (response) {
											if (response.success) {
												WPML_core.sync_menus(total_menus);
											}
										}
									});
		} else {
			icl_msync_message.hide();
			message = jQuery('#icl_msync_submit').data('message-complete');
			icl_msync_message.text(message);
			jQuery('.spinner').remove();
			jQuery('#icl_msync_cancel').fadeOut();
			icl_msync_message.fadeIn('slow');
			jQuery.ajax({
										url:     ajaxurl,
										data:    {
											'action': 'wpml_get_links_for_menu_strings_translation'
										},
										success: function (response) {
											if (response.success && response.data.items) {
												var element = jQuery('<p></p>');
												element.text(menus_sync.text1);
                                                element.append('<br>' + menus_sync.text2 + ' ' );
												var items = 0;

												for (var key in response.data.items) {
													if (response.data.items.hasOwnProperty(key)) {
														if(items>0) {
															element.append(', ');
														}
														var link = jQuery('<a></a>');
														link.attr('href', response.data.items[key]);
														link.text(key);
														link.appendTo(element);
														items++;
													}
												}
                                                element.append( '<br>' + menus_sync.text3);

												element.appendTo(jQuery('#icl_msync_confirm_form'));
											}
										}
									});

		}

	};

}());