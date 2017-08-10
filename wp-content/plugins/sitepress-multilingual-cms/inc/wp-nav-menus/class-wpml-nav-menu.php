<?php
require ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/nav-menu-translation/wpml-nav-menu-actions.class.php';

class WPML_Nav_Menu {
    private $current_menu;
    private $current_lang;

	/** @var  WPML_Term_Translation $term_translations */
	protected $term_translations;

	/** @var  WPML_Post_Translation $post_translations */
	protected $post_translations;

	/** @var SitePress $sitepress */
	protected $sitepress;

	/** @var WPDB $wpdb */
	public $wpdb;

    /** @var  WPML_Nav_Menu_Actions $nav_menu_actions */
    public $nav_menu_actions;

	function __construct( $sitepress, $wpdb, $post_translations, $term_translations ) {
		$this->sitepress         = $sitepress;
		$this->wpdb              = $wpdb;
		$this->post_translations = $post_translations;
		$this->term_translations = $term_translations;
		$this->nav_menu_actions  = new WPML_Nav_Menu_Actions( $sitepress,
		                                                     $wpdb,
		                                                     $post_translations,
		                                                     $term_translations );
	}

	public function init_hooks() {
		global $pagenow;

		if ( is_admin() ) {
			// filter for nav_menu_options
			add_filter( 'option_nav_menu_options', array( $this, 'option_nav_menu_options' ) );
			add_filter( 'wp_get_nav_menus', array( $this, 'wp_get_nav_menus_filter' ) );
		}

		// filter menus by language - also on the widgets page
		if ( 'nav-menus.php' === $pagenow
		     || 'widgets.php' === $pagenow
		     || ( isset( $_POST['action'] ) && 'save-widget' === $_POST['action'] )
		) {
			add_filter( 'get_terms', array( $this, 'get_terms_filter' ), 1, 3 );
		}

		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'wp_nav_menu_args', array( $this, 'wp_nav_menu_args_filter' ) );
		add_filter( 'wp_nav_menu_items', array( $this, 'wp_nav_menu_items_filter' ) );
		add_filter( 'nav_menu_meta_box_object', array( $this, '_enable_sitepress_query_filters' ) );
	}
    
    function init(){
        /** @var WPML_Request $wpml_request_handler */
	    /** @var WPML_Language_Resolution $wpml_language_resolution */
        global $sitepress, $sitepress_settings, $pagenow, $wpml_request_handler, $wpml_language_resolution;

		$this->adjust_current_language_if_required();
		
		$default_language = $sitepress->get_default_language();

        // add language controls for menus no option but javascript
        if($pagenow === 'nav-menus.php'){
            add_action('admin_footer', array($this, 'nav_menu_language_controls'), 10);
            
            wp_enqueue_script('wp_nav_menus', ICL_PLUGIN_URL . '/res/js/wp-nav-menus.js', ICL_SITEPRESS_VERSION, true);    
            wp_enqueue_style('wp_nav_menus_css', ICL_PLUGIN_URL . '/res/css/wp-nav-menus.css', array(), ICL_SITEPRESS_VERSION,'all');    
            
            // filter posts by language
            add_action('parse_query', array($this, 'parse_query'));
        }
        
        if(is_admin()){
            $this->_set_menus_language();
            $this->get_current_menu();
        }

        if ( isset( $_POST[ 'action' ] )
             && $_POST[ 'action' ] === 'menu-get-metabox'
             && (bool) ( $lang = $wpml_language_resolution->get_referrer_language_code() ) !== false
        ) {
            $sitepress->switch_lang ( $lang );
        }

        if ( isset( $this->current_menu[ 'language' ] )
             && isset( $this->current_menu[ 'id' ] )
             && $this->current_menu[ 'id' ]
             && $this->current_menu[ 'language' ]
             && $this->current_menu[ 'language' ] != $default_language
             && isset( $_GET[ 'menu' ] )
             && empty( $_GET[ 'lang' ] )
        ) {
            wp_redirect(
                admin_url(
                    sprintf(
                        'nav-menus.php?menu=%d&lang=%s',
                        $this->current_menu[ 'id' ],
                        $this->current_menu[ 'language' ]
                    )
                )
            );
        }
        
		$this->current_lang = $wpml_request_handler->get_requested_lang();

        if(isset($_POST['icl_wp_nav_menu_ajax'])){
            $this->ajax($_POST);
        }

        // for theme locations that are not translated into the current language
        // reflect status in the theme location navigation switcher
        add_action('admin_footer', array($this, '_set_custom_status_in_theme_location_switcher'));
        
        // filter menu by language when adjust ids is off
        // not on ajax calls
        if(!$sitepress_settings['auto_adjust_ids'] && !defined('DOING_AJAX')){
            add_filter('get_term', array($sitepress, 'get_term_adjust_id'), 1, 1);
        }
	    $this->setup_menu_item();

	    if ( $this->sitepress->get_wp_api()->is_core_page( 'menu-sync/menus-sync.php' ) ) {
		    $this->setup_menu_synchronization();
	    }

		add_action( 'wp_ajax_icl_msync_confirm', array( $this, 'sync_menus_via_ajax' ) );
	    add_action( 'wp_ajax_wpml_get_links_for_menu_strings_translation', array( $this, 'get_links_for_menu_strings_translation_ajax' ) );
    }

	function sync_menus_via_ajax( ) {
		if ( isset( $_POST[ '_icl_nonce_menu_sync' ] ) && wp_verify_nonce( $_POST[ '_icl_nonce_menu_sync' ], '_icl_nonce_menu_sync' ) ) {
			
			if (!session_id()) {
				session_start();
			}
			
			global $icl_menus_sync,$wpdb, $wpml_post_translations, $wpml_term_translations, $sitepress;
			include_once ICL_PLUGIN_PATH . '/inc/wp-nav-menus/menus-sync.php';
			$icl_menus_sync = new ICLMenusSync( $sitepress, $wpdb, $wpml_post_translations, $wpml_term_translations );
			$icl_menus_sync->init( isset( $_SESSION[ 'wpml_menu_sync_menu' ] ) ? $_SESSION[ 'wpml_menu_sync_menu' ] : null );
			$results = $icl_menus_sync->do_sync( $_POST['sync']);
			$_SESSION[ 'wpml_menu_sync_menu' ] = $results;
			$_SESSION[ 'wpml_menu_sync_menu' ] = $results;
			wp_send_json_success( true );
		} else {
			wp_send_json_error( false );
		}
	}

	public function get_links_for_menu_strings_translation_ajax() {
		global $icl_menus_sync, $wpml_post_translations, $wpml_term_translations;
		include_once ICL_PLUGIN_PATH . '/inc/wp-nav-menus/menus-sync.php';
		$icl_menus_sync = new ICLMenusSync( $this->sitepress, $this->wpdb, $wpml_post_translations, $wpml_term_translations );
		wp_send_json_success( $icl_menus_sync->get_links_for_menu_strings_translation() );
	}

	// Menus sync submenu
    function admin_menu_setup(){
		global $sitepress;
		if(!isset($sitepress) || !$sitepress->get_setting( 'setup_complete' )) return;

		$top_page = apply_filters('icl_menu_main_page', ICL_PLUGIN_FOLDER.'/menu/languages.php');
        add_submenu_page( $top_page, 
            __( 'WP Menus Sync', 'sitepress' ), __( 'WP Menus Sync', 'sitepress' ), 
            'wpml_manage_wp_menus_sync', ICL_PLUGIN_FOLDER . '/menu/menu-sync/menus-sync.php' );
    }

	/**
	 *
	 * Associates menus without language information with default language
	 *
	 */
	private function _set_menus_language() {
		global $wpdb, $sitepress;

		$default_language   = $sitepress->get_default_language ();
		$untranslated_menus = $wpdb->get_col (
												"
									            SELECT term_taxonomy_id
									            FROM {$wpdb->term_taxonomy} tt
									            LEFT JOIN {$wpdb->prefix}icl_translations i
									              ON CONCAT('tax_', tt.taxonomy ) = i.element_type
									                AND i.element_id = tt.term_taxonomy_id
									            WHERE tt.taxonomy='nav_menu'
									              AND i.language_code IS NULL"
		);
		foreach ( (array) $untranslated_menus as $item ) {
			$sitepress->set_element_language_details ( $item, 'tax_nav_menu', null, $default_language );
		}
		$untranslated_menu_items = $wpdb->get_col (
													"
										            SELECT p.ID
										            FROM {$wpdb->posts} p
										            LEFT JOIN {$wpdb->prefix}icl_translations i
										              ON CONCAT('post_', p.post_type )  = i.element_type
										                AND i.element_id = p.ID
										            WHERE p.post_type = 'nav_menu_item'
										              AND i.language_code IS NULL"
		);
		if ( !empty( $untranslated_menu_items ) ) {
			foreach ( $untranslated_menu_items as $item ) {
				$sitepress->set_element_language_details ( $item, 'post_nav_menu_item', null, $default_language );
			}
		}
	}

	function ajax( $data ) {
		if ( $data['icl_wp_nav_menu_ajax'] == 'translation_of' ) {
			$trid = isset( $data['trid'] ) ? $data['trid'] : false;
			echo $this->render_translation_of( $data['lang'], $trid );
		}
		exit;
	}
    
    function _get_menu_language($menu_id){
        /** @var WPML_Term_Translation $wpml_term_translations */
        global $wpml_term_translations;

        return $menu_id ? $wpml_term_translations->lang_code_by_termid($menu_id) : false;
    }

	/**
	 *
	 * Gets first menu in a specific language
	 * used to override nav_menu_recently_edited when a different language is selected
	 *
	 * @param string $lang
	 * @return int
	 */
	function _get_first_menu( $lang ) {
        global $wpdb;
        $menu_tt_id = $wpdb->get_var("SELECT MIN(element_id) FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_nav_menu' AND language_code='".esc_sql($lang)."'");    

        return $menu_tt_id
            ? (int)$wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d",$menu_tt_id))
            : false;
    }
    
    function get_current_menu(){
        global $sitepress, $wpml_request_handler;
        $nav_menu_recently_edited      = get_user_option ( 'nav_menu_recently_edited' );
        $nav_menu_recently_edited_lang = $this->_get_menu_language ( $nav_menu_recently_edited );
        $current_language              = $sitepress->get_current_language ();
		$admin_language_cookie = $wpml_request_handler->get_cookie_lang();
		if( !isset( $_REQUEST['menu'] ) && $nav_menu_recently_edited_lang != $current_language ){
            // if no menu is specified and the language is set override nav_menu_recently_edited
            $nav_menu_selected_id = $this->_get_first_menu( $current_language );
            if($nav_menu_selected_id){
                update_user_option(get_current_user_id(), 'nav_menu_recently_edited', $nav_menu_selected_id);    
            }else{
                $_REQUEST['menu'] = 0;
            }
            
        }elseif( !isset( $_REQUEST['menu'] ) && !isset($_GET['lang']) 
                && (empty($nav_menu_recently_edited_lang) || $nav_menu_recently_edited_lang != $admin_language_cookie )
                && (empty($_POST['action']) || $_POST['action']!='update')){
            // if no menu is specified, no language is set, override nav_menu_recently_edited if its language is different than default           
            $nav_menu_selected_id = $this->_get_first_menu( $current_language );
            update_user_option(get_current_user_id(), 'nav_menu_recently_edited', $nav_menu_selected_id);
        }elseif(isset( $_REQUEST['menu'] )){
            $nav_menu_selected_id = $_REQUEST['menu'];
        }else{
            $nav_menu_selected_id = $nav_menu_recently_edited;            
        }
        
        $this->current_menu['id'] = $nav_menu_selected_id;        
        if($this->current_menu['id']){
            $this->_load_menu($this->current_menu['id']);
        }else{
	        $this->current_menu['trid'] = isset( $_GET['trid'] ) ? (int) $_GET['trid'] : null;
            if(isset($_POST['icl_nav_menu_language'])){
                $this->current_menu['language'] = $_POST['icl_nav_menu_language'];
            }elseif(isset($_GET['lang'])){
	            $this->current_menu['language'] = (int) $_GET['lang'];    
            }else{
                $this->current_menu['language'] = $admin_language_cookie;
            }            
            $this->current_menu['translations'] = array();
        }    
    }

	/**
	 * @param bool|int $menu_id
	 *
	 * @return array
	 */
	function _load_menu( $menu_id = false ) {
		$menu_id          = $menu_id ? $menu_id : $this->current_menu['id'];
		$menu_term_object = get_term( $menu_id, 'nav_menu' );
		if ( ! empty( $menu_term_object->term_taxonomy_id ) ) {
			$ttid                         = $menu_term_object->term_taxonomy_id;
			$current_menu                 = array( 'id' => $menu_id );
			$current_menu['trid']         = $this->term_translations->get_element_trid( $ttid );
			$current_menu['translations'] = $current_menu['trid']
				? $this->sitepress->get_element_translations( $current_menu['trid'], 'tax_nav_menu' ) : array();
			$current_menu['language']     = $this->term_translations->lang_code_by_termid( $menu_id );
		}
		$this->current_menu = ! empty( $current_menu['translations'] ) ? $current_menu : null;

		return $this->current_menu;
	}

	function nav_menu_language_controls() {
		global $sitepress, $wpdb;
		$this->_load_menu();
		$default_language = $sitepress->get_default_language();
		$current_lang     = isset( $this->current_menu['language'] ) ? $this->current_menu['language'] : $sitepress->get_current_language();
        $langsel = '<br class="clear" />';    
        
        // show translations links if this is not a new element              
        if(isset($this->current_menu['id']) && $this->current_menu['id']){
            $langsel .= '<div class="howto icl_nav_menu_text" style="float:right;">';    
            $langsel .= __('Translations:', 'sitepress');                
            foreach($sitepress->get_active_languages() as $lang){            
                if ( ! isset( $this->current_menu[ 'language' ] )
                     || $lang[ 'code' ] == $this->current_menu[ 'language' ] ) {
                    continue;
                }
                if(isset($this->current_menu['translations'][$lang['code']])){
                    $lang_suff = '&lang=' . $lang['code'];
                    $menu_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d",$this->current_menu['translations'][$lang['code']]->element_id));
                    $tr_link = '<a style="text-decoration:none" title="'. esc_attr(__('edit translation', 'sitepress')).'" href="'.admin_url('nav-menus.php').
                        '?menu='.$menu_id. $lang_suff .'">'.
                        $lang['display_name'] . '&nbsp;<img src="'.ICL_PLUGIN_URL.'/res/img/edit_translation.png" alt="'. esc_attr(__('edit', 'sitepress')).
                        '" width="12" height="12" /></a>';
                }else{
                    $tr_link = '<a style="text-decoration:none" title="'. esc_attr(__('add translation', 'sitepress')).'" href="'.admin_url('nav-menus.php').
                        '?action=edit&menu=0&trid='.$this->current_menu['trid'].'&lang='.$lang['code'].'">'. 
                        $lang['display_name'] . '&nbsp;<img src="'.ICL_PLUGIN_URL.'/res/img/add_translation.png" alt="'. esc_attr(__('add', 'sitepress')).
                        '" width="12" height="12" /></a>';
                }
                $trs[] = $tr_link ;
            }
            $langsel .= '&nbsp;';
						if (isset($trs)) {
							$langsel .= join (', ', $trs);
						}
            $langsel .= '</div><br />';    
            $langsel .= '<div class="howto icl_nav_menu_text" style="float:right;">';    
            $langsel .= '<div><a href="'.admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/menu-sync/menus-sync.php').'">' . __('Synchronize menus between languages.', 'sitepress') . '</a></div>';
            $langsel .= '</div>';    
            
        }
        
        // show languages dropdown                
        $langsel .= '<label class="menu-name-label howto"><span>' . __('Language', 'sitepress') . '</span>';
        $langsel .= '&nbsp;&nbsp;';          
        $langsel .= '<select name="icl_nav_menu_language" id="icl_menu_language">';    
        foreach($sitepress->get_active_languages() as $lang){
            if(isset($this->current_menu['translations'][$lang['code']]) && $this->current_menu['language'] != $lang['code']) continue;            
            if(isset($this->current_menu['language']) && $this->current_menu['language']){
                $selected = $lang['code'] == $this->current_menu['language'] ? ' selected="selected"' : '';    
            }else{
                $selected = $lang['code'] == $sitepress->get_current_language() ? ' selected="selected"' : '';
            }
            $langsel .= '<option value="' . $lang['code'] . '"' . $selected . '>' . $lang['display_name'] . '</option>';    
        }
        $langsel .= '</select>';
        $langsel .= '</label>';

		if ( $current_lang !== $default_language ) {
			// show 'translation of' if this element is not in the default language and there are untranslated elements
			$langsel .= '<span id="icl_translation_of_wrap">';
			$trid_current = ! empty( $this->current_menu['trid'] ) ? $this->current_menu['trid'] : ( isset( $_GET['trid'] ) ? $_GET['trid'] : 0 );
			$langsel .= $this->render_translation_of( $current_lang, (int)$trid_current );
			$langsel .= '</span>';
		}
		$langsel .= '</span>';
        
        // add trid to form
        if($this->current_menu['trid']){
            $langsel .= '<input type="hidden" id="icl_nav_menu_trid" name="icl_nav_menu_trid" value="' . $this->current_menu['trid'] . '" />';
        }

		$langsel .= '';

		echo $this->render_button_language_switcher_settings();
        ?>
        <script type="text/javascript">
        addLoadEvent(function(){
			var update_menu_form = jQuery('#update-nav-menu');
			update_menu_form.find('.publishing-action:first').before('<?php echo addslashes_gpc($langsel); ?>');
            jQuery('#side-sortables').before('<?php $this->languages_menu() ?>');
            <?php if($this->current_lang != $default_language): echo "\n"; ?>
				jQuery('.nav-tabs .nav-tab').each(function(){
					jQuery(this).attr('href', jQuery(this).attr('href')+'&lang=<?php echo $this->current_lang ?>');
				});        
				var original_action = update_menu_form.attr('ACTION') ? update_menu_form.attr('ACTION') : '';
				update_menu_form.attr('ACTION', original_action+'?lang=<?php echo $this->current_lang ?>');
            <?php endif; ?>
			WPML_core.wp_nav_align_inputs();

        });
        </script>
        <?php            
    }

	function get_menus_without_translation( $lang, $trid = 0 ) {
		$res_query          = "
                SELECT ts.element_id, ts.trid, t.name
		        FROM {$this->wpdb->prefix}icl_translations ts
		        JOIN {$this->wpdb->term_taxonomy} tx ON ts.element_id = tx.term_taxonomy_id
		        JOIN {$this->wpdb->terms} t ON tx.term_id = t.term_id
		        LEFT JOIN {$this->wpdb->prefix}icl_translations mo
		            ON mo.trid = ts.trid
		            	AND mo.language_code = %s
		        WHERE ts.element_type='tax_nav_menu'
	                AND ts.language_code != %s
		            AND ts.source_language_code IS NULL
		            AND tx.taxonomy = 'nav_menu'
		            AND ( mo.element_id IS NULL OR ts.trid = %d )
		    ";
		$res_query_prepared = $this->wpdb->prepare( $res_query, $lang, $lang, $trid );
		$res                = $this->wpdb->get_results( $res_query_prepared );
		$menus              = array();
		foreach ( $res as $row ) {
			$menus[ $row->trid ] = $row;
		}

		return $menus;
	}

	private function render_translation_of( $lang, $trid = false ) {
		global $sitepress;
		$out = '';

		if ( $sitepress->get_default_language() != $lang ) {
			$menus    = $this->get_menus_without_translation( $lang, (int) $trid );
			$disabled = empty( $this->current_menu['id'] ) && isset( $_GET['trid'] ) ? ' disabled="disabled"' : '';
			$out .= '<label class="menu-name-label howto"><span>' . __( 'Translation of', 'sitepress' ) . '</span>&nbsp;';
			$out .= '<select name="icl_translation_of" id="icl_menu_translation_of"' . $disabled . '>';
			$out .= '<option value="none">--' . __( 'none', 'sitepress' ) . '--</option>';
			foreach ( $menus as $mtrid => $m ) {
				if ( (int) $trid === (int) $mtrid ) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
				$out .= '<option value="' . $m->element_id . '"' . $selected . '>' . $m->name . '</option>';
			}
			$out .= '</select>';
			$out .= '</label>';
			if ( $disabled !== '' ) {
				$out .= '<input type="hidden" name="icl_nav_menu_trid" value="' . (int)$_GET['trid'] . '"/>';
			}
		}

		return $out;
	}

	private function render_button_language_switcher_settings() {
		/* @var WPML_Language_Switcher $wpml_language_switcher */
		global $wpml_language_switcher;

		$output            = '';
		$default_lang      = $this->sitepress->get_default_language();
		$default_lang_menu = isset( $this->current_menu['translations'][ $default_lang ] )
			? $this->current_menu['translations'][ $default_lang ] : null;

		if ( $default_lang_menu && isset( $default_lang_menu->element_id ) ) {
			$output = '<div id="wpml-ls-menu-management" style="display:none;">';
			$output .= $wpml_language_switcher->get_button_to_edit_slot( 'menus', $default_lang_menu->element_id );
			$output .= '</div>';
		}

		return $output;
	}
    
    function get_menus_by_language(){
        global $wpdb, $sitepress;
        $langs = array();
				$res_query = "
            SELECT lt.name AS language_name, l.code AS lang, COUNT(ts.translation_id) AS c
            FROM {$wpdb->prefix}icl_languages l
                JOIN {$wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code
                JOIN {$wpdb->prefix}icl_translations ts ON l.code = ts.language_code            
            WHERE lt.display_language_code=%s
                AND l.active = 1
                AND ts.element_type = 'tax_nav_menu'
            GROUP BY ts.language_code
            ORDER BY major DESC, english_name ASC
        ";
				$admin_language = $sitepress->get_admin_language();
				$res_query_prepared = $wpdb->prepare($res_query, $admin_language);
        $res = $wpdb->get_results($res_query_prepared);
        foreach($res as $row){
            $langs[$row->lang] = $row;
        }        
        return $langs;
    }

	function languages_menu( $echo = true ) {
		global $sitepress;
		$langs = $this->get_menus_by_language();

		// include empty languages
		foreach ( $sitepress->get_active_languages() as $lang ) {
			if ( ! isset( $langs[ $lang[ 'code' ] ] ) ) {
				$langs[ $lang[ 'code' ] ]                = new stdClass();
				$langs[ $lang[ 'code' ] ]->language_name = $lang[ 'display_name' ];
				$langs[ $lang[ 'code' ] ]->lang          = $lang[ 'code' ];
			}
		}
		$url = admin_url( 'nav-menus.php' );
		$ls  = array();
		foreach ( $langs as $l ) {
			$class        = $l->lang == $this->current_lang ? ' class="current"' : '';
			$url_suffix      = '?lang=' . $l->lang;
			$count_string = isset( $l->c ) && $l->c > 0 ? ' (' . $l->c . ')' : '';
			$ls[ ]        = '<a href="' . $url . $url_suffix . '"' . $class . '>' . esc_html( $l->language_name ) . $count_string . '</a>';
		}
		$ls_string = '<div class="icl_lang_menu icl_nav_menu_text">';
		$ls_string .= join( '&nbsp;|&nbsp;', $ls );
		$ls_string .= '</div>';
		if ( $echo ) {
			echo $ls_string;
		}

		return $ls_string;
	}
    
    function get_terms_filter($terms, $taxonomies, $args){
        global $wpdb, $sitepress, $pagenow;        
        // deal with the case of not translated taxonomies
        // we'll assume that it's called as just a single item
	    if ( ! $sitepress->is_translated_taxonomy( $taxonomies[0] ) && 'nav_menu' !== $taxonomies[0] ) {
            return $terms;
        }      
        
        // special case for determining list of menus for updating auto-add option
	    if ( 'nav-menus.php' === $pagenow
	         && array_key_exists( 'fields', $args )
	         && array_key_exists( 'action', $_POST )
	         && 'nav_menu' === $taxonomies[0]
	         && 'ids' === $args['fields']
	         && 'update' === $_POST['action']
	    ) {
            return $terms;
        }
          
        if(!empty($terms)){
            $txs = array();
            foreach($taxonomies as $t){
                $txs[] = 'tax_' . $t;
            }
            $el_types = wpml_prepare_in( $txs );
            
            // get all term_taxonomy_id's
            $tt = array();
            foreach($terms as $t){
                if(is_object($t)){
                    $tt[] = $t->term_taxonomy_id;    
                }else{
                    if(is_numeric($t)){
                        $tt[] = $t;    
                    }
                }
            }
            
            // filter the ones in the current language
            $ftt = array();
            if(!empty($tt)){
                $ftt = $wpdb->get_col(
                        $wpdb->prepare("
                            SELECT element_id
                            FROM {$wpdb->prefix}icl_translations
                            WHERE element_type IN ({$el_types})
                              AND element_id IN (" . wpml_prepare_in($tt, '%d') . ")
                              AND language_code=%s", $this->current_lang )
                );
            }

            foreach($terms as $k=>$v){
                if(isset($v->term_taxonomy_id) && !in_array($v->term_taxonomy_id, $ftt)){
                    unset($terms[$k]);
                }
            }
        }                
        return  array_values($terms);        
    }
    
    // filter posts by language    
    function parse_query($q){
        global $sitepress;
        // not filtering nav_menu_item
        if($q->query_vars['post_type'] == 'nav_menu_item'){
            return $q;
        } 
        
        // also - not filtering custom posts that are not translated
        if($sitepress->is_translated_post_type($q->query_vars['post_type'])){
            $q->query_vars['suppress_filters'] = 0;
        }
        
        return $q;
    }

    /**
     * @param mixed $val
     *
     * @return mixed
     */
    function option_nav_menu_options( $val ){
        global $wpdb, $sitepress;
        // special case of getting menus with auto-add only in a specific language
		$debug_backtrace = $sitepress->get_backtrace( 5 ); //Ignore objects and limit to first 5 stack frames, since 4 is the highest index we use

        if ( isset ( $debug_backtrace[4] ) && $debug_backtrace[4]['function'] === '_wp_auto_add_pages_to_menu' && ! empty( $val['auto_add'] ) ){
            $post_lang = isset( $_POST['icl_post_language'] ) ? filter_var( $_POST['icl_post_language'], FILTER_SANITIZE_STRING ) : false;
            $post_lang = ! $post_lang && isset( $_POST['lang'] ) ? filter_var( $_POST['lang'], FILTER_SANITIZE_STRING ) : $post_lang;
            $post_lang = ! $post_lang && $this->is_duplication_mode() ? $sitepress->get_current_language() : $post_lang;

			if ( $post_lang ) {
				$val['auto_add'] = $wpdb->get_col( $wpdb->prepare( "
					SELECT element_id
					FROM {$wpdb->prefix}icl_translations
					WHERE element_type = 'tax_nav_menu'
						AND element_id IN ( " . wpml_prepare_in( $val['auto_add'], '%d' ) . " )
						AND language_code = %s", $post_lang ) );
			}
        }

        return $val;
    }

    /**
     * @return bool
     */
    private function is_duplication_mode() {
        return isset( $_POST['langs'] );
    }

	function wp_nav_menu_args_filter( $args ) {

		if ( ! $args[ 'menu' ] ) {
			$locations = get_nav_menu_locations();
			if ( isset( $args[ 'theme_location' ] ) && isset( $locations[ $args[ 'theme_location' ] ] ) ) {
				$args[ 'menu' ] = icl_object_id( $locations[ $args[ 'theme_location' ] ], 'nav_menu' );
			}
		};

		if ( ! $args[ 'menu' ] ) {
			remove_filter( 'theme_mod_nav_menu_locations', array( $this->nav_menu_actions, 'theme_mod_nav_menu_locations' ) );
			$locations = get_nav_menu_locations();
			if ( isset( $args[ 'theme_location' ] ) && isset( $locations[ $args[ 'theme_location' ] ] ) ) {
				$args[ 'menu' ] = icl_object_id( $locations[ $args[ 'theme_location' ] ], 'nav_menu' );
			}
			add_filter( 'theme_mod_nav_menu_locations', array( $this->nav_menu_actions, 'theme_mod_nav_menu_locations' ) );
		}

		// $args[ "menu" ] can be an object consequently to widget's call
		if ( is_object($args[ 'menu' ]) && ( ! empty( $args[ 'menu' ]->term_id )) ) {
				$args['menu'] = wp_get_nav_menu_object(icl_object_id($args['menu']->term_id, 'nav_menu'));
		}

		if ( ( ! is_object ( $args['menu'] )) && is_numeric ( $args['menu'] ) ) {
				$args[ 'menu' ] = wp_get_nav_menu_object( icl_object_id( $args[ 'menu' ], 'nav_menu' ) );
		}

		if ( ( ! is_object ( $args['menu'] )) && is_string ( $args["menu"] ) ) {
            $term = get_term_by( 'slug', $args[ 'menu' ], 'nav_menu' );
            if ( false === $term) {
                    $term = get_term_by( 'name', $args[ 'menu' ], 'nav_menu' );
            }

            if ( false !== $term ) {
                    $args['menu'] = wp_get_nav_menu_object(icl_object_id($term->term_id, 'nav_menu'));
            }
		}

		if ( ! is_object ( $args['menu'] ) ) {
				$args['menu'] = false;
		}

		return $args;
	}
    
    function wp_nav_menu_items_filter($items){
        $items = preg_replace(
            '|<li id="([^"]+)" class="menu-item menu-item-type-taxonomy"><a href="([^"]+)">([^@]+) @([^<]+)</a>|', 
            '<li id="$1" class="menu-item menu-item-type-taxonomy"><a href="$2">$3</a>', $items);
        return $items;
    }
    
    function _set_custom_status_in_theme_location_switcher(){
        global $sitepress_settings, $sitepress, $wpdb;

        if ( !$sitepress_settings ) {
            return;
        }
        $tl = (array)get_theme_mod('nav_menu_locations');
        $menus_not_translated = array();
        foreach($tl as $k=>$menu){
            $menu_tt_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",$menu));
            $menu_trid = $sitepress->get_element_trid($menu_tt_id, 'tax_nav_menu');
            $menu_translations = $sitepress->get_element_translations($menu_trid, 'tax_nav_menu');
            if(!isset($menu_translations[$this->current_lang]) || !$menu_translations[$this->current_lang]){
                $menus_not_translated[] = $k;                
            }
        }
        if(!empty($menus_not_translated)){
            ?>
            <script type="text/javascript">
            addLoadEvent(function(){
                <?php foreach($menus_not_translated as $menu_id): ?>
	            var menu_id = '<?php echo $menu_id?>';
	            var location_menu_id = jQuery('#locations-' + menu_id);
	            if(location_menu_id.length > 0){
                    location_menu_id.find('option').first().html('<?php echo esc_js(__('not translated in current language','sitepress')) ?>');
                    location_menu_id.css('font-style','italic');
                    location_menu_id.change(function(){if(jQuery(this).val()!=0) jQuery(this).css('font-style','normal');else jQuery(this).css('font-style','italic')});
                }
                <?php endforeach; ?>
            });            
            </script>
            <?php             
        }
    }
    
    // on the nav menus when selecting pages using the pagination filter pages > 2 by language    
    function _enable_sitepress_query_filters($args){
        if(isset($args->_default_query)){
            $args->_default_query['suppress_filters'] = false;    
        }
        return $args;
    }

	function wp_get_nav_menus_filter( $menus ) {
		global $pagenow;
		if ( is_admin () && isset( $pagenow ) && $pagenow === 'customize.php' ) {
			$menus = $this->unfilter_non_default_language_menus ( $menus );
		}

		return $menus;
	}

	private function setup_menu_item() {
		add_action( 'admin_menu', array( $this, 'admin_menu_setup' ) );
	}

	private function setup_menu_synchronization() {
		global $icl_menus_sync, $wpml_post_translations, $wpml_term_translations;
		include_once ICL_PLUGIN_PATH . '/inc/wp-nav-menus/menus-sync.php';
		$icl_menus_sync = new ICLMenusSync( $this->sitepress, $this->wpdb, $wpml_post_translations, $wpml_term_translations );
	}

	private function unfilter_non_default_language_menus( $menus ) {
		global $sitepress, $wpml_term_translations;
		$default_language = $sitepress->get_default_language ();

		foreach ( $menus as $index => $menu ) {
			$menu_ttid = is_object ( $menu ) ? $menu->term_taxonomy_id : $menu;
			/** @var WPML_Term_Translation $wpml_term_translations */
			$menu_language = $wpml_term_translations->get_element_lang_code ( $menu_ttid );
			if ( $menu_language != $default_language && $menu_language != null ) {
				unset( $menus[ $index ] );
			}
		}

		return $menus;
	}
	
	private function adjust_current_language_if_required( ) {
		global $pagenow;
		
		if ( $pagenow === 'nav-menus.php' && isset( $_GET[ 'menu' ] ) && $_GET[ 'menu' ] ) {
			$current_lang = $this->sitepress->get_current_language();
			$menu_lang    = $this->_get_menu_language( (int)$_GET[ 'menu' ] );
			if ( $menu_lang && ( $current_lang !== $menu_lang ) ) {
				$this->sitepress->switch_lang( $menu_lang );
				$_GET[ 'lang' ] = $menu_lang;
			}
		}
		
	}
} 

?>
