<?php

abstract class WPML_TM_MCS_Section_UI {

	private $id;
	private $title;

	public function __construct( $id, $title ) {
		$this->id = $id;
		$this->title = $title;
	}

	public function render_top_link() {
		?>
		<a href="#<?php echo $this->id; ?>"><?php echo $this->title ?></a>
		<?php
	}

	public function render() {

		?>

		<div class="wpml-section" id="<?php echo $this->id; ?>">

		    <div class="wpml-section-header">
		        <h3><?php echo $this->title ?></h3>
			</div>

			<div class="wpml-section-content">
				<?php $this->render_content(); ?>
			</div>

		</div>

		<?php

	}

	protected abstract function render_content();
}

