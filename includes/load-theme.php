<?php
/**
 * Try to load a front end theme. Default to a basic fallback theme if none are found.
 * @since 2.3.0-alpha
 *
 * @package ReallySimpleCMS
 */

if(!file_exists(PATH . THEMES)) mkdir(PATH . THEMES);

if(isEmptyDir(PATH . THEMES) || !file_exists(slash(PATH . THEMES) . getSetting('theme'))) {
	// The themes directory is either empty or the current theme is broken
	$theme_path = null;
} else {
	$theme_path = slash(PATH . THEMES) . getSetting('theme');
	
	if(file_exists($theme_path . '/functions.php')) require_once $theme_path . '/functions.php';
	
	if(!defined('THEME_VERSION')) define('THEME_VERSION', RS_VERSION);
}