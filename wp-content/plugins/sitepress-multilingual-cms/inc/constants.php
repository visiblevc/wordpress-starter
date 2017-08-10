<?php
if ( file_exists( ICL_PLUGIN_PATH . '/inc/sandbox.inc' ) ) {
	require ICL_PLUGIN_PATH . '/inc/sandbox.inc';
	define( 'OTG_SANDBOX', true );
} else {
	define( 'OTG_SANDBOX', false );
}

if ( ! defined( 'ICL_API_ENDPOINT' ) ) {
	define( 'ICL_API_ENDPOINT', 'https://www.icanlocalize.com' );
}

if ( ! defined( 'OTG_TRANSLATION_PROXY_URL' ) ) {
	define( 'OTG_TRANSLATION_PROXY_URL', 'https://tp.wpml.org' );
}

if ( ! defined( 'ICL_PLUGIN_INACTIVE' ) ) {
	define( 'ICL_PLUGIN_INACTIVE', false );
}

if ( defined( 'PHP_INT_MIN' ) ) {
	define( 'WPML_PRIORITY_BEFORE_EVERYTHING', PHP_INT_MIN );
} else {
	define( 'WPML_PRIORITY_BEFORE_EVERYTHING', ~PHP_INT_MAX );
}

define ( 'ICL_TM_NOT_TRANSLATED', 0);
define ( 'ICL_TM_WAITING_FOR_TRANSLATOR', 1);
define ( 'ICL_TM_IN_PROGRESS', 2);
define ( 'ICL_TM_NEEDS_UPDATE', 3);  //virt. status code (based on needs_update)
define ( 'ICL_TM_DUPLICATE', 9);
define ( 'ICL_TM_COMPLETE', 10);
define ( 'ICL_TM_IN_BASKET', 20);
//@since 3.2
define ( 'ICL_TM_PENDING_TP', 102);

define('ICL_TM_NOTIFICATION_NONE', 0);
define('ICL_TM_NOTIFICATION_IMMEDIATELY', 1);
define('ICL_TM_NOTIFICATION_DAILY', 2);

define('ICL_TM_TMETHOD_MANUAL', 0);
define('ICL_TM_TMETHOD_EDITOR', 1);
define('ICL_TM_TMETHOD_PRO', 2);

if ( ! defined( 'ICL_TM_DOCS_PER_PAGE' ) ) {
	define( 'ICL_TM_DOCS_PER_PAGE', 20 );
}

define('ICL_ASIAN_LANGUAGE_CHAR_SIZE', 6);

/* legacy? */
define( 'CMS_REQUEST_WAITING_FOR_PROJECT_CREATION', 1 );

define ( 'ICL_FINANCE_LINK', '/finance' );

define( 'MESSAGE_TRANSLATION_IN_PROGRESS', 3 );
define( 'MESSAGE_TRANSLATION_COMPLETE', 4 );

define( 'ICL_LANG_SEL_BLUE_FONT_CURRENT_NORMAL', '#ffffff' );
define( 'ICL_LANG_SEL_BLUE_FONT_CURRENT_HOVER', '#000000' );
define( 'ICL_LANG_SEL_BLUE_BACKGROUND_CURRENT_NORMAL', '#0099cc' );
define( 'ICL_LANG_SEL_BLUE_BACKGROUND_CURRENT_HOVER', '#0099cc' );
define( 'ICL_LANG_SEL_BLUE_FONT_OTHER_NORMAL', '#000000' );
define( 'ICL_LANG_SEL_BLUE_FONT_OTHER_HOVER', '#000000' );
define( 'ICL_LANG_SEL_BLUE_BACKGROUND_OTHER_NORMAL', '#eeeeee' );
define( 'ICL_LANG_SEL_BLUE_BACKGROUND_OTHER_HOVER', '#cccccc' );
define( 'ICL_LANG_SEL_BLUE_BORDER', '#000000' );
define( 'ICL_LANG_SEL_WHITE_FONT_CURRENT_NORMAL', '#444444' );
define( 'ICL_LANG_SEL_WHITE_FONT_CURRENT_HOVER', '#000000' );
define( 'ICL_LANG_SEL_WHITE_BACKGROUND_CURRENT_NORMAL', '#ffffff' );
define( 'ICL_LANG_SEL_WHITE_BACKGROUND_CURRENT_HOVER', '#eeeeee' );
define( 'ICL_LANG_SEL_WHITE_FONT_OTHER_NORMAL', '#444444' );
define( 'ICL_LANG_SEL_WHITE_FONT_OTHER_HOVER', '#000000' );
define( 'ICL_LANG_SEL_WHITE_BACKGROUND_OTHER_NORMAL', '#ffffff' );
define( 'ICL_LANG_SEL_WHITE_BACKGROUND_OTHER_HOVER', '#eeeeee' );
define( 'ICL_LANG_SEL_WHITE_BORDER', '#aaaaaa' );
define( 'ICL_LANG_SEL_GRAY_FONT_CURRENT_NORMAL', '#222222' );
define( 'ICL_LANG_SEL_GRAY_FONT_CURRENT_HOVER', '#000000' );
define( 'ICL_LANG_SEL_GRAY_BACKGROUND_CURRENT_NORMAL', '#eeeeee' );
define( 'ICL_LANG_SEL_GRAY_BACKGROUND_CURRENT_HOVER', '#dddddd' );
define( 'ICL_LANG_SEL_GRAY_FONT_OTHER_NORMAL', '#222222' );
define( 'ICL_LANG_SEL_GRAY_FONT_OTHER_HOVER', '#000000' );
define( 'ICL_LANG_SEL_GRAY_BACKGROUND_OTHER_NORMAL', '#eeeeee' );
define( 'ICL_LANG_SEL_GRAY_BACKGROUND_OTHER_HOVER', '#dddddd' );
define( 'ICL_LANG_SEL_GRAY_BORDER', '#555555' );

define( 'ICL_PRO_TRANSLATION_COST_PER_WORD', 0.09 );
define( 'ICL_PRO_TRANSLATION_PICKUP_XMLRPC', 0 );
define( 'ICL_PRO_TRANSLATION_PICKUP_POLLING', 1 );

define( 'ICL_REMOTE_WPML_CONFIG_FILES_INDEX', 'https://d2salfytceyqoe.cloudfront.net/' );

define( 'ICL_ICONS_URL', ICL_PLUGIN_URL . '/res/img/' );
define( 'ICL_ICON', ICL_ICONS_URL . 'icon.png' );
define( 'ICL_ICON16', ICL_ICONS_URL . 'icon16.png' );

define( 'WPML_ELEMENT_IS_NOT_TRANSLATED', 0 );
define( 'WPML_ELEMENT_IS_TRANSLATED', 1 );
define( 'WPML_ELEMENT_IS_DUPLICATED', 2 );
define( 'WPML_ELEMENT_IS_A_DUPLICATE', 3 );

define( 'WPML_STRING_TABLE_NAME_CONTEXT_LENGTH', 160 );

define( "WPML_QUERY_IS_ROOT", 1 );
define( "WPML_QUERY_IS_OTHER_THAN_ROOT", 2 );
define( "WPML_QUERY_IS_NOT_FOR_POST", 3 );

define( 'WPML_XDOMAIN_DATA_OFF', 	0 );
define( 'WPML_XDOMAIN_DATA_GET', 	1 );
define( 'WPML_XDOMAIN_DATA_POST', 	2 );

define( 'WPML_TT_TAXONOMIES_NOT_TRANSLATED', 1 );
define( 'WPML_TT_TAXONOMIES_ALL', 0 );
// This sets the number of rows in the table to be displayed by this class, not the actual number of terms.
define( 'WPML_TT_TERMS_PER_PAGE', 10 );
define( 'WPML_TRANSLATE_CUSTOM_FIELD', 2 );
define( 'WPML_COPY_CUSTOM_FIELD', 1 );
define( 'WPML_IGNORE_CUSTOM_FIELD', 0 );

define( 'WPML_POST_META_CONFIG_INDEX_SINGULAR', 'custom-field' );
define( 'WPML_POST_META_SETTING_INDEX_SINGULAR', 'custom_field' );
define( 'WPML_POST_META_CONFIG_INDEX_PLURAL', 'custom-fields' );
define( 'WPML_POST_META_SETTING_INDEX_PLURAL', 'custom_fields_translation' );

define( 'WPML_TERM_META_CONFIG_INDEX_SINGULAR', 'custom-term-field' );
define( 'WPML_TERM_META_CONFIG_INDEX_PLURAL', 'custom-term-fields' );

define( 'WPML_TERM_META_SETTING_INDEX_SINGULAR', 'custom_term_field' );
define( 'WPML_TERM_META_SETTING_INDEX_PLURAL', 'custom_term_fields_translation' );

define( 'WPML_POST_META_READONLY_SETTING_INDEX', 'custom_fields_readonly_config' );
define( 'WPML_TERM_META_READONLY_SETTING_INDEX', 'custom_term_fields_readonly_config' );

define( 'WPML_POST_TYPE_READONLY_SETTING_INDEX', 'custom_types_readonly_config' );

define( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY',  1 );
define( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN',     2 );
define( 'WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER',  3 );

define( 'WPML_ELEMENT_TRANSLATIONS_CACHE_GROUP', 'element_translations' );

define('WEBSITE_DETAILS_TRANSIENT_KEY', 'wpml_icl_query_website_details');