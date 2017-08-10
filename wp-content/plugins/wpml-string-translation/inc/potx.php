<?php
/**
 * @package wpml-core
 */

// $Id: potx.inc,v 1.1.2.17.2.7.2.19.4.1 2009/07/19 12:54:42 goba Exp $

/**
 * @file
 *   Extraction API used by the web and command line interface.
 *
 *   This include file implements the default string and file version
 *   storage as well as formatting of POT files for web download or
 *   file system level creation. The strings, versions and file contents
 *   are handled with global variables to reduce the possible memory overhead
 *   and API clutter of passing them around. Custom string and version saving
 *   functions can be implemented to use the functionality provided here as an
 *   API for Drupal code to translatable string conversion.
 *
 *   The potx-cli.php script can be used with this include file as
 *   a command line interface to string extraction. The potx.module
 *   can be used as a web interface for manual extraction.
 *
 *   For a module using potx as an extraction API, but providing more
 *   sophisticated functionality on top of it, look into the
 *   'Localization server' module: http://drupal.org/project/l10n_server
 */

/**
 * Silence status reports.
 */
define('POTX_STATUS_SILENT', 0);

/**
 * Drupal message based status reports.
 */
define('POTX_STATUS_MESSAGE', 1);

/**
 * Command line status reporting.
 *
 * Status goes to standard output, errors to standard error.
 */
define('POTX_STATUS_CLI', 2);

/**
 * Structured array status logging.
 *
 * Useful for coder review status reporting.
 */
define('POTX_STATUS_STRUCTURED', 3);

/**
 * Core parsing mode:
 *  - .info files folded into general.pot
 *  - separate files generated for modules
 */
define('POTX_BUILD_CORE', 0);

/**
 * Multiple files mode:
 *  - .info files folded into their module pot files
 *  - separate files generated for modules
 */
define('POTX_BUILD_MULTIPLE', 1);

/**
 * Single file mode:
 *  - all files folded into one pot file
 */
define('POTX_BUILD_SINGLE', 2);

/**
 * Save string to both installer and runtime collection.
 */
define('POTX_STRING_BOTH', 0);

/**
 * Save string to installer collection only.
 */
define('POTX_STRING_INSTALLER', 1);

/**
 * Save string to runtime collection only.
 */
define('POTX_STRING_RUNTIME', 2);

/**
 * Parse source files in Drupal 5.x format.
 */
define('POTX_API_5', 5);

/**
 * Parse source files in Drupal 6.x format.
 *
 * Changes since 5.x documented at http://drupal.org/node/114774
 */
define('POTX_API_6', 6);

/**
 * Parse source files in Drupal 7.x format.
 *
 * Changes since 6.x documented at http://drupal.org/node/224333
 */
define('POTX_API_7', 7);

/**
 * When no context is used. Makes it easy to look these up.
 */
define('POTX_CONTEXT_NONE', NULL);

/**
 * When there was a context identification error.
 */
define('POTX_CONTEXT_ERROR', FALSE);

/**
 * Process a file and put extracted information to the given parameters.
 *
 * @param $file_path
 *   Complete path to file to process.
 * @param $strip_prefix
 *   An integer denoting the number of chars to strip from filepath for output.
 * @param $save_callback
 *   Callback function to use to save the collected strings.
 * @param $version_callback
 *   Callback function to use to save collected version numbers.
 * @param $default_domain
 *   Default domain to be used if one can't be found.
 */
function _potx_process_file($file_path,
							$strip_prefix = 0,
							$save_callback = '_potx_save_string',
							$version_callback = '_potx_save_version',
							$default_domain = '') {

  global $_potx_tokens, $_potx_lookup;

  // Always grab the CVS version number from the code
	if ( !wpml_st_file_path_is_valid( $file_path ) ) {
		return;
	}
  $code = file_get_contents($file_path);
  $file_name = $strip_prefix > 0 ? substr($file_path, $strip_prefix) : $file_path;
  _potx_find_version_number($code, $file_name, $version_callback);

  // Extract raw PHP language tokens.
  $raw_tokens = token_get_all($code);
  unset($code);

  // Remove whitespace and possible HTML (the later in templates for example),
  // count line numbers so we can include them in the output.
  $_potx_tokens = array();
  $_potx_lookup = array();
  $token_number = 0;
  $line_number = 1;
         // Fill array for finding token offsets quickly.
         $src_tokens = array(
            '__', 'esc_attr__', 'esc_html__', '_e', 'esc_attr_e', 'esc_html_e',
            '_x', 'esc_attr_x', 'esc_html_x', '_ex',
            '_n', '_nx'
         );
  foreach ($raw_tokens as $token) {
    if ((!is_array($token)) || (($token[0] != T_WHITESPACE) && ($token[0] != T_INLINE_HTML))) {
      if (is_array($token)) {
        $token[] = $line_number;

         if ($token[0] == T_STRING || ($token[0] == T_VARIABLE && in_array($token[1], $src_tokens))) {
           if (!isset($_potx_lookup[$token[1]])) {
             $_potx_lookup[$token[1]] = array();
           }
           $_potx_lookup[$token[1]][] = $token_number;
         }
      }
      $_potx_tokens[] = $token;
      $token_number++;
    }
    // Collect line numbers.
    if (is_array($token)) {
      $line_number += count(explode("\n", $token[1])) - 1;
    }
    else {
      $line_number += count(explode("\n", $token)) - 1;
    }
  }
  unset($raw_tokens);

  if(!empty($src_tokens))
  foreach($src_tokens as $tk){
    _potx_find_t_calls_with_context($file_name, $save_callback, $tk, $default_domain);
  }

}

/**
 * Escape quotes in a strings depending on the surrounding
 * quote type used.
 *
 * @param $str
 *   The strings to escape
 */
function _potx_format_quoted_string($str) {
  $quo = substr($str, 0, 1);
  $str = substr($str, 1, -1);
  if ($quo == '"') {
    $str = stripcslashes($str);
  }
  else {
    $str = strtr($str, array("\\'" => "'", "\\\\" => "\\"));
  }
  return addcslashes($str, "\0..\37\\\"");
}

/**
 * Output a marker error with an extract of where the error was found.
 *
 * @param $file
 *   Name of file
 * @param $line
 *   Line number of error
 * @param $marker
 *   Function name with which the error was identified
 * @param $ti
 *   Index on the token array
 * @param $error
 *   Helpful error message for users.
 * @param $docs_url
 *   Documentation reference.
 */
function _potx_marker_error($file, $line, $marker, $ti, $error, $docs_url = NULL) {
  global $_potx_tokens;

  $tokens = '';
  $ti += 2;
  $tc = count($_potx_tokens);
  $par = 1;
  while ((($tc - $ti) > 0) && $par) {
    if (is_array($_potx_tokens[$ti])) {
      $tokens .= $_potx_tokens[$ti][1];
    }
    else {
      $tokens .= $_potx_tokens[$ti];
      if ($_potx_tokens[$ti] == "(") {
        $par++;
      }
      else if ($_potx_tokens[$ti] == ")") {
        $par--;
      }
    }
    $ti++;
  }
  potx_status('error', $error, $file, $line, $marker .'('. $tokens, $docs_url);
}

/**
 * Status notification function.
 *
 * @param $op
 *   Operation to perform or type of message text.
 *     - set:    sets the reporting mode to $value
 *               use one of the POTX_STATUS_* constants as $value
 *     - get:    returns the list of error messages recorded
 *               if $value is true, it also clears the internal message cache
 *     - error:  sends an error message in $value with optional $file and $line
 *     - status: sends a status message in $value
 * @param $value
 *   Value depending on $op.
 * @param $file
 *   Name of file the error message is related to.
 * @param $line
 *   Number of line the error message is related to.
 * @param $excerpt
 *   Excerpt of the code in question, if available.
 * @param $docs_url
 *   URL to the guidelines to follow to fix the problem.
 */
function potx_status($op, $value = NULL, $file = NULL, $line = NULL, $excerpt = NULL, $docs_url = NULL) {
  static $mode = POTX_STATUS_CLI;
  static $messages = array();

  switch ($op) {
    case 'set':
      // Setting the reporting mode.
      $mode = $value;
      return;

    case 'get':
      // Getting the errors. Optionally deleting the messages.
      $errors = $messages;
      if (!empty($value)) {
        $messages = array();
      }
      return $errors;

    case 'error':
    case 'status':

      // Location information is required in 3 of the four possible reporting
      // modes as part of the error message. The structured mode needs the
      // file, line and excerpt info separately, not in the text.
      $location_info = '';
      if (($mode != POTX_STATUS_STRUCTURED) && isset($file)) {
        if (isset($line)) {
          if (isset($excerpt)) {
            $location_info = potx_t('At %excerpt in %file on line %line.', array('%excerpt' => $excerpt, '%file' => $file, '%line' => $line));
          }
          else {
            $location_info = potx_t('In %file on line %line.', array('%file' => $file, '%line' => $line));
          }
        }
        else {
          if (isset($excerpt)) {
            $location_info = potx_t('At %excerpt in %file.', array('%excerpt' => $excerpt, '%file' => $file));
          }
          else {
            $location_info = potx_t('In %file.', array('%file' => $file));
          }
        }
      }

      // Documentation helpers are provided as readable text in most modes.
      $read_more = '';
      if (($mode != POTX_STATUS_STRUCTURED) && isset($docs_url)) {
        $read_more = ($mode == POTX_STATUS_CLI) ? potx_t('Read more at @url', array('@url' => $docs_url)) : potx_t('Read more at <a href="@url">@url</a>', array('@url' => $docs_url));
      }

      // Error message or progress text to display.
      switch ($mode) {
        case POTX_STATUS_CLI:
          if(defined('STDERR') && defined('STDOUT')){
            fwrite($op == 'error' ? STDERR : STDOUT, join("\n", array($value, $location_info, $read_more)) ."\n\n");
          }
          break;
        case POTX_STATUS_SILENT:
          if ($op == 'error') {
            $messages[] = join(' ', array($value, $location_info, $read_more));
          }
          break;
        case POTX_STATUS_STRUCTURED:
          if ($op == 'error') {
            $messages[] = array($value, $file, $line, $excerpt, $docs_url);
          }
          break;
      }
      return;
  }
}

/**
 * Detect all occurances of t()-like calls.
 *
 * These sequences are searched for:
 *   T_STRING("$function_name") + "(" + T_CONSTANT_ENCAPSED_STRING + ")"
 *   T_STRING("$function_name") + "(" + T_CONSTANT_ENCAPSED_STRING + ","
 *
 * @param $file
 *   Name of file parsed.
 * @param $save_callback
 *   Callback function used to save strings.
 * @param function_name
 *   The name of the function to look for (could be 't', '$t', 'st'
 *   or any other t-like function).
 * @param $string_mode
 *   String mode to use: POTX_STRING_INSTALLER, POTX_STRING_RUNTIME or
 *   POTX_STRING_BOTH.
 */
function _potx_find_t_calls($file, $save_callback, $function_name = 't', $string_mode = POTX_STRING_RUNTIME) {
  global $_potx_tokens, $_potx_lookup;

  // Lookup tokens by function name.
  if (isset($_potx_lookup[$function_name])) {
    foreach ($_potx_lookup[$function_name] as $ti) {
      list($ctok, $par, $mid, $rig) = array($_potx_tokens[$ti], $_potx_tokens[$ti+1], $_potx_tokens[$ti+2], $_potx_tokens[$ti+3]);
      list($type, $string, $line) = $ctok;
      if ($par == "(") {
        if (in_array($rig, array(")", ","))
          && (is_array($mid) && ($mid[0] == T_CONSTANT_ENCAPSED_STRING))) {
            // This function is only used for context-less call types.
            $save_callback(_potx_format_quoted_string($mid[1]), POTX_CONTEXT_NONE, $file, $line, $string_mode);
        }
        else {
          // $function_name() found, but inside is something which is not a string literal.
          _potx_marker_error($file, $line, $function_name, $ti, potx_t('The first parameter to @function() should be a literal string. There should be no variables, concatenation, constants or other non-literal strings there.', array('@function' => $function_name)), 'http://drupal.org/node/322732');
        }
      }
    }
  }
}

/**
 * Detect all occurances of t()-like calls from Drupal 7 (with context).
 *
 * These sequences are searched for:
 *   T_STRING("$function_name") + "(" + T_CONSTANT_ENCAPSED_STRING + ")"
 *   T_STRING("$function_name") + "(" + T_CONSTANT_ENCAPSED_STRING + ","
 *   and then an optional value for the replacements and an optional array
 *   for the options with an optional context key.
 *
 * @param $file
 *   Name of file parsed.
 * @param $save_callback
 *   Callback function used to save strings.
 * @param string $function_name
 * @param string $default_domain
 * @param int $string_mode
 *   String mode to use: POTX_STRING_INSTALLER, POTX_STRING_RUNTIME or
 *   POTX_STRING_BOTH.
 *
 * @internal param $function_name The name of the function to look for (could be 't', '$t', 'st'*   The name of the function to look for (could be 't', '$t', 'st'
 *   or any other t-like function). Drupal 7 only supports context on t().
 */
function _potx_find_t_calls_with_context(
	$file,
	$save_callback,
	$function_name = '_e',
	$default_domain = '',
	$string_mode = POTX_STRING_RUNTIME
) {
	global $_potx_tokens, $_potx_lookup;

	// Lookup tokens by function name.
	if ( isset( $_potx_lookup[ $function_name ] ) ) {
		foreach ( $_potx_lookup[ $function_name ] as $ti ) {
			list( $ctok, $par, $mid, $rig ) = array(
				$_potx_tokens[ $ti ],
				$_potx_tokens[ $ti + 1 ],
				$_potx_tokens[ $ti + 2 ],
				$_potx_tokens[ $ti + 3 ]
			);
			list( $type, $string, $line ) = $ctok;
			if ( $par == "(" ) {
				if ( in_array( $rig, array( ")", "," ) )
					 && ( is_array( $mid ) && ( $mid[ 0 ] == T_CONSTANT_ENCAPSED_STRING ) )
				) {
					// By default, there is no context.
					$domain = POTX_CONTEXT_NONE;
					if ( $rig == ',' ) {
						if ( in_array( $function_name, array( '_x', '_ex', 'esc_attr_x', 'esc_html_x' ), true ) ) {
							$domain_offset  = 6;
							$context_offset = 4;
						} elseif ( $function_name == '_n' ) {
							$domain_offset  = _potx_find_end_of_function( $ti, '(', ')' ) - 1 - $ti;
							$context_offset = false;
							$text_plural    = $_potx_tokens[ $ti + 4 ][ 1 ];
						} elseif ( $function_name == '_nx' ) {
							$domain_offset  = _potx_find_end_of_function( $ti, '(', ')' ) - 1 - $ti;
							$context_offset = $domain_offset - 2;
							$text_plural    = $_potx_tokens[ $ti + 4 ][ 1 ];
						} else {
							$domain_offset  = 4;
							$context_offset = false;
						}

						if ( ! isset( $_potx_tokens[ $ti + $domain_offset ][ 1 ] )
							 || ! preg_match( '#^(\'|")(.+)#', $_potx_tokens[ $ti + $domain_offset ][ 1 ] )
						) {
							if ( $default_domain ) {
								$domain = $default_domain;
							} else {
								continue;
							}
						} else {
							$domain = trim( $_potx_tokens[ $ti + $domain_offset ][ 1 ], "\"' " );
						}

						// exception for gettext calls with contexts
						if ( false !== $context_offset && isset( $_potx_tokens[ $ti + $context_offset ] ) ) {
							if ( ! preg_match( '#^(\'|")(.+)#', @$_potx_tokens[ $ti + $context_offset ][ 1 ] ) ) {
								$constant_val = @constant( $_potx_tokens[ $ti + $context_offset ][ 1 ] );
								if ( ! is_null( $constant_val ) ) {
									$context = $constant_val;
								} else {
									if ( function_exists( @$_potx_tokens[ $ti + $context_offset ][ 1 ] ) ) {
										$context = @$_potx_tokens[ $ti + $context_offset ][ 1 ]();
										if ( empty( $context ) ) {
											continue;
										}
									} else {
										continue;
									}
								}
							} else {
								$context = trim( $_potx_tokens[ $ti + $context_offset ][ 1 ], "\"' " );
							}

						} else {
							$context = false;
						}
					}
					if ( $domain !== POTX_CONTEXT_ERROR && is_callable( $save_callback, false, $callback_name ) ) {
						// Only save if there was no error in context parsing.
						call_user_func( $save_callback,
										_potx_format_quoted_string( $mid[ 1 ] ),
										$domain,
										@strval( $context ),
										$file,
										$line,
										$string_mode );
						if ( isset( $text_plural ) ) {
							call_user_func( $save_callback,
											_potx_format_quoted_string( $text_plural ),
											$domain,
											$context,
											$file,
											$line,
											$string_mode );
						}
					}
				} else {
					// $function_name() found, but inside is something which is not a string literal.
					_potx_marker_error( $file,
										$line,
										$function_name,
										$ti,
										potx_t( 'The first parameter to @function() should be a literal string. There should be no variables, concatenation, constants or other non-literal strings there.',
												array( '@function' => $function_name ) ),
										'http://drupal.org/node/322732' );
				}
			}
		}
	}
}

/**
 * Helper function to look up the token closing the current function.
 *
 * @param $here
 *   The token at the function name
 */
function _potx_find_end_of_function($here, $open = '{', $close = '}') {
  global $_potx_tokens;

  // Seek to open brace.
  while (is_array($_potx_tokens[$here]) || $_potx_tokens[$here] != $open) {
    $here++;
  }
  $nesting = 1;
  while ($nesting > 0) {
    $here++;
    if (!is_array($_potx_tokens[$here])) {
      if ($_potx_tokens[$here] == $close) {
        $nesting--;
      }
      if ($_potx_tokens[$here] == $open) {
        $nesting++;
      }
    }
  }
  return $here;
}

/**
 * Helper to move past potx_t() and format_plural() arguments in search of context.
 *
 * @param $here
 *   The token before the start of the arguments
 */
function _potx_skip_args($here) {
  global $_potx_tokens;

  $nesting = 0;
  // Go through to either the end of the function call or to a comma
  // after the current position on the same nesting level.
  while (!(($_potx_tokens[$here] == ',' && $nesting == 0) ||
           ($_potx_tokens[$here] == ')' && $nesting == -1))) {
    $here++;
    if (!is_array($_potx_tokens[$here])) {
      if ($_potx_tokens[$here] == ')') {
        $nesting--;
      }
      if ($_potx_tokens[$here] == '(') {
        $nesting++;
      }
    }
  }
  // If we run out of nesting, it means we reached the end of the function call,
  // so we skipped the arguments but did not find meat for looking at the
  // specified context.
  return ($nesting == 0 ? $here : FALSE);
}

/**
 * Helper to find the value for 'context' on t() and format_plural().
 *
 * @param $tf
 *   Start position of the original function.
 * @param $ti
 *   Start position where we should search from.
 * @param $file
 *   Full path name of file parsed.
 * @param function_name
 *   The name of the function to look for. Either 'format_plural' or 't'
 *   given that Drupal 7 only supports context on these.
 */
function _potx_find_context($tf, $ti, $file, $function_name) {
  global $_potx_tokens;

  // Start from after the comma and skip the possible arguments for the function
  // so we can look for the context.
  if (($ti = _potx_skip_args($ti)) && ($_potx_tokens[$ti] == ',')) {
    // Now we actually might have some definition for a context. The $options
    // argument is coming up, which might have a key for context.
    echo "TI:" . $ti."\n";
    list($com, $arr, $par) = array($_potx_tokens[$ti], $_potx_tokens[$ti+1], $_potx_tokens[$ti+2]);
    if ($com == ',' && $arr[1] == 'array' && $par == '(') {
      $nesting = 0;
      $ti += 3;
      // Go through to either the end of the array or to the key definition of
      // context on the same nesting level.
      while (!((is_array($_potx_tokens[$ti]) && (in_array($_potx_tokens[$ti][1], array('"context"', "'context'"))) && ($_potx_tokens[$ti][0] == T_CONSTANT_ENCAPSED_STRING) && ($nesting == 0)) ||
               ($_potx_tokens[$ti] == ')' && $nesting == -1))) {
        $ti++;
        if (!is_array($_potx_tokens[$ti])) {
          if ($_potx_tokens[$ti] == ')') {
            $nesting--;
          }
          if ($_potx_tokens[$ti] == '(') {
            $nesting++;
          }
        }
      }
      if ($nesting == 0) {
        // Found the 'context' key on the top level of the $options array.
        list($arw, $str) = array($_potx_tokens[$ti+1], $_potx_tokens[$ti+2]);
        if (is_array($arw) && $arw[1] == '=>' && is_array($str) && $str[0] == T_CONSTANT_ENCAPSED_STRING) {
          return _potx_format_quoted_string($str[1]);
        }
        else {
          list($type, $string, $line) = $_potx_tokens[$ti];
          // @todo: fix error reference.
          _potx_marker_error($file, $line, $function_name, $tf, potx_t('The context element in the options array argument to @function() should be a literal string. There should be no variables, concatenation, constants or other non-literal strings there.', array('@function' => $function_name)), 'http://drupal.org/node/322732');
          // Return with error.
          return POTX_CONTEXT_ERROR;
        }
      }
      else {
        // Did not found 'context' key in $options array.
        return POTX_CONTEXT_NONE;
      }
    }
  }

  // After skipping args, we did not find a comma to look for $options.
  return POTX_CONTEXT_NONE;
}

/**
 * Get the exact CVS version number from the file, so we can
 * push that into the generated output.
 *
 * @param $code
 *   Complete source code of the file parsed.
 * @param $file
 *   Name of the file parsed.
 * @param $version_callback
 *   Callback used to save the version information.
 */
function _potx_find_version_number($code, $file, $version_callback) {
  // Prevent CVS from replacing this pattern with actual info.
  if (preg_match('!\\$I'.'d: ([^\\$]+) Exp \\$!', $code, $version_info)) {
    $version_callback($version_info[1], $file);
  }
  else {
    // Unknown version information.
    $version_callback($file .': n/a', $file);
  }
}

/**
 * Default $version_callback used by the potx system. Saves values
 * to a global array to reduce memory consumption problems when
 * passing around big chunks of values.
 *
 * @param $value
 *   The ersion number value of $file. If NULL, the collected
 *   values are returned.
 * @param $file
 *   Name of file where the version information was found.
 */
function _potx_save_version($value = NULL, $file = NULL) {
  global $_potx_versions;

  if (isset($value)) {
    $_potx_versions[$file] = $value;
  }
  else {
    return $_potx_versions;
  }
}

/**
 * Default $save_callback used by the potx system. Saves values
 * to global arrays to reduce memory consumption problems when
 * passing around big chunks of values.
 *
 * @param $value
 *   The string value. If NULL, the array of collected values
 *   are returned for the given $string_mode.
 * @param $context
 *   From Drupal 7, separate contexts are supported. POTX_CONTEXT_NONE is
 *   the default, if the code does not specify a context otherwise.
 * @param $file
 *   Name of file where the string was found.
 * @param $line
 *   Line number where the string was found.
 * @param $string_mode
 *   String mode: POTX_STRING_INSTALLER, POTX_STRING_RUNTIME
 *   or POTX_STRING_BOTH.
 */
function _potx_save_string($value = NULL, $context = NULL, $file = NULL, $line = 0, $string_mode = POTX_STRING_RUNTIME) {
  global $_potx_strings, $_potx_install;

  if (isset($value)) {
    switch ($string_mode) {
      case POTX_STRING_BOTH:
        // Mark installer strings as duplicates of runtime strings if
        // the string was both recorded in the runtime and in the installer.
        $_potx_install[$value][$context][$file][] = $line .' (dup)';
        // Break intentionally missing.
      case POTX_STRING_RUNTIME:
        // Mark runtime strings as duplicates of installer strings if
        // the string was both recorded in the runtime and in the installer.
        $_potx_strings[$value][$context][$file][] = $line . ($string_mode == POTX_STRING_BOTH ? ' (dup)' : '');
        break;
      case POTX_STRING_INSTALLER:
        $_potx_install[$value][$context][$file][] = $line;
        break;
    }
  }
  else {
    return ($string_mode == POTX_STRING_RUNTIME ? $_potx_strings : $_potx_install);
  }
}

function potx_t( $string, $args = array() ) {

    return strtr ( $string, $args );
}
