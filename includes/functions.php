<?php
/**
 * Site-wide functions.
 * @since 1.0.0-alpha
 *
 * @package ReallySimpleCMS
 *
 * ## CONSTANTS ##
 * - string COOKIE_HASH
 *
 * ## FUNCTIONS ##
 * HEADER & FOOTER:
 * - getThemeScript(string $script, string $version): string
 * - putThemeScript(string $script, string $version): void
 * - getThemeStylesheet(string $stylesheet, string $version): string
 * - putThemeStylesheet(string $stylesheet, string $version): void
 * - headerScripts(string|array $exclude, array $include_styles, array $include_scripts): void
 * - footerScripts(string|array $exclude, array $include_styles, array $include_scripts): void
 * - bodyClasses(string|array $addtl_classes): string
 * - adminBar(): void
 * MISCELLANEOUS:
 * - handleSecureLogin(): void
 * - guessPageType(): void
 * - postTypeExists(string $type): bool
 * - taxonomyExists(string $taxonomy): bool
 * - getPost(string $slug): object
 * - getTerm(string $slug): object
 * - getCategory(string $slug): object
 * - getMenu(string $slug): void
 * - getWidget(string $slug, bool $display_title = false): void
 * - registerMenu(string $name, string $slug): void
 * - registerWidget(string $title, string $slug): void
 * - formatEmail(string $heading, array $fields): string
 */

// Generate a cookie hash based on the site's URL
define('COOKIE_HASH', md5(getSetting('site_url')));

/*------------------------------------*\
    HEADER & FOOTER
\*------------------------------------*/

/**
 * Fetch a theme-specific script file.
 * @since 2.0.7-alpha
 *
 * @param string $script -- The script to load.
 * @param string $version (optional) -- The script's version.
 * @return string
 */
function getThemeScript(string $script, string $version = RS_VERSION): string {
	$theme_path = slash(THEMES) . getSetting('theme');
	
	return '<script src="' . slash($theme_path) . $script .
		(!empty($version) ? '?v=' . $version : '') . '"></script>';
}

/**
 * Output a theme-specific script file.
 * @since 1.3.0-beta
 *
 * @param string $script -- The script to load.
 * @param string $version (optional) -- The script's version.
 */
function putThemeScript(string $script, string $version = RS_VERSION): void {
	echo getThemeScript($script, $version);
}

/**
 * Fetch a theme-specific stylesheet.
 * @since 2.0.7-alpha
 *
 * @param string $stylesheet -- The stylesheet to load.
 * @param string $version (optional) -- The stylesheet's version.
 * @return string
 */
function getThemeStylesheet(string $stylesheet, string $version = RS_VERSION): string {
	$theme_path = slash(THEMES) . getSetting('theme');
	
	return '<link href="' . slash($theme_path) . $stylesheet .
		(!empty($version) ? '?v=' . $version : '') . '" rel="stylesheet">';
}

/**
 * Output a theme-specific stylesheet.
 * @since 1.3.0-beta
 *
 * @param string $stylesheet -- The stylesheet to load.
 * @param string $version (optional) -- The stylesheet's version.
 */
function putThemeStylesheet(string $stylesheet, string $version = RS_VERSION): void {
	echo getThemeStylesheet($stylesheet, $version);
}

/**
 * Load all header scripts and stylesheets.
 * @since 2.4.2-alpha
 *
 * @param string|array $exclude (optional) -- The script(s) to exclude.
 * @param array $include_styles (optional) -- Any additional stylesheets to include.
 * @param array $include_scripts (optional) -- Any additional scripts to include.
 */
function headerScripts(string|array $exclude = '', array $include_styles = array(), array $include_scripts = array()): void {
	if(!is_array($exclude)) $exclude = explode(' ', $exclude);
	
	$debug = false;
	if(defined('DEBUG_MODE') && DEBUG_MODE) $debug = true;
	
	// Button stylesheet
	if(!in_array('button', $exclude, true))
		putStylesheet('button' . ($debug ? '' : '.min') . '.css');
	
	// Default stylesheet
	if(!in_array('style', $exclude, true))
		putStylesheet('style' . ($debug ? '' : '.min') . '.css');
	
	if(!in_array('fa', $exclude, true)) {
		// Font Awesome icons stylesheet
		putStylesheet('font-awesome.min.css', ICONS_VERSION);
	
		// Font Awesome font-face rules stylesheet
		putStylesheet('font-awesome-rules.min.css');
	}
	
	// Additional custom stylesheets
	if(!empty($include_styles) && is_array($include_styles)) {
		foreach($include_styles as $style)
			putThemeStylesheet($style[0] . '.css', $style[1] ?? THEME_VERSION);
	}
	
	// jQuery library
	if(!in_array('jquery', $exclude, true)) putScript('jquery.min.js', JQUERY_VERSION);
	
	// Additional custom scripts
	if(!empty($include_scripts) && is_array($include_scripts)) {
		foreach($include_scripts as $script)
			putThemeScript($script[0] . '.js', $script[1] ?? THEME_VERSION);
	}
}

/**
 * Load all footer scripts and stylesheets.
 * @since 2.4.2-alpha
 *
 * @param string|array $exclude (optional) -- The script(s) to exclude.
 * @param array $include_styles (optional) -- Any additional stylesheets to include.
 * @param array $include_scripts (optional) -- Any additional scripts to include.
 */
function footerScripts(string|array $exclude = '', array $include_styles = array(), array $include_scripts = array()): void {
	if(!is_array($exclude)) $exclude = explode(' ', $exclude);
	
	$debug = false;
	if(defined('DEBUG_MODE') && DEBUG_MODE) $debug = true;
	
	// Additional custom stylesheets
	if(!empty($include_styles) && is_array($include_styles)) {
		foreach($include_styles as $style)
			putThemeStylesheet($style[0] . '.css', $style[1] ?? THEME_VERSION);
	}
	
	// Default scripts
	if(!in_array('script', $exclude, true)) putScript('script.js');
	
	// Additional custom scripts
	if(!empty($include_scripts) && is_array($include_scripts)) {
		foreach($include_scripts as $script)
			putThemeScript($script[0] . '.js', $script[1] ?? THEME_VERSION);
	}
}

/**
 * Construct a list of CSS classes for the body tag.
 * @since 2.2.3-alpha
 *
 * @param string|array $addtl_classes (optional) -- Additional classes to include.
 * @return string
 */
function bodyClasses(string|array $addtl_classes = array()): string {
	global $rs_post, $rs_term, $session;
	
	$classes = array();
	
	if($rs_post) {
		$id = $rs_post->getPostId();
		$parent = $rs_post->getPostParent();
		$type = $rs_post->getPostType();
		
		$classes[] = getSetting('theme') . '-theme';
		$classes[] = $rs_post->getPostSlug($id);
		$classes[] = $type;
		$classes[] = $type . '-id-' . $id;
		
		if($parent !== 0) $classes[] = $rs_post->getPostSlug($parent) . '-child';
		if(isHomePage($id)) $classes[] = 'home-page';
	} elseif($rs_term) {
		$id = $rs_term->getTermId();
		$taxonomy = $rs_term->getTermTaxonomy();
		
		$classes[] = getSetting('theme') . '-theme';
		$classes[] = $rs_term->getTermSlug($id);
		$classes[] = $taxonomy;
		$classes[] = $taxonomy . '-id-' . $id;
	}
	
	$classes = array_merge($classes, (array)$addtl_classes);
	
	if($session) $classes[] = 'logged-in';
	
	return implode(' ', $classes);
}

/**
 * Construct an admin bar for logged in users.
 * @since 2.2.7-alpha
 */
function adminBar(): void {
	global $rs_post, $rs_term, $session, $post_types, $taxonomies;
	?>
	<div id="admin-bar">
		<ul class="menu">
			<li>
				<?php
				// Admin options
				echo domTag('a', array(
					'href' => 'javascript:void(0)',
					'content' => domTag('i', array(
						'class' => 'fa-solid fa-gauge-high'
					)) . ' ' . domTag('span', array(
						'content' => 'Admin'
					))
				));
				?>
				<ul class="sub-menu">
					<?php
					// Dashboard
					echo domTag('li', array(
						'content' => domTag('a', array(
							'href' => '/admin/',
							'content' => 'Dashboard'
						))
					));
					
					// Post types
					foreach($post_types as $post_type) {
						if(!userHasPrivilege('can_view_' . str_replace(' ', '_',
							$post_type['labels']['name_lowercase'])) || !$post_type['show_in_admin_bar']) continue;
						
						// Taxonomies
						$taxes = array();
						
						if(!empty($post_type['taxonomies'])) {
							foreach($post_type['taxonomies'] as $tax) {
								if(array_key_exists($tax, $taxonomies)) {
									if(userHasPrivilege('can_view_' . str_replace(' ', '_',
										$taxonomies[$tax]['labels']['name_lowercase'])) &&
										$taxonomies[$tax]['show_in_admin_bar']
									) {
										$taxes[] = domTag('li', array(
											'content' => domTag('a', array(
												'href' => '/admin/' . $taxonomies[$tax]['menu_link'],
												'content' => $taxonomies[$tax]['label']
											))
										));
									}
								}
							}
							
							$submenu = domTag('ul', array(
								'class' => 'sub-menu',
								'content' => implode('', $taxes)
							));
						}
						
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => '/admin/' . $post_type['menu_link'],
								'content' => $post_type['label']
							)) . (!empty($submenu) ? $submenu : null)
						));
						
						unset($submenu);
					}
					
					// Comments
					if(userHasPrivilege('can_view_comments')) {
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => '/admin/comments.php',
								'content' => 'Comments'
							))
						));
					}
					
					// Customization (themes/menus/widgets)
					if(userHasPrivileges(array(
						'can_view_themes',
						'can_view_menus',
						'can_view_widgets'
					), 'OR')) {
						$submenu = domTag('ul', array(
							'class' => 'sub-menu',
							'content' => (userHasPrivilege('can_view_themes') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/themes.php',
									'content' => 'Themes'
								))
							)) : null) . (userHasPrivilege('can_view_menus') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/menus.php',
									'content' => 'Menus'
								))
							)) : null) . (userHasPrivilege('can_view_widgets') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/widgets.php',
									'content' => 'Widgets'
								))
							)) : null)
						));
						
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => 'javascript:void(0)',
								'content' => 'Customization'
							)) . $submenu
						));
					}
					
					// Users
					if(userHasPrivilege('can_view_users')) {
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => '/admin/users.php',
								'content' => 'Users'
							))
						));
					}
					
					// Logins
					if(userHasPrivileges(array(
						'can_view_login_attempts',
						'can_view_login_blacklist',
						'can_view_login_rules'
					), 'OR')) {
						$submenu = domTag('ul', array(
							'class' => 'sub-menu',
							'content' => (userHasPrivilege('can_view_login_attempts') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/logins.php',
									'content' => 'Attempts'
								))
							)) : null) . (userHasPrivilege('can_view_login_blacklist') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/logins.php?page=blacklist',
									'content' => 'Blacklist'
								))
							)) : null) . (userHasPrivilege('can_view_login_rules') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/logins.php?page=rules',
									'content' => 'Rules'
								))
							)) : null)
						));
						
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => 'javascript:void(0)',
								'content' => 'Logins'
							)) . $submenu
						));
					}
					
					// Settings
					if(userHasPrivileges(array(
						'can_edit_settings',
						'can_view_user_roles'
					), 'OR')) {
						$submenu = domTag('ul', array(
							'class' => 'sub-menu',
							'content' => (userHasPrivilege('can_edit_settings') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/settings.php',
									'content' => 'General'
								))
							)) : null) . (userHasPrivilege('can_edit_settings') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/settings.php?page=design',
									'content' => 'Design'
								))
							)) : null) . (userHasPrivilege('can_view_user_roles') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/settings.php?page=user_roles',
									'content' => 'User Roles'
								))
							)) : null)
						));
						
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => 'javascript:void(0)',
								'content' => 'Settings'
							)) . $submenu
						));
					}
					
					// About
					echo domTag('li', array(
						'content' => domTag('a', array(
							'href' => '/admin/about.php',
							'content' => 'About'
						))
					));
					
					unset($submenu);
					?>
				</ul>
			</li>
			<li>
				<?php
				// New options
				echo domTag('a', array(
					'href' => 'javascript:void(0)',
					'content' => domTag('i', array(
						'class' => 'fa-solid fa-plus'
					)) . ' ' . domTag('span', array(
						'content' => 'New'
					))
				));
				?>
				<ul class="sub-menu">
					<?php
					// Post types
					foreach($post_types as $post_type) {
						if(!userHasPrivilege(($post_type['name'] === 'media' ? 'can_upload_media' :
							'can_create_' . str_replace(' ', '_', $post_type['labels']['name_lowercase']))) ||
							!$post_type['show_in_admin_bar']) continue;
						
						// Taxonomies
						$taxes = array();
						
						if(!empty($post_type['taxonomies'])) {
							foreach($post_type['taxonomies'] as $tax) {
								if(array_key_exists($tax, $taxonomies)) {
									if(userHasPrivilege('can_create_' . str_replace(' ', '_',
										$taxonomies[$tax]['labels']['name_lowercase'])) &&
										$taxonomies[$tax]['show_in_admin_bar']
									) {
										$taxes[] = domTag('li', array(
											'content' => domTag('a', array(
												'href' => '/admin/' . $taxonomies[$tax]['menu_link'] . (
													$tax === 'category' ? '?action=create' : '&action=create'
												),
												'content' => $taxonomies[$tax]['labels']['name_singular']
											))
										));
									}
								}
							}
							
							$submenu = domTag('ul', array(
								'class' => 'sub-menu',
								'content' => implode('', $taxes)
							));
						}
						
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => '/admin/' . $post_type['menu_link'] . ($post_type['name'] === 'media' ?
									'?action=upload' : ($post_type['name'] === 'post' ? '?action=create' : '&action=create')
								),
								'content' => $post_type['labels']['name_singular']
							)) . (!empty($submenu) ? $submenu : null)
						));
						
						unset($submenu);
					}
					
					// Customization (themes/menus/widgets)
					if(userHasPrivileges(array(
						'can_create_themes',
						'can_create_menus',
						'can_create_widgets'
					), 'OR')) {
						$submenu = domTag('ul', array(
							'class' => 'sub-menu',
							'content' => (userHasPrivilege('can_create_themes') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/themes.php?action=create',
									'content' => 'Theme'
								))
							)) : null) . (userHasPrivilege('can_create_menus') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/menus.php?action=create',
									'content' => 'Menu'
								))
							)) : null) . (userHasPrivilege('can_create_widgets') ? domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/widgets.php?action=create',
									'content' => 'Widget'
								))
							)) : null)
						));
						
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => 'javascript:void(0)',
								'content' => 'Customization'
							)) . $submenu
						));
					}
					
					// Users
					if(userHasPrivilege('can_create_users')) {
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => '/admin/users.php?action=create',
								'content' => 'User'
							))
						));
					}
					
					// Logins
					if(userHasPrivilege('can_create_login_rules')) {
						$submenu = domTag('ul', array(
							'class' => 'sub-menu',
							'content' => domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/logins.php?page=rules&action=create',
									'content' => 'Rule'
								))
							))
						));
						
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => 'javascript:void(0)',
								'content' => 'Login'
							)) . $submenu
						));
					}
					
					// Settings
					if(userHasPrivilege('can_create_user_roles')) {
						$submenu = domTag('ul', array(
							'class' => 'sub-menu',
							'content' => domTag('li', array(
								'content' => domTag('a', array(
									'href' => '/admin/settings.php?page=user_roles&action=create',
									'content' => 'User Roles'
								))
							))
						));
						
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => 'javascript:void(0)',
								'content' => 'Settings'
							)) . $submenu
						));
					}
					?>
				</ul>
			</li>
			<?php
			// Edit link
			if(!is_null($rs_post)) {
				echo domTag('li', array(
					'content' => domTag('a', array(
						'href' => '/admin/posts.php?id=' . $rs_post->getPostId() . '&action=edit',
						'content' => domTag('i', array(
							'class' => 'fa-solid fa-feather-pointed'
						)) . ' ' . domTag('span', array(
							'content' => 'Edit'
						))
					))
				));
			} elseif(!is_null($rs_term)) {
				echo domTag('li', array(
					'content' => domTag('a', array(
						'href' => '/admin/' . ($rs_term->getTermTaxonomy() === 'category' ? 'categories.php' : 'terms.php') .
							'?id=' . $rs_term->getTermId() . '&action=edit',
						'content' => domTag('i', array(
							'class' => 'fa-solid fa-feather-pointed'
						)) . ' ' . domTag('span', array(
							'content' => 'Edit'
						))
					))
				));
			}
			?>
		</ul>
		<div class="user-dropdown">
			<?php
			// Display name
			echo domTag('span', array(
				'content' => 'Welcome, ' . $session['display_name']
			));
			
			// Avatar
			echo getMedia($session['avatar'], array(
				'class' => 'avatar',
				'width' => 20,
				'height' => 20
			));
			?>
			<ul class="user-dropdown-menu">
				<?php
				// Avatar (large)
				echo getMedia($session['avatar'], array(
					'class' => 'avatar-large',
					'width' => 100,
					'height' => 100
				));
				
				// Profile
				echo domTag('li', array(
					'content' => domTag('a', array(
						'href' => '/admin/profile.php',
						'content' => 'My Profile'
					))
				));
				
				// Log out
				echo domTag('li', array(
					'content' => domTag('a', array(
						'href' => '/login.php?action=logout',
						'content' => 'Log Out'
					))
				));
				?>
			</ul>
		</div>
	</div>
	<?php
}

/*------------------------------------*\
    MISCELLANEOUS
\*------------------------------------*/

/**
 * Prevent direct access to `/login.php` if a login slug has been set.
 * @since 1.3.12-beta
 */
function handleSecureLogin(): void {
	$login_slug = getSetting('login_slug');
	
	// Check for secure login
	if(!empty($login_slug)) {
		if(!isset($_GET['secure_login'])) {
			if(str_starts_with($_SERVER['REQUEST_URI'], '/login.php')) {
				if(!isset($_GET['action']) && !isset($_GET['pw_reset']) && !isset($_GET['pw_forgot']))
					redirect('/404.php');
			} elseif(str_contains($_SERVER['REQUEST_URI'], $login_slug))
				redirect('/login.php?secure_login=' . $login_slug);
		} else {
			if($_GET['secure_login'] !== $login_slug)
				redirect('/404.php');
		}
	}
}

/**
 * Determine the type of page being viewed (e.g., post, term, etc.).
 * @since 1.3.11-beta
 */
function guessPageType(): void {
	global $rs_query, $rs_post, $rs_term;
	
	if(isset($_GET['preview']) && $_GET['preview'] === 'true' && isset($_GET['id']) && $_GET['id'] > 0) {
		$rs_post = new \Engine\Post;
	} else {
		$raw_uri = $_SERVER['REQUEST_URI'];
		
		// Check whether the current page is the home page
		if($raw_uri === '/' || str_starts_with($raw_uri, '/?')) {
			$home_page = $rs_query->selectField('settings', 's_value', array(
				's_name' => 'home_page'
			));
			
			$slug = $rs_query->selectField('posts', 'p_slug', array(
				'p_id' => $home_page
			));
		} else {
			$uri = explode('/', $raw_uri);
			$uri = array_filter($uri);
			
			// Remove the query string
			if(str_starts_with(end($uri), '?'))
				array_pop($uri);
			
			$slug = array_pop($uri);
		}
		
		// Check whether the current page is a post or a term
		if($rs_query->selectRow('posts', 'COUNT(p_slug)', array(
			'p_slug' => $slug
		)) > 0) {
			$rs_post = new \Engine\Post;
		} elseif($rs_query->selectRow('terms', 'COUNT(t_slug)', array(
			't_slug' => $slug
		)) > 0) {
			$rs_term = new \Engine\Term;
		} else {
			// Catastrophic failure, abort
			redirect('/404.php');
		}
	}
}

/**
 * Check whether a post type exists in the database.
 * @since 1.0.5-beta
 *
 * @param string $type -- The post's type.
 * @return bool
 */
function postTypeExists(string $type): bool {
	global $rs_query;
	
	$type = sanitize($type);
	
	return $rs_query->selectRow('posts', 'COUNT(p_type)', array(
		'p_type' => $type
	)) > 0;
}

/**
 * Check whether a taxonomy exists in the database.
 * @since 1.0.5-beta
 *
 * @param string $taxonomy -- The taxonomy's name.
 * @return bool
 */
function taxonomyExists(string $taxonomy): bool {
	global $rs_query;
	
	$taxonomy = sanitize($taxonomy);
	
	return $rs_query->selectRow('taxonomies', 'COUNT(ta_name)', array(
		'ta_name' => $taxonomy
	)) > 0;
}

/**
 * Create a Post object based on a provided slug.
 * @since 2.2.3-alpha
 *
 * @param string $slug -- The post's slug.
 * @return object
 */
function getPost(string $slug): object {
	return new \Engine\Post($slug);
}

/**
 * Create a Term object based on a provided slug.
 * @since 1.0.6-beta
 *
 * @param string $slug -- The term's slug.
 * @return object
 */
function getTerm(string $slug): object {
	return new \Engine\Term($slug);
}

/**
 * Alias for the getTerm function.
 * @since 2.4.1-alpha
 *
 * @see getTerm()
 * @param string $slug -- The category's slug.
 * @return object
 */
function getCategory(string $slug): object {
	return getTerm($slug);
}

/**
 * Fetch a nav menu.
 * @since 2.2.3-alpha
 *
 * @param string $slug -- The menu's slug.
 */
function getMenu(string $slug): void {
	$rs_menu = new \Engine\Menu;
	$rs_menu->getMenu($slug);
}

/**
 * Fetch a widget.
 * @since 2.2.1-alpha
 *
 * @param string $slug -- The widget's slug.
 * @param bool $display_title (optional) -- Whether to display the widget's title.
 */
function getWidget(string $slug, bool $display_title = false): void {
	global $rs_query;
	
	$px = 'p_';
	
	$widget = $rs_query->selectRow('posts', array($px . 'title', $px . 'content', $px . 'status'), array(
		$px . 'slug' => $slug,
		$px . 'type' => 'widget'
	));
	
	if(empty($widget)) {
		echo domTag('div', array(
			'class' => 'widget',
			'content' => domTag('h3', array(
				'class' => 'widget-title',
				'content' => 'The specified widget does not exist.'
			))
		));
	} elseif($widget[$px . 'status'] === 'inactive') {
		echo domTag('div', array(
			'class' => 'widget',
			'content' => domTag('h3', array(
				'class' => 'widget-title',
				'content' => 'The specified widget could not be loaded.'
			))
		));
	} else {
		echo domTag('div', array(
			'class' => 'widget ' . $slug,
			'content' => ($display_title ? domTag('h3', array(
				'class' => 'widget-title',
				'content' => $widget[$px . 'title']
			)) : '') . domTag('div', array(
				'class' => 'widget-content',
				'content' => $widget[$px . 'content']
			))
		));
	}
}

/**
 * Register a menu.
 * @since 1.0.0-beta
 *
 * @param string $name -- The menu's name.
 * @param string $slug -- The menu's slug.
 */
function registerMenu(string $name, string $slug): void {
	global $rs_query;
	
	$px = 't_';
	$slug = sanitize($slug);
	
	$menu = $rs_query->selectRow('terms', '*', array(
		$px . 'slug' => $slug,
		$px . 'taxonomy' => getTaxonomyId('nav_menu')
	));
	
	if(empty($menu)) {
		$rs_query->insert('terms', array(
			$px . 'name' => $name,
			$px . 'slug' => $slug,
			$px . 'taxonomy' => getTaxonomyId('nav_menu')
		));
	}
}

/**
 * Register a widget.
 * @since 1.0.0-beta
 *
 * @param string $title -- The widget's title.
 * @param string $slug -- The widget's slug.
 */
function registerWidget(string $title, string $slug): void {
	global $rs_query;
	
	$px = 'p_';
	$slug = sanitize($slug);
	
	$widget = $rs_query->selectRow('posts', '*', array(
		$px . 'slug' => $slug,
		$px . 'type' => 'widget'
	));
	
	if(empty($widget)) {
		$rs_query->insert('posts', array(
			$px . 'title' => $title,
			$px . 'created' => 'NOW()',
			$px . 'content' => '',
			$px . 'status' => 'active',
			$px . 'slug' => $slug,
			$px . 'type' => 'widget'
		));
	}
}

/**
 * Format an email message with HTML and CSS.
 * @since 2.0.5-alpha
 *
 * @param string $heading -- The email heading.
 * @param array $fields -- The email fields.
 * @return string
 */
function formatEmail(string $heading, array $fields): string {
	$content = '<div style="background-color: #ededed; padding: 3rem 0;">';
	$content .= '<div style="background-color: #fdfdfd; border: 1px solid #cdcdcd; border-top-color: #ededed; color: #101010 !important; margin: 0 auto; padding: 0.75rem 1.5rem; width: 60%;">';
	$content .= !empty($heading) ? '<h2 style="text-align: center;">' . $heading . '</h2>' : '';
	$content .= !empty($fields['name']) && !empty($fields['email']) ? '<p style="margin-bottom: 0;"><strong>Name:</strong> ' . $fields['name'] . '</p><p style="margin-top: 0;"><strong>Email:</strong> ' . $fields['email'] . '</p>' : '';
	$content .= '<p style="border-top: 1px dashed #adadad; padding-top: 1em;">' . str_replace("\r\n", '<br>', $fields['message']) . '</p>';
	$content .= '</div></div>';
	
	return $content;
}