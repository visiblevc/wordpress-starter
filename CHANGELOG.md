# Changelog

This changelog is for the Docker image. There might be changes to the gulpfile that are not listed here.

## 0.12.0 - latest
- This project now maintains 2 separate `Dockerfile`, depending on the version of PHP you'd like to have installed.

**Available Dockerfiles**:

| PHP Version | Tags |
| ----------- | ---- |
| **7.0**     | `latest` `latest-php7.0` `0.12.0` `0.12.0-php7.0` |
| **5.6**     | `latest-php5.6` `0.12.0-php5.6` |

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
