jQuery( document ).ready( function( $ ) {    	

	/** Append OTGS Theme tab */
	var js_array= installer_theme_install_localize.js_array_installer;	
	
	if (!($.isEmptyObject(js_array))) {
		//Unempty		
		for(var key in js_array) {
			//Dont append if we are on commercial plugins tab page and if there are no themes
			if ((!(js_array[key]['is_commercial_plugin_tab'])) && (!(installer_theme_install_localize.no_associated_themes))) {
			    $('div.wp-filter ul.filter-links').append('<li><a data-sort="'+key+'" href="#">'+ js_array[key]['the_hyperlink_text'] +'</a></li>');
			}
		}
	}
	
	/** Page load event tab selected identifier */
	var loaded_browsing_tab=installer_theme_extended_object.getParameterByName('browse');
	if (loaded_browsing_tab.length > 0) {
		
		var frontend_tab_selected_tab = loaded_browsing_tab;	
		
	} else if (0 == loaded_browsing_tab.length){
		
		//WordPress defaults to 'Featured' when theme install is loaded without the browse parameter		
		var frontend_tab_selected_tab  = 'featured';	
	}
	
	/** Prepare data on page load event for AJAX */
	var data = {
			action: 'installer_theme_frontend_selected_tab',
			installer_theme_frontend_selected_tab_nonce: installer_theme_install_localize.installer_theme_frontend_selected_tab_nonce,			
			frontend_tab_selected :frontend_tab_selected_tab
	};

	//Call AJAX
	installer_theme_extended_object.doAJAX(data,frontend_tab_selected_tab,js_array);	

 	/** When user clicks on any tab */
	$(document).on('click','.filter-links li > a',function () {
		
		//Get data_sort
		var data_sort =$(this).attr('data-sort');
		
		if (data_sort) {			
			//data_sort is set, prepare data			
			var data = {
					action: 'installer_theme_frontend_selected_tab',
					installer_theme_frontend_selected_tab_nonce: installer_theme_install_localize.installer_theme_frontend_selected_tab_nonce,	
					frontend_tab_selected : data_sort
				};
			
			//Call AJAX
			installer_theme_extended_object.doAJAX(data,data_sort,js_array);

		}	
	});		
	
	var fullhash = window.location.hash;  
	if (fullhash.length > 0) {
		var product_selector=fullhash+' '+'.enter_site_key_js';
		if ($(product_selector).length ) {
			$(product_selector).click();
		}
	}
});

//Installer theme extended JS object for methods
var installer_theme_extended_object = {
		
		getParameterByName: function(name) {
		    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
		        results = regex.exec(location.search);
		    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	    },

		doAJAX: function(data,data_sort,js_array) {
			
			//We only want to post to AJAX if its an OTGS tab
		 	jQuery.post(installer_theme_install_localize.ajaxurl, data, function(response) {	
		 		//AJAX response
		 		var myObject = jQuery.parseJSON(response);
		 		if (typeof myObject != "undefined") {		 			
		 			if(myObject.hasOwnProperty("output")){
				 		var tab_selected= myObject.output;
				 		if (data_sort in js_array) {		 			
					 		if (!(installer_theme_install_localize.js_array_installer[tab_selected]['registration_status'])) {	
					 			//Not registered, no theme response		 			
					 			var unregistered_message= myObject.unregistered_messages;		 					 			
					 			jQuery('.no-themes').html(unregistered_message);
					 		}
				 		}	 				
		 			}
		 		}		 		
		 	});						
		}
};