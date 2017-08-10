<?php

function wpml_st_pos_scan_store_results( $string, $domain, $context, $file, $line ) {
	global $__wpml_st_po_file_content;
	static $strings = array();

	$key = md5( $domain . $context . $string );
	//avoid duplicates
	if ( isset( $strings[ $key ] ) ) {
		return false;
	}
	$strings[ $key ] = true;

	$file = @file( $file );
	if ( ! empty( $file ) ) {
		$__wpml_st_po_file_content .= PHP_EOL;
		$__wpml_st_po_file_content .= '# ' . @trim( $file[ $line - 2 ] ) . PHP_EOL;
		$__wpml_st_po_file_content .= '# ' . @trim( $file[ $line - 1 ] ) . PHP_EOL;
		$__wpml_st_po_file_content .= '# ' . @trim( $file[ $line ] ) . PHP_EOL;
	}

	//$__wpml_st_po_file_content .= 'msgid "'.str_replace('"', '\"', $string).'"' . PHP_EOL;
	$__wpml_st_po_file_content .= PHP_EOL;
	if ( $context ) {
		$__wpml_st_po_file_content .= 'msgctxt "' . $context . '"' . PHP_EOL;
	}
	$__wpml_st_po_file_content .= 'msgid "' . $string . '"' . PHP_EOL;
	$__wpml_st_po_file_content .= 'msgstr ""' . PHP_EOL;
}
