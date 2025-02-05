<?php
/**
 * Core class used to implement the Menu object.
 * This class loads data from the terms, term relationships, posts, and postmeta tables of the database
 *  for use on the front end of the CMS.
 * @since 2.2.3-alpha
 *
 * @package ReallySimpleCMS
 * @subpackage Engine
 *
 * ## VARIABLES ##
 * - private array $tables
 * - private array $px
 *
 * ## METHODS ##
 * - public getMenu(string $slug): void
 * - private getMenuItemDescendants(int $id): void
 * GETTER METHODS:
 * - private getMenuItemMeta(int $id): array
 * - private getMenuItemParent(int $id): int
 * MISCELLANEOUS:
 * - private isCurrentPage(string $uri): bool
 * - private menuItemHasParent(int $id): bool
 * - private menuItemHasChildren(int $id): bool
 */
namespace Engine;

class Menu {
	/**
	 * The associated database tables.
	 * 0 => `terms`, 1 => `posts`
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var array
	 */
	private $tables = array('terms', 'posts');
	
	/**
	 * The table prefixes.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var array
	 */
	private $px = array('t_', 'p_');
	
	/**
	 * Construct a nav menu.
	 * @since 2.2.2-alpha
	 *
	 * @access public
	 * @param string $slug -- The menu's slug.
	 */
	public function getMenu(string $slug): void {
		global $rs_query, $post_types, $taxonomies;
		
		$id = $rs_query->selectField($this->tables[0], $this->px[0] . 'id', array(
			$this->px[0] . 'slug' => $slug
		));
		?>
		<nav class="nav-menu menu-id-<?php echo $id; ?>">
			<ul>
				<?php
				$relationships = $rs_query->select('term_relationships', 'tr_post', array(
					'tr_term' => $id
				));
				
				$itemmeta = array();
				$i = 0;
				
				foreach($relationships as $relationship) {
					$itemmeta[] = $this->getMenuItemMeta($relationship['tr_post']);
					$itemmeta[$i] = array_reverse($itemmeta[$i]);
					$itemmeta[$i]['post'] = $relationship['tr_post'];
					$i++;
				}
				
				// Sort the array in ascending index order
				asort($itemmeta);
				
				foreach($itemmeta as $meta) {
					$menu_item = $rs_query->selectRow($this->tables[1], array(
						$this->px[1] . 'id', $this->px[1] . 'title', $this->px[1] . 'status'
					), array(
						$this->px[1] . 'id' => $meta['post']
					));
					
					// Skip over invalid items
					if($menu_item[$this->px[1] . 'status'] === 'invalid') continue;
					
					if(!$this->menuItemHasParent($menu_item[$this->px[1] . 'id'])) {
						$domain = $_SERVER['HTTP_HOST'];
						$permalink = '';
						$external = false;
						
						if(isset($meta['post_link'])) {
							$type = $rs_query->selectField($this->tables[1], $this->px[1] . 'type', array(
								$this->px[1] . 'id' => $meta['post_link']
							));
							
							if(!empty($type) && $post_types[$type]['show_in_nav_menus']) {
								$permalink = isHomePage((int)$meta['post_link']) ? '/' :
									getPermalink($type, $this->getMenuItemParent($meta['post_link']));
							}
						} elseif(isset($meta['term_link'])) {
							$tax_id = $rs_query->selectField($this->tables[0], $this->px[0] . 'taxonomy', array(
								$this->px[0] . 'id' => $meta['term_link']
							));
							
							$taxonomy = $rs_query->selectField('taxonomies', 'ta_name', array(
								'ta_id' => $tax_id
							));
							
							if(!empty($taxonomy) && $taxonomies[$taxonomy]['show_in_nav_menus']) {
								$permalink = getPermalink($taxonomy, $this->getMenuItemParent($meta['term_link']));
							}
						} elseif(isset($meta['custom_link'])) {
							$permalink = $meta['custom_link'];
							
							// Set up external links
							if(!str_contains($permalink, $domain)) $external = true;
						}
						
						if(!empty($permalink)) {
							$classes = array();
							
							if($this->isCurrentPage($permalink)) $classes[] = 'current-menu-item';
							if($this->menuItemHasChildren($menu_item[$this->px[1] . 'id'])) $classes[] = 'menu-item-has-children';
							
							// Sort the classes to make sure they're in alphabetical order
							asort($classes);
							
							$tag_args = array(
								'href' => $permalink
							);
							
							if($external === true) {
								$tag_args['target'] = '_blank';
								$tag_args['rel'] = 'noreferrer noopener';
							}
							
							$tag_args['content'] = $menu_item[$this->px[1] . 'title'];
							?>
							<li<?php echo !empty($classes) ? ' class="' . implode(' ', $classes) . '"' : ''; ?>>
								<?php
								echo domTag('a', $tag_args);
								
								if($this->menuItemHasChildren($menu_item[$this->px[1] . 'id']))
									$this->getMenuItemDescendants($menu_item[$this->px[1] . 'id']);
								?>
							</li>
							<?php
						}
					}
				}
				?>
			</ul>
		</nav>
		<?php
	}
	
	/**
	 * Fetch all descendants of a menu item.
	 * @since 2.2.2-alpha
	 *
	 * @access private
	 * @param int $id
	 */
	private function getMenuItemDescendants(int $id): void {
		global $rs_query, $post_types;
		?>
		<ul class="sub-menu">
			<?php
			$children = $rs_query->select($this->tables[1], $this->px[1] . 'id', array(
				$this->px[1] . 'parent' => $id
			));
			
			$itemmeta = array();
			$i = 0;
			
			foreach($children as $child) {
				$itemmeta[] = $this->getMenuItemMeta($child[$this->px[1] . 'id']);
				$itemmeta[$i] = array_reverse($itemmeta[$i]);
				$itemmeta[$i]['post'] = $child[$this->px[1] . 'id'];
				$i++;
			}
			
			// Sort the array in ascending index order
			asort($itemmeta);
			
			foreach($itemmeta as $meta) {
				$menu_item = $rs_query->selectRow($this->tables[1], array($this->px[1] . 'id', $this->px[1] . 'title'), array(
					$this->px[1] . 'id' => $meta['post']
				));
				
				$domain = $_SERVER['HTTP_HOST'];
				$permalink = '';
				$external = false;
				
				if(isset($meta['post_link'])) {
					$type = $rs_query->selectField($this->tables[1], $this->px[1] . 'type', array(
						$this->px[1] . 'id' => $meta['post_link']
					));
					
					if($post_types[$type]['show_in_nav_menus']) {
						$permalink = isHomePage((int)$meta['post_link']) ? '/' :
							getPermalink($type, $this->getMenuItemParent($meta['post_link']));
					}
				} elseif(isset($meta['term_link'])) {
					$permalink = getPermalink('category', $this->getMenuItemParent($meta['term_link']));
				} elseif(isset($meta['custom_link'])) {
					$permalink = $meta['custom_link'];
					
					// Set up external links
					if(!str_contains($permalink, $domain)) $external = true;
				}
				
				if(!empty($permalink)) {
					$classes = array();
					
					if($this->isCurrentPage($permalink)) $classes[] = 'current-menu-item';
					if($this->menuItemHasChildren($menu_item[$this->px[1] . 'id'])) $classes[] = 'menu-item-has-children';
					
					// Sort the classes to make sure they're in alphabetical order
					asort($classes);
					
					$tag_args = array(
						'href' => $permalink
					);
					
					if($external === true) {
						$tag_args['target'] = '_blank';
						$tag_args['rel'] = 'noreferrer noopener';
					}
					
					$tag_args['content'] = $menu_item[$this->px[1] . 'title'];
					?>
					<li<?php echo !empty($classes) ? ' class="' . implode(' ', $classes) . '"' : ''; ?>>
						<?php
						echo domTag('a', $tag_args);
						
						if($this->menuItemHasChildren($menu_item[$this->px[1] . 'id']))
							$this->getMenuItemDescendants($menu_item[$this->px[1] . 'id']);
						?>
					</li>
					<?php
				}
			}
			?>
		</ul>
		<?php
	}
	
	/*------------------------------------*\
		GETTER METHODS
	\*------------------------------------*/
	
	/**
	 * Fetch a menu item's metadata.
	 * @since 2.2.2-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return array
	 */
	private function getMenuItemMeta(int $id): array {
		global $rs_query;
		
		$itemmeta = $rs_query->select('postmeta', array('pm_key', 'pm_value'), array(
			'pm_post' => $id
		));
		
		$meta = array();
		
		foreach($itemmeta as $metadata) {
			$values = array_values($metadata);
			
			for($i = 0; $i < count($metadata); $i += 2)
				$meta[$values[$i]] = $values[$i + 1];
		}
		
		return $meta;
	}

	/**
	 * Fetch a menu item's parent.
	 * @since 2.2.2-alpha
	 *
	 * @access private
	 * @param int $id
	 * @return int
	 */
	private function getMenuItemParent(int $id): int {
		global $rs_query;
		
		return $rs_query->selectField($this->tables[1], $this->px[1] . 'id', array(
			$this->px[1] . 'id' => $id
		));
	}
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Check whether a menu item's URI matches the current page URI.
	 * @since 2.2.3-alpha
	 *
	 * @access private
	 * @param string $uri -- The page URI.
	 * @return bool
	 */
	private function isCurrentPage(string $uri): bool {
		global $rs_query;
		
		return $uri === $_SERVER['REQUEST_URI'];
	}
	
	/**
	 * Check whether a menu item has a parent.
	 * @since 2.2.2-alpha
	 *
	 * @access private
	 * @param int $id -- The child menu item's id.
	 * @return bool
	 */
	private function menuItemHasParent(int $id): bool {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->tables[1], $this->px[1] . 'parent', array(
			$this->px[1] . 'id' => $id
		)) !== 0;
	}

	/**
	 * Check whether a menu item has children.
	 * @since 2.2.2-alpha
	 *
	 * @access private
	 * @param int $id -- The parent menu item's id.
	 * @return bool
	 */
	private function menuItemHasChildren(int $id): bool {
		global $rs_query;
		
		return $rs_query->select($this->tables[1], 'COUNT(*)', array(
			$this->px[1] . 'parent' => $id
		)) > 0;
	}
}