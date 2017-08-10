<?php
class ICanLocalizeQuery{

	private $site_id;
	private $access_key;
	private $error = null;
	private $sitepress;
	private $wpml_icl_client;

	function __construct( $site_id = null, $access_key = null, SitePress $sitepress = null, $wpml_icl_client = null ) {
		$this->site_id    = $site_id;
		$this->access_key = $access_key;
		if ( null === $sitepress ) {
			global $sitepress;
		}
		$this->sitepress = $sitepress;
		if ( null === $wpml_icl_client ) {
			$wpml_icl_client = new WPML_ICL_Client( new WP_Http(), new WPML_WP_API() );
		}
		$this->wpml_icl_client = $wpml_icl_client;
	}
      
      public function setting($setting){
          return $this->$setting;
      }
      
      public function error(){
          return $this->error;
      }

    function updateAccount($data){        
        $request = ICL_API_ENDPOINT . '/websites/'.$data['site_id'].'/update_by_cms.xml';
        unset($data['site_id']);
        $response = $this->request($request, 'POST', $data);
        if(!$response){
            return $this->error;
        }else{
            return 0;            
        }
    }

	/**
	 * @param bool $force
	 *
	 * @return array of website details returned from a direct API call to ICL
	 */
	function get_website_details( $force = false ) {
		$res = $this->sitepress->get_wp_api()->get_transient( WEBSITE_DETAILS_TRANSIENT_KEY );

		if ( ! $res || $force ) {
			$website_details_cache_index = '_last_valid_icl_website_details';
			$request_url                 = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '.xml?accesskey=' . $this->access_key;
			$res                         = $this->request( $request_url );
			if ( isset( $res['info']['website'] ) ) {
				$res = $res['info']['website'];
				$this->sitepress->set_setting( $website_details_cache_index, $res, true );
			} else {
				$res = $this->sitepress->get_setting( $website_details_cache_index, array() );
			}
			$this->sitepress->get_wp_api()->set_transient( WEBSITE_DETAILS_TRANSIENT_KEY, $res, DAY_IN_SECONDS );
		}
		return $res;
	}

	private function request( $request, $method = 'GET', $formvars = null ) {
		$this->wpml_icl_client->set_method( $method );
		$this->wpml_icl_client->set_post_data( $formvars );

		return $this->wpml_icl_client->request( $request );
	}
    
}

/**
 * gzdecode implementation
 *
 * @see http://hu.php.net/manual/en/function.gzencode.php#44470
 * 
 * @param string $data
 * @param string $filename
 * @param string $error
 * @param int $maxlength
 * @return string
 */
function icl_gzdecode($data, &$filename = '', &$error = '', $maxlength = null) {
    $len = strlen ( $data );
    if ($len < 18 || strcmp ( substr ( $data, 0, 2 ), "\x1f\x8b" )) {
        $error = "Not in GZIP format.";
        return null; // Not GZIP format (See RFC 1952)
    }
    $method = ord ( substr ( $data, 2, 1 ) ); // Compression method
    $flags = ord ( substr ( $data, 3, 1 ) ); // Flags
    if ($flags & 31 != $flags) {
        $error = "Reserved bits not allowed.";
        return null;
    }
    $headerlen = 10;
    if ($flags & 4) {
        // 2-byte length prefixed EXTRA data in header
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $extralen = unpack ( "v", substr ( $data, 8, 2 ) );
        $extralen = $extralen [1];
        if ($len - $headerlen - 2 - $extralen < 8) {
            return false; // invalid
        }
        $headerlen += 2 + $extralen;
    }
    $filename = "";
    if ($flags & 8) {
        // C-style string
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $filenamelen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            return false; // invalid
        }
        $filename = substr ( $data, $headerlen, $filenamelen );
        $headerlen += $filenamelen + 1;
    }
    if ($flags & 16) {
        // C-style string COMMENT data in header
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $commentlen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            return false; // Invalid header format
        }
        $headerlen += $commentlen + 1;
    }
    if ($flags & 2) {
        // 2-bytes (lowest order) of CRC32 on header present
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $calccrc = crc32 ( substr ( $data, 0, $headerlen ) ) & 0xffff;
        $headercrc = unpack ( "v", substr ( $data, $headerlen, 2 ) );
        $headercrc = $headercrc [1];
        if ($headercrc != $calccrc) {
            $error = "Header checksum failed.";
            return false; // Bad header CRC
        }
        $headerlen += 2;
    }
    // GZIP FOOTER
    $datacrc = unpack ( "V", substr ( $data, - 8, 4 ) );
    $datacrc = sprintf ( '%u', $datacrc [1] & 0xFFFFFFFF );
    $isize = unpack ( "V", substr ( $data, - 4 ) );
    $isize = $isize [1];
    // decompression:
    $bodylen = $len - $headerlen - 8;
    if ($bodylen < 1) {
        // IMPLEMENTATION BUG!
        return null;
    }
    $body = substr ( $data, $headerlen, $bodylen );
    $data = "";
    if ($bodylen > 0) {
        switch ($method) {
            case 8 :
                // Currently the only supported compression method:
                $data = gzinflate ( $body, $maxlength );
                break;
            default :
                $error = "Unknown compression method.";
                return false;
        }
    } // zero-byte body content is allowed
    // Verifiy CRC32
    $crc = sprintf ( "%u", crc32 ( $data ) );
    $crcOK = $crc == $datacrc;
    $lenOK = $isize == strlen ( $data );
    if (! $lenOK || ! $crcOK) {
        $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
        return false;
    }
    return $data;
}
