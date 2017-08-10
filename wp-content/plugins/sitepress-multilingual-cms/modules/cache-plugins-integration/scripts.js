jQuery(document).ready(function(){
    jQuery('#wpml_cpi_options :radio').change(function(){
        var thisr = jQuery(this);
        jQuery.ajax({
                type: "POST",
                url: icl_ajx_url,
                data: "icl_ajx_action=wpml_cpi_options&automatic=" + thisr.val()
            });        
        
    });
    
    jQuery('#wpml_cpi_clear_cache').click(function(){
        var thisb = jQuery(this);
        thisb.attr('style', 'background:url('+icl_ajxloaderimg_src+');background-repeat:no-repeat;').attr('disabled','disabled');
        jQuery.ajax({
                type:'POST',
                url:icl_ajx_url,
                data:'icl_ajx_action=wpml_cpi_clear_cache',
                success: function(){
                    thisb.fadeOut();
                }
            });        
        
    });
});