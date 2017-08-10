# 2.5.2

## Fixes
* [wpmltm-1602] Fixed the query which list items in the Translation Management dashboard
* [wpmlst-1047] Fixed exporting in po files duplicated strings with different contexts
* [wpmlst-1117] Removed feature for page builders that updates the translated post when the original post is updated
* [wpmlst-1009] Fixed the display of string tracking in source

## Features
* [wpmlst-1044] Grouped strings registered without context / domain

# 2.5.1

## Fixes
* [wpmlst-1014] Raised the order of calling the `wpml_translatable_user_meta_fields` filter to allow displaying translated user meta on the front-end
 
## API
* [wpmlst-1032] Add actions for cleaning up unused package strings `wpml_start_string_package_registration` and `wpml_delete_unused_package_strings`

# 2.5.0

## Fixes
* [wpmlst-907] Fixed an issue when trying to register a string with `0` as name
* [wpmlst-920] Fixed an issue in double registering of Multilingual Widget content
* [wpmlcore-3333] Fixed an issue that was happening when you try to scan strings before completing the wizard. Now it is not allowed
* [wpmlst-954] Fixed database error when running WPML reset
* [wpmlst-937] Fixed an issue when importing large `.po` files
* [wpmlst-988] Fixed issue with DEFAULT in Text fields for compatibility with MySQL 5.7
* [wpmlcore-3505] Add troubleshooting option `Recreate ST DB Cache tables` to re-run ST upgrade
* [wpmlst-996] Fix 'Create PO file' functionality so it includes the msgctxt when required
* [wpmlst-1028] Fix wrong table prefix when resetting WPML in multisite

## API
* [wpmlcore-3372] Added more API hooks including `wpml_add_string_translation`

## Performances
* [wpmlst-925] Improved usage of server's resources when scanning themes or plugins for strings
* [wpmlst-831] Improved page loading and memory consumption in Theme's and Plugin's localization page

## Features
* [wpmlst-955] Add support to translate shortcode strings used by page builders.

# 2.4.3

## Features
* [wpmlst-929] Updated package registration API to give option to connect package to post.   

# 2.4.2.1

## Fixes
* [wcml-1499] Problem with WooCommerce endpoints resolved
* [wpmlst-945] Fixed PHP notice when Views uses AJAX pagination.
* [wpmlst-946] Fixed PHP notice when "My Account" endpoint contains special chars

# 2.4.2

## Fixes
* [wpmlst-889] Sanitized the strings before registering them in String Translation tables.
* [wpmlst-896] Improved the detection of URLs used to store data in `icl_string_urls`.
* [wpmlst-919] Admin notices for strings scanning when a plugin is installed/updated can be permanently dismissed.

# 2.4.1.1

## Fixes
* [wpmlst-892] Moved table migration from Core plugin to String Translation, to avoid dependency issues
* [wpmlst-895] Fixed index in icl_string_pages table

# 2.4.1

## Fixes
* [wpmlcore-3278] Fixed illegal mix of collations between `icl_strings` and `icl_string_pages` tables 
* [wpmlst-881] Removed leading backslash `\` to avoid warnings in PHP <5.3
* [wpmlst-880] Fixed error appearing during plugin update
* [wpmlst-882] Improved handling of the the admin notice Something doesn't look right with "Caching of String Translation plugin"
* [wpmlst-886] Improved caching of strings per page, to not flood tables with duplicated data and not cause performance issues
* [wpmlst-888] Improved caching of strings per page, it requires less memory in db now.

# 2.4.0

## Fixes
* [wpmlst-836] Fixed getting translated string when icl_t is called directly after icl_register_string.
* [wpmlst-819] Improvement to ST performance, especially important where there are a lot of registered strings.  
* [wpmlst-825] New box in WPML > ST to exclude contexts from auto-registration. Currently all strings are auto-registered by default.
* [wpmlst-745] Keep track of which strings have links to content and fix the links to translated content in string translations
* [wpmlst-879] Fixed outdated check message.
* Other minor bug fixes and improvements

# 2.3.9

## Fixes
* [wpmlcore-3030] Class auto loading is not compatible with version of PHP older than 5.3

## Performances
* [wpmlga-133] Improved class autoloading by using class mapping, instead of file system lookup

#2.3.8

## Fixes
* [wpmlst-817] Fixed possible XSS issue in the taxonomy label translation.

## Performances
* [wpmlst-798] Added object caching and optimized code for getting translated strings
* [wpmlst-793] Added migration logic to reuse existing string translations if they exist
* [wpmlcore-2988] Removed unneeded dependencies checks in admin pages: this now runs only once and later only when activating/deactivating plugins

## Features
* [wpmlcore-2972] Minor improvement to how WPML fetch updated translations of WordPress strings.

#2.3.7

## Fixes
* [wpmlst-784] Don't show translated blog name and description on customize.php

# 2.3.7

## Fixes
* [wpmlst-696] Fixed issue when blog title or tagline was empty
* [gfml-64] Fixed empty string check in `WPML_Displayed_String_Filter::translate_by_name_and_context` function
* [wpmlst-724] Fixed issue with theme scanned on each access to WPML > Plugin and theme localization
* [wpmlst-744] Fixed error when scanning themes/plugins for strings on localhost on Windows
* [wpmlst-751] Fixed filtering rewrite rules for WP 4.5 when the rules are cleared
* [wpmlst-759] Fixed filtering of translation jobs when selecting a from language
* [wpmlst-756] Fixed wpml_translate_string filter to not filter arrays if they are passed as the string value
* [wpmlst-762] Fixed a fatal error that could occur on reactivation of the plugins after a reset

## Features
* [wpmlst-706] Author biographical info is now translatable in string translation

## API
### Filters
* [wpmlcore-2676] Added a new hook "wpml_translatable_user_meta_fields"

# 2.3.6.1

## Fixes
* Fix dependency check issue ("WPML Update is incomplete" notice)

# 2.3.6

## Fixes
* [wpmlst-695] Fix performance issue when checking fo sticky links plugin

## Performances
* [wpmlcore-2528] Cached calls to `glob()` function when auto loading classes

## Cleanup
* [wpmlcore-2541] Removal of "icon-32" usage

# 2.3.5

## Fixes
* [wpmlst-685] Fixed incorrect filtering of rewrite rules when permalink bases contains strings which are used in other permalink patterns
* [wpmlst-694] Fixed an issue preventing widget strings and site titles from being registered as translatable strings on new installations
* [wpmlst-349] Fixed encoding of special characters in the Search field

# 2.3.4

## Fixes
* Added backward compatibility for `__DIR__` magic constant not being supported before PHP 5.3.

# 2.3.3

## Fixes
* [wpmlga-96] WordPress 4.4 compatibility: pulled all html headings by one (e.g. h2 -> h1, he -> h2, etc.)

## Performances
* [wpmlst-584] Improved cache flushing in Packages Translations, solving the `PHP Fatal error: Call to undefined method WP_Object_Cache::__get()` message WPEngine users were getting

# 2.3.2

## Fixes
* [wpmlst-668] Removed the wrongly shown "Products slugs are set to be translated, but they are missing their translation" message
* [wpmlst-676] Resolved the "Fatal error: Class 'WPML_WPDB_User' not found" message when deactivating WPML-Core with WPML-ST still active
* [wpmltm-959] Escape html in Post titles displayed in admin messages
* [wpmlst-478] Fixed nested wildcards for setting admin options translatable were not working
* [wpmlst-681] Fix warning in Layouts if there are no languages to translate to
* [wpmltm-972] Improved the repairing of broken database states implemented for Translation Polling, to cover more cases
* [wpmltm-992] Disabled admin notice about missing PHP required setting, when not in a WPML page, and if the `ICL_HIDE_TRANSLATION_SERVICES` constant is not set to true. Also added the ability to hide that notice.

# 2.3.1

## Performances
* [wpmlst-656] Improved the time to import WP translations after clicking on "Review changes and update"
* [wpmlst-670] Improved the cache warming performances, when display a string right after registering it (reuse the strings cache between string filters)

# 2.3.0

## Feature
* [wpmlst-471] Allow icl_register_string to register a string in any language
* [wpmlst-474] Added the package language to the url to the translation dashboard (this applies to the Package box, where used by other plugins like Layouts)
* [wpmlst-475] Add a language selector to the package metabox (eg. as seen on the Layout editor)
* [wpmlst-482] Add a language selector to the Admin bar menu to set the language of a package (eg. as seen on GravityForms)
* [wpmlst-505] Add support for sending strings in any language to the translation basket

## Fixes
* [wpmlst-630] Fixed a glitch causing the registration of WPML-ST strings when scanning a theme
* [wpmlst-619] The WPML language selector properly shows language names in the right language
* [wpmlst-426] Footer in emails are now shown in the right languagegit pull --renaasas
* [wpmlst-483] Fixed registering of strings with gettext_contexts
* [wpmlst-547] Improved handling of strings by WP Customizer when default language is other than English
* [wpmlst-572] Fixed broken HTML in Auto Register Strings
* [wpmlcore-2259] Fixed translation of taxonomy labels when original is not in English
* [wpmlst-655] Fixed domain name fallback to 'default' or 'WordPress' for gettext context strings
* [wpmlst-664] Fixed ssue translating slugs for WooCommerce product base
* Other minor bug fixes

## API

### Filters
* [wpmltm-684] `wpml_element_translation_job_url`

# 2.2.6

## Fixes
* [wpmlst-469] Solved `Warning: in_array() expects parameter 2 to be array, null given`
* [wpmlst-432] Clear the current string language cache when switching languages via 'wpml_switch_language' action

## Performances
* [wpmlst-462] Fixed too many SQL queries when the user's administrator language is not one of the active languages
* [wpmlst-460] Fixed `icl_register_string` to reduce the number of SQL queries
* [wpmlst-467] Improve performance of string translation
* [wpmlst-461] Improved performance with slug translation

# 2.2.5
* Fixed performance issue with string translation when looking up translations by name

# 2.2.4

## Fixes
* Solved the "Invalid argument supplied for foreach()" PHP warning
* Fixed a typo in a gettext string

# 2.2.3

## Fixes
* Fixed issues translating widget strings
* Fixed a problem with slug translation showing translated slug for English on Multilingual Content Setup when Admin language is other than English
* Fixed slug translation so it works with the default permalink structure
* Fixed caching problem with admin texts which caused some admin texts to not update correctly
* Removed `PHP Fatal error: Specified key was too long; max key length is 1000 bytes` caused by `gettext_context_md5`
* Fixed string scanning issues
* Fixed slug translations so that they are not used when they are disabled
* Fixed Auto register strings for translation
* Fixed admin texts so the settings are loaded from the default language and not the administrator's language
* Fixed fatal error when an old version of WPML is active
* Fixed an issue where wrong translations were displayed for strings registered by version 2.2 and older if the database contained the same string value for different string names
* Replaced deprecated constructor of Multilingual Widget for compatibility with WP 4.3

## New
* Support multi-line strings when importing and exporting po files
* Support gettext contexts in string translation
* Updated dependency check module

## API
* New hooks added (see https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/)
	* Filters
		* `wpml_get_translated_slug` to get the translated slug for a custom post type

# 2.2.2

## Fixes
* Resolved problem with "removed" strings

## New
* Updated dependency check module

# 2.2.1

## New
* Updated dependency check module

# 2.2.0

## Fixes
* Improved handling of strings with names or contexts longer than the DB field lengths
* Fixed PHP errors and notices
* Fixed custom menu item translation problems
* Fixed custom post type slug translation issues when cpt key is different to slug and when has_archive is set to a string
* Fixed admin text string registration from admin and wpml-config.xml

## Improvements
* Fixed plugin dependency to the core
* Performance improvements

## API
* Improved API and created documentation for it in wpml.org

# 2.1.3

## Fixes
* Fixed issues with broken URL rewrite and translatable custom post types

# 2.1.2

* Works with WPML 3.1.9.4 on WordPress 4

# 2.1.1

* Additional fixes to URLs with non-English characters

# 2.1.0

* Security update

# 2.0.14

## Fixes
* Fixed a menu synchronisation issue with custom links string

# 2.0.13

## Fixes
* Fixed an issue that prevented _n and _nx gettext tags from being properly parsed and imported.

# 2.0.12

## Fixes
* Fixed 'translate_string' filter which now takes arguments in the right order and returns the right value when WPML/ST are not active

# 2.0.11

## Fixes
* Removed PHP Warnings during image uploading

# 2.0.10

## Improvements
* Speed improvements in functions responsible for downloading and scanning .mo files.
* Added support for _n() strings

## Fixes
* Fixed fatal error when bulk updating plugins
* Removed infinite loop in Appearance > Menu on secondary language when updating menus
* Fixed: when user was editing translated post, admin language changed to this language when he saved. 

# 2.0.9

## Fixes
* The previously fixed dependency bug still didn't cover the case of String Translation being activate by users before WPML and was still causing an issue, making the plugin not visible. This should be now fixed.

# 2.0.8

## Fixes
* Fixed dependency bug: plugin should avoid any functionality when WPML is not active

# 2.0.7

## Improvements
* New way to translate strings from plugins and themes: being on plugin/theme configuration screen, switch language using switcher in admin bar and provide translation.

## Compatibility
* "woocommerce_email_from_name" and "woocommerce_email_from_address" are translatable now

## Fixes
* Removed PHP notices


# 2.0.6

## Improvements
* New way to define plugin url is now tolerant for different server settings

## Fixes
* Minor syntax fixes
* Fixed possible SQL injections
* If string data was stored as serialized array, indexed by numbers, position 0 was not displayed on front-end. 
* Fixed issues with caching values in icl_translate()
* WordPress sometimes displayed wrong blog name when configured in multi site mode. It is also fixed. 


# 2.0.5

## Fixes
* Fixed Slug translation issues leading to 404 in some circumstances
* Support for gettext strings with ampersand in context name
* Updated links to wpml.org
* Updated issues about WPDB class, now we do not call mysql_* functions directly and we use WPDB::prepare() in correct way
* Handled case where ICL_PLUGIN_PATH constant is not defined (i.e. when plugin is activated before WPML core)
* Removed closing php tags + line breaks, causing PHP notices, in some cases and during plugin activation
* Fixed typos when calling in some places _() instead of __()
* Fixed Korean locale in .mo file name

# 2.0.4

## Fixes
* Fixed issue translating strings when default site language is not "English"
* Fixed locale for Vietnamese (from "vn" to "vi")
* Updated translations
* Removed attempts to show warning when in the login page
* Replace hardcoded references of 'wpml-string-translation' with WPML_ST_FOLDER

# 2.0.3

## Fixes
* Handled dependency from SitePress::get_setting()

# 2.0.2

## Performances
* Reduced the number of calls to *$sitepress->get_current_language()*, *$this->get_active_languages()* and *$this->get_default_language()*, to avoid running the same queries more times than needed
* No more queries when translating strings from default String Translation language, when calling l18n functions (e.g. __(), _x(), etc.)

## Feature
* Added WPML capabilities (see online documentation)

## Fixes
* Fixed bug in slug translation when the slug is empty
* Removed html escaping before sending strings to professional translation
