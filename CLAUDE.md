# IP Blacklist for Cloudflare

## Overview
Blacklists IP addresses via Cloudflare's firewall API when someone attempts to log in with a banned username. Designed to protect WordPress sites behind Cloudflare from brute-force attacks.

## Architecture

```
ip-blacklist-for-cloudflare.php    # Entry point, defines constants, instantiates CFIP_Blacklist
classes/
├── Plugin.php                     # CFIP_Blacklist - main controller
├── CloudflareAPIController.php    # CFIP_CloudflareAPIController - API requests
├── SiteSettings.php               # CFIP_SiteSettings - settings + credential management
└── Helpers.php                    # CFIP_Helpers - URL parsing
views/
└── SiteSettingsView.php           # Admin settings page template
```

## Key Classes

### CFIP_Blacklist (Plugin.php)
Main controller. Hooks into `wp_authenticate` to check usernames.

- `checkUserLoginName($username)` - Core logic: checks if username is banned, triggers blacklist
- `blacklistIPAddress()` - Gets user IP, calls Cloudflare API, stores in site option
- `singleSiteBlacklistIP($ip)` - Blacklists IP for the current site's zone
- `getUserIPAddress()` - Extracts IP from `$_SERVER` headers (supports proxies)
- `getBannedIPs()` / `updateBannedIPs()` / `removeBannedIP()` - CRUD for `cfip_banned_addresses` site option
- AJAX: `ajaxUnblacklistIP()`, `ajaxClearLog()`, `ajaxLoadLog()`

### CFIP_CloudflareAPIController (CloudflareAPIController.php)
Handles all Cloudflare API v4 communication.

- `request($method, $url, $body)` - Master HTTP method using `wp_remote_get`/`wp_remote_post`
- `blacklistIP($zone_id, $ip)` - POST to `/zones/{id}/firewall/access_rules/rules`
- `deleteBlacklistedIP($zone_id, $rule_id)` - DELETE from firewall rules
- `getZones()` - GET `/zones`
- `getZoneId($domain)` - Resolves domain to zone ID (caches in DB)

### CFIP_SiteSettings (SiteSettings.php)
- `areCredentialsSet()` - Checks for CF credentials (own or inherited from CF plugin)
- `isCloudflarePluginActive()` - Detects official Cloudflare plugin
- `isLoggingEnabled()` - Checks `enable_logging` setting

## Settings (wp_options)
- `cfip_settings` - API credentials, zone ID, banned usernames, feature flags
- `cfip_banned_addresses` - Site option storing blacklisted IPs with zone/rule data
- `cfip_log` - Debug log entries

## Testing
Tests are in `../tests/unit/cloudflare-ip-blacklist/`. Run with:
```bash
make test-plugin PLUGIN=cloudflare-ip-blacklist
```

## API Endpoints Used
- `GET /zones` - List zones
- `GET /zones?name={domain}` - Find zone by domain
- `POST /zones/{id}/firewall/access_rules/rules` - Create block rule
- `DELETE /zones/{id}/firewall/access_rules/rules/{rule_id}` - Delete block rule
