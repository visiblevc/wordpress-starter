jQuery( 'document' ).ready( function(){

	jQuery( '#wpml_add_strings' ).submit(function() {
		var strings = [];

		jQuery( '.js-wpml-btn-cancel, .js-wpml-btn-add-strings'  ).attr( 'disabled', 'disabled');
		jQuery( '.spinner' ).addClass( 'is-active' );

		jQuery( '.icl_st_row_cb:checked' ).each(function(){
			var st_fields_wrapper = jQuery( this ).parent().next();
			var fields_to_disable = 'input[name="icl_strings[]"], input[name="icl_translations[]"], input[name="icl_fuzzy[]"], input[name="icl_name[]"], input[name="icl_context[]"]';
			var string = {
				original: st_fields_wrapper.find( 'input[name="icl_strings[]"]' ).val(),
				translation: st_fields_wrapper.find( 'input[name="icl_translations[]"]' ).val(),
				fuzzy: st_fields_wrapper.find( 'input[name="icl_fuzzy[]"]' ).val(),
				name: st_fields_wrapper.find( 'input[name="icl_name[]"]' ).val(),
				context: st_fields_wrapper.find( 'input[name="icl_context[]"]' ).val()
			};
			strings.push( string );
			jQuery( this ).attr( 'disabled', 'disabled' );
			st_fields_wrapper.find( fields_to_disable ).attr( 'disabled', 'disabled' );
		});

		jQuery( '#strings_json' ).val( JSON.stringify( strings ) );
	});
});