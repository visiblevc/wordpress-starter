jQuery(document).ready(function () {
	jQuery('*[data-tp-enabled="0"]').hide();
	jQuery('.wpml_tp_custom_dismiss_able').click(icl_dismiss_custom_text);
});

function icl_dismiss_custom_text() {
	var item = jQuery(this);
	item.parentNode.parentNode.removeChild(item.parentNode);
	jQuery.ajax({
		type:'POST',
		url : item.href,
		success: function(msg){
		}
	});
	return false;
}
