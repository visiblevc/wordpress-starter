<div class="wrap">
    <h2><?php echo __('Translation options', 'sitepress') ?></h2>
    <br />
    <?php include dirname(__FILE__) . '/_posts_sync_options.php'; ?>

    <?php if(defined('WPML_ST_VERSION')): ?>
    <?php  include WPML_ST_PATH . '/menu/_slug-translation-options.php'; ?>
    <?php endif; ?>

    <br clear="all" />
    <?php 
	include dirname(__FILE__) . '/_custom_types_translation.php';
	
	do_action('icl_tm_menu_mcsetup');

    do_action('icl_menu_footer'); 
	?>
</div>