=== Cloudflare IP Blacklist ===
Contributors: MillerMediaNow, mikemm01
Tags: security, login, passwords, password, profile, users, brute force, username, banned usernames
Requires PHP: 5.6
Requires at least: 3.0
Tested up to: 5.3.2
Stable tag: 1.0.0
License: GPLv2

Automatically block IP addresses on Cloudflare for users attempting to use certain prohibited usernames when attempting to login.

== Description ==

Cloudflare provides security and optimization services for websites all around the internet. WordPress sites are often attacked by bots or hackers and, while Cloudflare has preset firewall rules to help, it doesn't always filter out all malicious activity.

Cloudflare IP Blacklist allows WordPress admins to add a list of prohibited usernames and if someone attempts to log in with one of these usernames, this users IP address is automatically added to the Cloudflare blacklist. By doing this, the next time this user attempts to load the site, they will be blocked by Cloudflare before their requests get to your site's server. Add an extra layer of security today to your site!

== Installation ==

1. Upload `Cloudflare IP Blacklist` to the `/wp-content/plugins/` directory
1. Activate the plugin through the _Plugins_ menu in WordPress
1. Configure plugin through admin menu _Cloudflare IP Blacklist_

== Request ==

If you find that a part of this plugin isn't working, please don't simply click the Wordpress "It's broken" button. Let us know what's broken in [its support forum](https://wordpress.org/support/plugin/cloudflare-ip-blacklist/) so we can make it better.

== Screenshots ==

1. Settings screen
2. Logging screen

== Changelog ==
= 1.0 =
* Initial public release

