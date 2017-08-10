<script type="text/html" id="table-listing-no-jobs">
	<div id="table-listing-no-jobs-wrapper">
		<p><?php _e( 'There are no Jobs in the queue.', 'wpml-translation-management' ); ?></p>
		<p><?php _e( 'In order to add jobs to the queue you must:', 'wpml-translation-management' ); ?></p>
		<ul style="list-style: circle; padding-left: 20px ">
			<li><?php printf( __( 'go to the <a href="%s">Translation Dashboard</a>', 'wpml-translation-management' ),
			                  "admin.php?page=" . WPML_TM_FOLDER . "/menu/main.php&sm=dashboard" ); ?></li>
			<li><?php _e( 'add documents to the basket', 'wpml-translation-management' ); ?></li>
			<li><?php _e( 'send them for translation', 'wpml-translation-management' ); ?></li>
		</ul>
	</div>
</script>