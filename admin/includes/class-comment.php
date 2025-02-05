<?php
/**
 * Admin class used to implement the Comment object.
 * @since 1.1.0-beta_snap-01
 *
 * @package ReallySimpleCMS
 *
 * Comments are left by users as feedback for a post on the front end of the site.
 * Comments can be created (front end only), moderated, and deleted.
 *
 * ## VARIABLES ##
 * - private int $id
 * - private int $post
 * - private int $author
 * - private string $created
 * - private string $content
 * - private int $upvotes
 * - private int $downvotes
 * - private string $status
 * - private int $parent
 * - private string $action
 * - private array $paged
 * - private string $table
 * - private string $px
 *
 * ## METHODS ##
 * - public __construct(int $id, string $action)
 * LISTS, FORMS, & ACTIONS:
 * - public listRecords(): void
 * - public createRecord(): void
 * - public editRecord(): void
 * - public updateCommentStatus(string $status, int $id): void
 * - public approveComment(): void
 * - public unapproveComment(): void
 * - public spamComment(): void
 * - public deleteRecord(): void
 * - public deleteSpamComments(): void
 * VALIDATION:
 * - private validateSubmission(array $data): string
 * MISCELLANEOUS:
 * - public pageHeading(): void
 * - private exitNotice(string $exit_status, int $status_code): string
 * - private bulkActions(): void
 * - private getPost(int $id): string
 * - private getPostPermalink(int $id): string
 * - private getAuthor(int $id): string
 * - private getCommentCount(string $status, string $search): int
 */
class Comment implements AdminInterface {
	/**
	 * The currently queried comment's id.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access private
	 * @var int
	 */
	private $id;
	
	/**
	 * The post the currently queried comment is attached to.
	 * @since 1.1.7[b]
	 *
	 * @access private
	 * @var int
	 */
	private $post;
	
	/**
	 * The currently queried comment's author.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var int
	 */
	private $author;
	
	/**
	 * The currently queried comment's creation date.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $created;
	
	/**
	 * The currently queried comment's content.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $content;
	
	/**
	 * The currently queried comment's upvotes.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var int
	 */
	private $upvotes;
	 
	/**
	 * The currently queried comment's downvotes.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var int
	 */
	private $downvotes;
	
	/**
	 * The currently queried comment's status.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $status;
	
	/**
	 * The currently queried comment's parent.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var int
	 */
	private $parent;
	
	/**
	 * The current action.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $action;
	
	/**
	 * The pagination.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var array
	 */
	private $paged = array();
	
	/**
	 * The associated database table.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $table = 'comments';
	
	/**
	 * The table prefix.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $px = 'c_';
	
	/**
	 * Class constructor.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @param string $action -- The current action.
	 */
	public function __construct(int $id, string $action) {
		global $rs_query;
		
		$this->action = $action;
		
		if($id > 0) {
			$cols = array_keys(get_object_vars($this));
			$exclude = array('action', 'paged', 'table', 'px');
			$cols = array_diff($cols, $exclude);
			
			$cols = array_map(function($col) {
				return $this->px . $col;
			}, $cols);
			
			$comment = $rs_query->selectRow($this->table, $cols, array(
				$this->px . 'id' => $id
			));
			
			foreach($comment as $key => $value) {
				$col = substr($key, mb_strlen($this->px));
				$this->$col = $comment[$key];
			}
		} else {
			$this->id = 0;
		}
	}
	
	/*------------------------------------*\
		LISTS, FORMS, & ACTIONS
	\*------------------------------------*/
	
	/**
	 * Construct a list of all comments in the database.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access public
	 */
	public function listRecords(): void {
		global $rs_query;
		
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
					'content' => 'Comment',
					'post' => 'Post',
					'author' => 'Author',
					'posted-date' => 'Posted Date',
					'upvotes' => domTag('i', array(
						'class' => 'fa-solid fa-thumbs-up',
						'title' => 'Upvotes'
					)),
					'downvotes' => domTag('i', array(
						'class' => 'fa-solid fa-thumbs-down',
						'title' => 'Downvotes'
					))
				);
				
				echo tableHeaderRow($header_cols);
				?>
			</thead>
			<tbody>
				<?php
				$order_by = $this->px . 'created';
				$order = 'DESC';
				
				if($status === 'all')
					$db_status = array('<>', 'spam');
				else
					$db_status = $status;
				
				if(!is_null($search)) {
					// Search results
					$comments = $rs_query->select($this->table, '*', array(
						$this->px . 'content' => array('LIKE', '%' . $search . '%'),
						$this->px . 'status' => $db_status
					), array(
						'order_by' => $order_by,
						'order' => $order,
						'limit' => array($this->paged['start'], $this->paged['per_page'])
					));
				} else {
					// All results
					$comments = $rs_query->select($this->table, '*', array(
						$this->px . 'status' => $db_status
					), array(
						'order_by' => $order_by,
						'order' => $order,
						'limit' => array($this->paged['start'], $this->paged['per_page'])
					));
				}
				
				foreach($comments as $comment) {
					list($c_id, $c_post, $c_author, $c_created, $c_content,
						$c_upvotes, $c_downvotes, $c_status, $c_parent
					) = array(
						$comment[$this->px . 'id'],
						$comment[$this->px . 'post'],
						$comment[$this->px . 'author'],
						$comment[$this->px . 'created'],
						$comment[$this->px . 'content'],
						$comment[$this->px . 'upvotes'],
						$comment[$this->px . 'downvotes'],
						$comment[$this->px . 'status'],
						$comment[$this->px . 'parent']
					);
					
					// Action links
					$actions = array(
						// Approve/unapprove
						userHasPrivilege('can_edit_comments') ? ($c_status === 'approved' ?
							actionLink('unapprove', array(
								'caption' => 'Unapprove',
								'id' => $c_id
							)) : actionLink('approve', array(
								'caption' => 'Approve',
								'id' => $c_id
							))) : null,
						// Spam
						userHasPrivilege('can_edit_comments') ? ($c_status !== 'spam' ?
							actionLink('spam', array(
								'caption' => 'Spam',
								'id' => $c_id
							)) : null
							) : null,
						// Edit
						userHasPrivilege('can_edit_comments') ? actionLink('edit', array(
							'caption' => 'Edit',
							'id' => $c_id
						)) : null,
						// Delete
						userHasPrivilege('can_delete_comments') ? actionLink('delete', array(
							'classes' => 'modal-launch delete-item',
							'data_item' => 'comment',
							'caption' => 'Delete',
							'id' => $c_id
						)) : null,
						// View
						domTag('a', array(
							'href' => $this->getPostPermalink($c_post) . '#comment-' . $c_id,
							'content' => 'View'
						))
					);
					
					// Filter out any empty actions
					$actions = array_filter($actions);
					
					echo tableRow(
						// Bulk select
						tdCell(domTag('input', array(
							'type' => 'checkbox',
							'class' => 'checkbox',
							'value' => $c_id
						)), 'bulk-select'),
						// Comment
						tdCell(trimWords($c_content) . ($c_status === 'pending' && $status === 'all' ? ' &mdash; ' .
							domTag('em', array(
								'content' => 'pending approval'
							)) : '') .
							domTag('div', array(
								'class' => 'actions',
								'content' => implode(' &bull; ', $actions)
							)), 'content'
						),
						// Post
						tdCell($this->getPost($c_post), 'post'),
						// Author
						tdCell($this->getAuthor($c_author), 'author'),
						// Date posted
						tdCell(formatDate($c_created, 'd M Y @ g:i A'), 'posted-date'),
						// Upvotes
						tdCell($c_upvotes, 'upvotes'),
						// Downvotes
						tdCell($c_downvotes, 'downvotes')
					);
				}
				
				if(empty($comments))
					echo tableRow(tdCell('There are no comments to display.', '', count($header_cols)));
				?>
			</tbody>
			<tfoot>
				<?php echo tableHeaderRow($header_cols); ?>
			</tfoot>
		</table>
		<?php
		// Bulk actions
		if(!empty($comments)) $this->bulkActions();
		
		// Set up page navigation
		echo pagerNav($this->paged['current'], $this->paged['count']);
		
        include_once PATH . ADMIN . INC . '/modal-delete.php';
	}
	
	/**
	 * Create a new comment.
	 * @since 1.3.10-beta
	 *
	 * @access public
	 */
	public function createRecord(): void {
		// Unused because comments are only created on the front end
	}
	
	/**
	 * Edit an existing comment.
	 * @since 1.1.0-beta_snap-02
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
						// Content
						echo formRow(array('Content', true), array(
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
								'value' => 'approved',
								'selected' => ($this->status === 'approved' ? 1 : 0),
								'content' => 'Approved'
							)) . domTag('option', array(
								'value' => 'pending',
								'selected' => ($this->status === 'pending' ? 1 : 0),
								'content' => 'Pending'
							)) . domTag('option', array(
								'value' => 'spam',
								'selected' => ($this->status === 'spam' ? 1 : 0),
								'content' => 'Spam'
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
							'value' => 'Update Comment'
						));
						?>
					</table>
				</form>
			</div>
			<?php
		}
	}
	
	/**
	 * Update a comment's status.
	 * @since 1.2.9-beta
	 *
	 * @access public
	 * @param string $status -- The comment's status.
	 * @param int $id (optional) -- The comment's id.
	 */
	public function updateCommentStatus(string $status, int $id = 0): void {
		global $rs_query;
		
		if($id !== 0) $this->id = $id;
		
		if(empty($this->id) || $this->id <= 0) {
			redirect(ADMIN_URI);
		} else {
			$rs_query->update($this->table, array(
				$this->px . 'status' => $status
			), array(
				$this->px . 'id' => $this->id
			));
			
			if(is_null($this->post)) {
				$this->post = $rs_query->selectField($this->table, $this->px . 'post', array(
					$this->px . 'id' => $this->id
				));
			}
			
			// Update the approved comment count for the attached post
			$count = $rs_query->select($this->table, 'COUNT(*)', array(
				$this->px . 'post' => $this->post,
				$this->px . 'status' => 'approved'
			));
			
			$rs_query->update('postmeta', array(
				'pm_value' => $count
			), array(
				'pm_post' => $this->post,
				'pm_key' => 'comment_count'
			));
		}
	}
	
	/**
	 * Approve a comment.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access public
	 */
	public function approveComment(): void {
		$this->updateCommentStatus('approved');
		
		redirect(ADMIN_URI);
	}
	
	/**
	 * Unapprove a comment.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access public
	 */
	public function unapproveComment(): void {
		$this->updateCommentStatus('pending');
		
		redirect(ADMIN_URI);
	}
	
	/**
	 * Send a comment to spam.
	 * @since 1.3.7-beta
	 *
	 * @access public
	 */
	public function spamComment(): void {
		$this->updateCommentStatus('spam');
		
		redirect(ADMIN_URI);
	}
	
	/**
	 * Delete an existing comment.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access public
	 */
	public function deleteRecord(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0) {
			redirect(ADMIN_URI);
		} else {
			$rs_query->delete($this->table, array(
				$this->px . 'id' => $this->id
			));
			
			// Update the approved comment count for the attached post
			$count = $rs_query->select($this->table, 'COUNT(*)', array(
				$this->px . 'post' => $this->post,
				$this->px . 'status' => 'approved'
			));
			
			$rs_query->update('postmeta', array(
				'pm_value' => $count
			), array(
				'pm_post' => $this->post,
				'pm_key' => 'comment_count'
			));
			
			redirect(ADMIN_URI . '?exit_status=del_success');
		}
	}
	
	/**
	 * Delete all spam comments.
	 * @since 1.3.7-beta
	 *
	 * @access public
	 */
	public function deleteSpamComments(): void {
		global $rs_query;
		
		$rs_query->delete($this->table, array(
			$this->px . 'status' => 'spam'
		));
	}
	
	/*------------------------------------*\
		VALIDATION
	\*------------------------------------*/
	
	/**
	 * Validate the form data.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access private
	 * @param array $data -- The submission data.
	 * @return string
	 */
	private function validateSubmission(array $data): string {
		global $rs_query;
		
		if(empty($data['content'])) {
			return exitNotice('REQ', -1);
			exit;
		}
		
		if($data['status'] !== 'approved' && $data['status'] !== 'pending')
			$data['status'] = 'pending';
		
		$rs_query->update($this->table, array(
			$this->px . 'content' => $data['content'],
			$this->px . 'status' => $data['status']
		), array(
			$this->px . 'id' => $this->id
		));
		
		foreach($data as $key => $value) $this->$key = $value;
		
		redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&exit_status=edit_success');
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
		switch($this->action) {
			case 'create':
				// unused
				break;
			case 'edit':
				$title = 'Edit Comment: { ' . domTag('em', array(
					'content' => $this->getAuthor($this->author)
				)) . ' }';
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			default:
				$title = 'Comments';
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
				// Search
				recordSearch(array(
					'status' => $status
				));
				
				// Info
				adminInfo();
				
				echo domTag('hr');
				
				// Notices
				if(!getSetting('enable_comments'))
					echo notice('Comments are currently disabled. You can enable them on the <a href="' . ADMIN . '/settings.php">settings page</a>.', 2, false, true);
				
				// Exit notices
				if(isset($_GET['exit_status']))
					echo $this->exitNotice($_GET['exit_status']);
				?>
				<ul class="status-nav">
					<?php
					$keys = array('all', 'approved', 'pending', 'spam');
					$count = array();
					
					foreach($keys as $key) {
						if($key === 'all') {
							if(!is_null($search) && $key === $status)
								$count[$key] = $this->getCommentCount('', $search);
							else
								$count[$key] = $this->getCommentCount();
						} else {
							if(!is_null($search) && $key === $status)
								$count[$key] = $this->getCommentCount($key, $search);
							else
								$count[$key] = $this->getCommentCount($key);
						}
					}
					
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
	 * @since 1.4.0-beta_snap-02
	 *
	 * @param string $exit_status -- The exit status.
	 * @param int $status_code (optional) -- The type of notice to display.
	 * @return string
	 */
	private function exitNotice(string $exit_status, int $status_code = 1): string {
		return exitNotice(match($exit_status) {
			'edit_success' => 'Comment updated! ' . domTag('a', array(
				'href' => ADMIN_URI,
				'content' => 'Return to list'
			)) . '?',
			'del_success' => 'The comment was successfully deleted.',
			default => 'The action was completed successfully.'
		}, $status_code);
	}
	
	/**
	 * Construct bulk actions.
	 * @since 1.2.7-beta
	 *
	 * @access private
	 */
	private function bulkActions(): void {
		$status = $_GET['status'] ?? '';
		?>
		<div class="bulk-actions">
			<?php
			if(userHasPrivilege('can_edit_comments')) {
				echo domTag('select', array(
					'class' => 'actions',
					'content' => domTag('option', array(
						'value' => 'approved',
						'content' => 'Approve'
					)) . domTag('option', array(
						'value' => 'pending',
						'content' => 'Unapprove'
					)) . domTag('option', array(
						'value' => 'spam',
						'content' => 'Spam'
					))
				));
				
				// Update status
				button(array(
					'class' => 'bulk-update',
					'title' => 'Bulk status update',
					'label' => 'Update'
				));
			}
			
			if(userHasPrivilege('can_delete_comments')) {
				// Delete
				button(array(
					'class' => 'bulk-delete',
					'title' => 'Bulk delete',
					'label' => 'Delete'
				));
				
				if($status === 'spam') {
					// Clear spam
					button(array(
						'class' => 'bulk-delete-spam',
						'title' => 'Delete all spam',
						'label' => 'Clear spam'
					));
				}
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Fetch a comment's post.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access private
	 * @param int $id -- The post's id.
	 * @return string
	 */
	private function getPost(int $id): string {
		global $rs_query;
		
		$title = $rs_query->selectField('posts', 'p_title', array(
			'p_id' => $id
		));
		
		return domTag('a', array(
			'href' => $this->getPostPermalink($id),
			'content' => $title
		));
	}
	
	/**
	 * Fetch a post's permalink.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access private
	 * @param int $id -- The post's id.
	 * @return string
	 */
	private function getPostPermalink(int $id): string {
		global $rs_query;
		
		$post = $rs_query->selectRow('posts', array('p_slug', 'p_parent', 'p_type'), array(
			'p_id' => $id
		));
		
		return getPermalink($post['p_type'], $post['p_parent'], $post['p_slug']);
	}
	 
	/**
	 * Fetch a comment's author.
	 * @since 1.1.0-beta_snap-02
	 *
	 * @access private
	 * @param int $id -- The author's id.
	 * @return string
	 */
	private function getAuthor(int $id): string {
		global $rs_query;
		
		$author = $rs_query->selectField('usermeta', 'um_value', array(
			'um_user' => $id,
			'um_key' => 'display_name'
		));
		
		return empty($author) ? 'Anonymous' : $author;
	}
	
	/**
	 * Fetch the comment count based on a specific status.
	 * @since 1.1.7-beta
	 *
	 * @access private
	 * @param string $status (optional) -- The comment's status.
	 * @param string $search (optional) -- The search query.
	 * @return int
	 */
	private function getCommentCount(string $status = '', string $search = ''): int {
		global $rs_query;
		
		if(empty($status))
			$db_status = array('<>', 'spam');
		else
			$db_status = $status;
		
		if(!empty($search)) {
			return $rs_query->select($this->table, 'COUNT(*)', array(
				$this->px . 'content' => array('LIKE', '%' . $search . '%'),
				$this->px . 'status' => $db_status
			));
		} else {
			return $rs_query->select($this->table, 'COUNT(*)', array(
				$this->px . 'status' => $db_status
			));
		}
	}
}