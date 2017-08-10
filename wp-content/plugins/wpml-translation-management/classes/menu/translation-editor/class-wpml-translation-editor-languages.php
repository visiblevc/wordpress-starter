<?php

class WPML_Translation_Editor_Languages extends WPML_SP_User {

	private $job;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( &$sitepress, $job ) {
		parent::__construct( $sitepress );
		$this->job = $job;
	}

	public function get_model() {
		$source_lang = $this->sitepress->get_language_details( $this->job->source_language_code );
		$target_lang = $this->sitepress->get_language_details( $this->job->language_code );

		$data        = array(
			'source'      => $this->job->source_language_code,
			'target'      => $this->job->language_code,
			'source_lang' => $source_lang['display_name'],
			'target_lang' => $target_lang['display_name']
		);
		$data['img'] = array(
			'source_url' => $this->sitepress->get_flag_url( $this->job->source_language_code ),
			'target_url' => $this->sitepress->get_flag_url( $this->job->language_code )
		);


		return $data;
	}
}

