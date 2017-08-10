<div class="wrap">
    <h2><?php esc_html_e( 'Support', 'sitepress' ) ?></h2>

    <p style="margin-top: 20px;">
		<?php printf( esc_html__( 'Technical support for clients is available via %sWPML forums%s.', 'sitepress' ), '<a target="_blank" href="https://wpml.org/forums/">', '</a>' ); ?>
    </p>

	<?php
	$wpml_plugins_list = SitePress::get_installed_plugins();

	echo '
        <table class="widefat" style="width: auto;">
            <thead>
                <tr>    
                    <th>' . esc_html__( 'Plugin Name', 'sitepress' ) . '</th>
                    <th style="text-align:right">' . esc_html__( 'Status', 'sitepress' ) . '</th>
                    <th>' . esc_html__( 'Active', 'sitepress' ) . '</th>
                    <th>' . esc_html__( 'Version', 'sitepress' ) . '</th>
                </tr>
            </thead>    
            <tbody>
        ';

	foreach ( $wpml_plugins_list as $name => $plugin_data ) {

		$plugin_name = $name;
		$file        = $plugin_data['file'];
		$dir         = dirname( $file );

		echo '<tr>';
		echo '<td><i class="icon18 ' . esc_attr( $plugin_data['slug'] ) . '"></i>' . esc_html( $plugin_name ) . '</td>';
		echo '<td align="right">';
		if ( empty( $plugin_data['plugin'] ) ) {
			echo esc_html__( 'Not installed', 'sitepress' );
		} else {
			echo esc_html__( 'Installed', 'sitepress' );
		}
		echo '</td>';
		echo '<td align="center">';
		echo isset( $file ) && is_plugin_active( $file ) ? esc_html__( 'Yes', 'sitepress' ) : esc_html__( 'No', 'sitepress' );
		echo '</td>';
		echo '<td align="right">';
		echo isset( $plugin_data['plugin']['Version'] ) ? esc_html( $plugin_data['plugin']['Version'] ) : esc_html__( 'n/a', 'sitepress' );
		echo '</td>';
		echo '</tr>';

	}

	echo '
            </tbody>
        </table>
    ';

	?>

    <p style="margin-top: 20px;">
		<?php printf( esc_html__( 'For advanced access or to completely uninstall WPML and remove all language information, use the %stroubleshooting%s page.', 'sitepress' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php' ) ) . '">', '</a>' ); ?>
    </p>

    <p style="margin-top: 20px;">
		<?php printf( esc_html__( 'For retrieving debug information if asked by support person, use the %sdebug information%s page.', 'sitepress' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/debug-information.php' ) ) . '">', '</a>' ); ?>
    </p>

	<?php do_action( 'wpml_support_page_after' ); ?>

</div>
