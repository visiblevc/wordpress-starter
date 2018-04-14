# Changelog

## 0.18.0 - latest

**BREAKING CHANGE:** `URL_REPLACE` environment variable now only accepts `after_url`, rather than `before_url,after_url`. This will gracefully fix itself if it encounters the old format for this version only, but will break on subsequent versions.

## Minor
- Create and use a non-root user (admin) with passwordless sudo priviledges, rather than using the root user.
- Plugins and themes can now be space-separated (preferred) or comma-separated (legacy).
- Readdress #110 to print out logs in a similar, but greatly simplified, format.
- Install and remove themes and plugins in parallel.
- Greatly simplify build pipeline.

## 0.17.0

### Minor
- Add PHP 7.2 (`latest`, `latest-php7.2`, `0.17.0-php7.2`)
- Add `DB_USER` config option to environment variables

## 0.16.0
- **BREAKING CHANGE:** Builds will now exit if any plugin or theme installs fail.
- Improve logging.

## 0.15.2

### Fixes
- **REALLY** fix error causing volumed plugins and/or themes to be deleted.
- Move `.dockercache` out of index directory so it can't be accessed directly.

## 0.15.1

### Fixes
- Fix error causing volumed plugins to be deleted.
- Always flush `.htaccess` to add rewrite rules back in the event a plugin modified it (e.g. `w3-total-cache`).

### Other
- Add `wp-cli` bash completions to the wordpress container.

## 0.15.0

### Deprecations

**Note:** Both deprecations will still work as usual until the next release cycle.

#### `[local]plugin-name` & `[local]theme-name` syntax

The build process is now aware of locally-volumed plugins and themes automatically.

Additionally, listing locally-volumed plugins and themes in your `docker-compose.yml` file is optional; you may list them if you'd like to keep your compose file declarative, or you may skip listing them completely. The build will complete the same either way.

#### `SEARCH_REPLACE` has been renamed to `URL_REPLACE`

We chose to rename this because, although you may search and replace strings that are not URLs, the build process requires them to be.

This name reflects that requirement better and will lead to less confusion down the road.

### `VERBOSE` environement variable

Logging has been changed to show necessary information by default.

### Improvements
- Add `php7.1` base image.
- Widespread efficiency improvements to build process.
- Reduce the number of Dockerfile layers.
- If `SERVER_NAME` is specified (eg. `example.com`), create a `ServerAlias` in the apache configs for `www.example.com`.

### Fixes
- Plugins and themes are only pruned if they meet the following criteria (Closes #51):
  - Not listed in `docker-compose.yml`.
  - Not added as a local volume.
- Adjust permissions of volumed files and directories so that they remain editable outside the container, and remain secure. (Closes #12)
- Fix critical security vulnerability. HT @joerybruijntjes.

## 0.14.0
- Add `SERVER_NAME` variable to allow for quickly setting that directive in the apache config for users interested in running in production.

## 0.13.0
- Add `certbot` into the image to allow for easily obtaining and renewing SSL certificates

## 0.12.2
- Add missing `exif` php extension to handle WordPress media upload metadata parsing.
- Bump default base theme to `twentyseventeen`

## 0.12.1
- This project now maintains 2 separate `Dockerfile`, depending on the version of PHP you'd like to have installed.

**Available Dockerfiles**:

| PHP Version | Tags |
| ----------- | ---- |
| **7.0**     | `latest` `latest-php7.0` `0.12.1` `0.12.1-php7.0` |
| **5.6**     | `latest-php5.6` `0.12.1-php5.6` |

## 0.11.0
- Add `WP_VERSION` environment variable which allows users to specify which version of WordPress is installed.

## 0.10.1
- fix: Add default permalink structure (`/%year%/%monthnum%/%postname%/`). Closes #42.

## 0.10.0

- BREAKING CHANGE: Explicitly listing all plugin/theme requirements (including those located in volumed directories) required.
- Refactor run.sh into functions
- Add new URL plugin format to better check existence after initial build.
- Add new URL theme format to better check existence after initial build.
- Add `VERBOSE` environment variable option which allows the build to run verbosely if one chooses.
- Prettier logging.
- Add `--activate` after plugin installs so that all plugins start out activated.
- Many other improvements

## 0.9.0

- Add `DB_HOST` environment variable - Fix #46
- Various bug fixes and improvements

## 0.8.0

- Add `DB_PREFIX` optional param - Fix #37
- Only create .htaccess for non-multisite installations - Fix #32
- Various bug fixes and improvements

## 0.7.0

- Support Wordpress Multisite - Fix #33
- Fix a typo in the gulpfile

## 0.6.0

- Update wp-cli
- `gulp clean` now also clears generated templates
- Support for `WP_DEBUG_LOG` and `WP_DEBUG_DISPLAY` options

## 0.5.0

- Clear any apache pid file before starting apache

## 0.4.0

- Add a script to run wp-cli via npm
- Recreate the `wp-cli.yml` and `wp-config.php` every time to make sure they are up to date.

## 0.3.0

- Remove all the scripts
- Consolidate and improve the `run.sh`
- Update the documentation

## 0.2.0
