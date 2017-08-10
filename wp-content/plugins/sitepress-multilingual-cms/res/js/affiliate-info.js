jQuery(document).ready(function(){
    jQuery('#icl_affiliate_info_check').submit(iclAffiliateInfoCheck);    
});

function iclAffiliateInfoCheck(){
    var thisf = jQuery(this);
    thisf.find('.icl_cyan_box').hide();
    thisf.find(':submit').before(icl_ajxloaderimg);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            dataType: 'json',
            data: "icl_ajx_action=affiliate_info_check&" + thisf.serialize(),
            success: function(msg){
                thisf.find(':submit').prev().hide(function(){
                    if(msg.error){
                        thisf.find('.icl_error_text').fadeIn();
                    }else{
                        thisf.find('.icl_valid_text').fadeIn();
                    }
                });
            }
        });        
    
    return false;
}
