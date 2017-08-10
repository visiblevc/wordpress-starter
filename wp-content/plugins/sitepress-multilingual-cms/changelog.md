# 3.6.3

## Fixes

* [wpmlcore-3411] Fixed some glitches for dropdown language switchers
* [wpmlcore-3853] Fixed `SitePress::get_term_adjust_id` to retain the `object_id` if needed. Fixes missing WC product variations
* [wpmlcore-3847] Fixed error on front-end when using WPML in Menus but with incomplete setup
* [wpmlcore-3839] Fixed not saved settings when assigning a language switcher to a menu with a name in an encoded language (Hebrew, Chinese, ...)
* [wpmlcore-3866] Fixed some compatibility issues for language switcher in menus
* [wpmlcore-3007] Fixed title sanitization when German (de_DE) and Danish (da_DA) languages are present
* [wpmlcore-3837] Fixed path returned by `get_home_path` when in the second language and using languages in domains
* [wpmlcore-3860] Fixed default locale for some affected languages
* [wpmlcore-3885] Fixed domain in the `content_url` function to match the current domain URL
* [wpmlcore-3766] Applied some changes in WPML cookies: `wpml_referer_url` will be set on WCML and `_icl_current_language` will be set only if user is logged in
* [wpmlcore-3427] Fixed redirection from URL with no language directory to URL with default language directory (applies with root page setup)
* [wpmlcore-3889] Fixed fatal error during WPML setup (step 2) if not possible to connect to WP translation API
* [wpmlmedia-114] Fixed retrieving gallery's images after changing the default language
* [wpmlcore-3876] Added page builder requirements message for Divi theme
* [wpmlcore-3892] Fixed post relationship for Types plugin when saving posts
* [wpmlcore-3883] Fixed issue where translations can get disconnected when editing a duplicate post
* [wpmlcore-3893] Fixed issue where an auto-draft draft is being assigned as a translation
* [wpmlcore-3903] Fixed issues for retrieving object terms in a language different than the request language (related to `wp_get_object_terms`
* [wpmlcore-4035] Fixed issue when updating any WPML user field from other user 
* [wpmlcore-4042] Fixed issue in WPML wizard language not matching site language on fresh install
* [wpmlcore-4142] Fixed issue in user admin language when trying to set a language which is not an active WPML language

## Features
* [wpmlcore-3597] WPML now scans for language switcher templates in the uploads folder
* [wpmlcore-3510] Introduced a new generic shortcode for language switcher `wpml_language_switcher` 
* [wpmlcore-3886] Downloading and using `wpml-config.xml` for parent theme when available 

## Compatibility
* [wpmlcore-4185] Removed Avada page builders integration.
* [wpmlcore-3910] Resolved compatibility issues with 'Twenty Seventeen` theme and translation of Theme Options
* [wpmlcore-3937] Fixed issue with Enfold's page builder and new string translation workflow
* [wpmlcore-3943] Fixed issue with single sign-on when Object Cache is enabled using W3TC
* [wpmlcore-3797] Resolved JS conflict with ACF Pro, affecting WYSIWYG fields

## Security
* [wpmlcore-3773] Several security fixes

## API
### Filters
* [wpmlcore-3913] Added `wpml_duplicate_generic_string` in some locations to replace deprecated `icl_duplicate_generic_string`

# 3.6.2

## Fixes
* [wpmlcore-3809] Updating serialized CF that are set to 'copy' won't causes extra rows in the DB
* [wpmlcore-3807] Notifications for translating a page builders can now be dismissed
* [wpmlcore-3703] Language switcher settings are now accessible from mobiles
* [wpmlcore-3803] Restored option for setting editing language as admin language
* [wpmlcore-3772] Resolved issue with users not staying logged in across domains when using different domains per language
* [wpmlcore-3789] Fixed compatibility issue with Yoast SEO and BuddyPress causing an "Uncaught exception"
* [wpmlcore-3415] Fixed conflict of Language Switcher with multiple queried taxonomies in WooCommerce layered navigation
* [wpmlcore-3738] Fixed issue where in some installations, the Language Switcher was missing from the secondary language menu 
* [wpmlcore-3758] Fixes issue with Sticky header appearing in the Wizard when using Chrome
* [wpmlcore-3130] Fixed issue where duplicated posts were having the "Needs update" status
* [wpmlcore-3776] Fixed compatibility issue with Enfold causing "Warning: ILLEGAL STRING OFFSET 'LANGUAGE_CODE'"
* [wpmlcore-3701] Clearing language switcher colors was not updating the preview
* [wpmlcore-3770] Some menus were not available to assign a language switcher
* [wpmlcore-3731] Resolved notice when passing an associative array of post types to `WP_Query`
* [wpmlcore-3774] Prevent `Exception` when the cache directory is not created or not writable
* [wpmlcore-3796] In the language switcher dialog, the save button was disabled after clicking on it with no selected slot

# 3.6.1

## Fixes
* [wpmlcore-3763] Fixed trying to get property of non-object issue when wp_rerite wasn't set
* [wpmlcore-3759] Fixed fatal error `Uncaught exception ‘InvalidArgumentException’`
* [wpmlcore-3750] Default custom language switcher options are now initialized after the setup wizard
* [wpmlcore-3765] Language switcher in menu is properly shown when the source language of the menu is different than the default language
* [wpmlcore-3769] Fixes possible issues when `glob()` returns `false`

## Usability
* [wpmlcore-3735] Disabled the Save button in language switcher settings after clicking it

# 3.6.0

## Fixes
* [wpmlcore-3204] Fixed a compatibility issue with Yoast redirections
* [wpmlcore-3363] Fixed an issue with the browser redirection when using languages with regional variations (e.g. fr-CA).
* [wpmlcore-3311] Fixed multisite install compatibility issue with WP 4.6+ using `sunrise`
* [wpmlcore-3266] On multisite, the main site settings are not altered anymore during sub-site setup
* [wpmlcore-3361] Fixed a login redirection issue in sites with languages configured as domain
* [wpmlcore-2968] Fixed a redirection issue Nginx servers with language configured as domain
* [wpmlcore-2849] Fixed issue with cloning non object values for wp_query object.
* [wpmlcore-3421] Fixed page template synchronization if template is set to default
* [wpmlcore-3199] Removed Snoopy class and use WP_Http class instead.
* [wpmlcore-2565] Fixed a compatibility issue with `get_option('siteurl')` in sites with languages set as domain
* [wpmlcore-3434] Fixed WPML_Backend_Request::get_ajax_request_lang
* [wpmlcore-3420] Fixed \SitePress::_sync_custom_field to handle serialized data.
* [wpmlcore-3374] Removed the restriction of only English as default language when synchronizing WP menu
* [wpmlcore-3441] Fixed issue in the WPML notice when adding posts from frontend 
* [wpmlcore-3404] Fixed issue when duplicating posts that have comments to be duplicated
* [wpmlst-887] Fixed issue while dismissing admin notices from plugin that contains special chars in the title
* [wpmlcore-3438] Fixed issue with setting static blog page when page is permanently deleted
* [wpmlcore-3471] Fixed compatibility issue when using Google PageSpeed Tools in Apache or Nginx
* [wpmlcore-3451] Prevent double ampersand encoding in language switcher URL
* [wpmlcore-3479] Fixed issue where language information wasn't saved when posts were created via AJAX call
* [wpmlcore-2289] The language switcher in Twenty Sixteen's footer was cut off
* [wpmlcore-1869] The language switcher in Twenty Fifteen's footer was partially hidden by the sidebar
* [wpmlcore-2884] In some cases (taxonomy, author, date and post type archives), the language switcher custom link for empty translation was not displayed
* [wpmlcore-3494] Removed admin user language feature in sites running WordPress 4.7 or higher and included migration logic while upgrading WP. This is now integrated to WordPress itself.
* [wpmlcore-3497] Adjusted URL in wp_upload_dir return for when WPML is set up as domain
* [wpmlcore-3544] Automatically download WordPress mo files for active languages
* [wpmlcore-3682] Fix handling of slashes when copying custom fields

## Usability
* [wpmlcore-3325] Changed the message style in Sync Field Functionality
* [wpmlcore-3399] Added a warning about missing menu items when only one language is configured in the site

## Features
* [wpmlcore-1448] Redesign of the language switcher (new admin UI, new API)

# 3.5.3.1

## Fixes
* [wpmlcore-2535] Fixed a compatibility issue with `bloginfo('stylesheet_url')` when languages is set as domain
* [wpmlcore-3392] Fixed performance issue with SSO when language per domain is configured.
* [wpmlcore-2375] Fixed an URL inconsistency when using `get_page_link`

# 3.5.3

## Fixes
* [wpmlcore-3366] Update language switcher color picker validation to support rgb and transparent option 

# 3.5.2

## Fixes
* [wpmlcore-3244] Fixed an issue with the re-initialization of WPML in Network install, after resetting settings of main sub-site.
* [wpmlcore-2663] Fixed an issue with the browser redirection when using languages in directories.
* [wpmlcore-2682] Fixed an issue with the browser redirection when using languages with regional variations (e.g. fr-CA).
* [wpmlcore-3009] Fixed duplicated language querystring argument when redirecting to a child page.
* [wpmlcore-3310] Post meta won't return an empty value after automatic post meta synchronization is ran.
* [wpmlcore-3334] Fixed some issues when translating **from a non-default language** and MO files are used for gettext strings (introduced in WP 4.6).
* [wpmltm-1532] Ensure the correct source language is used when sending translation jobs to Translation Proxy.
* Other minor bug fixes.

## API

### Filters
* [wpmlcore-3160] Added `wpml_is_translated_taxonomy`.

### Actions
* [wpmlcore-3316] Added `wpml_user_switch_language`.

## Performances
* [wpmlcore-3227] Reduced the number of queries in the posts listing pages.
* [wpmlcore-2692] Added caching for Twig templates.

## Features
* [wpmlcore-3320] Users can now login across domains when WPML is configured to use languages per domain (it uses the "window.postMessage" web API).

## Usability
* [wpmlcore-3006] Improved usability of the admin notice "Term hierarchy synchronization".
* [wpmlcore-1101] Added a spinner and disabled buttons in the WPML installation wizard.

# 3.5.1.1

# Fixes
* [wpmlcore-3303] Removed updating of ST DB Cache tables: moved to ST plugin, to avoid dependency issues

# 3.5.1

# Fixes
* [wpmlcore-3280] Fixed `Fatal error: Uncaught exception 'InvalidArgumentException' with message 'Argument ID must be numeric and greater than 0` when filtering permalinks
* [wpmlcore-3272] Fixed `Fatal error: Declaration of WPML_Post_Element::get_type() must be compatible with that of WPML_Translation_Element::get_type()` for PHP 5.2
* [wpmlst-881] Removed leading backslash `\` to avoid warnings in PHP <5.3
* [wpmlcore-3278] Fixed fatal error appearing during upgrade: `WordPress database error: specified key was too long; max key length is 1000`
* [wpmlcore-3273] Fixed uncaught exception in cases where `domain_name_context_md5` column didn't exist in `icl_strings` table
* [wpmlcore-3291] Improved query validation used for caching strings
* [wpmlst-888] Improved caching of strings per page, update of db structure.

# 3.5.0

## Fixes
* [wpmlcore-2590] Fix conversion to absolute link when link is to a post in another language
* [wpmlcore-2937] Fixed multisite so that links to posts on sub-sites are correct
* [wpmlcore-2952] Canonical for non translated post types must always be the "default language" URL
* [wpmlcore-2631] Fixed XML-RPC API calls to set the language of a newly created post (e.g. `metaWeblog.newPost`, `wp.newPost`, and `wp.newPage`).
* [wpmlcore-3081] Duplicated content will use the same canonical for all all languages
* [wpmlsl-28] Fix sticky links so that the correct link is used when the same slug is used in different languages
* [wpmlsl-32] Fix sticky links so that they convert correctly when the current post has duplicates
* [wpmlcore-1761] Fix notice when no field is specified in tax_query when getting posts.
* Other minor bug fixes

## Performances
* [wpmlcore-3172] Reduce SQL queries when checking if site uses ICanLocalize translation services previous to WPML 3.2
* [wpmlcore-3197] Removed the WPML Dashboard Widget to avoid unnecessary calls.

# 3.4.1.1
 
## Fixes
* [wpmltm-1487] Reduced automatic calls to ICanLocalize server to one per hour

## Compatibility
* [wpmlcore-3143] Add filters for the Events Calendar and Events Pro plugins to use for recurring events 

# 3.4.1

## Fixes
* [wpmlcore-3030] Class auto loading is not compatible with version of PHP older than 5.3
* [wpmlcore-3034] Fix missing slash at the end of alternatives links 
* [wpmlcore-3076] Updating forum user profile when using bbPress, or in general, when a user profile happens from the front-end, won't throw any notice

## Performances
* [wpmlga-133] Improved class autoloading by using class mapping, instead of file system lookup
* [wpmlcore-3026] If translation management column is hidden from screen options don't run additional queries.
* [wpmlcore-3053] Added filter `wpml_hide_management_column` to allow hiding of translation management column.

# 3.4.0

## Fixes
* [wpmlcore-2720] Avoid language parameter for search when language negotiation is not set to "language as a parameter"
* [wpmlcore-2727] Setup Wizard - Default language was ignored after changing it
* [wpmlcore-1269] Strings are now translated in step 2 of the install wizard
* [wpmlcore-2246] Fixed text orientation for RTL languages in install wizard step 2
* [wpmlcore-2795] `is_front_page()` did not work with "lang" parameter and another parameter
* [wpmlcore-2647] Added a new hook `wpml_url_converter_get_abs_home` (for third part plugins compatibility)
* [wpmlcore-2447] A warning was thrown when saving WPML language switcher options and `ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS` set to `true`
* [wpmlst-778] CPT archive slug wrongly translated when `has_archive` starts with the rewrite slug of CPT
* [wpmlcore-2728] Removed the language directory for media files links attached to root page
* [wpmltm-1343] Keep translation status when it's updated.
* [wpmlcore-2840] Fixed issue with missing cron job to publish future posts for translations when "Sync publishing date" is enabled.
* [wpmlcore-2658] The query is now parsed by WPML on AJAX requests (for 3rd part compatibility)
* [wpmltm-1351] Fixed `\WPML_Admin_Post_Actions::get_trid_from_referer`function to get trid form refferer only if needed
* [wpmlcore-2789] Multiple IDs or slugs (from categories or tags) were not always correctly adjusted
* [wpmlcore-2789] Deep nested taxonomy queries were not adjusted
* [wpmlcore-2841] `parse_url` was returning inconsistent results depending on PHP version
* [wpmlcore-2198] When mixing parameter and slug for custom taxonomies, an error was thrown in WP 4.4
* [wpmlcore-2595] Improved URL conversion when posts or terms translations have different slugs than the original
* [wpmlcore-2731] Display the hreflang meta even if the post has no translations, as long as it's set to be translatable
* [wpmlcore-2589] Display the language switcher only if the CPT is set to be translatable
* [wpmlcore-2878] Queries are not altered when browser redirection is enabled
* [wpmlcore-2803] Form input labels are properly linked to their inputs in WPML > Languages
* [wpmlcore-2899] Replaced use of `$HTTP_RAW_POST_DATA` with `php://input`
* [wpmlcore-2704] Fixed language links position in Appearance -> Menus for RTL admin languages
* [wpmltm-1395] Fixed creation of slug when "Copy from original language if translation language uses encoded URLs" is selected.
* [wpmlcore-2916] Fixed notice shown on "Inactive content" section, after changing the default language and removing it from the active languages
* [wpmltm-1401] Fixed caching issue on WP-Engine when new terms are created.
* [wpmltm-1400] Fixed issue when translation job wrongly updated when sending batch failed.
* [wpmlcore-2918] Resolved issue with autoloader class when brackets are used in the website's path
* [wpmlcore-2948] Fixed issue where unexpected menu items were created on page duplication with "Auto add pages" option enabled.
* [wpmlcore-2951] Fixed Menus Sync so the links to menu strings are now only displayed for already existing menus
* [wpmlcore-2967] Display custom posts as disabled on the multilingual content page if they are set as don't translate in wpml-config.xml files.
* [wpmlcore-2989] Fixed color presets for the footer language switcher.
* [wpmlcore-2998] Fixed widget language switcher preview to show the current native language correctly
* [wpmlcore-3002] Escape Preserve url parameters on Languages page.
* [wpmlcore-3000] Fixed issue with translation of "Uncategorized" term when not using English as default language
* [wpmlst-817] Fixed XSS issue with unescaped output.
* [wpmltm-1442] Resolved SQL errors when sending a job to a translation service fails

## Compatibility
* [wpmlcore-2701] Yoast SEO Premium - stop it from automatically creating a redirect when a duplicated post is updated.
* [wpmlcore-2929] Fixed compatibility issue with `hyperdb` which prevents from populating `icl_languages` table

## Features
* [wpmltm-1189] Implemented new design for translation editor.
* [wpmlcore-2774] Implemented new design for taxonomy translation.

## Performance
* [wpmlcore-2619] Improved speed of the archive page when many languages are active
* [wpmlcore-2988] Removed unneeded dependencies checks in admin pages: this now runs only once and later only when activating/deactivating plugins

## Cosmetic
* [wpmlcore-3015] Raised z-index of language switcher, to solve a compatibility issue with the twenty-six theme.

# 3.3.8

## Fixes
* [wpmlcore-2805] Resolved issue with WooCommerce Cart not showing products when switching between languages in domains
* [wpmlcore-2792] `PHP Notice: Undefined offset: 0 in sitepress.class.php on line 2345`
* [wpmlcore-2802] Corrected wrong paginated link with plain URL and language as a parameter
* [wpmlcore-2793] Language as parameter, conflicts with browser-redirect and manually set home page
* [wpmlcore-2795] `is_front_page()` did not work with "lang" parameter and another parameter
* [wpmlcore-2797] 301 redirects on product page with non-ASCII characters in slug
* [wpmlcore-2824] Improved caching of converted URLs
* [wpmlcore-2822] The second page of paginated content was not accessible in secondary language with same slug in translated content
* [wpmlcore-2823] On front page with paginated content, we had an unexpected redirection in secondary language from 2nd page to 1st page (pretty permalinks and language as a parameter)
* [wpmlcore-2816, wpmlcore-2821, wpmlcore-2828] Fixed malformed pagination URL when using pretty permalinks and language as a parameter

## API

### Filters
* Added the `wpml_head_langs` filter to allow changing the languages rendered in the `hreflang` meta

# 3.3.7

## Fixes
* [wpmlcore-2206] Fixed edit language issue when option 'Use flag from WPML' for flag file is chosen
* [wpmlcore-873] Fixed rtl alignment for language Urdu
* [wpmlcore-1428] Removed Moldavian language, since it's a non existing language (use Romanian instead).
* [wpmlcore-2602] Fixed post status synchronization if "Copy publishing date" option is used.
* [wpmlcore-2572] Limit query modification in class Page_Name_Query_Filter
* [wpmlcore-2524] Fixed post with same name was creating the same sample permalink
* [wpmlcore-2553] Fixed duplciated URI when it contains a query string and languages in domains is used.
* [wpmlcore-2608] Fixed Yoast SEO sitemap generated, in directory per language excludes other languages.
* [wpmlcore-2612] Fixed unexpected browser redirection on root page
* [wpmlcore-2179] Fixed issue inconsistency with Wordpress core - pages can be accessed trough category.
* [wpmlcore-2604] Fixed compatibility issue with Yoast SEO when domain per language is used.
* [wpmlcore-2624] Improved post date syncronization.
* [wpmlcore-2623] Fixed issue with missing trailing slashes when Yoast SEO is enabled.
* [wpmlcore-2629] Fixed Post/Pages accessible without language directory when root page is enabled
* [wpmlcore-2596] Fixed issued with different domain per language and domains beginning with number
* [wpmlcore-2523] Fixed issue in Yoast SEO sitemap which was not showing taxonomies in other languages than default
* [wpmlcore-2067] When the blog page is deleted, and then re-translated, is now possible to set the blog page in Settings > Reading
* [wpmlcore-2646] Improved `sitepress.js` to use `var` when declaring variables, so to allow minification and combination of scripts in strict mode
* [wpmlcore-2577] Fixed issue with WP SEO disabling "Adjust IDs for multilingual functionality"
* [wpmlcore-2529] Fixed default category mismatch when adding a new language.
* [wpmlcore-2589] Show language switcher if CPT is set to do nothing
* [wpmlcore-2638] `wp_get_archives` now works for any custom post type
* [wpmlcore-2675] Fixed potential bug with old PHP version (<5.2.9) when filtering posts
* [wpmlcore-2573] Fixed bug when `wpml_active_languages` filter hook is called before or during `wp` action hook
* [wpmlcore-2625] Fix language selectors on taxonomy edit screens for WP 4.5
* [wpmlcore-2664] Fixed notices when updating to latest version with browser redirection activated.
* [wpmlcore-2697] Fixed glitch in screen "Menus" for select box: "Translation of" for WP 4.5
* [wpmlcore-2745] Fix redirect for hidden language when using languages in domains 

## Performances
* [wpmlcore-2143] Remove non-translators cache from options.

## Features
* [wpmlcore-2574] Auto-disconnect duplicates when they are edited
* [wpmlcore-2633] Auto-disconnect duplicates only if there are changes.
* [wpmlcore-2495] Increased the accepted lenght of "Default Locale" and "Tag" in the languages table, so to allow variations such as "de_DE_formal"
* [wpmlcore-2606] Added a warning for SEO issues with automatic redirection
* [wpmlcore-2667] The `Language tag` in *WPML* -> *Languages* -> *Edit Languages* is now pre-filled, for known languages, with a two letters language code (same as the language code). Users can still use language codes with variations.
* [wpmltm-1215] Hide system fields when displaying custom fields and terms meta
* [wpmlcore-2708, wpmlcore-2680] Added an option in "WPML -> Languages -> SEO Options" to chose when to render the `hreflang` links

## Compatibility
* [wpmlcore-2676] WordPress SEO user meta fields are now translatable with WPML String Translation

## Security
* [wpmlcore-2761] Enforced security

## API

### Filters
* [wpmlcore-2593] Added `wpml_element_trid` to get the `trid` of a translated element: required for `wpml_get_element_translations` filter and others which expect this argument.
* [wpmlcore-2537] Fixed hook "wpml_make_post_duplicates" to produce duplicate post instead of independent translation.
* [wpmlcore-1861] Fixed hook "wpml_make_post_duplicates" to duplicates custom post fields.

# 3.3.6

## Fixes
* [wpmlcore-2463, wpmlcore-2546, wcml-867]  Resolved issue that lead to database corruption and fatal error `InvalidArgumentException: element_id and type do not match`
* [wpmlcore-1954] Added constant that disables automatic update of wpml-config.xml configurations: ICL_REMOTE_WPML_CONFIG_DISABLED
* [wpmlcore-2505] Fixed URL handling when using Domains per languages in an installation under a sub-folder
* [wpmlcore-2562] Resolved issue when re-activating WPML in sites having corrupted databases
* [wpmlcore-2511] Inject "hreflang" meta tags higher in the head.
* [wpmlcore-2518] Show "Display hidden languages" option to admins and users users with "translate" capability
* [wpmlcore-2536] Improve Yoast SEO compatibility by filtering out hidden language posts from sitemap
* [wpmlcore-2571] Fix browser redirect so redirect only happens if the current page language is not one of the preferred browser languages
* [wpmlcore-2351] Fixed different results produced in non default languages by queries with mixed post type and taxonomies in filter
* [wpmlcore-1104] Improve TM notice handling in core.
* [wpmlcore-2407] Fixed issue with post status when post date sync is enabled

## Performances
* [wpmlcore-2528] Cached calls to `glob()` function when auto loading classes

## Cleanup
* [wpmlcore-1616] Removed references to Font Awesome library and replaced usage with Dashicons
* [wpmlcore-2541] Removal of "icon-32" usage

## Features
* [wpmlcore-2418] Added under the hood logic, to handle taxonomy terms meta translation

## Compatibility
* [wpmlcore-2586] Avoid redeclaring the `Twig_Autoloader` class, if another plugin or theme already declared it.

## 3.3.4

## Fixes
* [wpmlcore-2465] Fixed AJAX loading of Media in WP-Admin when domains per languages are used 
* [wpmlcore-2433] Fixed compatibility issues with W3 Total Cache when Object caching is used
* [wpmlcore-2420] Fix menu synchronization when menu item has quotes in its title
* [wpmlcore-2445] Use of Fileinfo functions to read file mime type when uploading a custom flag, fall back to the now deprecated `mime_content_type` function, if the first set of cuntions is not available
* [wpmlcore-2453] Fixed fatal error when setting a custom taxonomy as translatable (`Fatal error - Class WPML_Term_Language_Synchronization not found in sitepress.class.php`)
* [wpmlcore-2448] Fixed `WordPress database error You have an error in your SQL syntax` message, caused by empty or corrupted languages order.
* [wpmlcore-2452] Adding a comment to a translated post won't redirect user to the default language.
* [wpmlcore-2136] Corrected "Slawisch" to "Slowakisch" in German language name for "Slovak"

# 3.3.3

## Fixes
* Added backward compatibility for `__DIR__` magic constant not being supported before PHP 5.3.
* [wpmlcore-2089] When not using languages in domains, the language information should be passed as url argument to the AJAX url

# 3.3.2

## Fixes
* [wpmlga-96] WordPress 4.4 compatibility: pulled all html headings by one (e.g. h2 -> h1, he -> h2, etc.)
* [wpmlcore-2318] Fixed some UI issues caused by changes in WordPress 4.4 styles
* [wpmlcore-2089] AJAX calls when using languages in domain, now calls the correct AJAX url, rather than the url of the default language.
* [wpmlcore-2060] Custom fields created by Types set as do nothing are now properly duplicated when duplicating a post

# 3.3.1

## Fixes
* [wpmlcore-2402] Fixed issue when visiting User Profile or Translation Interface page as non-admin translator when hidden languages are active
* [wpmlcore-2177] Added constant that hides the WPML Dashboard widget: ICL_HIDE_DASHBOARD_WIDGET
* [wpmlcore-2366] Fixed issue with admin messages not hiding
* [wpmlcore-2027] Fixed a corner case where get_permalink() was not working when "%category%" was included in Permalink
* [wpmlcore-2345] Fix caching of locale when using W3TC
* [wpmlcore-2347] Fix caching of page on front and post on front when using W3TC
* [wpmlcore-2369] Fix other caching issues when using W3TC
* [wpmlcore-2370] Fix issue when installing WPML in multisites with different admin language set for default site
* [wpmlcore-2364] Fix warning in WPML_Query_Parser::parse_query()
* [wpmlcore-2274] Fixed Warning: mkdir(): No such file or directory
* [wpmlcore-2384] Fixed loss of already active languages when activating/deactivating languages
* [wpmlcore-2385] Fixed loss of languages order when activating/deactivating languages
* [wpmlcore-2386] Fixed stylesheet and login urls being translated incorrectly when homeurl are siteurl different.
* [wpmlcore-2361] Fixed post meta data set to be copied to translations, not being copied when editing the original post.

## Features
* [wpmlcore-2388] Ability to upload svg files for custom flags

## Performance
* [wpmlcore-2349] Cache get_source_language_by_trid to improve performance
* [wpmlcore-2363] Cache calls to add flags to post list column

## API (see https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/)
* Shortcodes
	* [wpmlcore-2371] `[wpml_language_form_field]`, like the `wpml_language_form_field` action, it will render the hidden input field, but it can be also used to get the string in PHP.
* Filters
	* [wpmlcore-2371] `wpml_language_form_input_field`, which changed the hidden input field rendered by the `wpml_add_language_form_field` action
    * [wpmlcore-1626] `wpml_translation_validation_data`, which is called when validating data submitted by the translation editor (arguments: `$validation_results`, `$data_to_validate`)

# 3.3.0

## Fixes
* [wpmlcore-2278] Resolved dependency on of trid on browser referrer
* [wpmlcore-2185] Fixed issue with duplicated posts reverting to scheduled state with missed schedule status
* [wpmlcore-1810] Removed obsolete setting "Add links to the original content with rel="canonical" attributes."
* [wpmlcore-2286] Resolved `PHP Notice:  bbp_setup_current_user was called <strong>incorrectly</strong>.`
* [wpmlcore-2102] Incorrect .htaccess when using a directory for the default language
* [wpmlcore-2130] Categories to navigation menu can be now added when WPML is active
* [wpmlcore-2144] Fixed incosisten behavior with hierarchical post types using the same slug
* [wpmlcore-1468] Setting a menu language switcher as "horizontal" now allows to set it back to "dropdown"
* [wpmlcore-2076] Fixes compatibility issues with languages switcher and 2015 theme
* [wpmlcore-2113] Removed asynchronous AJAX requests
* [wpmlcore-2233] Terms count are properly updated when setting a post a private
* [wpmlcore-2243] Fixed compatibility issues with Views
* [wpmlst-629] Removed dependency of ST with TM, causing a `Call to undefined function object_to_array()` fatal error
* [wpmlcore-2230] Fixed database errors when showing pages list and there are no taxonomies associated with the pages post type
* [wpmlst-619] The WPML language selector properly shows language names in the right language
* [wpmlcore-2224] Fixed random DB Errors & maximum execution time errors when upgrading to WPML 3.3
* [wpmlcore-2232] `\SitePress::pre_option_page` properly caches data
* [wpmlcore-2212] Password-protected posts and private status are properly copied to translations, when this setting is enabled
* [wpmlcore-1797] Fixed the "Display hidden languages" options for users
* [wpmlcore-2168] Fixed redirection for child pages 
* [wpmlcore-2158] Fixed issue with menu theme location being lost after updating another menu item
* [wpmlcore-2093] Administrator language switcher will be only added after installation is completed
* [wpmlcore-2038] Fixes the issue of `WPML_Root_Page` class not being included in some cases
* [wpmlcore-2134] Fixes the issue of errors in the communication with ICL leading to a white-screen
* [wpmlcore-2125] get_term_children results will be consistent when there is an element with the same id in `icl_translations`
* [wpmlcore-2024] The "Translate Independently" button now work when autosave is off
* [wpmlcore-2038] Resolved conflict with Avada theme and root page
* [wpmlcore-2168] Fix so that taxonomies can have a custom language template for the default language
* [wpmlcore-2047] Filter url for scripts and styles when language is per domain
* [wpmlcore-1887] Fixed an issue causing a notice and incorrect results for certain taxonomy queries that involved custom post types
* [wpmlcore-2058] Resolved notice "Undefined index: strings_language" after WPML activation and configuration
* [wpmlcore-2201] Fixes issue with password-protected posts in different domains per language configurations
* [wpmlcore-2058] Resolved notice "Undefined index: strings_language" after WPML activation﻿
* [wpmlcore-2087] Resolved http(s) protocol and different domains per language issues
* [wpmlcore-2167] Resolved broken settings issue with WooCommerce during WPML activation
* [wpmlcore-2171] Resolved notices when selecting "All languages" in admin
* [wpmlcore-2176] Removed Translation Management dependency when duplicated posts are updated
* [wpmlcore-2184] Resolved issues when deleting a Layout which has no cells (hence no package)
* [wpmlcore-2225] Fix synchronizing post private state
* [wpmltm-820] Fix so that Gravity Forms with a large amount of fields can be send to translation
* [wpmlcore-2259] Fix translation of taxonomy labels when original is not in English
* [wpmlcore-2269] Adjust the current language if it's different from the language of the menu in the get param
* [wpmlcore-2277] Fixed issue with comments not being shown on back-end in multisites using WooCommerce
* [wpmlcore-1499] Fixed issue with javascript browser redirection happening even in default language
* [wpmlcore-2317] Don't show the taxonomy language switcher on the taxonomy edit page
* [wpmlcore-2325] Resolves database error when adding new sites in network installations
* [wpmlcore-2320] Fixed issue with bbPress and WPML configuration in network installations
* Other minor bug fixes

## Features
* [wpmlcore-1532] Introduced internationalisation API for wp_mail()
* [wpmlcore-2043] Data transfer between domains (when using languages in domains): needed with WooCommerce, and in preparation for other upcoming features
* [wpmlcore-2149] Added button to to clear all WPML caches
* [wpmlcore-2186] WPML now allows to load a taxonomy template by language, also for the default language

## API

### Filters
* [wpmlcore-2200] Added `blog_translators` to programmatically change the list of translators
* [wpmlcore-2138] Added `wpml_active_languages_access` to filter active languages
* [wpmlcore-2138] fixed `wpml_icon_to_translation` to pass the post ID

## Performances
* [wpmlcore-2055] Improved browser redirect performances
* [wpmlcore-2196] Fixed performance issues when lists posts (in particular, but not only, WooCommerce products)

# 3.2.7

## Fixes
* [wpmlcore-2046] Security tightening for an issue reported by Julio Potier from SecuPress
* [wpmlcore-2033] Posts are now visible in the default language, even if the blog display page is not translated in all languages
* [wpmlcore-2035] Shop page in default language wasn't showing products in some cases
* [wpmlcore-2030] The copy from original post content functionality is now copying paragraphs and all formatting
* [wpmlcore-2032] Trashed pages and posts are ignored by the language switcher
* [wpmlcore-2040] Fixed typos in some gettext domains
* [wpmlst-432] Clear the current string language cache when switching languages via 'wpml_switch_language' action

## Performances
* [wpmlcore-2034, wpmlcore-1543] Resolved performance issues caused by the _icl_cache object

## API
* [wpmlcore-2157] Added `wpml_admin_language_switcher_active_languages` filter to change the active languages used by the admin language switcher
* [wpmlcore-2157] Added `wpml_admin_language_switcher_items` filter to change the languages shown in the admin language switcher

# 3.2.6

## Fixes
* Fixed a performance issue when looking up pages by slugs, a large database and using MyISAM as database engine
* Fixed a performance issue with updating languages cache for sites under heavy loads

# 3.2.5

## Fixes
* Fixed a performance issue when looking up pages by slug and having a very large number of posts in the database

# 3.2.4

## Fixes
* Solved the problem when, in some cases, WPML was showing a corrupted settings warning
* Fixed the xdomain script not always running, due to a dependency issue
* Solved a problem where page slugs where given precedence over custom post type slugs when resolving permalinks, even though the URI specified the custom post type

# 3.2.3

## Fixes
* Fixed a potential security issue
* Fixed missing parentheses in mobile switcher 
* Changing admin language on one site (multisite) does not sets admin language on all sites anymore
* When 'Language as Parameter' is used, wp_link_pages() pagination produce now a properly formed URL
* Fixed WPML breaking XML-RPC calls
* Fixed wrong order of languages when using `icl_get_languages` with `orderby=name`
* Fixed several glitches and issues related to the menus synchronization
* Fixed redirection issues when the same slug is used in translations with hierarchical post types
* Fixed page template being unchangeable in translated content, after deactivating the "Synchronize page template" setting
* Fixed "This is a translation of" being empty when creating a new translation
* Fixed "This is a translation of" so it's enabled correctly
* Removed errors when deactivating WPML for a subsite in multisite install
* Fixed an issue preventing subsites in a subdirectory multisite setup from completing the last setup step
* Removed creation of duplicated 'update_wpml_config_index' cron job
* Fixed domain name mapping when a domain name in one language includes the domain name from another language
* Fixed problem with Greek characters in taxonomy names causing problems with taxonomy translations editor
* Fixed language switcher not filtering out empty date archive translations
* Fixed issues resolving hierarchical page URLs when parent and child use the same page-slug
* Fixed issue where urls pointing at hidden laguage posts were redirected incorrectly when usinging the languages as parameters setting
* Fixed 'wpml_active_languages' filter so it returns languages with correct data
* Fixed an issue in where attachments were wrongfully filtered by language and hence not displayed in the front-end in some cases

## Improvements
* Improved menus synchronization performances
* Improved caching of posts, taxonomy translation data, and strings
* Improved general performance
* Wildcard entries for admin string settings now work for sub-keys on all levels in addition to top-level keys

## API
* New hooks added (see https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/)
	* Filters
		* `wpml_permalink`
		* `wpml_element_language_details`
		* `wpml_element_language_code`
		* `wpml_elements_without_translations`
	* Actions
		* `wpml_switch_language`

## Compatibility
* WooCommerce
	* When WPML is set to use languages per domain, switching languages won't lose the cart data
	* The shop page is now properly processed as a shop page, instead of behaving as a regular custom post type archive page
* Access
	* Improved WPML capabilities definitions by adding descriptive labels
* MU Domain Mapping
	* Adjusted URL filtering for multisites so that languages in directories works with non-default locations for the wp-content directory

# 3.2.2

## Fixes
* `Fixed Warning: base64_encode() expects parameter 1 to be string, array given`

## New
* Updated dependency check module

# 3.2.1

## Fixes
* `do_action( 'wpml_add_language_selector' );` now properly echoes the language switcher
* Resolved a dependency issue: `Fatal error: Class 'WPML_Post_Translation' not found`

# 3.2.0

## New
* Support for Translation Proxy
* Now it is possible to select the preferred admin language among all available languages and not only active languages
* Functionality has been added to block the translation of already translated taxonomy terms through the translation management functionality

## Fixes
* Fixed position of radio buttons dedicated to change default language
* Fixed some PHP notices and warnings
* Performance improvements when loading menus
* Fixed errors from hide-able admin message boxes
* Restyled admin message boxes
* Fixed errors in reading wpml-config.xml file
* Fixed wrongful inclusion of the taxonomy translation JavaScript that was causing notices and performance losses in the back-end
* Fixed an issue leading to taxonomies not being copied to translated posts, despite the option for it having been set
* Settings information moved from Troubleshooting page to Debug information page
* Fixed "Reset languages" option
* Added fallback function for `array_replace_recursive()`, which might be unavailable in old versions of PHP
* Fixed Directory for default language and root page/HTML file as root problems
* Fixed language order setting not working due to Javascript errors
* Fixed default category translation by removing language suffix in category name
* Fixed post/page title display when many languages are active
* Fixed different problems with menu creation and synchronization
* Fixed scheduled post translations from being automatically published
* Fixed taxonomy label translation from Taxonomy Translation screen
* Fixed wrong page redirection when 2 child pages use the same slug
* Fixed ability to have two separate language switcher widgets on the same page when the "Mobile Friendly Always" option is selected
* Removed trailing slash from ICL_PLUGIN_URL constant
* Fixed search results url when Language display is set to parameter
* Fixed post same-slug-across-languages problems
* Fixed issues related to accessing content of hidden languages

## Compatibility
* Added wrapper functions for mb string functions
* Removed usage of deprecated ColorPicker jQuery plugin, replaced with wpColorPicker jQuery plugin
* Fixed various WP SEO XML sitemap issues

## Improvements
* Flags column visible for WooCommerce products edit list table
* Improved taxonomy synchronization between languages

## Performances
* WPML is getting more and more faster, even when dealing with a lot of content

## API
* Improved API and created documentation for it in wpml.org

# 3.1.9.7

## Fixes
* Saving menus in non default language leads to 404 error
* Updated Installer to fix issues with happening when trying to install other products
    
# 3.1.9.6

## Security
* Another security update (purposely undisclosed)
    
# 3.1.9.5

## Fixes
* "Illegal mix of collation" error ([forum thread](https://wpml.org/forums/topic/404-error-on-all-pages-with-wpml-3-1-9-4-on-wp-engine-hosting/))
* Categories by languages are not displayed: ([forum thread](https://wpml.org/forums/topic/missing-categories-after-upgrade/))
    
# 3.1.9.4

## Fixes
* Fixed WordPress 4.2 compatibility issues
* Fixed languages order settings retention
* Fixed "Catchable fatal error: Object of class stdClass could not be converted to string" when visiting the plugins page or scanning the wpml-config.xml file
* Fixed "Duplicate featured image to translation" checkbox not getting automatically checked

## Other
* Performance improvements when duplicating a post and synchronizing terms at the same time
* Updated Installer

# 3.1.9 - 3.1.9.3

## Security
* Security Update, Bug and Fix (purposely undisclosed)

## Fixes
* Fixed an issue causing sites using WooCommerce to become inaccessible
* Fixed a notice occurring on duplicating WooCommerce products
* Fixed an issue with Menu Synchronisation and custom links that were not recognised as translated
* Fixed an issue with the taxonomy label translation. Label translation still necessitates the use of English as String Language as well as, as Admin Language

# 3.1.8.4

## Fixes
* Fixed an issue causing sites using WooCommerce to become inaccessible
* Fixed a notice occurring on duplicating WooCommerce products
* Fixed an issue with Menu Synchronisation and custom links that were not recognised as translated
* Fixed an issue with the taxonomy label translation. Label translation still necessitates the use of English as String Language as well as, as Admin Language

# 3.1.8.3

## Fixes
* Replaced flag for Traditional Chinese with the correct one
* Fixed an issue with using paginated front pages
* Fixed an issue with using a root page while using static front pages
* Fixed an issue with additional slashes in urls
* Fixed an issue with same terms names in more than one language
* Fixed support for post slug translation
* Fixed an issue with term_ids being filtered despite the feature having been disabled
* Fixed issues with duplicate terms and erroneous language assignments to terms, resulting from setting a taxonomy from untranslated to translated

# 3.1.8.2

## Fixes
* Fixed compatibility issue of json_encode() function with PHP < 5.3.0 not accepting the options argument
* Fixed an issue with some terms not being properly displayed on the Taxonomy Translations Screen
	
# 3.1.8.1

## Fixes
* Fixed a compatibility issue with WooCommerce, showing up when upgrading to 3.1.8

# 3.1.8

## Improvements
* Added template tag to display HTML input with current language wpml_the_language_input_field()
* Added support for translation of string packages
* Minor speed improvements related to operations on arrays
* Minor speed improvements related to string caching
* Minor speed and compatibility improvement: JS files are now called from footer
* Installer: Added pagination for site keys list of Account -> My Sites
* Installer: Allow registering new sites by clicking a link in the WordPress admin instead of copying and pasting the site url in the Account -> My Sites section
* Installer: Display more detailed debug information related to connectivity issues with the WPML repository
* Button "Post type assignments" added to Troubleshooting page allowing to synchronise post types and languages in translation table
* Added functionality to allow the same term name across multiple languages without the use of @lang suffixes
* Added functionality to remove existing language suffixes to the troubleshooting menu

## Compatibility
* Fixed category setting for Woocommerce products

## Fixes
* Fixed some PHP notices and warnings
* Fixed search form on secondary language
* Fixed issue with caching on page set as front page
* Fixed: Taxonomy terms are not showing on the WPML->Taxonomy Translation page 
* icl_object_id function now works well also with unregistered custom post types
* Minor issues with language switcher on mobile devices
* Fixed problem with language switcher options during installation
* Textarea for additional CSS for language switcher was too wide, now it fits into screen
* Fixed influence of admin language on settings for language switcher in menu
* wp_nav_menu now always displays language switcher when configured
* Fixed custom queries while using a root page
* Fixed DB error when fetching completed translations from ICanLocalize
* Installer: Fixed problem with WPML registration information (site key) not being saved when the option_value field in the wp_options table used a different charset than the default WordPress charset defined in wp-config.php
* Installer: Reversed the order in which the site keys are displayed
* Fixed pagination on root page
* Fixed root page permalink on post edit screen
* Fixed root page preview
* Fixed minor issues in wp-admin with RTL languages 
* Fix for conflicting values of context when registering strings for translation
* Fixed: Archive of untranslated custom post type should not display 'rel="alternate" hreflang="" href=""' in header
* Fixed language filters for get_terms() function
* Fixed problem with taxonomy (e.g. category) parents synchronization
* Fixed problem with editing post slug
* Removed unnecessary taxonomy selector from Taxonomy translation page
* Fixed some PHP notices on WPML Languages page with "All Languages" selected on language switcher
* Fixed problem with adding categories by simply pressing "Enter" key (post edit screen)
* Removed option to delete default category translations
* Fixed missing terms filtering by current language in admin panel
* Fixed problem with comments quick edit

# 3.1.7.2

## Improvements
* Installer support for WordPress Multisite

## Fixes
* Fixed: Caching issue when duplicating posts

# 3.1.7.1

## Fixes
* Fixed: Cannot send documents to translation
* Fixed: WordPress database error: Duplicate entry during post delete
* Fixed: Preview page does not work on Root page
* Fixed: Fatal error: Call to a member function get_setting() on a non-object

# 3.1.7

## Improvements
* Added template functions for reading/saving WPML settings, for future use
* When wp-admin language is switched to non default and user will update any plugin or theme configuration, this value will be recognized as translation and updated correctly
* Added "Remote WPML config files" functionality
* New version of installer
* Added various descriptions of WPML settings, displayed on configuration screens
* Added shortcodes for language switchers

## Compatibility
* WP SEO plugin compatibility enhancements
* Compatibility with WP 4.0: Removed like_escape()* calls

## Fixes
* get_custom_post_type_archive_link() now always returns correct link
* Fixed url filters for different languages in different domains configured
* In icl_object_id we were checking if post type is registered: WordPress doesn't require this, so we removed this to be compatible with filters from other plugins
* Fixed hreflang attribute for tag/category archive pages
* Fixed permissions checking for document translators
* Fixed: media tags added to default language instead of translation
* Fixed broken relationship consistency when translating posts 
* Replaced strtolower() function calls with mb_strtolower() and gained better compatibility with non-ASCII languages

# 3.1.6

## Improvements
* Languages can have now apostrophes in their names
* Time of first activation of WPML plugin reduced to about 5% of previous results
* Administrator can add user role to display hidden languages
* New way to define WPML_TM_URL is now tolerant for different server settings
* Added debug information box to WPML >* Support page

## Compatibility
* WP SEO plugin compatibility enhancements
* Added filters to be applied when custom fields are duplicated
* Added filtering stylesheet URI back
* Fixed compatibility with new version of NextGen Gallery plugin

## Fixes
* Fixed possible SQL injections
* Function 'get_post_type_archive_link' was not working with WPML, it is fixed now 
* WPML is no longer removing backslashes from post content, when post it duplicated
* Enhanced filtering of home_url() function
* Translated drafts are saved now with correct language information
* Improved translation of hierarchical taxonomies
* wp_query can have now more than one slug passed to category_name
* Support for translate_object_id filter - this can be used in themes instead of the icl_object_id function
* get_term_adjust_id cache fixed to work also if term is in multiple taxonomies
* Fixed wrong post count for Language Links
* Fixed widget previews
* Fixed warning in sitepress::get_inactive_content()
* Fixed string translation for multilevel arrays where content was indexed by numbers, it was not possible to translate elements on position zero
* Function url_to_postid() is now filtered by WPML plugin to return correct post ID
* WordPress Multisite: when you switch between blogs, $sitepress->get_default_language() returned sometimes wrong language. It is fixed
* Broken language switcher in custom post type archive pages was fixed
* Removed references to global $wp_query in query filtering functions
* icl_object_id works now for private posts
* constants ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS + ICL_DONT_LOAD_LANGUAGES_JS are respected now when JS and CSS files are loaded
* Fixed broken wp_query when querying not translatable Custom Post Type by its name: WPML was removing this name, which resulted with wrong list of posts
* When was set root page, secondary loops displayed wrong results

# 3.1.5

## Improvements
* check_settings_integrity() won't run SQL queries on front-end and in the back-end it will run only once and only in specific circumstances
* We added ability to add language information to duplicated content, when WPML_COMPATIBILITY_TEST_MODE is defined
* Option to create database dump was removed as it was not working correctly. Please use additional plugins to do this (eg https://wordpress.org/plugins/adminer/ )

## Usability
* We added links to String Translation if there are labels or urls that needs to be translated, when running menu synchronization

## Compatibility
* is_ajax() function is now deprecated and replaced by wpml_is_ajax() - **plugins and themes developers**: make sure you're updating your code!
* Compatibility with WordPress 3.9 - WPML plugins were adjusted to use WPDB class in correct way, no direct usages of mysql_** functions

## Fixes
* Parent pages can be now changed or removed
* Fixed issue when a showing paginated subqueries in home page (in non default language)
* In some circumstance translated posts statuses doesn't get synchronized after publishing a post: fixed now
* The "Connect translation" feature now works also when the WYSIWYG is not shown
* We make use of debug_backtrace in some places: wherever it is possible (and depending on used PHP version), we've reduced the impact of this call by limiting the number of retrieved frames, or the complexity of the retrieved data
* In some server configuration we were getting either a 404 error or a redirect loop
* Static front page is not loosing now custom page template on translations when paged
* Corrupted settings when no language in array - fixing this now doesn't end with pages missing
* Custom Post Types when was set not to be translated was also not displayed, now it's fixed
* Root page can work now with parameters
* Fixed compatibility with CRED/Views (404, rewrite_rules_filter)
* Fixed potential bug caused by redeclaration of icl_js_escape() function
* Gallery was not displayed on root home page, this is fixed now
* Filtered language doesn't remain as 'current value' on the wpml taxonomy page - this is also fixed
* Information about hidden languages was displayed duplicated and doubled after every page refresh
* Fixed filtering wp_query with tax_query element 
* Ajax now "knows" language of page which made a ajax call
* Removed PHP Notice on secondary language front page
* Updated links to wpml.org
* Many fixes in caching data what results in better site performance
* Taxonomy @lang suffixes wasn't hidden always when it was necessary, now this is also fixed
* Removed conflict between front page which ID was same as ID of actually displayed taxonomy archive page
* Fixed saving setting for custom fields translation
* Removed warning in inc/absolute-links/absolute-links.php
* Removed duplicated entries in hidden languages list
* Fixed Notice message when duplicating posts using Translation Management
* Update posts using element_id instead of translation_id
* Fixed PHP Fatal error: Cannot use object of type WP_Error as array
* Option to translate custom posts slugs is now hidden when it is no set to translate them
* Fixed monthly archive page, now it shows language switcher with correct urls
* You can restore trashed translation when you tried to edit this
* When you try to delete an item from untranslated menu, you saw PHP errors, now this is also fixed
* Fixed compatibility issue with PHP versions < 5.3.6: constants DEBUG_BACKTRACE_PROVIDE_OBJECT and DEBUG_BACKTRACE_IGNORE_ARGS does not exist before this version, causing a PHP notice message
* Fixed wrong links to attachments in image galleries
* Fixed not hidden spinner after re-install languages
* Handled timeout error message when fixing languages table
* Made SitePress::slug_template only work for taxonomies
* Fixed problems with missing taxonomies configuration from wpml-config.xml file
* MENU (Automatically add new top-level pages to this menu) option was not synchronised
* WP 3.9 compatibility issue: new version of WordPress doesn't automatically load dialog JS
* Fixed WP 3.9 compatibility issues related to language switcher widget
* Category hierarchy and pages hierarchy are now synchronised during translation
* Fixed problem with redirecting to wrong page with the same slug in different language after upgrade to WP3.9
* Fixed problem with Sticky Links and Custom Taxonomies
* Home url not converted in current language when using different domains per language and WP in a folder
* Fixed typos when calling in some places _() instead of __()
* Fixed Korean locale in .mo file name

# 3.1.4

## Fixes
* The default menu in other language has gone
* Menu stuck on default language
* Infinite loop in auto-adjust-ids
* Translations lose association
* The "This is a translation of" drop down wasn't filled for non-original content
* Removed language breakdown for Spam comments
* Pages with custom queries won't show 404 errors
* Languages in directories produces 404 for all pages when HTTP is using non standard 80 port
* Fixed icl_migrate_2_0_0() logic
* When a database upgrade is required, it won't fail with invalid nonce
* Error in all posts page, where there are no posts
* php notice when adding a custom taxonomy to a custom post
* The default uncategorized category doesn't appear in the default language
* Fixed locale for Vietnamese (from "vn" to "vi")
* Fixed languages.csv file for Portuguese (Portugal) and Portuguese (Brazilian) mixed up --this requires a manual fix in languages editor for existing sites using one or both languages--
* Pre-existing untranslatable custom post types disappears once set as translatable
* Languages settings -> Language per domain: once selected and page is reloaded, is the option is not properly rendered
* Languages settings -> Language per domain: custom languages generate a notice
* Updated translations
* Custom Fields set to be copied won't get lost anymore
* Scheduled posts won't lose their translation relationship
* Excluded/included posts in paginated custom queries won't cause any notice message
* Replace hardcoded references of 'sitepress-multilingual-cms' with ICL_PLUGIN_FOLDER
* Replace hardcoded references of 'wpml-string-translation' with WPML_ST_FOLDER
* Replace hardcoded references of 'wpml-translation-management' with WPML_TM_* FOLDER

## Improvements
* Generated keys of cached data should use the smallest possible amount of memory
* The feature that allows to set orphan posts as source of other posts has been improved in order to also allow to set the orphan post as translation of an existing one
* Added support to users with corrupted settings
* Improved language detection from urls when using different domains
* Added admin notices for custom post types set as translatable and with translatable slugs when translated slugs are missing

# 3.1.3

## Fixes
* In SitePress_Setup::languages_table_is_complete -> comparison between number of existing languages and number of built in languages changed from != to <
* In SitePress_Setup::fill_languages -> added "$lang_locales = icl_get_languages_locales();" needed for repopulating language tables
* Added cache clearing to icl_fix_languages logic on the troubleshooting page
* Wording changes for the fix languages section on the troubleshooting page
* Logic changes for the fix languages section on the troubleshooting page -> added checkbox and the button is enabled only when the checkbox is on
* Added WPML capabilities to all roles with cap 'manage_options' when activate
* Not remove WPML caps from super admin when deactivate

# 3.1.2

## Fixes
* Fixed a potential issue when element source language is set to an empty string rather than null: when reading element translations, either NULL or '' will be handled as NULL

# 3.1.1

## Fixes
* Fixed an issue that occurs with some configurations, when reading WPML settings

# 3.1.0

## Performances
* Reduced number of queries to one per request when retrieving Admin language
* Reduced the number of calls to *$sitepress->get_current_language()*, *$this->get_active_languages()* and *$this->get_default_language()*, to avoid running the same queries more times than needed
* Dramatically reduced the amount of queries ran when checking if content is properly translated in several back-end pages
* A lot of data is now cached, further reducing queries

## Improvements
* Improved javascript code style
* Orphan content is now checked when (re)activating the plugin, rather than in each request on back-end side
* If languages tables are incomplete, it will be possible to restore them

## Feature
* When setting a value for "This is a translation of", and the current content already has translations in other languages, each translation gets properly synchronized, as long as there are no conflicts. In case of conflicts, translation won't be synchronized, while the current content will be considered as not linked to an original (in line with the old behavior)
* Categories, tags and taxonomies templates files don't need to be translated anymore (though you can still create a translated file). Taxonomy templates will follow this hierarchy: '{taxonomy}-{lang}-{term_slug}-{lang}.php', '{taxonomy}-{term_slug}-{lang}.php', '{taxonomy}-{lang}-{term_slug}-2.php', '{taxonomy}-{term_slug}-2.php', '{taxonomy}-{lang}.php', '{taxonomy}.php'
* Administrators can now edit content that have been already sent to translators
* Ability to set, in the post edit page, an orphan post as source of translated post
* Added WPML capabilities (see online documentation)
* Add support to users with corrupted settings

## Security
* Improved security by using *$wpdb->prepare()* wherever is possible
* Database dump in troubleshooting page is now available to *admin* and *super admin* users only

## Fixes
* Admin Strings configured with wpml-config.xml files are properly shown and registered in String Translation
* Removed max length issue in translation editor: is now possible to send content of any length
* Taxonomy Translation doesn't hang anymore on custom hierarchical taxonomies
* Is now possible to translate content when displaying "All languages", without facing PHP errors
* Fixed issues on moderated and spam comments that exceed 999 items
* Changed "Parsi" to "Farsi" (as it's more commonly used) and fixed some language translations in Portuguese
* Deleting attachment from post that are duplicated now deleted the duplicated image as well (if "When deleting a post, delete translations as well" is flagged)
* Translated static front-page with pagination won't loose the template anymore when clicking on pages
* Reactivating WPML after having added content, will properly set the default language to the orphan content
* SSL support is now properly handled in WPML->Languages and when setting a domain per language
* Empty categories archives does not redirect to the home page anymore
* Menu and Footer language switcher now follow all settings in WPML->Languages
* Post metas are now properly synchronized among duplicated content
* Fixed a compatibility issue with SlideDeck2 that wasn't retrieving images
* Compatibility with WP-Types repeated fields not being properly copied among translations
* Compatibility issue with bbPress
* Removed warnings and unneeded HTML elements when String Translation is not installed/active
* Duplicated content retains the proper status
* Browser redirect for 2 letters language codes now works as expected
* Menu synchronization now properly fetches translated items
* Menu synchronization copy custom items if String Translation is not active, or WPML default languages is different than String Translation language
* When deleting the original post, the source language of translated content is set to null or to the first available language
* Updated localized strings
* Posts losing they relationship with their translations
* Checks if string is already registered before register string for translation. Fixed because it wasn't possible to translate plural and singular taxonomy names in Woocommerce Multilingual
* Fixed error when with hierarchical taxonomies and taxonomies with same names of terms
