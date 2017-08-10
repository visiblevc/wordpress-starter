<?php

class WPML_Slash_Management {

	public function match_trailing_slash_to_reference( $url, $reference_url ) {
		if ( trailingslashit( $reference_url ) === $reference_url && ! $this->has_lang_param( $url ) ) {
			return trailingslashit( $url );
		} else {
			return untrailingslashit( $url );
		}
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	private function has_lang_param( $url ) {
		return strpos( $url, '?lang=' ) !== false || strpos( $url, '&lang=' ) !== false;
	}

	public function maybe_user_trailingslashit( $url, $method ) {
		global $wp_rewrite;

		if ( null !== $wp_rewrite ) {
			return user_trailingslashit( $url );
		} else {
			return 'untrailingslashit' === $method ? untrailingslashit( $url ) : trailingslashit( $url );
		}
	}
}