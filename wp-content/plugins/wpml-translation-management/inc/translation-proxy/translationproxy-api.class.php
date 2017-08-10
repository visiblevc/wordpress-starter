<?php
/**
 * @package wpml-core
 * @subpackage wpml-core
 */

if ( ! class_exists( 'TranslationProxy_Api_Error' ) ) {
	class TranslationProxy_Api_Error extends Exception {

		public function __construct( $message ) {
			WPML_TranslationProxy_Com_Log::log_error( $message );

			parent::__construct( $message );
		}
	}
}

if ( ! class_exists( 'TranslationProxy_Api' ) ) {
	class TranslationProxy_Api {
		const API_VERSION = 1.1;

		public static function proxy_request( $path, $params = array(), $method = 'GET', $multi_part = false, $has_return_value = true ) {

			return wpml_tm_load_tp_networking()->send_request( OTG_TRANSLATION_PROXY_URL . $path, $params, $method, $has_return_value );
		}

		public static function proxy_download( $path, $params ) {

			return wpml_tm_load_tp_networking()->send_request( OTG_TRANSLATION_PROXY_URL . $path, $params, 'GET', true, false );
		}

		public static function service_request( $url, $params = array(), $method = 'GET', $has_return_value = true, $json_response = false, $has_api_response = false ) {

			return wpml_tm_load_tp_networking()->send_request( $url, $params, $method, $has_return_value, $json_response, $has_api_response );
		}

		public static function add_parameters_to_url( $url, $params ) {
			if ( preg_match_all( '/\{.+?\}/', $url, $symbs ) ) {
				foreach ( $symbs[0] as $symb ) {
					$without_braces = preg_replace( '/\{|\}/', '', $symb );
					if ( preg_match_all( '/\w+/', $without_braces, $indexes ) ) {
						foreach ( $indexes[0] as $index ) {
							if ( isset( $params[ $index ] ) ) {
								$value = $params[ $index ];
								$url   = preg_replace( preg_quote( "/$symb/" ), $value, $url );
							}
						}
					}
				}
			}

			return $url;
		}
	}
}

if ( ! function_exists( "gzdecode" ) ) {
	/**
	 * Inflates a string enriched with gzip headers. Counterpart to gzencode().
	 * Extracted from upgradephp
	 * http://include-once.org/p/upgradephp/
	 *
	 * officially available by default in php @since 5.4.
	 */
	function gzdecode( $gzdata, $maxlen = null ) {

		#-- decode header
		$len = strlen( $gzdata );
		if ( $len < 20 ) {
			return;
		}
		$head = substr( $gzdata, 0, 10 );
		$head = unpack( "n1id/C1cm/C1flg/V1mtime/C1xfl/C1os", $head );
		list( $ID, $CM, $FLG, $MTIME, $XFL, $OS ) = array_values( $head );
		$FTEXT    = 1 << 0;
		$FHCRC    = 1 << 1;
		$FEXTRA   = 1 << 2;
		$FNAME    = 1 << 3;
		$FCOMMENT = 1 << 4;
		$head     = unpack( "V1crc/V1isize", substr( $gzdata, $len - 8, 8 ) );
		list( $CRC32, $ISIZE ) = array_values( $head );

		#-- check gzip stream identifier
		if ( $ID != 0x1f8b ) {
			trigger_error( "gzdecode: not in gzip format", E_USER_WARNING );

			return;
		}
		#-- check for deflate algorithm
		if ( $CM != 8 ) {
			trigger_error( "gzdecode: cannot decode anything but deflated streams", E_USER_WARNING );

			return;
		}

		#-- start of data, skip bonus fields
		$s = 10;
		if ( $FLG & $FEXTRA ) {
			$s += $XFL;
		}
		if ( $FLG & $FNAME ) {
			$s = strpos( $gzdata, "\000", $s ) + 1;
		}
		if ( $FLG & $FCOMMENT ) {
			$s = strpos( $gzdata, "\000", $s ) + 1;
		}
		if ( $FLG & $FHCRC ) {
			$s += 2; // cannot check
		}

		#-- get data, uncompress
		$gzdata = substr( $gzdata, $s, $len - $s );
		if ( $maxlen ) {
			$gzdata = gzinflate( $gzdata, $maxlen );

			return ( $gzdata ); // no checks(?!)
		} else {
			$gzdata = gzinflate( $gzdata );
		}

		#-- check+fin
		$chk = crc32( $gzdata );
		if ( $CRC32 != $chk ) {
			trigger_error( "gzdecode: checksum failed (real$chk != comp$CRC32)", E_USER_WARNING );
		} elseif ( $ISIZE != strlen( $gzdata ) ) {
			trigger_error( "gzdecode: stream size mismatch", E_USER_WARNING );
		} else {
			return ( $gzdata );
		}
	}
}
