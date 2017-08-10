<div class="migrate-tab content-tab">
	<p class="saved-migration-profile-label"><?php _e( 'Would you like to use a saved migration profile?', 'wp-sync-db' ); ?></p>
	<ul class="migration-profile-options">
		<?php foreach( $this->settings['profiles'] as $key => $profile ) { ++$key ?>
			<li><a href="<?php echo $this->plugin_base , '&wpsdb-profile=' , $key; ?>"><?php printf( '%s - %s', $key, $profile['name'] ); ?></a><span class="main-list-delete-profile-link" data-profile-id="<?php echo $key; ?>">&times;</span></li>
		<?php } ?>
		<li><a href="<?php echo $this->plugin_base , '&wpsdb-profile=-1'; ?>"><?php _e( 'Nope, let\'s start fresh...', 'wp-sync-db' ); ?></a></li>
	</ul>
</div>
