<?php
/**
 * Post type registry functions.
 * @since 1.3.15-beta
 *
 * @package ReallySimpleCMS
 *
 * ## FUNCTIONS [4] ##
 * - registerPostType(string $name, array $args): ?array
 * - unregisterPostType(string $name, bool $del_posts): bool
 * - registerDefaultPostTypes(): void
 * - postTypeExists(string $type): bool
 */

/**
 * Register a post type.
 * @since 1.3.15-beta
 *
 * @param string $name -- The post type's name.
 * @param array $args (optional) -- The args.
 * @return null|array
 */
function registerPostType(string $name, array $args = array()): ?array {
	global $rs_register;
	
	return $rs_register->registerPostType($name, $args);
}

/**
 * Unregister a post type.
 * @since 1.3.15-beta
 *
 * @param string $name -- The post type's name.
 * @param bool $del_posts -- Whether to delete all post data from the database.
 * @return bool
 */
function unregisterPostType(string $name, bool $del_posts = false): bool {
	global $rs_register;
	
	return $rs_register->unregisterPostType($name, $del_posts);
}

/**
 * Register default post types.
 * @since 1.0.1-beta
 */
function registerDefaultPostTypes(): void {
	// Page
	registerPostType('page', array(
		'hierarchical' => true,
		'menu_icon' => array('copy', 'regular')
	));
	
	// Post
	registerPostType('post', array(
		'menu_link' => 'posts.php',
		'menu_icon' => 'newspaper',
		'comments' => true,
		'taxonomies' => array(
			'category'
		)
	));
	
	// Media
	registerPostType('media', array(
		'labels' => array(
			'create_item' => 'Upload Media'
		),
		'show_in_nav_menus' => false,
		'menu_link' => 'media.php',
		'menu_icon' => 'images'
	));
	
	// Nav_menu_item
	registerPostType('nav_menu_item', array(
		'labels' => array(
			'name' => 'Menu Items',
			'name_singular' => 'Menu Item'
		),
		'public' => false,
		'create_privileges' => false
	));
	
	// Widget
	registerPostType('widget', array(
		'public' => false,
		'menu_link' => 'widgets.php'
	));
}

/**
 * Check whether a post type exists.
 * @since 1.0.5-beta
 *
 * @param string $type -- The post's type.
 * @return bool
 */
function postTypeExists(string $type): bool {
	global $rs_post_types;
	
	return !empty($rs_post_types) && array_key_exists($type, $rs_post_types);
}