<?php

/**
 * Class WPML_TM_Post_Edit_Link_Anchor
 *
 * Creates post links with a given anchor text, pointing at the back-end
 * post edit view
 */
class WPML_TM_Post_Edit_Link_Anchor extends WPML_TM_Post_Link_Anchor {

	protected function link_target() {

		return $this->sitepress->get_wp_api()->get_edit_post_link( $this->post_id );
	}
}