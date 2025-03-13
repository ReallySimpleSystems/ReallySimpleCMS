<?php
/**
 * Try to load a front end theme. Default to a basic fallback theme if none are found.
 * @since 2.3.0-alpha
 *
 * @package ReallySimpleCMS
 */

if(!file_exists(PATH . THEMES)) mkdir(PATH . THEMES);

if(!isEmptyDir(PATH . THEMES) && file_exists(slash(PATH . THEMES) . getSetting('theme'))) {
	$rs_theme_path = slash(PATH . THEMES) . getSetting('theme');
	
	if(file_exists($rs_theme_path . '/functions.php')) requireFile($rs_theme_path . '/functions.php');
	
	if(!defined('THEME_VERSION')) define('THEME_VERSION', RS_VERSION);
}