=== LaterPay ===

Contributors: laterpay, dominik-rodler, avahura
Tags: laterpay, extension, migrate, subscriptions
Requires at least: 3.5.2
Tested up to: 4.4.2
Stable tag: trunk
Author URI: https://laterpay.net
Plugin URI: https://github.com/laterpay/laterpay-wordpress-subscriber-migrator
License: MIT
License URI: http://opensource.org/licenses/MIT

Migrate subscribers from other payment services to LaterPay. (Extension for the LaterPay WordPress plugin version 0.9.11.3 or greater).


== Description ==

The LaterPay migrator allows you to migrate existing subscribers from other payment services to LaterPay.

We recommend the following high-level process for the migration:

- Install the LaterPay plugin on your site (https://wordpress.org/plugins/laterpay/)
- Install the LaterPay migrator plugin on your site (this plugin here). It will show up in the LaterPay plugin backend as tab 'Migration'.
- Configure the LaterPay plugin; make sure that you
    - Give all roles used for subscribers unlimited access to paid content in the LaterPay advanced settings ('LaterPay' page of the WordPress settings)
    - Configure corresponding time passes for your subscriptions in the 'Pricing' tab of the LaterPay plugin
- Switch the LaterPay plugin into live mode in the 'Account' tab of the LaterPay plugin
- Deactivate your subscription plugin so that new subscriptions or renewals are not possible anymore
- Export the subscriber data from your subscription service
- Import the subscriber data as CSV file in the 'Migration' tab of the LaterPay plugin
- Configure and start the migration process in the 'Migration' tab of the LaterPay plugin

The LaterPay migration plugin will then render an eye-catching bar at the top of the screen for every active subscriber.

It prompts the subscriber to switch to a free LaterPay time pass for the remaining duration of their subscription.
If a subscriber has not yet switched, the plugin sends an email 14 days before the subscription expires with a request to switch to a free LaterPay time pass for the remaining duration of the subscription.
If a subscriber has not yet switched, the plugin sends an email at the end of the day on which the subscription expires with a request to switch to a free LaterPay time pass for the remaining duration of the subscription.

When a subscriber switches to a LaterPay time pass or when the subscription expires, the user role that gives him unlimited access to all paid content will be removed from him.
From then on, access to paid content requires a LaterPay time pass or an individual purchase of that content.


== Installation ==

- Upload the LaterPay Subscriber Migrator plugin on the ‘Install Plugins’ page of your WordPress installation
  (/wp-admin/plugin-install.php?tab=upload) and activate it on the ‘Plugins’ page (/wp-admin/plugins.php).
  The plugin will show up in the admin sidebar with a callout pointing at it.
- Click on the LaterPay entry in the admin sidebar and go to the 'Migration' tab to configure the migration process.

The plugin will notify you about available updates that you can install with a single click.


== Frequently Asked Questions ==

= From which subscription solutions can this plugin migrate subscribers to LaterPay? =
This plugin has been tested with DigiMember, but should work for a range of subscription services.

= Which version of the LaterPay WordPress plugin is required? =
You have to have installed version 0.9.11.3 or greater of the LaterPay WordPress plugin, because the ability to extend the LaterPay WordPress plugin with plugins like this here has been added in version 0.9.11.3.


== Screenshots ==

1. Empty state after installation of migrator plugin
2. Active migration process


== Changelog ==

= 1.0 (April 2, 2015): Initial Release =
* Added functionality to upload a CSV file with subscriber data
* Added functionality to notify existing subscribers about the upcoming migration via a sitenotice bar
* Added functionality to notify existing subscribers about the upcoming migration via emails sent with MailChimp
