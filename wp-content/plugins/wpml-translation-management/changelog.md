# 2.2.7

## Fixes
* [wpmltm-1599] Fixed an issue when saving the translation pickup mode
* [wpmltm-1580] Fixed an issue when translating forms created with Gravity Forms containing HTML elements
* [wpmltm-1571] Fixed stripped backslash in the translation editor fields
* [wpmltm-852] Fixed email notification informing about new string translation jobs
* [wpmltm-1613] Fixed issue where flag icons were sometimes displayed incorrectly when viewing "All Languages"

## Performances
* [wpmltm-1576] Improved performance of translation management dashboard.

# 2.2.6

## Fixes
* [wpmltm-1587] Fixed issue where multiple lines got stripped when copying all fields in the Translation Editor
* [wpmlcore-3784] Fixed compatibility issue with ACF Pro causing fatal error for translatable field groups 

# 2.2.5

## Fixes
* [wpmltm-1555] Fixed a console error in translation basket when sending batches to an unsupported language
* [wpmltm-1528] Clear batch when rolling back to avoid duplicatd jobs
* [wpmltm-1566] Prevent sending empty units in XLIFF files 

# 2.2.4.1

## Fixes
* [wpmltm-1554] Removed ACF compatibility classes: they will be part of the ACF bridge plugin (ACFML: wpmlbridge-17)

# 2.2.4

## Fixes
* [wpmltm-1549] Fix fatal error in user account when user is translator

# 2.2.3

## Fixes
* [wpmltm-1504] Fixed the link to "Custom fields translation".
* [wpmltm-1434] Fixed the validation of zipped XLIFF files, when they are made from Mac OS.
* [wpmltm-1524] Fixed a fatal error when using "Advanced Custom Fields" plugin and saving a group with no fields in it.
* [wpmltm-1525] Fixed an issue with the multi-site configuration, when a sub-site admin can't access post translations.
* [wpmltm-1529] Extra fields received from Translation Proxy are properly rendered.

## Performances
* [wpmlcore-3227] Reduced the number of queries in the post listing pages.

# 2.2.2

## Fixes
* [wpmlcore-3104] Fixed compatibility issue with ACF repeated fields
* [wpmltm-1381] Fixed word count for translatable custom fields.
* [wpmltm-1489] Resolved a compatibility issue with a change in `WP_Http` introduced in WP 4.6
* [wpmlcore-3104] ACF Repeater subfields are now visible on edit post screen after downloading translated content with Translation Management
* [wpmlcore-2637] Add `wpml_translate_link_targets` filter to fix links to point to translated content
* [wpmltm-1175] Fix missing links in other posts and strings when translations return via pro translation
* [wpmltm-1467] Fixed NextGen Gallery compatibility issue.
* Other minor bug fixes

## Features
* [wpmltm-1241] Added button in Translation Dashboard to refresh language pairs defined in Translation Proxy
* [wpmltm-744] Improved messages when sending jobs to Translation Proxy fails
* [wpmltm-1204] Added UI to Multilingual Content Setup Tab to allow to scan the whole site for links that need fixing.
* [wpmltm-1487] Added a button in WPML > TM > Translators to refresh translators data from ICanLocalize

# 2.2.1.2
 
## Feature
* [wpmltm-1487] Added button to refresh data from ICanLocalize

# 2.2.1.1
 
## Fixes
* [wpmltm-1487] Reduced automatic calls to ICanLocalize server to one per hour

# 2.2.1

## Fixes
* [wpmlcore-3030] Class auto loading is not compatible with version of PHP older than 5.3

## Performances
* [wpmlga-133] Improved class autoloading by using class mapping, instead of file system lookup

# 2.2.0

## Fixes
* [wpmltm-1212] The post edit link was wrongly disabled with specific language pairs
* [wpmlcore-2899] Replaced use of `$HTTP_RAW_POST_DATA` with `php://input`
* [wpmltm-1134] The upper "Apply" button in WPML -> Translations (Translations Queue) now works as expected
* [wpmltm-1339] The "Check all" checkbox in WPML -> Translations (Translations Queue) page now selects all jobs
* [wpmltm-1391] The date in "Last time translations were picked up" now displays the actual time stamp of the last pickup
* [wpmltm-1390] Don't display the export XLIFF section on the Translations Queue page when the user doesn't have any translation languages

## Features
* [wpmltm-1189] Implement new design for translation editor.
* [wpmltm-1094] Add ability to set the 'field type' to wpml-config.xml filter
* [wpmlcore-2774] Implement new design for taxonomy translation.
* [wpmltm-1179] Translation Management now logs messages exchanged with the translation service
* [wpmlcore-2773] Added ability to export all/filtered jobs in WPML -> Translations (Translations Queue)

## Performances
* [wpmlcore-2988] Removed unneeded dependencies checks in admin pages: this now runs only once and later only when activating/deactivating plugins

## Usability
* [wpmltm-1442] Improved feedback message when sending jobs to a translation service fails
* [wpmltm-1408] Improved the admin notice when the XLIFF is missing the `target` element, or the element is empty

## Performances
* [wpmlcore-2988] Removed unneeded dependencies checks in admin pages: this now runs only once and later only when activating/deactivating plugins

# 2.1.7

## Fixes
* [wpmltm-1348] JS syntax error in WP 4.5 (related to new jQuery version)

# 2.1.6

## Fixes
* [wpmltm-1166] Replaced issue of deprecated function get_currentuserinfo for compatibility with WordPress 4.5
* [wpmltm-1177] Fixed issue where translated XLIFFs with '0' as content were not accepted from Translation Management
* [wpmlst-736] Fixed `Fatal error: Uncaught exception 'InvalidArgumentException' with message 'Tried to load a string filter for a non-existent language'`
* [wpmltm-1098] Handle exception raised when trying to enable XML-RPC but it is unavailable
* [wpmltm-1138] Added option to disconnect multiple duplicates (bulk mode) in TM Basket before sending for translation.
* [wpmltm-1160] Fix links to translation editor.
* [wpmltm-1213] Fixed issue with incorrect redirections after translation save/update and wrong post/page edit links.
* [wpmltm-1257] Reduce number of DB queries on listing pages.
* [wpmltm-1256] Fixed broken translation jobs display when "no results" were rendered previously.
* [wpmltm-1212] Fixed wrong post edit link for translator when lang_from and lang_to are equals

## Features
* [wpmltm-1215] Hide system fields when displaying custom fields and terms meta
* [wpmltm-1026] Added `external-file` in XLIFF files, to allow third party services to access the original URL.

# 2.1.5

## Fixes
* [wpmltm-1154] Fixed issues in possible database inconsistencies when choosing to cancel all local translation jobs after activating a translation service

## Performances
* [wpmlcore-2528] Cached calls to `glob()` function when auto loading classes

## Cleanup
* [wpmlcore-2541] Removal of "icon-32" usage

# 2.1.4

## Feature
* [wpmlcore-538] Added an informative message to promote WCML when WooCoomerce is installed but WCML is not
  
# 2.1.3

## Fixes
* Added backward compatibility for `__DIR__` magic constant not being supported before PHP 5.3.

# 2.1.2

## Fixes
* [wpmlga-96] WordPress 4.4 compatibility: pulled all html headings by one (e.g. h2 -> h1, he -> h2, etc.)
* [wpmltm-811] Fixed an UI issue in several admin pages with checkboxes being wrongly aligned
* [wpmltm-966] Fixed some UI issues caused by changes in WordPress 4.4 styles

# 2.1.1

## Fixes
* [wpmlst-668] Fix message in dashboard about missing slug translations
* [wpmltm-970] Fix issue with message for missing php settings/extensions not hiding
* [wpmltm-959] Escape html in Post titles under the Translation jobs page
* [wpmltm-1008] Fixed an issue causing users that were translators but did not have administrator capabilities to not be able to access Translation Management functionality on sites that were set to only have hidden secondary languages.

## Performances
* [wpmltm-963] Calculation of words count is now done through multiple AJAX calls and with proper progress feedback

# 2.1.0

# Features
* [wpmlst-505] Add support for sending strings in any language to the translation basket
* [wpmltm-688] Lost connections between translations jobs in WPML and TP due to rolling back a site from a backup are now repaired automatically in many cases
* [wpmltm-783] Added action in Translation Jobs tab, to trigger translation download for batches
* [wpmltm-777] Added words count feature in Translation Dashboard
* [wpmltm-931] Added check for required php ini settings for allow_url_fopen and php_openssl extension

## Fixes
* [wpmlcore-2212] Password-protected posts and private status are properly copied to translations, when this setting is enabled
* [wpmltm-736] Notes to translators are sent again
* [wpmltm-880] Fix so that post format is synchronized as required
* [wpmltm-928] Fixed count of documents in WPML Dashboard widget
* [wpmltm-924] Fixed issue of Translation Jobs listing when String translations is not activated

## API

### Filters:
* [wpmltm-801, wpmltm-797] `wpml_is_translator`
* [wpmltm-801, wpmltm-800] `wpml_translator_languages_pairs`

### Actions
* [wpmltm-801, wpmltm-799] `wpml_edit_translator`

### Performances
* [wpmlcore-1347] Improved multiple posts duplication performances

# 2.0.5

## Fixes
* [wpmltm-714] WPML won't activate Translation Service if the project has not been created in TP. This fix is also in preparation of the migration for ICanLocalize users.

# 2.0.4

## Features
* [wpmltm-787] Allow to completely disable translation services from appearing in the translators tab by setting the `ICL_HIDE_TRANSLATION_SERVICES` constant

# 2.0.3

## Fixes
* Translation Editor now shows existing translated content, if there was a previous translation
* Translation Editor won't changes language pairs for translators anymore
* Titles for packages and posts won't get mixed up in the translation jobs table anymore
* Users set as translators can translate content again, using the translation editor, even if there is not a translation job created for that content
* An editor can translate content if he's set as a translator

# 2.0.2

## New
* Updated dependency check module

# 2.0.1

## New
* Updated dependency check module

# 2.0.0

## New
* Handle translation jobs in batches/groups
* Select other translation services for professional translation
* Now, shortcodes are not considered in the estimation of the number of words of post content 
* Translation Analytics and XLIFF plugins are now embedded into Translation Management (some features might be disabled until the next version)

## Performances
* Improved performances
* General improvements in the quality of the JavaScript and PHP code

## Fixes
* Fixed PHP warning on the Add translator screen when no Translation Service was set yet
* Fixed checkbox validation in Translation Editor
* Fixed issues with translations when switching from Translation editor to WordPress editor
* Fixed SQL error when using Professional translation
* Fixed wrong category assignment when translating via the Translation editor

# 1.9.8

## Fixes
* Fixed a style issue with the "View Original" link of Translation Jobs table

# 1.9.7

## Improvements
* Support for string translation packages 
* Removed PHP warning when in Translation Dashboard and only one language is defined. Replaced with an admin notice

## Fixes
* Fixed issue with in proper notices in Translation Editor when user tries to translate document which was assigned to another user before
* Fixed issue with "Copy from" in Translation Editor 
* Fixed multiple issues with translation of hierarchical taxonomies

# 1.9.6

## Improvements
* Compatibility with WPML Core

# 1.9.5

## Improvements
* New way to define plugin url is now tolerant for different server settings
* Support for different formats of new lines in XLIFF files

## Fixes
* Fixed possible SQL injections
* When you preselect posts with status "Translation Complete" on WPML > Translation Management dashboard, it show wrong results. This is fixed now

# 1.9.4

## Improvements
* Defining global variables to improve code inspection

## Fixes
* Removed notice after "abort translation"
* Updated links to wpml.org
* Fixed Translation Editor notices in wp_editor()
* Handled case where ICL_PLUGIN_PATH constant is not defined (i.e. when plugin is activated before WPML core)
* Fixed Translation Editor - Notice: wp_editor() and not working editors in WP3.9 (changes for additional fields)
* Fixed not working "Copy from..." links for Gravity forms fields
* Fixed Korean locale in .mo file name

# 1.9.3

## Fixes
* Handled dependency from SitePress::get_setting()
* Changed vn to vi in locale files
* Updated translations
* Replace hardcoded references of 'wpml-translation-management' with WPML_TM_FOLDER

# 1.9.2

## Performances
* Reduced the number of calls to *$sitepress->get_current_language()*, *$this->get_active_languages()* and *$this->get_default_language()*, to avoid running the same queries more times than needed

## Features
* Added WPML capabilities (see online documentation)

## Fixes
* Improved SSL support for CSS and JavaScript files
