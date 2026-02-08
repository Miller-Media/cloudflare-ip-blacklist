=== IP Blacklist for Cloudflare ===
Contributors: MillerMedia,mikemm01
Tags: login, password, passwords, security, profile
Tested up to: 6.9
Stable tag: 1.0.2
Requires PHP: 8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Blacklist IP addresses that attempt to login with a banned username through Cloudflare.

== Description ==

Cloudflare provides security and optimization services for websites all around the internet. WordPress sites are often attacked by bots or hackers and, while Cloudflare has preset firewall rules to help, it doesn’t always filter out all malicious activity.

Cloudflare IP Blacklist allows WordPress admins to add a list of prohibited usernames and if someone attempts to log in with one of these usernames, this users IP address is automatically added to the Cloudflare blacklist. By doing this, the next time this user attempts to load the site, they will be blocked by Cloudflare before their requests get to your site’s server. Add an extra layer of security today to your site!

** This plugin relies on the use of Cloudflare, a third-party service that increases security and performance for web sites and services across the internet. For more information, visit the Cloudflare website and their privacy policy. This plugin is not officially endorsed, built or maintained by the Cloudflare team; rather, we are a development company that uses their services every day! **

== Changelog ==

= 1.0.2 =
* Compatibility updates for WordPress 6.9 and PHP 8.1+
* Fixed PHP 8.2 dynamic property deprecation warnings

= 1.0.1 =
* Initial public release

