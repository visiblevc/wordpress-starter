<?php


    
class WPML_Disqus_Integration{
    
    function __construct(){
        add_action('init', array($this, 'init'));
    }
    
    function init(){
        add_action('disqus_language_filter', array($this, 'language'));
    }
    
    function language(){
        global $sitepress;
        
        /*
        LANGUAGES = [
            ('English', 'en'),
            ('Arabic', 'ar'),
            ('Afrikaans', 'af'),
            ('Albanian', 'sq'),
            ('Azerbaijani', 'az'),
            ('Basque', 'eu'),
            ('Bulgarian', 'bg'),
            ('Burmese', 'my'),
            ('Chinese (Simplified)', 'zh'),
            ('Chinese (Traditional)', 'zh_HANT'),
            ('Croatian', 'hr'),
            ('Czech', 'cs'),
            ('Danish', 'da'),
            ('Dutch', 'nl'),
            ('Esperanto', 'eo'),
            ('Estonian', 'et'),
            ('Finnish', 'fi'),
            ('French', 'fr'),
            ('Galician', 'gl'),
            ('German (formal)', 'de_formal'),
            ('German (informal)', 'de_inf'),
            ('Greek', 'el'),
            ('Greenlandic', 'kl'),
            ('Hebrew', 'he'),
            ('Hungarian', 'hu'),
            ('Italian', 'it'),
            ('Icelandic', 'is'),
            ('Indonesian', 'id'),
            ('Japanese', 'ja'),
            ('Khmer', 'km'),
            ('Korean', 'ko'),
            ('Laotian', 'lo'),
            ('Latin', 'la'),
            ('Latvian', 'lv'),
            ('Letzeburgesch', 'lb'),
            ('Lithuanian', 'lt'),
            ('Macedonian', 'mk'),
            ('Malay (Bahasa Melayu)', 'ms'),
            (u'Norwegian (Bokm�l)', 'nb'),
            ('Persian', 'fa'),
            ('Polish', 'pl'),
            ('Portuguese (Brazil)', 'pt_BR'),
            ('Portuguese (European)', 'pt_EU'),
            ('Romanian', 'ro'),
            ('Russian', 'ru'),
            ('Serbian (Cyrillic)', 'sr_CYRL'),
            ('Serbian (Latin)', 'sr_LATIN'),
            ('Slovak', 'sk'),
            ('Slovenian', 'sl'),
            ('Spanish (Argentina)', 'es_AR'),
            ('Spanish (Mexico)', 'es_MX'),
            ('Spanish (Spain)', 'es_ES'),
            ('Swedish', 'sv_SE'),
            ('Tok Pisin', 'tpi'),
            ('Turkish', 'tr'),
            ('Thai', 'th'),
            ('Ukrainian', 'uk'),
            ('Vietnamese', 'vi'),
            ]        
        */
        
        $map = array(
            'ar'    => 'ar',
            'bg'    => 'bg',
            'bs'    => '', //
            'ca'    => 'ca',
            'cs'    => 'cs',
            'cy'    => 'cy',
            'da'    => 'da',
            'de'    => 'de_formal',
            'el'    => 'el',
            'en'    => 'en',
            'eo'    => 'eo',
            'es'    => 'es_ES',
            'et'    => 'et',
            'eu'    => 'eu',
            'fa'    => 'fa',
            'fi'    => 'fi',
            'fr'    => 'fr',
            'ga'    => '', //
            'he'    => 'he',
            'hi'    => '',
            'hr'    => 'hr',
            'hu'    => 'hu',
            'hy'    => 'hy',
            'id'    => 'id',
            'is'    => 'id',
            'it'    => 'it',
            'ja'    => 'ja',
            'ko'    => 'ko',
            'ku'    => '',
            'la'    => 'la',
            'lt'    => 'lt',
            'lv'    => 'lv',
            'mk'    => 'mk',
            'mn'    => '', //
            'mo'    => '', //ro?
            'mt'    => '', //
            'nb'    => 'nb',
            'ne'    => '', //
            'nl'    => 'nl',
            'pa'    => '', //
            'pl'    => 'pl',
            'pt-br'    => 'pt_BR',
            'pt-pt'    => 'PT_EU',
            'qu'    => '', //
            'ro'    => 'ro',
            'ru'    => 'ru',
            'sk'    => 'sk',
            'sl'    => 'sl',
            'so'    => '', //
            'sq'    => 'sq',
            'sr'    => 'sr_CYRL',
            'sv'    => 'sv_SE',
            'ta'    => '', //
            'th'    => 'th',
            'tr'    => 'tr',
            'uk'    => 'uk',
            'ur'    => 'ur',
            'uz'    => '', //
            'vi'    => 'vi',
            'yi'    => '', //
            'zh-hans'    => 'zh',
            'zh-hant'    => 'zh_AHNT',
            'zu'    => 'af'
        );
        
        $map = apply_filters('wpml_disqus_language_map', $map);

		$current_language = $sitepress->get_current_language();
		$lang = isset($map[ $current_language ]) ? $map[ $current_language ] : '';
            
        return $lang;
    }
    
}

$WPML_Disqus_Integration = new WPML_Disqus_Integration;

?>
