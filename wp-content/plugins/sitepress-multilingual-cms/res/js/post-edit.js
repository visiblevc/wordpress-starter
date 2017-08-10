/*globals icl_ajx_url */

/**
 * Created by andrea.
 * Date: 23/01/14
 * Time: 17:28
 */

jQuery(document).ready(function ($) {

	setupCopyButtons();

	var postEdit = postEdit || {};

	postEdit.$connect_translations_dialog = $('#connect_translations_dialog');
	postEdit.$no_posts_found_message = postEdit.$connect_translations_dialog.find('.js-no-posts-found');
	postEdit.$posts_found_container = postEdit.$connect_translations_dialog.find('.js-posts-found');
	postEdit.$ajax_loader = postEdit.$connect_translations_dialog.find('.js-ajax-loader');
	postEdit.$connect_translations_dialog_confirm = $("#connect_translations_dialog_confirm");

	postEdit.connect_element_translations_open = function(event) {

		if (typeof(event.preventDefault) !== 'undefined' ) {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		postEdit.$connect_translations_dialog.find('#post_search').val('');
		postEdit.$connect_translations_dialog.find('#assign_to_trid').val('');
		postEdit.$connect_translations_dialog.dialog('open');
		postEdit.connect_element_translations_data();

	};

	postEdit.connect_element_translations_data = function() {

		var $connect_translations_dialog_selector = $('#post_search', postEdit.$connect_translations_dialog );

		var trid = $('#icl_connect_translations_trid').val();
		var post_type = $('#icl_connect_translations_post_type').val();
		var source_language = $('#icl_connect_translations_language').val();
		var nonce = $('#_icl_nonce_get_orphan_posts').val();
		var data = 'icl_ajx_action=get_orphan_posts&source_language=' + source_language + '&trid=' + trid + '&post_type=' + post_type + '&_icl_nonce=' + nonce;

		postEdit.$ajax_loader.show();

		var request = $.ajax({
			type: "POST",
			url: icl_ajx_url,
			dataType: 'json',
			data: data
		});

		request.done(function( posts ) {

			var $assignPostButton = $('.js-assign-button');

			if ( posts.length > 0 ) {

				postEdit.$posts_found_container.show();
				postEdit.$no_posts_found_message.hide();
				$assignPostButton.prop('disabled', false);

				$connect_translations_dialog_selector.autocomplete({
					minLength: 0,
					source: posts,
					focus: function (event, ui) {
						$connect_translations_dialog_selector.val(ui.item.label);
						return false;
					},
					select: function (event, ui) {
						$connect_translations_dialog_selector.val(ui.item.label);
						$("#assign_to_trid").val(ui.item.value);
						return false;
					}
				})
					.focus()
					.data("ui-autocomplete")._renderItem = function (ul, item) {
					return $("<li>")
						.append(jQuery("<a></a>").text(item.label))
						.appendTo(ul);

				};
			} else {
				postEdit.$posts_found_container.hide();
				postEdit.$no_posts_found_message.show();
				$assignPostButton.prop('disabled', true);
			}

		});

		request.always(function() {
			postEdit.$ajax_loader.hide(); // Hide ajax loader always, no matter if ajax succeed or not.
		});

	};

	postEdit.connect_element_translations_init = function () {

		postEdit.$connect_translations_dialog.dialog(
			{
				dialogClass  : 'wpml-dialog otgs-ui-dialog',
				width        : 'auto',
				modal        : true,
				autoOpen     : false,
				closeOnEscape: true,
				buttons      : [
					{
						text   : postEdit.$connect_translations_dialog.data('cancel-label'),
						'class': 'button button-secondary alignleft',
						click  : function () {
							$(this).dialog("close");
						}
					}, {
						text   : postEdit.$connect_translations_dialog.data('ok-label'),
						'class': 'button button-primary js-assign-button',
						click  : function () {
							$(this).dialog("close");
							postEdit.connect_element_translations_do();
						}
					}
				]
			}
		);

	}(); // Auto executable function

	postEdit.connect_element_translations_do = function() {

		var trid = $("#assign_to_trid").val();
		var post_type = $('#icl_connect_translations_post_type').val();
		var post_id = $('#icl_connect_translations_post_id').val();
		var nonce = $('#_icl_nonce_get_posts_from_trid').val();

		var data = 'icl_ajx_action=get_posts_from_trid&trid=' + trid + '&post_type=' + post_type + '&_icl_nonce=' + nonce;

		var request = $.ajax({
			type: "POST",
			url: icl_ajx_url,
			dataType: 'json',
			data: data
		});

		request.done(function ( posts ) {

			if ( posts.length > 0 ) {
				var $list = $('#connect_translations_dialog_confirm_list');
				$list.empty();
				var $ul = $('<ul />').appendTo( $list );

				var translation_set_has_source_language = false;

				$.each(posts, function () {
					var $li  = $('<li>').append('<span></span>');
					$li.find('span').text('[' + this.language + '] ' + this.title);
					$li.appendTo ( $ul );
					if(this.source_language && !translation_set_has_source_language) {
						translation_set_has_source_language = true;
					}
				});

				var alert = $('<p>').append(jQuery('<strong></strong>').html(postEdit.$connect_translations_dialog.data('alert-text')));
				alert.appendTo($list);

				var set_as_source_checkbox = $('<input type="checkbox" value="1" name="set_as_source" />');

				if(!translation_set_has_source_language) {
					set_as_source_checkbox.attr('checked', 'checked');
				}
				var action = $('<label>').append(set_as_source_checkbox).append(postEdit.$connect_translations_dialog.data('set_as_source-text'));
				action.appendTo($list);

				postEdit.$connect_translations_dialog_confirm.dialog(
					{
						dialogClass: 'wpml-dialog otgs-ui-dialog',
						resizable  : false,
						width      : 'auto',
						autoOpen   : true,
						modal      : true,
						buttons    : [
							{
								text   : postEdit.$connect_translations_dialog_confirm.data('cancel-label'),
								'class': 'button button-secondary alignleft',
								click  : function () {
									$(this).dialog("close");
									postEdit.$connect_translations_dialog.dialog('open');
								}
							}, {
								text   : postEdit.$connect_translations_dialog_confirm.data('assign-label'),
								'class': 'button button-primary js-confirm-connect-this-post',
								click  : function () {

									var $confirmButton = $('.js-confirm-connect-this-post');
									$confirmButton.prop('disabled', true).removeClass('button-primary').addClass('button-secondary');

									$('<span class="spinner" />').appendTo($confirmButton);

									var nonce = $('#_icl_nonce_connect_translations').val();

									var data_object = {
										icl_ajx_action: 'connect_translations',
										post_id       : post_id,
										post_type     : post_type,
										new_trid      : trid,
										_icl_nonce    : nonce,
										set_as_source : (set_as_source_checkbox.is(':checked') ? 1 : 0)
									};

									var request = $.ajax(
										{
											type    : "POST",
											url     : icl_ajx_url,
											dataType: 'json',
											data    : data_object
										}
									);

									request.done(
										function (result) {
											if (result) {
												postEdit.$connect_translations_dialog.dialog("close");
												location.reload();
											}
										}
									);
								}
							}
						]
					}
				);
			}
		}
		);
	};

	$('#icl_document_connect_translations_dropdown')
		.find('.js-set-post-as-source')
		.on('click', postEdit.connect_element_translations_open );

	/**
	 * HOTFIX DIALOG BOX
	 * Remove when WooCommerce does not include jquery-ui smoothness anymore
	 **/
	var jQueryUI = $( '#jquery-ui-style-css[href*="smoothness"]' ),
		jQuerySmoothnessHref;

	// if jquery ui smoothness css is loaded
	if( jQueryUI.length ) {
		// click on Connect with translations
		$( 'body' ).on( 'click', '#icl_document_connect_translations_dropdown .js-set-post-as-source', function() {
			var connectDialog = $( '[aria-describedby="connect_translations_dialog"]'), intervalCheckDialog;

			// abort if dialog does not exists
			if( ! connectDialog.length ) return false;

			// backup href of jquery ui smoothness
			jQuerySmoothnessHref = jQueryUI.attr( 'href' );

			// remove jquery ui smoothness css
			jQueryUI.attr( 'href', '' );

			// check every 250ms if connect translations dialog is still open
			intervalCheckDialog = setInterval( function() {
				// if dialog is not open anymore
				if( ! connectDialog.is(':visible') ) {
					if( $( '.ui-widget-overlay' ).length == 0 ) {
						// reapply jquery ui smoothness css again
						jQueryUI.attr( 'href', jQuerySmoothnessHref );
						// stop interval
						clearInterval( intervalCheckDialog );
					}
				}
			}, 250 );
		} );
	}
	/* HOTFIX END */

	var $submit_post_form = $('#post');
	$submit_post_form.find(':input').on('change', function(e) {
		edit_form_change();
	});

	window.edit_form_change = function() {
		$('#icl-duplicate-post').attr( 'data-changed', 'true' );
	}

	$submit_post_form.on('submit', function (e) {
		var $trigger  = $('#icl-duplicate-post');
		if ($trigger.length > 0 && $trigger.data('changed') === true ) {
			e.preventDefault();
			var $answer = window.confirm(icl_duplicate_data.icl_duplicate_message);
			var $spinner = $('#publishing-action .spinner');
			if ($answer) {
				$spinner.toggleClass('is-active');
				$.ajax({
					method: "POST",
					url: ajaxurl,
					data: {
						action: 'check_duplicate',
						post_id: $trigger.val(),
						icl_duplciate_nonce: $('#icl-duplicate-post-nonce').val()
					}
				})
					.success(function ($resp) {
						$spinner.toggleClass('is-active');
						if ($resp.data) {
							$('#icl-duplicate-post').remove();
							$submit_post_form.submit();
						} else {
							alert(icl_duplicate_data.icl_duplicate_fail);
						}
					})
					.error(function () {
						$spinner.toggleClass('is-active');
						alert(icl_duplicate_data.icl_duplicate_fail);
					});
			}
		}
	});
});

function setupCopyButtons() {
	jQuery('#icl_translate_independent').click(function () {
		jQuery(this).attr('disabled', 'disabled').after(icl_ajxloaderimg);
		jQuery.ajax({
			type: "POST", url: icl_ajx_url,
			data: "icl_ajx_action=reset_duplication&post_id=" + jQuery('#post_ID').val() + '&_icl_nonce=' + jQuery('#_icl_nonce_rd').val(),
			success: function (msg) {
				location.reload()
			}
		});
	});
	jQuery('#icl_set_duplicate').click(function () {
		if (confirm(jQuery(this).next().html())) {
			jQuery(this).attr('disabled', 'disabled').after(icl_ajxloaderimg);
			var icl_set_duplicate = jQuery('#icl_set_duplicate');
			var wpml_original_post_id = icl_set_duplicate.data('wpml_original_post_id');
			var post_lang = icl_set_duplicate.data('post_lang');
			jQuery.ajax({
				type: "POST", url: icl_ajx_url,
				data: "icl_ajx_action=set_duplication&wpml_original_post_id=" + wpml_original_post_id + '&_icl_nonce=' + jQuery('#_icl_nonce_sd').val()  + '&post_lang=' + post_lang,
				success: function (msg) {
					location.replace(
						location.href.replace('post-new.php', 'post.php').replace(/&trid=([0-9]+)/, '') + '&post=' + msg.data.id + '&action=edit');
				}
			});
		}
	});
}