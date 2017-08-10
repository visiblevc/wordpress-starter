<?php

global $pagenow;
$filtered_import = filter_input( INPUT_GET, 'import',FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
$filtered_step   = filter_input( INPUT_GET, 'step',FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );

if ($pagenow == 'admin.php' && 0 === strcmp( $filtered_import, 'wordpress' ) && $filtered_step == 1 ) {
	add_action('admin_head', 'icl_import_xml');
}

function icl_import_xml() {
	global $sitepress;
	$langs = $sitepress->get_active_languages();
	if (empty($langs)) {
		return;
	}
	$default = $sitepress->get_default_language();
	
		$out = '<h3>' . esc_html__('Select Language', 'sitepress') . '</h3><p><select name="icl_post_language">';
		foreach ($langs as $lang) {
			$out .= '<option value="' . esc_attr( $lang['code'] ) . '"';
			if ($default == $lang['code']) {
				$out .= ' selected="selected"';
			}
			$out .= '>' . esc_html( $lang['native_name'] ) . '<\/option>';
		}
		$out .= '<\/select><\/p>';
	
	echo '
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery("#wpbody-content").find("form .submit").before(\'' . $out . '\');
		});
	</script>
	';
}

add_action('import_start', 'icl_import_xml_start', 0);
function icl_import_xml_start() {
	set_time_limit(0);
	$_POST['icl_tax_post_tag_language'] = $_POST['icl_tax_category_language'] = $_POST['icl_tax_language'] = $_POST['icl_post_language'];
}