WP REST API Cache
====
Enable caching for WordPress REST API and increase speed of your application

- [Installation](#installation)
- [Filters](#filters)
- [How to use filters](#how-to-use-filters)

Installation
====
1. Copy the `wp-rest-api-cache` folder into your `wp-content/plugins` folder
2. Activate the `WP REST API Cache` plugin via the plugin admin page

Filters
====
| Filter    | Argument(s) |
|-----------|-----------|
| rest_cache_headers | array **$headers**<br>string **$request_uri**<br>WP_REST_Server **$server**<br>WP_REST_Request **$request** |
| rest_cache_skip | boolean **$skip** ( default: WP_DEBUG )<br>string **$request_uri**<br>WP_REST_Server **$server**<br>WP_REST_Request **$request** |
| rest_cache_key | string **$request_uri**<br>WP_REST_Server **$server**<br>WP_REST_Request **$request** |
| rest_cache_timeout | int **$timeout**<br>int **$length**<br>int **$period** |
| rest_cache_update_options | array **$options** |
| rest_cache_get_options | array **$options** |
| rest_cache_show_admin | boolean **$show** |
| rest_cache_show_admin_menu | boolean **$show** |
| rest_cache_show_admin_bar_menu | boolean **$show** |

How to use filters
----
- **sending headers**

```PHP
add_filter( 'rest_cache_headers', function( $headers ) {
	$headers['Cache-Control'] = 'public, max-age=3600';
	
	return $headers;
} );
```

- **changing the cache timeout**

```PHP
add_filter( 'rest_cache_timeout', function() {
	// https://codex.wordpress.org/Transients_API#Using_Time_Constants
	return 15 * DAY_IN_SECONDS;
} );
```
or
```PHP
add_filter( 'rest_cache_get_options', function( $options ) {
	if ( ! isset( $options['timeout'] ) ) {
		$options['timeout'] = array();
	}

	// https://codex.wordpress.org/Transients_API#Using_Time_Constants
	$options['timeout']['length'] = 15;
	$options['timeout']['period'] = DAY_IN_SECONDS;
	
	return $options;
} );
```

- **skipping cache**

```PHP
add_filter( 'rest_cache_skip', function( $skip, $request_uri ) {
	if ( ! $skip && false !== stripos( 'wp-json/acf/v2', $request_uri ) ) {
		return true;
	}

	return $skip;
}, 10, 2 );
```

- **show / hide admin links**

![WP REST API Cache](http://airesgoncalves.com.br/screenshot/wp-rest-api-cache/readme/filter-admin-show.gif)