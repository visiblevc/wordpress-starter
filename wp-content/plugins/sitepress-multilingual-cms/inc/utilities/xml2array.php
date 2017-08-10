<?php

function icl_xml2array( $contents, $get_attributes = true ) {
	$xml2array = new WPML_XML2Array( $contents, $get_attributes );

	return $xml2array->run();
}