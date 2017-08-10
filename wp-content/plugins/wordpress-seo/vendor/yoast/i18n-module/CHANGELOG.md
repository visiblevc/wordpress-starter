# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.0.0

### Added
- Add native support for WordPress.org

### Changed
- [DEPRECATION] Postfix all classes with _v2. This prevents autoload collisions with 1.0 versions. 

## 1.2.1

### Removed
- Remove backwards incompatible changes. These are only backwards incompatible in a WordPress context, but this is the target audience.

## 1.2.0

### Added
- Add native support for WordPress.org

## 1.1.0

### Changed
- Use the user locale for deciding whether to display the message to the user.

## 1.0.1

### Fixed
- Fix issue where a notice would be shown if the API response had no wp_locale.

## 1.0.0

### Added
- Initial release of the i18n module to show the user a notice about their
language not having been translated to 100%.
