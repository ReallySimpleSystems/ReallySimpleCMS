<?php
/**
 * Initialize the system and load all core files.
 * @since 1.3.0-alpha
 *
 * @package ReallySimpleCMS
 *
 * SYSTEM INITIALIZATION:
 *
 * ## PHASE 1 - Critical setup ##
 * - constants.php -- System-defined constants.
 * - critical-functions.php [RS_CRIT_FUNC] -- Functions required for the system to run.
 *
 * ## PHASE 2 - Base setup ##
 * - config.php [RS_CONFIG] -- The database config.
 * - debug.php [RS_DEBUG_FUNC] -- Debugging functions.
 * - global-functions.php [GLOBAL_FUNC] -- Global functions (available to front and back end).
 * - class-query.php -- The Query class. Interacts with the database.
 * - schema.php [RS_SCHEMA] -- The database schema.
 * - maintenance.php (optional) -- This file is only loaded if the system is in maintenance mode.
 *
 * ## PHASE 3 - Theme setup ##
 * - update.php -- The system updater.
 * - functions.php [RS_FUNC] -- The primary functions file.
 * - theme-functions.php -- Theme-specific functions.
 * - load-theme.php -- The theme loader.
 * - sitemap-index.php -- The sitemap index generator.
 * - load-template.php -- The page template loader.
 */

require_once __DIR__ . '/includes/constants.php';
require_once RS_CRIT_FUNC;

baseSetup();

// Maintenance mode checks
if((defined('MAINT_MODE') && MAINT_MODE) &&
	(!isAdmin() && !isLogin() && !is404()) && !isset($session)
) {
	// We're in maintenance mode
	require_once PATH . INC . '/maintenance.php';
} else {
	registerDefaultPostTypes();
	registerDefaultTaxonomies();
	
	// Check whether only the base files and functions should be initialized
	if(!defined('BASE_INIT') || (defined('BASE_INIT') && !BASE_INIT)) {
		// Check for software updates
		if(file_exists(PATH . INC . '/update.php') && isset($session))
			require_once PATH . INC . '/update.php';
		
		require_once RS_FUNC;
		
		if(isLogin()) {
			// We're logging in
			handleSecureLogin();
		} elseif(!isAdmin() && !is404()) {
			// Initialize the theme
			require_once PATH . INC . '/theme-functions.php';
			require_once PATH . INC . '/load-theme.php';
			
			// Determine the type of page being viewed (e.g., post, term, etc.)
			// This must fire AFTER theme loads to include custom post types, taxonomies, etc.
			guessPageType();
			
			// Initialize sitemaps and page template
			include_once PATH . INC . '/sitemap-index.php';
			require_once PATH . INC . '/load-template.php';
		}
	}
} // Onward!