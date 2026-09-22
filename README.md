# Low Profile

Hides what identifies a WordPress install and closes the enumeration paths it leaves open by default. Settings → Low Profile, one switch per guard, everything on by default except the author-archive redirect.

`readme.txt` is the WordPress.org listing and the authority on what each guard does.

## What it covers

**Software and version**

- Removes the generator tag from the head and from feeds.
- Replaces `?ver=<version>` on core scripts and styles with an opaque token that still changes on every update. Plugin and theme assets keep their own version strings. The login page's bundled assets are unbundled so the same applies there.
- Removes the emoji detection script and its styles.
- Drops the `X-Powered-By` and `X-Pingback` headers.

**Discovery**

- Removes the REST API link tag and `Link:` header, the shortlink tag and header, oEmbed discovery links, the RSD and Windows Live Writer links, and the per-category feed links. The REST API keeps working; it is just not announced.

**Remote publishing**

- Refuses XML-RPC requests with a 403, including `system.listMethods` and pingbacks.
- Disables pingbacks: the methods go, and every post answers "closed" whatever its stored ping status.
- Disables application passwords, so REST clients cannot authenticate with basic auth.

**User enumeration**

- Hides `/wp-json/wp/v2/users` from visitors. Logged-in users keep it, so the block editor's author picker still works.
- Optionally redirects author archives, whose URLs contain the login name, to the home page. This runs before the canonical redirect, so `?author=1` never resolves to `/author/<login>/`.
- Leaves users out of the core sitemap.
- Strips the author name and URL from oEmbed responses.

**Login form**

- One message whether the username or the password was wrong. Applied on `authenticate`, so it covers a theme's own login form as well as wp-login.php, and leaves password-reset and registration messages alone.
- The lost-password form sends an unknown username or email address exactly where a known one goes, redirect target included.

**File editing**

- Disables the theme and plugin editors through WordPress's capability checks rather than by relying on `DISALLOW_FILE_EDIT`, so it holds on hosts whose generated `wp-config.php` sets that constant to false (WP Engine does). A configuration that already sets it to true is respected.

**Search engine indexing**

- When the site address ends with a non-production suffix such as `.wpenginepowered.com`, `.kinsta.cloud` or `.ddev.site`: `noindex, nofollow` on every page and as an `X-Robots-Tag` header on every WordPress response, including the login screen and wp-admin, a disallow-all `robots.txt`, and no sitemap. Files served directly by the web server need a server-level rule. Decided by hostname, so copying a database between staging and production cannot carry the wrong indexing state. The suffix list is editable.

## What it does not do

No login rate limiting, firewall, or HTTP security headers such as `Strict-Transport-Security`, `X-Frame-Options` or a Content Security Policy. Those belong to the host, CDN or web server. The plugin makes no network requests and stores a single option.

## Development

No build step. `low-profile.php` boots two classes: `LowProfile_Settings` (the option, defaults, sanitizer, screen) and `LowProfile_Guards` (the hooks, one method per guard). Adding a guard means a field definition in `LowProfile_Settings::fields()` and a branch in `LowProfile_Guards::boot()`.

Check against the WordPress.org guidelines before a release:

```bash
wp plugin install plugin-check --activate
wp plugin check low-profile
```

## Releasing

Bump the version in `low-profile.php` (header and `LOWPROFILE_VERSION`) and `readme.txt` (`Stable tag` and the changelog), tag, and publish a GitHub release. `.github/workflows/deploy.yml` pushes the tag to WordPress.org SVN using the `SVN_USERNAME` / `SVN_PASSWORD` repository secrets. `.distignore` keeps repo-only files out of the release.
