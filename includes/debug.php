<?php
/**
 * Debugging functions.
 * @since 1.0.1-alpha
 *
 * @package ReallySimpleCMS
 */

if(!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

// Check whether the CMS is in debug mode
if(DEBUG_MODE === true && !ini_get('display_errors'))
	ini_set('display_errors', 1);
elseif(DEBUG_MODE === false && ini_get('display_errors'))
	ini_set('display_errors', 0);

/**
 * Display a deprecation notice.
 * @since 1.3.12-beta
 */
function deprecated(): void {
	global $rs_error;
	
	$rs_error->generateDeprecation();
}