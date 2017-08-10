<?php if((!$this->repository_has_subscription($repository_id) && $match = $this->get_matching_cp($repository)) && $match['exp']): ?>
<p class="alignright installer_highlight"><strong><?php printf('Price offers available until %s', date_i18n(get_option( 'date_format' ), $match['exp'])) ?></strong></p>
<?php endif; ?>

<h3 id="repository-<?php echo $repository_id ?>"><?php echo $repository['data']['name'] ?></h3>
<?php
    $generic_product_name = $this->settings['repositories'][$repository_id]['data']['product-name'];
?>
<table class="widefat otgs_wp_installer_table" id="installer_repo_<?php echo $repository_id ?>">

    <tr>
        <td>&nbsp;</td>
        <td class="otgsi_register_product_wrap" align="center" valign="top">
            <?php // IF NO SUBSCRIPTION ?>
            <?php if(!$this->repository_has_subscription($repository_id)): ?>

            <div style="text-align: right;">
                <span><?php _e('Already bought?', 'installer'); ?>&nbsp;</span>
                <a class="enter_site_key_js button-primary<?php if( WP_Installer::get_repository_hardcoded_site_key( $repository_id ) ): ?> disabled<?php endif ?>" href="#"
                    <?php if( WP_Installer::get_repository_hardcoded_site_key( $repository_id ) ): ?>
                        style="cursor: help"
                        disabled="disabled"
                        title="<?php printf( esc_attr__("Site-key was set by %s, most likely in wp-config.php. Please remove the constant before attempting to register.", 'installer'), 'OTGS_INSTALLER_SITE_KEY_' . strtoupper($repository_id) ) ?>"
                    <?php endif; ?>
                >
                    <?php printf(__('Register %s', 'installer'), $generic_product_name); ?>
                </a>&nbsp;&nbsp;
                <form class="otgsi_site_key_form" method="post">
                <input type="hidden" name="action" value="save_site_key" />
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('save_site_key_' . $repository_id) ?>" />
                <input type="hidden" name="repository_id" value="<?php echo $repository_id ?>">
                <?php _e('2. Enter your site key', 'installer'); ?>
                <input type="text" size="10" name="site_key_<?php echo $repository_id ?>" placeholder="<?php echo esc_attr('site key') ?>" />
                <input class="button-primary" type="submit" value="<?php esc_attr_e('OK', 'installer') ?>" />
                <input class="button-secondary cancel_site_key_js" type="button" value="<?php esc_attr_e('Cancel', 'installer') ?>" />

                <div class="alignleft" style="margin-top:6px;"><?php printf(__('1. Go to your %s%s account%s and add this site URL: %s', 'installer'),
                    '<a href="' . $this->settings['repositories'][$repository_id]['data']['site_keys_management_url'] . '?add='.urlencode($this->get_installer_site_url( $repository_id )).'">',
                      $generic_product_name, '</a>', $this->get_installer_site_url( $repository_id )); ?></div>
                </form>


            </div>

            <?php
                $site_key = false;

            // IF SUBSCRIPTION
            else:

                $site_key = $this->settings['repositories'][$repository_id]['subscription']['key'];
                $subscription_type = $this->get_subscription_type_for_repository($repository_id);
                $upgrade_options = $this->get_upgrade_options($repository_id);
                $expired = false;

            ?>

            <?php if($this->repository_has_expired_subscription($repository_id)): $expired = true; ?>
                <div>
                    <p class="installer-warn-box">
                        <?php _e('Subscription expired. You need to either purchase a new subscription or upgrade if available.', 'installer') ?>
                        <span class="alignright">
                            <a class="update_site_key_js button-secondary" href="#" data-repository=<?php echo $repository_id ?> data-nonce="<?php echo wp_create_nonce('update_site_key_' . $repository_id) ?>">
                                <?php  _e('Revalidate subscription', 'installer'); ?>
                            </a>
                        </span>
                        <br />
                        <span class="details"><?php _e("If you have already purchased or renewed your subscription and you can still see this message, please revalidate your subscription", 'installer') ?></span>
                    </p>
                </div>
            <?php else: ?>
                <?php $this->show_subscription_renew_warning($repository_id, $subscription_type); ?>
            <?php endif; ?>

            <div class="alignright">
                <a class="remove_site_key_js button-secondary" href="#" data-repository=<?php echo $repository_id ?>
                    data-confirmation="<?php esc_attr_e('Are you sure you want to unregister?', 'installer') ?>"
                    data-nonce="<?php echo wp_create_nonce('remove_site_key_' . $repository_id) ?>"
                    <?php if( WP_Installer::get_repository_hardcoded_site_key( $repository_id ) ): ?>
                     style="cursor: help"
                     disabled="disabled"
                     title="<?php printf( esc_attr__("Site-key was set by %s, most likely in wp-config.php. Please remove the constant before attempting to unregister.", 'installer'), 'OTGS_INSTALLER_SITE_KEY_' . strtoupper($repository_id) ) ?>"
                    <?php endif; ?>
                    >
                    <?php printf(__("Unregister %s from this site", 'installer'), $generic_product_name) ?></a>&nbsp;
                <a class="update_site_key_js button-secondary" href="#" data-repository=<?php echo $repository_id ?>
                    data-nonce="<?php echo wp_create_nonce('update_site_key_' . $repository_id) ?>">
                    <?php _e('Check for updates', 'installer'); ?>
                </a>
            </div>

            <?php if(empty($expired)): ?>
            <div class="alignleft">
                <?php if($expires = $this->settings['repositories'][$repository_id]['subscription']['data']->expires): ?>
                    <?php printf(__('%s is registered on this site. You will receive automatic updates until %s', 'installer'), $generic_product_name, date_i18n('F j, Y', strtotime($expires))); ?>
                <?php else: ?>
                    <?php printf(__('%s is registered on this site. Your Lifetime account gives you updates for life.', 'installer'), $generic_product_name); ?>
                <?php endif; ?>
            </div>
            <?php endif; //if(empty($expired)) ?>

            <?php endif; // if(!repository_has_subscription) ?>
            <br clear="all" />
            <div class="installer-error-box hidden"></div>

        </td>
    </tr>

    <?php

    $subscription_type = isset($subscription_type) ? $subscription_type : null;
    $expired = isset($expired) ? $expired : null;
    $upgrade_options = isset($upgrade_options) ? $upgrade_options : null;
    $packages = $this->_render_product_packages($repository['data']['packages'], $subscription_type, $expired, $upgrade_options, $repository_id);
    if(empty($subscription_type) || $expired){
        $subpackages_expandable = true;
    }else{
        $subpackages_expandable = false;
    }

    ?>

    <?php foreach($packages as $package): ?>
    <tr id="repository-<?php echo $repository_id ?>_<?php echo $package['id'] ?>">
        <td><img width="140" height="140" src="<?php echo $package['image_url'] ?>" /></td>
        <td>
            <p><strong><?php echo $package['name'] ?></strong></p>
            <p><?php echo $package['description'] ?></p>

            <?php if($package['products']): ?>
                <?php foreach($package['products'] as $product): ?>
                <ul class="installer-products-list" style="display:inline">
                    <li>
                        <a class="button-secondary" href="<?php echo $product['url'] ?>"><?php echo $product['label'] ?></a>
                    </li>
                </ul>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if($package['downloads']): ?>
            <?php include $this->plugin_path() . '/templates/downloads-list.php'; ?>
            <?php endif; ?>

            <?php if(!empty($package['sub-packages'])): ?>

                <?php $subpackages = $this->_render_product_packages($package['sub-packages'], $subscription_type, $expired, $upgrade_options, $repository_id); ?>

                <?php if($subpackages): ?>

                <?php if($subpackages_expandable): ?>
                <h5><a class="installer_expand_button" href="#" title="<?php esc_attr_e('Click to see individual components options.', 'installer') ?>"><?php _e('Individual components', 'installer') ?></a></h5>
                <?php endif; ?>

                <table class="otgs_wp_installer_subtable" style="<?php if($subpackages_expandable) echo 'display:none' ?>">
                <?php foreach($subpackages as $package): ?>
                    <tr id="repository-<?php echo $repository_id ?>_<?php echo $package['id'] ?>">
                        <td><img width="70" height="70" src="<?php echo $package['image_url'] ?>" /></td>
                        <td>
                            <p><strong><?php echo $package['name'] ?></strong></p>
                            <p><?php echo $package['description'] ?></p>

                            <?php if($package['products']): ?>
                                <?php foreach($package['products'] as $product): ?>
                                    <ul class="installer-products-list" style="display:inline">
                                        <li>
                                            <a class="button-secondary" href="<?php echo $product['url'] ?>"><?php echo $product['label'] ?></a>
                                        </li>
                                    </ul>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if($package['downloads']): ?>
                                <?php include $this->plugin_path() . '/templates/downloads-list.php'; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </table>
                <?php endif; ?>

            <?php endif;  ?>


        </td>
    </tr>

    <?php endforeach; ?>

</table>


<p><i><?php printf(__('This page lets you install plugins and update existing plugins. To remove any of these plugins, go to the %splugins%s page and if you have the permission to remove plugins you should be able to do this.', 'installer'), '<a href="' . admin_url('plugins.php') . '">' , '</a>'); ?></i></p>



<br />
