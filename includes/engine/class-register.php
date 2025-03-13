<?php
/**
 * Core class used to implement the Register object. This handles registration of various components.
 * @since 1.4.0-beta_snap-03
 *
 * @package ReallySimpleCMS
 * @subpackage Engine
 *
 * ## CONSTANTS [5] ##
 * - public array REQUIRED_MODULES
 * - public array DEFAULT_THEMES
 * - public array DEFAULT_ADMIN_THEMES
 * - public array DEFAULT_POST_TYPES
 * - public array DEFAULT_TAXONOMIES
 *
 * ## METHODS [12] ##
 * MODULES:
 * - public registerModule(string $name, array $args): ?array
 * - public unregisterModule(string $name, bool $del_data = false): bool
 * THEMES:
 * - public registerTheme(string $name, array $args): ?array
 * - public unregisterTheme(string $name, bool $del_data): bool
 * ADMIN THEMES:
 * - public registerAdminTheme(string $name, array $args): ?array
 * - public unregisterAdminTheme(string $name, bool $del_data): bool
 * POST TYPES:
 * - public registerPostType(string $name, array $args): ?array
 * - public unregisterPostType(string $name, bool $del_posts): bool
 * - private getPostTypeLabels(string $name, array $labels): array
 * TAXONOMIES:
 * - public registerTaxonomy(string $name, string $post_type, array $args): ?array
 * - public unregisterTaxonomy(string $name, bool $del_terms): bool
 * - private getTaxonomyLabels(string $name, array $labels): array
 */
namespace Engine;

class Register {
	/**
	 * Required modules (included with fresh installs).
	 * These cannot be unregistered as they are required by core functionality.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @var array
	 */
	public const REQUIRED_MODULES = array('domtags');
	
	/**
	 * Default themes (included with fresh installs).
	 * Carbon theme cannot be unregistered as it is considered the primary default.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @var array
	 */
	public const DEFAULT_THEMES = array('carbon');
	
	/**
	 * Default admin themes (included with fresh installs).
	 * Bedrock theme cannot be unregistered as it is considered the primary default.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @var array
	 */
	public const DEFAULT_ADMIN_THEMES = array('bedrock', 'sky', 'forest', 'ocean', 'sunset', 'harvest');
	
	/**
	 * Default post types (included with fresh installs).
	 * These cannot be unregistered as they are required by core functionality.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @var array
	 */
	public const DEFAULT_POST_TYPES = array('page', 'media', 'post', 'nav_menu_item', 'widget');
	
	/**
	 * Default taxonomies (included with fresh installs).
	 * These cannot be unregistered as they are required by core functionality.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @var array
	 */
	public const DEFAULT_TAXONOMIES = array('category', 'nav_menu');
	
	/*------------------------------------*\
		MODULES
	\*------------------------------------*/
	
	/**
	 * Register a module.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 * @param string $name -- The module's name.
	 * @param array $args (optional) -- The args.
	 * @return null|array
	 */
	public function registerModule(string $name, array $args = array()): ?array {
		global $rs_modules;
		
		if(!is_array($rs_modules)) $rs_modules = array();
		
		$name = sanitize($name);
		
		if(empty($name) || strlen($name) > 20)
			exit('A module\'s name must be between 1 and 20 characters long.');
		
		// If the name is already registered, abort
		if(moduleExists($name)) return null;
		
		$defaults = array(
			'label' => $name,
			'author' => array(),
			'version' => null,
			'description' => ''
			#'slug' => $name,
			#'labels' => array(),
			#'public' => true,
		);
		
		$args = array_merge($defaults, $args);
		
		// Remove any unrecognized args
		foreach($args as $key => $value)
			if(!array_key_exists($key, $defaults)) unset($args[$key]);
		
		$args['is_required'] = in_array($name, self::REQUIRED_MODULES, true) ? true : false;
		$args['name'] = $name;
		
		// Add the module to the global array
		$rs_modules[$name] = $args;
		
		return $rs_modules[$name];
	}

	/**
	 * Unregister a module.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 * @param string $name -- The module's name.
	 * @param bool $del_data (optional) -- Whether to delete all associated data.
	 * @return bool
	 */
	public function unregisterModule(string $name, bool $del_data = false): bool {
		global $rs_modules;
		
		$name = sanitize($name);
		
		if(moduleExists($name) && !$rs_modules[$name]['is_required']) {
			$file_path = slash(PATH . MODULES) . slash($name);
			
			if($del_data) removeDir($file_path);
			
			unset($rs_modules[$name]);
			return true;
		}
		
		return false;
	}
	
	/*------------------------------------*\
		THEMES
	\*------------------------------------*/
	
	/**
	 * Register a theme.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 * @param string $name -- The theme's name.
	 * @param array $args (optional) -- The args.
	 * @return null|array
	 */
	public function registerTheme(string $name, array $args = array()): ?array {
		global $rs_themes;
		
		if(!is_array($rs_themes)) $rs_themes = array();
		
		$name = sanitize($name);
		
		if(empty($name) || strlen($name) > 20)
			exit('A theme\'s name must be between 1 and 20 characters long.');
		
		// If the name is already registered, abort
		if(themeExists($name)) return null;
		
		$defaults = array(
			'label' => ucwords($name),
			'author' => array(),
			'version' => null,
			'stylesheet' => null
			#'labels' => array()
		);
		
		$args = array_merge($defaults, $args);
		
		// Remove any unrecognized args
		foreach($args as $key => $value)
			if(!array_key_exists($key, $defaults)) unset($args[$key]);
		
		$args['stylesheet'] = slash(PATH . THEMES) . slash($name) . 'style.css';
		$args['is_default'] = in_array($name, self::DEFAULT_THEMES, true) ? true : false;
		$args['name'] = $name;
		
		// Add the admin theme to the global array
		$rs_themes[$name] = $args;
		
		return $rs_themes[$name];
	}
	
	/**
	 * Unregister a theme.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 * @param string $name -- The theme's name.
	 * @param bool $del_data (optional) -- Whether to delete all associated data.
	 * @return bool
	 */
	public function unregisterTheme(string $name, bool $del_data = false): bool {
		global $rs_themes;
		
		$name = sanitize($name);
		
		if(themeExists($name) && $name !== 'carbon') {
			$file_path = slash(PATH . THEMES) . slash($name);
			
			if($del_data) removeDir($file_path);
			
			unset($rs_admin_themes[$name]);
			return true;
		}
		
		return false;
	}
	
	/*------------------------------------*\
		ADMIN THEMES
	\*------------------------------------*/
	
	/**
	 * Register an admin theme.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 * @param string $name -- The theme's name.
	 * @param array $args (optional) -- The args.
	 * @return null|array
	 */
	public function registerAdminTheme(string $name, array $args = array()): ?array {
		global $rs_admin_themes;
		
		if(!is_array($rs_admin_themes)) $rs_admin_themes = array();
		
		$name = sanitize($name);
		
		if(empty($name) || strlen($name) > 20)
			exit('An admin theme\'s name must be between 1 and 20 characters long.');
		
		// If the name is already registered, abort
		if(adminThemeExists($name)) return null;
		
		$defaults = array(
			'label' => ucwords($name),
			'author' => array(),
			'version' => null,
			'path' => null
			#'labels' => array()
		);
		
		$args = array_merge($defaults, $args);
		
		// Remove any unrecognized args
		foreach($args as $key => $value)
			if(!array_key_exists($key, $defaults)) unset($args[$key]);
		
		$args['path'] = slash(PATH . ADMIN_THEMES) . slash($name) . $name . '.css';
		$args['is_default'] = in_array($name, self::DEFAULT_ADMIN_THEMES, true) ? true : false;
		$args['name'] = $name;
		
		// Add the admin theme to the global array
		$rs_admin_themes[$name] = $args;
		
		return $rs_admin_themes[$name];
	}
	
	/**
	 * Unregister an admin theme.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 * @param string $name -- The theme's name.
	 * @param bool $del_data (optional) -- Whether to delete all associated data.
	 * @return bool
	 */
	public function unregisterAdminTheme(string $name, bool $del_data = false): bool {
		global $rs_admin_themes;
		
		$name = sanitize($name);
		
		if(adminThemeExists($name) && $name !== 'bedrock') {
			$file_path = slash(PATH . ADMIN_THEMES) . slash($name);
			
			if($del_data) removeDir($file_path);
			
			unset($rs_admin_themes[$name]);
			return true;
		}
		
		return false;
	}
	
	/*------------------------------------*\
		POST TYPES
	\*------------------------------------*/
	
	/**
	 * Register a post type.
	 * @since 1.0.0-beta
	 *
	 * @access public
	 * @param string $name -- The post type's name.
	 * @param array $args (optional) -- The args.
	 * @return null|array
	 */
	public function registerPostType(string $name, array $args = array()): ?array {
		global $rs_query, $rs_post_types, $rs_taxonomies;
		
		if(!is_array($rs_post_types)) $rs_post_types = array();
		
		$name = sanitize($name);
		
		if(empty($name) || strlen($name) > 20)
			exit('A post type\'s name must be between 1 and 20 characters long.');
		
		// If the name is already registered, abort
		if(postTypeExists($name) || taxonomyExists($name)) return null;
		
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
		
		$args['is_default'] = in_array($name, self::DEFAULT_POST_TYPES, true) ? true : false;
		$args['name'] = $name;
		$args['labels'] = $this->getPostTypeLabels($name, $args['labels']);
		$args['label'] = $args['labels']['name'];
		
		// Add the post type to the global array
		$rs_post_types[$name] = $args;
		
		if($args['create_privileges']) {
			$name_lowercase = str_replace(' ', '_', $args['labels']['name_lowercase']);
			
			$privileges = array(
				'can_view_' . $name_lowercase,
				'can_create_' . $name_lowercase,
				'can_edit_' . $name_lowercase,
				'can_delete_' . $name_lowercase
			);
			
			$db_privileges = $rs_query->select(array('user_privileges', 'up_'), '*', array(
				'name' => array('IN', $privileges[0], $privileges[1], $privileges[2], $privileges[3]),
				'is_default' => ($args['is_default'] ? 1 : 0)
			));
			
			if(empty($db_privileges)) {
				$insert_ids = array();
				
				for($i = 0; $i < count($privileges); $i++) {
					$insert_ids[] = $rs_query->insert(array('user_privileges', 'up_'), array(
						'name' => $privileges[$i]
					));
					
					if($privileges[$i] === 'can_view_' . $name_lowercase ||
						$privileges[$i] === 'can_create_' . $name_lowercase ||
						$privileges[$i] === 'can_edit_' . $name_lowercase
					) {
						// Editor
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Editor'),
							'privilege' => $insert_ids[$i]
						));
						
						// Moderator
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Moderator'),
							'privilege' => $insert_ids[$i]
						));
						
						// Administrator
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Administrator'),
							'privilege' => $insert_ids[$i]
						));
					} elseif($privileges[$i] === 'can_delete_' . $name_lowercase) {
						// Moderator
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Moderator'),
							'privilege' => $insert_ids[$i]
						));
						
						// Administrator
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Administrator'),
							'privilege' => $insert_ids[$i]
						));
					}
				}
			}
		}
		
		return $rs_post_types[$name];
	}

	/**
	 * Unregister a post type.
	 * @since 1.0.5-beta
	 *
	 * @access public
	 * @param string $name -- The post type's name.
	 * @param bool $del_posts -- Whether to delete all post data from the database.
	 * @return bool
	 */
	public function unregisterPostType(string $name, bool $del_posts = false): bool {
		global $rs_query, $rs_post_types;
		
		$name = sanitize($name);
		
		if(postTypeExists($name) && !$rs_post_types[$name]['is_default']) {
			if($del_posts) {
				$posts = $rs_query->select(array('posts', 'p_'), 'id', array(
					'type' => $name
				));
				
				foreach($posts as $post) {
					$rs_query->delete(array('postmeta', 'pm_'), array(
						'post' => $post['p_id']
					));
				}
				
				$rs_query->delete(array('posts', 'p_'), array(
					'type' => $name
				));
			}
			
			$type = str_replace(' ', '_', $rs_post_types[$name]['labels']['name_lowercase']);
			
			$privileges = array(
				'can_view_' . $type,
				'can_create_' . $type,
				'can_edit_' . $type,
				'can_delete_' . $type
			);
			
			foreach($privileges as $privilege) {
				$rs_query->delete(array('user_relationships', 'ue_'), array(
					'privilege' => getUserPrivilegeId($privilege)
				));
				
				$rs_query->delete(array('user_privileges', 'up_'), array(
					'name' => $privilege
				));
			}
			
			unset($rs_post_types[$name]);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Set all post type labels.
	 * @since 1.0.1-beta
	 *
	 * @access private
	 * @param string $name -- The post type's name.
	 * @param array $labels (optional) -- Any predefined labels.
	 * @return array
	 */
	private function getPostTypeLabels(string $name, array $labels = array()): array {
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
	
	/*------------------------------------*\
		TAXONOMIES
	\*------------------------------------*/
	
	/**
	 * Register a taxonomy.
	 * @since 1.0.1-beta
	 *
	 * @param string $name -- The taxonomy's name.
	 * @param string $post_type -- The associated post type.
	 * @param array $args (optional) -- The args.
	 * @return null|array
	 */
	public function registerTaxonomy(string $name, string $post_type, array $args = array()): ?array {
		global $rs_query, $rs_taxonomies, $rs_post_types;
		
		if(!is_array($rs_taxonomies)) $rs_taxonomies = array();
		
		$name = sanitize($name);
		
		if(empty($name) || strlen($name) > 20)
			exit('A taxonomy\'s name must be between 1 and 20 characters long.');
		
		// If the name is already registered, abort
		if(taxonomyExists($name) || postTypeExists($name)) return null;
		
		$taxonomy = $rs_query->selectRow(array('taxonomies', 'ta_'), '*', array(
			'name' => $name
		));
		
		if(empty($taxonomy)) {
			$rs_query->insert(array('taxonomies', 'ta_'), array(
				'name' => $name
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
		
		$args['is_default'] = in_array($name, self::DEFAULT_TAXONOMIES, true) ? true : false;
		$args['post_type'] = $post_type;
		$args['name'] = $name;
		$args['labels'] = $this->getTaxonomyLabels($name, $args['labels']);
		$args['label'] = $args['labels']['name'];
		
		// Add the taxonomy to the global array
		$rs_taxonomies[$name] = $args;
		
		if($args['create_privileges']) {
			$name_lowercase = str_replace(' ', '_', $args['labels']['name_lowercase']);
			
			$privileges = array(
				'can_view_' . $name_lowercase,
				'can_create_' . $name_lowercase,
				'can_edit_' . $name_lowercase,
				'can_delete_' . $name_lowercase
			);
			
			$db_privileges = $rs_query->select(array('user_privileges', 'up_'), '*', array(
				'name' => array('IN', $privileges[0], $privileges[1], $privileges[2], $privileges[3]),
				'is_default' => ($args['is_default'] ? 1 : 0)
			));
			
			if(empty($db_privileges)) {
				$insert_ids = array();
				
				for($i = 0; $i < count($privileges); $i++) {
					$insert_ids[] = $rs_query->insert(array('user_privileges', 'up_'), array(
						'name' => $privileges[$i]
					));
					
					if($privileges[$i] === 'can_view_' . $name_lowercase ||
						$privileges[$i] === 'can_create_' . $name_lowercase ||
						$privileges[$i] === 'can_edit_' . $name_lowercase
					) {
						// Editor
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Editor'),
							'privilege' => $insert_ids[$i]
						));
						
						// Moderator
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Moderator'),
							'privilege' => $insert_ids[$i]
						));
						
						// Administrator
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Administrator'),
							'privilege' => $insert_ids[$i]
						));
					} elseif($privileges[$i] === 'can_delete_' . $name_lowercase) {
						// Moderator
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Moderator'),
							'privilege' => $insert_ids[$i]
						));
						
						// Administrator
						$rs_query->insert(array('user_relationships', 'ue_'), array(
							'role' => getUserRoleId('Administrator'),
							'privilege' => $insert_ids[$i]
						));
					}
				}
			}
		}
		
		if(!empty($args['default_term']['name']) && !empty($args['default_term']['slug'])) {
			$term = $rs_query->selectRow(array('terms', 't_'), 'COUNT(*)', array(
				'slug' => $args['default_term']['slug']
			)) > 0;
			
			if(!$term) {
				$rs_query->insert(array('terms', 't_'), array(
					'name' => $args['default_term']['name'],
					'slug' => $args['default_term']['slug'],
					'taxonomy' => getTaxonomyId($name)
				));
			}
		}
		
		return $rs_taxonomies[$name];
	}

	/**
	 * Unregister a taxonomy.
	 * @since 1.0.5-beta
	 *
	 * @param string $name -- The taxonomy's name.
	 * @param bool $del_terms -- Whether to delete all term data from the database.
	 * @return bool
	 */
	function unregisterTaxonomy(string $name, bool $del_terms = false): bool {
		global $rs_query, $rs_taxonomies;
		
		$name = sanitize($name);
		
		if(taxonomyExists($name) && !$rs_taxonomies[$name]['is_default']) {
			if($del_terms) {
				$terms = $rs_query->select(array('terms', 't_'), 'id', array(
					'taxonomy' => getTaxonomyId($name)
				));
				
				foreach($terms as $term) {
					$rs_query->delete(array('term_relationships', 'tr_'), array(
						'term' => $term
					));
					
					$rs_query->delete(array('terms', 't_'), array(
						'id' => $term
					));
				}
				
				$rs_query->delete(array('taxonomies', 'ta_'), array(
					'name' => $name
				));
			}
			
			$taxonomy = str_replace(' ', '_', $rs_taxonomies[$name]['labels']['name_lowercase']);
			
			$privileges = array(
				'can_view_' . $taxonomy,
				'can_create_' . $taxonomy,
				'can_edit_' . $taxonomy,
				'can_delete_' . $taxonomy
			);
			
			foreach($privileges as $privilege) {
				$rs_query->delete(array('user_relationships', 'ue_'), array(
					'privilege' => getUserPrivilegeId($privilege)
				));
				
				$rs_query->delete(array('user_privileges', 'up_'), array(
					'name' => $privilege
				));
			}
			
			unset($rs_taxonomies[$name]);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Set all taxonomy labels.
	 * @since 1.0.4-beta
	 *
	 * @access private
	 * @param string $name -- The taxonomy's name.
	 * @param array $labels (optional) -- Any predefined labels.
	 * @return array
	 */
	private function getTaxonomyLabels(string $name, array $labels = array()): array {
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
}