    var otgs_wp_installer = {
    
    init: function(){
        
        jQuery('.otgs_wp_installer_table').on('click', '.enter_site_key_js', otgs_wp_installer.show_site_key_form);
        jQuery('.otgs_wp_installer_table').on('click', '.cancel_site_key_js', otgs_wp_installer.hide_site_key_form);
        
        jQuery('.otgs_wp_installer_table').on('click', '.remove_site_key_js', otgs_wp_installer.remove_site_key);
        jQuery('.otgs_wp_installer_table').on('click', '.update_site_key_js', otgs_wp_installer.update_site_key);
            
        jQuery('.otgs_wp_installer_table').on('submit', '.otgsi_site_key_form', otgs_wp_installer.save_site_key);
        jQuery('.otgs_wp_installer_table').on('submit', '.otgsi_downloads_form', otgs_wp_installer.download_downloads);
        jQuery('.otgs_wp_installer_table').on('change', '.otgsi_downloads_form :checkbox[name="downloads[]"]', otgs_wp_installer.update_downloads_form);
        
        jQuery('.installer-dismiss-nag').click(otgs_wp_installer.dismiss_nag);
        
        jQuery('.otgs_wp_installer_table').on('click', '.installer_expand_button', otgs_wp_installer.toggle_subpackages);

        otgs_wp_installer.scroll_to_repository();

        if( typeof pagenow != 'undefined' && pagenow == 'plugins'){

            jQuery(document).ajaxSuccess(function(event, xhr, settings) {
                var data = otgs_wp_installer.getQueryParameters(settings.data);
                if(typeof data.action != 'undefined' && data.action == 'update-plugin'){
                    response = xhr.responseJSON.data;
                    if(typeof response.error != 'undefined'){
                        var default_error = jQuery('#' + response.slug + '-update .update-message').html();
                        jQuery('#' + response.slug + '-update .update-message').html(default_error + ' &raquo;<span class="installer-red-text"> ' + response.error + '</span>');
                    }
                }
                return false;
            });

        }

        if( typeof pagenow != 'undefined' && pagenow == 'plugin-install' ){
            jQuery( '.plugin-install-tab-commercial .search-plugins' ).remove();
        }

    },

    getQueryParameters : function(str) {
        return (str || document.location.search).replace(/(^\?)/,'').split("&").map(function(n){return n = n.split("="),this[n[0]] = n[1],this}.bind({}))[0];
    },

    reset_errors: function(){
        jQuery('.installer-error-box').html('').hide();
    },
    
    show_error: function(repo, text){
        jQuery('#installer_repo_' + repo).find('.installer-error-box').html(text).show();
    },
    
    show_site_key_form: function(){

        if( jQuery(this).hasClass('disabled') ) {
            alert( jQuery(this).attr('title') );
            return false;
        }

        otgs_wp_installer.reset_errors();
        
        var form = jQuery(this).parent().find('form.otgsi_site_key_form');
        jQuery(this).prev().hide();
        jQuery(this).hide();
        form.css('display', 'inline');    
        form.find('input[name^=site_key_]').focus().val('');
        form.find('input').removeAttr('disabled');        
        
        form.closest('.otgsi_register_product_wrap').addClass('otgsi_yellow_bg');
        
        return false;
    },
    
    hide_site_key_form: function(){
        var form = jQuery(this).closest('form');        
        form.hide();
        form.parent().find('.enter_site_key_js').show();
        form.parent().find('.enter_site_key_js').prev().show();
        
        form.closest('.otgsi_register_product_wrap').removeClass('otgsi_yellow_bg');
        otgs_wp_installer.reset_errors();
        return false;
    },
    
    save_site_key: function(){
        
        var thisf = jQuery(this);
        var data = jQuery(this).serialize();
        jQuery(this).find('input').attr('disabled', 'disabled');        
        
        var spinner = jQuery('<span class="spinner"></span>');
        spinner.css({display: 'inline-block', float: 'none'}).prependTo(jQuery(this));
        
        otgs_wp_installer.reset_errors();
        
        jQuery.ajax({url: ajaxurl, type: 'POST', dataType:'json', data: data, success: 
            function(ret){
                if(!ret.error){                    
                    otgs_wp_installer.saved_site_key();
                }else{
                    otgs_wp_installer.show_error(thisf.find('[name=repository_id]').val(), ret.error);
                    thisf.find('input').removeAttr('disabled');        
                }
                
                if(typeof ret.debug != 'undefined'){
                    thisf.append('<textarea style="width:100%" rows="20">' + ret.debug + '</textarea>');
                }
                
                spinner.remove();
            }
        });
        
        return false;
        
    },
    
    saved_site_key: function(){
        location.reload();
    },
    
    remove_site_key: function(){

        if( jQuery(this).attr('disabled') == 'disabled' ){

            alert( jQuery(this).attr('title') );
            return false;

        } else {

            if(confirm(jQuery(this).data('confirmation'))){

                jQuery('<span class="spinner"></span>').css({visibility: 'visible', float: 'none'}).prependTo(jQuery(this).parent());
                data = {action: 'remove_site_key', repository_id: jQuery(this).data('repository'), nonce: jQuery(this).data('nonce')}
                jQuery.ajax({url: ajaxurl, type: 'POST', data: data, success: otgs_wp_installer.removed_site_key});
            }

        }

        return false;  
    },

    removed_site_key: function(){
        location.reload();
    },
    
    update_site_key: function(){
        var error_wrap = jQuery(this).closest('.otgsi_register_product_wrap').find('.installer-error-box');
        error_wrap.html('');

        var spinner = jQuery('<span class="spinner"></span>');

        spinner.css({visibility: 'visible', float: 'none'}).prependTo(jQuery(this).parent());
        data = {action: 'update_site_key', repository_id: jQuery(this).data('repository'), nonce: jQuery(this).data('nonce')}
        jQuery.ajax({
            url: ajaxurl, 
            type: 'POST', 
            data: data, 
            dataType: 'json',
            complete: function( event, xhr, settings ){
                var error = '';
                if(xhr == 'success') {
                    var ret = event.responseJSON;
                    if(ret.error){
                        error = ret.error;
                    }else{
                        otgs_wp_installer.updated_site_key(ret);
                    }
                }else{
                    error = 'Error processing request (' + xhr + '). Please try again!';
                }

                if( error ){
                    error_wrap.html('<p>' + error + '</p>').show();
                    spinner.remove();
                }

            }
        });

        return false;
        
    },
    
    updated_site_key: function(ret){
        location.reload();            
    },
    
    update_downloads_form: function(){

        var checked = jQuery('.otgsi_downloads_form :checkbox:checked[name="downloads[]"]').length;
        
        if(checked){
            jQuery(this).closest('form').find(':submit, :checkbox[name=activate]').removeAttr('disabled');
        }else{
            jQuery(this).closest('form').find(':submit, :checkbox[name=activate]').attr('disabled', 'disabled');
        }
        
        
    },
    
    download_downloads: function(){

        var activate = jQuery(this).find(":checkbox:checked[name=activate]").val(),
            action_button = jQuery(this).find('input[type="submit"]');
            downloads_form = jQuery(this),
            idx = 0,
            checkboxes = [];
                
        jQuery(this).find(':checkbox:checked[name="downloads[]"]').each(function(){
            if(jQuery(this).attr('disabled')) return;
            checkboxes[idx] = jQuery(this);
            idx++;
            jQuery(this).attr('disabled', 'disabled');
        });

        idx = 0;

        if( typeof checkboxes[idx] != 'undefined' ){
            download_and_activate( checkboxes[idx] );
            action_button.attr('disabled', 'disabled');
        }

        function download_and_activate( elem ){

            var this_tr = elem.closest('tr');
            var is_update = this_tr.find('.installer-red-text').length;
            if(is_update){
                var installing = this_tr.find('.installer-status-updating');
                var installed = this_tr.find('.installer-status-updated');
            }else{
                var installing = this_tr.find('.installer-status-installing');
                var installed = this_tr.find('.installer-status-installed');

            }
            if(activate){
                var activating = this_tr.find('.installer-status-activating');
                var activated = this_tr.find('.installer-status-activated');
            }

            if( this_tr.find('.for_spinner_js .spinner').length > 0 ){
                var spinner = this_tr.find('.for_spinner_js .spinner');
            }else{
                var spinner = this_tr.find('.installer-status-downloading');
            }

            otgs_wp_installer.reset_errors();
            downloads_form.find('div.installer-status-success').hide();
            spinner.css('visibility', 'visible');
            installing.show();

            var plugin_name = this_tr.find('.installer_plugin_name').html();
            if(is_update){
                otgs_wp_installer.show_download_progress_status(downloads_form, installer_strings.updating.replace('%s', plugin_name));
            }else{
                otgs_wp_installer.show_download_progress_status(downloads_form, installer_strings.installing.replace('%s', plugin_name));
            }


            data = {action: 'installer_download_plugin', data: elem.val(), activate: activate}

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function(ret){
                    installing.hide();

                    if(!ret.success){
                        installed.addClass('installer-status-error');
                        installed.html(installed.data('fail'));

                        if(ret.message){
                            installed.closest('.otgs_wp_installer_table').find('.installer-error-box').html('<p>' + ret.message + '</p>').show();
                        }else{
                            installed.closest('.otgs_wp_installer_table').find('.installer-error-box').html('<p>' + downloads_form.find('.installer-revalidate-message').html() + '</p>').show();
                        }


                    }

                    installed.show();
                    spinner.fadeOut();

                    if(ret.version){
                        this_tr.find('.installer_version_installed').html('<span class="installer-green-text">' + ret.version + '</span>');
                    }

                    if(ret.success && activate){

                        otgs_wp_installer.show_download_progress_status(downloads_form, installer_strings.activating.replace('%s', plugin_name));
                        activating.show();
                        spinner.show();
                        this_tr.find('.installer-red-text').removeClass('installer-red-text').addClass('installer-green-text').html(ret.version);

                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {action: 'installer_activate_plugin', plugin_id: ret.plugin_id, nonce: ret.nonce},
                            success: function(ret){
                                activating.hide();
                                if(!ret.error ){
                                    activated.show();
                                }

                                spinner.fadeOut();

                                idx++;
                                if( typeof checkboxes[idx] != 'undefined' ){
                                    download_and_activate( checkboxes[idx] );
                                }else{
                                    otgs_wp_installer.hide_download_progress_status(downloads_form);
                                    downloads_form.find('div.installer-status-success').show();
                                    action_button.removeAttr('disabled');
                                }
                            }
                        });
                    }else{
                        idx++;
                        if( typeof checkboxes[idx] != 'undefined' ){
                            download_and_activate( checkboxes[idx] );
                        }else{
                            otgs_wp_installer.hide_download_progress_status(downloads_form);
                            downloads_form.find('div.installer-status-success').show();
                            action_button.removeAttr('disabled');
                        }
                    }
                }

            });

        };

        return false;
    },


    show_download_progress_status: function(downloads_form, text){

        downloads_form.find('.installer-download-progress-status').html(text).fadeIn();

    },

    hide_download_progress_status: function(downloads_form){

        downloads_form.find('.installer-download-progress-status').html('').fadeOut();

    },

    dismiss_nag: function(){

        var thisa = jQuery(this);

        data = {action: 'installer_dismiss_nag', repository: jQuery(this).data('repository')}

        jQuery.ajax({url: ajaxurl, type: 'POST', dataType:'json', data: data, success:
            function(ret){
                thisa.closest('.otgs-is-dismissible').remove();
            }
        });

        return false;
    },
    
    toggle_subpackages: function(){
        var list = jQuery(this).closest('td').find('.otgs_wp_installer_subtable');
        
        if(list.is(':visible')){
            list.slideUp('fast');
        }else{
            list.slideDown('fast');
        }


        return false;

    },

    scroll_to_repository: function(){

        var ref = window.location.hash.replace('#', '');

        if(ref) {
            var split = ref.split('/');
            var repo        = split[0];

            if(typeof split[1] != 'undefined'){
                var package     = split[1];
                var repo_element = jQuery('#repository-' + repo);



                if(repo_element.length){

                    jQuery('html, body').animate({
                        scrollTop: repo_element.offset().top
                    }, 1000);

                    var package_element = jQuery('#repository-' + repo +'_' + package);

                    if(package_element.length && !package_element.is(':visible')){
                        package_element.parents('.otgs_wp_installer_subtable').slideDown();
                        package_element.addClass('installer_highlight_package');
                    }

                    package_element.find('.button-secondary').removeClass('button-secondary').addClass('button-primary');
                }
            }

        }

    }

    
}


jQuery(document).ready(otgs_wp_installer.init);