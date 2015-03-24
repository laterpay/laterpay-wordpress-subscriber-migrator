=== LaterPay ===

Contributors: laterpay, dominik-rodler
Tags: laterpay, extension, migrate, subscriptions
Requires at least: 3.5.2
Tested up to: 4.1.1
Stable tag: trunk
Author URI: https://laterpay.net
Plugin URI: https://github.com/laterpay/laterpay-wordpress-subscriber-migrator
License: MIT
License URI: http://opensource.org/licenses/MIT

Migrate existing subscribers from other services to LaterPay. This is an extension for the LaterPay WordPress plugin. It requires the LaterPay WordPress plugin > v0.9.11.2.


== Description ==
XXX


== Installation ==

# Upload the LaterPay Subscriber Migrator plugin on the ‘Install Plugins’ page of your WordPress installation
  (/wp-admin/plugin-install.php?tab=upload) and activate it on the ‘Plugins’ page (/wp-admin/plugins.php).
  The WordPress plugin will show up in the admin sidebar with a callout pointing at it.
# Click on the LaterPay entry in the admin sidebar and go to the 'Migration' tab to configure the migration process.

The plugin will notify you about available updates that you can install with a single click.


== Screenshots ==

1. Empty state
2. Migration state

== Changelog ==

= 1.0 (March 26, 2015): Initial Release =
* Added functionality to upload a CSV file with subscriber data
* Added functionality to notify existing subscribers about the upcoming migration via a sitenotice bar
* Added functionality to notify existing subscribers about the upcoming migration via emails sent with MailChimp
