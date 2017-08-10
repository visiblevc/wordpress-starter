/**
 * Created by Created by OnTheGo Systems.
 */

var WPML_Core = WPML_Core || {};

WPML_Core.ColorPicker = function () {
	var self = this;

	self.init = function () {
		jQuery(document).ready(function () {
			var colorPickers = jQuery('.wp-picker-container .wpml-colorpicker');

			if (colorPickers.length) {
				jQuery(colorPickers).on('focus', function () {
					jQuery(this).data('old-value', jQuery(this).val());
				});

				jQuery(colorPickers).on('blur', function () {
					var oldValue = '';
					if (jQuery(this).data('old-value')) {
						oldValue = jQuery(this).data('old-value');
					}
					if (jQuery(this).hasClass('iris-error') || !self.isValidColor(jQuery(this).val())) {
						jQuery(this).val(oldValue);
					}
				});
			}

		});
	};

	self.isValidColor = function (input) {
		return /(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test(input);
	};

	self.init();
};

WPML_Core.ColorPicker = new WPML_Core.ColorPicker();
