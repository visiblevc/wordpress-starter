<?php

//Fix new Language Switcher widget implementation
//old sample -> unserialize('a:1:{i:1;a:1:{s:5:"title";b:0;}}');
//new sample -> unserialize('a:2:{i:1;a:1:{s:5:"title";b:0;}s:12:"_multiwidget";i:1;}');
$widget_icl_lang_sel_widget = get_option('widget_icl_lang_sel_widget');
if(is_admin() && $widget_icl_lang_sel_widget) { // && !isset($widget_icl_lang_sel_widget['_multiwidget'])) {
	$widget_icl_lang_sel_widget[ '_multiwidget' ] = 1;
	$sitepress_settings = get_option('icl_sitepress_settings');
	$icl_widget_title_show = isset($sitepress_settings[ 'icl_widget_title_show' ]) ? $sitepress_settings[ 'icl_widget_title_show' ] : false;

	foreach ( $widget_icl_lang_sel_widget as $idx => $data ) {
		if(is_array($data) && !isset($data['title_show'])) {
			$widget_icl_lang_sel_widget[$idx]['title_show'] = $icl_widget_title_show;
		}
	}

	update_option( 'widget_icl_lang_sel_widget', $widget_icl_lang_sel_widget );
}
//$sidebars = 'a:5:{s:19:"wp_inactive_widgets";a:0:{}s:9:"sidebar-1";a:8:{i:0;s:21:"icl_lang_sel_widget-2";i:1;s:10:"calendar-2";i:2;s:8:"search-2";i:3;s:14:"recent-posts-2";i:4;s:17:"recent-comments-2";i:5;s:10:"archives-2";i:6;s:12:"categories-2";i:7;s:6:"meta-2";}s:9:"sidebar-2";a:0:{}s:9:"sidebar-3";a:0:{}s:13:"array_version";i:3;}';
//$sidebars = unserialize($sidebars);
$sidebars = get_option('sidebars_widgets');

//Fix widget id from single to multi instance
$fixed = false;
foreach ( $sidebars as $sidebar_id => $widgets ) {
	if ( is_array( $widgets ) ) {
		foreach ( $widgets as $index => $widget_id ) {
			if ( $widget_id == 'icl_lang_sel_widget' ) {
				$sidebars[ $sidebar_id ][ $index ] = 'icl_lang_sel_widget-1';
				$fixed                             = true;
				break;
			}
		}
	}
	if ( $fixed ) {
		break;
	}
}

if($fixed) {
	update_option('sidebars_widgets', $sidebars);
}