<?php
    if ( ! defined( 'ABSPATH' ) ) {
        // prevent direct access to this file
        exit;
    }
?>

<div class="lp_sitenotice" style="background:<?php echo $laterpay_migrator['bg_color']; ?>; color:<?php echo $laterpay_migrator['text_color']; ?>;">
    <span class="lp_sitenotice__message"><?php echo $laterpay_migrator['message']; ?></span>
    <a href="#"
        id="lp_buySubscription"
        class"lp_sitenotice__button button button-primary"><?php echo $laterpay_migrator['button_text']; ?></a>
    <a href="#" id="lp_fakeButton" class="lp_js_doPurchase" style="display:none;" data-laterpay=""></a>
</div>
