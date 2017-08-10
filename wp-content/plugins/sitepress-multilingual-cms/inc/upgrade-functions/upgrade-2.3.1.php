<?php

$iclsettings = get_option('icl_sitepress_settings');

if($iclsettings['theme_localization_type'] == 2 && !empty($iclsettings['gettext_theme_domain_name'])){
	$iclsettings['theme_localization_load_textdomain'] = 1;
	update_option('icl_sitepress_settings', $iclsettings);
}elseif(empty($iclsettings['theme_localization_type'])){
	$iclsettings['theme_localization_type'] = 2;
	$iclsettings['theme_localization_load_textdomain'] = 0;
	update_option('icl_sitepress_settings', $iclsettings);
}

$sql = "ALTER TABLE {$wpdb->prefix}icl_locale_map CHANGE locale VARCHAR(32) NOT NULL";
$wpdb->query($sql);


$wpdb->query("DELETE m FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key='_alp_processed' AND p.post_type='nav_menu_item'");

?>
