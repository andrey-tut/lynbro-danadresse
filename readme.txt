=== Lynbro Danadresse ===
Contributors: lynbro
Tags: address autocomplete, denmark, dawa, woocommerce, address validation
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.4.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Typo-tolerant Danish address autocomplete & validation that autofills WooCommerce and every major form builder — powered by danadresse.dk.

== Description ==

Lynbro Danadresse Autofill adds fast, typo-tolerant **Danish address autocomplete** to your whole site — the WooCommerce checkout, My Account address forms, and the address field of every major form builder — then **validates delivery addresses** so parcels stop bouncing because of a mistyped street or postal code.

It is the official WordPress companion for [danadresse.dk](https://danadresse.dk), a DAWA-compatible Danish address API. If you previously used DAWA (dawadocs.dataforsyningen.dk), the suggestion format and address IDs are the same.

= Works everywhere — automatically =

One widget, many plugins, through built-in **integration adapters** that know each plugin's fields. Composite address fields also fill **postcode and city** for you:

* **WooCommerce** — classic checkout, the **Checkout Block**, and My Account (and any WooCommerce extension that uses the standard checkout — Subscriptions, Bookings, Memberships…).
* **WPForms** — the Address field.
* **Gravity Forms** — the Address field.
* **Fluent Forms** — the Address field.
* **Formidable Forms** — the Address field.
* **Forminator** — the Address field.
* **Everest Forms** — the Address field.
* **Easy Digital Downloads** — the checkout billing address.
* **GiveWP** — the donation billing address.
* **Paid Memberships Pro** — the membership checkout billing address.
* **Ultimate Member** — the profile / registration address field.
* **Contact Form 7, Ninja Forms, Elementor** — auto-detect an address field, or point the plugin at it with a CSS selector.
* **Any theme or custom form** — a CSS selector, a `data-lynbro-danadresse` attribute, the `[lynbro_danadresse_field]` shortcode, or the "Danish address field" block.

Turn each integration on or off from a simple **Integrations** screen that marks which target plugins are active on your site.

= Highlights =

* **Typo-tolerant** — "radhuspladsen" finds "Rådhuspladsen"; oe↔ø, aa↔å, ae↔æ folding included.
* **Address validation (datavask)** — on WooCommerce checkout, Danish delivery addresses are washed against the register; the result (exact / close / not recognised) is saved on the order and shown to the shop admin. Optionally block clearly invalid addresses.
* **Privacy-sound by design** — your API key stays on the server; visitors' browsers never talk to the address service, and no visitor IP is forwarded by the plugin.
* **Accessible** — the suggestion list is a proper ARIA combobox with full keyboard support.
* **Light** — one small script + style, loaded only where an address field exists.

= You can see what it is doing =

No black box. The plugin's own screens answer the questions a shop owner actually asks:

* **Status** — is the key working? Run a live test lookup without leaving WordPress.
* **Statistics** — how many lookups this site made, day by day, for the last 30 days.
* **Log** — the last failures and what each one means in plain words ("Key blocked — unlock it in the danadresse.dk dashboard"). Only technical facts are kept; the address text a visitor typed is never stored.
* **Languages** — which translations WordPress has installed for your site.
* **Tools** — export or reset the plugin's settings.

= What you need =

A free API key from [danadresse.dk](https://danadresse.dk) (2,000 lookups/month on the free tier — enough for most small shops). Larger plans are available on danadresse.dk; the plugin itself is fully functional and free.

= For developers =

* `lynbro_danadresse_integrations` — add or modify an integration adapter.
* `lynbro_danadresse_result` — filter the suggestion list server-side.
* `lynbro_danadresse_washed` — action fired after each address validation.
* `lynbro_danadresse_attach_selectors` — filter the CSS selectors the widget attaches to.
* `lynbro_danadresse_api_base` — point the client at a self-hosted mirror.

== External Services ==

This plugin talks to **one** external service: the danadresse.dk address API (`https://api.danadresse.dk`), operated by LynBro ApS, Denmark.

* **What is sent:** the address text a visitor types into an autofill-enabled field (plus your API key, sent server-to-server), and — when address validation is enabled — the delivery address entered at checkout. Requests are proxied through your server; the visitor's browser never contacts the service and no visitor IP is forwarded by the plugin.
* **When:** only while a visitor types in an address field, when an order with a Danish delivery address is placed (if validation is enabled), or when you run a test lookup in the settings.
* **Terms:** https://danadresse.dk/terms — **Privacy:** https://danadresse.dk/privacy

No other external requests are made. Without an API key configured, the plugin makes no external requests at all.

**WordPress.org plugin directory (the shared Lynbro "Our plugins" screen).**
Apart from the above, one request goes to WordPress.org, from every Lynbro plugin
alike: when an administrator who may install plugins opens Lynbro → Our plugins,
the screen asks the WordPress.org Plugin Directory API
(https://api.wordpress.org/plugins/info/1.2/) which Lynbro plugins are listed,
through WordPress core's own `plugins_api()` — the same call the Plugins → Add New
screen makes.

* **What is sent:** the query (plugins by the author "lynbro"), plus what WordPress
  core adds to every request to WordPress.org: your WordPress version, the admin
  language and, in the User-Agent, your site address. Nothing about your visitors,
  users or content.
* **When:** only on that screen, at most once a day (the answer is cached for a day;
  after a failed request, an hour). No other screen and no visitor request triggers it.
* Installing a suggested plugin uses WordPress core's own installer, which downloads
  the plugin from downloads.wordpress.org — only when you click "Install now".
* WordPress.org [Terms of Service](https://wordpress.org/about/terms-of-service/) ·
  [Privacy Policy](https://wordpress.org/about/privacy/)

== Installation ==

1. Install and activate the plugin.
2. Create a free API key at danadresse.dk and paste it under **Lynbro → Danadresse → Settings**.
3. Open the **Integrations** tab. WooCommerce and every detected form builder are on by default — toggle any you don't want. For a form without a dedicated adapter, add its CSS selector, use the shortcode/block, or turn on Auto-detect.

== Frequently Asked Questions ==

= Which form plugins are supported? =

WooCommerce (classic + Checkout Block + My Account), WPForms, Gravity Forms, Fluent Forms, Formidable Forms and Easy Digital Downloads have built-in adapters that also fill postcode and city. Contact Form 7, Ninja Forms, Elementor and any custom form attach through auto-detect, a CSS selector, the shortcode or the block.

= Do I need an API key? =

Yes — a free danadresse.dk key gives 2,000 lookups/month, enough for most small shops. The key is stored on your server and never sent to visitors.

= Is this DAWA? =

The API is DAWA-compatible (same autocomplete and datavask semantics and the same address IDs), operated by danadresse.dk. Existing DAWA integrations migrate without changing IDs.

= What happens if the service is down or my quota is used up? =

Autocomplete quietly disappears and forms keep working as plain text fields. Address validation never blocks checkout when the service is unreachable.

= Which data is stored in my database? =

Your settings, a local 30-day lookup counter, a short list of recent errors (time, kind of lookup, error code — never the address text), short-lived response caches, and — when validation is enabled — the validation result on each order. Nothing else.

= Does the plugin download a copy of the Danish address register? =

No. Every suggestion is a live lookup against the service, so you always get current data and your database stays small. There is no import step and nothing to keep in sync.

= Is it translated into Danish? =

Yes. Translations are served by translate.wordpress.org and installed by WordPress itself, so they stay current without a plugin update. Both the admin screens and the suggestion list follow whatever language WordPress is running in; the **Languages** tab shows what is installed on your site.

= Where do I see whether it is actually working? =

**Lynbro → Danadresse → Status** runs a live test lookup through your key. **Statistics** shows daily lookup counts from this site, and **Log** lists recent failures with a plain-language explanation of each.

== Screenshots ==

1. Autocomplete on the WooCommerce checkout — one address, typed once.
2. Settings — paste your danadresse.dk API key and choose how suggestions behave.
3. Integrations — toggle autofill for 14 plugins; the ones active on your site are marked.
4. Status — a live test lookup, so you can confirm the key works without leaving WordPress.
5. Statistics — daily lookups from this site over the last 30 days.
6. Log — recent failures in plain words; no address text is ever stored.
7. Languages — what WordPress has installed for your site, nothing to configure.
8. About — what the plugin does, how it handles data, and the external service it uses.
9. How it works — one address, typed once, autofilled into checkout, form and membership fields.

== Changelog ==

= 0.4.5 =
* Readme: External Services now names the one WordPress.org request the shared "Our plugins" screen makes — what is sent, when, and the WordPress.org terms and privacy policy.
* The plugin names and descriptions on "Our plugins" and in the suggestions are translatable.
* Links on Lynbro screens use the darker brand green, which meets WCAG AA contrast on the admin background.

= 0.4.4 =
* Shared Lynbro admin 1.1.0: after two weeks of use and a first success (for example settings saved), this plugin's own screen asks once, politely and without stars, whether it is doing its job. Answers are per user: "Maybe later" or "Don't ask again", and only one Lynbro plugin asks in any 30 days.
* A small "Suggested for this site" block under the plugin's main screen lists Lynbro plugins that fit what the site already uses (WooCommerce, forms, a Danish setup …), with the reason. It can be hidden; nothing installs on its own and nothing is sent anywhere.
* Tab labels on Lynbro screens meet WCAG AA contrast.
* Lynbro admin screens now read the plugin's bundled translations (languages/) when WordPress has no language pack for it, so a Danish admin sees Danish instead of English.

= 0.4.3 =
* Fix: on sites with a page cache, address autocomplete stopped answering once the cached page was older than a day (the security token in the page had expired). The field now fetches a fresh token from your site when that happens and repeats the lookup, so every lookup is still checked.

= 0.4.2 =
* The Tools screen can no longer be pointed at another plugin's settings. It used to send the list of options to export or reset along with the request, and any name starting with "lynbro" was accepted — so on a site running several Lynbro plugins, one plugin's Tools form could read or delete another's settings. The list now comes from code, and each screen's form is signed for that screen alone.

= 0.4.1 =
* The public address-lookup endpoint now requires a valid nonce on every request. It previously fell back to accepting any request that claimed to come from this site, which a Referer header can claim for free — so the endpoint, which spends your API quota, was effectively open. On a page cached longer than the nonce lifetime the field simply behaves as ordinary text.

= 0.4.0 =
* Nothing from this plugin appears outside its own screens any more. The review request moved onto the settings page, and the redirect that used to hijack the browser after activation is gone — the Settings link on the plugins row does the same job without moving you.
* Translations now come from translate.wordpress.org, the way WordPress installs them for every plugin, instead of riding along in the package. They stay current without waiting for a plugin update, and the Languages screen reports what is actually installed on your site rather than what shipped in the zip.

= 0.3.1 =
* Fixes WooCommerce autofill being off while the Integrations screen showed it as enabled. The front end was gated on a leftover option from before the integration toggles existed, which could disagree with the checkbox you actually see.
* Fixes turning an integration off having no effect. A disabled toggle is now respected.

= 0.3.0 =
* New **Statistics** screen: daily lookup counts from this site over the last 30 days, so you can see your own consumption without opening the danadresse.dk dashboard.
* New **Log** screen: the last failures with a plain-language explanation of each. It records only the time, the kind of lookup and the error code — never the address a visitor typed.
* New **Languages**, **Feedback** and **About** screens, so what the plugin does with your data and which external service it uses are visible inside WordPress, not only in the readme.
* A single, dismissible review request that appears once, after a week of actual use, and never again.

= 0.2.4 =
* Fixes address validation flagging valid deliveries as unrecognised. The floor/door part of the address is no longer sent to the validator — with it, a perfectly good address scored "not recognised", which shops using "block checkout on unrecognised address" would have turned into lost orders.

= 0.2.3 =
* A blocked API key now points the administrator to the danadresse.dk dashboard, which is the only place it can be unlocked. The previous in-plugin "Re-bind this site" button relied on a request header the service has since removed for security, so it would have looked like it worked while changing nothing.

= 0.2.2 =
* Backs off on the per-minute rate-limit window, which is the one address autocomplete actually reaches — the monthly counter still shows plenty left at that moment. Plans without a per-minute cap are unaffected.

= 0.2.1 =
* Follows the danadresse.dk integration guide to the letter: writes `forslagstekst` (never the raw `tekst`, which carries an empty segment by DAWA convention), drills into an access address for floor/door and fills it anyway when the building has no units, and backs off on the reported rate-limit budget instead of running into 429s.

= 0.2.0 =
* Adapters for GiveWP, Forminator, Everest Forms, Paid Memberships Pro and Ultimate Member — 14 integrations in total.
* Sends a stable per-site device id, so the address service no longer sees admin, REST and cron requests as different devices.
* Recognises the service's machine-readable error codes and explains exactly what to do; adds a one-click "Re-bind this site" recovery when a key was blocked.
* Never caches a response longer than the service says it stays fresh.
* Fills correctly for addresses that have no floor/door level.

= 0.1.0 =
* Initial release. Universal Danish address autofill with built-in adapters for WooCommerce (classic + Checkout Block + My Account), WPForms, Gravity Forms, Fluent Forms, Formidable Forms and Easy Digital Downloads; auto-detect + CSS-selector + shortcode + block for everything else; address validation (datavask) with order badge; ARIA combobox widget; server-side key proxy with caching and rate limiting.
