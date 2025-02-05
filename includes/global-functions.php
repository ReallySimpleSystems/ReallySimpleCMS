<?php
/**
 * Global variables and functions (front end and back end accessible).
 * @since 1.2.0-alpha
 *
 * @package ReallySimpleCMS
 *
 * ## VARIABLES ##
 * - array $post_types
 * - array $taxonomies
 *
 * ## FUNCTIONS ##
 * DATABASE & INSTALLATION:
 * - populateTables(array $user_data, array $settings_data): void
 * - populateTable(string $table): void
 * - repopulateTable(string $table): void
 * - populatePosts(int $author): array
 * - populateUserRoles(): void
 * - populateUserPrivileges(): void
 * - populateUsers(array $args): int
 * - populateSettings(array $args): void
 * - populateTaxonomies(): void
 * - populateTerms(int $post): void
 * POST TYPES & TAXONOMIES:
 * - getPostTypeLabels(string $name, array $labels): array
 * - registerPostType(string $name, array $args): void
 * - unregisterPostType(string $name, bool $del_posts): void
 * - registerDefaultPostTypes(): void
 * - getTaxonomyLabels(string $name, array $labels): array
 * - registerTaxonomy(string $name, string $post_type, array $args): void
 * - unregisterTaxonomy(string $name): void
 * - registerDefaultTaxonomies(): void
 * USER PRIVILEGES:
 * - userHasPrivilege(string $privilege, ?int $role): bool
 * - userHasPrivileges(array $privileges, string $logic, ?int $role): bool
 * - getUserRoleId(string $name): int
 * - getUserPrivilegeId(string $name): int
 * MISCELLANEOUS:
 * - isEmptyDir(string $dir): ?bool
 * - isHomePage(int $id): bool
 * - isLogin(): bool
 * - is404(): bool
 * - getScript(string $script, string $version): string
 * - putScript(string $script, string $version): void
 * - getStylesheet(string $stylesheet, string $version): string
 * - putStylesheet(string $stylesheet, string $version): void
 * - getSetting(string $name): string
 * - putSetting(string $name): void
 * - getPermalink(string $name, int $parent, string $slug): string
 * - isValidSession(string $session): bool
 * - getOnlineUser(string $session): array
 * - getMediaSrc(int $id): string
 * - getMedia(int $id, array $args): string
 * - getTaxonomyId(string $name): int
 * - trimWords(string $text, int $num_words, string $more): string
 * - sanitize(string $text, string $regex, bool $lc): string
 * - button(array $args, bool $link): void
 * - formatDate(string $date, string $format): string
 * - generateHash(int $length, bool $special_chars, string $salt): string
 * - generatePassword(int $length, bool $special_chars): string
 */

require_once PATH . INC . '/domtags.php';

// Set the server timezone
ini_set('date.timezone', date_default_timezone_get());

// Global variables
$post_types = array();
$taxonomies = array();

/*------------------------------------*\
    DATABASE & INSTALLATION
\*------------------------------------*/

/**
 * Populate the database tables on installation.
 * @since 1.7.0-alpha
 *
 * @param array $user_data -- The user data.
 * @param array $settings_data -- The settings data.
 */
function populateTables(array $user_data, array $settings_data): void {
	global $rs_query;
	
	populateUserRoles();
	populateUserPrivileges();
	
	$user = populateUsers($user_data);
	$post = populatePosts($user);
	
	$settings_data['home_page'] = $post['home_page'];
	populateSettings($settings_data);
	
	populateTaxonomies();
	populateTerms($post['blog_post']);
	
	// Set post indexing based on the global setting
	$posts = $rs_query->select('posts', 'p_id');
	
	foreach($posts as $post) {
		$meta = $rs_query->update('postmeta', array(
			'pm_value' => getSetting('do_robots')
		), array(
			'pm_post' => $post['p_id'],
			'pm_key' => 'index_post'
		));
	}
}

/**
 * Populate a database table without erasing anything (safe).
 * @since 1.0.8-beta
 *
 * @param string $table -- The table.
 */
function populateTable(string $table): void {
	global $rs_query;
	
	$schema = dbSchema();
	
	switch($table) {
		case 'postmeta':
		case 'posts':
			$names = array('postmeta', 'posts');
			
			foreach($names as $name) {
				if($rs_query->tableExists($name)) {
					$rs_query->dropTable($name);
					$rs_query->doQuery($schema[$name]);
				}
			}
			
			$admin_user_role = getUserRoleId('Administrator');
			$admin = $rs_query->selectField('users', 'u_id', array(
				'u_role' => $admin_user_role
			), array(
				'order_by' => 'u_id',
				'order' => 'ASC',
				'limit' => '1'
			));
			
			populatePosts($admin);
			break;
		case 'settings':
			populateSettings();
			break;
		case 'taxonomies':
			populateTaxonomies();
			break;
		case 'terms':
		case 'term_relationships':
			$names = array('terms', 'term_relationships');
			
			foreach($names as $name) {
				if($rs_query->tableExists($name)) {
					$rs_query->dropTable($name);
					$rs_query->doQuery($schema[$name]);
				}
			}
			
			$post = $rs_query->selectField('posts', 'p_id', array(
				'p_status' => 'published',
				'p_type' => 'post'
			), array(
				'order_by' => 'id',
				'order' => 'ASC',
				'limit' => '1'
			));
			
			populateTerms($post);
			break;
		case 'usermeta':
		case 'users':
			$names = array('usermeta', 'users');
			
			foreach($names as $name) {
				if($rs_query->tableExists($name)) {
					$rs_query->dropTable($name);
					$rs_query->doQuery($schema[$name]);
				}
			}
			
			populateUsers();
			break;
		case 'user_privileges': // Also populates `user_relationships`
			populateUserPrivileges();
			break;
		case 'user_roles':
			populateUserRoles();
			break;
	}
}

/**
 * Re-populate a database table. Erases all existing data (use with caution).
 * @since 1.4.0-beta_snap-02
 *
 * @param string $table -- The table.
 */
function repopulateTable(string $table): void {
	global $rs_query;
	
	$schema = dbSchema();
	
	if($rs_query->tableExists($table)) {
		$rs_query->dropTable($table);
		$rs_query->doQuery($schema[$table]);
	}
	
	#populateTable($table);
}

/**
 * Populate the `posts` database table.
 * @since 1.3.7-alpha
 * @deprecated from 1.7.0-alpha to 1.0.8-beta
 *
 * @param int $author -- The author's id.
 * @return array
 */
function populatePosts(int $author): array {
	global $rs_query;
	
	// Create a sample page
	$post['home_page'] = $rs_query->insert('posts', array(
		'p_title' => 'Sample Page',
		'p_author' => $author,
		'p_created' => 'NOW()',
		'p_content' => '<p>This is just a sample page to get you started.</p>',
		'p_status' => 'published',
		'p_slug' => 'sample-page',
		'p_type' => 'page'
	));
	
	// Create a sample blog post
	$post['blog_post'] = $rs_query->insert('posts', array(
		'p_title' => 'Sample Blog Post',
		'p_author' => $author,
		'p_created' => 'NOW()',
		'p_content' => '<p>This is your first blog post. Feel free to remove this text and replace it with your own.</p>',
		'p_status' => 'published',
		'p_slug' => 'sample-post'
	));
	
	$postmeta = array(
		'home_page' => array(
			'title' => 'Sample Page',
			'description' => 'Just a simple meta description for your sample page.',
			'index_post' => 0,
			'feat_image' => 0,
			'template' => 'default'
		),
		'blog_post' => array(
			'title' => 'Sample Blog Post',
			'description' => 'Just a simple meta description for your first blog post.',
			'index_post' => 0,
			'feat_image' => 0,
			'comment_status' => 1,
			'comment_count' => 0
		)
	);
	
	foreach($postmeta as $metadata) {
		foreach($metadata as $key => $value) {
			$rs_query->insert('postmeta', array(
				'pm_post' => $post[key($postmeta)],
				'pm_key' => $key,
				'pm_value' => $value
			));
		}
		
		next($postmeta);
	}
	
	return $post;
}

/**
 * Populate the `user_roles` database table.
 * @since 1.0.8-beta
 */
function populateUserRoles(): void {
	global $rs_query;
	
	$roles = array('User', 'Editor', 'Moderator', 'Administrator');
	
	foreach($roles as $role) {
		$rs_query->insert('user_roles', array(
			'ur_name' => $role,
			'ur_is_default' => 1
		));
	}
}

/**
 * Populate the `user_privileges` and `user_relationships` database tables.
 * @since 1.0.8-beta
 */
function populateUserPrivileges(): void {
	global $rs_query;
	
	$admin_pages = array(
		'update',
		'pages',
		'posts',
		'categories',
		'media',
		'comments',
		'themes',
		'menus',
		'widgets',
		'users',
		'login_attempts',
		'login_blacklist',
		'login_rules',
		'settings',
		'user_roles'
	);
	
	$privileges = array(
		'can_view_',
		'can_create_',
		'can_edit_',
		'can_delete_'
	);
	
	foreach($admin_pages as $admin_page) {
		foreach($privileges as $privilege) {
			switch($admin_page) {
				case 'update':
					if($privilege === 'can_edit_')
						$privilege = 'can_update_core';
					else
						continue 2;
					break;
				case 'media':
					if($privilege === 'can_create_') $privilege = 'can_upload_';
					break;
				case 'comments':
					// Skip 'can_create_' for comments
					if($privilege === 'can_create_') continue 2;
					break;
				case 'login_attempts':
					// Skip 'can_create_', 'can_edit_', and 'can_delete_' for settings
					if($privilege === 'can_create_' || $privilege === 'can_edit_' || $privilege === 'can_delete_')
						continue 2;
					break;
				case 'settings':
					// Skip 'can_view_', 'can_create_', and 'can_delete_' for settings
					if($privilege === 'can_view_' || $privilege === 'can_create_' || $privilege === 'can_delete_')
						continue 2;
					break;
			}
			
			$rs_query->insert('user_privileges', array(
				'up_name' => ($admin_page === 'update' ? $privilege : $privilege . $admin_page),
				'up_is_default' => 1
			));
		}
	}
	
	/**
	 * List of privileges:
	 * 1 => 'can_update_core',
	 * 2 => 'can_view_pages', 3 => 'can_create_pages', 4 => 'can_edit_pages', 5 => 'can_delete_pages',
	 * 6 => 'can_view_posts', 7 => 'can_create_posts', 8 => 'can_edit_posts', 9 => 'can_delete_posts',
	 * 10 => 'can_view_categories', 11 => 'can_create_categories', 12 => 'can_edit_categories', 13 => 'can_delete_categories',
	 * 14 => 'can_view_media', 15 => 'can_upload_media', 16 => 'can_edit_media', 17 => 'can_delete_media',
	 * 18 => 'can_view_comments', 19 => 'can_edit_comments', 20 => 'can_delete_comments',
	 * 21 => 'can_view_themes', 22 => 'can_create_themes', 23 => 'can_edit_themes', 24 => 'can_delete_themes',
	 * 25 => 'can_view_menus', 26 => 'can_create_menus', 27 => 'can_edit_menus', 28 => 'can_delete_menus',
	 * 29 => 'can_view_widgets', 30 => 'can_create_widgets', 31 => 'can_edit_widgets', 32 => 'can_delete_widgets',
	 * 33 => 'can_view_users', 34 => 'can_create_users', 35 => 'can_edit_users', 36 => 'can_delete_users',
	 * 37 => 'can_view_login_attempts',
	 * 38 => 'can_view_login_blacklist', 39 => 'can_create_login_blacklist', 40 => 'can_edit_login_blacklist', 41 => 'can_delete_login_blacklist',
	 * 42 => 'can_view_login_rules', 43 => 'can_create_login_rules', 44 => 'can_edit_login_rules', 45 => 'can_delete_login_rules',
	 * 46 => 'can_edit_settings',
	 * 47 => 'can_view_user_roles', 48 => 'can_create_user_roles', 49 => 'can_edit_user_roles', 50 => 'can_delete_user_roles'
	 */
	
	$roles = $rs_query->select('user_roles', 'ur_id', array(
		'ur_id' => array('IN', 1, 2, 3, 4)
	), array(
		'order_by' => 'ur_id'
	));
	
	foreach($roles as $role) {
		switch($role['ur_id']) {
			case 1:
				// User
				$privileges = array();
				break;
			case 2:
				// Editor
				$privileges = array(
					2, 3, 4, 6, 7, 8, 10, 11, 12, 14, 15, 16, 33, 47
				);
				break;
			case 3:
				// Moderator
				$privileges = array(
					2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
					16, 17, 18, 19, 20, 21, 25, 26, 27, 29, 30, 31,
					33, 34, 35, 37, 38, 39, 40, 41, 42, 43, 44, 47
				);
				break;
			case 4:
				// Administrator
				$privileges = array(
					1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14,
					15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26,
					27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38,
					39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50
				);
				break;
		}
		
		foreach($privileges as $privilege) {
			$insert_id = $rs_query->insert('user_relationships', array(
				'ue_role' => $role['ur_id'],
				'ue_privilege' => $privilege
			));
		}
	}
}

/**
 * Populate the `users` database table.
 * @since 1.3.1-alpha
 * @deprecated from 1.7.0-alpha to 1.0.8-beta
 *
 * @param array $args (optional) -- User-supplied arguments.
 * @return int
 */
function populateUsers(array $args = array()): int {
	global $rs_query;
	
	$defaults = array(
		'username' => 'admin',
		'password' => '12345678',
		'email' => 'admin@rscms.com',
		'role' => getUserRoleId('Administrator')
	);
	
	$args = array_merge($defaults, $args);
	
	$hashed_password = password_hash($args['password'], PASSWORD_BCRYPT, array('cost' => 10));
	
	$user = $rs_query->insert('users', array(
		'u_username' => $args['username'],
		'u_password' => $hashed_password,
		'u_email' => $args['email'],
		'u_registered' => 'NOW()',
		'u_role' => $args['role']
	));
	
	$usermeta = array(
		'first_name' => '',
		'last_name' => '',
		'display_name' => $args['username'],
		'avatar' => 0,
		'theme' => 'default',
		'dismissed_notices' => ''
	);
	
	foreach($usermeta as $key => $value) {
		$rs_query->insert('usermeta', array(
			'um_user' => $user,
			'um_key' => $key,
			'um_value' => $value
		));
	}
	
	return $user;
}

/**
 * Populate the `settings` database table.
 * @since 1.3.0-alpha
 * @deprecated from 1.7.0-alpha to 1.0.8-beta
 *
 * @param array $args (optional) -- User-supplied arguments.
 */
function populateSettings(array $args = array()): void {
	global $rs_query;
	
	$defaults = array(
		'site_title' => 'My Website',
		'description' => 'A new ' . RS_ENGINE . ' website!',
		'site_url' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'],
		'admin_email' => 'admin@rscms.com',
		'default_user_role' => getUserRoleId('User'),
		'home_page' => $rs_query->selectField('posts', 'id', array(
			'status' => 'published',
			'type' => 'page'
		), array(
			'order_by' => 'id',
			'order' => 'ASC',
			'limit' => '1'
		)),
		'do_robots' => 1,
		'enable_comments' => 1,
		'auto_approve_comments' => 0,
		'allow_anon_comments' => 0,
		'track_login_attempts' => 0,
		'delete_old_login_attempts' => 0,
		'login_slug' => '',
		'site_logo' => 0,
		'site_icon' => 0,
		'theme' => 'carbon',
		'theme_color' => '#ededed'
	);
	
	$args = array_merge($defaults, $args);
	
	foreach($args as $name => $value) {
		$rs_query->insert('settings', array(
			's_name' => $name,
			's_value' => $value
		));
	}
}

/**
 * Populate the `taxonomies` database table.
 * @since 1.5.0-alpha
 * @deprecated from 1.7.0-alpha to 1.0.8-beta
 */
function populateTaxonomies(): void {
	global $rs_query;
	
	$taxonomies = array('category', 'nav_menu');
	
	foreach($taxonomies as $taxonomy) {
		$rs_query->insert('taxonomies', array(
			'ta_name' => $taxonomy
		));
	}
}

/**
 * Populate the `terms` database table.
 * @since 1.5.0-alpha
 * @deprecated from 1.7.0-alpha to 1.0.8-beta
 *
 * @param int $post -- The post's id.
 */
function populateTerms(int $post): void {
	global $rs_query;
	
	$term = $rs_query->insert('terms', array(
		't_name' => 'Uncategorized',
		't_slug' => 'uncategorized',
		't_taxonomy' => getTaxonomyId('category'),
		't_count' => 1
	));
	
	$rs_query->insert('term_relationships', array(
		'tr_term' => $term,
		'tr_post' => $post
	));
}

/*------------------------------------*\
    POST TYPES & TAXONOMIES
\*------------------------------------*/

/**
 * Set all post type labels.
 * @since 1.0.1-beta
 *
 * @param string $name -- The post type's name.
 * @param array $labels (optional) -- Any predefined labels.
 * @return array
 */
function getPostTypeLabels(string $name, array $labels = array()): array {
	$name_default = ucwords(str_replace(
		array('_', '-'), ' ',
		($name === 'media' ? $name : $name . 's')
	));
	
	$name_lowercase = strtolower($name_default);
	$name_singular = ucwords(str_replace(array('_', '-'), ' ', $name));
	
	$defaults = array(
		'name' => $name_default,
		'name_lowercase' => $name_lowercase,
		'name_singular' => $name_singular,
		'list_items' => 'List ' . $name_default,
		'create_item' => 'Create ' . $name_singular,
		'edit_item' => 'Edit ' . $name_singular,
		'duplicate_item' => 'Duplicate ' . $name_singular,
		'no_items' => 'There are no ' . $name_lowercase . ' to display.',
		'title_placeholder' => $name_singular . ' title'
	);
	
	$labels = array_merge($defaults, $labels);
	
	return $labels;
}

/**
 * Register a post type.
 * @since 1.0.0-beta
 *
 * @param string $name -- The post type's name.
 * @param array $args (optional) -- The args.
 */
function registerPostType(string $name, array $args = array()): void {
	global $rs_query, $post_types, $taxonomies;
	
	if(!is_array($post_types)) $post_types = array();
	
	$name = sanitize($name);
	
	if(empty($name) || strlen($name) > 20)
		exit('A post type\'s name must be between 1 and 20 characters long.');
	
	// If the name is already registered, abort
	if(isset($post_types[$name]) || isset($taxonomies[$name])) return;
	
	$defaults = array(
		'slug' => $name,
		'labels' => array(),
		'public' => true,
		'hierarchical' => false,
		'create_privileges' => true,
		'show_in_stats_graph' => null,
		'show_in_admin_menu' => null,
		'show_in_admin_bar' => null,
		'show_in_nav_menus' => null,
		'menu_link' => 'posts.php?type=' . $name,
		'menu_icon' => null,
		'comments' => false,
		'taxonomies' => array()
	);
	
	$args = array_merge($defaults, $args);
	
	// Remove any unrecognized args
	foreach($args as $key => $value)
		if(!array_key_exists($key, $defaults)) unset($args[$key]);
	
	if(is_null($args['show_in_stats_graph'])) $args['show_in_stats_graph'] = $args['public'];
	if(is_null($args['show_in_admin_menu'])) $args['show_in_admin_menu'] = $args['public'];
	if(is_null($args['show_in_admin_bar'])) $args['show_in_admin_bar'] = $args['public'];
	if(is_null($args['show_in_nav_menus'])) $args['show_in_nav_menus'] = $args['public'];
	
	$default_post_types = array('page', 'media', 'post', 'nav_menu_item', 'widget');
	$args['is_default'] = in_array($name, $default_post_types, true) ? true : false;
	$args['name'] = $name;
	$args['labels'] = getPostTypeLabels($name, $args['labels']);
	$args['label'] = $args['labels']['name'];
	
	// Add the post type to the global array
	$post_types[$name] = $args;
	
	if($args['create_privileges']) {
		$name_lowercase = str_replace(' ', '_', $args['labels']['name_lowercase']);
		
		$privileges = array(
			'can_view_' . $name_lowercase,
			'can_create_' . $name_lowercase,
			'can_edit_' . $name_lowercase,
			'can_delete_' . $name_lowercase
		);
		
		$db_privileges = $rs_query->select('user_privileges', '*', array(
			'up_name' => array(
				'IN',
				$privileges[0],
				$privileges[1],
				$privileges[2],
				$privileges[3]
			),
			'up_is_default' => ($args['is_default'] ? 1 : 0)
		));
		
		if(empty($db_privileges)) {
			$insert_ids = array();
			
			for($i = 0; $i < count($privileges); $i++) {
				$insert_ids[] = $rs_query->insert('user_privileges', array(
					'up_name' => $privileges[$i]
				));
				
				if($privileges[$i] === 'can_view_' . $name_lowercase ||
					$privileges[$i] === 'can_create_' . $name_lowercase ||
					$privileges[$i] === 'can_edit_' . $name_lowercase) {
						
					// Editor
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Editor'),
						'ue_privilege' => $insert_ids[$i]
					));
					
					// Moderator
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Moderator'),
						'ue_privilege' => $insert_ids[$i]
					));
					
					// Administrator
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Administrator'),
						'ue_privilege' => $insert_ids[$i]
					));
				} elseif($privileges[$i] === 'can_delete_' . $name_lowercase) {
					// Moderator
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Moderator'),
						'ue_privilege' => $insert_ids[$i]
					));
					
					// Administrator
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Administrator'),
						'ue_privilege' => $insert_ids[$i]
					));
				}
			}
		}
	}
}

/**
 * Unregister a post type.
 * @since 1.0.5-beta
 *
 * @param string $name -- The post type's name.
 * @param bool $del_posts -- Whether to delete all post data from the database.
 */
function unregisterPostType(string $name, bool $del_posts = false): void {
	global $rs_query, $post_types;
	
	$name = sanitize($name);
	
	if((postTypeExists($name) || array_key_exists($name, $post_types)) && !$post_types[$name]['is_default']) {
		if($del_posts) {
			$posts = $rs_query->select('posts', 'p_id', array(
				'p_type' => $name
			));
			
			foreach($posts as $post) {
				$rs_query->delete('postmeta', array(
					'pm_post' => $post['p_id']
				));
			}
			
			$rs_query->delete('posts', array(
				'p_type' => $name
			));
		}
		
		$type = str_replace(' ', '_', $post_types[$name]['labels']['name_lowercase']);
		
		$privileges = array(
			'can_view_' . $type,
			'can_create_' . $type,
			'can_edit_' . $type,
			'can_delete_' . $type
		);
		
		foreach($privileges as $privilege) {
			$rs_query->delete('user_relationships', array(
				'ue_privilege' => getUserPrivilegeId($privilege)
			));
			
			$rs_query->delete('user_privileges', array(
				'up_name' => $privilege
			));
		}
		
		if(array_key_exists($name, $post_types)) unset($post_types[$name]);
	}
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
 * Set all taxonomy labels.
 * @since 1.0.4-beta
 *
 * @param string $name -- The taxonomy's name.
 * @param array $labels (optional) -- Any predefined labels.
 * @return array
 */
function getTaxonomyLabels(string $name, array $labels = array()): array {
	$name_default = ucwords(str_replace(
		array('_', '-'), ' ',
		($name === 'category' ? 'Categories' : $name . 's')
	));
	
	$name_lowercase = strtolower($name_default);
	$name_singular = ucwords(str_replace(array('_', '-'), ' ', $name));
	
	$defaults = array(
		'name' => $name_default,
		'name_lowercase' => $name_lowercase,
		'name_singular' => $name_singular,
		'list_items' => 'List ' . $name_default,
		'create_item' => 'Create ' . $name_singular,
		'edit_item' => 'Edit ' . $name_singular,
		'no_items' => 'There are no ' . $name_lowercase . ' to display.'
	);
	
	$labels = array_merge($defaults, $labels);
	
	return $labels;
}

/**
 * Register a taxonomy.
 * @since 1.0.1-beta
 *
 * @param string $name -- The taxonomy's name.
 * @param string $post_type -- The associated post type.
 * @param array $args (optional) -- The args.
 */
function registerTaxonomy(string $name, string $post_type, array $args = array()): void {
	global $rs_query, $taxonomies, $post_types;
	
	if(!is_array($taxonomies)) $taxonomies = array();
	
	$name = sanitize($name);
	
	if(empty($name) || strlen($name) > 20)
		exit('A taxonomy\'s name must be between 1 and 20 characters long.');
	
	// If the name is already registered, abort
	if(isset($taxonomies[$name]) || isset($post_types[$name])) return;
	
	$taxonomy = $rs_query->selectRow('taxonomies', '*', array(
		'ta_name' => $name
	));
	
	if(empty($taxonomy)) {
		$rs_query->insert('taxonomies', array(
			'ta_name' => $name
		));
	}
	
	$defaults = array(
		'slug' => $name,
		'labels' => array(),
		'public' => true,
		'hierarchical' => false,
		'create_privileges' => true,
		'show_in_stats_graph' => null,
		'show_in_admin_menu' => null,
		'show_in_admin_bar' => null,
		'show_in_nav_menus' => null,
		'menu_link' => 'terms.php?taxonomy=' . $name,
		'default_term' => array(
			'name' => '',
			'slug' => ''
		)
	);
	
	$args = array_merge($defaults, $args);
	
	// Remove any unrecognized args
	foreach($args as $key => $value)
		if(!array_key_exists($key, $defaults)) unset($args[$key]);
	
	if(is_null($args['show_in_stats_graph'])) $args['show_in_stats_graph'] = $args['public'];
	if(is_null($args['show_in_admin_menu'])) $args['show_in_admin_menu'] = $args['public'];
	if(is_null($args['show_in_admin_bar'])) $args['show_in_admin_bar'] = $args['public'];
	if(is_null($args['show_in_nav_menus'])) $args['show_in_nav_menus'] = $args['public'];
	
	$default_taxonomies = array('category', 'nav_menu');
	$args['is_default'] = in_array($name, $default_taxonomies, true) ? true : false;
	$args['post_type'] = $post_type;
	$args['name'] = $name;
	$args['labels'] = getTaxonomyLabels($name, $args['labels']);
	$args['label'] = $args['labels']['name'];
	
	// Add the taxonomy to the global array
	$taxonomies[$name] = $args;
	
	if($args['create_privileges']) {
		$name_lowercase = str_replace(' ', '_', $args['labels']['name_lowercase']);
		
		$privileges = array(
			'can_view_' . $name_lowercase,
			'can_create_' . $name_lowercase,
			'can_edit_' . $name_lowercase,
			'can_delete_' . $name_lowercase
		);
		
		$db_privileges = $rs_query->select('user_privileges', '*', array(
			'up_name' => array(
				'IN',
				$privileges[0],
				$privileges[1],
				$privileges[2],
				$privileges[3]
			),
			'up_is_default' => ($args['is_default'] ? 1 : 0)
		));
		
		if(empty($db_privileges)) {
			$insert_ids = array();
			
			for($i = 0; $i < count($privileges); $i++) {
				$insert_ids[] = $rs_query->insert('user_privileges', array(
					'up_name' => $privileges[$i]
				));
				
				if($privileges[$i] === 'can_view_' . $name_lowercase ||
					$privileges[$i] === 'can_create_' . $name_lowercase ||
					$privileges[$i] === 'can_edit_' . $name_lowercase) {
						
					// Editor
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Editor'),
						'ue_privilege' => $insert_ids[$i]
					));
					
					// Moderator
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Moderator'),
						'ue_privilege' => $insert_ids[$i]
					));
					
					// Administrator
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Administrator'),
						'ue_privilege' => $insert_ids[$i]
					));
				} elseif($privileges[$i] === 'can_delete_'.$name_lowercase) {
					// Moderator
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Moderator'),
						'ue_privilege' => $insert_ids[$i]
					));
					
					// Administrator
					$rs_query->insert('user_relationships', array(
						'ue_role' => getUserRoleId('Administrator'),
						'ue_privilege' => $insert_ids[$i]
					));
				}
			}
		}
	}
	
	if(!empty($args['default_term']['name']) && !empty($args['default_term']['slug'])) {
		$term = $rs_query->selectRow('terms', 'COUNT(*)', array(
			't_slug' => $args['default_term']['slug']
		)) > 0;
		
		if(!$term) {
			$rs_query->insert('terms', array(
				't_name' => $args['default_term']['name'],
				't_slug' => $args['default_term']['slug'],
				't_taxonomy' => getTaxonomyId($name)
			));
		}
	}
}

/**
 * Unregister a taxonomy.
 * @since 1.0.5-beta
 *
 * @param string $name -- The taxonomy's name.
 * @param bool $del_terms -- Whether to delete all term data from the database.
 */
function unregisterTaxonomy(string $name): void {
	global $rs_query, $taxonomies;
	
	$name = sanitize($name);
	
	if((taxonomyExists($name) || array_key_exists($name, $taxonomies)) && !$taxonomies[$name]['is_default']) {
		if($del_terms) {
			$terms = $rs_query->select('terms', 't_id', array(
				't_taxonomy' => getTaxonomyId($name)
			));
			
			foreach($terms as $term) {
				$rs_query->delete('term_relationships', array(
					'tr_term' => $term
				));
				
				$rs_query->delete('terms', array(
					't_id' => $term
				));
			}
			
			$rs_query->delete('taxonomies', array(
				'ta_name' => $name
			));
		}
		
		$taxonomy = str_replace(' ', '_', $taxonomies[$name]['labels']['name_lowercase']);
		$privileges = array(
			'can_view_' . $taxonomy,
			'can_create_' . $taxonomy,
			'can_edit_' . $taxonomy,
			'can_delete_' . $taxonomy
		);
		
		foreach($privileges as $privilege) {
			$rs_query->delete('user_relationships', array(
				'ue_privilege' => getUserPrivilegeId($privilege)
			));
			
			$rs_query->delete('user_privileges', array(
				'up_name' => $privilege
			));
		}
		
		if(array_key_exists($name, $taxonomies)) unset($taxonomies[$name]);
	}
}

/**
 * Register default taxonomies.
 * @since 1.0.4-beta
 */
function registerDefaultTaxonomies(): void {
	// Category
	registerTaxonomy('category', 'post', array(
		'menu_link' => 'categories.php',
		'default_term' => array(
			'name' => 'Uncategorized',
			'slug' => 'uncategorized'
		)
	));
	
	// Nav_menu
	registerTaxonomy('nav_menu', '', array(
		'labels' => array(
			'name' => 'Menus',
			'name_lowercase' => 'menus',
			'name_singular' => 'Menu',
			'list_items' => 'List Menus',
			'create_item' => 'Create Menu',
			'edit_item' => 'Edit Menu'
		),
		'public' => false,
		'create_privileges' => false,
		'menu_link' => 'menus.php'
	));
}

/*------------------------------------*\
    USER PRIVILEGES
\*------------------------------------*/

/**
 * Check whether a user has a specified privilege.
 * @since 1.7.2-alpha
 *
 * @param string $privilege -- The privilege's name.
 * @param null|int $role (optional) -- The role's id.
 * @return bool
 */
function userHasPrivilege(string $privilege, ?int $role = null): bool {
	global $rs_query, $session;
	
	if(is_null($role)) $role = $session['role'];
	
	$id = $rs_query->selectField('user_privileges', 'up_id', array(
		'up_name' => $privilege
	));
	
	return $rs_query->selectRow('user_relationships', 'COUNT(*)', array(
		'ue_role' => $role,
		'ue_privilege' => $id
	)) > 0;
}

/**
 * Check whether a user has a specified group of privileges.
 * @since 1.2.0-beta_snap-02
 *
 * @param array $privileges (optional) -- A list of the privileges' names.
 * @param string $logic (optional) -- Query logic operator.
 * @param null|int $role (optional) -- The role's id.
 * @return bool
 */
function userHasPrivileges(array $privileges = array(), string $logic = 'AND', ?int $role = null): bool {
	if(!is_array($privileges)) $privileges = (array)$privileges;
	
	foreach($privileges as $privilege) {
		if(strtoupper($logic) === 'AND') {
			if(userHasPrivilege($privilege, $role) === false) return false;
		} elseif(strtoupper($logic) === 'OR') {
			if(userHasPrivilege($privilege, $role) === true) return true;
		}
	}
	
	if(strtoupper($logic) === 'AND')
		return true;
	elseif(strtoupper($logic) === 'OR')
		return false;
}

// Include the user privileges functions file
//require_once PATH.INC.'/user-privileges.php';

/**
 * Fetch a user role's id.
 * @since 1.0.5-beta
 *
 * @param string $name -- The role's name.
 * @return int
 */
function getUserRoleId(string $name): int {
	global $rs_query;
	
	$name = sanitize($name);
	
	return (int)$rs_query->selectField('user_roles', 'ur_id', array(
		'ur_name' => $name
	)) ?? 0;
}

/**
 * Fetch a user privilege's id.
 * @since 1.0.5-beta
 *
 * @param string $name -- The privilege's name.
 * @return int
 */
function getUserPrivilegeId(string $name): int {
	global $rs_query;
	
	$name = sanitize($name);
	
	return (int)$rs_query->selectField('user_privileges', 'up_id', array(
		'up_name' => $name
	)) ?? 0;
}

/*------------------------------------*\
    MISCELLANEOUS
\*------------------------------------*/

/**
 * Check whether a directory is empty.
 * @since 2.3.0-alpha
 *
 * @param string $dir -- The directory to open.
 * @return null|bool
 */
function isEmptyDir(string $dir): ?bool {
	if(!is_readable($dir)) return null;
	
	$handle = opendir($dir);
	
	while(($entry = readdir($handle)) !== false)
		if($entry !== '.' && $entry !== '..') return false;
	
	return true;
}

/**
 * Check whether a post is the website's home page.
 * @since 1.4.0-alpha
 *
 * @param int $id -- The post's id.
 * @return bool
 */
function isHomePage(int $id): bool {
	global $rs_query;
	
	return (int)$rs_query->selectField('settings', 's_value', array(
		's_name' => 'home_page'
	)) === $id;
}

/**
 * Check whether the user is viewing the log in page.
 * @since 1.0.6-beta
 *
 * @return bool
 */
function isLogin(): bool {
	$login_slug = getSetting('login_slug');
	
	return str_starts_with($_SERVER['REQUEST_URI'], '/login.php') ||
		(!empty($login_slug) && str_contains($_SERVER['REQUEST_URI'], $login_slug));
}

/**
 * Check whether the user is viewing the 404 not found page.
 * @since 1.0.6-beta
 *
 * @return bool
 */
function is404(): bool {
	return str_starts_with($_SERVER['REQUEST_URI'], '/404.php');
}

/**
 * Fetch a script file.
 * @since 1.3.3-alpha
 *
 * @param string $script -- The script to load.
 * @param string $version (optional) -- The script's version.
 * @return string
 */
function getScript(string $script, string $version = RS_VERSION): string {
	return '<script src="' . slash(SCRIPTS) . $script .
		(!empty($version) ? '?v=' . $version : '') . '"></script>';
}

/**
 * Output a script file.
 * @since 1.3.0-beta
 *
 * @param string $script -- The script to load.
 * @param string $version (optional) -- The script's version.
 */
function putScript(string $script, string $version = RS_VERSION): void {
	echo getScript($script, $version);
}

/**
 * Fetch a stylesheet.
 * @since 1.3.3-alpha
 *
 * @param string $stylesheet -- The stylesheet to load.
 * @param string $version (optional) -- The stylesheet's version.
 * @return string
 */
function getStylesheet(string $stylesheet, string $version = RS_VERSION): string {
	return '<link href="' . slash(STYLES) . $stylesheet .
		(!empty($version) ? '?v=' . $version : '') . '" rel="stylesheet">';
}

/**
 * Output a stylesheet.
 * @since 1.3.0-beta
 *
 * @param string $stylesheet -- The stylesheet to load.
 * @param string $version (optional) -- The stylesheet's version.
 */
function putStylesheet(string $stylesheet, string $version = RS_VERSION): void {
	echo getStylesheet($stylesheet, $version);
}

/**
 * Retrieve a setting from the database.
 * @since 1.2.5-alpha
 *
 * @param string $name -- The setting's name.
 * @return string
 */
function getSetting(string $name): string {
	global $rs_query;
	
	return $rs_query->selectField('settings', 's_value', array(
		's_name' => $name
	));
}

/**
 * Output a setting from the database.
 * @since 1.3.0-beta
 *
 * @param string $name -- The setting's name.
 */
function putSetting(string $name): void { echo getSetting($name); }

/**
 * Construct a permalink.
 * @since 2.2.2-alpha
 *
 * @param string $name -- The post's name.
 * @param int $parent (optional) -- The post's parent.
 * @param string $slug (optional) -- The post's slug.
 * @return string
 */
function getPermalink(string $name, int $parent = 0, string $slug = ''): string {
	global $rs_query, $post_types, $taxonomies;
	
	if(array_key_exists($name, $post_types)) {
		$table = 'posts';
		$px = 'p_';
		
		if($name !== 'post' && $name !== 'page') {
			if($post_types[$name]['slug'] !== $name)
				$base = str_replace('_', '-', $post_types[$name]['slug']);
			else
				$base = str_replace('_', '-', $name);
		}
	} elseif(array_key_exists($name, $taxonomies)) {
		$table = 'terms';
		$px = 't_';
		
		if($taxonomies[$name]['slug'] !== $name)
			$base = str_replace('_', '-', $taxonomies[$name]['slug']);
		else
			$base = str_replace('_', '-', $name);
	}
	
	$permalink = array();
	
	while((int)$parent !== 0) {
		$item = $rs_query->selectRow($table, array($px . 'slug', $px . 'parent'), array(
			$px . 'id' => $parent
		));
		
		$parent = (int)$item[$px . 'parent'];
		$permalink[] = $item[$px . 'slug'];
	}
	
	$permalink = implode('/', array_reverse($permalink));
	
	// Construct the full permalink and return it
	return '/' . (isset($base) ? slash($base) : '') .
		(!empty($permalink) ? slash($permalink) : '') .
		(!empty($slug) ? slash($slug) : '');
}

/**
 * Check whether a user's session is valid.
 * @since 2.0.1-alpha
 *
 * @param string $session -- The session data.
 * @return bool
 */
function isValidSession(string $session): bool {
	global $rs_query;
	
	return $rs_query->selectRow('users', 'COUNT(*)', array(
		'u_session' => $session
	)) > 0;
}

/**
 * Fetch an online user's data.
 * @since 2.0.1-alpha
 *
 * @param string $session -- The session data.
 * @return array
 */
function getOnlineUser(string $session): array {
	global $rs_query;
	
	$user = $rs_query->select('users', array('u_id', 'u_username', 'u_role'), array(
		'u_session' => $session
	));
	
	if(!empty($user)) {
		$user = array_map(function($u) {
			return array(
				'id' => $u['u_id'],
				'username' => $u['u_username'],
				'role' => $u['u_role']
			);
		}, $user);
		
		$user = array_merge(...$user);
		
		$usermeta = array('display_name', 'avatar', 'theme', 'dismissed_notices');
		
		foreach($usermeta as $meta) {
			$user[$meta] = $rs_query->selectField('usermeta', 'um_value', array(
				'um_user' => $user['u_id'],
				'um_key' => $meta
			));
		}
		
		$user['dismissed_notices'] = unserialize($user['dismissed_notices']);
	}
	
	return $user;
}

/**
 * Fetch the source of a specified media item.
 * @since 2.1.5-alpha
 *
 * @param int $id -- The media's id.
 * @return string
 */
function getMediaSrc(int $id): string {
	global $rs_query;
	
	$media = $rs_query->selectField('postmeta', 'pm_value', array(
		'pm_post' => $id,
		'pm_key' => 'filepath'
	));
	
	if(!empty($media))
		return slash(UPLOADS) . $media;
	else
		return '//:0';
}

/**
 * Fetch a specified media item.
 * @since 2.2.0-alpha
 *
 * @param int $id -- The media's id.
 * @param array $args (optional) -- Additional args.
 * @return string
 */
function getMedia(int $id, array $args = array()): string {
	global $rs_query;
	
	$src = getMediaSrc($id);
	
	if(empty($args['cached'])) $args['cached'] = true;
	
	if($args['cached'] === true && $src !== '//:0') {
		$modified = $rs_query->selectField('posts', 'p_modified', array(
			'p_id' => $id
		));
		
		$src .= '?cached=' . formatDate($modified, 'YmdHis');
	}
	
	$mime_type = $rs_query->selectField('postmeta', 'pm_value', array(
		'pm_post' => $id,
		'pm_key' => 'mime_type'
	));
	
	// Determine what kind of HTML tag to construct based on the media's MIME type
	if(str_starts_with($mime_type, 'image') || $src === '//:0') {
		// Image tag
		$alt_text = $rs_query->selectField('postmeta', 'pm_value', array(
			'pm_post' => $id,
			'pm_key' => 'alt_text'
		));
		
		$props = array_merge(array(
			'src' => $src,
			'alt' => $alt_text
		), $args);
		
		$tag_args = array();
		
		foreach($props as $key => $value) {
			if($key === 'cached') continue;
			
			$tag_args[$key] = $value;
		}
		
		return domTag('img', $tag_args);
	} elseif(str_starts_with($mime_type, 'audio')) {
		// Audio tag
		return '<audio' . (!empty($args['class']) ? ' class="' . $args['class'] . '"' : '') . ' src="' .
			$src . '"></audio>';
	} elseif(str_starts_with($mime_type, 'video')) {
		// Video tag
		return '<video' . (!empty($args['class']) ? ' class="' . $args['class'] . '"' : '') . ' src="' .
			$src . '"></video>';
	} else {
		// Anchor tag
		if(empty($args['link_text'])) {
			$args['link_text'] = $rs_query->selectField('posts', 'p_title', array(
				'p_id' => $id
			));
		}
		
		$tag_args = array();
		
		if(!empty($args['class'])) $tag_args['class'] = $args['class'];
		
		$tag_args['href'] = $src;
		
		if(!empty($args['newtab']) && $args['newtab'] === 1) {
			$tag_args['target'] = '_blank';
			$tag_args['rel'] = 'noreferrer noopener';
		}
		
		$tag_args['content'] = $args['link_text'];
		
		return domTag('a', $tag_args);
	}
}

/**
 * Fetch a taxonomy's id.
 * @since 1.5.0-alpha
 *
 * @param string $name -- The taxonomy's name.
 * @return int
 */
function getTaxonomyId(string $name): int {
	global $rs_query;
	
	$name = sanitize($name);
	
	return (int)$rs_query->selectField('taxonomies', 'ta_id', array(
		'ta_name' => $name
	)) ?? 0;
}

/**
 * Trim text down to a specified number of words.
 * @since 1.2.5-alpha
 *
 * @param string $text -- The text to trim.
 * @param int $num_words (optional) -- The number of words to include before trimming.
 * @param string $more (optional) -- The 'more' text.
 * @return string
 */
function trimWords(string $text, int $num_words = 50, string $more = '&hellip;'): string {
	$words = explode(' ', $text);
	
	if(count($words) > $num_words) {
		$words = array_slice($words, 0, $num_words);
		
		return implode(' ', $words) . $more;
	} else {
		return $text;
	}
}

/**
 * Sanitize a string of text.
 * @since 1.0.0-beta
 *
 * @param string $text -- The text to sanitize.
 * @param string $regex (optional) -- The regex pattern.
 * @param bool $lc (optional) -- Whether to format in lowercase.
 * @return string
 */
function sanitize(string $text, string $regex = '/[^A-Za-z0-9_-]/', bool $lc = true): string {
	$text = strip_tags($text);
	
	if($lc) $text = strtolower($text);
	
	return preg_replace($regex, '', $text);
}

/**
 * Create a button.
 * @since 1.2.7-beta
 *
 * @param array $args (optional) -- The args.
 * @param bool $link (optional) -- Whether to link the button.
 */
function button(array $args = array(), bool $link = false): void {
	if($link) {
		echo domTag('a', array(
			'id' => (!empty($args['id']) ? $args['id'] : ''),
			'class' => (!empty($args['class']) ? $args['class'] . ' ' : '') . 'button',
			'href' => ($args['link'] ?? '#'),
			'title' => (!empty($args['title']) ? $args['title'] : ''),
			'content' => ($args['label'] ?? 'Button')
		));
	} else {
		echo domTag('button', array(
			'id' => (!empty($args['id']) ? $args['id'] : ''),
			'class' => (!empty($args['class']) ? $args['class'] . ' ' : '') . 'button',
			'title' => (!empty($args['title']) ? $args['title'] : ''),
			'content' => ($args['label'] ?? 'Button')
		));
	}
}

/**
 * Format a date string.
 * @since 1.2.1-alpha
 *
 * @param string $date -- The raw date.
 * @param string $format (optional) -- The date format.
 * @return string
 */
function formatDate(string $date, string $format = 'Y-m-d H:i:s'): string {
	return date_format(date_create($date), $format);
}

/**
 * Generate a random hash.
 * @since 2.0.5-alpha
 *
 * @param int $length (optional) -- The length of the hash.
 * @param bool $special_chars (optional) -- Whether to include special characters.
 * @param string $salt (optional) -- The hash salt.
 * @return string
 */
function generateHash(int $length = 20, bool $special_chars = true, string $salt = ''): string {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	
	if($special_chars) $chars .= '!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
	
	$hash = '';
	
	for($i = 0; $i < (int)$length; $i++)
		$hash .= substr($chars, rand(0, strlen($chars) - 1), 1);
	
	if(!empty($salt)) $hash = substr(md5(md5($hash . $salt)), 0, (int)$length);
	
	return $hash;
}

/**
 * Generate a random password.
 * @since 1.3.0-alpha
 *
 * @param int $length (optional) -- The length of the password.
 * @param bool $special_chars (optional) -- Whether to include special characters.
 * @return string
 */
function generatePassword(int $length = 16, bool $special_chars = true): string {
	return generateHash($length, $special_chars);
}