addLoadEvent(function () {
    var icl_lang_options = jQuery('#icl_lang_options');
    if (icl_lang_options.length) {
        icl_lang_options.insertBefore(jQuery('#post_id'));
        icl_lang_options.fadeIn();
    }
});

addLoadEvent(function () {
    if (language_items.length) {
        jQuery(".subsubsub").append('<br /><span id="icl_subsubsub"><\/span><br />');
        var languages_filter = jQuery(language_items.join(' | '));
        languages_filter.appendTo('#icl_subsubsub');
    }
});
