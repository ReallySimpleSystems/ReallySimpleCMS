<?php
/**
 * Admin class used to implement the Module object.
 * Modules can bee seen as extensions of core functionality. They are counted as external libraries, and their code exists semi-independent of core code, utilizing it to run their own code.
 * @since 1.4.0-beta_snap-03
 *
 * @package ReallySimpleCMS
 * @subpackage Admin
 *
 * ## VARIABLES [4] ##
 * - private string $name
 * - private array $mod_data
 * - private string $action
 * - private array $paged
 *
 * ## METHODS [9] ##
 * - public __construct(string $name, string $action, array $mod_data)
 * LISTS, FORMS, & ACTIONS:
 * - public listRecords(): void
 * - public 
 * - public updateModule(): void
 * - public 
 * VALIDATION:
 * - ## private validateSubmission(array $data): string
 * MISCELLANEOUS:
 * - public pageHeading(): void
 * - private exitNotice(string $exit_status, int $status_code): string
 * - private bulkActions(): void
 * - private isActive(string $name): bool
 * - private getResults(string $status, ?string $search): array
 * - private getEntryCount(string $status, ?string $search): int
 */
namespace Admin;

class Module {
	/**
	 * The module's name.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @var string
	 */
	private $name;
	
	/**
	 * The module's data.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @var array
	 */
	private $mod_data = array();
	
	/**
	 * The current action.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @var string
	 */
	private $action;
	
	/**
	 * The pagination.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @var array
	 */
	private $paged = array();
	
	/**
	 * Class constructor.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 * @param string $name -- The module's name.
	 * @param string $action -- The current action.
	 * @param array $mod_data (optional) -- The module data.
	 */
	public function __construct(string $name, string $action, array $mod_data = array()) {
		$this->name = $name;
		$this->action = $action;
		$this->mod_data = $mod_data;
	}
	
	/*------------------------------------*\
		LISTS, FORMS, & ACTIONS
	\*------------------------------------*/
	
	/**
	 * Construct a list of all modules.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 */
	public function listRecords(): void {
		global $rs_update;
		
		// Query vars
		$status = $_GET['status'] ?? 'all';
		$search = $_GET['search'] ?? null;
		$this->paged = paginate((int)($_GET['paged'] ?? 1));
		
		$this->pageHeading();
		?>
		<table class="data-table has-bulk-select">
			<thead>
				<?php
				$header_cols = array(
					'bulk-select' => domTag('input', array(
						'type' => 'checkbox',
						'class' => 'checkbox bulk-selector'
					)),
					'name' => 'Name',
					'author' => 'Author',
					'version' => 'Version',
					'description' => 'Description'
				);
				
				echo tableHeaderRow($header_cols);
				?>
			</thead>
			<tbody>
				<?php
				$modules = $this->getResults($status, $search);
				
				foreach($modules as $module) {
					// Action links
					$actions = array(
						// Activate/deactivate
						$this->isActive($module['name']) ? actionLink('deactivate', array(
							'caption' => 'Deactivate',
							'name' => $module['name']
						)) : actionLink('activate', array(
							'caption' => 'Activate',
							'name' => $module['name']
						)),
						// Delete
						!$this->isActive($module['name']) ? actionLink('delete', array(
							'classes' => 'modal-launch delete-item',
							'data_item' => 'module',
							'caption' => 'Delete',
							'name' => $module['name']
						)) : null
					);
					
					// Filter out any empty actions
					$actions = array_filter($actions);
					
					echo tableRow(
						// Bulk select
						tdCell(domTag('input', array(
							'type' => 'checkbox',
							'class' => 'checkbox',
							'value' => $module['name']
						)), 'bulk-select'),
						// Name
						tdCell(domTag('strong', array(
							'content' => $module['label']
						)) . (!$this->isActive($module['name']) ? ' &mdash; ' . domTag('em', array(
								'content' => 'inactive'
							)) : '') . domTag('br') . domTag('div', array(
							'class' => 'actions',
							'content' => !$module['is_required'] ? implode(' &bull; ', $actions) : domTag('em', array(
								'content' => 'required modules cannot be modified'
							))
						)), 'name'),
						// Author
						tdCell(domTag('a', array(
							'href' => $module['author']['url'],
							'content' => $module['author']['name'],
							'target' => '_blank',
							'rel' => 'noreferrer noopener'
						)), 'author'),
						// Version
						tdCell($module['version'] . ($rs_update->isUpdateAvailable($module['name'], $module['version']) ?
							' (' . domTag('a', array(
								'href' => ADMIN_URI . getQueryString(array(
									'name' => $module['name'],
									'action' => 'update',
								)),
								'content' => 'update'
							)) . ')' : ''), 'version'),
						// Description
						tdCell(!empty($module['description']) ? $module['description'] : '&mdash;', 'description'),
					);
				}
				
				if(empty($modules))
					echo tableRow(tdCell('There are no modules to display.', '', count($header_cols)));
				?>
			</tbody>
			<tfoot>
				<?php echo tableHeaderRow($header_cols); ?>
			</tfoot>
		</table>
		<?php
		// Bulk actions
		if(!empty($modules)) $this->bulkActions();
		
		// Set up page navigation
		echo pagerNav($this->paged['current'], $this->paged['count']);
		
        includeFile(PATH . MODALS . '/modal-delete.php');
	}
	
	/**
	 * Update a module.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 */
	public function updateModule(): void {
		global $rs_update;
		?>
		<div class="data-form-wrap clear">
			<?php
			echo domTag('p', array(
				'content' => 'Updating ' . domTag('strong', array(
					'content' => $this->mod_data['label']
				)) . ' from ' . domTag('strong', array(
					'content' => $this->mod_data['version']
				)) . ' to ' . domTag('strong', array(
					'content' => $rs_update->getCurrentVersion($this->mod_data['name'])
				)) . ':'
			));
			
			echo $rs_update->updateModule($this->mod_data['name']);
			?>
		</div>
		<?php
	}
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Construct the page heading.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access public
	 */
	public function pageHeading(): void {
		switch($this->action) {
			case 'install':
				$title = 'Install Module';
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			default:
				$title = 'Modules';
				$status = $_GET['status'] ?? 'all';
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
				// Install button
				if(userHasPrivilege('can_create_themes')) {
					echo actionLink('install', array(
						'classes' => 'button',
						'caption' => 'Install New'
					));
				}
				
				// Search
				recordSearch(array(
					'status' => $status
				));
				
				//Info
				adminInfo();
				
				echo domTag('hr');
				
				// Exit notices
				if(isset($_GET['exit_status'])) {
					$exit = $_GET['exit_status'];
					
					if($exit === 'activate_failure' || $exit === 'del_failure')
						$status_code = -1;
					
					if(isset($status_code))
						echo $this->exitNotice($exit, $status_code);
					else
						echo $this->exitNotice($exit);
				}
				?>
				<ul class="status-nav">
					<?php
					$keys = array('all', 'active', 'inactive', 'required', 'update');
					$count = array();
					
					foreach($keys as $key)
						$count[$key] = $this->getEntryCount($key, $search);
					
					// Statuses
					foreach($count as $key => $value) {
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => ADMIN_URI . ($key === 'all' ? '' : '?status=' . $key),
								'content' => ucfirst($key) . ' ' . domTag('span', array(
									'class' => 'count',
									'content' => '(' . $value . ')'
								))
							))
						));
						
						if($key !== array_key_last($count)) echo ' &bull; ';
					}
					?>
				</ul>
				<?php
				// Record count
				echo domTag('div', array(
					'class' => 'entry-count status',
					'content' => $count[$status] . ' ' . ($count[$status] === 1 ? 'entry' : 'entries')
				));
				
				$this->paged['count'] = ceil($count[$status] / $this->paged['per_page']);
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Generate an exit notice.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @param string $exit_status -- The exit status.
	 * @param int $status_code (optional) -- The type of notice to display.
	 * @return string
	 */
	private function exitNotice(string $exit_status, int $status_code = 1): string {
		return exitNotice(match($exit_status) {
			'install_success' => 'The module was successfully installed.',
			'activate_success' => 'The module was successfully activated.',
			'activate_failure' => 'The module could not be activated.',
			'del_success' => 'The module was successfully deleted.',
			'del_failure' => 'The module could not be deleted.',
			default => 'The action was completed successfully.'
		}, $status_code);
	}
	
	/**
	 * Construct bulk actions.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 */
	private function bulkActions(): void {
		global $rs_modules;
		?>
		<div class="bulk-actions">
			<?php
			#if(userHasPrivilege('can_edit_modules')) {
				$statuses = array('active', 'inactive');
				$list = array();
				
				foreach($statuses as $status) {
					$list[] = domTag('option', array(
						'value' => $status,
						'content' => ucfirst($status)
					));
				}
				
				echo domTag('select', array(
					'class' => 'actions',
					'content' => implode('', $list)
				));
				
				// Update status
				button(array(
					'class' => 'bulk-update',
					'title' => 'Bulk status update',
					'label' => 'Update'
				));
			#}
			
			#if(userHasPrivilege('can_delete_modules')) {
				// Delete
				button(array(
					'class' => 'bulk-delete',
					'title' => 'Bulk delete',
					'label' => 'Delete'
				));
			#}
			?>
		</div>
		<?php
	}
	
	/**
	 * Check whether a specified module is active.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @param string $name -- The module's name.
	 * @return bool
	 */
	private function isActive(string $name): bool {
		$active_modules = getSetting('active_modules');
		
		if(!empty($active_modules)) {
			$active_modules = unserialize($active_modules);
			
			return in_array($name, $active_modules, true);
		}
		
		return false;
	}
	
	/**
	 * Fetch all modules based on a specific status.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @param string $status -- The module's status.
	 * @param null|string $search -- The search query.
	 * @return array
	 */
	private function getResults(string $status, ?string $search): array {
		global $rs_modules;
		
		$modules = array();
		
		if(!is_null($search)) {
			// Search results
			switch($status) {
				case 'active':
					foreach($rs_modules as $module) {
						if($this->isActive($module['name']) && str_contains(strtolower($module['label']), $search))
							$modules[] = $module;
					}
					break;
				case 'inactive':
					foreach($rs_modules as $module) {
						if(!$this->isActive($module['name']) && str_contains(strtolower($module['label']), $search))
							$modules[] = $module;
					}
					break;
				case 'required':
					foreach($rs_modules as $module) {
						if($module['is_required'] === true && str_contains(strtolower($module['label']), $search))
							$modules[] = $module;
					}
					break;
				case 'update':
					break;
				default:
					foreach($rs_modules as $module) {
						if(str_contains(strtolower($module['label']), $search))
							$modules[] = $module;
					}
			}
		} else {
			// All results
			switch($status) {
				case 'active':
					foreach($rs_modules as $module) {
						if($this->isActive($module['name']))
							$modules[] = $module;
					}
					break;
				case 'inactive':
					foreach($rs_modules as $module) {
						if(!$this->isActive($module['name']))
							$modules[] = $module;
					}
					break;
				case 'required':
					foreach($rs_modules as $module) {
						if($module['is_required'] === true)
							$modules[] = $module;
					}
					break;
				case 'update':
					break;
				default:
					$modules = $rs_modules;
			}
		}
		
		return $modules;
	}
	
	/**
	 * Fetch the entry count based on a specific status.
	 * @since 1.4.0-beta_snap-03
	 *
	 * @access private
	 * @param string $status -- The module's status.
	 * @param null|string $search -- The search query.
	 * @return int
	 */
	private function getEntryCount(string $status, ?string $search): int {
		return count($this->getResults($status, $search));
	}
}