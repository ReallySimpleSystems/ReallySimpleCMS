<?php
/**
 * Admin class used to implement the Widget object. Inherits from the Post class.
 * @since 1.6.0-alpha
 *
 * @package ReallySimpleCMS
 *
 * Widgets are used to add small blocks of content to the front end of the website outside of the content area.
 * Widgets can be created, modified, and deleted. They are stored in the `posts` table as their own post type.
 *
 * ## VARIABLES ##
 * See `Post` class for a list of inherited vars
 * - private string $post_type
 *
 * ## METHODS ##
 * See `Post` class for a list of inherited methods
 * - public __construct(int $id, string $action)
 * LISTS, FORMS, & ACTIONS:
 * - public listRecords(): void
 * - public createRecord(): void
 * - public editRecord(): void
 * - public updateWidgetStatus(string $status, int $id): void
 * - public deleteRecord(): void
 * VALIDATION:
 * - private validateSubmission(array $data): string
 * MISCELLANEOUS:
 * - public pageHeading(): void
 * - private exitNotice(string $exit_status, int $status_code): string
 * - private bulkActions(): void
 */
class Widget extends Post implements AdminInterface {
	/**
	 * The currently queried widget's post type.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $post_type = 'widget';
	
	/**
	 * Class constructor.
	 * @since 1.1.1-beta
	 *
	 * @access public
	 * @param int $id -- The widget's id.
	 * @param string $action -- The current action.
	 */
	public function __construct(int $id, string $action) {
		global $rs_query;
		
		$this->action = $action;
		
		if($id > 0) {
			$cols = array_keys(get_object_vars($this));
			$exclude = array('action', 'paged', 'table', 'px', 'post_type');
			$cols = array_diff($cols, $exclude);
			
			$cols = array_map(function($col) {
				return $this->px . $col;
			}, $cols);
			
			$widget = $rs_query->selectRow($this->table, $cols, array(
				$this->px . 'id' => $id,
				$this->px . 'type' => $this->post_type
			));
			
			foreach($widget as $key => $value) {
				$col = substr($key, mb_strlen($this->px));
				$this->$col = $widget[$key];
			}
		} else {
			$this->id = 0;
		}
	}
	
	/*------------------------------------*\
		LISTS, FORMS, & ACTIONS
	\*------------------------------------*/
	
	/**
	 * Construct a list of all widgets in the database.
	 * @since 1.6.0-alpha
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
		<table class="data-table has-bulk-select">
			<thead>
				<?php
				$header_cols = array(
					'bulk-select' => domTag('input', array(
						'type' => 'checkbox',
						'class' => 'checkbox bulk-selector'
					)),
					'title' => 'Title',
					'slug' => 'Slug',
					'status' => 'Status'
				);
				
				echo tableHeaderRow($header_cols);
				?>
			</thead>
			<tbody>
				<?php
				$order_by = $this->px . 'title';
				$order = 'ASC';
				
				if(!is_null($search)) {
					// Search results
					$widgets = $rs_query->select($this->table, '*', array(
						$this->px . 'title' => array('LIKE', '%' . $search . '%'),
						$this->px . 'type' => $this->post_type
					), array(
						'order_by' => $order_by,
						'order' => $order,
						'limit' => array($this->paged['start'], $this->paged['per_page'])
					));
				} else {
					// All results
					$widgets = $rs_query->select($this->table, '*', array(
						$this->px . 'type' => $this->post_type
					), array(
						'order_by' => $order_by,
						'order' => $order,
						'limit' => array($this->paged['start'], $this->paged['per_page'])
					));
				}
				
				foreach($widgets as $widget) {
					list($w_id, $w_title, $w_status, $w_slug) = array(
						$widget[$this->px . 'id'],
						$widget[$this->px . 'title'],
						$widget[$this->px . 'status'],
						$widget[$this->px . 'slug']
					);
					
					$actions = array(
						// Edit
						userHasPrivilege('can_edit_widgets') ? actionLink('edit', array(
							'caption' => 'Edit',
							'id' => $w_id
						)) : null,
						// Delete
						userHasPrivilege('can_delete_widgets') ? actionLink('delete', array(
							'classes' => 'modal-launch delete-item',
							'data_item' => 'widget',
							'caption' => 'Delete',
							'id' => $w_id
						)) : null
					);
					
					// Filter out any empty actions
					$actions = array_filter($actions);
					
					echo tableRow(
						// Bulk select
						tdCell(domTag('input', array(
							'type' => 'checkbox',
							'class' => 'checkbox',
							'value' => $w_id
						)), 'bulk-select'),
						// Title
						tdCell(domTag('strong', array(
							'content' => $w_title
						)) . domTag('div', array(
							'class' => 'actions',
							'content' => implode(' &bull; ', $actions)
						)), 'title'),
						// Slug
						tdCell($w_slug, 'slug'),
						// Status
						tdCell(ucfirst($w_status), 'status')
					);
				}
				
				if(empty($widgets))
					echo tableRow(tdCell('There are no widgets to display.', '', count($header_cols)));
				?>
			</tbody>
			<tfoot>
				<?php echo tableHeaderRow($header_cols); ?>
			</tfoot>
		</table>
		<?php
		// Bulk actions
		if(!empty($widgets)) $this->bulkActions();
		
		// Set up page navigation
		echo pagerNav($this->paged['current'], $this->paged['count']);
		
        include_once PATH . ADMIN . INC . '/modal-delete.php';
	}
	
	/**
	 * Create a new widget.
	 * @since 1.6.0-alpha
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
					// Title
					echo formRow(array('Title', true), array(
						'tag' => 'input',
						'id' => 'title-field',
						'class' => 'text-input required invalid init',
						'name' => 'title',
						'value' => ($_POST['title'] ?? '')
					));
					
					// Slug
					echo formRow(array('Slug', true), array(
						'tag' => 'input',
						'id' => 'slug-field',
						'class' => 'text-input required invalid init',
						'name' => 'slug',
						'value' => ($_POST['slug'] ?? '')
					));
					
					// Content
					echo formRow('Content', array(
						'tag' => 'textarea',
						'id' => 'content-field',
						'class' => 'textarea-input',
						'name' => 'content',
						'cols' => 30,
						'rows' => 10,
						'content' => htmlspecialchars(($_POST['content'] ?? ''))
					));
					
					// Status
					echo formRow('Status', array(
						'tag' => 'select',
						'id' => 'status-field',
						'class' => 'select-input',
						'name' => 'status',
						'content' => domTag('option', array(
							'value' => 'active',
							'content' => 'Active'
						)) . domTag('option', array(
							'value' => 'inactive',
							'content' => 'Inactive'
						))
					));
					
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
						'value' => 'Create Widget'
					));
					?>
				</table>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Edit an existing widget.
	 * @since 1.6.1-alpha
	 *
	 * @access public
	 */
	public function editRecord(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0) {
			redirect(ADMIN_URI);
		} else {
			$this->pageHeading();
			?>
			<div class="data-form-wrap clear">
				<form class="data-form" action="" method="post" autocomplete="off">
					<table class="form-table">
						<?php
						// Title
						echo formRow(array('Title', true), array(
							'tag' => 'input',
							'id' => 'title-field',
							'class' => 'text-input required invalid init',
							'name' => 'title',
							'value' => $this->title
						));
						
						// Slug
						echo formRow(array('Slug', true), array(
							'tag' => 'input',
							'id' => 'slug-field',
							'class' => 'text-input required invalid init',
							'name' => 'slug',
							'value' => $this->slug
						));
						
						// Content
						echo formRow('Content', array(
							'tag' => 'textarea',
							'id' => 'content-field',
							'class' => 'textarea-input',
							'name' => 'content',
							'cols' => 30,
							'rows' => 10,
							'content' => htmlspecialchars($this->content)
						));
						
						// Status
						echo formRow('Status', array(
							'tag' => 'select',
							'id' => 'status-field',
							'class' => 'select-input',
							'name' => 'status',
							'content' => domTag('option', array(
								'value' => 'active',
								'selected' => ($this->status === 'active' ? 1 : 0),
								'content' => 'Active'
							)) . domTag('option', array(
								'value' => 'inactive',
								'selected' => ($this->status === 'inactive' ? 1 : 0),
								'content' => 'Inactive'
							))
						));
						
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
							'value' => 'Update Widget'
						));
						?>
					</table>
				</form>
			</div>
			<?php
		}
	}
	
	/**
	 * Update a widget's status.
	 * @since 1.2.9-beta
	 *
	 * @access public
	 * @param string $status -- The widget's status.
	 * @param int $id -- The widget's id.
	 */
	public function updateWidgetStatus(string $status, int $id): void {
		global $rs_query;
		
		$this->id = $id;
		
		if(empty($this->id) || $this->id <= 0)
			redirect(ADMIN_URI);
		else {
			$rs_query->update($this->table, array(
				$this->px . 'status' => $status
			), array(
				$this->px . 'id' => $this->id,
				$this->px . 'type' => $this->post_type
			));
		}
	}
	
	/**
	 * Delete an existing widget.
	 * @since 1.6.1-alpha
	 *
	 * @access public
	 */
	public function deleteRecord(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0) {
			redirect(ADMIN_URI);
		} else {
			$rs_query->delete($this->table, array(
				$this->px . 'id' => $this->id,
				$this->px . 'type' => $this->post_type
			));
			
			redirect(ADMIN_URI . '?exit_status=del_success');
		}
	}
	
	/*------------------------------------*\
		VALIDATION
	\*------------------------------------*/
	
	/**
	 * Validate the form data.
	 * @since 1.6.2-alpha
	 *
	 * @access private
	 * @param array $data -- The submission data.
	 * @return string
	 */
	private function validateSubmission(array $data): string {
		global $rs_query;
		
		if(empty($data['title']) || empty($data['slug'])) {
			return exitNotice('REQ', -1);
			exit;
		}
		
		$slug = sanitize($data['slug']);
		
		// Make sure the slug is unique
		if($this->slugExists($slug))
			$slug = getUniquePostSlug($slug);
		
		if($data['status'] !== 'active' && $data['status'] !== 'inactive')
			$data['status'] = 'active';
		
		switch($this->action) {
			case 'create':
				$insert_id = $rs_query->insert($this->table, array(
					$this->px . 'title' => $data['title'],
					$this->px . 'created' => 'NOW()',
					$this->px . 'modified' => 'NOW()',
					$this->px . 'content' => $data['content'],
					$this->px . 'status' => $data['status'],
					$this->px . 'slug' => $slug,
					$this->px . 'type' => $this->post_type
				));
				
				redirect(ADMIN_URI . '?id=' . $insert_id . '&action=edit&exit_status=create_success');
				break;
			case 'edit':
				$rs_query->update($this->table, array(
					$this->px . 'title' => $data['title'],
					$this->px . 'modified' => 'NOW()',
					$this->px . 'content' => $data['content'],
					$this->px . 'status' => $data['status'],
					$this->px . 'slug' => $slug
				), array(
					$this->px . 'id' => $this->id
				));
				
				foreach($data as $key => $value) $this->$key = $value;
				
				redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&exit_status=edit_success');
				break;
		}
	}
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Construct the page heading.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access public
	 */
	public function pageHeading(): void {
		global $rs_query;
		
		switch($this->action) {
			case 'create':
				$title = 'Create Widget';
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			case 'edit':
				$title = 'Edit Widget: { ' . domTag('em', array(
					'content' => $this->title
				)) . ' }';
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			default:
				$title = 'Widgets';
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
				if(userHasPrivilege('can_create_widgets')) {
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
				if(!is_null($search)) {
					$count = $rs_query->select($this->table, 'COUNT(*)', array(
						$this->px . 'title' => array('LIKE', '%' . $search . '%'),
						$this->px . 'type' => $this->post_type
					));
				} else {
					$count = $rs_query->select($this->table, 'COUNT(*)', array(
						$this->px . 'type' => $this->post_type
					));
				}
				
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
	 * @since 1.4.0-beta_snap-02
	 *
	 * @param string $exit_status -- The exit status.
	 * @param int $status_code (optional) -- The type of notice to display.
	 * @return string
	 */
	private function exitNotice(string $exit_status, int $status_code = 1): string {
		return exitNotice(match($exit_status) {
			'create_success' => 'The widget was successfully created. ' . domTag('a', array(
				'href' => ADMIN_URI,
				'content' => 'Return to list'
			)) . '?',
			'edit_success' => 'Widget updated! ' . domTag('a', array(
				'href' => ADMIN_URI,
				'content' => 'Return to list'
			)) . '?',
			'del_success' => 'The widget was successfully deleted.',
			default => 'The action was completed successfully.'
		}, $status_code);
	}
	
	/**
	 * Construct bulk actions.
	 * @since 1.2.9-beta
	 *
	 * @access private
	 */
	private function bulkActions(): void {
		?>
		<div class="bulk-actions">
			<?php
			if(userHasPrivilege('can_edit_widgets')) {
				echo domTag('select', array(
					'class' => 'actions',
					'content' => domTag('option', array(
						'value' => 'active',
						'content' => 'Active'
					)) . domTag('option', array(
						'value' => 'inactive',
						'content' => 'Inactive'
					))
				));
				
				// Update status
				button(array(
					'class' => 'bulk-update',
					'title' => 'Bulk status update',
					'label' => 'Update'
				));
			}
			
			if(userHasPrivilege('can_delete_widgets')) {
				// Delete
				button(array(
					'class' => 'bulk-delete',
					'title' => 'Bulk delete',
					'label' => 'Delete'
				));
			}
			?>
		</div>
		<?php
	}
}