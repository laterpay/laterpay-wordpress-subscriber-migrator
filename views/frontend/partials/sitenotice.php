<?php
    if ( ! defined( 'ABSPATH' ) ) {
        // prevent direct access to this file
        exit;
    }
?>

<div class="lp_siiitenotice" style="background:<?php echo $laterpay_migrator['bg_color']; ?>; color:<?php echo $laterpay_migrator['text_color']; ?>;">
    <div class="lp_siiitenotice__content">
        <div class="lp_siiitenotice__message"><?php echo $laterpay_migrator['message']; ?></div>
        <a href="#" id="lp_buySubscription" class="lp_siiitenotice__button" data-icon="b"><?php echo $laterpay_migrator['button_text']; ?></a>
        <a href="#" id="lp_fakeButton" class="lp_js_doPurchase" style="display:none;" data-laterpay=""></a>
    </div>
</div>
