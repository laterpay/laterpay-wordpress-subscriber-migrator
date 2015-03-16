<?php

class LaterPay_Migrator_Mail {

    /**
     * Notify users about their subscriptions expired
     *
     * @return array $expired_subscriptions
     */
    public static function notify_subscription_expired() {
        $expired_subscriptions = LaterPay_Migrator_Subscription::get_expired_subsriptions();
        if ( $expired_subscriptions ) {
            foreach ( $expired_subscriptions as $subscription ) {
                // notify user that his subscription expired
                self::send_subscription_expired_notification( $subscription );
                // TODO: remove expired subscription from table or mark as migrated??
                LaterPay_Migrator_Subscription::mark_as_migrated_to_laterpay( true );
            }
        }

        return $expired_subscriptions;
    }

    /**
     * Notify user that his subscription expired already
     *
     * @return [type] [description]
     */
    public static function send_subscription_expired_notification( $data ) {
        // TODO: implement mail sending
    }

    /**
     * Notify users about their subscriptions about to expiry ( 2 weeks )
     */
    public static function notify_subscription_about_to_expiry() {
        $subscriptions = LaterPay_Migrator_Subscription::get_subscriptions_by_date();
        if ( $subscriptions ) {
            foreach ( $subscriptions as $subscription ) {
                // notify user that his subscription about to be expired
                self::send_subscription_expiry_warning_notification( $subscription );
            }
        }

        return $subscriptions;
    }

    /**
     * Send notification email to the subscriber if their subscription about to expiry
     *
     * @return [type] [description]
     */
    public static function send_subscription_expiry_warning_notification( $data ) {
        // TODO: implement mail sending
    }
}
