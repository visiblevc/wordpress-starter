<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

settings_errors(); ?>
<div class="wrap">
	<h1><?php _e( 'WP REST API Cache', 'wp-rest-api-cache' ); ?></h1>
	<form action="<?php echo admin_url( 'options-general.php?page=rest-cache' ); ?>" method="POST">
		<?php wp_nonce_field( 'rest_cache_options', 'rest_cache_nonce' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Empty all cache', 'wp-rest-api-cache' ); ?></th>
				<td><a href="<?php echo self::_empty_cache_url(); ?>" class="button button-primary"><?php _e( 'empty cache', 'wp-rest-api-cache' ); ?></a></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Cache time', 'wp-rest-api-cache' ); ?></th>
				<td>
					<input type="number" id="fld-cache-time" min="1" style="width: 70px;" name="rest_cache_options[timeout][length]" value="<?php echo absint( $options['timeout']['length'] ); ?>">
					<?php $period = absint( $options['timeout']['period'] ); ?>
					<select name="rest_cache_options[timeout][period]">
						<option value="<?php echo absint( MINUTE_IN_SECONDS ); ?>"<?php selected( $period, MINUTE_IN_SECONDS ); ?>><?php _e( 'Minute(s)', 'wp-rest-api-cache' ); ?></option>
						<option value="<?php echo absint( HOUR_IN_SECONDS ); ?>"<?php selected( $period, HOUR_IN_SECONDS ); ?>><?php _e( 'Hour(s)', 'wp-rest-api-cache' ); ?></option>
						<option value="<?php echo absint( DAY_IN_SECONDS ); ?>"<?php selected( $period, DAY_IN_SECONDS ); ?>><?php _e( 'Day(s)', 'wp-rest-api-cache' ); ?></option>
						<option value="<?php echo absint( WEEK_IN_SECONDS ); ?>"<?php selected( $period, WEEK_IN_SECONDS ); ?>><?php _e( 'Week(s)', 'wp-rest-api-cache' ); ?></option>
						<option value="<?php echo absint( YEAR_IN_SECONDS ); ?>"<?php selected( $period, YEAR_IN_SECONDS ); ?>><?php _e( 'Year(s)', 'wp-rest-api-cache' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">&nbsp;</th>
				<td><input type="submit" class="button button-primary" value="<?php _e( 'save changes', 'wp-rest-api-cache' ); ?>"></td>
			</tr>
		</table>
	</form>
</div>