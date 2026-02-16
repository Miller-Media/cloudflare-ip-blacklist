=== IP Blacklist for Cloudflare ===
Contributors: MillerMediaNow, mikemm01
Tags: login, password, passwords, security, profile
Tested up to: 6.9.1
Stable tag: 1.2.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Blacklist IP addresses that attempt to login with a banned username through Cloudflare.

== Description ==

Cloudflare provides security and optimization services for websites all around the internet. WordPress sites are often attacked by bots or hackers and, while Cloudflare has preset firewall rules to help, it doesn’t always filter out all malicious activity.

Cloudflare IP Blacklist allows WordPress admins to add a list of prohibited usernames and if someone attempts to log in with one of these usernames, this users IP address is automatically added to the Cloudflare blacklist. By doing this, the next time this user attempts to load the site, they will be blocked by Cloudflare before their requests get to your site’s server. Add an extra layer of security today to your site!

** This plugin relies on the use of Cloudflare, a third-party service that increases security and performance for web sites and services across the internet. For more information, visit the Cloudflare website and their privacy policy. This plugin is not officially endorsed, built or maintained by the Cloudflare team; rather, we are a development company that uses their services every day! **

== Localizations ==
This plugin is available in the following languages:

* Albanian (Shqip)
* Arabic (العربية)
* Armenian (Հայերեն)
* Basque (Euskara)
* Bengali (বাংলা)
* Bulgarian (Български)
* Catalan (Català)
* Chinese Simplified (简体中文)
* Croatian (Hrvatski)
* Czech (Čeština)
* Danish (Dansk)
* Dutch (Nederlands)
* Estonian (Eesti)
* Finnish (Suomi)
* French (Français)
* Galician (Galego)
* Georgian (ქართული)
* German (Deutsch)
* Greek (Ελληνικά)
* Hebrew (עברית)
* Hindi (हिन्दी)
* Hungarian (Magyar)
* Indonesian (Bahasa Indonesia)
* Irish (Gaeilge)
* Italian (Italiano)
* Japanese (日本語)
* Korean (한국어)
* Latvian (Latviešu)
* Lithuanian (Lietuvių)
* Macedonian (Македонски)
* Norwegian (Norsk)
* Persian (فارسی)
* Persian - Afghanistan (دری)
* Polish (Polski)
* Portuguese - Brazil (Português do Brasil)
* Portuguese - Portugal (Português)
* Romanian (Română)
* Russian (Русский)
* Serbian (Српски)
* Slovak (Slovenčina)
* Slovenian (Slovenščina)
* Spanish (Español)
* Swedish (Svenska)
* Tamil (தமிழ்)
* Thai (ไทย)
* Turkish (Türkçe)
* Ukrainian (Українська)
* Urdu (اردو)
* Vietnamese (Tiếng Việt)
* Welsh (Cymraeg)

== Frequently Asked Questions ==

= How do I set up my Cloudflare API credentials? =

Go to Settings > Cloudflare IP Blacklist in your WordPress admin. You can authenticate using either a scoped API Token (recommended) or your Global API Key (legacy). For API Tokens, create one in your Cloudflare dashboard with the "Account > Account Firewall Access Rules > Edit" permission.

= What happens when someone tries to log in with a banned username? =

Their IP address is automatically added to your Cloudflare firewall block list. On their next visit, Cloudflare blocks them before the request ever reaches your server, saving your server resources.

= Can I use an API Token instead of the Global API Key? =

Yes! As of version 1.0.5, scoped API Tokens are fully supported and recommended over the Global API Key. Create a token in your Cloudflare dashboard with the required firewall permissions.

= What PHP and WordPress versions are supported? =

The plugin requires PHP 8.1 or higher and has been tested up to WordPress 6.9.1.

= What languages are supported? =

The plugin is available in 30 languages with more being added regularly. We are working toward supporting 50 languages total!

== Changelog ==

= 1.2.0 =
* Added translations for 50 languages
* Added POT translation template file
* Added Settings link on Plugins page
* Updated FAQ section
* Updated readme with complete language list


= 1.1.1 =
* Added translations for Russian, Polish, Dutch, Turkish, and Swedish
* Updated localization section in readme

= 1.1.0 =
* Added Chinese Simplified (zh_CN) translation

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

