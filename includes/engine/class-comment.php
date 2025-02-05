<?php
/**
 * Core class used to implement the Comment object.
 * This class loads data from the comments table of the database for use on the front end of the CMS.
 * @since 1.1.0-beta_snap-03
 *
 * @package ReallySimpleCMS
 * @subpackage Engine
 *
 * ## VARIABLES ##
 * - private int $post
 * - private string $table
 * - private string $px
 *
 * ## METHODS ##
 * - public __construct(int $post)
 * GETTER METHODS:
 * - public getCommentAuthor(int $id): string
 * - public getCommentAuthorId(int $id): int
 * - public getCommentDate(int $id): string
 * - public getCommentContent(int $id): string
 * - public getCommentUpvotes(int $id): int
 * - public getCommentDownvotes(int $id): int
 * - public getCommentStatus(int $id): string
 * - public getCommentParent(int $id): int
 * - public getCommentCount(int $post): int
 * - public getCommentPermalink(int $id): string
 * MISCELLANEOUS:
 * - public getCommentReplyBox(): void
 * - public getCommentFeed(): void
 * - public loadComments(int $offset, int $count): void
 * - public createComment(array $data): string
 * - public updateComment(array $data): void
 * - public deleteComment(int $id): void
 * - public incrementVotes(int $id, string $type): int
 * - public decrementVotes(int $id, string $type): int
 */
namespace Engine;

class Comment {
	/**
	 * The post the current comment belongs to.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access private
	 * @var int
	 */
	private $post;
	
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
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $post (optional) -- The post the comment belongs to.
	 */
	public function __construct(int $post = 0) {
		$this->post = $post;
	}
	
	/*------------------------------------*\
		GETTER METHODS
	\*------------------------------------*/
	
	/**
	 * Fetch a comment's author.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return string
	 */
	public function getCommentAuthor(int $id): string {
		global $rs_query;
		
		$author_id = $this->getCommentAuthorId($id);
		
		if($author_id === 0) {
			$author = 'Anonymous';
		} else {
			$author = $rs_query->selectField('usermeta', 'um_value', array(
				'um_user' => $author_id,
				'um_key' => 'display_name'
			));
		}
		
		return $author;
	}
	
	/**
	 * Fetch a comment's author id.
	 * @since 1.1.0-beta_snap-04
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return int
	 */
	public function getCommentAuthorId(int $id): int {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->table, $this->px . 'author', array(
			$this->px . 'id' => $id
		));
	}
	
	/**
	 * Fetch a comment's submission date.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return string
	 */
	public function getCommentDate(int $id): string {
		global $rs_query;
		
		$created = $rs_query->selectField($this->table, $this->px . 'created', array(
			$this->px . 'id' => $id
		));
		
		return formatDate($created, 'j M Y @ g:i A');
	}
	
	/**
	 * Fetch a comment's content.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return string
	 */
	public function getCommentContent(int $id): string {
		global $rs_query;
		
		return $rs_query->selectField($this->table, $this->px . 'content', array(
			$this->px . 'id' => $id
		));
	}
	
	/**
	 * Fetch a comment's number of upvotes.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return int
	 */
	public function getCommentUpvotes(int $id): int {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->table, $this->px . 'upvotes', array(
			$this->px . 'id' => $id
		));
	}
	
	/**
	 * Fetch a comment's number of downvotes.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return int
	 */
	public function getCommentDownvotes(int $id): int {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->table, $this->px . 'downvotes', array(
			$this->px . 'id' => $id
		));
	}
	
	/**
	 * Fetch a comment's status.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return string
	 */
	public function getCommentStatus(int $id): string {
		global $rs_query;
		
		return $rs_query->selectField($this->table, $this->px . 'status', array(
			$this->px . 'id' => $id
		));
	}
	
	/**
	 * Fetch a comment's parent.
	 * @since 1.1.0-beta_snap-04
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return int
	 */
	public function getCommentParent(int $id): int {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->table, $this->px . 'parent', array(
			$this->px . 'id' => $id
		));
	}
	
	/**
	 * Fetch the number of comments assigned to the current post.
	 * @since 1.1.0-beta_snap-04
	 *
	 * @access public
	 * @param int $post -- The post's id.
	 * @return int
	 */
	public function getCommentCount(int $post): int {
		global $rs_query;
		
		return $rs_query->select($this->table, 'COUNT(*)', array(
			$this->px . 'post' => $post
		));
	}
	
	/**
	 * Fetch a comment's permalink.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @return string
	 */
	public function getCommentPermalink(int $id): string {
		global $rs_query;
		
		$post = $rs_query->selectRow('posts', array('p_slug', 'p_parent', 'p_type'), array(
			'p_id' => $this->post
		));
		
		return getPermalink($post['p_type'], $post['p_parent'], $post['p_slug']) . '#comment-' . $id;
	}
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Construct the reply box for a comment feed.
	 * @since 1.1.0-beta_snap-04
	 *
	 * @access public
	 */
	public function getCommentReplyBox(): void {
		global $rs_post, $session, $post_types;
		
		// Check whether comments are enabled
		if(getSetting('enable_comments') && $post_types[$rs_post->getPostType()]['comments'] &&
			$rs_post->getPostMeta('comment_status')
		) {
			if(!is_null($session) || (is_null($session) && getSetting('allow_anon_comments'))) {
				echo domTag('div', array(
					'id' => 'comments-reply',
					'class' => 'textarea-wrap',
					'content' => domTag('div', array(
						'id' => 'reply-to'
					)) . domTag('input', array(
						'type' => 'hidden',
						'name' => 'post',
						'value' => $rs_post->getPostId()
					)) . domTag('input', array(
						'type' => 'hidden',
						'name' => 'replyto',
						'value' => 0
					)) . domTag('textarea', array(
						'class' => 'textarea-input',
						'cols' => 60,
						'rows' => 8,
						'placeholder' => 'Leave a comment'
					)) . domTag('button', array(
						'type' => 'submit',
						'class' => 'submit-comment button',
						'disabled' => 1,
						'content' => 'Submit'
					))
				));
			}
		}
	}
	
	/**
	 * Construct a comment feed.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 */
	public function getCommentFeed(): void {
		?>
		<div class="comments-wrap">
			<?php $this->loadComments(); ?>
		</div>
		<?php
	}
	
	/**
	 * Load a specified number of comments.
	 * @since 1.2.2-beta
	 *
	 * @access public
	 * @param int $offset (optional) -- The offset (starting point).
	 * @param int $count (optional) -- The number of comments to load.
	 */
	public function loadComments(int $offset = 0, int $count = 10): void {
		global $rs_query, $rs_post, $session, $post_types;
		
		$per_page = 10;
		
		$comments = $rs_query->select($this->table, $this->px . 'id', array(
			$this->px . 'post' => $this->post,
			$this->px . 'status' => 'approved'
		), array(
			'order_by' => $this->px . 'created',
			'order' => 'DESC',
			'limit' => array($offset, $count)
		));
		
		$approved = $rs_query->select($this->table, 'COUNT(*)', array(
			$this->px . 'post' => $this->post,
			$this->px . 'status' => 'approved'
		));
		
		if(empty($comments)) {
			echo domTag('p', array(
				'content' => 'No comments to display.'
			));
		} else {
			echo domTag('span', array(
				'class' => 'count hidden',
				'data-comments' => $offset + $count
			));
			
			foreach($comments as $comment) {
				$id = $comment[$this->px . 'id'];
				$parent = $this->getCommentParent($id);
				?>
				<div id="comment-<?php echo $id; ?>" class="comment">
					<p class="meta">
						<?php
						// Meta
						echo domTag('span', array(
							'class' => 'permalink',
							'content' => domTag('a', array(
								'href' => $this->getCommentPermalink($id),
								'content' => '#' . $id
							))
						)) . '&ensp;' . domTag('span', array(
							'class' => 'author',
							'content' => $this->getCommentAuthor($id)
						)) . '&ensp;' . domTag('span', array(
							'class' => 'created',
							'content' => $this->getCommentDate($id)
						));
						
						// Reply to
						if($parent !== 0) {
							echo domTag('span', array(
								'class' => 'replyto',
								'content' => 'replying to ' . domTag('a', array(
									'href' => $this->getCommentPermalink($parent),
									'content' => '#' . $parent
								))
							));
						}
						?>
					</p>
					<?php
					// Content
					echo domTag('div', array(
						'class' => 'content',
						'content' => nl2br($this->getCommentContent($id))
					));
					?>
					<p class="actions">
						<?php
						// Actions
						echo domTag('span', array(
							// Upvote
							'class' => 'upvote',
							'content' => domTag('span', array(
								'content' => $this->getCommentUpvotes($id)
							)) . ' ' . domTag('a', array(
								'href' => '#',
								'data-id' => $id,
								'data-vote' => 0,
								'title' => 'Upvote',
								'content' => domTag('i', array(
									'class' => 'fa-solid fa-thumbs-up'
								))
							))
						)) . ' &bull; ' . domTag('span', array(
							// Downvote
							'class' => 'downvote',
							'content' => domTag('span', array(
								'content' => $this->getCommentDownvotes($id)
							)) . ' ' . domTag('a', array(
								'href' => '#',
								'data-id' => $id,
								'data-vote' => 0,
								'title' => 'Downvote',
								'content' => domTag('i', array(
									'class' => 'fa-solid fa-thumbs-down'
								))
							))
						));
						
						if(getSetting('enable_comments') && $post_types[$rs_post->getPostType()]['comments'] &&
							$rs_post->getPostMeta('comment_status')
						) {
							if(!is_null($session) || (is_null($session) && getSetting('allow_anon_comments'))) {
								// Reply to
								echo ' &bull; ' . domTag('span', array(
									'class' => 'reply',
									'content' => domTag('a', array(
										'href' => '#',
										'data-replyto' => $id,
										'content' => 'Reply'
									))
								));
							}
						}
						
						if(!is_null($session) && ($session['id'] === $this->getCommentAuthorId($id) ||
							userHasPrivilege('can_edit_comments'))
						) {
							// Edit
							echo ' &bull; ' . domTag('span', array(
								'class' => 'edit',
								'content' => domTag('a', array(
									'href' => '#',
									'data-id' => $id,
									'content' => 'Edit'
								))
							));
						}
						
						if(!is_null($session) && ($session['id'] === $this->getCommentAuthorId($id) ||
							userHasPrivilege('can_delete_comments'))
						) {
							// Delete
							echo ' &bull; ' . domTag('span', array(
								'class' => 'delete',
								'content' => domTag('a', array(
									'href' => '#',
									'data-id' => $id,
									'content' => 'Delete'
								))
							));
						}
						?>
					</p>
				</div>
				<?php
			}
			
			if($approved > $per_page && $approved > $offset + $count) {
				echo domTag('button', array(
					'class' => 'load button',
					'content' => 'Load more'
				));
			}
		}
	}
	
	/**
	 * Create a new comment.
	 * @since 1.1.0-beta_snap-04
	 *
	 * @access public
	 * @param array $data -- The comment data.
	 * @return string
	 */
	public function createComment(array $data): string {
		global $rs_query, $session;
		
		if(!empty($data['content'])) {
			$status = getSetting('auto_approve_comments') ? 'approved' : 'pending';
			
			$rs_query->insert($this->table, array(
				$this->px . 'post' => $data['post'],
				$this->px . 'author' => ($session['id'] ?? 0),
				$this->px . 'created' => 'NOW()',
				$this->px . 'content' => htmlspecialchars($data['content']),
				$this->px . 'status' => $status,
				$this->px . 'parent' => $data['replyto']
			));
			
			$approved = $rs_query->select($this->table, 'COUNT(*)', array(
				$this->px . 'post' => $data['post'],
				$this->px . 'status' => 'approved'
			));
			
			$rs_query->update('postmeta', array('pm_value' => $approved), array(
				'pm_post' => $data['post'],
				'pm_key' => 'comment_count'
			));
			
			return domTag('p', array(
				'style' => 'margin-top: 0;',
				'content' => 'Your comment was submitted' . (!getSetting('auto_approve_comments') ? ' for review' : '') . '!'
			));
		}
		
		return domTag('p', array(
			'style' => 'margin-top: 0;',
			'content' => 'There was a problem submitting your comment.'
		));
	}
	
	/**
	 * Update an existing comment.
	 * @since 1.1.0-beta_snap-05
	 *
	 * @access public
	 * @param array $data -- The comment data.
	 */
	public function updateComment(array $data): void {
		global $rs_query;
		
		$rs_query->update($this->table, array(
			$this->px . 'content' => $data['content']
		), array(
			$this->px . 'id' => $data['id']
		));
	}
	
	/**
	 * Delete a comment.
	 * @since 1.1.0-beta_snap-04
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 */
	public function deleteComment(int $id): void {
		global $rs_query;
		
		$post = $rs_query->selectField($this->table, $this->px . 'post', array(
			$this->px . 'id' => $id
		));
		
		$rs_query->delete($this->table, array(
			$this->px . 'id' => $id
		));
		
		$count = $rs_query->select($this->table, 'COUNT(*)', array(
			$this->px . 'post' => $post,
			$this->px . 'status' => 'approved'
		));
		
		$rs_query->update('postmeta', array('pm_value' => $count), array(
			'pm_post' => $post,
			'pm_key' => 'comment_count'
		));
	}
	
	/**
	 * Increment (increase) the vote count.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @param string $type -- The vote type (upvotes).
	 * @return int
	 */
	public function incrementVotes(int $id, string $type): int {
		global $rs_query;
		
		$votes = $rs_query->selectField($this->table, $this->px . $type, array(
			$this->px . 'id' => $id
		));
		
		$rs_query->update($this->table, array(
			$this->px . $type => ++$votes
		), array(
			$this->px . 'id' => $id
		));
		
		return $votes;
	}
	
	/**
	 * Decrement (decrease) the vote count.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param int $id -- The comment's id.
	 * @param string $type -- The vote type (downvotes).
	 * @return int
	 */
	public function decrementVotes(int $id, string $type): int {
		global $rs_query;
		
		$votes = $rs_query->selectField($this->table, $this->px . $type, array(
			$this->px . 'id' => $id
		));
		
		$rs_query->update($this->table, array(
			$this->px . $type => --$votes
		), array(
			$this->px . 'id' => $id
		));
		
		return $votes;
	}
}