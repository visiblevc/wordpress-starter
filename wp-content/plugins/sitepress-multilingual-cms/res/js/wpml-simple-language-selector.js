var WPML_Core = WPML_Core || {};

WPML_Core.SimpleLanguageSelector = function () {
	var self         = this;

	var init = function () {
		jQuery(document).ready(function() {
            self.initialize_select2();
		});
	};
	
    var add_flags = function ( state ) {
        if (!state.id) { return state.text; }
        
        var text = state.text;
        if (jQuery(state.element).data('status') == 'active' ) {
            text = '<strong>&nbsp;' + text + '</strong>';
        }
        
        return '<img src="' + jQuery(state.element).data('flag_url') + '"/> ' + text;        
    };
    
    self.initialize_select2 = function () {
        jQuery('.js-simple-lang-selector-flags').select2({
            formatResult:       add_flags,
            formatSelection:    add_flags,
            escapeMarkup:       function(m) { return m; },
            width:              'resolve',
            dropdownCss:        {'z-index': parseInt(jQuery('.ui-dialog').css('z-index'), 10) + 100},
            dropdownAutoWidth:  true
        });
    };
    
	init();
	
};

WPML_Core.simple_language_selector = new WPML_Core.SimpleLanguageSelector();
