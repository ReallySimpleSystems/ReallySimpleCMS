<?php
/**
 * Admin class used to implement the Menu object. Inherits from the Term class.
 * Menus are used for website navigation on the front end of the website.
 * Menus can be created, modified, and deleted. Menus are stored in the `terms` table under the `nav_menu` taxonomy. Menu items are stored in the `posts` table as the `nav_menu_item` post type.
 * @since 1.8.0-alpha
 *
 * @package ReallySimpleCMS
 * @subpackage Admin
 *
 * ## VARIABLES [3] ##
 * See `Term` class for a list of inherited vars
 * - private string $tax_name
 * - private string $item_post_type
 * - private int $members
 *
 * ## METHODS [36] ##
 * See `Term` class for a list of inherited methods
 * - public __construct(int $id, string $action)
 * LISTS, FORMS, & ACTIONS:
 * - public listRecords(): void
 * - public createRecord(): void
 * - public editRecord(): void
 * - public deleteRecord(): void
 * - private createMenuItem(string $type, int $id, int $index): array
 * - private editMenuItem(int $id): void
 * - private moveUpMenuItem(int $id): void
 * - private moveDownMenuItem(int $id): void
 * - private deleteMenuItem(int $id): void
 * VALIDATION:
 * - private validateMenuSubmission(array $data): string
 * - private validateMenuItemSubmission(array $data, int $id): string
 * MISCELLANEOUS:
 * - public pageHeading(): void
 * - private exitNotice(string $exit_status, int $status_code): string
 * - private slugExists(string $slug): bool
 * - private isFirstSibling(int $id): bool
 * - private isLastSibling(int $id): bool
 * - private isPreviousSibling(int $previous, int $id): bool
 * - private isNextSibling(int $next, int $id): bool
 * - private isDescendant(int $id, int $ancestor): bool
 * - private hasSiblings(int $id): bool
 * - private getMenuItems(): void
 * - private getMenuItemsSidebar(): void
 * - private getMenuItemsList(int $id, string $type): string
 * - private getMenuItemDepth(int $id): int
 * - private getMenuItemMeta(int $id): array
 * - private getMenuRelationships(int $exclude): array
 * - private getParent(int $id): int
 * - private getParentList(int $parent, int $id): string
 * - private getSiblings(int $id): array
 * - private getPreviousSibling(int $id): int
 * - private getNextSibling(int $id): int
 * - private getFamilyTree(int $id): int
 * - private getDescendants(int $id): void
 * - private getResults(?string $search): array
 * - private getEntryCount(?string $search): int
 */
namespace Admin;

class Menu extends Term implements AdminInterface {
	/**
	 * The currently queried menu's taxonomy.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var string
	 */
	private $tax_name = 'nav_menu';
	
	/**
	 * The currently queried menu item's post type.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var string
	 */
	private $item_post_type = 'nav_menu_item';
	
	/**
	 * The number of members in a menu item's family tree.
	 * @since 1.8.7-alpha
	 *
	 * @access private
	 * @var int
	 */
	private $members = 0;
	
	/**
	 * Class constructor.
	 * @since 1.1.1-beta
	 *
	 * @access public
	 * @param int $id -- The menu's id.
	 * @param string $action -- The current action.
	 */
	public function __construct(int $id, string $action) {
		global $rs_query;
		
		$this->action = $action;
		
		if($id > 0) {
			$cols = array_keys(get_object_vars($this));
			$exclude = array('action', 'paged', 'tables', 'px', 'tax_name', 'item_post_type', 'members');
			$cols = array_diff($cols, $exclude);
			
			$menu = $rs_query->selectRow(array($this->tables[0], $this->px[0]), $cols, array(
				'id' => $id,
				'taxonomy' => getTaxonomyId($this->tax_name)
			));
			
			foreach($menu as $key => $value) {
				$col = substr($key, mb_strlen($this->px[0]));
				$this->$col = $menu[$key];
			}
		} else {
			$this->id = 0;
		}
	}
	
	/*------------------------------------*\
		LISTS, FORMS, & ACTIONS
	\*------------------------------------*/
	
	/**
	 * Construct a list of all menus in the database.
	 * @since 1.8.0-alpha
	 *
	 * @access public
	 */
	public function listRecords(): void {
		global $rs_query;
		
		// Query vars
		$search = $_GET['search'] ?? null;
		$this->paged = paginate((int)($_GET['paged'] ?? 1));
		
		$this->pageHeading();
		?>
		<table class="data-table">
			<thead>
				<?php
				$header_cols = array(
					'name' => 'Name',
					'slug' => 'Slug',
					'count' => 'Item Count'
				);
				
				echo tableHeaderRow($header_cols);
				?>
			</thead>
			<tbody>
				<?php
				$menus = $this->getResults($search);
				
				foreach($menus as $menu) {
					list($m_id, $m_name, $m_slug, $m_count) = array(
						$menu[$this->px[0] . 'id'],
						$menu[$this->px[0] . 'name'],
						$menu[$this->px[0] . 'slug'],
						$menu[$this->px[0] . 'count']
					);
					
					// Action links
					$actions = array(
						// Edit
						userHasPrivilege('can_edit_menus') ? actionLink('edit', array(
							'caption' => 'Edit',
							'id' => $m_id
						)) : null,
						// Delete
						userHasPrivilege('can_delete_menus') ? actionLink('delete', array(
							'classes' => 'modal-launch delete-item',
							'data_item' => 'menu',
							'caption' => 'Delete',
							'id' => $m_id
						)) : null
					);
					
					// Filter out any empty actions
					$actions = array_filter($actions);
					
					echo tableRow(
						// Name
						tdCell(domTag('strong', array(
							'content' => $m_name
						)) . domTag('div', array(
							'class' => 'actions',
							'content' => implode(' &bull; ', $actions)
						)), 'name'),
						// Slug
						tdCell($m_slug, 'slug'),
						// Item count
						tdCell($m_count, 'count')
					);
				}
				
				if(empty($menus))
					echo tableRow(tdCell('There are no menus to display.', '', count($header_cols)));
				?>
			</tbody>
			<tfoot>
				<?php echo tableHeaderRow($header_cols); ?>
			</tfoot>
		</table>
		<?php
		// Set up page navigation
		pagerNav($this->paged['current'], $this->paged['count']);
		
        includeFile(PATH . MODALS . '/modal-delete.php');
	}
	
	/**
	 * Create a new menu.
	 * @since 1.8.0-alpha
	 *
	 * @access public
	 */
	public function createRecord(): void {
		$this->pageHeading();
		?>
		<div class="data-form-wrap clear">
			<form class="data-form" action="" method="post" autocomplete="off">
				<div class="content">
					<?php
					// Name
					echo domTag('input', array(
						'id' => 'name-field',
						'class' => 'text-input required invalid init',
						'name' => 'name',
						'value' => ($_POST['name'] ?? ''),
						'placeholder' => 'Menu name',
						'autocomplete' => 'off'
					));
					
					// Slug
					echo domTag('input', array(
						'id' => 'slug-field',
						'class' => 'text-input required invalid init',
						'name' => 'slug',
						'value' => ($_POST['slug'] ?? ''),
						'placeholder' => 'Menu slug'
					));
					?>
				</div>
				<div class="sidebar">
					<div class="block">
						<?php
						echo domTag('h2', array(
							'content' => 'Add Menu Items'
						));
						?>
						<div class="row">
							<?php
							// Menu items sidebar
							$this->getMenuItemsSidebar();
							?>
						</div>
						<div id="submit" class="row">
							<?php
							// Submit button
							echo domTag('input', array(
								'type' => 'submit',
								'class' => 'submit-input button',
								'name' => 'submit',
								'value' => 'Create'
							));
							?>
						</div>
					</div>
				</div>
			</form>
			<div class="item-list-wrap">
				<?php
				// Menu items list
				echo $this->getMenuItems();
				?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Edit an existing menu.
	 * @since 1.8.0-alpha
	 *
	 * @access public
	 */
	public function editRecord(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0)
			redirect(ADMIN_URI);
		
		if(isset($_GET['item_id'])) {
			$item_id = (int)$_GET['item_id'];
			
			if(empty($item_id) || $item_id <= 0)
				redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit');
			
			$count = $rs_query->selectRow(array($this->tables[3], $this->px[3]), 'COUNT(*)', array(
				'id' => $item_id,
				'type' => $this->item_post_type
			));
			
			if($count === 0)
				redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit');
		}
		
		$this->pageHeading();
		?>
		<div class="data-form-wrap clear">
			<form class="data-form" action="" method="post" autocomplete="off">
				<div class="content">
					<?php
					// Name
					echo domTag('input', array(
						'id' => 'name-field',
						'class' => 'text-input required invalid init',
						'name' => 'name',
						'value' => $this->name,
						'placeholder' => 'Menu name',
						'autocomplete' => 'off'
					));
					
					// Slug
					echo domTag('input', array(
						'id' => 'slug-field',
						'class' => 'text-input required invalid init',
						'name' => 'slug',
						'value' => $this->slug,
						'placeholder' => 'Menu slug'
					));
					?>
				</div>
				<div class="sidebar">
					<div class="block">
						<?php
						echo domTag('h2', array(
							'content' => 'Add Menu Items'
						));
						?>
						<div class="row">
							<?php
							// Menu items sidebar
							$this->getMenuItemsSidebar();
							?>
						</div>
						<div id="submit" class="row">
							<?php
							// Submit button
							echo domTag('input', array(
								'type' => 'submit',
								'class' => 'submit-input button',
								'name' => 'submit',
								'value' => 'Update'
							));
							?>
						</div>
					</div>
				</div>
			</form>
			<div class="item-list-wrap">
				<?php
				// Menu items list
				echo $this->getMenuItems();
				?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Delete an existing menu.
	 * @since 1.8.1-alpha
	 *
	 * @access public
	 */
	public function deleteRecord(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0)
			redirect(ADMIN_URI);
		
		$rs_query->delete(array($this->tables[0], $this->px[0]), array(
			'id' => $this->id,
			'taxonomy' => $this->taxonomy
		));
		
		$relationships = $rs_query->select(array($this->tables[2], $this->px[2]), 'post', array(
			'term' => $this->id
		));
		
		$rs_query->delete(array($this->tables[2], $this->px[2]), array(
			'term' => $this->id
		));
		
		// Delete all menu items associated with the menu
		foreach($relationships as $relationship) {
			$rs_query->delete(array($this->tables[3], $this->px[3]), array(
				'id' => $relationship['tr_post']
			));
			
			$rs_query->delete(array($this->tables[4], $this->px[4]), array(
				'post' => $relationship['tr_post']
			));
		}
		
		redirect(ADMIN_URI . '?exit_status=del_success');
	}
	
	/**
	 * Create a menu item.
	 * @since 2.3.2-alpha
	 *
	 * @access private
	 * @param string $type -- The menu item's type.
	 * @param int $id -- The menu item's id.
	 * @param int $index -- The menu item's index.
	 * @return array
	 */
	private function createMenuItem(string $type, int $id, int $index): array {
		global $rs_query;
		
		if($type === 'post') {
			$post = $rs_query->selectRow(array($this->tables[3], $this->px[3]), array('id', 'title'), array(
				'id' => $id
			));
			
			$menu_item_id = $rs_query->insert(array($this->tables[3], $this->px[3]), array(
				'title' => $post['p_title'],
				'created' => 'NOW()',
				'modified' => 'NOW()',
				'content' => '',
				'slug' => '',
				'type' => $this->item_post_type
			));
			
			$rs_query->update(array($this->tables[3], $this->px[3]), array(
				'slug' => 'menu-item-' . $menu_item_id
			), array(
				'id' => $menu_item_id
			));
			
			return array(
				'id' => $menu_item_id,
				'post_link' => $post['p_id'],
				'menu_index' => $index
			);
		} elseif($type === 'term') {
			$term = $rs_query->selectRow(array($this->tables[0], $this->px[0]), array('id', 'name'), array(
				'id' => $id
			));
			
			$menu_item_id = $rs_query->insert(array($this->tables[3], $this->px[3]), array(
				'title' => $term[$this->px[0] . 'name'],
				'created' => 'NOW()',
				'modified' => 'NOW()',
				'content' => '',
				'slug' => '',
				'type' => $this->item_post_type
			));
			
			$rs_query->update(array($this->tables[3], $this->px[3]), array(
				'slug' => 'menu-item-' . $menu_item_id
			), array(
				'id' => $menu_item_id
			));
			
			return array(
				'id' => $menu_item_id,
				'term_link' => $term[$this->px[0] . 'id'],
				'menu_index' => $index
			);
		}
	}
	
	/**
	 * Edit a menu item.
	 * @since 1.8.1-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 */
	private function editMenuItem(int $id): void {
		global $rs_query;
		
		$message = isset($_POST['item_submit']) ? $this->validateMenuItemSubmission($_POST, $id) : '';
		
		$menu_item = $rs_query->selectRow(array($this->tables[3], $this->px[3]), '*', array(
			'id' => $id,
			'type' => $this->item_post_type
		));
		
		$meta = $this->getMenuItemMeta($id);
		
		$type = isset($meta['post_link']) ? 'post' : (isset($meta['term_link']) ? 'term' :
			(isset($meta['custom_link']) ? 'custom' : ''));
		
		echo domTag('hr', array(
			'class' => 'separator'
		));
		
		// Status messages
		echo $message;
		
		if(isset($_POST['item_submit']))
			echo '<meta http-equiv="refresh" content="0">';
		?>
		<form class="data-form" action="" method="post" autocomplete="off">
			<table class="form-table">
				<?php
				// Title
				echo formRow(array('Title', true), array(
					'tag' => 'input',
					'id' => 'title-field',
					'class' => 'text-input required invalid init',
					'name' => 'title',
					'value' => $menu_item['p_title']
				));
				
				// Link
				if($type === 'post') {
					echo formRow('Link to', array(
						'tag' => 'select',
						'id' => 'post-link-field',
						'class' => 'select-input',
						'name' => 'post_link',
						'content' => $this->getMenuItemsList((int)$meta['post_link'], $type)
					));
				} elseif($type === 'term') {
					echo formRow('Link to', array(
						'tag' => 'select',
						'id' => 'term-link-field',
						'class' => 'select-input',
						'name' => 'term_link',
						'content' => $this->getMenuItemsList((int)$meta['term_link'], $type)
					));
				} elseif($type === 'custom') {
					echo formRow('Link to', array(
						'tag' => 'input',
						'id' => 'custom-link-field',
						'class' => 'text-input',
						'name' => 'custom_link',
						'value' => $meta['custom_link']
					));
				}
				
				// Parent
				echo formRow('Parent', array(
					'tag' => 'select',
					'id' => 'parent-field',
					'class' => 'select-input',
					'name' => 'parent',
					'content' => domTag('option', array(
						'value' => '0',
						'content' => '(none)'
					)) . $this->getParentList($menu_item['p_parent'], $menu_item['p_id'])
				));
				
				// Separator
				echo formRow('', array(
					'tag' => 'hr',
					'class' => 'separator'
				));
				
				// Update and delete buttons
				echo formRow('', array(
					'tag' => 'input',
					'type' => 'submit',
					'class' => 'submit-input button',
					'name' => 'item_submit',
					'value' => 'Update'
				), array(
					'tag' => 'div',
					'class' => 'actions',
					'content' => domTag('a', array(
						'class' => 'button',
						'href' => ADMIN_URI . '?id=' . $this->id . '&action=edit&item_id=' . $menu_item['p_id'] .
							'&item_action=delete',
						'content' => 'Delete'
					))
				));
				?>
			</table>
		</form>
		<?php
	}
	
	/**
	 * Move a menu item one position up.
	 * @since 1.8.3-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 */
	private function moveUpMenuItem(int $id): void {
		global $rs_query;
		
		if($this->hasSiblings($id) && !$this->isFirstSibling($id)) {
			$current_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $id,
				'key' => 'menu_index'
			));
			
			$previous_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $this->getPreviousSibling($id),
				'key' => 'menu_index'
			));
			
			$rs_query->update(array($this->tables[4], $this->px[4]), array(
				'value' => $previous_index
			), array(
				'post' => $id,
				'key' => 'menu_index'
			));
			
			$relationships = $rs_query->select(array($this->tables[2], $this->px[2]), 'post', array(
				'term' => $this->id
			));
			
			foreach($relationships as $relationship) {
				$indexes[] = $rs_query->selectRow(array($this->tables[4], $this->px[4]), array('value', 'post'), array(
					'post' => $relationship['tr_post'],
					'key' => 'menu_index'
				));
			}
			
			// Sort the array in ascending index order
			asort($indexes);
			
			$i = 1;
			
			foreach($indexes as $index) {
				// Skip over any indexes that come after the current index (and its children) or before the previous index
				if($index['pm_post'] === $id ||
					(int)$index['pm_value'] >= ($current_index + $this->getFamilyTree($id)) ||
					(int)$index['pm_value'] < $previous_index) continue;
				
				// Update each menu item's index
				if($this->isDescendant($index['pm_post'], $id)) {
					$rs_query->update(array($this->tables[4], $this->px[4]), array(
						'value' => ($previous_index + $i)
					), array(
						'post' => $index['pm_post'],
						'key' => 'menu_index'
					));
					
					$i++;
				} else {
					// Update each menu item's index
					$rs_query->update(array($this->tables[4], $this->px[4]), array(
						'value' => (int)$index['pm_value'] + $this->getFamilyTree($id)
					), array(
						'post' => $index['pm_post'],
						'key' => 'menu_index'
					));
				}
			}
			
			redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&exit_status=move_success_item');
		}
		
		redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&exit_status=move_failure_item');
	}
	
	/**
	 * Move a menu item one position down.
	 * @since 1.8.3-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 */
	private function moveDownMenuItem(int $id): void {
		global $rs_query;
		
		if($this->hasSiblings($id) && !$this->isLastSibling($id)) {
			$next_sibling = $this->getNextSibling($id);
			
			$current_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $id,
				'key' => 'menu_index'
			));
			
			$next_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $next_sibling,
				'key' => 'menu_index'
			));
			
			$rs_query->update(array($this->tables[4], $this->px[4]), array(
				'value' => $current_index + $this->getFamilyTree($next_sibling)
			), array(
				'post' => $id,
				'key' => 'menu_index'
			));
			
			$relationships = $rs_query->select(array($this->tables[2], $this->px[2]), 'post', array(
				'term' => $this->id
			));
			
			foreach($relationships as $relationship) {
				$indexes[] = $rs_query->selectRow(array($this->tables[4], $this->px[4]), array('value', 'post'), array(
					'post' => $relationship['tr_post'],
					'key' => 'menu_index'
				));
			}
			
			// Sort the array in ascending index order
			asort($indexes);
			
			$i = 1;
			
			foreach($indexes as $index) {
				// Skip over any indexes that come before the current index or after the next index
				if($index['pm_post'] === $id ||
					(int)$index['pm_value'] < $current_index ||
					(int)$index['pm_value'] >= $next_index + $this->getFamilyTree($next_sibling)) continue;
				
				// Update each menu item's index
				if($this->isDescendant($index['pm_post'], $id)) {
					$rs_query->update(array($this->tables[4], $this->px[4]), array(
						'value' => $current_index + $this->getFamilyTree($this->getNextSibling($id)) + $i
					), array(
						'post' => $index['pm_post'],
						'key' => 'menu_index'
					));
					
					$i++;
				} else {
					// Update each menu item's index
					$rs_query->update(array($this->tables[4], $this->px[4]), array(
						'value' => (int)$index['pm_value'] - $this->getFamilyTree($id)
					), array(
						'post' => $index['pm_post'],
						'key' => 'menu_index'
					));
				}
			}
			
			redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&exit_status=move_success_item');
		}
		
		redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&exit_status=move_failure_item');
	}
	
	/**
	 * Delete a menu item.
	 * @since 1.8.1-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 */
	private function deleteMenuItem(int $id): void {
		global $rs_query;
		
		$parent = (int)$rs_query->selectField(array($this->tables[3], $this->px[3]), 'parent', array(
			'id' => $id
		));
		
		$rs_query->update(array($this->tables[3], $this->px[3]), array(
			'parent' => $parent
		), array(
			'parent' => $id
		));
		
		$count = $rs_query->select(array($this->tables[2], $this->px[2]), 'COUNT(*)', array(
			'term' => $this->id
		));
		
		$current_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
			'post' => $id,
			'key' => 'menu_index'
		));
		
		$rs_query->delete(array($this->tables[3], $this->px[3]), array(
			'id' => $id,
			'type' => $this->item_post_type
		));
		
		$rs_query->delete(array($this->tables[4], $this->px[4]), array(
			'post' => $id
		));
		
		$rs_query->delete(array($this->tables[2], $this->px[2]), array(
			'post' => $id
		));
		
		// Check whether the index is less than the last index
		if($current_index < $count - 1) {
			$relationships = $rs_query->select(array($this->tables[2], $this->px[2]), 'post', array(
				'term' => $this->id
			));
			
			foreach($relationships as $relationship) {
				$index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
					'post' => $relationship['tr_post'],
					'key' => 'menu_index'
				));
				
				if($index < $current_index) {
					continue;
				} else {
					$rs_query->update(array($this->tables[4], $this->px[4]), array(
						'value' => $index - 1
					), array(
						'post' => $relationship['tr_post'],
						'key' => 'menu_index'
					));
				}
			}
		}
		
		$count = $rs_query->select(array($this->tables[2], $this->px[2]), 'COUNT(*)', array(
			'term' => $this->id
		));
		
		$rs_query->update(array($this->tables[0], $this->px[0]), array(
			'count' => $count
		), array(
			'id' => $this->id
		));
		
		redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&exit_status=del_success_item');
	}
	
	/*------------------------------------*\
		VALIDATION
	\*------------------------------------*/
	
	/**
	 * Validate the menu form data.
	 * @since 1.8.0-alpha
	 *
	 * @access private
	 * @param array $data -- The submission data.
	 * @return string
	 */
	private function validateMenuSubmission(array $data): string {
		global $rs_query;
		
		if(empty($data['name']) || empty($data['slug'])) {
			return exitNotice('REQ', -1);
			exit;
		}
		
		$slug = sanitize($data['slug']);
		
		if($this->slugExists($slug))
			$slug = getUniqueTermSlug($slug);
		
		switch($this->action) {
			case 'create':
				$menu_id = $rs_query->insert(array($this->tables[0], $this->px[0]), array(
					'name' => $data['name'],
					'slug' => $slug,
					'taxonomy' => getTaxonomyId($this->tax_name)
				));
				
				if(!empty($data['menu_items'])) {
					$menu_items = $data['menu_items'];
					
					for($i = 0; $i < count($menu_items); $i++) {
						list($item_type, $item_id) = explode('-', $menu_items[$i]);
						
						$itemmeta = $this->createMenuItem($item_type, (int)$item_id, $i);
						$menu_item_id = array_shift($itemmeta);
						
						foreach($itemmeta as $key => $value) {
							$rs_query->insert(array($this->tables[4], $this->px[4]), array(
								'post' => $menu_item_id,
								'key' => $key,
								'value' => $value
							));
						}
						
						$rs_query->insert(array($this->tables[2], $this->px[2]), array(
							'term' => $menu_id,
							'post' => $menu_item_id
						));
					}
					
					$rs_query->update(array($this->tables[0], $this->px[0]), array(
						'count' => count($menu_items)
					), array(
						'id' => $menu_id
					));
				}
				
				if(!empty($data['custom_title']) && !empty($data['custom_link'])) {
					$menu_item_id = $rs_query->insert(array($this->tables[3], $this->px[3]), array(
						'title' => $data['custom_title'],
						'created' => 'NOW()',
						'modified' => 'NOW()',
						'content' => '',
						'slug' => '',
						'type' => $this->item_post_type
					));
					
					$rs_query->update(array($this->tables[3], $this->px[3]), array(
						'slug' => 'menu-item-' . $menu_item_id
					), array(
						'id' => $menu_item_id
					));
					
					$count = $rs_query->select(array($this->tables[2], $this->px[2]), 'COUNT(*)', array(
						'term' => $menu_id
					));
					
					$itemmeta = array(
						'custom_link' => $data['custom_link'],
						'menu_index' => $count
					);
					
					foreach($itemmeta as $key => $value) {
						$rs_query->insert(array($this->tables[4], $this->px[4]), array(
							'post' => $menu_item_id,
							'key' => $key,
							'value' => $value
						));
					}
					
					$rs_query->insert(array($this->tables[2], $this->px[2]), array(
						'term' => $menu_id,
						'post' => $menu_item_id
					));
					
					$rs_query->update(array($this->tables[0], $this->px[0]), array(
						'count' => $count + 1
					), array(
						'id' => $menu_id
					));
				}
				
				redirect(ADMIN_URI . '?id=' . $menu_id . '&action=edit&exit_status=create_success');
				break;
			case 'edit':
				$rs_query->update(array($this->tables[0], $this->px[0]), array(
					'name' => $data['name'],
					'slug' => $data['slug']
				), array(
					'id' => $this->id
				));
				
				if(!empty($data['menu_items'])) {
					$menu_items = $data['menu_items'];
					
					for($i = 0; $i < count($menu_items); $i++) {
						list($item_type, $item_id) = explode('-', $menu_items[$i]);
						
						$count = $rs_query->select(array($this->tables[2], $this->px[2]), 'COUNT(*)', array(
							'term' => $this->id
						));
						
						$itemmeta = $this->createMenuItem($item_type, (int)$item_id, $count);
						$menu_item_id = array_shift($itemmeta);
						
						foreach($itemmeta as $key => $value) {
							$rs_query->insert(array($this->tables[4], $this->px[4]), array(
								'post' => $menu_item_id,
								'key' => $key,
								'value' => $value
							));
						}
						
						$rs_query->insert(array($this->tables[2], $this->px[2]), array(
							'term' => $this->id,
							'post' => $menu_item_id
						));
					}
					
					$count = $rs_query->select(array($this->tables[2], $this->px[2]), 'COUNT(*)', array(
						'term' => $this->id
					));
					
					$rs_query->update(array($this->tables[0], $this->px[0]), array(
						'count' => $count
					), array(
						'id' => $this->id
					));
				}
				
				if(!empty($data['custom_title']) && !empty($data['custom_link'])) {
					$menu_item_id = $rs_query->insert(array($this->tables[3], $this->px[3]), array(
						'title' => $data['custom_title'],
						'created' => 'NOW()',
						'modified' => 'NOW()',
						'content' => '',
						'slug' => '',
						'type' => $this->item_post_type
					));
					
					$rs_query->update(array($this->tables[3], $this->px[3]), array(
						'slug' => 'menu-item-' . $menu_item_id
					), array(
						'id' => $menu_item_id
					));
					
					$count = $rs_query->select(array($this->tables[2], $this->px[2]), 'COUNT(*)', array(
						'term' => $this->id
					));
					
					$itemmeta = array(
						'custom_link' => $data['custom_link'],
						'menu_index' => $count
					);
					
					foreach($itemmeta as $key => $value) {
						$rs_query->insert(array($this->tables[4], $this->px[4]), array(
							'post' => $menu_item_id,
							'key' => $key,
							'value' => $value
						));
					}
					
					$rs_query->insert(array($this->tables[2], $this->px[2]), array(
						'term' => $this->id,
						'post' => $menu_item_id
					));
					
					$rs_query->update(array($this->tables[0], $this->px[0]), array(
						'count' => $count + 1
					), array(
						'id' => $this->id
					));
				}
				
				foreach($data as $key => $value) $this->$key = $value;
				
				redirect(ADMIN_URI . '?id=' . $this->id . '&action=' . $this->action . '&exit_status=edit_success');
				break;
		}
	}
	
	/**
	 * Validate the menu item form data.
	 * @since 1.8.1-alpha
	 *
	 * @access private
	 * @param array $data -- The submission data.
	 * @param int $id -- The menu item's id.
	 * @return string
	 */
	private function validateMenuItemSubmission(array $data, int $id): string {
		global $rs_query;
		
		if(empty($data['title'])) {
			return exitNotice('REQ', -1);
			exit;
		}
		
		$parent = (int)$rs_query->selectField(array($this->tables[3], $this->px[3]), 'parent', array(
			'id' => $id
		));
		
		$rs_query->update(array($this->tables[3], $this->px[3]), array(
			'title' => $data['title'],
			'modified' => 'NOW()',
			'parent' => $data['parent']
		), array(
			'id' => $id
		));
		
		if(!empty($data['post_link'])) {
			$rs_query->update(array($this->tables[4], $this->px[4]), array(
				'value' => $data['post_link']
			), array(
				'post' => $id,
				'key' => 'post_link'
			));
		} elseif(!empty($data['term_link'])) {
			$rs_query->update(array($this->tables[4], $this->px[4]), array(
				'value' => $data['term_link']
			), array(
				'post' => $id,
				'key' => 'term_link'
			));
		} elseif(!empty($data['custom_link'])) {
			$rs_query->update(array($this->tables[4], $this->px[4]), array(
				'value' => $data['custom_link']
			), array(
				'post' => $id,
				'key' => 'custom_link'
			));
		}
		
		$menu = $rs_query->selectField(array($this->tables[2], $this->px[2]), 'term', array(
			'post' => $id
		));
		
		$relationships = $rs_query->select(array($this->tables[2], $this->px[2]), 'post', array(
			'term' => $menu
		));
		
		$indexes = array();
		
		foreach($relationships as $relationship) {
			$indexes[] = $rs_query->selectRow(array($this->tables[4], $this->px[4]), array('post', 'value'), array(
				'post' => $relationship['tr_post'],
				'key' => 'menu_index'
			));
		}
		
		$current_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
			'post' => $id,
			'key' => 'menu_index'
		));
		
		if((int)$data['parent'] === 0 && $parent !== 0) {
			$count = $rs_query->select(array($this->tables[2], $this->px[2]), 'COUNT(*)', array(
				'term' => $menu
			));
			
			$rs_query->update(array($this->tables[4], $this->px[4]), array(
				'value' => $count - $this->getFamilyTree($id)
			), array(
				'post' => $id,
				'key' => 'menu_index'
			));
			
			$i = 1;
			
			foreach($indexes as $index) {
				// Skip over any indexes that come before the current index
				if((int)$index['pm_value'] <= $current_index) continue;
				
				// Check whether any menu items are children of the current menu item
				if($this->isDescendant($index['pm_post'], $id)) {
					$rs_query->update(array($this->tables[4], $this->px[4]), array(
						'value' => $count - $this->getFamilyTree($id) + $i
					), array(
						'post' => $index['pm_post'],
						'key' => 'menu_index'
					));
					
					$i++;
				} else {
					$rs_query->update(array($this->tables[4], $this->px[4]), array(
						'value' => (int)$index['pm_value'] - $this->getFamilyTree($id)
					), array(
						'post' => $index['pm_post'],
						'key' => 'menu_index'
					));
				}
			}
		} elseif((int)$data['parent'] !== 0) {
			$parent_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $data['parent'],
				'key' => 'menu_index'
			));
			
			if($current_index > $parent_index) {
				$rs_query->update(array($this->tables[4], $this->px[4]), array(
					'value' => $parent_index + 1
				), array(
					'post' => $id,
					'key' => 'menu_index'
				));
				
				$i = 1;
				
				foreach($indexes as $index) {
					// Skip over any indexes that come after the current index (and its children) or before the parent index
					if((int)$index['pm_value'] === $current_index ||
						(int)$index['pm_value'] >= ($current_index + $this->getFamilyTree($id)) ||
						(int)$index['pm_value'] <= $parent_index) continue;
					
					// Check whether any menu items are children of the current menu item
					if($this->isDescendant($index['pm_post'], $id)) {
						$rs_query->update(array($this->tables[4], $this->px[4]), array(
							'value' => $parent_index + 1 + $i
						), array(
							'post' => $index['pm_post'],
							'key' => 'menu_index'
						));
						
						$i++;
					} else {
						$rs_query->update(array($this->tables[4], $this->px[4]), array(
							'value' => (int)$index['pm_value'] + $this->getFamilyTree($id)
						), array(
							'post' => $index['pm_post'],
							'key' => 'menu_index'
						));
					}
				}
			} elseif($current_index < $parent_index) {
				// Determine the new index of the current menu item
				$new_index = $parent_index - $this->getFamilyTree($id) +
					$this->getFamilyTree((int)$data['parent']) - $this->getFamilyTree($id);
				
				$rs_query->update(array($this->tables[4], $this->px[4]), array(
					'value' => $new_index
				), array(
					'post' => $id,
					'key' => 'menu_index'
				));
				
				$i = 1;
				
				foreach($indexes as $index) {
					// Skip over any indexes that come before the current index or after the parent index
					if((int)$index['pm_value'] <= $current_index ||
						(int)$index['pm_value'] >= $parent_index + $this->getFamilyTree((int)$data['parent']) -
						$this->getFamilyTree($id)) continue;
					
					// Check whether any menu items are children of the current menu item
					if($this->isDescendant($index['pm_post'], $id)) {
						$rs_query->update(array($this->tables[4], $this->px[4]), array(
							'value' => $new_index + $i
						), array(
							'post' => $index['pm_post'],
							'key' => 'menu_index'
						));
						
						$i++;
					} else {
						$rs_query->update(array($this->tables[4], $this->px[4]), array(
							'value' => (int)$index['pm_value'] - $this->getFamilyTree($id)
						), array(
							'post' => $index['pm_post'],
							'key' => 'menu_index'
						));
					}
				}
			}
		}
		
		redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&item_id=' . $id .
			'&item_action=edit&exit_status=edit_success_item');
	}
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Construct the page heading.
	 * @since 1.3.14-beta
	 *
	 * @access public
	 */
	public function pageHeading(): void {
		global $rs_query;
		
		switch($this->action) {
			case 'create':
				$title = 'Create Menu';
				$message = isset($_POST['submit']) ? $this->validateMenuSubmission($_POST) : '';
				break;
			case 'edit':
				$title = 'Edit Menu: { ' . domTag('em', array(
					'content' => $this->name
				)) . ' }';
				$message = isset($_POST['submit']) ? $this->validateMenuSubmission($_POST) : '';
				break;
			default:
				$title = 'Menus';
				$search = $_GET['search'] ?? null;
		}
		?>
		<div class="heading-wrap">
			<?php
			// Page title
			echo domTag('h1', array(
				'content' => $title
			));
			
			if(!empty($this->action)) {
				// Status messages
				echo $message;
				
				// Exit notices
				if(isset($_GET['exit_status'])) {
					$exit = $_GET['exit_status'];
					
					if($exit === 'move_failure_item')
						$status_code = -1;
					
					if(isset($status_code))
						echo $this->exitNotice($exit, $status_code);
					else
						echo $this->exitNotice($exit);
				}
			} else {
				// Create button
				if(userHasPrivilege('can_create_menus')) {
					echo actionLink('create', array(
						'classes' => 'button',
						'caption' => 'Create New'
					));
				}
				
				// Search
				recordSearch();
				
				//Info
				adminInfo();
				
				echo domTag('hr');
				
				// Exit notices
				if(isset($_GET['exit_status']))
					echo $this->exitNotice($_GET['exit_status']);
				
				// Record count
				$count = $this->getEntryCount($search);
				
				echo domTag('div', array(
					'class' => 'entry-count',
					'content' => $count . ' ' . ($count === 1 ? 'entry' : 'entries')
				));
				
				$this->paged['count'] = ceil($count / $this->paged['per_page']);
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Generate an exit notice.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @param string $exit_status -- The exit status.
	 * @param int $status_code (optional) -- The type of notice to display.
	 * @return string
	 */
	private function exitNotice(string $exit_status, int $status_code = 1): string {
		return exitNotice(match($exit_status) {
			// Menus
			'create_success' => 'The menu was successfully created. ' . domTag('a', array(
				'href' => ADMIN_URI,
				'content' => 'Return to list'
			)) . '?',
			'edit_success' => 'Menu updated! ' . domTag('a', array(
				'href' => ADMIN_URI,
				'content' => 'Return to list'
			)) . '?',
			'del_success' => 'The menu was successfully deleted.',
			// Menu items
			'edit_success_item' => 'Menu item updated! ' . domTag('a', array(
				'href' => ADMIN_URI,
				'content' => 'Return to list'
			)) . '?',
			'move_success_item' => 'Menu item moved successfully. ' . domTag('a', array(
				'href' => ADMIN_URI,
				'content' => 'Return to list'
			)) . '?',
			'move_failure_item' => 'The menu item could not be moved.',
			'del_success_item' => 'The menu item was successfully deleted. ' . domTag('a', array(
				'href' => ADMIN_URI,
				'content' => 'Return to list'
			)) . '?',
			default => 'The action was completed successfully.'
		}, $status_code);
	}
	
	/**
	 * Check whether a slug exists in the database.
	 * @since 1.8.0-alpha
	 *
	 * @access private
	 * @param string $slug -- The menu's slug.
	 * @return bool
	 */
	private function slugExists(string $slug): bool {
		global $rs_query;
		
		if($this->id === 0) {
			return $rs_query->selectRow(array($this->tables[0], $this->px[0]), 'COUNT(slug)', array(
				'slug' => $slug
			)) > 0;
		} else {
			return $rs_query->selectRow(array($this->tables[0], $this->px[0]), 'COUNT(slug)', array(
				'slug' => $slug,
				'id' => array('<>', $this->id)
			)) > 0;
		}
	}
	
	/**
	 * Check whether a menu item is the first of its siblings.
	 * @since 1.8.12-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return bool
	 */
	private function isFirstSibling(int $id): bool {
		global $rs_query;
		
		$current_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
			'post' => $id,
			'key' => 'menu_index'
		));
		
		$siblings = $this->getSiblings($id);
		
		foreach($siblings as $sibling) {
			$index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $sibling,
				'key' => 'menu_index'
			));
			
			if($index < $current_index) return false;
		}
		
		return true;
	}
	
	/**
	 * Check whether a menu item is the last of its siblings.
	 * @since 1.8.12-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return bool
	 */
	private function isLastSibling(int $id): bool {
		global $rs_query;
		
		$current_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
			'post' => $id,
			'key' => 'menu_index'
		));
		
		$siblings = $this->getSiblings($id);
		
		foreach($siblings as $sibling) {
			$index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $sibling,
				'key' => 'menu_index'
			));
			
			if($index > $current_index) return false;
		}
		
		return true;
	}
	
	/**
	 * Check whether a menu item is the previous sibling of another menu item.
	 * @since 1.8.12-alpha
	 *
	 * @access private
	 * @param int $previous -- The previous menu item's id.
	 * @param int $id -- The menu item's id.
	 * @return bool
	 */
	private function isPreviousSibling(int $previous, int $id): bool {
		global $rs_query;
		
		$siblings = $this->getSiblings($id);
		
		if(in_array($previous, $siblings, true)) {
			$previous_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $previous,
				'key' => 'menu_index'
			));
			
			$current_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $id,
				'key' => 'menu_index'
			));
			
			if($previous_index < $current_index) {
				foreach($siblings as $sibling) {
					$index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
						'post' => $sibling,
						'key' => 'menu_index'
					));
					
					// Check whether the sibling's index falls in between the previous index and the current index
					if($index > $previous_index && $index < $current_index) return false;
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check whether a menu item is the next sibling of another menu item.
	 * @since 1.8.12-alpha
	 *
	 * @access private
	 * @param int $next -- The next menu item's id.
	 * @param int $id -- The menu item's id.
	 * @return bool
	 */
	private function isNextSibling(int $next, int $id): bool {
		global $rs_query;
		
		$siblings = $this->getSiblings($id);
		
		if(in_array($next, $siblings, true)) {
			$next_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $next,
				'key' => 'menu_index'
			));
			
			$current_index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $id,
				'key' => 'menu_index'
			));
			
			if($next_index > $current_index) {
				foreach($siblings as $sibling) {
					$index = (int)$rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
						'post' => $sibling,
						'key' => 'menu_index'
					));
					
					// Check whether the sibling's index falls in between the current index and the next index
					if($index > $current_index && $index < $next_index) return false;
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check whether a menu item is a descendant of another menu item.
	 * @since 1.8.6-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @param int $ancestor -- The menu item's ancestor.
	 * @return bool
	 */
	private function isDescendant(int $id, int $ancestor): bool {
		global $rs_query;
		
		do {
			$parent = $rs_query->selectField(array($this->tables[3], $this->px[3]), 'parent', array(
				'id' => $id
			));
			
			$id = (int)$parent;
			
			if($id === $ancestor) return true;
		} while($id !== 0);
		
		return false;
	}
	
	/**
	 * Check whether a menu item has siblings.
	 * @since 1.8.12-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return bool
	 */
	private function hasSiblings(int $id): bool {
		return count($this->getSiblings($id)) > 0;
	}
	
	/**
	 * Fetch a menu's items.
	 * @since 1.8.0-alpha
	 *
	 * @access private
	 */
	private function getMenuItems(): void {
		global $rs_query;
		?>
		<ul class="item-list">
			<?php
			$relationships = $rs_query->select(array($this->tables[2], $this->px[2]), 'post', array(
				'term' => $this->id
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
			uasort($itemmeta, function($a, $b) {
				return $a['menu_index'] > $b['menu_index'] ? 1 : -1;
			});
			
			foreach($itemmeta as $meta) {
				$menu_item = $rs_query->selectRow(array($this->tables[3], $this->px[3]), array(
					'id', 'title', 'status', 'parent'
				), array(
					'id' => $meta['post']
				));
				
				if(isset($meta['post_link'])) {
					$type = $rs_query->selectField(array($this->tables[3], $this->px[3]), 'type', array(
						'id' => $meta['post_link']
					));
				} elseif(isset($meta['term_link']))
					$type = 'term';
				elseif(isset($meta['custom_link']))
					$type = 'custom';
				?>
				<li class="menu-item depth-<?php echo $this->getMenuItemDepth($menu_item['p_id']) .
					($menu_item['p_status'] === 'invalid' ? ' invalid' : '');
				?>">
					<?php
					// Title
					echo domTag('strong', array(
						'content' => $menu_item['p_title']
					)) . ' &mdash; ' . domTag('small', array(
						'content' => domTag('em', array(
							'content' => empty($type) ? $menu_item['p_status'] : $type
						))
					));
					
					// Check whether the menu item's id is set
					if(isset($_GET['item_id']) && (int)$_GET['item_id'] === $menu_item['p_id']) {
						if(isset($_GET['item_action'])) {
							switch($_GET['item_action']) {
								case 'move_up':
									$this->moveUpMenuItem($menu_item['p_id']);
									break;
								case 'move_down':
									$this->moveDownMenuItem($menu_item['p_id']);
									break;
								case 'edit':
									// Cancel button
									echo domTag('div', array(
										'class' => 'actions',
										'content' => actionLink('edit', array(
											'caption' => 'Cancel',
											'id' => $this->id
										))
									));
									
									$this->editMenuItem($menu_item['p_id']);
									break;
								case 'delete':
									$this->deleteMenuItem($menu_item['p_id']);
									break;
							}
						}
					} else {
						// Action links
						echo domTag('div', array(
							'class' => 'actions',
							'content' => // Move up
								actionLink('edit', array(
									'caption' => '&uarr;',
									'id' => $this->id
								), array(
									'item_id' => $menu_item['p_id'],
									'item_action' => 'move_up'
								)) . ' &bull; ' .
								// Move down
								actionLink('edit', array(
									'caption' => '&darr;',
									'id' => $this->id
								), array(
									'item_id' => $menu_item['p_id'],
									'item_action' => 'move_down'
								)) . ' &bull; ' .
								// Edit
								actionLink('edit', array(
									'caption' => 'Edit',
									'id' => $this->id
								), array(
									'item_id' => $menu_item['p_id'],
									'item_action' => 'edit'
								))
						));
					}
					?>
				</li>
				<?php
			}
			
			if(empty($relationships)) {
				echo domTag('li', array(
					'class' => 'menu-item',
					'content' => 'This menu is empty!'
				));
			}
			?>
		</ul>
		<?php
	}
	
	/**
	 * Construct a sidebar of menu items.
	 * @since 1.8.0-alpha
	 *
	 * @access private
	 */
	private function getMenuItemsSidebar(): void {
		global $rs_query, $rs_post_types, $rs_taxonomies;
		
		$order_by = 'id';
		$order = 'DESC';
		
		foreach($rs_post_types as $post_type) {
			if(!$post_type['show_in_nav_menus']) continue;
			?>
			<fieldset>
				<legend><?php echo $post_type['label']; ?></legend>
				<ul class="checkbox-list">
					<?php
					$posts = $rs_query->select(array($this->tables[3], $this->px[3]), array('id', 'title'), array(
						'status' => 'published',
						'type' => $post_type['name']
					), array(
						'order_by' => $order_by,
						'order' => $order
					));
					
					foreach($posts as $post) {
						echo domTag('li', array(
							'content' => domTag('input', array(
								'type' => 'checkbox',
								'class' => 'checkbox-input',
								'name' => 'menu_items[]',
								'value' => 'post-' . $post['p_id'],
								'label' => array(
									'class' => 'checkbox-label',
									'content' => domTag('span', array(
										'title' => $post['p_title'],
										'content' => trimWords($post['p_title'], 5)
									))
								)
							))
						));
					}
					?>
				</ul>
			</fieldset>
			<?php
		}
		
		foreach($rs_taxonomies as $taxonomy) {
			if(!$taxonomy['show_in_nav_menus']) continue;
			?>
			<fieldset>
				<legend><?php echo $taxonomy['label']; ?></legend>
				<ul class="checkbox-list">
					<?php
					$terms = $rs_query->select(array($this->tables[0], $this->px[0]), array('id', 'name'), array(
						'taxonomy' => getTaxonomyId($taxonomy['name'])
					), array(
						'order_by' => $order_by,
						'order' => $order
					));
					
					foreach($terms as $term) {
						echo domTag('li', array(
							'content' => domTag('input', array(
								'type' => 'checkbox',
								'class' => 'checkbox-input',
								'name' => 'menu_items[]',
								'value' => 'term-' . $term[$this->px[0] . 'id'],
								'label' => array(
									'class' => 'checkbox-label',
									'content' => domTag('span', array(
										'title' => $term[$this->px[0] . 'name'],
										'content' => trimWords($term[$this->px[0] . 'name'], 5)
									))
								)
							))
						));
					}
					?>
				</ul>
			</fieldset>
			<?php
		}
		?>
		<fieldset>
			<legend>Custom</legend>
			<?php
			// Custom menu item title
			echo domTag('label', array(
				'for' => 'custom-title-field',
				'content' => 'Title'
			));
			
			echo domTag('input', array(
				'id' => 'custom-title-field',
				'class' => 'text-input',
				'name' => 'custom_title'
			));
			
			// Separator
			echo domTag('div', array(
				'class' => 'clear',
				'style' => 'height: 2px;'
			));
			
			// Custom menu item link
			echo domTag('label', array(
				'for' => 'custom-link-field',
				'content' => 'Link'
			));
			
			echo domTag('input', array(
				'id' => 'custom-link-field',
				'class' => 'text-input',
				'name' => 'custom_link'
			));
			?>
		</fieldset>
		<?php
	}
	
	/**
	 * Construct a list of menu items.
	 * @since 1.8.1-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @param string $type -- The menu item's type.
	 * @return string
	 */
	private function getMenuItemsList(int $id, string $type): string {
		global $rs_query;
		
		$list = '';
		
		if($type === 'post') {
			$post_type = $rs_query->selectField(array($this->tables[3], $this->px[3]), 'type', array(
				'id' => $id
			));
			
			$posts = $rs_query->select(array($this->tables[3], $this->px[3]), array('id', 'title'), array(
				'status' => 'published',
				'type' => $post_type
			));
			
			foreach($posts as $post) {
				$list .= domTag('option', array(
					'value' => $post['p_id'],
					'selected' => $post['p_id'] === $id,
					'content' => $post['p_title']
				));
			}
		} elseif($type === 'term') {
			$taxonomy = $rs_query->selectField(array($this->tables[0], $this->px[0]), 'taxonomy', array(
				'id' => $id
			));
			
			$terms = $rs_query->select(array($this->tables[0], $this->px[0]), array('id', 'name'), array(
				'taxonomy' => $taxonomy
			));
			
			foreach($terms as $term) {
				$list .= domTag('option', array(
					'value' => $term[$this->px[0] . 'id'],
					'selected' => $term[$this->px[0] . 'id'] === $id,
					'content' => $term[$this->px[0] . 'name']
				));
			}
		}
		
		return $list;
	}
	
	/**
	 * Determine a menu item's nested depth.
	 * @since 1.8.6-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return int
	 */
	private function getMenuItemDepth(int $id): int {
		global $rs_query;
		
		$depth = -1;
		
		do {
			$parent = $rs_query->selectField(array($this->tables[3], $this->px[3]), 'parent', array(
				'id' => $id
			));
			
			$id = (int)$parent;
			$depth++;
		} while($id !== 0);
		
		return $depth;
	}
	
	/**
	 * Fetch a menu item's metadata.
	 * @since 1.8.1-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return array
	 */
	private function getMenuItemMeta(int $id): array {
		global $rs_query;
		
		$itemmeta = $rs_query->select(array($this->tables[4], $this->px[4]), array('key', 'value'), array(
			'post' => $id
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
	 * Fetch a list of menu items related to a menu.
	 * @since 2.3.2-alpha
	 *
	 * @access private
	 * @param int $exclude -- The menu item to exclude.
	 * @return array
	 */
	private function getMenuRelationships(int $exclude): array {
		global $rs_query;
		
		$relationships = $rs_query->select(array($this->tables[2], $this->px[2]), 'post', array(
			'term' => $this->id
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
			if($meta['post'] === $exclude) continue;
			
			$items[] = $meta['post'];
		}
		
		return $items ?? array();
	}
	
	/**
	 * Fetch a menu item's parent.
	 * @since 1.8.12-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return int
	 */
	private function getParent(int $id): int {
		global $rs_query;
		
		return (int)$rs_query->selectField(array($this->tables[3], $this->px[3]), 'parent', array(
			'id' => $id
		));
	}
	
	/**
	 * Construct a list of parents.
	 * @since 1.8.6-alpha
	 *
	 * @access private
	 * @param int $parent -- The menu item's parent.
	 * @param int $id -- The menu item's id.
	 * @return string
	 */
	private function getParentList(int $parent, int $id): string {
		global $rs_query;
		
		$list = '';
		$menu_items = array();
		$i = 0;
		
		$menu = $rs_query->selectField(array($this->tables[2], $this->px[2]), 'term', array(
			'post' => $id
		));
		
		$relationships = $rs_query->select(array($this->tables[2], $this->px[2]), 'post', array(
			'term' => $menu
		));
		
		foreach($relationships as $relationship) {
			$menu_items[] = $rs_query->selectRow(array($this->tables[3], $this->px[3]), array('title', 'id'), array(
				'id' => $relationship['tr_post'],
				'type' => $this->item_post_type
			));
			
			$menu_items[$i]['menu_index'] = $rs_query->selectField(array($this->tables[4], $this->px[4]), 'value', array(
				'post' => $relationship['tr_post'],
				'key' => 'menu_index'
			));
			
			$menu_items[$i] = array_reverse($menu_items[$i]);
			$i++;
		}
		
		// Sort the array in ascending index order
		asort($menu_items);
		
		foreach($menu_items as $menu_item) {
			// Skip the current menu item
			if($menu_item['p_id'] === $id) continue;
			
			// Skip all descendant menu items
			if($this->isDescendant($menu_item['p_id'], $id)) continue;
			
			$list .= domTag('option', array(
				'value' => $menu_item['p_id'],
				'selected' => $menu_item['p_id'] === $parent,
				'content' => $menu_item['p_title']
			));
		}
		
		return $list;
	}
	
	/**
	 * Fetch all siblings of a menu item.
	 * @since 2.3.3-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return array
	 */
	private function getSiblings(int $id): array {
		global $rs_query;
		
		$menu_items = $this->getMenuRelationships($id);
		
		// Fetch any posts that share the same parent
		$posts = $rs_query->select(array($this->tables[3], $this->px[3]), 'id', array(
			'parent' => $this->getParent($id),
			'id' => array('<>', $id)
		));
		
		foreach($posts as $post)
			$same_parent[] = $post['p_id'];
		
		return isset($same_parent) ? array_intersect($menu_items, $same_parent) : array();
	}
	
	/**
	 * Fetch the previous sibling of a menu item.
	 * @since 1.8.12-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return int
	 */
	private function getPreviousSibling(int $id): int {
		global $rs_query;
		
		$siblings = $this->getSiblings($id);
		
		foreach($siblings as $sibling)
			if($this->isPreviousSibling($sibling, $id)) return $sibling;
	}
	
	/**
	 * Fetch the next sibling of a menu item.
	 * @since 1.8.12-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return int
	 */
	private function getNextSibling(int $id): int {
		global $rs_query;
		
		$siblings = $this->getSiblings($id);
		
		foreach($siblings as $sibling)
			if($this->isNextSibling($sibling, $id)) return $sibling;
	}
	
	/**
	 * Fetch the "family tree" of a menu item. Returns the number of members.
	 * @since 1.8.7-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 * @return int
	 */
	private function getFamilyTree(int $id): int {
		global $rs_query;
		
		$menu_item_id = $rs_query->selectField(array($this->tables[3], $this->px[3]), 'id', array(
			'id' => $id
		));
		
		if($menu_item_id) {
			$this->getDescendants($menu_item_id);
			$this->members++;
		}
		
		$members = $this->members;
		$this->members = 0;
		
		return $members;
	}
	
	/**
	 * Fetch all descendants of a menu item.
	 * @since 1.8.7-alpha
	 *
	 * @access private
	 * @param int $id -- The menu item's id.
	 */
	private function getDescendants(int $id): void {
		global $rs_query;
		
		$children = $rs_query->select(array($this->tables[3], $this->px[3]), 'id', array(
			'parent' => $id
		));
		
		foreach($children as $child) {
			$this->getDescendants($child['p_id']);
			$this->members++;
		}
	}
	
	/**
	 * Fetch all menus based on a specific status.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @param null|string $search -- The search query.
	 * @return array
	 */
	private function getResults(?string $search): array {
		global $rs_query;
		
		$order_by = 'name';
		$order = 'ASC';
		$limit = array($this->paged['start'], $this->paged['per_page']);
		
		if(!is_null($search)) {
			// Search results
			return $rs_query->select(array($this->tables[0], $this->px[0]), '*', array(
				'name' => array('LIKE', '%' . $search . '%'),
				'taxonomy' => getTaxonomyId($this->tax_name)
			), array(
				'order_by' => $order_by,
				'order' => $order,
				'limit' => $limit
			));
		} else {
			// All results
			return $rs_query->select(array($this->tables[0], $this->px[0]), '*', array(
				'taxonomy' => getTaxonomyId($this->tax_name)
			), array(
				'order_by' => $order_by,
				'order' => $order,
				'limit' => $limit
			));
		}
	}
	
	/**
	 * Fetch the menu count based on a specific status.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @param null|string $search -- The search query.
	 * @return int
	 */
	private function getEntryCount(?string $search): int {
		return count($this->getResults($search));
	}
}