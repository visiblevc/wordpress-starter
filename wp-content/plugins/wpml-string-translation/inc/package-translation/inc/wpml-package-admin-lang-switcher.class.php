<?php

class WPML_Package_Admin_Lang_Switcher {
	
	private $package;
	private $args;
	
	public function __construct( $package, $args ) {
		$this->package = new WPML_Package( $package );
		$this->args = $args;
		add_action( 'wp_before_admin_bar_render', array( $this, 'admin_language_switcher' ) );
		add_action( 'wp_after_admin_bar_render', array( $this, 'add_meta_box' ) );
	}
	
	function admin_language_switcher() {
		global $wp_admin_bar, $wpdb, $sitepress;
		
        $parent = 'WPML_PACKAGE_LANG';

		$package_language = $this->package->get_package_language();
		if ( ! $package_language ) {
			$package_language = $sitepress->get_default_language();
		}
		
		$wpml_pt_meta = new WPML_Package_Translation_Metabox( $this->package, $wpdb, $sitepress, $this->args );
		$package_language_name = $wpml_pt_meta->get_package_language_name();
		$wp_admin_bar->add_menu( array(
									  'parent' => false, 'id' => $parent,
									  'title'  => '<img src="' . $sitepress->get_flag_url( $package_language ) . '"> ' . $package_language_name,
									  'href'   => '#'
									)
								);
	}
	
	function add_meta_box() {
		
		global $wpdb, $sitepress;

		$metabox = '<div id="wpml-package-admin-bar-popup" style="display:none;position:fixed;z-index:9002;width:200px;padding:10px;border: 1px solid #8CCEEA;background-color:#FFF">';
		
		$wpml_pt_meta = new WPML_Package_Translation_Metabox( $this->package, $wpdb, $sitepress, $this->args );
		$metabox .= $wpml_pt_meta->get_metabox();
		
		$metabox .= '</div>';
		
		$metabox .= $this->add_js();
		
		// This is required when a new package is created but it doesn't have any translated content yet.
		// https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-556
		WPML_Simple_Language_Selector::enqueue_scripts();
		
		echo $metabox;
	}
	
	function add_js() {
		ob_start( );
		
		?>
		
			<script type="text/javascript">
				var WPML_Package_Translation = WPML_Package_Translation || {};
				
				WPML_Package_Translation.AdminLanguageSwitcher = function () {
					var self = this;
					
					self.init = function () {
						self.link = jQuery('#wp-admin-bar-WPML_PACKAGE_LANG').find('.ab-item');
						self.link.hover(self.show_lang_box, self.hide_lang_box);
						self.popup = jQuery('#wpml-package-admin-bar-popup');
						self.popup.css('left', self.link.offset().left);
						self.popup.hover(self.show_lang_box, self.hide_on_leave);
						
						jQuery('#icl_package_language').off('wpml-package-language-changed');
						jQuery('#icl_package_language').on('wpml-package-language-changed', self.package_language_changed);
					};
					
					self.show_lang_box = function() {
						self.popup.css('top', self.link.offset().top + self.link.height() - window.scrollTop);
						self.popup.show();
					};
					
					self.hide_lang_box = function() {
						self.popup.hide();
					};
					
					self.hide_on_leave = function(e) {
						// Make sure we are fully leaving the popup because this is sometimes
						// triggered when we open the language select and then move the mouse.
						var x = e.clientX;
						var y = e.clientY;
						var pos = self.popup.offset();
						if ( x < pos.left || x > pos.left + self.popup.width() || y < pos.top || y > pos.top + self.popup.height()) {
							self.popup.fadeOut(800);
						}
					};
					
					self.package_language_changed = function( e, lang ) {
						self.link.html( self.find_flag(lang) + ' ' + lang);
						self.popup.hide();
					};
					
					self.find_flag = function( lang ) {
						var flag = '';
						jQuery('.js-simple-lang-selector option').each( function() {
							if (jQuery(this).text().trim() == lang.trim()) {
								flag = '<img src="' + jQuery(this).data('flag_url') + '">';
							}
							
						});
						return flag;
					};
					self.init();
				};
				
				jQuery(document).ready(
					function () {
						WPML_Package_Translation.admin_language_switcher = new WPML_Package_Translation.AdminLanguageSwitcher();
					}
				);
			</script>
		
		<?php
		
		return ob_get_clean();
	}
	
}
