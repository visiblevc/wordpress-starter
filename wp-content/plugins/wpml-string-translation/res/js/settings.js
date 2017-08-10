/*jshint devel:true */
/*global jQuery */
var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.Settings = function () {
	"use strict";

	var self = this;

	self.updateTrackStringWarning = function (event) {
		var warning = jQuery('.js-track-strings-note');
		if (jQuery(this).prop('checked')) {
			warning.fadeIn();
		} else {
			warning.fadeOut();
		}
	};


	jQuery(document).ready(function () {
		jQuery('#track_strings').on('click', self.updateTrackStringWarning);
	});

};

WPML_String_Translation.settings = new WPML_String_Translation.Settings();