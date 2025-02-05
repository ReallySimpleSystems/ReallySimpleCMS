<?php
/**
 * Admin class used to implement the Post object.
 * @since 1.4.0-alpha
 *
 * @package ReallySimpleCMS
 *
 * Posts are the basis of the front end of the website. Currently, there are two post types: post (default, used for blog posts) and page (used for content pages).
 * Posts can be created, modified, and deleted.
 *
 * ## VARIABLES ##
 * - protected int $id
 * - protected string $title
 * - protected int $author
 * - protected string $created
 * - protected string $modified
 * - protected string $content
 * - protected string $status
 * - protected string $slug
 * - protected int $parent
 * - protected string $type
 * - private array $type_data
 * - private array $tax_data
 * - protected string $action
 * - protected array $paged
 * - protected string $table
 * - protected string $px
 *
 * ## METHODS ##
 * - public __construct(int $id, string $action, array $type_data)
 * LISTS, FORMS, & ACTIONS:
 * - public listRecords(): void
 * - public createRecord(): void
 * - public editRecord(): void
 * - public duplicatePost(): void
 * - public updatePostStatus(string $status, int $id): void
 * - public trashPost(): void
 * - public restorePost(): void
 * - public deleteRecord(): void
 * VALIDATION:
 * - private validateSubmission(array $data): string
 * MISCELLANEOUS:
 * - public pageHeading(): void
 * - private exitNotice(string $exit_status, int $status_code): string
 * - private bulkActions(): void
 * - protected slugExists(string $slug): bool
 * - private isTrash(int $id): bool
 * - private isDescendant(int $id, int $ancestor): bool
 * - protected getPostMeta(int $id): array
 * - private getStatusList(): string
 * - protected getAuthor(int $id): string
 * - private getAuthorList(int $id): string
 * - private getTerms(string $taxonomy, int $id): string
 * - private getTermsList(string $taxonomy, int $id): string
 * - private getParent(int $id): string
 * - private getParentList(string $type, int $parent, int $id): string
 * - private getTemplateList(int $id): string
 * - protected getPostCount(string $type, string $status, string $search, string $term): int
 */
class Post implements AdminInterface {
	/**
	 * The currently queried post's id.
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var int
	 */
	protected $id;
	
	/**
	 * The currently queried post's title.
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $title;
	
	/**
	 * The currently queried post's author.
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var int
	 */
	protected $author;
	
	/**
	 * The currently queried post's creation date (usually the publish date).
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $created;
	
	/**
	 * The currently queried post's modified date.
	 * @since 1.2.9-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $modified;
	
	/**
	 * The currently queried post's content.
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $content;
	
	/**
	 * The currently queried post's status.
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $status;
	
	/**
	 * The currently queried post's slug.
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $slug;
	
	/**
	 * The currently queried post's parent.
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var int
	 */
	protected $parent;
	
	/**
	 * The currently queried post's type.
	 * @since 1.0.1-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $type;
	
	/**
	 * The currently queried post's type data.
	 * @since 1.0.1-beta
	 *
	 * @access private
	 * @var array
	 */
	private $type_data = array();
	
	/**
	 * The currently queried post's taxonomy data.
	 * @since 1.0.6-beta
	 *
	 * @access private
	 * @var array
	 */
	private $tax_data = array();
	
	/**
	 * The current action.
	 * @since 1.3.13-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $action;
	
	/**
	 * The pagination.
	 * @since 1.3.13-beta
	 *
	 * @access protected
	 * @var array
	 */
	protected $paged = array();
	
	/**
	 * The associated database table.
	 * @since 1.3.13-beta
	 *
	 * @access protected
	 * @var string
	 */
	protected $table = 'posts';
	
	/**
	 * The table prefix.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access protected
	 * @var string
	 */
	protected $px = 'p_';
	
	/**
	 * Class constructor.
	 * @since 1.0.1-beta
	 *
	 * @access public
	 * @param int $id -- The post's id.
	 * @param string $action -- The current action.
	 * @param array $type_data (optional) -- The post type data.
	 */
	public function __construct(int $id, string $action, array $type_data = array()) {
		global $rs_query, $taxonomies;
		
		$this->action = $action;
		
		if($id > 0) {
			$cols = array_keys(get_object_vars($this));
			$exclude = array('type_data', 'tax_data', 'action', 'paged', 'table', 'px');
			$cols = array_diff($cols, $exclude);
			
			$cols = array_map(function($col) {
				return $this->px . $col;
			}, $cols);
			
			$post = $rs_query->selectRow($this->table, $cols, array(
				$this->px . 'id' => $id
			));
			
			foreach($post as $key => $value) {
				$col = substr($key, mb_strlen($this->px));
				$this->$col = $post[$key];
			}
		} else {
			$this->id = 0;
		}
		
		$this->type_data = $type_data;
		
		// Fetch any associated taxonomy data
		if(!empty($this->type_data['taxonomies'])) {
			foreach($this->type_data['taxonomies'] as $tax) {
				if(array_key_exists($tax, $taxonomies))
					$this->tax_data[] = $taxonomies[$tax];
			}
		}
	}
	
	/*------------------------------------*\
		LISTS, FORMS, & ACTIONS
	\*------------------------------------*/
	
	/**
	 * Construct a list of all posts in the database.
	 * @since 1.4.0-alpha
	 *
	 * @access public
	 */
	public function listRecords(): void {
		global $rs_query;
		
		// Query vars
		$type = $this->type_data['name'];
		$status = $_GET['status'] ?? 'all';
		$search = $_GET['search'] ?? null;
		$term = $_GET['term'] ?? '';
		$this->paged = paginate((int)($_GET['paged'] ?? 1));
		
		$this->pageHeading();
		?>
		<table class="data-table has-bulk-select">
			<thead>
				<?php
				if($this->type_data['hierarchical']) {
					$header_cols = array(
						'bulk-select' => domTag('input', array(
							'type' => 'checkbox',
							'class' => 'checkbox bulk-selector'
						)),
						'title' => 'Title',
						'author' => 'Author',
						'publish-date' => 'Publish Date',
						'parent' => 'Parent',
						'meta-title' => 'Meta Title',
						'meta-description' => 'Meta Desc.'
					);
					
					// Add a label for comments if they're enabled
					if(getSetting('enable_comments') && $this->type_data['comments']) {
						$offset = 5;
						
						$header_cols = array_slice($header_cols, 0, $offset, true) + array(
							'comments' => 'Comments'
						) + array_slice($header_cols, $offset, null, true);
					}
				} else {
					$header_cols = array(
						'bulk-select' => domTag('input', array(
							'type' => 'checkbox',
							'class' => 'checkbox bulk-selector'
						)),
						'title' => 'Title',
						'author' => 'Author',
						'publish-date' => 'Publish Date',
						'meta-title' => 'Meta Title',
						'meta-description' => 'Meta Desc.'
					);
					
					// Add a label for comments if they're enabled
					if(getSetting('enable_comments') && $this->type_data['comments']) {
						$offset = 4;
						
						$header_cols = array_slice($header_cols, 0, $offset, true) + array(
							'comments' => 'Comments'
						) + array_slice($header_cols, $offset, null, true);
					}
					
					// Add labels for any associated taxonomies
					if(!empty($this->tax_data)) {
						$offset = 3;
						
						foreach($this->tax_data as $tax) {
							$header_cols = array_slice($header_cols, 0, $offset, true) + array(
								'taxonomy' => $tax['label']
							) + array_slice($header_cols, $offset, null, true);
							$offset++;
						}
					}
				}
				
				echo tableHeaderRow($header_cols);
				?>
			</thead>
			<tbody>
				<?php
				$order_by = $type === 'page' ? $this->px . 'title' : $this->px . 'created';
				$order = $type === 'page' ? 'ASC' : 'DESC';
				
				if($status === 'all')
					$db_status = array('<>', 'trash');
				else
					$db_status = $status;
					
				if(!empty($term)) {
					$term_id = (int)$rs_query->selectField('terms', 't_id', array(
						't_slug' => $term
					));
					
					$relationships = $rs_query->select('term_relationships', 'tr_post', array(
						'tr_term' => $term_id
					));
					
					if(count($relationships) > 1) {
						$post_ids = array('IN');
						
						foreach($relationships as $rel)
							$post_ids[] = $rel['tr_post'];
					} elseif(count($relationships) > 0) {
						$post_ids = $relationships[0]['tr_post'];
					} else {
						$post_ids = 0;
					}
					
					// Term results
					$posts = $rs_query->select($this->table, '*', array(
						$this->px . 'id' => $post_ids,
						/* $this->px . 'status' => $db_status, */
						$this->px . 'type' => $type
					), array(
						'order_by' => $order_by,
						'order' => $order,
						'limit' => array($this->paged['start'], $this->paged['per_page'])
					));
				} elseif(!is_null($search)) {
					// Search results
					$posts = $rs_query->select($this->table, '*', array(
						$this->px . 'title' => array('LIKE', '%' . $search . '%'),
						$this->px . 'status' => $db_status,
						$this->px . 'type' => $type
					), array(
						'order_by' => $order_by,
						'order' => $order, 
						'limit' => array($this->paged['start'], $this->paged['per_page'])
					));
				} else {
					// All results
					$posts = $rs_query->select($this->table, '*', array(
						$this->px . 'status' => $db_status,
						$this->px . 'type' => $type
					), array(
						'order_by' => $order_by,
						'order' => $order,
						'limit' => array($this->paged['start'], $this->paged['per_page'])
					));
				}
				
				foreach($posts as $post) {
					list($p_id, $p_title, $p_author, $p_created,
						$p_status, $p_slug, $p_parent, $p_type
					) = array(
						$post[$this->px . 'id'],
						$post[$this->px . 'title'],
						$post[$this->px . 'author'],
						$post[$this->px . 'created'],
						$post[$this->px . 'status'],
						$post[$this->px . 'slug'],
						$post[$this->px . 'parent'],
						$post[$this->px . 'type']
					);
					
					$meta = $this->getPostMeta($p_id);
					$type_name = str_replace(' ', '_', $this->type_data['labels']['name_lowercase']);
					
					switch($p_status) {
						case 'draft':
							$is_published = false;
							break;
						case 'published': case 'private':
							$is_published = true;
							break;
					}
					
					// Terms
					$terms = array();
					
					if(!$this->type_data['hierarchical'] && !empty($this->tax_data)) {
						foreach($this->tax_data as $tax)
							$terms[] = tdCell($this->getTerms($tax['name'], $p_id), 'terms');
					}
					
					$term_cols = implode('', $terms);
					
					// Action links
					$actions = array(
						// Edit
						userHasPrivilege('can_edit_' . $type_name) && ($status !== 'trash' && $p_status !== 'trash') ?
							actionLink('edit', array(
								'caption' => 'Edit',
								'id' => $p_id
							)) : null,
						// Duplicate
						userHasPrivilege('can_create_' . $type_name) && ($status !== 'trash' && $p_status !== 'trash') ?
							actionLink('duplicate', array(
								'caption' => 'Duplicate',
								'id' => $p_id
							)) : null,
						// Trash/restore
						userHasPrivilege('can_edit_' . $type_name) ? (($status === 'trash' || $p_status === 'trash') ?
							actionLink('restore', array(
								'caption' => 'Restore',
								'id' => $p_id
							)) : actionLink('trash', array(
								'caption' => 'Trash',
								'id' => $p_id
							))) : null,
						// Delete
						($status === 'trash' || $p_status === 'trash') ? (userHasPrivilege('can_delete_' . $type_name) ?
							actionLink('delete', array(
								'classes' => 'modal-launch delete-item',
								'data_item' => strtolower($this->type_data['labels']['name_singular']),
								'caption' => 'Delete',
								'id' => $p_id
							)) : null) : (
						// View/preview
						domTag('a', array(
							'href' => ($is_published ? (isHomePage($p_id) ? '/' :
								getPermalink($p_type, $p_parent, $p_slug)) :
								('/?id=' . $p_id . '&preview=true')),
							'content' => ($is_published ? 'View' : 'Preview')
						)))
					);
					
					// Filter out any empty actions
					$actions = array_filter($actions);
					
					echo tableRow(
						// Bulk select
						tdCell(domTag('input', array(
							'type' => 'checkbox',
							'class' => 'checkbox',
							'value' => $p_id
						)), 'bulk-select'),
						// Title
						tdCell((isHomePage($p_id) ?
							domTag('i', array(
								'class' => 'fa-solid fa-house-chimney',
								'style' => 'cursor: help;',
								'title' => 'Home Page'
							)) . ' ' : '') .
							domTag('strong', array(
								'content' => $p_title
							)) . ($p_status !== 'published' && $status === 'all' ? ' &mdash; ' .
							domTag('em', array(
								'content' => $p_status
							)) : '') .
							domTag('div', array(
								'class' => 'actions',
								'content' => implode(' &bull; ', $actions)
							)), 'title'),
						// Author
						tdCell($this->getAuthor($p_author), 'author'),
						// Terms (hierarchical post types only)
						$term_cols,
						// Publish date
						tdCell(is_null($p_created) ? '&mdash;' :
							formatDate($p_created, 'd M Y @ g:i A'), 'publish-date'),
						// Parent (hierarchical post types only)
						$this->type_data['hierarchical'] ?
							tdCell($this->getParent($p_parent), 'parent') : '',
						// Comments
						getSetting('enable_comments') && $this->type_data['comments'] ?
							tdCell(($meta['comment_status'] ? $meta['comment_count'] : '&mdash;'), 'comments') : '',
						// Meta title
						tdCell(!empty($meta['title']) ? 'Yes' : 'No', 'meta-title'),
						// Meta description
						tdCell(!empty($meta['description']) ? 'Yes' : 'No', 'meta-description')
					);
				}
				
				if(empty($posts))
					echo tableRow(tdCell($this->type_data['labels']['no_items'], '', count($header_cols)));
				?>
			</tbody>
			<tfoot>
				<?php echo tableHeaderRow($header_cols); ?>
			</tfoot>
		</table>
		<?php
		// Bulk actions
		if(!empty($posts)) $this->bulkActions();
		
		// Set up page navigation
		echo pagerNav($this->paged['current'], $this->paged['count']);
		
        include_once PATH . ADMIN . INC . '/modal-delete.php';
	}
	
	/**
	 * Create a new post.
	 * @since 1.4.1-alpha
	 *
	 * @access public
	 */
	public function createRecord(): void {
		$type = $this->type_data['name'];
		
		$this->pageHeading();
		?>
		<div class="data-form-wrap clear">
			<form class="data-form" action="" method="post" autocomplete="off">
				<div class="content">
					<?php
					// Type (hidden)
					echo domTag('input', array(
						'type' => 'hidden',
						'name' => 'type',
						'value' => $type
					));
					
					// Title
					echo domTag('input', array(
						'id' => 'title-field',
						'class' => 'text-input required invalid init',
						'name' => 'title',
						'value' => ($_POST['title'] ?? ''),
						'placeholder' => $this->type_data['labels']['title_placeholder']
					));
					?>
					<div class="permalink">
						<?php
						// Permalink
						echo domTag('label', array(
							'for' => 'slug-field',
							'content' => domTag('strong', array(
								'content' => 'Permalink: '
							)) . getSetting('site_url') . getPermalink($this->type_data['name'])
						));
						echo domTag('input', array(
							'id' => 'slug-field',
							'class' => 'text-input required invalid init',
							'name' => 'slug',
							'value' => ($_POST['slug'] ?? '')
						));
						echo domTag('span', array(
							'content' => '/'
						));
						?>
					</div>
					<?php
					// Insert media button
					echo domTag('input', array(
						'type' => 'button',
						'class' => 'button-input button modal-launch',
						'value' => 'Insert Media',
						'data-type' => 'all',
						'data-insert' => 'true'
					));
					
					// Content
					echo domTag('textarea', array(
						'class' => 'textarea-input',
						'name' => 'content',
						'rows' => 25,
						'content' => htmlspecialchars(($_POST['content'] ?? ''))
					));
					?>
				</div>
				<div class="sidebar">
					<div class="block">
						<h2>Publish</h2>
						<div class="row">
							<?php
							// Status
							echo domTag('label', array(
								'for' => 'status-field',
								'content' => 'Status'
							));
							echo domTag('select', array(
								'id' => 'status-field',
								'class' => 'select-input',
								'name' => 'status',
								'content' => $this->getStatusList()
							));
							?>
						</div>
						<div class="row">
							<?php
							// Author
							echo domTag('label', array(
								'for' => 'author-field',
								'content' => 'Author'
							));
							echo domTag('select', array(
								'id' => 'author-field',
								'class' => 'select-input',
								'name' => 'author',
								'content' => $this->getAuthorList()
							));
							?>
						</div>
						<div class="row">
							<?php
							// Publish date
							echo domTag('label', array(
								'for' => 'created-field',
								'content' => 'Publish on'
							));
							echo domTag('br');
							echo domTag('input', array(
								'type' => 'date',
								'id' => 'created-field',
								'class' => 'date-input',
								'name' => 'created[]'
							));
							echo domTag('input', array(
								'type' => 'time',
								'id' => 'created-field',
								'class' => 'date-input',
								'name' => 'created[]'
							));
							?>
						</div>
						<div id="submit" class="row">
							<?php
							// Submit button
							echo domTag('input', array(
								'type' => 'submit',
								'class' => 'submit-input button',
								'name' => 'submit',
								'value' => 'Publish'
							));
							?>
						</div>
					</div>
					<?php
					if($this->type_data['hierarchical']) {
						?>
						<div class="block">
							<h2>Attributes</h2>
							<div class="row">
								<?php
								// Parent
								echo domTag('label', array(
									'for' => 'parent-field',
									'content' => 'Parent'
								));
								echo domTag('select', array(
									'id' => 'parent-field',
									'class' => 'select-input',
									'name' => 'parent',
									'content' => domTag('option', array(
										'value' => 0,
										'content' => '(none)'
									)) . $this->getParentList($type)
								));
								?>
							</div>
							<div class="row">
								<?php
								// Template
								echo domTag('label', array(
									'for' => 'template-field',
									'content' => 'Template'
								));
								echo domTag('select', array(
									'id' => 'template-field',
									'class' => 'select-input',
									'name' => 'template',
									'content' => domTag('option', array(
										'value' => 'default',
										'content' => 'Default'
									)) . $this->getTemplateList()
								));
								?>
							</div>
						</div>
						<?php
					} else {
						if(!empty($this->tax_data)) {
							foreach($this->tax_data as $tax) {
								?>
								<div class="block">
									<h2><?php echo $tax['label']; ?></h2>
									<div class="row">
										<?php
										// Terms list
										echo $this->getTermsList($tax['name']);
										?>
									</div>
								</div>
								<?php
							}
						}
					}
					
					if(getSetting('enable_comments') && $this->type_data['comments']) {
						?>
						<div class="block">
							<h2>Comments</h2>
							<div class="row">
								<?php
								// Enable comments
								$comments = isset($_POST['comments']) ||
									(!isset($_POST['comments']) && $this->type_data['comments']);
								
								echo domTag('input', array(
									'type' => 'checkbox',
									'class' => 'checkbox-input',
									'name' => 'comments',
									'value' => (!empty($comments) ? 1 : 0),
									'checked' => $comments,
									'label' => array(
										'class' => 'checkbox-label',
										'content' => domTag('span', array(
											'content' => 'Enable comments'
										))
									)
								));
								?>
							</div>
						</div>
						<?php
					}
					?>
					<div class="block">
						<h2>Featured Image</h2>
						<div class="row">
							<div class="image-wrap">
								<?php
								// Featured image thumbnail
								echo domTag('img', array(
									'src' => '//:0',
									'data-field' => 'thumb'
								));
								
								// Remove image button
								echo domTag('span', array(
									'class' => 'image-remove',
									'title' => 'Remove',
									'content' => domTag('i', array(
										'class' => 'fa-solid fa-xmark'
									))
								));
								?>
							</div>
							<?php
							// Featured image (hidden)
							echo domTag('input', array(
								'type' => 'hidden',
								'name' => 'feat_image',
								'value' => ($_POST['feat_image'] ?? 0),
								'data-field' => 'id'
							));
							
							// Choose image
							echo domTag('a', array(
								'class' => 'modal-launch',
								'href' => 'javascript:void(0)',
								'data-type' => 'image',
								'content' => 'Choose Image',
							));
							?>
						</div>
					</div>
				</div>
				<div class="metadata">
					<div class="block">
						<h2>Metadata</h2>
						<div class="row">
							<?php
							// Meta title
							echo domTag('label', array(
								'for' => 'meta-title-field',
								'content' => 'Title'
							));
							echo domTag('br');
							echo domTag('input', array(
								'id' => 'meta-title-field',
								'class' => 'text-input',
								'name' => 'meta_title',
								'value' => ($_POST['meta_title'] ?? '')
							));
							?>
						</div>
						<div class="row">
							<?php
							// Meta description
							echo domTag('label', array(
								'for' => 'meta-description-field',
								'content' => 'Description'
							));
							echo domTag('br');
							echo domTag('textarea', array(
								'id' => 'meta-description-field',
								'class' => 'textarea-input',
								'name' => 'meta_description',
								'cols' => 30,
								'rows' => 4,
								'content' => ($_POST['meta_description'] ?? '')
							));
							?>
						</div>
						<div class="row">
							<?php
							// Index post
							$index = isset($_POST['index_post']) ||
								(!isset($_POST['index_post']) && getSetting('do_robots'));
							
							echo domTag('input', array(
								'type' => 'checkbox',
								'class' => 'checkbox-input',
								'name' => 'index_post',
								'value' => ($index ? 1 : 0),
								'checked' => $index,
								'label' => array(
									'class' => 'checkbox-label',
									'content' => domTag('span', array(
										'content' => 'Index ' . strtolower($this->type_data['labels']['name_singular'])
									))
								)
							));
							?>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
		include_once PATH . ADMIN . INC . '/modal-upload.php';
	}
	
	/**
	 * Edit an existing post.
	 * @since 1.4.9-alpha
	 *
	 * @access public
	 */
	public function editRecord(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0) {
			redirect(ADMIN_URI);
		} else {
			if(empty($this->type)) {
				redirect(ADMIN_URI);
			} elseif($this->type === 'media') {
				redirect('media.php?id=' . $this->id . '&action=edit');
			} elseif($this->type === 'widget') {
				redirect('widgets.php?id=' . $this->id . '&action=edit');
			} else {
				if($this->isTrash($this->id)) {
					redirect(ADMIN_URI . ($this->type !== 'post' ? '?type=' . $this->type . '&' : '?') .
						'status=trash');
				} else {
					$this->pageHeading();
					
					$meta = $this->getPostMeta($this->id);
					
					if(!empty($meta['feat_image']))
						list($width, $height) = getimagesize(PATH . getMediaSrc($meta['feat_image']));
					?>
					<div class="data-form-wrap clear">
						<form class="data-form" action="" method="post" autocomplete="off">
							<div class="content">
								<?php
								// Title
								echo domTag('input', array(
									'id' => 'title-field',
									'class' => 'text-input required invalid init',
									'name' => 'title',
									'value' => $this->title,
									'placeholder' => $this->type_data['labels']['title_placeholder']
								));
								?>
								<div class="permalink">
									<?php
									// Permalink
									echo domTag('label', array(
										'for' => 'slug-field',
										'content' => domTag('strong', array(
											'content' => 'Permalink: '
										)) . getSetting('site_url') . getPermalink($this->type, $this->parent)
									));
									echo domTag('input', array(
										'id' => 'slug-field',
										'class' => 'text-input required invalid init',
										'name' => 'slug',
										'value' => $this->slug
									));
									echo domTag('span', array(
										'content' => '/'
									));
									?>
								</div>
								<?php
								// Insert media button
								echo domTag('input', array(
									'type' => 'button',
									'class' => 'button-input button modal-launch',
									'value' => 'Insert Media',
									'data-type' => 'all',
									'data-insert' => 'true'
								));
								
								// Content
								echo domTag('textarea', array(
									'class' => 'textarea-input',
									'name' => 'content',
									'rows' => 25,
									'content' => htmlspecialchars($this->content)
								));
								?>
							</div>
							<div class="sidebar">
								<div class="block">
									<h2>Publish</h2>
									<div class="row">
										<?php
										// Status
										echo domTag('label', array(
											'for' => 'status-field',
											'content' => 'Status'
										));
										echo domTag('select', array(
											'id' => 'status-field',
											'class' => 'select-input',
											'name' => 'status',
											'content' => $this->getStatusList()
										));
										?>
									</div>
									<div class="row">
										<?php
										// Author
										echo domTag('label', array(
											'for' => 'author-field',
											'content' => 'Author'
										));
										echo domTag('select', array(
											'id' => 'author-field',
											'class' => 'select-input',
											'name' => 'author',
											'content' => $this->getAuthorList($this->author)
										));
										?>
									</div>
									<div class="row">
										<?php
										// Publish date
										echo domTag('label', array(
											'for' => 'created-field',
											'content' => 'Published on'
										));
										echo domTag('br');
										echo domTag('input', array(
											'type' => 'date',
											'id' => 'created-field',
											'class' => 'date-input',
											'name' => 'created[]',
											'value' => (
												!is_null($this->created) ?
												formatDate($this->created, 'Y-m-d') :
												formatDate($this->modified, 'Y-m-d')
											)
										));
										echo domTag('input', array(
											'type' => 'time',
											'id' => 'created-field',
											'class' => 'date-input',
											'name' => 'created[]',
											'value' => (
												!is_null($this->created) ?
												formatDate($this->created, 'H:i') :
												formatDate($this->modified, 'H:i'))
										));
										?>
									</div>
									<div id="submit" class="row">
										<?php
										switch($this->status) {
											case 'draft':
												$is_published = false;
												break;
											case 'published': case 'private':
												$is_published = true;
												break;
										}
										
										// View/preview link
										echo $is_published ?
											domTag('a', array(
												'href' => (isHomePage($this->id) ? '/' : getPermalink(
													$this->type,
													$this->parent,
													$this->slug
												)),
												'target' => '_blank',
												'rel' => 'noreferrer noopener',
												'content' => 'View'
											)) :
											domTag('a', array(
												'href' => '/?id=' . $this->id . '&preview=true',
												'target' => '_blank',
												'rel' => 'noreferrer noopener',
												'content' => 'Preview'
											));
										
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
								<?php
								if($this->type_data['hierarchical']) {
									?>
									<div class="block">
										<h2>Attributes</h2>
										<div class="row">
											<?php
											// Parent
											echo domTag('label', array(
												'for' => 'parent-field',
												'content' => 'Parent'
											));
											echo domTag('select', array(
												'id' => 'parent-field',
												'class' => 'select-input',
												'name' => 'parent',
												'content' => domTag('option', array(
													'value' => 0,
													'content' => '(none)'
												)) .
												$this->getParentList(
													$this->type,
													$this->parent,
													$this->id
												)
											));
											?>
										</div>
										<div class="row">
											<?php
											// Template
											echo domTag('label', array(
												'for' => 'template-field',
												'content' => 'Template'
											));
											echo domTag('select', array(
												'id' => 'template-field',
												'class' => 'select-input',
												'name' => 'template',
												'content' => domTag('option', array(
													'value' => 'default',
													'content' => 'Default'
												)). $this->getTemplateList($this->id)
											));
											?>
										</div>
									</div>
									<?php
								} else {
									if(!empty($this->tax_data)) {
										foreach($this->tax_data as $tax) {
											?>
											<div class="block">
												<h2><?php echo $tax['label']; ?></h2>
												<div class="row">
													<?php
													// Terms list
													echo $this->getTermsList($tax['name'], $this->id);
													?>
												</div>
											</div>
											<?php
										}
									}
								}
								
								if(getSetting('enable_comments') && $this->type_data['comments']) {
									?>
									<div class="block">
										<h2>Comments</h2>
										<div class="row">
											<?php
											// Enable comments
											echo domTag('input', array(
												'type' => 'checkbox',
												'class' => 'checkbox-input',
												'name' => 'comments',
												'value' => $meta['comment_status'],
												'checked' => $meta['comment_status'],
												'label' => array(
													'class' => 'checkbox-label',
													'content' => domTag('span', array(
														'content' => 'Enable comments'
													))
												)
											));
											?>
										</div>
									</div>
									<?php
								}
								?>
								<div class="block">
									<h2>Featured Image</h2>
									<div class="row">
										<div class="image-wrap<?php echo !empty($meta['feat_image']) ?
											' visible' : ''; ?>" style="width: <?php echo $width ?? 0; ?>px;">
											<?php
											// Featured image thumbnail
											echo getMedia($meta['feat_image'], array(
												'data-field' => 'thumb'
											));
											
											// Remove image button
											echo domTag('span', array(
												'class' => 'image-remove',
												'title' => 'Remove',
												'content' => domTag('i', array(
													'class' => 'fa-solid fa-xmark'
												))
											));
											?>
										</div>
										<?php
										// Featured image (hidden)
										echo domTag('input', array(
											'type' => 'hidden',
											'name' => 'feat_image',
											'value' => $meta['feat_image'],
											'data-field' => 'id'
										));
										
										// Choose image
										echo domTag('a', array(
											'class' => 'modal-launch',
											'href' => 'javascript:void(0)',
											'data-type' => 'image',
											'content' => 'Choose Image',
										));
										?>
									</div>
								</div>
							</div>
							<div class="metadata">
								<div class="block">
									<h2>Metadata</h2>
									<div class="row">
										<?php
										// Meta title
										echo domTag('label', array(
											'for' => 'meta-title-field',
											'content' => 'Title'
										));
										echo domTag('br');
										echo domTag('input', array(
											'id' => 'meta-title-field',
											'class' => 'text-input',
											'name' => 'meta_title',
											'value' => ($meta['title'] ?? '')
										));
										?>
									</div>
									<div class="row">
										<?php
										// Meta description
										echo domTag('label', array(
											'for' => 'meta-description-field',
											'content' => 'Description'
										));
										echo domTag('br');
										echo domTag('textarea', array(
											'id' => 'meta-description-field',
											'class' => 'textarea-input',
											'name' => 'meta_description',
											'cols' => 30,
											'rows' => 4,
											'content' => ($meta['description'] ?? '')
										));
										?>
									</div>
									<div class="row">
										<?php
										// Index post
										echo domTag('input', array(
											'type' => 'checkbox',
											'class' => 'checkbox-input',
											'name' => 'index_post',
											'value' => $meta['index_post'],
											'checked' => $meta['index_post'],
											'label' => array(
												'class' => 'checkbox-label',
												'content' => domTag('span', array(
													'content' => 'Index ' . strtolower($this->type_data['labels']['name_singular'])
												))
											)
										));
										?>
									</div>
								</div>
							</div>
						</form>
					</div>
					<?php
					include_once PATH . ADMIN . INC . '/modal-upload.php';
				}
			}
		}
	}
	
	/**
	 * Duplicate a post.
	 * @since 1.3.7-beta
	 *
	 * @access public
	 */
	public function duplicatePost(): void {
		global $rs_query;
		
		if(empty($this->id) || $this->id <= 0) {
			redirect(ADMIN_URI);
		} else {
			if(empty($this->type)) {
				redirect(ADMIN_URI);
			} elseif($this->type === 'media') {
				redirect('media.php?id=' . $this->id . '&action=edit');
			} elseif($this->type === 'widget') {
				redirect('widgets.php?id=' . $this->id . '&action=edit');
			} else {
				if($this->isTrash($this->id)) {
					redirect(ADMIN_URI . ($this->type !== 'post' ? '?type=' . $this->type . '&' : '?') .
						'status=trash');
				} else {
					$this->pageHeading();
					?>
					<div class="data-form-wrap clear">
						<form class="data-form" action="" method="post" autocomplete="off">
							<table class="form-table">
								<?php
								// Original post
								echo formRow('Original Post', array(
									'tag' => 'input',
									'id' => 'original-post-field',
									'class' => 'text-input disabled',
									'name' => 'original_post',
									'value' => $this->title,
									'disabled' => 1
								));
								
								$new_title = 'Copy of ' . $this->title;
								
								// New post title
								echo formRow(array('New Title', true), array(
									'tag' => 'input',
									'id' => 'title-field',
									'class' => 'text-input required invalid init',
									'name' => 'title',
									'value' => $new_title
								));
								
								// New post slug
								echo formRow(array('New Slug', true), array(
									'tag' => 'input',
									'id' => 'slug-field',
									'class' => 'text-input required invalid init',
									'name' => 'slug',
									'value' => sanitize(str_replace(' ', '-', $new_title))
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
									'value' => 'Duplicate ' . $this->type_data['labels']['name_singular']
								));
								?>
							</table>
						</form>
					</div>
					<?php
				}
			}
		}
	}
	
	/**
	 * Update a post's status.
	 * @since 1.2.9-beta
	 *
	 * @access public
	 * @param string $status -- The post's status.
	 * @param int $id (optional) -- The post's id.
	 */
	public function updatePostStatus(string $status, int $id = 0): void {
		global $rs_query;
		
		if($id !== 0) $this->id = $id;
		
		if(empty($this->id) || $this->id <= 0) {
			redirect(ADMIN_URI);
		} else {
			$type = $rs_query->selectField($this->table, $this->px . 'type', array(
				$this->px . 'id' => $this->id
			));
			
			if($type === $this->type_data['name']) {
				if($status === 'published' || $status === 'private') {
					$db_status = $rs_query->selectField($this->table, $this->px . 'status', array(
						$this->px . 'id' => $this->id
					));
					
					if($db_status !== $status) {
						$rs_query->update($this->table, array(
							$this->px . 'created' => 'NOW()',
							$this->px . 'status' => $status
						), array(
							$this->px . 'id' => $this->id
						));
					} else {
						$rs_query->update($this->table, array(
							$this->px . 'status' => $status
						), array(
							$this->px . 'id' => $this->id
						));
					}
				} else {
					$rs_query->update($this->table, array(
						$this->px . 'created' => null,
						$this->px . 'status' => $status
					), array(
						$this->px . 'id' => $this->id
					));
				}
			}
		}
	}
	
	/**
	 * Send a post to the trash.
	 * @since 1.4.6-alpha
	 *
	 * @access public
	 */
	public function trashPost(): void {
		$this->updatePostStatus('trash');
		
		redirect($this->type_data['menu_link']);
	}
	
	/**
	 * Restore a post from the trash.
	 * @since 1.4.6-alpha
	 *
	 * @access public
	 */
	public function restorePost(): void {
		$this->updatePostStatus('draft');
		
		redirect($this->type_data['menu_link'] . ($this->type !== 'post' ? '&' : '?') . 'status=trash');
	}
	
	/**
	 * Delete a post.
	 * @since 1.4.7-alpha
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
			
			$rs_query->delete('postmeta', array(
				'pm_post' => $this->id
			));
			
			$relationships = $rs_query->select('term_relationships', '*', array(
				'tr_post' => $this->id
			));
			
			foreach($relationships as $relationship) {
				$rs_query->delete('term_relationships', array(
					'tr_id' => $relationship['tr_id']
				));
				
				$count = $rs_query->selectRow('term_relationships', 'COUNT(*)', array(
					'tr_term' => $relationship['tr_term']
				));
				
				$rs_query->update('terms', array(
					'tr_count' => $count
				), array(
					'tr_id' => $relationship['tr_term']
				));
			}
			
			$rs_query->delete('comments', array(
				'c_post' => $this->id
			));
			
			$menu_items = $rs_query->select('postmeta', 'pm_post', array(
				'pm_key' => 'post_link',
				'pm_value' => $this->id
			));
			
			// Set any menu items associated with the post to invalid
			foreach($menu_items as $menu_item) {
				$rs_query->update($this->table, array(
					$this->px . 'status' => 'invalid'
				), array(
					$this->px . 'id' => $menu_item['pm_post']
				));
			}
			
			redirect($this->type_data['menu_link'] . ($this->type !== 'post' ? '&' : '?') .
				'status=trash&exit_status=del_success');
		}
	}
	
	/*------------------------------------*\
		VALIDATION
	\*------------------------------------*/
	
	/**
	 * Validate the form data.
	 * @since 1.4.7-alpha
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
		
		if($this->slugExists($slug, $this->id))
			$slug = getUniquePostSlug($slug);
		
		if($this->action === 'duplicate') {
			// Fetch the old post data for duplication
			$old_post = $rs_query->selectRow($this->table, '*', array(
				$this->px . 'id' => $this->id
			));
			
			$old_postmeta = $rs_query->select('postmeta', '*', array(
				'pm_post' => $this->id
			));
			
			$old_term_relationships = $rs_query->select('term_relationships', '*', array(
				'tr_post' => $this->id
			));
		} else {
			$valid_statuses = array('draft', 'published', 'private');
			
			if(!in_array($data['status'], $valid_statuses, true))
				$data['status'] = 'draft';
			
			switch($data['status']) {
				case 'draft':
					$is_published = false;
					break;
				case 'published': case 'private':
					$is_published = true;
					break;
			}
			
			$postmeta = array(
				'title' => $data['meta_title'],
				'description' => $data['meta_description'],
				'feat_image' => $data['feat_image'],
				'index_post' => (isset($data['index_post']) ? 1 : 0)
			);
			
			if(isset($data['template'])) $postmeta['template'] = $data['template'];
			
			if($this->type_data['comments'])
				$postmeta['comment_status'] = isset($data['comments']) ? 1 : 0;
		}
		
		switch($this->action) {
			case 'create':
				// Check whether a date has been provided and is valid
				if(!empty($data['created'][0]) && !empty($data['created'][1]) && $data['created'][0] >= '1000-01-01')
					$data['created'] = implode(' ', $data['created']);
				else
					$data['created'] = 'NOW()';
				
				if(!$this->type_data['hierarchical']) $data['parent'] = 0;
				
				$insert_id = $rs_query->insert($this->table, array(
					$this->px . 'title' => $data['title'],
					$this->px . 'author' => $data['author'],
					$this->px . 'created' => ($is_published ? $data['created'] : null),
					$this->px . 'modified' => $data['date'],
					$this->px . 'content' => $data['content'],
					$this->px . 'status' => $data['status'],
					$this->px . 'slug' => $slug,
					$this->px . 'parent' => $data['parent'],
					$this->px . 'type' => $data['type']
				));
				
				if(isset($postmeta['comment_status'])) $postmeta['comment_count'] = 0;
				
				foreach($postmeta as $key => $value) {
					$rs_query->insert('postmeta', array(
						'pm_post' => $insert_id,
						'pm_key' => $key,
						'pm_value' => $value
					));
				}
				
				if(!empty($data['terms'])) {
					// Create new relationships
					foreach($data['terms'] as $term) {
						$rs_query->insert('term_relationships', array(
							'tr_term' => $term,
							'tr_post' => $insert_id
						));
						
						$count = $rs_query->selectRow('term_relationships', 'COUNT(*)', array(
							'tr_term' => $term
						));
						
						$rs_query->update('terms', array(
							't_count' => $count
						), array(
							't_id' => $term
						));
					}
				}
				
				redirect(ADMIN_URI . '?id=' . $insert_id . '&action=edit&exit_status=create_success');
				break;
			case 'edit':
				// Check whether a date has been provided and is valid
				if(!empty($data['created'][0]) && !empty($data['created'][1]) && $data['created'][0] >= '1000-01-01')
					$data['created'] = implode(' ', $data['created']);
				else
					$data['created'] = null;
				
				if(!$this->type_data['hierarchical']) $data['parent'] = 0;
				
				$rs_query->update($this->table, array(
					$this->px . 'title' => $data['title'],
					$this->px . 'author' => $data['author'],
					$this->px . 'created' => ($is_published ? $data['created'] : null),
					$this->px . 'modified' => 'NOW()',
					$this->px . 'content' => $data['content'],
					$this->px . 'status' => $data['status'],
					$this->px . 'slug' => $slug,
					$this->px . 'parent' => $data['parent']
				), array(
					$this->px . 'id' => $this->id
				));
				
				foreach($postmeta as $key => $value) {
					$rs_query->update('postmeta', array(
						'pm_value' => $value
					), array(
						'pm_post' => $this->id,
						'pm_key' => $key
					));
				}
				
				$relationships = $rs_query->select('term_relationships', '*', array(
					'tr_post' => $this->id
				));
				
				foreach($relationships as $relationship) {
					// Delete any unused relationships
					if(empty($data['terms']) || !in_array($relationship['tr_term'], $data['terms'], true)) {
						$rs_query->delete('term_relationships', array(
							'tr_id' => $relationship['tr_id']
						));
						
						$count = $rs_query->selectRow('term_relationships', 'COUNT(*)', array(
							'tr_term' => $relationship['tr_term']
						));
						
						$rs_query->update('terms', array(
							't_count' => $count
						), array(
							't_id' => $relationship['tr_term']
						));
					}
				}
				
				if(!empty($data['terms'])) {
					foreach($data['terms'] as $term) {
						$relationship = $rs_query->selectRow('term_relationships', 'COUNT(*)', array(
							'tr_term' => $term,
							'tr_post' => $this->id
						));
						
						// Skip existing relationships, otherwise create a new one
						if($relationship) {
							continue;
						} else {
							$rs_query->insert('term_relationships', array(
								'tr_term' => $term,
								'tr_post' => $this->id
							));
							
							$count = $rs_query->select('term_relationships', 'COUNT(*)', array(
								'tr_term' => $term
							));
							
							$rs_query->update('terms', array(
								't_count' => $count
							), array(
								't_id' => $term
							));
						}
					}
				}
				
				foreach($data as $key => $value) $this->$key = $value;
				
				redirect(ADMIN_URI . '?id=' . $this->id . '&action=edit&exit_status=edit_success');
				break;
			case 'duplicate':
				$insert_id = $rs_query->insert($this->table, array(
					$this->px . 'title' => $data['title'],
					$this->px . 'author' => $old_post['p_author'],
					$this->px . 'created' => null,
					$this->px . 'modified' => $old_post['p_modified'],
					$this->px . 'content' => $old_post['p_content'],
					$this->px . 'status' => 'draft', // Set new post to a draft so the user has a chance to make changes before it goes live
					$this->px . 'slug' => $slug,
					$this->px . 'parent' => $old_post['p_parent'],
					$this->px . 'type' => $old_post['p_type']
				));
				
				foreach($old_postmeta as $meta) {
					// Reset comments to zero
					if($meta['pm_key'] === 'comment_count') $meta['pm_value'] = 0;
					
					$rs_query->insert('postmeta', array(
						'pm_post' => $insert_id,
						'pm_key' => $meta['pm_key'],
						'pm_value' => $meta['pm_value']
					));
				}
				
				if(!empty($old_term_relationships)) {
					foreach($old_term_relationships as $relationship) {
						$rs_query->insert('term_relationships', array(
							'tr_term' => $relationship['tr_term'],
							'tr_post' => $insert_id
						));
						
						$count = $rs_query->selectRow('term_relationships', 'COUNT(*)', array(
							'tr_term' => $relationship['tr_term']
						));
						
						$rs_query->update('terms', array(
							't_count' => $count
						), array(
							't_id' => $relationship['tr_term']
						));
					}
				}
				
				redirect(ADMIN_URI . '?id=' . $insert_id . '&action=edit&exit_status=dup_success');
				break;
		}
	}
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Construct the page heading.
	 * @since 1.3.13-beta
	 *
	 * @access public
	 */
	public function pageHeading(): void {
		switch($this->action) {
			case 'create':
				$title = $this->type_data['labels']['create_item'];
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			case 'edit':
				$title = $this->type_data['labels']['edit_item'];
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			case 'duplicate':
				$title = $this->type_data['labels']['duplicate_item'];
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			default:
				$title = $this->type_data['label'];
				$type = $this->type_data['name'];
				$status = $_GET['status'] ?? 'all';
				$search = $_GET['search'] ?? null;
				$term = $_GET['term'] ?? '';
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
				if(userHasPrivilege('can_create_' . str_replace(' ', '_', $this->type_data['labels']['name_lowercase']))) {
					echo actionLink('create', array(
						'type' => ($type === 'post' ? null : $type),
						'classes' => 'button',
						'caption' => 'Create New'
					));
				}
				
				// Search
				recordSearch(array(
					'type' => $type,
					'status' => $status
				));
				
				// Info
				adminInfo();
				
				echo domTag('hr');
				
				// Exit notices
				if(isset($_GET['exit_status']))
					echo $this->exitNotice($_GET['exit_status']);
				?>
				<ul class="status-nav">
					<?php
					$keys = array('all', 'published', 'draft', 'private', 'trash');
					$count = array();
					
					foreach($keys as $key) {
						if($key === 'all') {
							if(!is_null($search) && $key === $status)
								$count[$key] = $this->getPostCount($type, '', $search);
							else
								$count[$key] = $this->getPostCount($type);
						} else {
							if(!is_null($search) && $key === $status)
								$count[$key] = $this->getPostCount($type, $key, $search);
							else
								$count[$key] = $this->getPostCount($type, $key);
						}
					}
					
					// Statuses
					foreach($count as $key => $value) {
						echo domTag('li', array(
							'content' => domTag('a', array(
								'href' => ADMIN_URI . '?type=' . $type . ($key === 'all' ? '' : '&status=' . $key),
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
				if(!empty($term)) {
					$t = str_replace('-', '_', $term);
					$count[$t] = $this->getPostCount($type, '', '', $term);
					
					$ct = $count[$t] . ' ' . ($count[$t] === 1 ? 'entry' : 'entries');
				} else {
					$ct = $count[$status] . ' ' . ($count[$status] === 1 ? 'entry' : 'entries');
				}
				
				echo domTag('div', array(
					'class' => 'entry-count status',
					'content' => $ct
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
		$post_type = $this->type_data['labels']['name_singular'];
		
		return exitNotice(match($exit_status) {
			'create_success' => 'The ' . strtolower($post_type) . ' was successfully created. ' . domTag('a', array(
				'href' => $this->type_data['menu_link'],
				'content' => 'Return to list'
			)) . '?',
			'edit_success' => $post_type . ' updated! ' . domTag('a', array(
				'href' => $this->type_data['menu_link'],
				'content' => 'Return to list'
			)) . '?',
			'dup_success' => 'The ' . strtolower($post_type) . ' was successfully duplicated.',
			'del_success' => 'The ' . strtolower($post_type) . ' was successfully deleted.',
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
			$type_name = str_replace(' ', '_', $this->type_data['labels']['name_lowercase']);
			
			if(userHasPrivilege('can_edit_' . $type_name)) {
				$statuses = array('published', 'draft', 'private', 'trash');
				$content = '';
				
				foreach($statuses as $status) {
					$content .= domTag('option', array(
						'value' => $status,
						'content' => ucfirst($status)
					));
				}
				
				echo domTag('select', array(
					'class' => 'actions',
					'content' => $content
				));
				
				// Update status
				button(array(
					'class' => 'bulk-update',
					'title' => 'Bulk status update',
					'label' => 'Update'
				));
			}
			
			if(userHasPrivilege('can_delete_' . $type_name)) {
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
	
	/**
	 * Check whether a slug exists in the database.
	 * @since 1.4.8-alpha
	 *
	 * @access protected
	 * @param string $slug -- The slug.
	 * @return bool
	 */
	protected function slugExists(string $slug): bool {
		global $rs_query;
		
		if($this->id === 0) {
			return $rs_query->selectRow($this->table, 'COUNT(' . $this->px . 'slug)', array(
				$this->px . 'slug' => $slug
			)) > 0;
		} else {
			return $rs_query->selectRow($this->table, 'COUNT(' . $this->px . 'slug)', array(
				$this->px . 'slug' => $slug,
				$this->px . 'id' => array('<>', $this->id)
			)) > 0;
		}
	}
	
	/**
	 * Check whether a post is in the trash.
	 * @since 1.4.9-alpha
	 *
	 * @access private
	 * @param int $id -- The post's id.
	 * @return bool
	 */
	private function isTrash(int $id): bool {
		global $rs_query;
		
		return $rs_query->selectField($this->table, $this->px . 'status', array(
			$this->px . 'id' => $id
		)) === 'trash';
	}
	
	/**
	 * Check whether a post is a descendant of another post.
	 * @since 1.4.9-alpha
	 *
	 * @access private
	 * @param int $id -- The post's id.
	 * @param int $ancestor -- The post's ancestor.
	 * @return bool
	 */
	private function isDescendant(int $id, int $ancestor): bool {
		global $rs_query;
		
		do {
			$parent = $rs_query->selectField($this->table, $this->px . 'parent', array(
				$this->px . 'id' => $id
			));
			$id = (int)$parent;
			
			if($id === $ancestor) return true;
		} while($id !== 0);
		
		return false;
	}
	
	/**
	 * Fetch a post's metadata.
	 * @since 1.4.10-alpha
	 *
	 * @access protected
	 * @param int $id -- The post's id.
	 * @return array
	 */
	protected function getPostMeta(int $id): array {
		global $rs_query;
		
		$postmeta = $rs_query->select('postmeta', array('pm_key', 'pm_value'), array(
			'pm_post' => $id
		));
		$meta = array();
		
		foreach($postmeta as $metadata) {
			$values = array_values($metadata);
			
			for($i = 0; $i < count($metadata); $i += 2)
				$meta[$values[$i]] = $values[$i + 1];
		}
		
		return $meta;
	}
	
	/**
	 * Construct a list of statuses.
	 * @since 1.3.11-beta
	 *
	 * @access private
	 * @return string
	 */
	private function getStatusList(): string {
		$list = '';
		$statuses = array('draft', 'published', 'private');
		
		foreach($statuses as $status) {
			$list .= domTag('option', array(
				'value' => $status,
				'selected' => ($status === $this->status),
				'content' => ucfirst($status)
			));
		}
		
		return $list;
	}
	
	/**
	 * Fetch a post's author.
	 * @since 1.4.0-alpha
	 *
	 * @access protected
	 * @param int $id -- The post's id.
	 * @return string
	 */
	protected function getAuthor(int $id): string {
		global $rs_query;
		
		return $rs_query->selectField('usermeta', 'um_value', array(
			'um_user' => $id,
			'um_key' => 'display_name'
		));
	}
	
	/**
	 * Construct a list of authors.
	 * @since 1.4.4-alpha
	 *
	 * @access private
	 * @param int $id (optional) -- The post's id.
	 * @return string
	 */
	private function getAuthorList(int $id = 0): string {
		global $rs_query;
		
		$list = '';
		$authors = $rs_query->select('users', array('u_id', 'u_username'), array(), array(
			'order_by' => 'u_username'
		));
		
		foreach($authors as $author) {
			$display_name = $rs_query->selectField('usermeta', 'um_value', array(
				'um_user' => $author['u_id'],
				'um_key' => 'display_name'
			));
			
			$list .= domTag('option', array(
				'value' => $author['u_id'],
				'selected' => ($author['u_id'] === $id),
				'content' => ($display_name === $author['u_username'] ? $display_name :
					$display_name . ' (' . $author['u_username'] . ')')
			));
		}
		
		return $list;
	}
	
	/**
	 * Fetch a post's terms.
	 * @since 1.5.0-alpha
	 *
	 * @access private
	 * @param string $taxonomy -- The term's taxonomy.
	 * @param int $id -- The post's id.
	 * @return string
	 */
	private function getTerms(string $taxonomy, int $id): string {
		global $rs_query;
		
		$terms = array();
		$relationships = $rs_query->select('term_relationships', 'tr_term', array(
			'tr_post' => $id
		));
		
		foreach($relationships as $relationship) {
			$term = $rs_query->selectRow('terms', '*', array(
				't_id' => $relationship['tr_term'],
				't_taxonomy' => getTaxonomyId($taxonomy)
			));
			
			if($term) {
				$terms[] = domTag('a', array(
					'href' => getPermalink($taxonomy, $term['t_parent'], $term['t_slug']),
					'content' => $term['t_name']
				));
			}
		}
		
		return empty($terms) ? '&mdash;' : implode(', ', $terms);
	}
	
	/**
	 * Construct a list of terms.
	 * @since 1.5.2-alpha
	 *
	 * @access private
	 * @param string $taxonomy -- The term's taxonomy.
	 * @param int $id (optional) -- The post's id.
	 * @return string
	 */
	private function getTermsList(string $taxonomy, int $id = 0): string {
		global $rs_query, $taxonomies;
		
		$list = '<ul id="terms-list">';
		$terms = $rs_query->select('terms', array('t_id', 't_name', 't_slug'), array(
			't_taxonomy' => getTaxonomyId($taxonomy)
		), array(
			'order_by' => 't_name'
		));
		
		foreach($terms as $term) {
			$relationship = $rs_query->selectRow('term_relationships', 'COUNT(*)', array(
				'tr_term' => $term['t_id'],
				'tr_post' => $id
			));
			
			$list .= domTag('li', array(
				'content' => domTag('input', array(
					'type' => 'checkbox',
					'class' => 'checkbox-input',
					'name' => 'terms[]',
					'value' => $term['t_id'],
					'checked' => ($relationship || ($id === 0 &&
						$term['t_slug'] === $taxonomies[$taxonomy]['default_term']['slug'])
					),
					'label' => array(
						'class' => 'checkbox-label',
						'content' => domTag('span', array(
							'content' => $term['t_name']
						))
					)
				))
			));
		}
		
		$list .= '</ul>';
		
		return $list;
	}
	
	/**
	 * Fetch a post's parent.
	 * @since 1.4.4-alpha
	 *
	 * @access private
	 * @param int $id -- The post's id.
	 * @return string
	 */
	private function getParent(int $id): string {
		global $rs_query;
		
		$parent = $rs_query->selectField($this->table, $this->px . 'title', array(
			$this->px . 'id' => $id
		));
		
		return empty($parent) ? '&mdash;' : $parent;
	}
	
	/**
	 * Construct a list of parents.
	 * @since 1.4.4-alpha
	 *
	 * @access private
	 * @param string $type -- The post's type.
	 * @param int $parent (optional) -- The post's parent id.
	 * @param int $id (optional) -- The post's id.
	 * @return string
	 */
	private function getParentList(string $type, int $parent = 0, int $id = 0): string {
		global $rs_query;
		
		$list = '';
		$posts = $rs_query->select($this->table, array($this->px . 'id', $this->px . 'title'), array(
			$this->px . 'status' => array('<>', 'trash'),
			$this->px . 'type' => $type
		));
		
		foreach($posts as $post) {
			list($p_id, $p_title) = array(
				$post[$this->px . 'id'],
				$post[$this->px . 'title']
			);
			
			if($id !== 0) {
				// Skip the current post
				if($p_id === $id) continue;
				
				// Skip all descendant posts
				if($this->isDescendant($p_id, $id)) continue;
			}
			
			$list .= domTag('option', array(
				'value' => $p_id,
				'selected' => ($p_id === $parent),
				'content' => $p_title
			));
		}
		
		return $list;
	}
	
	/**
	 * Construct a list of templates.
	 * @since 2.3.3-alpha
	 *
	 * @access private
	 * @param int $id (optional) -- The post's id.
	 * @return string
	 */
	private function getTemplateList(int $id = 0): string {
		global $rs_query;
		
		$templates_path = slash(PATH . THEMES) . getSetting('theme') . '/templates';
		
		if(file_exists($templates_path)) {
			// Fetch all templates in the directory
			$templates = array_diff(scandir($templates_path), array('.', '..'));
			
			$current = $rs_query->selectField('postmeta', 'pm_value', array(
				'pm_post' => $id,
				'pm_key' => 'template'
			));
			
			foreach($templates as $template) {
				$list[] = domTag('option', array(
					'value' => $template,
					'selected' => (isset($current) && $current === $template),
					'content' => ucwords(substr(
						str_replace('-', ' ', $template), 0,
						strpos($template, '.')
					))
				));
			}
			
			$list = implode('', $list);
		}
		
		return $list ?? '';
	}
	
	/**
	 * Fetch the post count based on a specific status or term.
	 * @since 1.4.0-alpha
	 *
	 * @access protected
	 * @param string $type -- The post's type.
	 * @param string $status (optional) -- The post's status.
	 * @param string $search (optional) -- The search query.
	 * @param string $term (optional) -- The term the post is linked to.
	 * @return int
	 */
	protected function getPostCount(string $type, string $status = '', string $search = '', string $term = ''): int {
		global $rs_query;
		
		if(empty($status))
			$db_status = array('<>', 'trash');
		else
			$db_status = $status;
		
		if(!empty($term)) {
			$term_id = (int)$rs_query->selectField('terms', 't_id', array(
				't_slug' => $term
			));
			
			$relationships = $rs_query->select('term_relationships', 'tr_post', array(
				'tr_term' => $term_id
			));
			
			if(count($relationships) > 1) {
				$post_ids = array('IN');
				
				foreach($relationships as $rel)
					$post_ids[] = $rel['tr_post'];
			} elseif(count($relationships) > 0) {
				$post_ids = $relationships[0]['tr_post'];
			} else {
				$post_ids = 0;
			}
			
			return $rs_query->select($this->table, 'COUNT(*)', array(
				$this->px . 'id' => $post_ids,
				$this->px . 'status' => $db_status,
				$this->px . 'type' => $type
			));
		} elseif(!empty($search)) {
			return $rs_query->select($this->table, 'COUNT(*)', array(
				$this->px . 'title' => array('LIKE', '%' . $search . '%'),
				$this->px . 'status' => $db_status,
				$this->px . 'type' => $type
			));
		} else {
			return $rs_query->select($this->table, 'COUNT(*)', array(
				$this->px . 'status' => $db_status,
				$this->px . 'type' => $type
			));
		}
	}
}