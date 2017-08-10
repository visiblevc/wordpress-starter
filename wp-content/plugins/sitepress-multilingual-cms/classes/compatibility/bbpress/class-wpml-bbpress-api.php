<?php

class WPML_BBPress_API {

	public function bbp_get_user_profile_url( $user_id = 0, $user_nicename = '' ) {
		return bbp_get_user_profile_url( $user_id, $user_nicename );
	}
}