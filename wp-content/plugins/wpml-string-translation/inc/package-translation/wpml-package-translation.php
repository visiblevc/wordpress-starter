<?php
/*
WPML Package Translation
This is now part of String Translation.
*/

if ( defined( 'WPML_PACKAGE_TRANSLATION' ) ) {
	return;
}

define( 'WPML_PACKAGE_TRANSLATION', '0.0.2' );
define( 'WPML_PACKAGE_TRANSLATION_PATH', dirname( __FILE__ ) );
define( 'WPML_PACKAGE_TRANSLATION_URL', WPML_ST_URL . '/inc/' . basename( WPML_PACKAGE_TRANSLATION_PATH ) );

require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-schema.class.php';

require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-constants.php';

require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-exception.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-helper.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-ui.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-html-packages.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-metabox.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-st.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-tm-jobs.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation-tm.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package.class.php';
require WPML_PACKAGE_TRANSLATION_PATH . '/inc/wpml-package-translation.class.php';

$WPML_package_translation               = new WPML_Package_Translation();
$WPML_Package_Translation_UI            = new WPML_Package_Translation_UI();
$WPML_Package_Translation_HTML_Packages = new WPML_Package_Translation_HTML_Packages();