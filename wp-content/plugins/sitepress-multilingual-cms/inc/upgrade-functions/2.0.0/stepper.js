
jQuery(document).ready(function(){
    //    iclStepper(jQuery('#icl-migrate-start').attr('href'), true);
    jQuery('#icl-migrate-start').one('click', function(){
        iclStepper(jQuery(this).attr('href'), '&init=1', true);
        return false;
    });
});

function iclStepper(href, addUrl, init) {
    init = typeof(init) != 'undefined' ? true : false;
    jQuery.ajax({
        type: "POST",
        url: href+addUrl,
        cache: false,
        dataType: 'json',
        success: function(data) {
            if (data.stop == true) {
                jQuery('#icl-migrate-progress .message').html('stopped');
            } else if (data.error == false) {
                if (init == true) {
                    jQuery('#icl-migrate-start').fadeOut(function(){
                        jQuery('#icl-migrate-progress').fadeIn().html(data.output).children('.message').html(data.message);
                        iclStepper(href, '&step='+data.step);
                    });
                //                    jQuery('#icl-migrate-progress').html(data.output).children('.message').html(data.message);
                } else {
                    jQuery('#icl-migrate-progress .message').html(data.message);
                    jQuery('#icl-migrate-progress .progress').animate({
                        width : data.barWidth+'%'
                    }, 100);
                    if (data.completed == true) {
                        jQuery('#icl-migrate').delay(3000).fadeOut();
                    } else {
                        iclStepper(href, '&step='+data.step);
                    }
                }
            } else {
                alert('error');
                jQuery('#icl-migrate-progress .message').html(data.error);
            }
        }
    });
}