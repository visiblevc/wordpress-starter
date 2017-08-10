<div class="progress-content">
	<span class="close-progress-content close-progress-content-button">&times;</span>
	<div>
		<h2 class="progress-title"><?php _e( 'Please wait while migration is running...', 'wp-sync-db' ); ?></h2>
	</div>
	<div class="progress-info-wrapper clearfix">
		<div class="progress-text"><?php _e( 'Establishing Connection', 'wp-sync-db' ); ?></div>
		<span class="timer"><?php echo __( 'Time Elapsed:', 'wp-sync-db' ) , ' 00:00:00'; ?></span>
	</div>
	<div class="clearfix"></div>
	<div class="progress-bar-wrapper">
		<div class="progress-tables-hover-boxes"></div>
		<div class="progress-label">wp_options</div>
		<div class="progress-bar"></div>
		<div class="progress-tables"></div>
	</div>

	<div class="migration-controls">
		<span class="pause-resume button"><?php _e( 'Pause', 'wp-sync-db' ); ?></span>
		<span class="cancel button"><?php _e( 'Cancel', 'wp-sync-db' ); ?></span>
	</div>

</div> <!-- end .progress-content -->
