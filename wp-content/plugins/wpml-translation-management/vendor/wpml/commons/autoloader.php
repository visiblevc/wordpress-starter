<?php
if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
	require_once dirname( __FILE__ ) . '/src/wpml-auto-loader.php';
} else {
	require_once __DIR__ . '/../../autoload.php';
}
