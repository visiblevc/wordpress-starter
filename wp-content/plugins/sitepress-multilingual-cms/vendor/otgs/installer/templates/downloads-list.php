                    <br clear="all" /><br />
                    <strong><?php _e('Downloads:', 'installer') ?></strong>
                    
                    <form method="post" class="otgsi_downloads_form">
                    
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>&nbsp;</th>
                                <th><?php _e('Plugin', 'installer') ?></th>
                                <th><?php _e('Current version', 'installer') ?></th>
                                <th><?php _e('Released', 'installer') ?></th>
                                <th><?php _e('Installed version', 'installer') ?></th>
                                <th>&nbsp;</th>
                                <th>&nbsp;</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>                        
                        <tbody>
                        <?php
                        foreach($package['downloads'] as $download): ?>
                            <tr>
                                <td>
                                    <label>
                                    <?php 
                                        $url =  $this->append_site_key_to_download_url($download['url'], $site_key, $repository_id);

                                        $download_data = array(
                                            'url'           => $url, 
                                            'slug'          => $download['slug'],
                                            'nonce'         => wp_create_nonce('install_plugin_' . $url),
                                            'repository_id' => $repository_id
                                        );
                                    ?>
                                    <input type="checkbox" name="downloads[]" value="<?php echo base64_encode(json_encode($download_data)); ?>" <?php 
                                        if($this->plugin_is_installed($download['name'], $download['slug'], $download['version']) && !$this->plugin_is_embedded_version($download['name'], $download['slug']) || WP_Installer()->dependencies->cant_download($repository_id) ): ?>disabled="disabled"<?php endif; ?> />&nbsp;
                                        
                                    </label>                                
                                </td>
                                <td class="installer_plugin_name"><?php echo $download['name'] ?></td>
                                <td><?php echo $download['version'] ?></td>
                                <td><?php echo date_i18n('F j, Y', strtotime($download['date'])) ?></td>
                                <td class="installer_version_installed">
                                    <?php if($v = $this->plugin_is_installed($download['name'], $download['slug'])): $class = version_compare($v, $download['version'], '>=') ? 'installer-green-text' : 'installer-red-text'; ?>
                                    <span class="<?php echo $class ?>"><?php echo $v; ?></span>
                                    <?php if($this->plugin_is_embedded_version($download['name'], $download['slug'])): ?>&nbsp;<?php _e('(embedded)', 'installer'); ?><?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="installer-status-installing"><?php _e('installing...', 'installer') ?></span>
                                    <span class="installer-status-updating"><?php _e('updating...', 'installer') ?></span>
                                    <span class="installer-status-installed" data-fail="<?php _e('failed!', 'installer') ?>"><?php _e('installed', 'installer') ?></span>
                                    <span class="installer-status-updated" data-fail="<?php _e('failed!', 'installer') ?>"><?php _e('updated', 'installer') ?></span>
                                </td>
                                <td>
                                    <span class="installer-status-activating"><?php _e('activating', 'installer') ?></span>                                    
                                    <span class="installer-status-activated"><?php _e('activated', 'installer') ?></span>
                                </td>
                                <td class="for_spinner_js"><span class="spinner"></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <br />

                    <div class="installer-error-box">
                    <?php if( !WP_Installer()->dependencies->is_uploading_allowed() ): ?>
                        <p><?php printf(__('Downloading is not possible because WordPress cannot write into the plugins folder. %sHow to fix%s.', 'installer'),
                                '<a href="http://codex.wordpress.org/Changing_File_Permissions">', '</a>') ?></p>
                    <?php elseif( WP_Installer()->dependencies->is_win_paths_exception($repository_id) ): ?>
                        <p><?php echo WP_Installer()->dependencies->win_paths_exception_message() ?></p>
                    <?php endif; ?>
                    </div>

                    <input type="submit" class="button-secondary" value="<?php esc_attr_e('Download', 'installer') ?>" disabled="disabled" />
                    &nbsp;
                    <label><input name="activate" type="checkbox" value="1" disabled="disabled" />&nbsp;<?php _e('Activate after download', 'installer') ?></label>

                    <div class="installer-download-progress-status"></div>

                    <div class="installer-status-success"><?php _e('Operation complete!', 'installer') ?></div>

                    <span class="installer-revalidate-message hidden"><?php _e("Download failed!\n\nPlease refresh the page and try again.", 'installer') ?></span>
                    </form>         
