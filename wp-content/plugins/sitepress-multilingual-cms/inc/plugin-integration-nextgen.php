<?php
/*
 * NextGen Gallery plugin integration.
 * 
 * - Filters the_content
 * -- Adjusts gallery preview image URL from default to current language (2.0.66 <=)
 */

class WPML_Plugin_Integration_Nexgen_Gallery
{

    function __construct(){
        if ( version_compare( NEXTGEN_GALLERY_PLUGIN_VERSION, '2.0.66', '<=' ) ) {
            add_filter( 'the_content', array('WPML_Plugin_Integration_Nexgen_Gallery', 'the_content_gallery_preview_images'), 1 );
    }
    }

    /**
     * Filters post content and fixes gallery preview images URL.
     * 
     * Adjust gallery preview image URL from default to current language.
     * Allows NextGen to match and replace preview images with gallery.
     * NextGen inserts image previews with default language URL.
     * 
     * @global SitePress $sitepress
     * @param string $content
     * @return string
     */
    public static function the_content_gallery_preview_images( $content ) {
        global $sitepress;
        if ( $sitepress->get_current_language() != $sitepress->get_default_language() ) {
            $default_url = preg_replace( '/(^http[s]?:\/\/)/', '',
                    $sitepress->language_url( $sitepress->get_default_language() ) );
            $current_url = preg_replace( '/(^http[s]?:\/\/)/', '', home_url() );
            $preview_url = $default_url . 'nextgen-attach_to_post/preview';
            $alt_preview_url = $default_url . 'index.php/nextgen-attach_to_post/preview';
            if ( preg_match_all( "#<img(.*)http(s)?://({$preview_url}|{$alt_preview_url})/id--(\\d+)(.*)\\/>#mi", $content, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $content = str_replace( $match[0], "<img{$match[1]}http{$match[2]}://{$current_url}nextgen-attach_to_post/preview/id--{$match[4]}\"{$match[5]}/>", $content );
                }
            }
        }
        return $content;
    }

}

$wpml_ngg = new WPML_Plugin_Integration_Nexgen_Gallery();