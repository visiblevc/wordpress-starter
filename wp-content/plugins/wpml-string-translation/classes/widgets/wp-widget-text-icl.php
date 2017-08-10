<?php

class WP_Widget_Text_Icl extends WP_Widget {
	const FILTER_PRIORITY = 0;

	/**
	 * WP_Widget_Text_Icl constructor.
	 */
	public function __construct() {
		$widget_ops  = array( 'classname' => 'widget_text_icl', 'description' => __( 'Multilingual arbitrary text or HTML', 'sitepress' ) );
		parent::__construct( 'text_icl', __( 'Multilingual Text', 'sitepress' ), $widget_ops );
	}

	function widget($args, $instance) {
		extract($args);
		$before_widget = $args['before_widget'];
		$after_widget = $args['after_widget'];
		$before_title = $args['before_title'];
		$after_title = $args['after_title'];
		if ($instance['icl_language'] != 'multilingual' && $instance['icl_language'] != ICL_LANGUAGE_CODE) {
			return;
		} else if ($instance['icl_language'] == 'multilingual' && function_exists('icl_t')) {
			// Get translations
			$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
			$was_hooked = remove_filter('widget_text', 'icl_sw_filters_widget_text', self::FILTER_PRIORITY);
			$text = apply_filters('widget_text', icl_t('Widgets', 'widget body - ' . $this->id, $instance['text']), $instance);
			if( $was_hooked ) {
				add_filter('widget_text', 'icl_sw_filters_widget_text', self::FILTER_PRIORITY);
			}
		} else {
			remove_filter('widget_title', 'icl_sw_filters_widget_title');
			$was_hooked = remove_filter('widget_text', 'icl_sw_filters_widget_text', self::FILTER_PRIORITY);
			$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
			$text = apply_filters('widget_text', $instance['text'], $instance);
			add_filter('widget_title', 'icl_sw_filters_widget_title');
			if( $was_hooked ) {
				add_filter('widget_text', 'icl_sw_filters_widget_text', self::FILTER_PRIORITY);
			}
		}
		echo $before_widget;
		if (!empty($title)) {
			echo $before_title . $title . $after_title;
		}
		?>
		<div class="textwidget"><?php echo $instance['filter'] ? wpautop($text) : $text; ?></div>
		<?php
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		global $wpdb;
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		if (current_user_can('unfiltered_html'))
			$instance['text'] = $new_instance['text'];
		else
			$instance['text'] = stripslashes(wp_filter_post_kses(addslashes($new_instance['text']))); // wp_filter_post_kses() expects slashed
		$instance['filter'] = isset($new_instance['filter']);

		if ($new_instance['icl_language'] == 'multilingual') {
			$string = $wpdb->get_row($wpdb->prepare("SELECT id, value, status FROM {$wpdb->prefix}icl_strings WHERE context=%s AND name=%s", 'Widgets', 'widget body - ' . $this->id));
			if ($string) {
				icl_st_update_string_actions('Widgets', 'widget body - ' . $this->id, $old_instance['text'], $instance['text']);
			} else {
				icl_register_string('Widgets', 'widget body - ' . $this->id, $instance['text']);
			}
		}
		$instance['icl_language'] = $new_instance['icl_language'];
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args((array) $instance, array(
			'title' => '',
			'text' => '',
			'icl_language' => 'multilingual',
			'icl_converted_from' => -1));
		$title = strip_tags($instance['title']);
		$text = esc_textarea($instance['text']);
		$language = $instance['icl_language'];

		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

		<p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Automatically add paragraphs'); ?></label></p>
		<?php
		icl_widget_text_language_selectbox($language, $this->get_field_name('icl_language'));
	}

}