                    
                    <form method="post" class="otgsi_downloads_form">
                    
                    <table class="installer-plugins-list-compact">
                        <thead>
                            <tr>
                                <th>&nbsp;</th>
                                <th><?php _e('Plugin', 'installer') ?></th>
                                <th><img src="<?php echo $this->plugin_url() ?>/res/img/globe.png" alt="<?php esc_attr_e('Available', 'installer') ?>" width="16" height="16"></th>
                                <th><img src="<?php echo $this->plugin_url() ?>/res/img/computer.png" alt="<?php esc_attr_e('Installed', 'installer') ?>" width="16" height="16"></th>
                                <th><img src="<?php echo $this->plugin_url() ?>/res/img/dn2.gif" alt="<?php esc_attr_e('Downloading', 'installer') ?>" width="16" height="16"></th>
                                <th><img src="<?php echo $this->plugin_url() ?>/res/img/on.png" alt="<?php esc_attr_e('Activate', 'installer') ?>" width="16" height="16"></th>
                            </tr>
                        </thead>                        
                        <tbody>
                        <?php foreach($product['downloads'] as $download): ?>
                            <?php if(empty($tr_oddeven) || $tr_oddeven == 'even') $tr_oddeven = 'odd'; else $tr_oddeven = 'even'; ?>
                            <tr class="<?php echo $tr_oddeven ?>">
                                <td>
                                    <label>
                                    <?php 
                                        $url =  $this->append_site_key_to_download_url($download['url'], $site_key, $repository_id );

                                        $download_data = array(
                                            'url'           => $url, 
                                            'slug'          => $download['slug'],
                                            'nonce'         => wp_create_nonce('install_plugin_' . $url),
                                            'repository_id' => $repository_id
                                        );

                                        $disabled = $expired ||
                                                    (
                                                        $this->plugin_is_installed($download['name'], $download['slug'], $download['version']) &&
                                                        !$this->plugin_is_embedded_version($download['name'], $download['slug'])
                                                    ) || WP_Installer()->dependencies->cant_download( $repository_id );

                                    ?>
                                    <input type="checkbox" name="downloads[]" value="<?php echo base64_encode(json_encode($download_data)); ?>" <?php 
                                        if($disabled): ?>disabled="disabled"<?php endif; ?> />&nbsp;
                                        
                                    </label>                                
                                </td>
                                <td class="installer_plugin_name"><?php echo $download['name'] ?></td>
                                <td><?php echo $download['version'] ?></td>
                                <td class="installer_version_installed">
                                    <?php if($v = $this->plugin_is_installed($download['name'], $download['slug'])):
                                            $class = version_compare($v, $download['version'], '>=') ? 'installer-green-text' : 'installer-red-text'; ?>
                                    <span class="<?php echo $class ?>"><?php echo $v; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="twelve">
                                    <div class="installer-status-downloading"><?php _e('downloading...', 'installer') ?></div>
                                    <div class="installer-status-downloaded" data-fail="<?php _e('failed!', 'installer') ?>"><?php _e('downloaded', 'installer') ?></div>
                                </td>
                                <td class="twelve">
                                    <div class="installer-status-activating"><?php _e('activating', 'installer') ?></div>
                                    <div class="installer-status-activated"><?php _e('activated', 'installer') ?></div>
                                </td>                                
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if( !WP_Installer()->dependencies->is_uploading_allowed() ): ?>
                        <p class="installer-error-box"><?php printf(__('Downloading is not possible because WordPress cannot write into the plugins folder. %sHow to fix%s.', 'installer'),
                                '<a href="http://codex.wordpress.org/Changing_File_Permissions">', '</a>') ?></p>
                    <?php elseif( WP_Installer()->dependencies->is_win_paths_exception($repository_id) ): ?>
                        <p><?php echo WP_Installer()->dependencies->win_paths_exception_message() ?></p>
                    <?php endif;?>

                    <br />
                    <input type="submit" class="button-secondary" value="<?php esc_attr_e('Download', 'installer') ?>" disabled="disabled" />
                    &nbsp;
                    <label><input name="activate" type="checkbox" value="1" disabled="disabled" />&nbsp;<?php _e('Activate after download', 'installer') ?></label>

                    <div class="installer-download-progress-status"></div>
                    <div class="installer-status-success"><?php _e('Operation complete!', 'installer') ?></div>

                    <span class="installer-revalidate-message hidden"><?php _e("Download failed!\n\nClick OK to revalidate your subscription or CANCEL to try again.", 'installer') ?></span>
                    </form>         
