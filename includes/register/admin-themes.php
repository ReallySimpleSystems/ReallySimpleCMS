<?php
/**
 * Admin theme registry functions.
 * @since 1.4.0-beta_snap-03
 *
 * @package ReallySimpleCMS
 *
 * ## FUNCTIONS [5] ##
 * - registerAdminTheme(string $name, array $args): ?array
 * - unregisterAdminTheme(string $name, bool $del_data): bool
 * - registerDefaultAdminThemes(): void
 * - loadAdminTheme(string $stylesheet, string $version): void
 * - adminThemeExists(string $name): bool
 */

/**
 * Register an admin theme.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The theme's name.
 * @param array $args (optional) -- The args.
 * @return null|array
 */
function registerAdminTheme(string $name, array $args = array()): ?array {
	global $rs_register;
	
	return $rs_register->registerAdminTheme($name, $args);
}

/**
 * Unregister an admin theme.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The theme's name.
 * @param bool $del_data (optional) -- Whether to delete all associated data.
 * @return bool
 */
function unregisterAdminTheme(string $name, bool $del_data = false): bool {
	global $rs_register;
	
	return $rs_register->unregisterAdminTheme($name, $del_data);
}

/**
 * Register default admin themes.
 * @since 1.4.0-beta_snap-03
 */
function registerDefaultAdminThemes(): void {
	global $rs_register;
	
	$default_themes = $rs_register::DEFAULT_ADMIN_THEMES;
	
	foreach($default_themes as $theme) {
		// Try to load the admin theme config file
		$reg = slash(PATH . ADMIN_THEMES) . slash($theme) . $theme . '.php';
		
		if(file_exists($reg)) requireFile($reg);
	}
}

/**
 * Load an admin theme's stylesheet.
 * @since 2.3.1-alpha
 *
 * @param string $path -- The path to the stylesheet.
 * @param string $version (optional) -- The stylesheet's version.
 */
function loadAdminTheme(string $path, string $version = RS_VERSION): void {
	domTagPr('link', array(
		'href' => $path . (!empty($version) ? '?v=' . $version : ''),
		'rel' => 'stylesheet'
	));
}

/**
 * Check whether an admin theme exists.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The theme's name.
 * @return bool
 */
function adminThemeExists(string $name): bool {
	global $rs_admin_themes;
	
	return !empty($rs_admin_themes) && array_key_exists($name, $rs_admin_themes);
}