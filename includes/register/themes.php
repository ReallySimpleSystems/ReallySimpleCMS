<?php
/**
 * Admin theme registry functions.
 * @since 1.4.0-beta_snap-03
 *
 * @package ReallySimpleCMS
 *
 * ## FUNCTIONS [4] ##
 * - registerTheme(string $name, array $args): ?array
 * - unregisterTheme(string $name, bool $del_data): bool
 * - registerDefaultThemes(): void
 * - themeExists(string $name): bool
 */

/**
 * Register an admin theme.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The theme's name.
 * @param array $args (optional) -- The args.
 * @return null|array
 */
function registerTheme(string $name, array $args = array()): ?array {
	global $rs_register;
	
	return $rs_register->registerTheme($name, $args);
}

/**
 * Unregister a theme.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The theme's name.
 * @param bool $del_data (optional) -- Whether to delete all associated data.
 * @return bool
 */
function unregisterTheme(string $name, bool $del_data = false): bool {
	global $rs_register;
	
	return $rs_register->unregisterTheme($name, $del_data);
}

/**
 * Register default themes.
 * @since 1.4.0-beta_snap-03
 */
function registerDefaultThemes(): void {
	global $rs_register;
	
	$default_themes = $rs_register::DEFAULT_THEMES;
	
	foreach($default_themes as $theme) {
		// Try to load the theme functions file
		$reg = slash(PATH . THEMES) . slash($theme) . 'functions.php';
		
		if(file_exists($reg)) requireFile($reg);
	}
}

/**
 * Check whether a theme exists.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The theme's name.
 * @return bool
 */
function themeExists(string $name): bool {
	global $rs_themes;
	
	return !empty($rs_themes) && array_key_exists($name, $rs_themes);
}