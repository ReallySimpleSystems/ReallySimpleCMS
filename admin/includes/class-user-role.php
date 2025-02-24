<?php
/**
 * Admin class used to implement the UserRole object. Inherits from the Settings class.
 * @since 1.1.1-beta
 *
 * @package ReallySimpleCMS
 *
 * User roles allow privileged users to perform actions throughout the CMS.
 * User roles can be created, modified, and deleted.
 *
 * ## VARIABLES ##
 * - private int $id
 * - private string $name
 * - private int $is_default
 * - private string $action
 * - private array $paged
 * - private string $page
 * - private string $table
 * - private string $px
 *
 * ## METHODS ##
 * - public __construct(int $id, string $action, string $page)
 * LISTS, FORMS, & ACTIONS:
 * - public listRecords(): void
 * - public createRecord(): void
 * - public editRecord(): void
 * - public deleteRecord(): void
 * VALIDATION:
 * - private validateSubmission(array $data): string
 * MISCELLANEOUS:
 * - public pageHeading(): void
 * - private exitNotice(string $exit_status, int $status_code): string
 * - private roleNameExists(string $name): bool
 * - private getPrivileges(int $id, int $is_default): string
 * - private getPrivilegesList(): string
 * - private getUserRoleCount(string $search): int
 */
class UserRole implements AdminInterface {
	/**
	 * The currently queried user role's id.
	 * @since 1.1.1-beta
	 *
	 * @access private
	 * @var int
	 */
	private $id;
	
	/**
	 * The currently queried user role's name.
	 * @since 1.1.1-beta
	 *
	 * @access private
	 * @var string
	 */
	private $name;
	
	/**
	 * The currently queried user role's status (default or not).
	 * @since 1.1.1-beta
	 *
	 * @access private
	 * @var string
	 */
	private $is_default;
	
	/**
	 * The current action.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var string
	 */
	private $action;
	
	/**
	 * The pagination.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var array
	 */
	private $paged = array();
	
	/**
	 * The current settings page.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var string
	 */
	private $page;
	
	/**
	 * The associated database table.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var string
	 */
	private $table = 'user_roles';
	
	/**
	 * The table prefix.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var string
	 */
	private $px = 'ur_';
	
	/**
	 * Class constructor.
	 * @since 1.1.1-beta
	 *
	 * @access public
	 * @param int $id -- The role's id.
	 * @param string $action -- The current action.
	 * @param string $page -- The current settings page.
	 */
	public function __construct(int $id, string $action, string $page) {
		global $rs_query;
		
		$this->action = $action;
		$this->page = $page;
		
		if($id !== 0) {
			$cols = array_keys(get_object_vars($this));
			$exclude = array('action', 'paged', 'page', 'table', 'px');
			$cols = array_diff($cols, $exclude);
			
			$role = $rs_query->selectRow($this->table, $cols, array(
				'id' => $id
			));
			
			foreach($role as $key => $value) $this->$key = $role[$key];
		} else {
			$this->id = 0;
		}
	}
	
	/*------------------------------------*\
		LISTS, FORMS, & ACTIONS
	\*------------------------------------*/
	
	/**
	 * Construct a list of all user roles in the database.
	 * @since 1.7.1-alpha
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
					'default-privileges' => 'Default Privileges',
					'custom-privileges' => 'Custom Privileges'
				);
				
				echo tableHeaderRow($header_cols);
				?>
			</thead>
			<tbody>
				<?php
				$order_by = 'id';
				$order = 'ASC';
				$limit = array($this->paged['start'], $this->paged['per_page']);
				
				if(!is_null($search)) {
					// Search results
					$roles = $rs_query->select($this->table, '*', array(
						'name' => array('LIKE', '%' . $search . '%'),
						'is_default' => 0
					), array(
						'order_by' => $order_by,
						'order' => $order,
						'limit' => $limit
					));
				} else {
					// All results
					$roles = $rs_query->select($this->table, '*', array(
						'is_default' => 0
					), array(
						'order_by' => $order_by,
						'order' => $order,
						'limit' => $limit
					));
				}
				
				foreach($roles as $role) {
					list($ur_id, $ur_name) = array(
						$role['id'],
						$role['name']
					);
					
					$actions = array(
						// Edit
						userHasPrivilege('can_edit_user_roles') ? actionLink('edit', array(
							'caption' => 'Edit',
							'page' => $this->page,
							'id' => $ur_id
						)) : null,
						// Delete
						userHasPrivilege('can_delete_user_roles') ? actionLink('delete', array(
							'classes' => 'modal-launch delete-item',
							'data_item' => 'user role',
							'caption' => 'Delete',
							'page' => $this->page,
							'id' => $ur_id
						)) : null
					);
					
					// Filter out any empty actions
					$actions = array_filter($actions);
					
					echo tableRow(
						// Name
						tdCell(domTag('strong', array(
							'content' => $ur_name
						)) . domTag('div', array(
							'class' => 'actions',
							'content' => implode(' &bull; ', $actions)
						)), 'name'),
						// Default Privileges
						tdCell($this->getPrivileges($ur_id, 1), 'default-privileges'),
						// Custom Privileges
						tdCell($this->getPrivileges($ur_id, 0), 'custom-privileges')
					);
				}
				
				if(empty($roles)) {
					echo tableRow(tdCell('There are no user roles to display.', '',
						count($header_cols)
					));
				}
				?>
			</tbody>
			<tfoot>
				<?php echo tableHeaderRow($header_cols); ?>
			</tfoot>
		</table>
		<?php
		// Set up page navigation
		echo pagerNav($this->paged['current'], $this->paged['count']);
		?>
		<h2 class="subheading">Default User Roles</h2>
		<table class="data-table">
			<thead>
				<?php echo tableHeaderRow($header_cols); ?>
			</thead>
			<tbody>
				<?php
				$roles = $rs_query->select($this->table, '*', array(
					'is_default' => 1
				), array(
					'order_by' => 'id'
				));
				
				foreach($roles as $role) {
					list($ur_id, $ur_name) = array(
						$role['id'],
						$role['name']
					);
					
					echo tableRow(
						// Name
						tdCell(domTag('strong', array(
							'content' => $ur_name
						)) . domTag('div', array(
							'class' => 'actions',
							'content' => domTag('em', array(
								'content' => 'default roles cannot be modified'
							))
						)), 'name'),
						// Default Privileges
						tdCell($this->getPrivileges($ur_id, 1), 'default-privileges'),
						// Custom Privileges
						tdCell($this->getPrivileges($ur_id, 0), 'custom-privileges')
					);
				}
				
				if(empty($roles)) {
					echo tableRow(tdCell('There are no user roles to display.', '',
						count($header_cols)
					));
				}
				?>
			</tbody>
			<tfoot>
				<?php echo tableHeaderRow($header_cols); ?>
			</tfoot>
		</table>
		<?php
		include_once PATH . ADMIN . INC . '/modal-delete.php';
	}
	
	/**
	 * Create a new user role.
	 * @since 1.7.2-alpha
	 *
	 * @access public
	 */
	public function createRecord(): void {
		$this->pageHeading();
		?>
		<div class="data-form-wrap clear">
			<form class="data-form" action="" method="post" autocomplete="off">
				<table class="form-table">
					<?php
					// Name
					echo formRow(array('Name', true), array(
						'tag' => 'input',
						'id' => 'name-field',
						'class' => 'text-input required invalid init',
						'name' => 'name',
						'value' => ($_POST['name'] ?? ''),
						'autocomplete' => 'off'
					));
					
					// Privileges
					echo formRow('Privileges', $this->getPrivilegesList());
					
					// Separator
					echo formRow('', array(
						'tag' => 'hr',
						'class' => 'separator'
					));
					
					// Submit button
					echo formRow('', array(
						'tag' => 'input',
						'type' => 'submit',
						'class' => 'submit-input button',
						'name' => 'submit',
						'value' => 'Create User Role'
					));
					?>
				</table>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Edit an existing user role.
	 * @since 1.7.2-alpha
	 *
	 * @access public
	 */
	public function editRecord(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0)
			redirect(ADMIN_URI . '?page=' . $this->page);
		
		if($this->is_default === 'yes')
			redirect(ADMIN_URI . '?page=' . $this->page);
		
		$this->pageHeading();
		?>
		<div class="data-form-wrap clear">
			<form class="data-form" action="" method="post" autocomplete="off">
				<table class="form-table">
					<?php
					// Name
					echo formRow(array('Name', true), array(
						'tag' => 'input',
						'class' => 'text-input required invalid init',
						'name' => 'name',
						'value' => $this->name
					));
					
					// Privileges
					echo formRow('Privileges', $this->getPrivilegesList());
					
					// Separator
					echo formRow('', array(
						'tag' => 'hr',
						'class' => 'separator'
					));
					
					// Submit button
					echo formRow('', array(
						'tag' => 'input',
						'type' => 'submit',
						'class' => 'submit-input button',
						'name' => 'submit',
						'value' => 'Update User Role'
					));
					?>
				</table>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Delete an existing user role.
	 * @since 1.7.2-alpha
	 *
	 * @access public
	 */
	public function deleteRecord(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0)
			redirect(ADMIN_URI . '?page=' . $this->page);
		
		if($this->is_default === 'yes')
			redirect(ADMIN_URI . '?page=' . $this->page);
		
		$rs_query->delete($this->table, array(
			'id' => $this->id
		));
		
		$rs_query->delete('user_relationships', array(
			'role' => $this->id
		));
		
		redirect(ADMIN_URI . '?page=' . $this->page . '&exit_status=del_success');
	}
	
	/*------------------------------------*\
		VALIDATION
	\*------------------------------------*/
	
	/**
	 * Validate the user role form data.
	 * @since 1.7.2-alpha
	 *
	 * @access private
	 * @param array $data -- The submission data.
	 * @return string
	 */
	private function validateSubmission(array $data): string {
		global $rs_query;
		
		if(empty($data['name'])) {
			return exitNotice('REQ', -1);
			exit;
		}
		
		if($this->roleNameExists($data['name'])) {
			return exitNotice('That name is already in use. Please choose another one.', -1);
			exit;
		}
		
		switch($this->action) {
			case 'create':
				$insert_id = $rs_query->insert($this->table, array(
					'name' => $data['name']
				));
				
				if(!empty($data['privileges'])) {
					foreach($data['privileges'] as $privilege) {
						$rs_query->insert('user_relationships', array(
							'role' => $insert_id,
							'privilege' => $privilege
						));
					}
				}
				
				redirect(ADMIN_URI . '?page=' . $this->page . '&id=' . $insert_id . '&action=edit&exit_status=create_success');
				break;
			case 'edit':
				$rs_query->update($this->table, array(
					'name' => $data['name']
				), array(
					'id' => $this->id
				));
				
				$relationships = $rs_query->select('user_relationships', '*', array(
					'role' => $this->id
				));
				
				foreach($relationships as $relationship) {
					// Delete any unused relationships
					if(empty($data['privileges']) || !in_array($relationship['privilege'], $data['privileges'], true)) {
						$rs_query->delete('user_relationships', array(
							'id' => $relationship['id']
						));
					}
				}
				
				if(!empty($data['privileges'])) {
					foreach($data['privileges'] as $privilege) {
						$relationship = $rs_query->selectRow('user_relationships', 'COUNT(*)', array(
							'role' => $this->id,
							'privilege' => $privilege
						));
						
						if($relationship) {
							continue;
						} else {
							$rs_query->insert('user_relationships', array(
								'role' => $this->id,
								'privilege' => $privilege
							));
						}
					}
				}
				
				foreach($data as $key => $value) $this->$key = $value;
				
				redirect(ADMIN_URI . '?page=' . $this->page . '&id=' . $this->id . '&action=' . $this->action .
					'&exit_status=edit_success');
				break;
		}
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
				$title = 'Create User Role';
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			case 'edit':
				$title = 'Edit User Role: { ' . domTag('em', array(
					'content' => $this->name
				)) . ' }';
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			default:
				$title = 'User Roles';
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
				if(isset($_GET['exit_status']))
					echo $this->exitNotice($_GET['exit_status']);
			} else {
				// Create button
				if(userHasPrivilege('can_create_user_roles')) {
					echo actionLink('create', array(
						'classes' => 'button',
						'caption' => 'Create New',
						'page' => $this->page
					));
				}
				
				// Search
				recordSearch(array(
					'page' => $this->page
				));
				
				//Info
				adminInfo();
				
				echo domTag('hr');
				
				// Exit notices
				if(isset($_GET['exit_status']))
					echo $this->exitNotice($_GET['exit_status']);
				
				// Record count
				if(!is_null($search))
					$count = $this->getUserRoleCount($search);
				else
					$count = $this->getUserRoleCount();
				
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
	 * @param string $exit_status -- The exit status.
	 * @param int $status_code (optional) -- The type of notice to display.
	 * @return string
	 */
	private function exitNotice(string $exit_status, int $status_code = 1): string {
		return exitNotice(match($exit_status) {
			'create_success' => 'The user role was successfully created. ' . domTag('a', array(
				'href' => ADMIN_URI . '?page=' . $this->page,
				'content' => 'Return to list'
			)) . '?',
			'edit_success' => 'User role updated! ' . domTag('a', array(
				'href' => ADMIN_URI . '?page=' . $this->page,
				'content' => 'Return to list'
			)) . '?',
			'del_success' => 'The user role was successfully deleted.',
			default => 'The action was completed successfully.'
		}, $status_code);
	}
	
	/**
	 * Check whether a user role name exists in the database.
	 * @since 1.7.3-alpha
	 *
	 * @access private
	 * @param string $name -- The role's name.
	 * @return bool
	 */
	private function roleNameExists(string $name): bool {
		global $rs_query;
		
		if($this->id === 0) {
			return $rs_query->selectRow($this->table, 'COUNT(name)', array(
				'name' => $name
			)) > 0;
		} else {
			return $rs_query->selectRow($this->table, 'COUNT(name)', array(
				'name' => $name,
				'id' => array('<>', $this->id)
			)) > 0;
		}
	}
	
	/**
	 * Fetch a user role's privileges.
	 * @since 1.7.2-alpha
	 *
	 * @access private
	 * @param int $id -- The role's id.
	 * @param int $is_default -- Whether the privilege is a default one (accepts 0 and 1 instead of false/true).
	 * @return string
	 */
	private function getPrivileges(int $id, int $is_default): string {
		global $rs_query;
		
		$privileges = array();
		
		$relationships = $rs_query->select('user_relationships', 'privilege', array(
			'role' => $id
		), array(
			'order_by' => 'privilege'
		));
		
		foreach($relationships as $relationship) {
			$privileges[] = $rs_query->selectField('user_privileges', 'name', array(
				'id' => $relationship['privilege'],
				'is_default' => $is_default
			));
		}
		
		$privileges = array_filter($privileges);
		
		return empty($privileges) ? '&mdash;' : implode(', ', $privileges);
	}
	
	/**
	 * Construct a list of user privileges.
	 * @since 1.7.2-alpha
	 *
	 * @access private
	 * @return string
	 */
	private function getPrivilegesList(): string {
		global $rs_query;
		
		$list = '<ul class="checkbox-list">';
		
		$privileges = $rs_query->select('user_privileges', '*', array(), array(
			'order_by' => 'id'
		));
		
		$list .= domTag('li', array(
			'content' => domTag('input', array(
				'type' => 'checkbox',
				'id' => 'select-all',
				'class' => 'checkbox-input',
				'label' => array(
					'content' => domTag('span', array(
						'content' => 'SELECT ALL'
					))
				)
			))
		));
		
		foreach($privileges as $privilege) {
			$relationship = $rs_query->selectRow('user_relationships', 'COUNT(*)', array(
				'role' => $this->id,
				'privilege' => $privilege['id']
			));
			
			$list .= domTag('li', array(
				'content' => domTag('input', array(
					'type' => 'checkbox',
					'class' => 'checkbox-input',
					'name' => 'privileges[]',
					'value' => $privilege['id'],
					'checked' => $relationship,
					'label' => array(
						'content' => domTag('span', array(
							'content' => $privilege['name']
						))
					)
				))
			));
		}
		
		$list .= '</ul>';
		
		return $list;
	}
	
	/**
	 * Fetch the user role count.
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @param string $search (optional) -- The search query.
	 * @return int
	 */
	private function getUserRoleCount(string $search = ''): int {
		global $rs_query;
		
		if(!empty($search)) {
			return $rs_query->select($this->table, 'COUNT(*)', array(
				'name' => array('LIKE', '%' . $search . '%'),
				'is_default' => 0
			));
		} else {
			return $rs_query->select($this->table, 'COUNT(*)', array(
				'is_default' => 0
			));
		}
	}
}