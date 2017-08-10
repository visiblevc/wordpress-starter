<?php

function pre_wpml_is_translated_post_type_filter( $translated, $type ) {

	return $type === 'attachment' ? true : $translated;
}

add_filter( 'pre_wpml_is_translated_post_type', 'pre_wpml_is_translated_post_type_filter', 10, 2 );