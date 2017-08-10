<div class="wrap">
    <h3><?php _e('Theme and Plugins compatibility with WPML', 'sitepress') ?></h3>

    <p><?php _e('Configuration for compatibility between your active plugins and theme is updated automatically on daily basis.','sitepress'); ?></p>
    <div id="icl_theme_plugins_compatibility">
        <p><?php printf(__('Last checked on %s','sitepress'),'<span id="wpml_conf_upd">'.date('F j, Y H:i a', get_option('wpml_config_index_updated')).'</span>') ?></p>

        <input class="button" id="update_wpml_config" value="<?php echo __('Update','sitepress') ?>" type="button" style="float:left" />

    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($){
        $('#update_wpml_config').click(function(){
            var el = $(this);
            var ajaxLoader = $('<span class="spinner" style="float:left"></span>');
            ajaxLoader.insertAfter(el).show();
            el.attr('disabled','disabled');
            jQuery.ajax({
                type : "post",
                url : ajaxurl,
                data : {
                    action: "update_wpml_config_index",
                    _icl_nonce: "<?php echo wp_create_nonce('icl_theme_plugins_compatibility_nonce'); ?>"
                },
                success: function(response) {
                    if(response)
                         $('#wpml_conf_upd').html(response);
                },
                complete: function() {
                    ajaxLoader.remove();
                    el.removeAttr('disabled');
                }
            });
        });
    });
</script>
