<?php
/**
 * Module registry functions.
 * @since 1.4.0-beta_snap-03
 *
 * @package ReallySimpleCMS
 *
 * ## FUNCTIONS [4] ##
 * - registerModule(string $name): void
 * - unregisterModule(string $name, bool $del_data): bool
 * - registerRequiredModules(): void
 * - moduleExists(string $name): bool
 */

/**
 * Register a module.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The module's name.
 * @param array $args (optional) -- The args.
 */
function registerModule(string $name, array $args = array()): void {
	global $rs_register;
	
	$reg = slash(PATH . MODULES) . slash($name) . $name . '.php';
	
	if(file_exists($reg)) {
		requireFile($reg);
		
		if(defined(strtoupper($name) . '_VERSION')) {
			$args['version'] = constant(strtoupper($name) . '_VERSION');
			
			$rs_register->registerModule($name, $args);
		}
	}
}

/**
 * Unregister a module.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The module's name.
 * @param bool $del_data (optional) -- Whether to delete all data from the database.
 * @return bool
 */
function unregisterModule(string $name, bool $del_data = false): bool {
	global $rs_register;
	
	return $rs_register->unregisterModule($name, $del_data);
}

/**
 * Register required modules.
 * @since 1.4.0-beta_snap-03
 */
function registerRequiredModules(): void {
	global $rs_query, $rs_register, $rs_modules;
	
	$active_modules = array();
	
	// DOMtags
	registerModule('domtags', array(
		'label' => 'DOMtags',
		'author' => array(
			'name' => 'Jace Fincham',
			'url' => 'https://jacefincham.com/'
		),
		'description' => 'DOMtags are a set of dynamically generated HTML DOM tags created through a series of PHP classes. They are meant for keeping backend code clean in large projects that make use of lots of HTML within the PHP, which can cause unnecessary clutter.'
	));
	
	// Auto-activate required modules
	foreach($rs_modules as $module) {
		if($module['is_required'] === true)
			$active_modules[] = $module['name'];
	}
	
	$active_modules = serialize($active_modules);
	$db_active_modules = getSetting('active_modules');
	
	if($active_modules !== $db_active_modules) {
		$rs_query->update(array('settings', 's_'), array(
			'value' => $active_modules
		), array(
			'name' => 'active_modules'
		));
	}
}

/**
 * Check whether a module exists.
 * @since 1.4.0-beta_snap-03
 *
 * @param string $name -- The module's name.
 * @return bool
 */
function moduleExists(string $name): bool {
	global $rs_modules;
	
	return !empty($rs_modules) && array_key_exists($name, $rs_modules);
}