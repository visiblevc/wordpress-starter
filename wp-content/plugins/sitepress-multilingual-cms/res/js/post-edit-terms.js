/*globals ajaxurl, icl_post_edit_messages */

jQuery(document).ready(
	function () {
		var language_selector;
		/* Check if we have a language switcher present. */
		language_selector = jQuery('select[name="icl_post_language"]');
		if (jQuery('[name="_wpml_root_page"]').length !== 0) {
			jQuery('#edit-slug-box').hide();
		}

		if (language_selector.length !== 0) {
			language_selector.on('change', iclPostLanguageAskConfirmation);
			iclSwitchPostLanguage();
		} else {
			// just add a hidden field with the information and the same id as the language switcher to the dom
			jQuery.ajax(
				{
					type:     "POST",
					url:      ajaxurl,
					dataType: 'json',
					data:     {
						action: 'wpml_get_default_lang'
					},
					success:  function (response) {
						var hidden_language_field = jQuery('<input id="icl_post_language" type="hidden"/>');
						hidden_language_field.val(response.data);
						jQuery(".wrap").append(hidden_language_field);
						iclSwitchPostLanguage();
					}
				}
			);
		}
	}
);

function iclGetSwitchLanguageConfirmation(){
    var lang_switch_confirm_html;
    var defer = jQuery.Deferred();

    /** @namespace icl_post_edit_messages.switch_language_title */
    /** @namespace icl_post_edit_messages.switch_language_alert */
    /** @namespace icl_post_edit_messages.connection_loss_alert */
    lang_switch_confirm_html = '<div id="icl_confirm_lang_switch">';
    lang_switch_confirm_html += '<h2>{switch_language_title}</h2>';
    lang_switch_confirm_html += '<p>{switch_language_message}</p>';
    lang_switch_confirm_html += '<p>{switch_language_confirm}</p>';
    lang_switch_confirm_html += '</div>';

	// make sure the title is html entities encoded.
    var post_name = WPML_core.htmlentities(jQuery('#title').val());

    lang_switch_confirm_html = lang_switch_confirm_html.replace('{switch_language_title}',icl_post_edit_messages.switch_language_title);
    lang_switch_confirm_html = lang_switch_confirm_html.replace('{switch_language_message}',icl_post_edit_messages.switch_language_message);
    lang_switch_confirm_html = lang_switch_confirm_html.replace('{switch_language_confirm}',icl_post_edit_messages.switch_language_confirm);
    lang_switch_confirm_html = lang_switch_confirm_html.replace('{post_name}', '<i>' + post_name + '</i>');

		jQuery(lang_switch_confirm_html).dialog(
			{
				modal  : true,
				width  : 'auto',
				buttons: {
					Ok    : function () {
						defer.resolve();
						jQuery(this).dialog("close");

					},
					Cancel: function () {
						defer.reject();
						jQuery(this).dialog("close");
					}
				}
			}
		);
    return defer.promise();
}

function iclPostLanguageAskConfirmation() {

	var post_language_switcher = jQuery('#icl_post_language');
	var previous_post_language = post_language_switcher.data('last_lang');

	jQuery('#edit-slug-buttons').find('> .cancel').click();


    iclGetSwitchLanguageConfirmation().done(function() {
        iclSwitchPostLanguage();
    }).fail(function() {
        post_language_switcher.val(previous_post_language);
    });
}

function iclSwitchPostLanguage() {
	var post_language_switcher = jQuery('#icl_post_language');
	var new_post_language = post_language_switcher.val();
	var previous_post_language = post_language_switcher.data('last_lang');
	var post_id = jQuery('#post_ID').val();
	if (!previous_post_language) {
		post_language_switcher.data('last_lang', new_post_language);
	} else {
		jQuery.ajax(
			{
				type:     "POST",
				url:      ajaxurl,
				dataType: 'json',
				data:     {
					wpml_from:    previous_post_language,
					action:       'wpml_switch_post_language',
                    _icl_nonce: icl_post_edit_messages._nonce,
					wpml_to:      new_post_language,
					wpml_post_id: post_id
				},
				success:  function () {
					post_language_switcher.data('last_lang', new_post_language);
					var url = jQuery(location).attr('href');
                    if (/lang=/.test(url)){
                        url = url.replace(/([\?&])(lang=)[^&#]*/, '$1$2' + new_post_language);
                    }else{
                        var sep = (url.indexOf('?') > -1) ? '&' : '?';
                        url = url + sep + 'lang=' + new_post_language;
                    }

                    window.location.replace(url);
				}
			}
		);
	}
}