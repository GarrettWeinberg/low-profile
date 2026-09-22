=== Low Profile ===
Contributors: garrettweinberg
Tags: security, hardening, xml-rpc, user enumeration, privacy
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hides what identifies your WordPress install and closes the enumeration paths it leaves open by default. One settings page of on/off switches.

== Description ==

A stock WordPress site tells the world its version, lists every user's login name over the REST API, confirms which usernames exist on the login form, and advertises XML-RPC, oEmbed and its API root in every response. None of that is needed for the site to work. Low Profile turns it off.

Every guard is a single switch on **Settings → Low Profile**, and everything is on by default except the author-archive redirect, which changes what visitors can reach.

**Software and version**

* Removes the generator tag from the head and from feeds.
* Replaces `?ver=<version>` on core scripts and styles with an opaque token that still changes on every update, so caches are busted without naming the version. Plugin and theme assets keep their own version strings. The login page's bundled assets, which normally carry the version too, are unbundled so the same applies there.
* Removes the emoji detection script, one of the most recognisable WordPress fingerprints.
* Drops the `X-Powered-By` and `X-Pingback` headers.

**Discovery**

* Removes the REST API link tag and `Link:` header, the shortlink tag and header, oEmbed discovery links, the RSD and Windows Live Writer links, and the per-category feed links. The REST API keeps working; it just is not announced.

**Remote publishing**

* Refuses XML-RPC requests with a 403. (`xmlrpc_enabled` alone leaves `system.listMethods` and pingbacks answering; this closes the whole file.)
* Disables pingbacks: the methods go, and every post answers "closed" whatever its stored ping status.
* Disables application passwords, so REST clients cannot authenticate with basic auth.

**User enumeration**

* Hides `/wp-json/wp/v2/users` from visitors. Logged-in users keep it, so the block editor's author picker still works.
* Optionally redirects author archives, whose URLs contain the login name, to the home page. This runs before WordPress's own canonical redirect, so `?author=1` goes home rather than to `/author/<login>/`.
* Leaves users out of the core sitemap.
* Strips the author name and URL from oEmbed responses.

**Login form**

* One message whether the username or the password was wrong, instead of WordPress confirming which usernames exist. The wording is editable. It is applied where authentication happens, so it covers a theme's own login form as well as wp-login.php, and leaves password-reset and registration messages alone.
* The lost-password form sends an unknown username to the same "check your email" screen a known one gets, so it no longer confirms which accounts exist.

**File editing**

* Disables the theme and plugin editors in wp-admin. This is done through WordPress's capability checks rather than by relying on `DISALLOW_FILE_EDIT`, so it holds on hosts whose generated `wp-config.php` sets that constant to false (WP Engine does). A configuration that already sets it to true is respected.

**Search engine indexing**

* When the site address ends with a non-production suffix such as `.wpenginepowered.com`, `.kinsta.cloud` or `.ddev.site`: `noindex, nofollow` on every page and as an `X-Robots-Tag` header on every response, a disallow-all `robots.txt`, and no sitemap. Because this is decided by hostname rather than by the "discourage search engines" option, copying a database between staging and production can never carry the wrong indexing state with it. The suffix list is editable.

= What it deliberately does not do =

Low Profile does not rate-limit logins, add a firewall, or set HTTP security headers such as `Strict-Transport-Security`, `X-Frame-Options` or a Content Security Policy. Those belong to your host, CDN or web server, where they apply whether or not WordPress renders the page. Use it alongside whatever your host provides, not instead of it.

= No tracking, no external requests =

The plugin makes no network requests and stores a single option.

== Installation ==

1. Install and activate the plugin.
2. Visit **Settings → Low Profile**. Everything sensible is already on; review the two guards that depend on your site: author archives and application passwords.

== Frequently Asked Questions ==

= Will this break the block editor? =

No. The REST API stays available. Only its advertisement is removed, and the users endpoint is hidden from visitors, not from logged-in users.

= I'm logged in, but /wp-json/wp/v2/users in my browser says "no route". =

That is expected. A REST request made straight from the address bar carries your cookie but not the nonce WordPress requires alongside it, so the API treats it as anonymous. The editor and any properly authenticated client still see the endpoint.

= I use the API anonymously with ?_embed. =

The embedded `author` object comes back as a 404 error object instead of the author, because that is the users endpoint being hidden. Turn off **Hide the REST users list** if a public client needs it.

= My site has author pages. =

Leave **Redirect author archives** off. It is off by default for that reason.

= Something authenticates against the REST API with an application password. =

Turn off **Disable application passwords**.

= I publish from the WordPress mobile app or through Jetpack. =

Turn off **Disable XML-RPC**.

= Does this replace a security plugin? =

It replaces the "hide WordPress" and "stop user enumeration" parts of one, without the rest. Brute-force protection and a firewall are different jobs.

= Does it work on multisite? =

It works per site. There is no network-level settings screen in this version.

== Changelog ==

= 1.0.1 =
* The file-editor guard now works on hosts that define DISALLOW_FILE_EDIT as false in their generated wp-config.php, such as WP Engine. It previously deferred to the constant, which cannot be redefined, and left the editors open there.

= 1.0.0 =
* Initial release.
