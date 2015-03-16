<?php
    if ( ! defined( 'ABSPATH' ) ) {
        // prevent direct access to this file
        exit;
    }
?>

<div class="lp_page wp-core-ui">

    <div id="lp_js_flashMessage" class="lp_flash-Message" style="display:none;">
        <p></p>
    </div>

    <div class="lp_navigation">
        <?php if ( ! $laterpay['plugin_is_in_live_mode'] ): ?>
            <a href="<?php echo add_query_arg( array( 'page' => $laterpay['admin_menu']['account']['url'] ), admin_url( 'admin.php' ) ); ?>"
               class="lp_plugin-mode-indicator"
               data-icon="h">
                <h2 class="lp_plugin-mode-indicator__title"><?php _e( 'Test mode', 'laterpay' ); ?></h2>
                <span class="lp_plugin-mode-indicator__text"><?php _e( 'Earn money in <i>live mode</i>', 'laterpay' ); ?></span>
            </a>
        <?php endif; ?>
        <?php echo $laterpay['top_nav']; ?>
    </div>

    <div class="lp_pagewrap">
        <!-- Data -->
    </div>

</div>
