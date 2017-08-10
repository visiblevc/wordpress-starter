/* <![CDATA[*/
jQuery(document).ready(function () {
	jQuery('.icl-admin-message-hide').on('click', function (event) {

		if (typeof(event.preventDefault) !== 'undefined' ) {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		var messageBox = jQuery(this).closest('.otgs-is-dismissible');
		if (messageBox) {
			var messageID = messageBox.attr('id');

			jQuery.ajax({
										url:      ajaxurl,
										type:     'POST',
										data:     {
											action:                 'icl-hide-admin-message',
											'icl-admin-message-id': messageID
										},
										dataType: 'json',
										success:  function (ret) {

											if (ret) {
												messageBox.fadeOut('slow', function () {
													messageBox.removeAttr('class');
													if (ret.type) {
														messageBox.addClass(ret.type);
													}
													messageBox.html(ret.text);
													messageBox.fadeIn();
												});
											} else {
												messageBox.fadeOut();
											}
										}
									});
		}
	});

	jQuery('a.icl-admin-message-link').on('click', function (event) {

		if (typeof(event.preventDefault) !== 'undefined' ) {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		jQuery.post(
			ajaxurl,
			{
				action: 'icl-hide-admin-message',
				'icl-admin-message-id': jQuery(this).parent().parent().attr('id')
			},
			function (response) {
			}
		);
	});
});
/*]]>*/