<?php
/* @todo: [WPML 3.3] check if needed in 3.3 */
/* This file includes a set of functions that can be used by WP plugins developers to make their plugins interact with WPML */

/* constants */
define('WPML_API_SUCCESS' , 0);
define('WPML_API_ERROR' , 99);
define('WPML_API_INVALID_LANGUAGE_CODE' , 1);
define('WPML_API_INVALID_TRID' , 2);
define('WPML_API_LANGUAGE_CODE_EXISTS' , 3);
define('WPML_API_CONTENT_NOT_FOUND' , 4);
define('WPML_API_TRANSLATION_NOT_FOUND' , 5);
define('WPML_API_INVALID_CONTENT_TYPE' , 6);
define('WPML_API_CONTENT_EXISTS' , 7);
define('WPML_API_FUNCTION_ALREADY_DECLARED', 8);
define('WPML_API_CONTENT_TRANSLATION_DISABLED', 9);

define('WPML_API_GET_CONTENT_ERROR' , 0);

define('WPML_API_MAGIC_NUMBER', 6);
define('WPML_API_ASIAN_LANGUAGES', 'zh-hans|zh-hant|ja|ko');
define('WPML_API_COST_PER_WORD', 0.09);


function _wpml_api_allowed_content_type($content_type){
    $reserved_types = array(
        'post'      => 1,
        'page'      => 1,
        'tax_post_tag'       => 1,
        'tax_category'  => 1,
        'comment'   => 1
    );
    return !isset($reserved_types[$content_type]) && preg_match('#([a-z0-9_\-])#i', $content_type);
}

/**
 * Add translatable content to the WPML translations table
 *
 * @since      1.3
 * @package    WPML
 * @subpackage WPML API
 *
 * @param string      $content_type  Content type.
 * @param int         $content_id    Content ID.
 * @param bool|string $language_code Content language code. (defaults to current language)
 * @param bool|int    $trid          Content trid - if a translation in a different language already exists.
 *
 * @return int error code
 */
function wpml_add_translatable_content($content_type, $content_id, $language_code = false, $trid = false){
    global $sitepress, $wpdb;

    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE;
    }

    if($trid){
        $trid_type   = $wpdb->get_var( $wpdb->prepare(" SELECT element_type
                                                        FROM {$wpdb->prefix}icl_translations
                                                        WHERE trid = %d ", $trid) );
        if(!$trid_type || $trid_type != $content_type){
            return WPML_API_INVALID_TRID;
        }
    }

    if($wpdb->get_var( $wpdb->prepare(" SELECT translation_id
                                        FROM {$wpdb->prefix}icl_translations
                                        WHERE element_type=%s
                                          AND element_id=%d", $content_type, $content_id))){
        return WPML_API_CONTENT_EXISTS;
    }

    $t = $sitepress->set_element_language_details($content_id, $content_type, $trid, $language_code);

    if(!$t){
        return WPML_API_ERROR;
    }else{
        return WPML_API_SUCCESS;
    }

}


/**
 * Update translatable content in the WPML translations table
 *
 * @since      1.3
 * @package    WPML
 * @subpackage WPML API
 *
 * @param string $content_type  Content type.
 * @param int    $content_id    Content ID.
 * @param string $language_code Content language code.
 *
 * @return int error code
 */
function wpml_update_translatable_content($content_type, $content_id, $language_code){
    global $sitepress;

    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if(!$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE;
    }

    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }

    $translations = $sitepress->get_element_translations($trid);
    if(isset($translations[$language_code]) && !$translations[$language_code]->element_id != $content_id){
        return WPML_API_LANGUAGE_CODE_EXISTS;
    }

    $t = $sitepress->set_element_language_details($content_id, $content_type, $trid, $language_code);

    if(!$t){
        return WPML_API_ERROR;
    }else{
        return WPML_API_SUCCESS;
    }

}

/**
 * Update translatable content in the WPML translations table
 *
 * @since      1.3
 * @deprecated deprecated since 3.2
 *             
 * @package    WPML
 * @subpackage WPML API
 *
 * @param string      $content_type  Content type.
 * @param int         $content_id    Content ID.
 * @param bool|string $language_code Content language code. (when ommitted - delete all translations associated with the respective content)
 *
 * @return int error code
 */
function wpml_delete_translatable_content($content_type, $content_id, $language_code = false){
    global $sitepress;

    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE;
    }

    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }

    if($language_code){
        $translations = $sitepress->get_element_translations($trid);
        if(!isset($translations[$language_code])){
            return WPML_API_TRANSLATION_NOT_FOUND;
        }

    }

    $sitepress->delete_element_translation($trid, $content_type, $language_code);

    return WPML_API_SUCCESS;
}

/**
 * Get trid value for a specific piece of content
 *
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 *
 * @return int trid or 0 for error
 *  */
function wpml_get_content_trid($content_type, $content_id){
    global $sitepress;

    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_GET_CONTENT_ERROR; //WPML_API_INVALID_CONTENT_TYPE;
    }

    $trid = $sitepress->get_element_trid($content_id, $content_type);

    if(!$trid){
        return WPML_API_GET_CONTENT_ERROR;
    }else{
        return $trid;
    }
}

/**
 * Detects the current language and returns the language relevant content id. optionally it can return the original id if a translation is not found
 * See also wpml_object_id_filter() in \template-functions.php
 *
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param bool $return_original return the original id when translation not found.
 *
 * @return int trid or 0 for error
 *  
 */
function wpml_get_content($content_type, $content_id, $return_original = true){
    global $sitepress, $wpdb;

    $trid = $sitepress->get_element_trid($content_id, $content_type);

    if(!$trid){
        return WPML_API_GET_CONTENT_ERROR;
    }else{
        if($content_id <= 0){
            return $content_id;
        }
        if($content_type=='category' || $content_type=='post_tag' || $content_type=='tag'){
            $content_id = $wpdb->get_var($wpdb->prepare(" SELECT term_taxonomy_id
                                                          FROM {$wpdb->term_taxonomy}
                                                          WHERE term_id = %d
                                                            AND taxonomy = %s",
                                                        $content_id, $content_type));
        }
        if($content_type=='post_tag'){
            $icl_element_type = 'tax_post_tag';
        }elseif($content_type=='category'){
            $icl_element_type = 'tax_category';
        }elseif($content_type=='page'){
            $icl_element_type = 'post';
        }else{
            $icl_element_type = $content_type;
        }

        $trid = $sitepress->get_element_trid($content_id, $icl_element_type);
        $translations = $sitepress->get_element_translations($trid, $icl_element_type);

        if(isset($translations[ICL_LANGUAGE_CODE]->element_id)){
            $ret_element_id = $translations[ICL_LANGUAGE_CODE]->element_id;
            if($content_type=='category' || $content_type=='post_tag'){
                $ret_element_id = $wpdb->get_var($wpdb->prepare(" SELECT t.term_id
                                                                  FROM {$wpdb->term_taxonomy} tx
                                                                  JOIN {$wpdb->terms} t
                                                                    ON t.term_id = tx.term_id
                                                                  WHERE tx.term_taxonomy_id = %d
                                                                    AND tx.taxonomy=%s", $ret_element_id, $content_type));
            }
        }else{
            $ret_element_id = $return_original ? $content_id : null;
        }

        return $ret_element_id;
    }
}

/**
 * Get translations for a certain piece of content
 *
 * @since      1.3
 * @package    WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int    $content_id   Content ID.
 * @param bool   $skip_missing
 *
 * @internal   param bool $return_original return the original id when translation not found.
 *
 * @return array|int translations or error code
 */
function wpml_get_content_translations($content_type, $content_id, $skip_missing = true){
    global $sitepress;

    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_TRANSLATION_NOT_FOUND;
    }

    $translations = $sitepress->get_element_translations($trid, $content_type, $skip_missing);

	$tr = array();
	foreach($translations as $k=>$v){
		$tr[$k] = $v->element_id;
	}

	return $tr;
}

/**
 *  Returns a certain translation for a piece of content
 *
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param bool $language_code
 *
 * @return int|array error code or array('lang'=>element_id)
 */
function wpml_get_content_translation($content_type, $content_id, $language_code){
    global $sitepress;

    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }

    $translations = $sitepress->get_element_translations($trid, $content_type, true);

    if(!isset($translations[$language_code])){
        return WPML_API_TRANSLATION_NOT_FOUND;
    }else{
        return array($language_code => $translations[$language_code]->element_id);
    }

}

/**
 * Returns the list of active languages
 * See also wpml_get_active_languages_filter() in \template-functions.php
 *
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 *
 * @return array
 *  */
function wpml_get_active_languages(){
    global $sitepress;
    $langs = $sitepress->get_active_languages();
    return $langs;
}



/**
 *  Get contents of a specific type
 *
 * @since      1.3
 * @package    WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 *
 * @param bool   $language_code
 *
 * @return int or array
 */

function wpml_get_contents($content_type, $language_code = false){
    global $sitepress, $wpdb;

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE;
    }

    if(!$language_code){
        $language_code = $sitepress->get_current_language();
    }

    $contents = $wpdb->get_col( $wpdb->prepare("SELECT element_id
                                                FROM {$wpdb->prefix}icl_translations
                                                WHERE element_type = %s AND language_code = %s",
                                                $content_type, $language_code ) );
    return $contents;

}

/**
 * Returns the number of the words that will be sent to translation and a cost estimate
 * @since      1.3
 * @package    WPML
 * @subpackage WPML API
 *
 * @param string      $string
 * @param bool|string $language - should be specified when the language is one of zh-hans|zh-hant|ja|ko
 *
 * @return array (count, cost)
 */
function wpml_get_word_count($string, $language = false){

    $asian_languages = explode('|', WPML_API_ASIAN_LANGUAGES);
	$count = 0;

    if($language && in_array($language, $asian_languages)){
        $count = ceil(strlen($string)/WPML_API_MAGIC_NUMBER);
    }elseif(is_string($string)){
		$words = preg_split( '/[\s\/]+/', $string, 0, PREG_SPLIT_NO_EMPTY );
		$count = count( $words );
    }

    $cost  = $count * WPML_API_COST_PER_WORD;

    $ret = array('count'=>$count, 'cost'=>$cost);

    return $ret;
}

/**
 *  Check user is translator
 *
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $from_language Language to translate from
 * @param string $to_language Language to translate into
 *
 * @return bool (true if translator)
 */
function wpml_check_user_is_translator($from_language, $to_language) {
    global $wpdb;

    $is_translator = false;

    if($current_user_id = get_current_user_id()){
        $translation_languages = $wpdb->get_row($wpdb->prepare("SELECT meta_value
                                                                FROM {$wpdb->usermeta}
                                                                WHERE user_id = %d
                                                                  AND meta_key = %s",
                                                               $current_user_id,
                                                               $wpdb->prefix . 'language_pairs'));
        if($translation_languages){
        foreach (unserialize($translation_languages->meta_value) as $key => $language) {
            if ($key == $from_language) {
                $is_translator = array_key_exists($to_language, $language);
            }
        }
    }
    }

    return $is_translator;
}

/**
 *  Check user is translator
 *
 * @since      1.3
 * @package    WPML
 * @subpackage WPML API
 *
 * @param int         $post_id          Post ID
 * @param             $cred_form_id
 * @param bool|string $current_language (optional) current language
 *
 * @internal   param int $form_id Form ID
 * @return bool (true if translator)
 */
function wpml_generate_controls($post_id, $cred_form_id , $current_language = false) {
    global $sitepress,$sitepress_settings;

    if (!$current_language)
        $current_language = $sitepress->get_default_language();

    if($current_language != $sitepress->get_language_for_element($post_id, 'post_' . get_post_type($post_id)))
        $current_language = $sitepress->get_language_for_element($post_id, 'post_' . get_post_type($post_id));

    $controls = array();

    $trid = $sitepress->get_element_trid($post_id,'post_' . get_post_type($post_id));
    $translations = $sitepress->get_element_translations($trid, 'post_' . get_post_type($post_id));

    foreach ($sitepress->get_active_languages() as $active_language) {
        if ($current_language == $active_language['code'] || !wpml_check_user_is_translator($current_language, $active_language['code']))
            continue;

        if (array_key_exists($active_language['code'], $translations)) {
            //edit translation
            $controls[$active_language['code']]['action'] = 'edit';
            $post_url = get_permalink($translations[$active_language['code']]->element_id);
            if(false===strpos($post_url,'?') || (false===strpos($post_url,'?') && $sitepress_settings['language_negotiation_type'] != '3')){
                $controls[$active_language['code']]['url'] = $post_url.'?action=edit_translation&cred-edit-form='.$cred_form_id; //CRED edit form ID
            }else{
                $controls[$active_language['code']]['url'] = $post_url.'&action=edit_translation&cred-edit-form='.$cred_form_id; //CRED edit form ID
            }
        } else {
            //add translation
            $controls[$active_language['code']]['action'] = 'create';
            $post_url = get_permalink($post_id);
            if(false===strpos($post_url,'?') || (false===strpos($post_url,'?') && $sitepress_settings['language_negotiation_type'] != '3')){
                $controls[$active_language['code']]['url'] = get_permalink($post_id).'?action=create_translation&trid='.$trid.'&to_lang='.$active_language['code'].'&source_lang='.$current_language.'&cred-edit-form='.$cred_form_id; //CRED new form ID
            }else{
                $controls[$active_language['code']]['url'] = get_permalink($post_id).'&action=create_translation&trid='.$trid.'&to_lang='.$active_language['code'].'&source_lang='.$current_language.'&cred-edit-form='.$cred_form_id; //CRED new form ID
            }
        }

        $controls[$active_language['code']]['language'] = $sitepress->get_display_language_name($active_language['code'], $current_language);
        $controls[$active_language['code']]['flag'] = $sitepress->get_flag_url($active_language['code']);
    }

    return $controls;
}

/**
 *  Get original content
 *
 * @since      1.3
 * @package    WPML
 * @subpackage WPML API
 *
 * @param int    $post_id Post ID
 * @param string $field   Post field
 *
 * @param bool   $field_name
 *
 * @return string or array
 */
function wpml_get_original_content($post_id, $field, $field_name = false) {

    $post = get_post($post_id);
    switch ($field) {
        case 'title':
            return $post->post_title;
            break;
        case 'content':
            return $post->post_content;
            break;
        case 'excerpt':
            return $post->post_excerpt;
            break;
        case 'categories':
            $terms =  get_the_terms($post->ID, 'category');
            $taxs = array();
            if($terms)
            foreach ($terms as $term) {
                $taxs[$term->term_taxonomy_id] = $term->name;
            }
            return $taxs;
            break;
        case 'tags':
            $terms = get_the_terms($post->ID, 'post_tag');
            $taxs = array();
            if($terms)
            foreach ($terms as $term) {
                $taxs[$term->term_taxonomy_id] = $term->name;
            }
            return $taxs;
            break;
        case 'taxonomies':
            return wpml_get_synchronizing_taxonomies($post_id,$field_name);
            break;
        case 'custom_fields':
            return wpml_get_synchronizing_fields($post_id,$field_name);
            break;
        default:
            break;
    }

    return WPML_API_ERROR;
}

/**
 *  Get synchronizing taxonomies
 *
 * @since      1.3
 * @package    WPML
 * @subpackage WPML API
 *
 * @param int $post_id Post ID
 *
 * @param     $tax_name
 *
 * @return array
 */
function wpml_get_synchronizing_taxonomies($post_id,$tax_name) {
    global $wpdb, $sitepress_settings;
    $taxs = array();
    // get custom taxonomies
    if (!empty($post_id)) {
        $taxonomies = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT tx.taxonomy 
                FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->term_relationships} tr ON tx.term_taxonomy_id = tr.term_taxonomy_id
                WHERE tr.object_id = %d
            ", $post_id));

        sort($taxonomies, SORT_STRING);

        foreach ($taxonomies as $t) {
            if ($tax_name == $t && @intval($sitepress_settings['taxonomies_sync_option'][$t]) == 1) {
                foreach (wp_get_object_terms($post_id, $t) as $trm) {
                    $taxs[$t][$trm->term_taxonomy_id] = $trm->name;
                }
            }
        }
}
    return $taxs;
}

/**
 *  Get synchronizing fields
 *
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param int $post_id Post ID
 * @param string $field_name Field name
 * @return array
 */
function wpml_get_synchronizing_fields($post_id,$field_name) {
    global $sitepress_settings;
    $custom_fields_values = array();
    if (!empty($post_id)) {

        if (is_array($sitepress_settings['translation-management']['custom_fields_translation'])) {

            foreach ($sitepress_settings['translation-management']['custom_fields_translation'] as $cf => $op) {

                if ($cf == $field_name && ($op == '2' || $op == '1')) {
                    $values = get_post_meta($post_id, $cf, false);
                    if (!empty($values)){
                        foreach ($values as $key=>$value) {
                            $custom_fields_values[$key] = $value;
                        }
                    }
                }
            }

        }
    }
    return $custom_fields_values;
}