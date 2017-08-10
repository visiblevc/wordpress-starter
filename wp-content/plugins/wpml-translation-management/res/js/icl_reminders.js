jQuery(document).ready(function(){

	// Added box resize for regular 'thickbox'
	icl_tb_set_size('a.icl_regular_thickbox');
});



function icl_tb_init(domChunk) {
    // copied from thickbox.js
    // add code so we can detect closure of popup

    jQuery(domChunk).unbind('click');

    jQuery(domChunk).click(function(){
    var t = this.title || this.name || "ICanLocalize Reminder";
    var a = this.href || this.alt;
    var g = this.rel || false;
    tb_show(t,a,g);

    jQuery('#TB_window').bind('unload', function(){
        url = location.href;
        if (url.indexOf('content-translation.php') != -1) {

            url = url.replace(/&icl_refresh_langs=1/g, '');
            url = url.replace(/&show_config=1/g, '');
            url = url.replace(/#.*/,'');
            if(jQuery('#icl_account_setup').is(':visible')) {
                location.href = url + "&icl_refresh_langs=1&show_config=1"
            } else {
                location.href = url + "&icl_refresh_langs=1"
            }
        } else if (url.indexOf('support.php') != -1) {
			location.href = url;
		}
        });

    this.blur();
    return false;
    });
}

function icl_tb_set_size(domChunk) {
    if (typeof(tb_getPageSize) != 'undefined') {

        var pagesize = tb_getPageSize();
        jQuery(domChunk).each(function() {
            var url = jQuery(this).attr('href');
            url += '&width=' + (pagesize[0] - 150);
            url += '&height=' + (pagesize[1] - 150);
            url += '&tb_avail=1'; // indicate that thickbox is available.
            jQuery(this).attr('href', url);
        });
    }
}


function icl_thickbox_reopen(url) {
  tb_remove();
  if (url.indexOf("?") == -1) {
    var glue = '?';
  } else {
    var glue = '&';
  }
  jQuery('#iclThickboxReopenLink').remove();
  jQuery('body').prepend('<a id="iclThickboxReopenLink" href="'+url+glue+'keepThis=true&amp;TB_iframe=true" class="thickbox" style="display:none;">test</a>');
  icl_tb_set_size('#iclThickboxReopenLink');
  jQuery('#iclThickboxReopenLink').addClass('initThickbox-processed').click(function() {
    var t = this.title || this.name || null;
    var a = this.href || this.alt;
    var g = this.rel || false;
    tb_show(t,a,g);
    this.blur();
    return false;
  });
  window.setTimeout(function() {
    jQuery('#iclThickboxReopenLink').trigger('click');
    jQuery('#TB_window').bind('unload', function() {
      window.location.href = unescape(window.location); // Add .pathname to get URL without query
    });
  }, 1000);
}

function icl_thickbox_refresh() {
  window.location.href = unescape(window.location);
}
