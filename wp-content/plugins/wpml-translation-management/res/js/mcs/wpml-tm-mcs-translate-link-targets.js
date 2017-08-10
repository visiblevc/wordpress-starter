/*jshint devel:true */
/*global jQuery */

var WPML_TM = WPML_TM || {};

WPML_TM.translateLinkTargets = function () {
	"use strict";

	var self = this;

	var init = function () {
		jQuery(document).ready(function () {
			self.button = jQuery('#wpml-scan-link-targets');
			self.postCount = self.button.data('post-count');
			self.stringCount = self.button.data('string-count');
			self.button.on('click', function () {
				self.button.prop('disabled', true);
				self.button.parent().find('.spinner').css('visibility', 'visible');
				self.numberFixed = 0;
				showCompletePercent( self.postCount, 'post' );
				wpmlScanLinkTargets(0, 10, true);
			});
		});
	};

	var wpmlScanLinkTargets = function ( start, count, isPosts ) {
		var message = self.button.data( isPosts ? 'post-message' : 'string-message' );
		jQuery.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				'last_processed': start,
				'number_to_process': count,
				'nonce': jQuery('[name=wpml-translate-link-targets]').val(),
				'action': isPosts ? 'WPML_Ajax_Update_Link_Targets_In_Posts' : 'WPML_Ajax_Update_Link_Targets_In_Strings'
			},
			success: function (response) {
				if (response.success) {
					self.numberFixed += response.data.links_fixed;
					if ( response.data.number_left > 0 ) {

						showCompletePercent( response.data.number_left, isPosts ? 'post' : 'string' );
						wpmlScanLinkTargets( response.data.last_processed + 1, 10, isPosts );
					} else {
						showCompletePercent( self.stringCount, 'string' );
						if ( isPosts && self.stringCount ) {
							wpmlScanLinkTargets( 0, 10, false );
						} else {
							self.button.prop('disabled', false);
							self.button.parent().find('.spinner').css('visibility', 'hidden');
							self.button.parent().find( '.results' ).html( self.button.data( 'complete-message').replace( '%s', self.numberFixed ) );
						}
					}

				}
			}
		});

	};

	var showCompletePercent = function( numberLeft, type ) {
		var total = type == 'post' ? self.postCount : self.stringCount,
			done = total - numberLeft,
			message = self.button.data( type + '-message' );

		message = message.replace( '%1$s', done );
		message = message.replace( '%2$s', total );

		self.button.parent().find( '.results' ).html( message );
	}

	init();

};

var translateLinkTargets = new WPML_TM.translateLinkTargets();

