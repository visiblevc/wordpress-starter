jQuery(document).ready(function(){  
    if(jQuery('form input[name="action"]').attr('value') ==='add-tag'){
        jQuery('.form-wrap p[class="submit"]').before(jQuery('#icl_tax_menu').html());    
    }else{
        var new_row = jQuery('#edittag table[class="form-table"] tr.term-description-wrap').clone()
            .removeClass('term-description-wrap').addClass('wpml-term-languages-wrap');
        jQuery('#edittag table[class="form-table"]:first').append( new_row );
        jQuery('#edittag table[class="form-table"]:first tr:last th:first').html('&nbsp;');
        jQuery('#edittag table[class="form-table"]:first tr:last td:last').html(jQuery('#icl_tax_menu').html());  
    }    
    jQuery('#icl_tax_menu').remove();

    jQuery('select[name="icl_tag_language"]').change(function(){
        var lang = jQuery(this).val();
        var ajx = location.href.replace(/#(.*)$/,'');
        ajx = ajx.replace(/pagenum=([0-9]+)/,'');
        if(-1 == location.href.indexOf('?')){
            url_glue='?';
        }else{
            url_glue='&';
        }   

        if(icl_this_lang != lang){
            jQuery('#icl_translate_options').fadeOut();
        }else{
            jQuery('#icl_translate_options').fadeIn();
        }

        jQuery('#posts-filter').parent().load(ajx+url_glue+'lang='+lang + ' #posts-filter', {}, function(resp){
            strt = resp.indexOf('<span id="icl_subsubsub">');
            endd = resp.indexOf('</span>\'', strt);
            lsubsub = resp.substr(strt,endd-strt+7);
            jQuery('table.widefat').before(lsubsub);
            tag_start = resp.indexOf('<div class="tagcloud">');
            tag_end  = resp.indexOf('</div>', tag_start);            
            tag_cloud = resp.substr(tag_start+22,tag_end-tag_start-22);
            jQuery('.tagcloud').html(tag_cloud);
        });
        
   });

    /*
     *  This section reads the hidden div containing the JSON encoded array of categories for which no checkbox is to be displayed.
     *  This is done to ensure that they cannot be deleted
     */
    var defaultCategoryJSON, defaultCategoryJSONDiv, defaultCategoryIDs, key, id;

    defaultCategoryJSONDiv = jQuery('#icl-default-category-ids');
    if (defaultCategoryJSONDiv.length !== 0) {
        defaultCategoryJSON = defaultCategoryJSONDiv.html();
        defaultCategoryIDs = jQuery.parseJSON(defaultCategoryJSON);

        for (key in defaultCategoryIDs) {
            if (defaultCategoryIDs.hasOwnProperty(key)) {
                id = defaultCategoryIDs[key];
                removeDefaultCatCheckBox(id);
            }
        }
    }

    iclTagLangSelectBar.init();
});

/**
 * Removes the checkbox for a given category from the DOM.
 * @param catID
 */
function removeDefaultCatCheckBox(catID) {
    var defaultCatCheckBox;

    defaultCatCheckBox = jQuery('#cb-select-' + catID);

    if (defaultCatCheckBox.length !== 0) {
        defaultCatCheckBox.remove();
    }
}

var iclTagLangSelectBar = {
    bar: false,
    taxonomy: false,
    addTagForm: false,
    formBlocked: false,
    init: function () {
        "use strict";
        var self = this;
        self.addTagForm = jQuery('#addtag');
        self.bar = jQuery('#icl_subsubsub');
        self.taxonomy = self.addTagForm.find('[name="taxonomy"]').val();
        self.displayBar();
        self.addHiddenSearchField();

        self.addTagForm.off('submit', 'preventSubmit');

        self.addTagForm.on('blur', function(){
            self.formBlocked = false;
        });

        jQuery(document).on('keydown', function(e){
            if(self.formBlocked){
                e.preventDefault();
            }
            if(e.keyCode == 13 && self.addTagForm.find('input:focus').length !== 0){
                self.formBlocked = true;
            }
        });

        jQuery(document).ajaxSuccess(function(evt, request, settings) {
            if(typeof settings === 'undefined' || typeof settings.data === 'undefined' || typeof settings.data.search === 'undefined')  return;
            self.updateCountDisplay(settings);
            self.formBlocked = false;

            if(settings.data.search('action=add-tag') != -1 && (settings.data.search('source_lang%3D') != -1 || settings.data.search('icl_translation_of') != -1) ) {

                var taxonomy = '';
                var vars = settings.data.split("&");
                for (var i=0; i<vars.length; i++) {
                    var pair = vars[i].split("=");
                    if (pair[0] == 'taxonomy') {
                        taxonomy = pair[1];
                        break;
                    }
                }

                jQuery('#icl_tax_'+taxonomy+'_lang .inside').html(icl_ajxloaderimg);
                jQuery.ajax({
                    type:'GET',
                    url : location.href.replace(/&trid=([0-9]+)/, ''),
                    success: function(msg){
                        jQuery('#icl_tax_adding_notice').fadeOut();
                        jQuery('#icl_tax_'+taxonomy+'_lang .inside').html(jQuery(msg).find('#icl_tax_'+taxonomy+'_lang .inside').html());
                    }
                })
            }
        });

        return self;
    },
    getCurrentLang: function () {
        "use strict";
        var self = this;

        return self.addTagForm.find('[name="icl_tax_' + self.taxonomy + '_language"]').val();
    },
    addHiddenSearchField: function () {
        "use strict";
        var self = this;

        var currentLang = self.bar.find('strong').find('span').attr('class');
        var hiddenField = jQuery('<input type="hidden" name="lang"/>');
        hiddenField.val(currentLang);
        jQuery('.search-form').append(hiddenField);

        return self;
    },
    displayBar: function () {
        "use strict";
        var langBar = jQuery('#icl_subsubsub');
        jQuery('table.widefat').before(langBar);
        langBar.show();
    },
    updateCountDisplay: function (settings) {
        "use strict";
        var self = this;
        if (settings.data.search('action=add-tag') != -1 || settings.data.search('action=delete-tag') != -1) {
            var change = settings.data.search('action=add-tag') !== -1 ? 1 : -1;
            var currentLangCount = self.updateCountSpan(change);
            var numDisplayTop = jQuery('.top').find('.displaying-num');
            var displayNumTxt = numDisplayTop.text().replace(currentLangCount, currentLangCount + change);
            jQuery('.displaying-num').text(displayNumTxt)
        }

        return self;
    },
    updateCountSpan: function (change) {
        var self = this;
        var currentLangCount = 0;

        [self.getCurrentLang(), 'all'].forEach(function (lang) {
            "use strict";
            var countElement = self.bar.find('.' + lang);
            var count = parseInt(countElement.text());
            var newCount = count + change;
            countElement.text(newCount);
            currentLangCount = countElement.closest('strong').length !== 0 ? count : currentLangCount;
        });

        return currentLangCount;
    }
};