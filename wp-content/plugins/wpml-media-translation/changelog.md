# 2.1.24

## Fixes
* [wpmlmedia-110] Fixed an issue when deleting media and the "Organise my uploads into month- and year-based folders" option is unchecked
* [wpmlmedia-116] Set translation status when attachment is duplicated

# 2.1.23

## Fixes
* [wpmlmedia-57] Fixed an issue with the front-end navigation for attachments.

## Performances
* [wpmlmedia-102] Adapted the batch limit depending on active languages and introduced the `WPML_MEDIA_BATCH_LIMIT` constant to override this value.

# 2.1.22

## Fixes
* [wpmlcore-3030] Class auto loading is not compatible with version of PHP older than 5.3

## Performances
* [wpmlga-133] Improved class autoloading by using class mapping, instead of file system lookup

#2.1.21

##Fixes
* [wpmlcore-2897] Fixed duplication of featured image where the flag "Duplicate featured image to translations" was ignored

##Performances
* [wpmlmedia-90] Fixed constant duplication of media when editing the source page
* [wpmlcore-2988] Removed unneeded dependencies checks in admin pages: this now runs only once and later only when activating/deactivating plugins

#2.1.20

##Fixes
* [wpmlmedia-81] Fixed language filter issue in Media Library section.

#2.1.19

##Fixes
* [wpmlmedia-76] Resolved "Not Found" errors in front-end for attachments in secondary languages
* [wpmlmedia-75] Improved upload process in WP-Admin for images uploaded in secondary languages 

##Performances
* [wpmlcore-2528] Cached calls to `glob()` function when auto loading classes

##Cleanup
* [wpmlcore-2541] Removal of "icon-32" usage

# 2.1.17

##Fixes
* Added backward compatibility for `__DIR__` magic constant not being supported before PHP 5.3.

# 2.1.16

## Fixes
* [wpmlga-96] WordPress 4.4 compatibility: pulled all html headings by one (e.g. h2 -> h1, he -> h2, etc.)

# 2.1.15

## Fixes
* [wpmlmedia-72] Fixed a potential issue which may happen when third party plugins tries to duplicate an attachment which doesn't exists

# 2.1.14

## New
* Updated dependency versions

## Fix
* [wpmltm-830] Fix duplication of featured image when using the translation editor
* [wpmlmedia-54] Fix issue when all secondary languages were hidden and Media translation page was not available
* [wpmlmedia-71] Don't try to make duplicate attachments if the original attachment has no language set (ie trid == null)

# 2.1.13

## New
* Updated dependency check module

# 2.1.12

## New
* Updated dependency check module

# 2.1.11

## New
* Updated dependency check module

# 2.1.9

## Fix
* Enabled media translation for files uploaded with XML-RPC

##Improvements
* Plugin dependency to core

# 2.1.7

## Compatibility
* Achieved compatibility with Media Library Assistant plugin

## Fix
* Media Translation now supports wildcard when querying for media by its mime type
* Fixed thumbnails of images in secondary language
* Added support for Trash in Media Library

# 2.1.6

## Compatibility
* Fixed compatibility with Types plugin - execution of save_post hooks respects now other plugins

## Fix
* Fixed issue with hundreds of duplicated images
* Fixed: Language_filter_upload_page() doesn't support multiple mime types and mime types with wildcards

# 2.1.5

## Improvements
* New way to define plugin url is now tolerant for different server settings

## Fix
* Fixed media item list in different languages: if some plugin adds its own parameter to URL, lang parameter was not concatenated correctly
* Removed references to global $wp_query in query filtering functions
* When you import posts, WPML created unnecessary media attachments. It is fixed now

# 2.1.4

## Fix
* Handled case where ICL_PLUGIN_PATH constant is not defined (i.e. when plugin is activated before WPML core)
* Fixed Korean locale in .mo file name

# 2.1.3

## Fix
* Handled dependency from SitePress::get_setting()
* Updated translations
* Several fixes to achieve compatibility with WordPress 3.9
* Updated links to wpml.org

# 2.1.2

## Performances
* Reduced the number of calls to *$sitepress->get_current_language()*, *$this->get_active_languages()* and *$this->get_default_language()*, to avoid running the same queries more times than needed

## Feature
* Added WPML capabilities (see online documentation)

## Fix
* Improved SSL support for included CSS and JavaScript files
