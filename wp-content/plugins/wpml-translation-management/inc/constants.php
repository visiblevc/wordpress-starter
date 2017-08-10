<?php
define('WPML_TM_FOLDER', basename(WPML_TM_PATH));

define('WPML_TM_URL', plugins_url('', dirname(__FILE__)));

define('TP_MIGRATION_NOT_STARTED', 0);
define('TP_MIGRATION_REQUESTED', 2);
define('TP_MIGRATION_IN_PROGRESS', 3);
define('TP_MIGRATION_WAITING_CONFIRMATION', 4);
define('TP_MIGRATION_COMPLETED', 1);

if ( ! defined( 'TRANSLATION_PROXY_XLIFF_VERSION' ) ) {
	define( 'TRANSLATION_PROXY_XLIFF_VERSION', '12' );
}

if ( ! defined( 'WPML_XLIFF_TM_URL' ) ) {
	define( 'WPML_XLIFF_TM_URL', plugins_url( '', dirname( __FILE__ ) ) );
}

if ( ! defined( 'WPML_XLIFF_TM_NEWLINES_REPLACE' ) ) {
	define( 'WPML_XLIFF_TM_NEWLINES_REPLACE', 1 ); //
}

if ( ! defined( 'WPML_XLIFF_TM_NEWLINES_ORIGINAL' ) ) {
	define( 'WPML_XLIFF_TM_NEWLINES_ORIGINAL', 2 ); //
}

if ( ! defined( 'WPML_XLIFF_DEFAULT_VERSION' ) ) {
	define( 'WPML_XLIFF_DEFAULT_VERSION', '12' );
}
if ( ! defined( 'TA_URL_ENDPOINT' ) ) {
	define('TA_URL_ENDPOINT', 'https://www.icanlocalize.com');
}

if ( ! defined( 'TA_SCHEDULE_OCCURENCE' ) ) {
	define('TA_SCHEDULE_OCCURENCE', 'daily');
}

global $asian_languages;
$asian_languages = array('ja', 'ko', 'zh-hans', 'zh-hant', 'mn', 'ne', 'hi', 'pa', 'ta', 'th');
