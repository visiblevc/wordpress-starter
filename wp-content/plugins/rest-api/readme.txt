=== WordPress REST API (Version 2) ===
Contributors: rmccue, rachelbaker, danielbachhuber, joehoyle
Tags: json, rest, api, rest-api
Requires at least: 4.6
Tested up to: 4.7-alpha
Stable tag: 2.0-beta15
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Access your site's data through an easy-to-use HTTP REST API. (Version 2)

== Description ==
WordPress is moving towards becoming a fully-fledged application framework, and we need new APIs. This project was born to create an easy-to-use, easy-to-understand and well-tested framework for creating these APIs, plus creating APIs for core.

This plugin provides an easy to use REST API, available via HTTP. Grab your site's data in simple JSON format, including users, posts, taxonomies and more. Retrieving or updating data is as simple as sending a HTTP request.

Want to get your site's posts? Simply send a `GET` request to `/wp-json/wp/v2/posts`. Update user with ID 4? Send a `PUT` request to `/wp-json/wp/v2/users/4`. Get all posts with the search term "awesome"? `GET /wp-json/wp/v2/posts?filter[s]=awesome`. It's that easy.

The WordPress REST API exposes a simple yet easy interface to WP Query, the posts API, post meta API, users API, revisions API and many more. Chances are, if you can do it with WordPress, the API will let you do it.

The REST API also includes an easy-to-use JavaScript API based on Backbone models, allowing plugin and theme developers to get up and running without needing to know anything about the details of getting connected.

Check out [our documentation][docs] for information on what's available in the API and how to use it. We've also got documentation on extending the API with extra data for plugin and theme developers!

All tickets for the project are being tracked on [GitHub][]. You can also take a look at the [recent updates][] for the project.

[docs]: http://v2.wp-api.org/
[GitHub]: https://github.com/WP-API/WP-API
[recent updates]: http://make.wp-api.org/

== Installation ==

Install the WP REST API via the plugin directory, or by uploading the files manually to your server.

For full-flavoured API support, you'll need to be using pretty permalinks to use the plugin, as it uses custom rewrite rules to power the API.

Once you've installed and activated the plugin, [check out the documentation](http://v2.wp-api.org/) for details on your newly available endpoints.

== Changelog ==

= 2.0 Beta 15.0 (October 07, 2016) =

* Introduce support for Post Meta, Term Meta, User Meta, and Comment Meta in
their parent endpoints.

  For your meta fields to be exposed in the REST API, you need to register
  them. WordPress includes a `register_meta()` function which is not usually
  required to get/set fields, but is required for API support.

  To register your field, simply call register_meta and set the show_in_rest
  flag to true. Note: register_meta must be called separately for each meta
  key.

  (props @rmccue, @danielbachhuber, @kjbenk, @duncanjbrown, [#2765][gh-2765])

* Introduce Settings endpoint.

  Expose options to the REST API with the `register_setting()` function, by
  passing `$args = array( 'show_in_rest' => true )`. Note: WordPress 4.7 is
  required. See changeset [38635][https://core.trac.wordpress.org/changeset/38635].

  (props @joehoyle, @fjarrett, @danielbachhuber, @jonathanbardo,
  @greatislander, [#2739][gh-2739])

* Attachments controller, change permissions check to match core.

  Check for the `upload_files` capability when creating an attachment.

  (props @nullvariable, @adamsilverstein, [#2743][gh-2743])

* Add `?{taxonomy}_exclude=` query parameter

  This mirrors our existing support for ?{taxonomy}= filtering in the posts
  controller (which allows querying for only records with are associated with
  any of the provided term IDs for the specified taxonomy) by adding an
  equivalent `_exclude` variant to list IDs of terms for which associated posts
  should NOT be returned.

  (props @kadamwhite, [#2756][gh-2756])

* Use `get_comment_type()` when comparing updating comment status.

  Comments having a empty `comment_type` within WordPress bites us again.
  Fixes a bug where comments could not be updated because of bad comparison
  logic.

  (props @joehoyle, [#2753][gh-2753])

[gh-2765]: https://github.com/WP-API/WP-API/issues/2765
[gh-2739]: https://github.com/WP-API/WP-API/issues/2739
[gh-2743]: https://github.com/WP-API/WP-API/issues/2743
[gh-2756]: https://github.com/WP-API/WP-API/issues/2756
[gh-2753]: https://github.com/WP-API/WP-API/issues/2753

= 2.0 Beta 13.0 (March 29, 2016) =

* BREAKING CHANGE: Fix Content-Disposition header parsing.

  Allows regular form submissions from HTML forms, as well as properly formatted HTTP requests from clients. Note: this breaks backwards compatibility, as previously, the header parsing was completely wrong.

  (props @rmccue, [#2239](https://github.com/WP-API/WP-API/pull/2239))

* BREAKING CHANGE: Use compact links for embedded responses if they are available.

  Introduces curies for sites running WordPress 4.5 or greater; no changes for those running WordPress 4.4.

  (props @joehoyle, [#2412](https://github.com/WP-API/WP-API/pull/2412))

* JavaScript client updates:

  * Support lodash, plus older and newer underscore: add an alias for `_.contains`
  * Add args and options on the model/collection prototypes
  * Rework category/tag mixins to support new API structure
  * Add workaround for the null/empty values returned by the API when creating a new post * these values are not accepted for subsequent updates/saves, so explicitly excluding them. See https://github.com/WP-API/WP-API/pull/2393
  * Better handling of the (special) `me` endpoint
  * Schema parsing cleanup
  * Introduce `wp.api.loadPromise` so developers can ensure api load complete before using

  (props @adamsilverstein, [#2403](https://github.com/WP-API/WP-API/pull/2403))

* Only adds alternate link header for publicly viewable CPTs.

  (props @bradyvercher, [#2387](https://github.com/WP-API/WP-API/pull/2387))

* Adds `roles` param for `GET /wp/v2/users`.

  (props @BE-Webdesign, [#2372](https://github.com/WP-API/WP-API/pull/2372))

* Declares `password` in user schema, but never displays it.

  (props @danielbachhuber, [#2386](https://github.com/WP-API/WP-API/pull/2386))

* Permits `edit` context for requests which can edit the user.

  (props @danielbachhuber, [#2383](https://github.com/WP-API/WP-API/pull/2383))

* Adds `rest_pre_insert_{$taxonomy}` filter for terms.

  (props @kjbenk, [#2377](https://github.com/WP-API/WP-API/pull/2377))

* Supports taxonomy collection args on posts endpoint.

  (props @joehoyle, [#2287](https://github.com/WP-API/WP-API/pull/2287))

* Removes post meta link from post response.

  (props @joehoyle, [#2288](https://github.com/WP-API/WP-API/pull/2288))

* Registers `description` attribute when registering args from schema.

  (props @danielbachhuber, [#2362](https://github.com/WP-API/WP-API/pull/2362))

* Uses `$comment` from the database with `rest_insert_comment` action.

  (props @danielbachhuber, [#2349](https://github.com/WP-API/WP-API/pull/2349))

* Removes unnecessary global variables from users controller.

  (props @claudiosmweb, [#2335](https://github.com/WP-API/WP-API/pull/2335))

* Ensures `GET /wp/v2/categories` with out of bounds offset doesn't return results.

  (props @danielbachhuber, [#2313](https://github.com/WP-API/WP-API/pull/2313))

* Adds top-level support for date queries on posts and comments.

  (props @BE-Webdesign, [#2266](https://github.com/WP-API/WP-API/pull/2266), [#2291](https://github.com/WP-API/WP-API/pull/2291))

* Respects `show_avatars` setting for comments.

  (props @BE-Webdesign, [#2271](https://github.com/WP-API/WP-API/pull/2271))

* Uses cached `get_the_terms()` for terms-for-post for better performance.

  (props @rmccue, [#2257](https://github.com/WP-API/WP-API/pull/2257))

* Ensures comments search is an empty string.

  (props @rmccue, [#2256](https://github.com/WP-API/WP-API/pull/2256))

* If no title is provided in create attachment request or file metadata, falls back to filename.

  (props @danielbachhuber, [#2254](https://github.com/WP-API/WP-API/pull/2254))

* Removes unused `$img_url_basename` variable in attachments controller.

  (props @danielbachhuber, [#2250](https://github.com/WP-API/WP-API/pull/2250))

= 2.0 Beta 12.0 (February 9, 2016) =

* BREAKING CHANGE: Removes meta endpoints from primary plugin.

  If your project depends on post meta endpoints, please install [WP REST API Meta Endpoints](https://wordpress.org/plugins/rest-api-meta-endpoints/). For the gory history of meta, read [#1425](https://github.com/WP-API/WP-API/issues/1425) and linked issues. At this time, we recommend using `register_rest_field()` to expose meta ([docs](http://v2.wp-api.org/extending/modifying/)).

  (props @danielbachhuber, [#2172](https://github.com/WP-API/WP-API/pull/2172))

* BREAKING CHANGE: Returns original resource when deleting PTCU.

  Now that all resources require the `force` param, we don't need to wrap delete responses with the `trash` state.

  (props @danielbachhuber, [#2163](https://github.com/WP-API/WP-API/pull/2163))

* BREAKING CHANGE: Uses `roles` rather than `role` in the Users controller.

  Building the REST API gives us the opportunity to standardize on `roles`, instead of having both `roles` and `role`.

  (props @joehoyle, [#2177](https://github.com/WP-API/WP-API/pull/2177))

* BREAKING CHANGES: Moves to consistent use of `context` throughout controllers.

  Contexts limit the data present in the response. Here's how to think of them: `embed` correlates with sidebar representation, `view` represents the primary public view, and `edit` is the data expected for an editor.

  (props @danielbachhuber, [#2205](https://github.com/WP-API/WP-API/pull/2205), [#2204](https://github.com/WP-API/WP-API/pull/2204), [#2203](https://github.com/WP-API/WP-API/pull/2203), [#2218](https://github.com/WP-API/WP-API/pull/2218), [#2216](https://github.com/WP-API/WP-API/pull/2216), [#2230](https://github.com/WP-API/WP-API/pull/2230), [#2184](https://github.com/WP-API/WP-API/pull/2184), [#2235](https://github.com/WP-API/WP-API/pull/2235))

* BREAKING CHANGE: Removes `post_*` query param support for `GET /wp/v2/comments`.

  The proper pattern is to use `GET /wp/v2/posts` to fetch the post IDs to limit the request to.

  (props @danielbachhuber, [#2165](https://github.com/WP-API/WP-API/pull/2165))

* BREAKING CHANGE: Introduces `rest_validate_request_arg()`/`rest_sanitize_request_arg()`.

  Dedicated functions means we can use them for validating / sanitizing query args too. Removes `WP_REST_Controller::validate_schema_property()` and `WP_REST_Controller::sanitize_schema_property()`.

  (props @danielbachhuber, [#2166](https://github.com/WP-API/WP-API/pull/2166), [#2213](https://github.com/WP-API/WP-API/pull/2213))

* Requires minimum value of 1 for `page` param.

  (props @danielbachhuber, [#2241](https://github.com/WP-API/WP-API/pull/2241))

* Introduces `media_type` and `mime_type` params for `GET /wp/v2/media`.

  (props @danielbachhuber, [#2231](https://github.com/WP-API/WP-API/pull/2231))

* Uses the term cache for post data.

  (props @rmccue, [#2234](https://github.com/WP-API/WP-API/pull/2234))

* Supports for querying comments where `post=0`.

  (props @danielbachhuber, [#1865](https://github.com/WP-API/WP-API/pull/1865))

* Exposes taxonomy and post type capabilities in `context=edit`.

  (props @danielbachhuber, [#2216](https://github.com/WP-API/WP-API/pull/2216))

* Errors early when user can't GET types or taxonomies when `context=edit`.

  (props @danielbachhuber, [#2218](https://github.com/WP-API/WP-API/pull/2218))

* Passes original $request context to `prepare_items_query`.

  (props @danielbachhuber, [#2211](https://github.com/WP-API/WP-API/pull/2211))

* Adds `parent` and `parent_exclude` params to GET Comments.

  (props @danielbachhuber, [#2206](https://github.com/WP-API/WP-API/pull/2206))

* Enforces minimum 1 and maximum 100 values for `per_page` parameter.

  (props @danielbachhuber, [#2209](https://github.com/WP-API/WP-API/pull/2209))

* Adds `author` and `author_exclude` params to GET Posts and Comments.

  (props @danielbachhuber, [#2200](https://github.com/WP-API/WP-API/pull/2202), [#2200](https://github.com/WP-API/WP-API/pull/2202))

* Adds `menu_order` param for `GET` Pages; support `menu_order` orderby.

  (props @danielbachhuber, [#2193](https://github.com/WP-API/WP-API/pull/2193))

* Only calls `sanitize_text_field()` when sanitizing `type=string,format=email`.

  (props @danielbachhuber, [#2185](https://github.com/WP-API/WP-API/pull/2185))

* Validates `GET /wp/v2/comments` private query params.

  Returns an error when user doesn't have permission to use them, instead of silently discarding.

  (props @danielbachhuber, [#2178](https://github.com/WP-API/WP-API/pull/2178))

* Explicitly prevents uploading attachments to other attachments or revisions.

  (props @danielbachhuber, [#2180](https://github.com/WP-API/WP-API/pull/2180))

* Permits user urls to be edited through the API.

  (props @danielbachhuber, [#2182](https://github.com/WP-API/WP-API/pull/2182))

* Marks all Status, Type and Taxonomy fields as `readonly`.

  (props @danielbachhuber, [#2181](https://github.com/WP-API/WP-API/pull/2181))

* Adds validation callbacks to collection query params.

  (props @danielbachhuber, [#2170](https://github.com/WP-API/WP-API/pull/2170), [#2171](https://github.com/WP-API/WP-API/pull/2171), [#2176](https://github.com/WP-API/WP-API/pull/2176), [#2174](https://github.com/WP-API/WP-API/pull/2174), [#2175](https://github.com/WP-API/WP-API/pull/2175))

* Links taxonomy terms to the post type collections they support.

  (props @danielbachhuber, [#2167](https://github.com/WP-API/WP-API/pull/2167))

* Returns error when making a `GET` request with invalid context.

  (props @danielbachhuber, [#2169](https://github.com/WP-API/WP-API/pull/2169))

* Adds `trash` status to `GET /wp/v2/statuses`.

  (props @danielbachhuber, [#2158](https://github.com/WP-API/WP-API/pull/2158))

* Indicates when fields have HTML in schema.

  (props @joehoyle, [#2159](https://github.com/WP-API/WP-API/pull/2159))

* Permits viewing of User who has published any Public posts.

  (props @danielbachhuber, [#2155](https://github.com/WP-API/WP-API/pull/2155))

* Respects `show_avatars` option when adding avatars to Users.

  (props @nullvariable, [#2151](https://github.com/WP-API/WP-API/pull/2151))

* Controllers use `$namespace` and `$rest_base` class variables for easier subclassing.

  (props @danielbachhuber, [#2119](https://github.com/WP-API/WP-API/pull/2119), [#2130](https://github.com/WP-API/WP-API/pull/2130), [#2131](https://github.com/WP-API/WP-API/pull/2131), [#2132](https://github.com/WP-API/WP-API/pull/2132), [#2133](https://github.com/WP-API/WP-API/pull/2133), [#2134](https://github.com/WP-API/WP-API/pull/2134), [#2139](https://github.com/WP-API/WP-API/pull/2139), [#2141](https://github.com/WP-API/WP-API/pull/2141), [#2142](https://github.com/WP-API/WP-API/pull/2142))

= 2.0 Beta 11.0 (January 25, 2016) =

* BREAKING CHANGE: Moves Post->Term relations to the Post Resource

  Previously, a client would fetch a Post's Tags with `GET /wp/v2/posts/<id>/tags`.

  In Beta 11, an array of term ids is included on the Post resource.

  The collection of terms for a Post can be fetched with `GET /wp/v2/tags?post=<id>`.

  The `WP_REST_Posts_Terms_Controller` class no longer exists.

  (props @joehoyle, [#2063](https://github.com/WP-API/WP-API/pull/2063))

* BREAKING CHANGE: Adds latest JS client including a minified version.

  See pull request for a summarized changelog.

  (props @adamsilverstein, [#1981](https://github.com/WP-API/WP-API/pull/1981))

* BREAKING CHANGE: Changes `featured_image` attribute on Posts to `featured_media`.

  While featuring other attachment types isn't yet officially supported, this makes it easier for us to introduce the possibility in the future.

  (props @danielbachhuber, [#2044](https://github.com/WP-API/WP-API/pull/2044))

* BREAKING CHANGE: Uses discrete schema title for categories and tags.

  If you've used `register_rest_field( 'term' )`, you'll need to change `'term'` to `'tag'` and/or `'category'`.

  (props @danielbachhuber, [#2005](https://github.com/WP-API/WP-API/pull/2005))

* BREAKING CHANGE: Makes many filters dynamic based on the controller type.

  If you were using the `rest_prepare_term` filter, you'll need to change it to `rest_prepare_post_tag` or `rest_prepare_category`.

  If you were using `rest_post_query` or `rest_terms_query`, you'll need update your use to `rest_page_query`, etc.

  If you were using `rest_post_trashable`, `rest_insert_post` or `rest_delete_post`, they are now dynamic based on the post type slug.

  (props @danielbachhuber, [#2008](https://github.com/WP-API/WP-API/pull/2008), [#2010](https://github.com/WP-API/WP-API/pull/2010), [#2057](https://github.com/WP-API/WP-API/pull/2057), [#2058](https://github.com/WP-API/WP-API/pull/2058))

* Renames `GET /wp/v2/comments` `user` param to `author` to match resource attribute.

  Not a breaking change, because it didn't work in the first place.

  (props @danielbachhuber, [#2105](https://github.com/WP-API/WP-API/pull/2105))

* Adds support for `GET /wp/v2/pages parent=1,2,3`.

  (props @danielbachhuber, [#2101](https://github.com/WP-API/WP-API/pull/2101))

* Persists image metadata title and caption when not present in the request.

  (props @danielbachhuber, [#2079](https://github.com/WP-API/WP-API/pull/2079))

* Add `parent_exclude` param to `GET /wp/v2/posts`.

  (props @danielbachhuber, [#2077](https://github.com/WP-API/WP-API/pull/2077))

* Adds `slug` param support for collections of Posts, Users, and Taxonomy Terms.

  (props @danielbachhuber, [#2071](https://github.com/WP-API/WP-API/pull/2071), [#2072](https://github.com/WP-API/WP-API/pull/2072), [#2103](https://github.com/WP-API/WP-API/pull/2103))

* When a comment is already trashed, returns `410:rest_already_trashed`.

  (props @danielbachhuber, [#2069](https://github.com/WP-API/WP-API/pull/2069))

* Filter the responses by context after processing additional fields.

  (props @danielbachhuber, [#2067](https://github.com/WP-API/WP-API/pull/2067))

* Adds `offset` param support for collections of Posts, Users, Comments, and Taxonomy Terms.

  (props @danielbachhuber, [#2061](https://github.com/WP-API/WP-API/pull/2061), [#2062](https://github.com/WP-API/WP-API/pull/2062), [#2064](https://github.com/WP-API/WP-API/pull/2064), [#2076](https://github.com/WP-API/WP-API/pull/2076))

* Adds `rest_insert_{$taxonomy}` and `rest_delete_{$taxonomy}` actions.

  (props @danielbachhuber, [#2060](https://github.com/WP-API/WP-API/pull/2060))

* Provides more helpful error message/code on Post Create/Update fail.

  (props @danielbachhuber, [#2053](https://github.com/WP-API/WP-API/pull/2053))

* Forces `GET /wp/v2/media` to be limited to `'status' => [ inherit, private, trash ]`

  (props @danielbachhuber, [#2026](https://github.com/WP-API/WP-API/pull/2026))

* Uses more correct error code for `Comment::delete` permission check.

  (props @danielbachhuber, [#2054](https://github.com/WP-API/WP-API/pull/2054))

* Calls `prepare_item_for_response()` directly in create and update methods.

  This lets us pass the original request through, giving the method and its filter genuine context, and avoids an
unnecessary call to `get_item()`.

  (props @danielbachhuber, [#2038](https://github.com/WP-API/WP-API/pull/2038), [#2040](https://github.com/WP-API/WP-API/pull/2040), [#2041](https://github.com/WP-API/WP-API/pull/2041), [#2043](https://github.com/WP-API/WP-API/pull/2043), [#2042](https://github.com/WP-API/WP-API/pull/2042))

* Moves permission check methods across controllers.

  Placing them above the method they're supposed to check makes the code more readable.

  (props @danielbachhuber, [#2030](https://github.com/WP-API/WP-API/pull/2030), [#2029](https://github.com/WP-API/WP-API/pull/2029), [#2034](https://github.com/WP-API/WP-API/pull/2034), [#2036](https://github.com/WP-API/WP-API/pull/2036), [#2037](https://github.com/WP-API/WP-API/pull/2037), [#2035](https://github.com/WP-API/WP-API/pull/2035), [#2039](https://github.com/WP-API/WP-API/pull/2039))

* Requires `force` argument for `DELETE /wp/v2/<taxonomy>/<id>`.

  (props @danielbachhuber, [#2028](https://github.com/WP-API/WP-API/pull/2028))

* Conditionally requires and defines REST API classes and functions.

  (props @danielbachhuber, [#2023](https://github.com/WP-API/WP-API/pull/2023), [#2024](https://github.com/WP-API/WP-API/pull/2024))

* Avoid a duplicate query for the comment count.

  (props @rmccue, [#2015](https://github.com/WP-API/WP-API/pull/2015))

* Parses `$date` if available in `prepare_date_response()`

  (props @adamsilverstein, [#1951](https://github.com/WP-API/WP-API/pull/1951))

* Abstracts `POST /wp/v2/media` permissions check.

  (props @danielbachhuber, [#2003](https://github.com/WP-API/WP-API/pull/2003))

* Adds `exclude` param to getting collections of Posts, Users, Comments, and Taxonomy Terms.

  (props @danielbachhuber, [#1998](https://github.com/WP-API/WP-API/pull/1998), [#1999](https://github.com/WP-API/WP-API/pull/1999), [#2000](https://github.com/WP-API/WP-API/pull/2000), [#2002](https://github.com/WP-API/WP-API/pull/2002))

* Adds `rest_comment_query` for filtering `GET /wp/v2/comments`.

  (props @danielbachhuber, [#2007](https://github.com/WP-API/WP-API/pull/2007))

* Uses HTTP status code `500` for `db_update_error` when creating an attachment.

  (props @danielbachhuber, [#1993](https://github.com/WP-API/WP-API/pull/1993))

* Adds helpful description to `force` param across all `DELETE` registrations

  (props @danielbachhuber, [#2004](https://github.com/WP-API/WP-API/pull/2004), [#2027](https://github.com/WP-API/WP-API/pull/2027))

* In `GET /wp/v2/<taxonomy>`, drops support for `orderby=>term_id`.

  Only one `id` is exposed through the REST API.

  (props @danielbachhuber, [#1990](https://github.com/WP-API/WP-API/pull/1990))

= 2.0 Beta 10.0 (January 11, 2016) =

* SECURITY: Ensure media of private posts are private too.

  Reported by @danielbachhuber on 2016-01-08.

* BREAKING CHANGE: Removes compatibility repo for WordPress 4.3.

  WordPress 4.4 is now the minimum supported WordPress version.

  (props @danielbachhuber, [#1848](https://github.com/WP-API/WP-API/pull/1848))

* BREAKING CHANGE: Changes link relation for types and taxonomies.

  In Beta 9, this link relation was introduced as `item`, which isn't correct. The relation has been changed to `https://api.w.org/items`.

  (props @danielbachhuber, [#1853](https://github.com/WP-API/WP-API/pull/1853))

* BREAKING CHANGE: Introduces `edit` context for `wp/v2/types` and `wp/v2/taxonomies`.

  Some fields have moved into this context, which require `edit_posts` and `manage_terms`, respectively.

  (props @danielbachhuber, [#1894](https://github.com/WP-API/WP-API/pull/1894), [#1864](https://github.com/WP-API/WP-API/pull/1864))

* BREAKING CHANGE: Removes `post_format` as a term `_link` for Posts.

  Post formats aren't a custom taxonomy in the eyes of the REST API.

  (props @danielbachhuber, [#1854](https://github.com/WP-API/WP-API/pull/1854))

* Declares `parent` query param for Pages.

  (props @danielbachhuber, [#1975](https://github.com/WP-API/WP-API/pull/1975))

* Permits logged-in users to query for media.

  (props @danielbachhuber, [#1973](https://github.com/WP-API/WP-API/pull/1973))

* Removes duplicated query params from Terms controller.

  (props @danielbachhuber, [#1963](https://github.com/WP-API/WP-API/pull/1963))

* Adds `include` param to `/wp/v2/posts`, `/wp/v2/users`, `/wp/v2/<taxonomy>` and `/wp/v2/comments`.

  (props @danielbachhuber, [#1961](https://github.com/WP-API/WP-API/pull/1961), [#1964](https://github.com/WP-API/WP-API/pull/1964), [#1968](https://github.com/WP-API/WP-API/pull/1968), [#1971](https://github.com/WP-API/WP-API/pull/1971))

* Ensures `GET /wp/v2/posts` respects `order` and `orderby` params.

  (props @danielbachhuber, [#1962](https://github.com/WP-API/WP-API/pull/1962))

* Fixes fatal by loading `wp-admin/includes/user.php` to expose `wp_delete_user()`.

  (props @danielbachhuber, [#1958](https://github.com/WP-API/WP-API/pull/1958))

* Permits making a post sticky when also supplying an empty password.

  (props @westonruter, [#1949](https://github.com/WP-API/WP-API/pull/1949))

* Uses `WP_REST_Request` internally across controllers.

  (props @danielbachhuber, [#1933](https://github.com/WP-API/WP-API/pull/1933), [#1939](https://github.com/WP-API/WP-API/pull/1939), [#1934](https://github.com/WP-API/WP-API/pull/1934), [#1938](https://github.com/WP-API/WP-API/pull/1938))

* Cleans up permissions checks in `WP_REST_Terms_Controller`.

  (props @danielbachhuber, [#1941](https://github.com/WP-API/WP-API/pull/1941))

* Uses `show_in_rest` to determine publicness for post types.

  (props @danielbachhuber, [#1942](https://github.com/WP-API/WP-API/pull/1942))

* Makes `description` strings available for translation.

  (props @danielbachhuber, [#1944](https://github.com/WP-API/WP-API/pull/1944))

* Checks `assign_terms` cap for taxonomy when managing post terms.

  (props @danielbachhuber, [#1940](https://github.com/WP-API/WP-API/pull/1940))

* Defer to `edit_posts` of the custom post type when accessing private query vars.

  (props @danielbachhuber, [#1886](https://github.com/WP-API/WP-API/pull/1886))

* Allows Terms collection params to be filtered.

  (props @rachelbaker, [#1882](https://github.com/WP-API/WP-API/pull/1882))

* Renames post terms create/delete permissions callback.

  (props @wpsmith, [#1923](https://github.com/WP-API/WP-API/pull/1923))

* Fixes invalid use of 'uri' as schema `type`.

  (props @wpsmith, [#1913](https://github.com/WP-API/WP-API/pull/1913))

* Casts integer with (int) over intval for speed.

  (props @wpsmith, [#1907](https://github.com/WP-API/WP-API/pull/1907))

* Fixes PHP Doc typo for `validate_schema_property` and `sanitize_schema_property`.

  (props @wpsmith, @danielbachhuber, [#1909](https://github.com/WP-API/WP-API/pull/1909), [#1910](https://github.com/WP-API/WP-API/pull/1910))

* Adds a helpful description to the `filter` argument.

  (props @danielbachhuber, [#1885](https://github.com/WP-API/WP-API/pull/1885))

* Changes order of Users response to match schema order.

  (props @rachelbaker, [#1879](https://github.com/WP-API/WP-API/pull/1879))

* Adjusts Posts pagination headers for `filter` params.

  (props @rachelbaker, [#1878](https://github.com/WP-API/WP-API/pull/1878))

* Uses proper status code when failing to get comments of private post.

  (props @danielbachhuber, [#1866](https://github.com/WP-API/WP-API/pull/1867))

* Fixes invalid capability for comments get items permissions callback.

  `manage_comments` doesn't exist; `moderate_comments` does.

  (props @danielbachhuber, [#1866](https://github.com/WP-API/WP-API/pull/1866))

* Permits creating comments without an assigned post.

  (props @danielbachhuber, [#1857](https://github.com/WP-API/WP-API/pull/1857))

* Prevents error notice when `show_in_rest` isn't set for a post type.

  (props @danielbachhuber, [#1852](https://github.com/WP-API/WP-API/pull/1852))

= 2.0 Beta 9.0 (December 11, 2015) =

* BREAKING CHANGE: Move tags and categories to top-level endpoints.

  Tags are now accessible at `/wp/v2/tags`, and categories accessible at `/wp/v2/categories`. Post terms reside at `/wp/v2/posts/<id>/tags` and `/wp/v2/<id>/categories`.

  (props @danielbachhuber, [#1802](https://github.com/WP-API/WP-API/pull/1802))

* BREAKING CHANGE: Return object for requests to `/wp/v2/taxonomies`.

  This is consistent with `/wp/v2/types` and `/wp/v2/statuses`.

  (props @danielbachhuber, [#1825](https://github.com/WP-API/WP-API/pull/1825))

* BREAKING CHANGE: Remove `rest_get_timezone()`.

  `json_get_timezone()` was only ever used in v1. This function causes fatals, and shouldn't be used.

  (props @danielbachhuber, [#1823](https://github.com/WP-API/WP-API/pull/1823))

* BREAKING CHANGE: Rename `register_api_field()` to `register_rest_field()`.

  Introduces a `register_api_field()` function for backwards compat, which calls `_doing_it_wrong()`. However, `register_api_field()` won't ever be committed to WordPress core, so you should update your function calls.

  (props @danielbachhuber, [#1824](https://github.com/WP-API/WP-API/pull/1824))

* BREAKING CHANGE: Change taxonomies' `post_type` argument to `type`.

  It's consistent with how we're exposing post types in the API.

  (props @danielbachhuber, [#1824](https://github.com/WP-API/WP-API/pull/1824))

* Sync infrastructure with shipped in WordPress 4.4.

  * `wp-includes/rest-api/rest-functions.php` is removed, and its functions moved into `wp-includes/rest-api.php`.
  * Send nocache headers for REST requests. [#34832](https://core.trac.wordpress.org/ticket/34832)
  * Fix handling of HEAD requests. [#34837](https://core.trac.wordpress.org/ticket/34837)
  * Mark `WP_REST_Server::get_raw_data()` as static. [#34768](https://core.trac.wordpress.org/ticket/34768)
  * Unabbreviate error string. [#34818](https://core.trac.wordpress.org/ticket/34818)

* Change terms endpoints to use `term_id` not `tt_id`.

  (props @joehoyle, [#1837](https://github.com/WP-API/WP-API/pull/1837))

* Standardize declaration of `context` param for `GET` requests across controllers.

  However, we're still inconsistent in which controllers expose which params. Follow [#1845](https://github.com/WP-API/WP-API/issues/1845) for further discussion.

  (props @danielbachhuber, [#1795](https://github.com/WP-API/WP-API/pull/1795), [#1835](https://github.com/WP-API/WP-API/pull/1835), [#1838](https://github.com/WP-API/WP-API/pull/1838))

* Link types / taxonomies to their collections, and vice versa.

  Collections link to their type / taxonomy with the `about` relation; types / taxonomies link to their colletion with the `item` relation, which is imperfect and may change in the future.

  (props @danielbachhuber, [#1814](https://github.com/WP-API/WP-API/pull/1814), [#1817](https://github.com/WP-API/WP-API/pull/1817), [#1829](https://github.com/WP-API/WP-API/pull/1829). [#1846](https://github.com/WP-API/WP-API/pull/1846))

* Add missing 'wp/v2' in Location Response header when creating new Post Meta.

  (props @johanmynhardt, [#1790](https://github.com/WP-API/WP-API/pull/1790))

* Expose Post collection query params, including `author`, `order`, `orderby` and `status`.

  (props @danielbachhuber, [#1793](https://github.com/WP-API/WP-API/pull/1793))

* Ignore sticky posts by default.

  (props @danielbachhuber, [#1801](https://github.com/WP-API/WP-API/pull/1801))

* Include `full` image size in attachment `sizes` attribute.

  (props @danielbachhuber, [#1806](https://github.com/WP-API/WP-API/pull/1806))

* In text strings, use `id` instead of `ID`.

  `ID` is an implementation artifact. Our Resources use `id`.

  (props @danielbachhuber, [#1803](https://github.com/WP-API/WP-API/pull/1803))

* Ensure `attachment.sizes[]` use `mime_type` instead of `mime-type`.

  (props @danielbachhuber, [#1809](https://github.com/WP-API/WP-API/pull/1809))

* Introduce `rest_authorization_required_code()`.

  Many controllers returned incorrect HTTP codes, which this also fixes.

  (props @danielbachhuber, [#1808](https://github.com/WP-API/WP-API/pull/1808))

* Respect core's `comment_registration` setting.

  If it's enabled, require users to be logged in to comment.

  (props @danielbachhuber, [#1826](https://github.com/WP-API/WP-API/pull/1826))

* Default to wildcard when searching users.

  (props @danielbachhuber, [#1827](https://github.com/WP-API/WP-API/pull/1827))

* Bring the wp-api.js library up to date for v2 of the REST API.

  (props @adamsilverstein, [#1828](https://github.com/WP-API/WP-API/pull/1828))

* Add `rest_prepare_status` filter.

  (props @danielbachhuber, [#1830](https://github.com/WP-API/WP-API/pull/1830))

* Make `prepare_*` filters more consistent.

  (props @danielbachhuber, [#1831](https://github.com/WP-API/WP-API/pull/1831))

* Add `rest_prepare_post_type` filter for post types.

  (props @danielbachhuber, [#1833](https://github.com/WP-API/WP-API/pull/1833))

= 2.0 Beta 8.0 (December 1, 2015) =

* Prevent fatals when uploading attachment by including admin utilities.

  (props @danielbachhuber, [#1756](https://github.com/WP-API/WP-API/pull/1756))

* Return 201 status code when creating a term.

  (props @danielbachhuber, [#1753](https://github.com/WP-API/WP-API/pull/1753))

* Don't permit requesting terms cross routes.

  Clients should only be able to request categories from the category route, and tags from the tag route.

  (props @danielbachhuber, [#1764](https://github.com/WP-API/WP-API/pull/1764))

* Set `fields=>id` when using `WP_User_Query` to fix large memory usage

  (props @joehoyle, [#1770](https://github.com/WP-API/WP-API/pull/1770))

* Fix Post `_link` to attached attachments.

  (props @danielbachhuber, [#1777](https://github.com/WP-API/WP-API/pull/1777))

* Add support for getting a post with a custom public status.

  (props @danielbachhuber, [#1765](https://github.com/WP-API/WP-API/pull/1765))

* Ensure post content doesn't get double-slashed on update.

  (props @joehoyle, [#1772](https://github.com/WP-API/WP-API/pull/1772))

* Change 'int' to 'integer' for `WP_REST_Controller::validate_schema_property()`

  (props @wpsmith, [#1759](https://github.com/WP-API/WP-API/pull/1759))

= 2.0 Beta 7.0 (November 17, 2015) =

* Sync infrastructure from WordPress core as of r35691.

  * Remove `register_api_field()` because it's conceptually tied to `WP_REST_Controller` [#34730](https://core.trac.wordpress.org/ticket/34730)
  * Update the REST API header links to use api.w.org [#34303](https://core.trac.wordpress.org/ticket/34303)
  * Require the `$namespace` argument in `register_rest_route()` [#34416](https://core.trac.wordpress.org/ticket/34416)
  * Include `enum` and `description` in help data [#34543](https://core.trac.wordpress.org/ticket/34543)
  * Save `preg_match` iterations in `WP_REST_Server` [#34488](https://core.trac.wordpress.org/ticket/34488)
  * Don't return route URL in `WP_REST_Request:get_params()` [#34647](https://core.trac.wordpress.org/ticket/34647)

* Restore `register_api_field()` within the plugin.

  (props @danielbachhuber, [#1748](https://github.com/WP-API/WP-API/pull/1748))

* Require admin functions for use of `wp_handle_upload()`, fixing fatal.

  (props @joehoyle, [#1746](https://github.com/WP-API/WP-API/pull/1746))

* Properly handle requesting terms where `parent=0` and `0` is a string.

  (props @danielbachhuber, [#1739](https://github.com/WP-API/WP-API/pull/1739))

* Prevent PHP error notice when `&filter` isn't an array.

  (props @danielbachhuber, [#1734](https://github.com/WP-API/WP-API/pull/1734))

* Change link relations to use api.w.org.

  (props @danielbachhuber, [#1726](https://github.com/WP-API/WP-API/pull/1726))

= 2.0 Beta 6.0 (November 12, 2015) =

* Remove global inclusion of wp-admin/includes/admin.php

  For a long time, the REST API loaded wp-admin/includes/admin.php to make use of specific admin utilities. Now, it only loads those admin utilities when it needs them.

  If your custom endpoints make use of admin utilities, you'll need to make sure to load wp-admin/includes/admin.php before you use them.

  (props @joehoyle, [#1696](https://github.com/WP-API/WP-API/pull/1696))

* Link directly to the featured image in a Post's links.

  (props @rmccue, [#1563](https://github.com/WP-API/WP-API/pull/1563), [#1711](https://github.com/WP-API/WP-API/pull/1711))

* Provide object type as callback argument for custom API fields.

  (props @jtsternberg, [#1714](https://github.com/WP-API/WP-API/pull/1714))

* Change users schema order to be order of importance instead of alpha.

  (props @rachelbaker, [#1708](https://github.com/WP-API/WP-API/pull/1708))

* Clarify documentation for `date` and `modified` attributes.

  (props @danielbachhuber, [#1715](https://github.com/WP-API/WP-API/pull/1715))

* Update the wp-api.js client from the client-js repo.

  (props @rachelbaker, [#1709](https://github.com/WP-API/WP-API/pull/1709))

* Fix the `format` enum to be an array of strings.

  (props @joehoyle, [#1707](https://github.com/WP-API/WP-API/pull/1707))

* Run revisions for collection through `prepare_response_for_collection()`.

  (props @danielbachhuber, @rachelbaker, [#1671](https://github.com/WP-API/WP-API/pull/1671))

* Expose `date_gmt` for `view` context of Posts and Comments.

  (props @danielbachhuber, [#1690](https://github.com/WP-API/WP-API/pull/1690))

* Fix PHP and JS docblock formatting.

  (props @ahmadawais, [#1699](https://github.com/WP-API/WP-API/pull/1698), [#1699](https://github.com/WP-API/WP-API/pull/1699), [#1701](https://github.com/WP-API/WP-API/pull/1701), [#1700](https://github.com/WP-API/WP-API/pull/1700), [#1702](https://github.com/WP-API/WP-API/pull/1702), [#1703](https://github.com/WP-API/WP-API/pull/1703))

* Include `media_details` attribute for attachments in embed context.

  For image attachments, media_details includes a sizes array of image sizes, which is useful for templating.

  (props @danielbachhuber, [#1667](https://github.com/WP-API/WP-API/pull/1667))

* Make `WP_REST_Controller` error messages more helpful by specifying method to subclass.

  (props @danielbachhuber, [#1670](https://github.com/WP-API/WP-API/pull/1670))

* Expose `slug` in `embed` context for Users.

  `user_nicename` is a public attribute, used in user URLs, so this is safe data to present.

  (props @danielbachhuber, [#1666](https://github.com/WP-API/WP-API/pull/1666))

* Handle falsy value from `wp_count_terms()`, fixing fatal.

  (props @joehoyle, [#1641](https://github.com/WP-API/WP-API/pull/1641))

* Correct methods in `WP_REST_SERVER::EDITABLE` description.

  (props @rachelbaker, [#1601](https://github.com/WP-API/WP-API/pull/1601))

* Add the embed context to Users collection query params.

  (props @rachelbaker, [#1591](https://github.com/WP-API/WP-API/pull/1591))

* Add Terms Controller collection args details.

  (props @rachelbaker, [#1603](https://github.com/WP-API/WP-API/pull/1603))

* Set comment author details from current user.

  (props @rmccue, [#1580](https://github.com/WP-API/WP-API/pull/1580))

* More hook documentation.

  (props @adamsilverstein, [#1556](https://github.com/WP-API/WP-API/pull/1556), [#1560](https://github.com/WP-API/WP-API/pull/1560))

* Return the trashed status of deleted posts/comments.

  When a post or a comment is deleted, returns a flag to say whether it's been trashed or properly deleted.

  (props @pento, [#1499](https://github.com/WP-API/WP-API/pull/1499))

* In `WP_REST_Posts_Controller::update_item()`, check the post ID based on the proper post type.

  (props @rachelbaker, [#1497](https://github.com/WP-API/WP-API/pull/1497))

= 2.0 Beta 5.0 (October 23, 2015) =

* Load api-core as a compatibility library

  Now api-core has been merged into WordPress trunk (for 4.4) we should no longer load the infrastructure code when it's already available. This also fixes a fatal error for users who were on trunk.

  (props @rmccue)

* Switch to new mysql_to_rfc3339

  (props @rmccue)

* Double-check term taxonomy

  (props @rmccue)

* Load admin functions

  This was removed from the latest beta of WordPress in the REST API infrastructure, a more long term fix is planned.

  (props @joehoyle)

* Add Add compat shim for renamed `rest_mysql_to_rfc3339()`

  (props @danielbachhuber)

* Compat shim for `wp_is_numeric_array()`

  (props @danielbachhuber)

* Revert Switch to register_post_type_args filter

  (props @joehoyle)

= 2.0 Beta 4.0 (August 14, 2015) =

* Show public user information through the user controller.

  In WordPress as of [r32683](https://core.trac.wordpress.org/changeset/32683) (scheduled for 4.3), `WP_User_Query` now has support for getting users with published posts.

  To match current behaviour in WordPress themes and feeds, we now expose this public user information. This includes the avatar, description, user ID, custom URL, display name, and URL, for users who have published at least one post on the site. This information is available to all clients; other fields and data for all users are still only available when authenticated.

  (props @joehoyle, @rmccue, @Shelob9, [#1397][gh-1397], [#839][gh-839], [#1435][gh-1435])

* Send schema in OPTIONS requests and index.

  Rather than using separate `/schema` endpoints, the schema for items is now available through an OPTIONS request to the route. This means that full documentation is now available for endpoints through an OPTIONS request; this includes available methods, what data you can pass to the endpoint, and the data you'll get back.

  This data is now also available in the main index and namespace indexes. Simply request the index with `context=help` to get full schema data. Warning: this response will be huge. The schema for single endpoints is also available in the collection's OPTIONS response.

  **⚠️ This breaks backwards compatibility** for clients relying on schemas being at their own routes. These clients should instead send `OPTIONS` requests.

  Custom endpoints can register their own schema via the `schema` option on the route. This option should live side-by-side with the endpoints (similar to `relation` in WP's meta queries), so your registration call will look something like:

  ```php
  register_rest_route( 'test-ns', '/test', array(
    array(
      'methods' => 'GET',
      'callback' => 'my_test_callback',
    ),

    'schema' => 'my_schema_callback',
  ) );
  ```

  (props @rmccue, [#1415][gh-1415], [#1222][gh-1222], [#1305][gh-1305])

* Update JavaScript API for version 2.

  Our fantastic JavaScript API from version 1 is now available for version 2, refreshed with the latest and greatest changes.

  As a refresher: if you want to use it, simply make your script depend on `wp-api` when you enqueue it. If you want to enqueue the script manually, add `wp_enqueue_script( 'wp-api' )` to a callback on `wp_enqueue_scripts`.

  (props @tlovett1, @kadamwhite, @nathanrice, [#1374][gh-1374], [#1320][gh-1320])

* Embed links inside items in a collection.

  Previously when fetching a collection of items, you only received the items themselves. To fetch the links as well via embedding, you needed to make a request to the single item with `_embed` set.

  No longer! You can now request a collection with embeds enabled (try `/wp/v2/posts?_embed`). This will embed links inside each item, allowing you to build interface items much easier (for example, post archive pages can get featured image data at the same time).

  This also applies to custom endpoints. Any endpoint that returns a list of objects will automatically have the embedding applied to objects inside the list.

  (props @rmccue, [#1459][gh-1459], [#865][gh-865])

* Fix potential XSS vulnerability.

  Requests from other origins could potentially run code on the API domain, allowing cross-origin access to authentication cookies or similar.

  Reported by @xknown on 2015-07-23.

* Move `/posts` `WP_Query` vars back to `filter` param.

  In version 1, we had internal `WP_Query` vars available via `filter` (e.g. `filter[s]=search+term`). For our first betas of version 2, we tried something different and exposed these directly on the endpoint. The experiment has now concluded; we didn't like this that much, so `filter` is back.

  We plan on adding nicer looking arguments to collections in future releases, with a view towards being consistent across different collections. We also plan on opening up the underlying query vars via `filter` for users, comments, and terms as well.

  **⚠️ This breaks backwards compatibility** for users using WP Query vars. Simply change your `x=y` parameter to `filter[x]=y`.

  (props @WP-API, [#1420][gh-1420])

* Respect `rest_base` for taxonomies.

  **⚠️ This breaks backwards compatibility** by changing the `/wp/v2/posts/{id}/terms/post_tag` endpoint to `/wp/v2/posts/{id}/tag`.

  (props @joehoyle, [#1466][gh-1466])

* Add permission check for retrieving the posts collection in edit context.

  By extension of the fact that getting any individual post yields a forbidden context error when the `context=edit` and the user is not authorized, the user should also not be permitted to list any post items when unauthorized.

  (props @danielpunkass, [#1412][gh-1412])

* Ensure the REST API URL always has a trailing slash.

  Previously, when pretty permalinks were enabled, the API URL during autodiscovery looked like `/wp-json`, whereas the non-pretty permalink URL looked like `?rest_route=/`. These are now consistent, and always end with a slash character to simplify client URL building.

  (props @danielpunkass, @rmccue, [#1426][gh-1426], [#1442][gh-1442], [#1455][gh-1455], [#1467][gh-1467])

* Use `wp_json_encode` instead of `json_encode`

  Since WordPress 4.1, `wp_json_encode` has been available to ensure encoded values are sane, and that non-UTF8 encodings are supported. We now use this function rather than doing the encode ourselves.

  (props @rmccue, @pento, [#1417][gh-1417])

* Add `role` to schema for users.

  The available roles you can assign to a user are now available in the schema as an `enum`.

  (props @joehoyle, [#1400][gh-1400])

* Use the schema for validation inside the comments controller.

  Previously, the schema was merely a decorative element for documentation inside the comments controller. To bring it inline with our other controllers, the schema is now used internally for validation.

  (props @joehoyle, [#1422][gh-1422])

* Don't set the Location header in update responses.

  Previously, the Location header was sent when updating resources due to some inadvertent copypasta. This header should only be sent when creating to direct clients to the new resource, and isn't required when you're already on the correct resource.

  (props @rachelbaker, [#1441][gh-1441])

* Re-enable the `rest_insert_post` action hook for `WP_REST_Posts_Controller`

  This was disabled during 2.0 development to avoid breaking lots of plugins on the `json_insert_post` action. Now that we've changed namespaces and are Mostly Stable (tm), we can re-enable the action.

  (props @jaredcobb, [#1427][gh-1427], [#1424][gh-1424])

* Fix post taxonomy terms link URLs.

  When moving the routes in a previous beta, we forgot to correct the links on post objects to the new correct route. Sorry!

  (props @rachelbaker, @joehoyle, [#1447][gh-1447], [#1383][gh-1383])

* Use `wp_get_attachment_image_src()` on the image sizes in attachments.

  Since the first versions of the API, we've been building attachment URLs via `str_replace`. Who knows why we were doing this, but it caused problems with custom attachment URLs (such as CDN-hosted images). This now correctly uses the internal functions and filters.

  (props @joehoyle, [#1462][gh-1462])

* Make the embed context a default, not forced.

  If you want embeds to bring in full data rather than with `context=edit`, you can now change the link to specify `context=view` explicitly.

  (props @rmccue, [#1464][gh-1464])

* Ensure we always use the `term_taxonomy_id` and never expose `term_id` publicly.

  Previously, `term_id` was inadvertently exposed in some error responses.

  (props @jdolan, [#1430][gh-1430])

* Fix adding alt text to attachments on creation.

  Previously, this could only be set when updating an attachment, not when creating one.

  (props @joehoyle, [#1398][gh-1398])

* Throw an error when registering routes without a namespace.

  Namespaces should **always** be provided when registering routes. We now throw a `doing_it_wrong` error when attempting to register one. (Previously, this caused a warning, or an invalid internal route.)

  If you *really* need to register namespaceless routes (e.g. to replicate an existing API), call `WP_REST_Server::register_route` directly rather than using the convenience function.

  (props @joehoyle, @rmccue, [#1355][gh-1355])

* Show links on embeds.

  Previously, links were accidentally stripped from embedded response data.

  (props @rmccue, [#1472][gh-1472])

* Clarify insufficient permisssion error when editing posts.

  (props @danielpunkass, [#1411][gh-1411])

* Improve @return inline docs for rest_ensure_response()

  (props @Shelob9, [#1328][gh-1328])

* Check taxonomies exist before trying to set properties.

  (props @joehoyle, @rachelbaker, [#1354][gh-1354])

* Update controllers to ensure we use `sanitize_callback` wherever possible.

  (props @joehoyle, [#1399][gh-1399])

* Add more phpDoc documentation, and correct existing documentation.

  (props @Shelob9, @rmccue, [#1432][gh-1432], [#1433][gh-1433], [#1465][gh-1465])

* Update testing infrastructure.

  Travis now runs our coding standards tests in parallel, and now uses the new, faster container-based testing infrastructure.

  (props @ntwb, @frozzare, [#1449][gh-1449], [#1457][gh-1457])

[View all changes](https://github.com/WP-API/WP-API/compare/2.0-beta3...2.0-beta4)

[gh-839]: https://github.com/WP-API/WP-API/issues/839
[gh-865]: https://github.com/WP-API/WP-API/issues/865
[gh-1222]: https://github.com/WP-API/WP-API/issues/1222
[gh-1305]: https://github.com/WP-API/WP-API/issues/1305
[gh-1310]: https://github.com/WP-API/WP-API/issues/1310
[gh-1320]: https://github.com/WP-API/WP-API/issues/1320
[gh-1328]: https://github.com/WP-API/WP-API/issues/1328
[gh-1354]: https://github.com/WP-API/WP-API/issues/1354
[gh-1355]: https://github.com/WP-API/WP-API/issues/1355
[gh-1372]: https://github.com/WP-API/WP-API/issues/1372
[gh-1374]: https://github.com/WP-API/WP-API/issues/1374
[gh-1383]: https://github.com/WP-API/WP-API/issues/1383
[gh-1397]: https://github.com/WP-API/WP-API/issues/1397
[gh-1398]: https://github.com/WP-API/WP-API/issues/1398
[gh-1399]: https://github.com/WP-API/WP-API/issues/1399
[gh-1400]: https://github.com/WP-API/WP-API/issues/1400
[gh-1402]: https://github.com/WP-API/WP-API/issues/1402
[gh-1411]: https://github.com/WP-API/WP-API/issues/1411
[gh-1412]: https://github.com/WP-API/WP-API/issues/1412
[gh-1413]: https://github.com/WP-API/WP-API/issues/1413
[gh-1415]: https://github.com/WP-API/WP-API/issues/1415
[gh-1417]: https://github.com/WP-API/WP-API/issues/1417
[gh-1420]: https://github.com/WP-API/WP-API/issues/1420
[gh-1422]: https://github.com/WP-API/WP-API/issues/1422
[gh-1424]: https://github.com/WP-API/WP-API/issues/1424
[gh-1426]: https://github.com/WP-API/WP-API/issues/1426
[gh-1427]: https://github.com/WP-API/WP-API/issues/1427
[gh-1430]: https://github.com/WP-API/WP-API/issues/1430
[gh-1432]: https://github.com/WP-API/WP-API/issues/1432
[gh-1433]: https://github.com/WP-API/WP-API/issues/1433
[gh-1435]: https://github.com/WP-API/WP-API/issues/1435
[gh-1441]: https://github.com/WP-API/WP-API/issues/1441
[gh-1442]: https://github.com/WP-API/WP-API/issues/1442
[gh-1447]: https://github.com/WP-API/WP-API/issues/1447
[gh-1449]: https://github.com/WP-API/WP-API/issues/1449
[gh-1455]: https://github.com/WP-API/WP-API/issues/1455
[gh-1455]: https://github.com/WP-API/WP-API/issues/1455
[gh-1457]: https://github.com/WP-API/WP-API/issues/1457
[gh-1459]: https://github.com/WP-API/WP-API/issues/1459
[gh-1462]: https://github.com/WP-API/WP-API/issues/1462
[gh-1464]: https://github.com/WP-API/WP-API/issues/1464
[gh-1465]: https://github.com/WP-API/WP-API/issues/1465
[gh-1466]: https://github.com/WP-API/WP-API/issues/1466
[gh-1467]: https://github.com/WP-API/WP-API/issues/1467
[gh-1472]: https://github.com/WP-API/WP-API/issues/1472

= 2.0 Beta 3.0 (July 1, 2015) =

* Add ability to declare sanitization and default options for schema fields.

  The `arg_options` array can be used to declare the sanitization callback,
  default value, or requirement of a field.

  (props @joehoyle, [#1345][gh-1345])
  (props @joehoyle, [#1346][gh-1346])

* Expand supported parameters for creating and updating Comments.

  (props @rachelbaker, [#1245][gh-1245])

* Declare collection parameters for Terms of a Post.

  Define the available collection parameters in `get_collection_params()` and
  allow Terms of a Post to be queried by term order.

  (props @danielbachhuber, [#1332][gh-1332])

* Improve the Attachment error message for an invalid Content-Disposition

  (props @danielbachhuber, [#1317][gh-1317])

* Return 200 status when updating Attachments, Comments, and Users.

  (props @rachelbaker, [#1348][gh-1348])

* Remove unnecessary `handle_format_param()` method.

  (props @danielbachhuber, [#1331][gh-1331])

* Add `author_avatar_url` field to the Comment response and schema.

  (props @rachelbaker [#1327][gh-1327])

* Introduce `rest_do_request()` for making REST requests internally.

  (props @danielbachhuber, [#1333][gh-1333])

* Remove unused DateTime class.

  (props @rmccue, [#1314][gh-1314])

* Add inline documentation for `$wp_rest_server` global.

  (props @Shelob9, [#1324][gh-1324])

  [View all changes](https://github.com/WP-API/WP-API/compare/2.0-beta2...2.0-beta3)
  [gh-1245]: https://github.com/WP-API/WP-API/issues/1245
  [gh-1314]: https://github.com/WP-API/WP-API/issues/1314
  [gh-1317]: https://github.com/WP-API/WP-API/issues/1317
  [gh-1318]: https://github.com/WP-API/WP-API/issues/1318
  [gh-1324]: https://github.com/WP-API/WP-API/issues/1324
  [gh-1326]: https://github.com/WP-API/WP-API/issues/1326
  [gh-1327]: https://github.com/WP-API/WP-API/issues/1327
  [gh-1331]: https://github.com/WP-API/WP-API/issues/1331
  [gh-1332]: https://github.com/WP-API/WP-API/issues/1332
  [gh-1333]: https://github.com/WP-API/WP-API/issues/1333
  [gh-1345]: https://github.com/WP-API/WP-API/issues/1345
  [gh-1346]: https://github.com/WP-API/WP-API/issues/1346
  [gh-1347]: https://github.com/WP-API/WP-API/issues/1347
  [gh-1348]: https://github.com/WP-API/WP-API/issues/1348

= 2.0 Beta 2.0 (May 28, 2015) =

* Load the WP REST API before the main query runs.

  The `rest_api_loaded` function now hooks into the `parse_request` action.
  This change prevents the main query from being run on every request and
  allows sites to set `WP_USE_THEMES` to `false`.  Previously, the main query
  was always being run (`SELECT * FROM wp_posts LIMIT 10`), even though the
  result was never used and couldn't be cached.

  (props @rmccue, [#1270][gh-1270])

* Register a new field on an existing WordPress object type.

  Introduces `register_api_field()` to add a field to an object and
  its schema.

  (props @joehoyle, @rachelbaker, [#927][gh-927])
  (props @joehoyle, [#1207][gh-1207])
  (props @joehoyle, [#1243][gh-1243])

* Add endpoints for viewing, creating, updating, and deleting Terms for a Post.

  The new `WP_REST_Posts_Terms_Controller` class controller supports routes for
  Terms that belong to a Post.

  (props @joehoyle, @danielbachhuber, [#1216][gh-1216])

* Add pagination headers for collection queries.

  The `X-WP-Total` and `X-WP-TotalPages` are now present in terms, comments,
  and users collection responses.

  (props @danielbachhuber, [#1182][gh-1182])
  (props @danielbachhuber, [#1191][gh-1191])
  (props @danielbachhuber, @joehoyle, [#1197][gh-1197])

* List registered namespaces in the index for feature detection.

  The index (`/wp-json` by default) now contains a list of the available
  namespaces. This allows for simple feature detection. You can grab the index
  and check namespaces for `wp/v3` or `pluginname/v2`, which indicate the
  supported endpoints on the site.

  (props @rmccue,, [#1283][gh-1283])

* Standardize link property relations and support embedding for all resources.

  Change link properties to use IANA-registered relations.  Also adds embedding
  support to Attachments, Comments and Terms.

  (props @rmccue, @rachelbaker, [#1284][gh-1284])

* Add support for Composer dependency management.

  Allows you to recursively install/update the WP REST API inside of WordPress
  plugins or themes.

  (props @QWp6t, [#1157][gh-1157])

* Return full objects in the delete response.

  Instead of returning a random message when deleting a Post, Comment, Term, or
  User provide the original resource data.

  (props @danielbachhuber, [#1253][gh-1253])
  (props @danielbachhuber, [#1254][gh-1254])
  (props @danielbachhuber, [#1255][gh-1255])
  (props @danielbachhuber, [#1256][gh-1256])

* Return programmatically readable error messages for invalid or missing
  required parameters.

  (props @joehoyle, [#1175][gh-1175])

* Declare supported arguments for Comment and User collection queries.

  (props @danielbachhuber, [#1211][gh-1211])
  (props @danielbachhuber, [#1217][gh-1217])

* Automatically validate parameters based on Schema data.

  (props @joehoyle, [#1128][gh-1128])

* Use the `show_in_rest` attributes for exposing Taxonomies.

  (props @joehoyle, [#1279][gh-1279])

* Handle `parent` when creating or updating a Term.

  (props @joehoyle, [#1221][gh-1221])

* Limit fields returned in `embed` context User responses.

  (props @rachelbaker, [#1251][gh-1251])

* Only include `parent` in term response when tax is hierarchical.

  (props @danielbachhuber, [#1189][gh-1189])

* Fix bug in creating comments if `type` was not set.

  (props @rachelbaker, [#1244][gh-1244])

* Rename `post_name` field to `post_slug`.

  (props @danielbachhuber, [#1235][gh-1235])

* Add check when creating a user to verify the provided role is valid.

  (props @rachelbaker, [#1267][gh-1267])

* Add link properties to the Post Status response.

  (props @joehoyle, [#1243][gh-1243])

* Return `0` for `parent` in Post response instead of `null`.

  (props @danielbachhuber, [#1269][gh-1269])

* Only link `author` when there's a valid author

  (props @danielbachhuber, [#1203][gh-1203])

* Only permit querying by parent term when tax is hierarchical.

  (props @danielbachhuber, [#1219][gh-1219])

* Only permit deleting posts of the proper type

  (props @danielbachhuber, [#1257][gh-1257])

* Set pagination headers even when no found posts.

  (props @danielbachhuber, [#1209][gh-1209])

* Correct prefix in `rest_request_parameter_order` filter.

  (props @quasel, [#1158][gh-1158])

* Retool `WP_REST_Terms_Controller` to follow Posts controller pattern.

  (props @danielbachhuber, [#1170][gh-1170])

* Remove unused `accept_json argument` from the `register_routes` method.

  (props @quasel, [#1160][gh-1160])

* Fix typo in `sanitize_params` inline documentation.

  (props @Shelob9, [#1226][gh-1226])

* Remove commented out code in dispatch method.

  (props @rachelbaker, [#1162][gh-1162])


[View all changes](https://github.com/WP-API/WP-API/compare/2.0-beta1.1...2.0-beta2)
[gh-927]: https://github.com/WP-API/WP-API/issues/927
[gh-1128]: https://github.com/WP-API/WP-API/issues/1128
[gh-1157]: https://github.com/WP-API/WP-API/issues/1157
[gh-1158]: https://github.com/WP-API/WP-API/issues/1158
[gh-1160]: https://github.com/WP-API/WP-API/issues/1160
[gh-1162]: https://github.com/WP-API/WP-API/issues/1162
[gh-1168]: https://github.com/WP-API/WP-API/issues/1168
[gh-1170]: https://github.com/WP-API/WP-API/issues/1170
[gh-1171]: https://github.com/WP-API/WP-API/issues/1171
[gh-1175]: https://github.com/WP-API/WP-API/issues/1175
[gh-1176]: https://github.com/WP-API/WP-API/issues/1176
[gh-1177]: https://github.com/WP-API/WP-API/issues/1177
[gh-1181]: https://github.com/WP-API/WP-API/issues/1181
[gh-1182]: https://github.com/WP-API/WP-API/issues/1182
[gh-1188]: https://github.com/WP-API/WP-API/issues/1188
[gh-1189]: https://github.com/WP-API/WP-API/issues/1189
[gh-1191]: https://github.com/WP-API/WP-API/issues/1191
[gh-1197]: https://github.com/WP-API/WP-API/issues/1197
[gh-1200]: https://github.com/WP-API/WP-API/issues/1200
[gh-1203]: https://github.com/WP-API/WP-API/issues/1203
[gh-1207]: https://github.com/WP-API/WP-API/issues/1207
[gh-1209]: https://github.com/WP-API/WP-API/issues/1209
[gh-1210]: https://github.com/WP-API/WP-API/issues/1210
[gh-1211]: https://github.com/WP-API/WP-API/issues/1211
[gh-1216]: https://github.com/WP-API/WP-API/issues/1216
[gh-1217]: https://github.com/WP-API/WP-API/issues/1217
[gh-1219]: https://github.com/WP-API/WP-API/issues/1219
[gh-1221]: https://github.com/WP-API/WP-API/issues/1221
[gh-1226]: https://github.com/WP-API/WP-API/issues/1226
[gh-1235]: https://github.com/WP-API/WP-API/issues/1235
[gh-1243]: https://github.com/WP-API/WP-API/issues/1243
[gh-1244]: https://github.com/WP-API/WP-API/issues/1244
[gh-1249]: https://github.com/WP-API/WP-API/issues/1249
[gh-1251]: https://github.com/WP-API/WP-API/issues/1251
[gh-1253]: https://github.com/WP-API/WP-API/issues/1253
[gh-1254]: https://github.com/WP-API/WP-API/issues/1254
[gh-1255]: https://github.com/WP-API/WP-API/issues/1255
[gh-1256]: https://github.com/WP-API/WP-API/issues/1256
[gh-1257]: https://github.com/WP-API/WP-API/issues/1257
[gh-1259]: https://github.com/WP-API/WP-API/issues/1259
[gh-1267]: https://github.com/WP-API/WP-API/issues/1267
[gh-1268]: https://github.com/WP-API/WP-API/issues/1268
[gh-1269]: https://github.com/WP-API/WP-API/issues/1269
[gh-1270]: https://github.com/WP-API/WP-API/issues/1270
[gh-1276]: https://github.com/WP-API/WP-API/issues/1276
[gh-1277]: https://github.com/WP-API/WP-API/issues/1277
[gh-1279]: https://github.com/WP-API/WP-API/issues/1279
[gh-1283]: https://github.com/WP-API/WP-API/issues/1283
[gh-1284]: https://github.com/WP-API/WP-API/issues/1284
[gh-1295]: https://github.com/WP-API/WP-API/issues/1295
[gh-1301]: https://github.com/WP-API/WP-API/issues/1301


= 2.0 Beta 1.1 =

* Fix user access security vulnerability.

  Authenticated users were able to escalate their privileges bypassing the
  expected capabilities check.

  Reported by @kacperszurek on 2015-05-16.

= 2.0 Beta 1 (April 28, 2015) =

Partial rewrite and evolution of the REST API to prepare for core integration.

For versions 0.x through 1.x, see the [legacy plugin changelog](https://wordpress.org/plugins/json-rest-api/changelog/).
