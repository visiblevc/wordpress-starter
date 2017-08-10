<h3><?php echo $args['box_title']  ?></h3>

<?php
if(empty($args['repository']) || empty($args['package']) || empty($args['product'])){
    echo __('Incorrect setup', 'installer');
    return;
}

$product = false;
foreach($this->settings['repositories'][$repository_id]['data']['packages'] as $package_idx => $package){

    //pre 1.3 backwardds compatibility
    if(!isset($package['id'])){
        $package['id'] = sanitize_title_with_dashes($package['name']);
    }

    if($package['id'] == $args['package']){
        $product = $this->settings['repositories'][$repository_id]['data']['packages'][$package_idx]['products'][$args['product']];
        break;
    }
}


if(!$product){
    echo __('Invalid product', 'installer');
    return;
}

if(isset($this->settings['repositories'][$repository_id])){
    if(isset($this->settings['repositories'][$repository_id]['subscription']['key'])){
        $site_key = $this->settings['repositories'][$repository_id]['subscription']['key'];
    }else{
        $site_key = false;
    }
}else{
    echo __('Unknown repository', 'installer');
    return;
}

$subscription_type = $this->get_subscription_type_for_repository($repository_id);
$expired = false;

if($subscription_type != $product['subscription_type'] && !$this->have_superior_subscription($subscription_type, $product) && $site_key){
    $subscription_no_match = sprintf(__(' Your current site key (%s) does not match the selected product (%s).', 'installer'), $site_key, $product['name']);
}

if(!isset($args['product_name'])) $args['product_name'] = $product['name'];

?>

<div class="otgs_wp_installer_table otgs_wp_installer_table_compact">

<p><?php echo $args['box_description']  ?></p>


<?php if(!$this->repository_has_subscription($repository_id) || !empty($subscription_no_match)): ?>

            <?php if(!empty($subscription_no_match)): ?>
            <div class="installer-warn-box">
            <?php echo $subscription_no_match; ?>
            </div>
            <br />
            <?php endif; ?>

            <a class="button-primary" href="<?php echo $this->append_parameters_to_buy_url($product['url'], $repository_id, $args) ?>"><?php printf(__('Buy %s', 'installer'), $args['product_name']) ?></a>

            <div>
                <br />
                <?php printf(__('Already bought %s?', 'installer'), $args['product_name']) ?>
                <a class="enter_site_key_js<?php if( WP_Installer::get_repository_hardcoded_site_key( $repository_id ) ): ?> disabled<?php endif ?>" href="#"
                    <?php if( WP_Installer::get_repository_hardcoded_site_key( $repository_id ) ): ?>
                        style="cursor: help"
                        disabled="disabled"
                        title="<?php printf( esc_attr__("Site-key was set by %s, most likely in wp-config.php. Please remove the constant before attempting to register.", 'installer'), 'OTGS_INSTALLER_SITE_KEY_' . strtoupper($repository_id) ) ?>"
                    <?php endif; ?>
                >
                    <?php _e('Enter site key', 'installer') ?>
                </a>&nbsp;&nbsp;

                <form class="otgsi_site_key_form" method="post">
                <input type="hidden" name="action" value="save_site_key" />
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('save_site_key_' . $repository_id) ?>" />
                <input type="hidden" name="repository_id" value="<?php echo $repository_id ?>">
                <input type="text" size="10" name="site_key_<?php echo $repository_id ?>" />
                <input class="button-secondary" type="submit" value="<?php esc_attr_e('Add', 'installer') ?>" />
                </form>
                <div class="installer-error-box hidden" style="margin-top:10px;"></div>
            </div>

<?php else: ?>

    <?php if($this->repository_has_expired_subscription($repository_id)): $expired = true; ?>

        <div><p class="installer-warn-box"><?php _e('Subscription is expired.', 'installer') ?></p></div>

    <?php else: ?>

        <?php if($this->show_subscription_renew_warning($repository_id, $subscription_type)): ?>

            <ul class="installer-products-list">
            <?php foreach($product['renewals'] as $renewal): ?>
                <li>
                    <a href="<?php echo $this->append_parameters_to_buy_url($renewal['url'], $repository_id, $args) ?>"><?php printf(__('Renew %s', 'installer'), $args['product_name']) ?></a>
                </li>
            <?php endforeach; ?>
            </ul>

        <?php endif; ?>

    <?php endif; ?>

    <center>
    <a class="remove_site_key_js" href="#" data-repository=<?php echo $repository_id ?>
        data-confirmation="<?php esc_attr_e('Are you sure you want to remove this site key?', 'installer') ?>"
        data-nonce="<?php echo wp_create_nonce('remove_site_key_' . $repository_id) ?>"
        <?php if( WP_Installer::get_repository_hardcoded_site_key( $repository_id ) ): ?>
            style="cursor: help;color:#999999"
            disabled="disabled"
            title="<?php printf( esc_attr__("Site-key was set by %s, most likely in wp-config.php. Please remove the constant before attempting to unregister.", 'installer'), 'OTGS_INSTALLER_SITE_KEY_' . strtoupper($repository_id) ) ?>"
        <?php endif; ?>
        >
            <?php printf(__("Remove current site key (%s)", 'installer'), $site_key) ?>
    </a>
    </center>
    <br />

    <?php include $this->plugin_path() . '/templates/downloads-list-compact.php'; ?>



<?php endif; ?>

<?php
if( isset( $args[ 'name' ] ) ):
    $support_link = $this->get_support_tag_by_name($args['name'], $args['repository']); ?>

    <?php if($support_link): ?>
<p><a href="<?php echo $support_link ?>" target="_blank"><?php printf(__('%s support on wpml.org', 'installer'), $args['name'] ) ?></a></p>

<?php endif; ?>
<?php
// compatibility for installer 1.1
elseif( isset( $args[ 'support_link' ] ) ): ?>
    <p><?php echo $args[ 'support_link' ]; ?></p>
<?php endif; ?>

</div>
