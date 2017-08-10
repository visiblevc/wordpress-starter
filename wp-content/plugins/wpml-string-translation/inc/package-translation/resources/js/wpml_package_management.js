/*globals jQuery, ajaxurl*/
/*jshint browser:true, devel:true*/

var WPML_Package_Translation = WPML_Package_Translation || {};

(function () {
	"use strict";

	WPML_Package_Translation.ManagementPage = function () {
		var self = this;

		self.init = function () {
			jQuery('.js_package_all_cb').on('change', self.check_uncheck_all);
			jQuery('.js_package_row_cb').on('change', self.enable_disable_delete);
			jQuery('#delete_packages').on('click', self.delete_selected_packages);
			jQuery('#package_kind').on('change', self.filter_by_kind);
		};

		self.check_uncheck_all = function () {
			var package_all_cb = jQuery('.js_package_all_cb').first();

			var checked = package_all_cb.prop('checked');
			jQuery('.js_package_row_cb').each(
				function () {
					if (!jQuery(this).is(':disabled')) {
						jQuery(this).prop('checked', checked);
					}
				}
			);

			self.enable_disable_delete();
		};

		self.enable_disable_delete = function () {
			var enable = jQuery('.js_package_row_cb:checked:visible').length !== 0;

			jQuery('#delete_packages').prop('disabled', !enable);
		};

		self.delete_selected_packages = function () {

			if (confirm(jQuery('.js-delete-confirm-message').html())) {
				jQuery('#delete_packages').prop('disabled', true);
				jQuery('.spinner').css( 'float', 'none' ).addClass( 'is-active' );

				var selected = jQuery('.js_package_row_cb:checked:visible');

				var packages = [];

				selected.each(
					function () {
						packages.push(jQuery(this).val());
					}
				);

				var data = {
					action:   'wpml_delete_packages',
					wpnonce:  jQuery('#wpml_package_nonce').attr('value'),
					packages: packages
				};

				jQuery.ajax(
					{
						url:     ajaxurl,
						type:    'post',
						data:    data,
						success: function () {
							selected.each(
								function () {
									var package_row = jQuery(this).closest('.js_package');
									package_row.fadeOut(
										1000, function () {
											jQuery(this).remove();
										}
									);
								}
							);
							jQuery('.spinner').removeClass( 'is-active' );

						}
					}
				);
			}
		};

		self.all_selected = function () {
			var kind_slug = jQuery('#package_kind_filter').val();
			return kind_slug === '-1';
		};

		self.filter_by_kind = function () {
			var kind_slug = jQuery('#package_kind_filter').val();
			var icl_package_translations = jQuery('#icl_package_translations');
			var icl_package_translations_body = icl_package_translations.find('tbody');
			if (self.all_selected()) {
				icl_package_translations_body.find('tr').show();
			} else {
				icl_package_translations_body.find('tr').hide();
				icl_package_translations_body.find('tr.js_package.js_package_' + kind_slug).show();
			}
			self.enable_disable_delete();
		};

		self.init();
	};

	jQuery(document).ready(
		function () {
			WPML_Package_Translation.management_page = new WPML_Package_Translation.ManagementPage();
		}
	);

}());