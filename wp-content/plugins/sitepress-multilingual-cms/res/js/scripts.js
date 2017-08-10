/*global jQuery, icl_ajx_url, icl_ajx_saved, icl_ajx_error, icl_ajxloaderimg_src */

var WPML_core = WPML_core || {};

WPML_core.htmlentities = function( s ) {
	return jQuery("<div/>").text( s ).html()
};

WPML_core.PostEditDuplicates = function($) {
    var self = this;

    var _init = function()
	{
		self.initialize_dulicates_click_handler();
	};

	self.initialize_dulicates_click_handler = function () {
        var dupesInputs = jQuery('#post').find('input[name="icl_dupes[]"]');
        var makeDuplicates = jQuery('#icl_make_duplicates');
		dupesInputs.off('change');
        dupesInputs.on('change', _duplicate_click_handler);
        makeDuplicates.off('click');
        makeDuplicates.on('click', _make_duplicate_click_handler);
	};

	var _duplicate_click_handler = function () {
		if(jQuery('#post').find('input[name="icl_dupes[]"]:checked').length > 0){
			jQuery('#icl_make_duplicates').show().removeAttr('disabled');
		}else{
			jQuery('#icl_make_duplicates').hide().attr('disabled', 'disabled');
		}
	};

    var _make_duplicate_click_handler = function() {
        var langs = [];
        jQuery('#post').find('input[name="icl_dupes[]"]:checked').each(function(){langs.push(jQuery(this).val())});
        langs = langs.join(',');
        jQuery(this).attr('disabled', 'disabled').after(icl_ajxloaderimg);
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,data: "icl_ajx_action=make_duplicates&post_id=" + jQuery('#post_ID').val() + '&langs=' + langs + '&_icl_nonce=' + jQuery('#_icl_nonce_mdup').val(),
            success: function(msg){location.reload()}
        });
    };
	
	_init();
};

jQuery(document).ready(function($){
	WPML_core.post_edit_duplicates = new WPML_core.PostEditDuplicates();
    var catAdder = jQuery('#category-adder');
    if (catAdder.html()) {
        catAdder.prepend('<p>' + icl_cat_adder_msg + '</p>');
    }
    jQuery('select[name="icl_translation_of"]').change(function(){jQuery('#icl_translate_options').fadeOut();});
    jQuery('#icl_dismiss_help').click(iclDismissHelp);
    jQuery('#icl_dismiss_upgrade_notice').click(iclDismissUpgradeNotice);
    jQuery(document).delegate('a.icl_toggle_show_translations', 'click', iclToggleShowTranslations);

    /* needed for tagcloud */
    oldajaxurl = false;

    jQuery(document).delegate("#icl_make_translatable_submit", 'click', icl_make_translatable);

    icl_admin_language_switcher();

    jQuery('a.icl_user_notice_hide').click(icl_hide_user_notice);

    jQuery(document).delegate('#wpml_als_help_link', 'click', function () {
        var adminBarALS = jQuery('#wp-admin-bar-WPML_ALS');
        var alsHelpPopup = jQuery('#icl_als_help_popup');
        adminBarALS.removeClass('hover');
        alsHelpPopup.css('left', adminBarALS.position().left - 10);
        alsHelpPopup.show();
    });

    icl_popups.attach_listeners();

    var slugTranslation = jQuery('#icl_slug_translation');
    if (slugTranslation.length) {
        iclSaveForm_success_cb.push(function (form, response) {
            if (form.attr('name') === 'icl_slug_translation') {
                if (response[1] === 1) {
                    jQuery('.icl_slug_translation_choice').show();
                } else {
                    jQuery('.icl_slug_translation_choice').hide();
                }
            } else if (form.attr('name') === 'icl_custom_posts_sync_options') {
                jQuery('.icl_st_slug_tr_warn').hide();
            }
        });

        slugTranslation.submit(iclSaveForm);
        jQuery('.icl_slug_translation_choice input[type=checkbox]').change(function () {
            var table_row = jQuery(this).closest('tr');
            var cpt_slugs = jQuery(table_row).find('.js-cpt-slugs');

            if (jQuery(this).prop('checked')) {
                cpt_slugs.show();
            }
            else {
                cpt_slugs.hide();
            }
        });
    }

    jQuery('#icl_custom_posts_sync_options').submit(function(){
        iclHaltSave = false;
        var slugTranslationChoice = jQuery('.icl_slug_translation_choice input[type=text].js-translate-slug');
        var ajaxResponseCP = jQuery('#icl_ajx_response_cp');
        slugTranslationChoice.removeClass('icl_error_input');
        ajaxResponseCP.html('').fadeOut();
        slugTranslationChoice.each(function(){
            if(jQuery(this).is(':visible') && !jQuery(this).is(':disabled') && jQuery.trim(jQuery(this).val()) === ''){
                jQuery(this).addClass('icl_error_input');
                iclHaltSave = true;
            }
        });

        if(iclHaltSave){
			if (confirm( jQuery('#js_custom_posts_sync_button').data('message') )) {
				iclHaltSave = false;
		        slugTranslationChoice.removeClass('icl_error_input');
			} 
        }
    });

    jQuery('.icl_sync_custom_posts').change(function(){
        var val = jQuery(this).val();
        var table_row = jQuery(this).closest('tr');
        var cpt_slugs = jQuery(table_row).find('.js-cpt-slugs');
        var icl_slug_translation = jQuery(table_row).find(':checkbox');
        if (val == 1) {
            icl_slug_translation.closest('.icl_slug_translation_choice').show();
            if( icl_slug_translation.prop('checked') && cpt_slugs) {
                cpt_slugs.show();
            }
        } else if(cpt_slugs) {
            icl_slug_translation.closest('.icl_slug_translation_choice').hide();
            cpt_slugs.hide();
        }

    });

    jQuery(document).delegate('.icl_error_input', 'focus', function() {
        jQuery(this).removeClass('icl_error_input');
    });

    $('.js-toggle-colors-edit').on('click', function(e) {
        e.preventDefault();

        var $target = $( $(this).attr('href') );
        var $caret = $(this).find('.js-arrow-toggle');

        if ( $target.is(':visible') ) {
            $target.slideUp();
            $caret.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
        }
        else {
            $target.slideDown();
            $caret.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
        }

        return false;
    });

    $('#js-post-availability').on('change', function(e) {

        var $target = $( $(this).data('target') );

        if ( $(this).prop('checked') ) {
            $target.show();
        }
        else {
            $target.hide();
        }

    });

    $('.js-wpml-navigation-links a').on('click', function(e) { // prevent default scrolling for navigation links
        e.preventDefault();

        var $target = $( $(this).attr('href') );

        if ( $target.length !== 0 ) {
            var offset = 0;
            var wpAdminBar = jQuery('#wpadminbar');
            if ( wpAdminBar.length !== 0 ) {
                offset = wpAdminBar.height();
            }

            $('html, body').animate({
                scrollTop: $target.offset().top - offset
             }, 300, function() {
                var $header = $target.find('.wpml-section-header h3');
                $header.addClass('active');
                console.log($header);
                setTimeout(function(){
                    $header.removeClass('active');
                }, 700);
             });
        }

        return false;
    });

	var icl_untranslated_blog_posts = $("input[name=icl_untranslated_blog_posts]");
	var icl_untranslated_blog_posts_help = $('#icl_untranslated_blog_posts_help');

	var update_icl_untranslated_blog_posts = function () {
		//Get the value of currently selected radio option
		var value = icl_untranslated_blog_posts.filter(':checked').val();

		if (value == 0) {
			icl_untranslated_blog_posts_help.fadeOut('slow');
		} else {
			icl_untranslated_blog_posts_help.fadeIn('slow');
		}
	};

	update_icl_untranslated_blog_posts();
	icl_untranslated_blog_posts.bind('click', update_icl_untranslated_blog_posts);
});

function fadeInAjxResp(spot, msg, err){
    if(err != undefined){
        col = jQuery(spot).css('color');
        jQuery(spot).css('color','red');
    }
    jQuery(spot).html(msg);
    jQuery(spot).fadeIn();
    window.setTimeout(fadeOutAjxResp, 3000, spot);
    if(err != undefined){
        jQuery(spot).css('color',col);
    }
}

function fadeOutAjxResp(spot){
    jQuery(spot).fadeOut();
}

var icl_ajxloaderimg = '<img src="'+icl_ajxloaderimg_src+'" alt="loading" width="16" height="16" />';

var iclHaltSave = false; // use this for multiple 'submit events'
var iclSaveForm_success_cb = [];
function iclSaveForm() {

	if (iclHaltSave) {
		return false;
	}
	var form_name = jQuery(this).attr('name');
	jQuery('form[name="' + form_name + '"] .icl_form_errors').html('').hide();
	var ajx_resp = jQuery('form[name="' + form_name + '"] .icl_ajx_response').attr('id');
	fadeInAjxResp('#' + ajx_resp, icl_ajxloaderimg);
	var serialized_form_data = jQuery(this).serialize();
	jQuery.ajax({
		type: "POST",
		url: icl_ajx_url,
		data: "icl_ajx_action=" + jQuery(this).attr('name') + "&" + serialized_form_data,
		success: function (msg) {
			var spl = msg.split('|');
			if (parseInt(spl[0]) == 1) {
				fadeInAjxResp('#' + ajx_resp, icl_ajx_saved);
				for (var i = 0; i < iclSaveForm_success_cb.length; i++) {
					iclSaveForm_success_cb[i](jQuery('form[name="' + form_name + '"]'), spl);
				}
				if (form_name == 'icl_slug_translation' ||
						form_name == 'wpml_ls_settings_form' ||
						form_name == 'icl_custom_posts_sync_options') {
					location.reload();
				}
			} else {
				var icl_form_errors = jQuery('form[name="' + form_name + '"] .icl_form_errors');
				var error_html = (typeof spl[1] != 'undefined') ? spl[1] : spl[0];
				icl_form_errors.html(error_html);
				icl_form_errors.fadeIn();
				fadeInAjxResp('#' + ajx_resp, icl_ajx_error, true);
			}
		}
	});
	return false;
}

function iclDismissHelp(){
    var thisa = jQuery(this);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=dismiss_help&_icl_nonce=" + jQuery('#icl_dismiss_help_nonce').val(),
            success: function(msg){
                thisa.closest('#message').fadeOut();
            }
    });
    return false;
}

function iclDismissUpgradeNotice(){
    var thisa = jQuery(this);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=dismiss_upgrade_notice&_icl_nonce=" + jQuery('#_icl_nonce_dun').val(),
            success: function(msg){
                thisa.parent().parent().fadeOut();
            }
    });
    return false;
}

function iclToggleShowTranslations(){
    jQuery('a.icl_toggle_show_translations').toggle();
    jQuery('#icl_translations_table').toggle();
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=toggle_show_translations&_icl_nonce=" + jQuery('#_icl_nonce_tst').val()
    });
    return false;
}

function icl_copy_from_original(lang, trid){
	jQuery('#icl_cfo').after(icl_ajxloaderimg).attr('disabled', 'disabled');

    //has visual = set to normal non-html editing mode
    var ed;
    var content_type = (typeof tinyMCE !== 'undefined' && ( ed = tinyMCE.get('content') ) && !ed.isHidden() && ed.hasVisual === true) ? 'rich' : 'html';
    var excerpt_type = (typeof tinyMCE !== 'undefined' && ( ed = tinyMCE.get('excerpt') ) && !ed.isHidden() && ed.hasVisual === true) ? 'rich' : 'html';

	// figure out all available editors and their types
	jQuery.ajax({
		            type:     "POST",
		            dataType: 'json',
		            url:      icl_ajx_url,
		            data:     "icl_ajx_action=copy_from_original&lang=" + lang + '&trid=' + trid + '&content_type=' + content_type + '&excerpt_type=' + excerpt_type + '&_icl_nonce=' + jQuery('#_icl_nonce_cfo_' + trid).val(),
		            success:  function (msg) {
			            if (msg.error) {
				            alert(msg.error);
			            } else {
				            try {
					            if (msg.content) {
						            if (typeof tinyMCE !== 'undefined' && ( ed = tinyMCE.get('content') ) && !ed.isHidden()) {
							            ed.focus();
							            if (tinymce.isIE) {
								            ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
							            }
							            ed.execCommand('mceInsertContent', false, msg.content);
						            } else {
							            wpActiveEditor = 'content';
							            edInsertContent(edCanvas, msg.content);
						            }
					            }
					            if (typeof msg.title !== "undefined") {
						            jQuery('#title-prompt-text').hide();
						            jQuery('#title').val(msg.title);
					            }
					            //handling of custom fields
					            //these have to be of array type with the indexes editor_type editor_name and value
					            //possible types are editor or text
					            //in case of text te prompt to be removed might have to be provided
					            for (var element in msg.customfields) {
						            if (msg.customfields.hasOwnProperty(element) && msg.customfields[element].editor_type === 'editor') {
							            if (typeof tinyMCE !== 'undefined' && ( ed = tinyMCE.get(msg.customfields[element].editor_name) ) && !ed.isHidden()) {
								            ed.focus();
								            if (tinymce.isIE) {
									            ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
								            }
								            ed.execCommand('mceInsertContent', false, msg.customfields[element].value);
							            } else {
								            wpActiveEditor = msg.customfields[element].editor_name;
								            edInsertContent(edCanvas, msg.customfields[element].value);
							            }
						            } else {
							            jQuery('#' + msg.customfields[element].editor_name).val(msg.customfields[element].value);
						            }
					            }
				            } catch (err) {
                            }
			            }
			            jQuery('#icl_cfo').next().fadeOut();
		            }
	            });

	return false;
}

function icl_make_translatable(){
    var that = jQuery(this);
    jQuery(this).attr('disabled', 'disabled');
    jQuery('#icl_div_config').find('.icl_form_success').hide();
    var iclMakeTranslatable = jQuery('#icl_make_translatable');
    var translate = iclMakeTranslatable.attr('checked') ? 1 : 0;
    var custom_post = iclMakeTranslatable.val();
    var custom_taxs_on = [];
    var custom_taxs_off = [];
    jQuery(".icl_mcs_custom_taxs").each(function(){
        if(jQuery(this).attr('checked')){
            custom_taxs_on.push(jQuery(this).val());
        }else{
            custom_taxs_off.push(jQuery(this).val());
        }

    });

    var cfnames = [];
    var cfvals = [];
    jQuery('.icl_mcs_cfs:checked').each(function(){
        if(!jQuery(this).attr('disabled')){
            cfnames.push(jQuery(this).attr('name').replace(/^icl_mcs_cf_/,''));
            cfvals.push(jQuery(this).val());
        }
    });

    jQuery.post(location.href,
        {
                'post_id'       : jQuery('#post_ID').val(),
                'icl_action'    : 'icl_mcs_inline',
                'custom_post'   : custom_post,
                'translate'     : translate,
                'custom_taxs_on[]'   : custom_taxs_on,
                'custom_taxs_off[]'   : custom_taxs_off,
                'cfnames[]'   : cfnames,
                'cfvals[]'   : cfvals,
                '_icl_nonce' : jQuery('#_icl_nonce_imi').val()

        },
        function(data) {
            that.removeAttr('disabled');
            if(translate){
                var iclDiv = jQuery('#icl_div');
                if (iclDiv.length > 0) {
                    iclDiv.remove();
                }
                var prependTo = jQuery('#side-sortables');
                prependTo = prependTo.html() ? prependTo : jQuery('#normal-sortables');
                prependTo.prepend(
                    '<div id="icl_div" class="postbox">' + jQuery(data).find('#icl_div').html() + '</div>'
                );
                jQuery('#icl_mcs_details').html(jQuery(data).find('#icl_mcs_details').html());
            }else{
                jQuery('#icl_div').hide();
                jQuery('#icl_mcs_details').html('');
            }
            jQuery('#icl_div_config').find('.icl_form_success').fadeIn();
			
			WPML_core.post_edit_duplicates.initialize_dulicates_click_handler();
        }
    );

    return false;
}

function icl_admin_language_switcher(){
    jQuery('#icl-als-inside').width( jQuery('#icl-als-actions').width() - 4 );
    jQuery('#icl-als-toggle, #icl-als-inside').bind('mouseenter', function() {
        jQuery('#icl-als-inside').removeClass('slideUp').addClass('slideDown');
        setTimeout(function() {
            if ( jQuery('#icl-als-inside').hasClass('slideDown') ) {
                jQuery('#icl-als-inside').slideDown(100);
                jQuery('#icl-als-first').addClass('slide-down');
            }
        }, 200);
    }).bind('mouseleave', function() {
        jQuery('#icl-als-inside').removeClass('slideDown').addClass('slideUp');
        setTimeout(function() {
            if ( jQuery('#icl-als-inside').hasClass('slideUp') ) {
                jQuery('#icl-als-inside').slideUp(100, function() {
                    jQuery('#icl-als-first').removeClass('slide-down');
                });
            }
        }, 300);
    });

    jQuery('#show-settings-link, #contextual-help-link').bind('click', function(){
        jQuery('#icl-als-wrap').toggle();
    })
}

function icl_hide_user_notice(){
    var notice = jQuery(this).attr('href').replace(/^#/, '');
    var thisa = jQuery(this);

    jQuery.ajax({
        type: "POST",
        dataType: 'json',
        url: icl_ajx_url,
        data: "icl_ajx_action=save_user_preferences&user_preferences[notices]["+notice+"]=1&_icl_nonce="+jQuery('#_icl_nonce_sup').val(),
        success: function(msg){
            thisa.parent().parent().fadeOut();
        }
    });

    return false;
}

function icl_cf_translation_preferences_submit(cf, obj) {
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: 'action=wpml_ajax&icl_ajx_action=wpml_cf_translation_preferences&translate_action='+obj.parent().children('input:[name="wpml_cf_translation_preferences['+cf+']"]:checked').val()+'&'+obj.parent().children('input:[name="wpml_cf_translation_preferences_data_'+cf+'"]').val() + '&_icl_nonce = ' + jQuery('#_icl_nonce_cftpn').val(),
        cache: false,
        error: function(html){
            jQuery('#wpml_cf_translation_preferences_ajax_response_'+cf).html('Error occured');
        },
        beforeSend: function(html){
            jQuery('#wpml_cf_translation_preferences_ajax_response_'+cf).html(icl_ajxloaderimg);
        },
        success: function(html){
            jQuery('#wpml_cf_translation_preferences_ajax_response_'+cf).html(html);
        },
        dataType: 'html'
    });

}

/* icl popups */
var icl_popups = {
    attach_listeners: function () {
        jQuery('.icl_pop_info_but').click(function () {

            jQuery('.icl_pop_info').hide();
            var pop = jQuery(this).next();

            var _tdoffset = 0;
            var _p = pop.parent().parent();
            if (_p[0]['nodeName'] == 'TD') {
                _tdoffset = _p.width() - 30;
            }

            pop.show(function () {
                var animate = {};
                var fold = jQuery(window).width() + jQuery(window).scrollLeft();
                if (fold < pop.offset().left + pop.width()) {
                    animate.left = '-=' + (pop.width() - _tdoffset);
                }
                if (parseInt(jQuery(window).height() + jQuery(window).scrollTop()) < parseInt(pop.offset().top) + pop.height()) {
                    animate.top = '-=' + pop.height();
                }
                if (animate) pop.animate(animate);
            });
        });
        jQuery('.icl_pop_info_but_close').click(function () {
            jQuery(this).parent().fadeOut();
        });
    }
};
