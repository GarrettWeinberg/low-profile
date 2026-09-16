# Low Profile

Hides what identifies a WordPress install and closes the enumeration paths it leaves open by default. Settings → Low Profile, one switch per guard, everything on by default except the author-archive redirect.

`readme.txt` is the WordPress.org listing and the authority on what each guard does.

## Development

No build step. `low-profile.php` boots two classes: `LowProfile_Settings` (the option, defaults, sanitizer, screen) and `LowProfile_Guards` (the hooks, one method per guard). Adding a guard means a field definition in `LowProfile_Settings::fields()` and a branch in `LowProfile_Guards::boot()`.

Check against the WordPress.org guidelines before a release:

```bash
wp plugin install plugin-check --activate
wp plugin check low-profile
```

## Releasing

Bump the version in `low-profile.php` (header and `LOWPROFILE_VERSION`) and `readme.txt` (`Stable tag` and the changelog), tag, and publish a GitHub release. `.github/workflows/deploy.yml` pushes the tag to WordPress.org SVN using the `SVN_USERNAME` / `SVN_PASSWORD` repository secrets. `.distignore` keeps repo-only files out of the release.
