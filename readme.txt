=== IP Blacklist for Cloudflare ===
Contributors: MillerMediaNow, mikemm01
Tags: login, password, passwords, security, profile
Tested up to: 6.9.1
Stable tag: 1.0.9
Requires PHP: 8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Blacklist IP addresses that attempt to login with a banned username through Cloudflare.

== Description ==

Cloudflare provides security and optimization services for websites all around the internet. WordPress sites are often attacked by bots or hackers and, while Cloudflare has preset firewall rules to help, it doesn’t always filter out all malicious activity.

Cloudflare IP Blacklist allows WordPress admins to add a list of prohibited usernames and if someone attempts to log in with one of these usernames, this users IP address is automatically added to the Cloudflare blacklist. By doing this, the next time this user attempts to load the site, they will be blocked by Cloudflare before their requests get to your site’s server. Add an extra layer of security today to your site!

** This plugin relies on the use of Cloudflare, a third-party service that increases security and performance for web sites and services across the internet. For more information, visit the Cloudflare website and their privacy policy. This plugin is not officially endorsed, built or maintained by the Cloudflare team; rather, we are a development company that uses their services every day! **

== Changelog ==

= 1.0.9 =
* Added Japanese (ja) translation

= 1.0.8 =
* Tested up to WordPress 6.9.1

= 1.0.7 =
* Added dismissible review prompt notice after 14 days of usage

= 1.0.6 =
* Added translations for Spanish, French, German, Portuguese (Brazilian), and Italian

= 1.0.5 =
* Added support for scoped API tokens as an alternative to Global API Key
* Added connected state UI with credential status bar and masked credential preview
* Added Disconnect action to easily switch authentication methods
* Added internationalization (i18n) support for all user-facing strings
* Labeled Global API Key as Legacy, recommending API Token for new installs
* Added required Cloudflare token permissions in API Token field description

= 1.0.4 =
* Added output escaping to settings page
* Scoped nonce verification to plugin settings page only
* Removed unnecessary error suppression on nonce verification

= 1.0.3 =
* Improved setup instructions for Cloudflare API credentials

= 1.0.2 =
* Compatibility updates for WordPress 6.9 and PHP 8.1+
* Fixed PHP 8.2 dynamic property deprecation warnings

= 1.0.1 =
* Initial public release

