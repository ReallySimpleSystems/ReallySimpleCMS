<?php
/**
 * Named constants for the system.
 * @since 2.3.0-alpha
 *
 * @package ReallySimpleCMS
 */

/*------------------------------------*\
    SOFTWARE VERSIONS
\*------------------------------------*/

// Minimum supported PHP version
define('PHP_MINIMUM', '8.0');

// Recommended PHP version
define('PHP_RECOMMENDED', '8.1');

// Current system version
#define('RS_VERSION', '1.3.13-beta'); // testing update system
#define('RS_VERSION', '1.3.14-beta');
define('RS_VERSION', '1.4.0-beta_snap-03');

// Current jQuery version
define('JQUERY_VERSION', '3.7.1');

// Current Font Awesome icons version
define('ICONS_VERSION', '6.2.1');

/*------------------------------------*\
    DIRECTORIES
\*------------------------------------*/

// Absolute path to the root directory
define('PATH', dirname(__DIR__));

// Path to the `includes` directory
define('INC', '/includes');

// Path to the `resources` directory
define('RES', '/resources');

// Path to the `admin` directory
define('ADMIN', '/admin');

// Path to the `content` directory
define('CONT', '/content');

// Path to the `setup` directory
define('SETUP', '/setup');

// Path to the `ajax` directory
define('AJAX', INC . '/ajax');

// Path to the `modals` directory
define('MODALS', INC . '/modals');

// Path to the `register` directory
define('REGISTER', INC . '/register');

// Path to the stylesheets directory
define('STYLES', RES . '/css');

// Path to the scripts directory
define('SCRIPTS', RES . '/js');

// Path to the `modules` directory
define('MODULES', CONT . '/modules');

// Path to the `themes` directory
define('THEMES', CONT . '/themes');

// Path to the `admin-themes` directory
define('ADMIN_THEMES', CONT . '/admin-themes');

// Path to the `uploads` directory
define('UPLOADS', CONT . '/uploads');

/*------------------------------------*\
    CORE FILES
\*------------------------------------*/

// Path to the database configuration file
define('RS_CONFIG', PATH . '/config.php');

// Path to the database schema file
define('RS_SCHEMA', PATH . INC . '/schema.php');

// Path to the primary functions file
define('RS_FUNC', PATH . INC . '/functions.php');

// Path to the debugging functions file
define('RS_DEBUG_FUNC', PATH . INC . '/debug.php');

// Path to the critical functions file
define('RS_CRIT_FUNC', PATH . INC . '/critical-functions.php');

// Path to the global functions file
define('GLOBAL_FUNC', PATH . INC . '/global-functions.php');

// Path to the admin functions file
define('RS_ADMIN_FUNC', PATH . INC . '/admin-functions.php');
define('ADMIN_FUNC', RS_ADMIN_FUNC); // deprecated

/*------------------------------------*\
    MISCELLANEOUS
\*------------------------------------*/

// Engine name
define('RS_ENGINE', 'ReallySimpleCMS');

// Developer name
define('RS_DEVELOPER', 'ReallySimpleSoftware');

// Project start
define('RS_PROJ_START', '2019');

## CREDITS ##

// Lead developer
define('RS_LEAD_DEV', array(
	'name' => 'Jace Fincham',
	'url' => 'https://jacefincham.com/'
));