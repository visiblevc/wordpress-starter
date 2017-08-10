this["WPML_TM"] = this["WPML_TM"] || {};

this["WPML_TM"]["templates/translation-editor/footer.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class="alignleft">\n\t<button class="cancel wpml-dialog-close-button js-dialog-cancel">' +
((__t = (cancel)) == null ? '' : __t) +
'</button>\n\t<button class="button-secondary wpml-resign-button js-resign">' +
((__t = (resign)) == null ? '' : __t) +
'</button>\n</div>\n<div class = "alignright">\n\t<span class = "js-saving-message" style = "display:none"><img src="' +
((__t = (loading_url)) == null ? '' : __t) +
'" alt="' +
((__t = (saving)) == null ? '' : __t) +
'" height="16" width="16"/>' +
((__t = (saving)) == null ? '' : __t) +
'</span>\n\t<button class = "button button-primary button-large wpml-dialog-close-button js-save-and-close">' +
((__t = (save_and_close)) == null ? '' : __t) +
'</button>\n\t<button class = "button button-primary button-large wpml-dialog-close-button js-save">' +
((__t = (save)) == null ? '' : __t) +
'</button>\n</div>\n<div class="text-center">\n\t<div class="progress-bar js-progress-bar"><div class="progress-bar-text"></div></div>\n\t<label><input class="js-translation-complete" name="complete" type="checkbox"/>' +
((__t = (translation_complete)) == null ? '' : __t) +
'</label>\n</div>';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/group.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {

 if ( title ) { ;
__p +=
((__t = ( title )) == null ? '' : __t);
 } ;
__p += '\n<div class="inside">\n</div>\n\n';
 if ( divider ) { ;
__p += '\n<hr />\n';
 } ;
__p += '\n<button class="button-copy button-secondary js-button-copy-group">\n\t<i class="otgs-ico-copy"></i>\n</button>\n';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/header.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p +=
((__t = ( title )) == null ? '' : __t) +
'\n<a href="' +
((__t = ( link_url )) == null ? '' : __t) +
'" class="view" target="_blank">' +
((__t = ( link_text )) == null ? '' : __t) +
'</a>\n';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/image.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '\t<div class="inside">\n\t\t<img src="' +
((__t = ( image_src )) == null ? '' : __t) +
'">\n\t</div>\n\n\n';
 if ( divider ) { ;
__p += '\n<hr />\n';
 } ;
__p += '\n';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/languages.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '\t<input type="hidden" name="source_lang" value="' +
((__t = ( language.source )) == null ? '' : __t) +
'" />\n\t<input type="hidden" name="target_lang" value="' +
((__t = ( language.target )) == null ? '' : __t) +
'" />\n\t<h3 class="wpml-header-original">' +
((__t = ( labels.source_lang )) == null ? '' : __t) +
':\n\t\t<span class="wpml-title-flag">\n\t\t\t<img src="' +
((__t = ( language.img.source_url )) == null ? '' : __t) +
'"\n\t\t\t\t alt="' +
((__t = ( language.source_lang )) == null ? '' : __t) +
'"/>\n\t\t</span>\n\t\t<strong>' +
((__t = ( language.source_lang )) == null ? '' : __t) +
'</strong>\n\t</h3>\n\n\t<h3 class="wpml-header-translation">' +
((__t = ( labels.target_lang )) == null ? '' : __t) +
':\n\t\t<span class="wpml-title-flag">\n\t\t\t<img src="' +
((__t = ( language.img.target_url )) == null ? '' : __t) +
'"\n\t\t\t\t alt="' +
((__t = ( language.target_lang )) == null ? '' : __t) +
'"/>\n\t\t</span>\n\t\t<strong>' +
((__t = ( language.target_lang )) == null ? '' : __t) +
'</strong>\n\t</h3>\n\n\t<div class="wpml-copy-container">\n\t\t<button class="button-secondary button-copy-all js-button-copy-all" title="' +
((__t = ( labels.copy_from_original )) == null ? '' : __t) +
'">\n\t\t\t<i class="otgs-ico-copy"></i> ' +
((__t = ( labels.copy_all )) == null ? '' : __t) +
'\n\t\t</button>\n\t</div>\n';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/note.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<p>' +
((__t = ( note )) == null ? '' : __t) +
'</p>\n\n';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/section.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '<div class="handlediv button-link"><br></div>\n<h3 class="hndle">\n\t<span>' +
((__t = ( section.title )) == null ? '' : __t) +
' ';
 if ( section.empty ) { ;
__p += '&nbsp;<i>' +
((__t = ( section.empty_message )) == null ? '' : __t);
 } ;
__p += '</span>\n\t';
 if ( section.sub_title ) { ;
__p += '\n\t<span class="subtitle"><i class="otgs-ico-warning"></i>' +
((__t = ( section.sub_title )) == null ? '' : __t) +
'</span>\n\t';
 } ;
__p += '\n</h3>\n\n<div class="inside">\n</div>\n';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/single-line.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '<label>' +
((__t = (field.title)) == null ? '' : __t) +
'</label>\n<input readonly class="original_value js-original-value" value="' +
__e( field.field_data ) +
'" type="text" ' +
((__t = (field.original_direction)) == null ? '' : __t) +
'/>\n<button class="button-copy button-secondary js-button-copy icl_tm_copy_link otgs-ico-copy" id="icl_tm_copy_link_' +
((__t = (field.field_type)) == null ? '' : __t) +
'" title="' +
((__t = ( labels.copy_from_original )) == null ? '' : __t) +
'"/>\n<input class="translated_value js-translated-value" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][data]" value="' +
__e( field.field_data_translated ) +
'" type="text" ' +
((__t = (field.translation_direction)) == null ? '' : __t) +
'/>\n<div class="field_translation_complete">\n\t<label><input class="icl_tm_finished js-field-translation-complete" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][finished]" type="checkbox" ';
 if (field.field_finished) { ;
__p += ' checked="checked" ';
 } ;
__p += ' />' +
((__t = (labels.translation_complete)) == null ? '' : __t) +
'</label>\n</div>\n<input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][tid]" value="' +
((__t = (field.tid)) == null ? '' : __t) +
'">\n<input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][format]" value="base64">\n';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/textarea.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '<label>' +
((__t = (field.title)) == null ? '' : __t) +
'</label>\n<textarea class="original_value js-original-value" readonly cols="22" rows="10" ' +
((__t = (field.original_direction)) == null ? '' : __t) +
'>' +
((__t = ( field.field_data )) == null ? '' : __t) +
'</textarea>\n<button class="button-copy button-secondary js-button-copy icl_tm_copy_link otgs-ico-copy" id="icl_tm_copy_link_' +
((__t = (field.field_type)) == null ? '' : __t) +
'" title="' +
((__t = ( labels.copy_from_original )) == null ? '' : __t) +
'"/>\n<textarea class="translated_value js-translated-value cols="22" rows="10" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][data]" ' +
((__t = (field.translation_direction)) == null ? '' : __t) +
'>' +
((__t = ( field.field_data_translated )) == null ? '' : __t) +
'</textarea>\n<div class="field_translation_complete">\n\t<label><input class="icl_tm_finished js-field-translation-complete" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][finished]" type="checkbox" ';
 if (field.field_finished) { ;
__p += ' checked="checked" ';
 } ;
__p += ' />' +
((__t = (labels.translation_complete)) == null ? '' : __t) +
'</label>\n</div>\n<input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][tid]" value="' +
((__t = (field.tid)) == null ? '' : __t) +
'">\n<input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][format]" value="base64">\n';

}
return __p
};

this["WPML_TM"]["templates/translation-editor/wysiwyg.html"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '<label>' +
((__t = (field.title)) == null ? '' : __t) +
'</label>\n<div id="original_' +
((__t = (field.field_type)) == null ? '' : __t) +
'_placeholder"></div>\n<button class="button-copy button-secondary js-button-copy icl_tm_copy_link otgs-ico-copy" id="icl_tm_copy_link_' +
((__t = (field.field_type)) == null ? '' : __t) +
'" title="' +
((__t = ( labels.copy_from_original )) == null ? '' : __t) +
'"/>\n<div id="translated_' +
((__t = (field.field_type)) == null ? '' : __t) +
'_placeholder"></div>\n<input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][tid]" value="' +
((__t = (field.tid)) == null ? '' : __t) +
'">\n<input type="hidden" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][format]" value="base64">\n<div class="field_translation_complete">\n    <label><input class="icl_tm_finished js-field-translation-complete" name="fields[' +
((__t = (field.field_type)) == null ? '' : __t) +
'][finished]" type="checkbox" ';
 if (field.field_finished) { ;
__p += ' checked="checked" ';
 } ;
__p += ' />' +
((__t = (labels.translation_complete)) == null ? '' : __t) +
'</label>\n</div>\n';

}
return __p
};