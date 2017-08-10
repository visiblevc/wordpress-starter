<?php

class WPML_TM_String_Basket_Request {
	/** @var  WPML_Translation_Basket $basket */
	private $basket;

	/**
	 * WPML_TM_String_Request constructor.
	 *
	 * @param WPML_Translation_Basket $basket_instance
	 */
	public function __construct( &$basket_instance ) {
		$this->basket = &$basket_instance;
	}

	/**
	 * @param array $post clone of $_POST
	 */
	public function send_to_basket( $post ) {
		$post         = stripslashes_deep( $post );
		$string_ids   = explode( ',', $post['strings'] );
		$translate_to = array();
		foreach ( $post['translate_to'] as $lang_to => $one ) {
			$translate_to[ $lang_to ] = $lang_to;
		}
		if ( ! empty( $translate_to ) ) {
			$this->basket->add_strings_to_basket( $string_ids, $post['icl-tr-from'], $translate_to );
		}
	}
}