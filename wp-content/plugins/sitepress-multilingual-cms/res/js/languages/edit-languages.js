jQuery(document).ready(function(){
    jQuery("#icl_edit_languages_add_language_button").click(function(){
        jQuery(this).fadeOut('fast',function(){jQuery("#icl_edit_languages_table tr:last, .icl_edit_languages_show").show();});
        jQuery('#icl_edit_languages_ignore_add').val('false');
    });
    jQuery("#icl_edit_languages_cancel_button").click(function(){
        jQuery(this).fadeOut('fast',function(){
            jQuery("#icl_edit_languages_add_language_button").show();
            jQuery(".icl_edit_languages_show").hide();
            jQuery("#icl_edit_languages_table").find("tr:last input").each(function(){
                jQuery(this).val('');
            });
            jQuery('#icl_edit_languages_ignore_add').val('true');
            jQuery('#icl_edit_languages_form').find(':submit').attr('disabled','disabled');
        });
    });
    jQuery('.icl_edit_languages_use_upload').click(function(){
        jQuery(this).closest('ul').find('.wpml-edit-languages-flag-use-field').hide();
        jQuery(this).closest('ul').find('.wpml-edit-languages-flag-upload-wrapper').show();
    });
    jQuery('.icl_edit_languages_use_field').click(function(){
        jQuery(this).closest('ul').find('.wpml-edit-languages-flag-upload-wrapper').hide();
        jQuery(this).closest('ul').find('.wpml-edit-languages-flag-use-field').show();
    });
    jQuery('#icl_edit_languages_form').find(':submit').attr('disabled','disabled');
    jQuery('#icl_edit_languages_form input, #icl_edit_languages_form select').click(function(){
        jQuery('#icl_edit_languages_form').find(':submit').removeAttr('disabled');
    });
});