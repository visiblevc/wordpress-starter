<?php
// part of SitePress class definition
if(!defined('ICL_LANGUAGE_CODE')){
    define('ICL_LANGUAGE_CODE', $this->this_lang);
}  
$language_details = $this->get_language_details(ICL_LANGUAGE_CODE);
if(!defined('ICL_LANGUAGE_NAME')){
    define('ICL_LANGUAGE_NAME', $language_details['display_name']);
}  
if(!defined('ICL_LANGUAGE_NAME_EN')){
    define('ICL_LANGUAGE_NAME_EN', $language_details['english_name']);
}