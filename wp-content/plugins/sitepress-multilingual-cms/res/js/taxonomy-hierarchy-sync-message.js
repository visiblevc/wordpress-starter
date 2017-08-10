jQuery( document ).ready( function(){
    jQuery( document ).ajaxComplete(function(event, xhr, settings){
        if( settings.data ){
            if( settings.data.search( 'action=wpml_tt_sync_hierarchy_save' ) !== -1 ){
				location.reload();
            }
        }
    });
});