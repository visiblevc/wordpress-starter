/*globals jQuery, ajaxurl */

jQuery(document).ready(function () {
	'use strict';

	var otgsNotice = jQuery('.otgs-notice');
	var otgsCollapseText = '.otgs-notice-collapse-text';
	var otgsCollapsedText = '.otgs-notice-collapsed-text';

	jQuery( otgsCollapsedText ).hide();

	var preventDefaultEvent = function (event) {
		if (typeof(event.preventDefault) !== 'undefined') {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}
	};

	var noticeAction = function (noticeBox, action) {
		if (noticeBox) {
			var ajaxAction  = 'otgs-' + action + '-notice';
			var noticeId    = _.escape( noticeBox.data('id') );
			var noticeGroup = noticeBox.data('group');
			var nonce       = noticeBox.data('nonce');

			jQuery.ajax({
				url:      ajaxurl,
				type:     'POST',
				data:     {
					action:  ajaxAction,
					'id':    noticeId,
					'group': noticeGroup,
					nonce:   nonce
				},
				dataType: 'json'
			});
		}
	};

	var toggleNotice = function ( contentToMinimize, contentToMaximize ) {
		contentToMinimize.toggle();
		contentToMaximize.toggle();
	};

	otgsNotice.on('click', '.notice-dismiss, a.otgs-dismiss-link', function (event) {
		preventDefaultEvent(event);

		var noticeBox = jQuery(this).closest('.is-dismissible');
		noticeAction(noticeBox, 'dismiss');
	});

	otgsNotice.on('click', '.notice-hide, a.otgs-hide-link', function (event) {
		preventDefaultEvent(event);

		var noticeBox = jQuery(this).closest('.is-dismissible');
		noticeAction(noticeBox, 'hide');
	});

	otgsNotice.on('click', '.otgs-notice-collapse-hide', function (event) {
		preventDefaultEvent(event);

		jQuery( this ).toggle();
		var noticeCollapseText = jQuery(this).siblings( otgsCollapseText );
		var noticeCollapsedText = jQuery(this).siblings( otgsCollapsedText );
		toggleNotice( noticeCollapseText, noticeCollapsedText );
	});

	otgsNotice.on('click', '.otgs-notice-collapse-show', function (event) {
		preventDefaultEvent(event);
		
		jQuery(this).closest( otgsCollapsedText ).siblings( '.otgs-notice-collapse-hide' ).toggle();
		var noticeCollapseCollapseText = jQuery(this).closest( otgsCollapsedText ).siblings( otgsCollapseText );
		var noticeCollapseCollapsedText = jQuery(this).closest( otgsCollapsedText );
		toggleNotice( noticeCollapseCollapseText, noticeCollapseCollapsedText );
	});

	otgsNotice.on('click', '.notice-action.notice-action-link', function (event) {
		var groupToDismiss = jQuery(this).data('dismiss-group');
		var nonce = jQuery(this).data('nonce');
		var jsCallback = jQuery(this).data('js-callback');

		if (groupToDismiss) {

			if (jsCallback && typeof window[jsCallback] === 'function') {
				window[jsCallback](jQuery(this), function () {
					dismissGroup(groupToDismiss, nonce);
				});
			} else {
				dismissGroup(groupToDismiss, nonce);
			}
		}
	});

	var dismissGroup = function (groupToDismiss, nonce) {
		jQuery.ajax({
									url:      ajaxurl,
									type:     'POST',
									data:     {
										action:  'otgs-dismiss-group',
										group: groupToDismiss,
										nonce: nonce
									},
									dataType: 'json',
									complete: function () {
										location.reload();
									}
								});
	};
});