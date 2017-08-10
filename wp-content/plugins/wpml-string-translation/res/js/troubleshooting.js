jQuery(document).ready(function () {
    jQuery('#icl_run_st_db_cache_command').click(function () {
        var self = jQuery(this);
        var nonce = self.data('nonce');

        self.attr('disabled', 'disabled');
        self.after(icl_ajxloaderimg);
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "wpml-st-upgrade-db-cache-command",
                nonce: nonce
            },
            success: function(response) {
                var msg = self.data('success-message');
                alert(msg);
            },
            complete: function() {
                self.removeAttr('disabled');
                self.next().fadeOut();
            }
        });
    });
});