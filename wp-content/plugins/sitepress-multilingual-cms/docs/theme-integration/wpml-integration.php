<?php 

// HOME URL
// USAGE: replace references to the blog home url such as:
// - get_option('home')
// - bloginfo('home')
// - bloginfo('url')
// - get_bloginfo('url')
// - etc...
// with wpml_get_home_url()
// * IMPORTANT: Most themes also add a trailing slash (/) to the URL. This function already includes it, so don't add the slash when using it.
function wpml_get_home_url(){
    if(function_exists('icl_get_home_url')){
        return icl_get_home_url();
    }else{
        return rtrim(get_bloginfo('url') , '/') . '/';
    }
}



// LANGUAGE SELECTOR
// USAGE place this on the single.php, page.php, index.php etc... - inside the loop
// function wpml_content_languages($args)
// args: skip_missing, before, after
// defaults: skip_missing = 1, before =  __('This post is also available in: '), after = ''
function wpml_content_languages($args=''){
    parse_str($args);
    if(function_exists('icl_get_languages')){
        $languages = icl_get_languages($args);
        if(1 < count($languages)){
            echo isset($before) ? esc_html( $before ) : esc_html__('This post is also available in: ', 'sitepress');
            foreach($languages as $l){
                if(!$l['active']) $langs[] = '<a href="'.$l['url'].'">'.$l['translated_name'].'</a>';
            }
            echo join(', ', $langs);
            echo isset($after) ? esc_html( $after ) : '';
        }    
    }
} 


// LINKS TO SPECIFIC ELEMENTS
// USAGE
// args: $element_id, $element_type='post', $link_text='', $optional_parameters=array(), $anchor='', $echoit = true
function wpml_link_to_element($element_id, $element_type='post', $link_text='', $optional_parameters=array(), $anchor='', $echoit = true){
    if(!function_exists('icl_link_to_element')){    
        switch($element_type){
            case 'post':
            case 'page':
                $ret = '<a href="'. esc_url( get_permalink($element_id) ) .'">';
                if($anchor){
                    $ret .= esc_html( $anchor );
                }else{
                    $ret .= esc_html( get_the_title($element_id) );
                }
                $ret .= '<a>'; 
                break;
            case 'tag':
            case 'post_tag':
                $tag = get_term_by('id', $element_id, 'tag', ARRAY_A);
                $ret = '<a href="'.esc_url( get_tag_link($element_id) ) .'">' . esc_html( $tag->name ) . '</a>';
                break;
            case 'category':
                $ret = '<a href="'.esc_url( get_tag_link($element_id) ) .'">' . esc_html( get_the_category_by_ID($element_id) ) . '</a>';
                break;
            default: $ret = '';
        }
        if($echoit){
            echo $ret;
        }else{
            return $ret;
        }        
    }else{
        return icl_link_to_element($element_id, $element_type, $link_text, $optional_parameters, $anchor, $echoit);
    }        
}

// Languages links to display in the footer
//
function wpml_languages_list($skip_missing=0, $div_id = "footer_language_list"){
    if(function_exists('icl_get_languages')){
        $languages = icl_get_languages('skip_missing='.intval($skip_missing));
        if(!empty($languages)){
            echo '<div id="'. esc_attr( $div_id ) .'"><ul>';
            foreach($languages as $l){
                echo '<li>';
                if(!$l['active']) echo '<a href="'. esc_url(  $l['url'] ) .'">';
                echo '<img src="'. esc_url(  $l['country_flag_url'] ) .'" alt="'. esc_attr(  $l['language_code'] ) .'" />';
                if(!$l['active']) echo '</a>';
                if(!$l['active']) echo '<a href="'. esc_url(  $l['url'] ) .'">';
                echo $l['native_name'];
                if(!$l['active']) echo ' ('. esc_attr(  $l['translated_name'] ) .')';
                if(!$l['active']) echo '</a>';
                echo '</li>';
            }
            echo '</ul></div>';
        }
    }
}

function wpml_languages_selector(){
    do_action('icl_language_selector');    
}

function wpml_t($context, $name, $original_value){
    if(function_exists('icl_t')){
        return icl_t($context, $name, $original_value);
    }else{
        return $original_value;
    }
}

function wpml_register_string($context, $name, $value){
    if(function_exists('icl_register_string') && trim($value)){
        icl_register_string($context, $name, $value);
    }    
}

function wpml_get_object_id($element_id, $element_type='post', $return_original_if_missing=false, $ulanguage_code=null){
    if(function_exists('icl_object_id')){
        return icl_object_id($element_id, $element_type, $return_original_if_missing, $ulanguage_code);
    }else{
        return $element_id;
    }    
}

function wpml_default_link($anchor){
    global $sitepress;
    $qv = false;
    
    if(is_single()){
        $qv = 'p=' . get_the_ID();
    }elseif(is_page()){
        $qv = 'page_id=' . get_the_ID();
    }elseif(is_tag()){
        $tag = &get_term(intval( get_query_var('tag_id') ), 'post_tag', OBJECT, 'display');        
        $qv = 'tag=' . $tag->slug;
    }elseif(is_category()){        
        $qv = 'cat=' . get_query_var('cat');
    }elseif(is_year()){        
        $qv = 'year=' . get_query_var('year');
    }elseif(is_month()){        
        $qv = 'm=' . get_query_var('year') . sprintf('%02d', get_query_var('monthnum'));
    }elseif(is_day()){        
        $qv = 'm=' . get_query_var('year') . sprintf('%02d', get_query_var('monthnum')) . sprintf('%02d', get_query_var('day'));
    }elseif(is_search()){        
        $qv = 's=' . get_query_var('s');
    }elseif(is_tax()){
        $qv = get_query_var('taxonomy') . '=' . get_query_var('term');        
    }
    
    if(false !== strpos(wpml_get_home_url(),'?')){
        $url_glue = '&';
    }else{
        $url_glue = '?';
    }
    
    if($qv){
        $link = '<a href="' .  $sitepress->language_url($sitepress->get_default_language()) . $url_glue . $qv . '" rel="nofollow">' . esc_html($anchor) . '</a>';
    }else{
        $link = '';
    } 

    return $link;
}

?>