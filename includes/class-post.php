<?php
/**
 * Core class used to implement the Post object.
 * This class loads data from the posts table of the database for use on the front end of the CMS.
 * @since 1.0.2-alpha
 *
 * @package ReallySimpleCMS
 *
 * ## VARIABLES ##
 * - private string $slug
 * - private array $type_data
 * - private array $tax_data
 * - private array $tables
 * - private array $px
 *
 * ## METHODS ##
 * - public __construct(string $slug)
 * GETTER METHODS:
 * - public getPostId(): int
 * - public getPostTitle(): string
 * - public getPostAuthor(): string
 * - public getPostDate(): string
 * - public getPostModDate(): string
 * - public getPostContent(): string
 * - public getPostStatus(): string
 * - public getPostSlug(int $id): string
 * - public getPostParent(): int
 * - public getPostType(): string
 * - public getPostFeaturedImage(): string
 * - public getPostMeta(string $key): string
 * - public getPostTerms(string $taxonomy, bool $linked): array
 * - public getPostComments(bool $feed_only): void
 * - public getPostPermalink(string $type, int $parent, string $slug): string
 * MISCELLANEOUS:
 * - public getPostUrl(): string
 * - public postHasFeaturedImage(): bool
 */
class Post {
	/**
	 * The currently queried post's slug.
	 * @since 2.2.3-alpha
	 *
	 * @access private
	 * @var string
	 */
	private $slug;
	
	/**
	 * The currently queried post's type data.
	 * @since 1.0.6-beta
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
	 * The associated database tables.
	 * 0 => `posts`, 1 => `postmeta`
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var array
	 */
	private $tables = array('posts', 'postmeta');
	
	/**
	 * The table prefixes.
	 * 0 => `p_`, 1 => `pm_`
	 * @since 1.3.14-beta
	 *
	 * @access private
	 * @var array
	 */
	private $px = array('p_', 'pm_');
	
	/**
	 * Class constructor. Sets the default queried post slug.
	 * @since 2.2.3-alpha
	 *
	 * @access public
	 * @param string $slug (optional) -- The post's slug.
	 */
	public function __construct(string $slug = '') {
		global $rs_query, $rs_session, $rs_post_types, $rs_taxonomies;
		
		if(!empty($slug)) {
			$this->slug = $slug;
			$status = $this->getPostStatus(); // This line does nothing ??
		} else {
			$raw_uri = $_SERVER['REQUEST_URI'];
			
			// Home page
			if($raw_uri === '/' || (str_starts_with($raw_uri, '/?') && !isset($_GET['preview']))) {
				$home_page = $rs_query->selectField('settings', 'value', array(
					'name' => 'home_page'
				));
				
				$this->slug = $this->getPostSlug($home_page);
				$status = $this->getPostStatus();
				
				switch($status) {
					case 'draft':
					case 'trash':
						$is_published = false;
						break;
					case 'published':
					case 'private':
						$is_published = true;
						break;
				}
				
				if(!$is_published) {
					if($status === 'draft' && isset($rs_session))
						redirect('/?id=' . $home_page . '&preview=true');
					else
						redirect('/404.php');
				} else {
					if($status === 'private' && !isset($rs_session))
						redirect('/404.php');
				}
			} // All other pages
			 else {
				// Check whether the current post is a preview and the id is valid
				if(isset($_GET['preview']) && $_GET['preview'] === 'true' && isset($_GET['id']) && $_GET['id'] > 0) {
					$this->slug = $this->getPostSlug($_GET['id']);
					$status = $this->getPostStatus();
					
					switch($status) {
						case 'draft':
						case 'trash':
							$is_published = false;
							break;
						case 'published':
						case 'private':
							$is_published = true;
							break;
					}
					
					if($status !== 'draft') {
						if($is_published) {
							// Redirect to the proper URL
							redirect($this->getPostPermalink(
								$this->getPostType(),
								$this->getPostParent(),
								$this->slug
							));
						} else {
							redirect('/404.php');
						}
					}
					
					if(!isset($rs_session)) redirect('/404.php');
				} else {
					$uri = explode('/', $raw_uri);
					
					// Filter out any empty array values
					$uri = array_filter($uri);
					
					// Check whether the last element of the array is the slug
					if(str_starts_with(end($uri), '?')) {
						// Fetch the query string at the end of the array
						$query_string = array_pop($uri);
					}
					
					$this->slug = array_pop($uri);
					$id = $this->getPostId();
					$status = $this->getPostStatus();
					
					switch($status) {
						case 'draft':
							$is_published = false;
							break;
						case 'published':
						case 'private':
							$is_published = true;
							break;
					}
					
					if(!$is_published) {
						if($status === 'draft' && isset($rs_session) && !empty($id))
							redirect('/?id=' . $id . '&preview=true');
						else
							redirect('/404.php');
					} else {
						if($status === 'private' && !isset($rs_session))
							redirect('/404.php');
						
						if(isHomePage($id)) {
							redirect('/');
						} else {
							$permalink = $this->getPostPermalink(
								$this->getPostType(),
								$this->getPostParent(),
								$this->getPostSlug($id)
							);
							
							if(isset($query_string)) $permalink .= $query_string;
							if($raw_uri !== $permalink) redirect($permalink);
						}
					}
				}
			}
		}
		
		if(array_key_exists($this->getPostType(), $rs_post_types)) {
			$this->type_data = $rs_post_types[$this->getPostType()];
			
			// Fetch any associated taxonomy data
			if(!empty($this->type_data['taxonomies'])) {
				foreach($this->type_data['taxonomies'] as $tax) {
					if(array_key_exists($tax, $rs_taxonomies))
						$this->tax_data[] = $rs_taxonomies[$tax];
				}
			}
		} else {
			// Unrecognized post type, abort
			redirect('/404.php');
		}
	}
	
	/*------------------------------------*\
		GETTER METHODS
	\*------------------------------------*/
	
	/**
	 * Fetch the post's id.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return int
	 */
	public function getPostId(): int {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->tables[0], 'id', array(
			'slug' => $this->slug
		));
	}
	
	/**
	 * Fetch the post's title.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostTitle(): string {
		global $rs_query;
		
		return $rs_query->selectField($this->tables[0], 'title', array(
			'slug' => $this->slug
		));
    }
	
	/**
	 * Fetch the post's author.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostAuthor(): string {
		global $rs_query;
		
		$author = $rs_query->selectField($this->tables[0], 'author', array(
			'slug' => $this->slug
		));
		
		return $rs_query->selectField('usermeta', 'value', array(
			'user' => $author,
			'datakey' => 'display_name'
		));
	}
	
	/**
	 * Fetch the post's publish date.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostDate(): string {
		global $rs_query;
		
		$date = $rs_query->selectField($this->tables[0], 'date', array(
			'slug' => $this->slug
		));
		
		if(empty($date)) {
			$date = $rs_query->selectField($this->tables[0], 'modified', array(
				'slug' => $this->slug
			));
		}
		
		return formatDate($date, 'j M Y @ g:i A');
    }
	
	/**
	 * Fetch the post's modified date.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostModDate(): string {
		global $rs_query;
		
		$modified = $rs_query->selectField($this->tables[0], 'modified', array(
			'slug' => $this->slug
		));
		
		return formatDate($modified, 'j M Y @ g:i A');
    }
	
	/**
	 * Fetch the post's content.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostContent(): string {
		global $rs_query;
		
		return $rs_query->selectField($this->tables[0], 'content', array(
			'slug' => $this->slug
		));
    }
	
	/**
	 * Fetch the post's status.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostStatus(): string {
		global $rs_query;
		
		return $rs_query->selectField($this->tables[0], 'status', array(
			'slug' => $this->slug
		));
    }
	
	/**
	 * Fetch the post's slug.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @param int $id -- The post's id.
	 * @return string
	 */
    public function getPostSlug(int $id): string {
		global $rs_query;
		
		return $rs_query->selectField($this->tables[0], 'slug', array(
			'id' => $id
		));
    }
	
	/**
	 * Fetch the post's parent.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return int
	 */
	public function getPostParent(): int {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->tables[0], 'parent', array(
			'slug' => $this->slug
		));
    }
	
	/**
	 * Fetch the post's type.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostType(): string {
		global $rs_query;
		
		return $rs_query->selectField($this->tables[0], 'type', array(
			'slug' => $this->slug
		));
	}
	
	/**
	 * Fetch the post's featured image.
	 * @since 2.2.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostFeaturedImage(): string {
		global $rs_query;
		
		$featured_image = (int)$rs_query->selectField($this->tables[1], 'value', array(
			'post' => $this->getPostId(),
			'datakey' => 'feat_image'
		));
		
		return getMedia($featured_image, array(
			'class' => 'featured-image'
		));
    }
	
	/**
	 * Fetch the post's metadata.
	 * @since 2.2.3-alpha
	 *
	 * @access public
	 * @param string $key -- The meta database key.
	 * @return string
	 */
	public function getPostMeta(string $key): string {
		global $rs_query;
		
		$field = $rs_query->selectField($this->tables[1], 'value', array(
			'post' => $this->getPostId(),
			'datakey' => $key
		));
		
		// Escape double quotes in meta descriptions
		if($key === 'description')
			$field = str_replace('"', '&quot;', $field);
		
		return $field;
    }
	
	/**
	 * Fetch the post's terms.
	 * @since 2.4.1-alpha
	 *
	 * @access public
	 * @param string $taxonomy -- The term's taxonomy.
	 * @param bool $linked (optional) -- Whether to link the terms.
	 * @return array
	 */
	public function getPostTerms(string $taxonomy, bool $linked = true): array {
		global $rs_query;
		
		$terms = array();
		
		$relationships = $rs_query->select('term_relationships', 'term', array(
			'post' => $this->getPostId()
		));
		
		foreach($relationships as $relationship) {
			$slug = $rs_query->selectField('terms', 'slug', array(
				'id' => $relationship['term'],
				'taxonomy' => getTaxonomyId($taxonomy)
			));
			
			$rs_term = getTerm($slug);
			
			$terms[] = $linked ? domTag('a', array(
					'href' => $rs_term->getTermUrl(),
					'content' => $rs_term->getTermName()
				)) : $rs_term->getTermName();
		}
		
		return $terms;
	}
	
	/**
	 * Fetch the post's comments.
	 * @since 1.1.0-beta_snap-03
	 *
	 * @access public
	 * @param bool $feed_only (optional) -- Whether to only display the comment feed.
	 */
	public function getPostComments(bool $feed_only = false): void {
		$rs_comment = new Comment($this->getPostId());
		
		if(!$feed_only) $rs_comment->getCommentReplyBox();
		
		$rs_comment->getCommentFeed();
	}
	
	/**
	 * Fetch the post's permalink.
	 * @since 2.2.5-alpha
	 *
	 * @access public
	 * @param string $type -- The post's type.
	 * @param int $parent -- The post's parent.
	 * @param string $slug (optional) -- The post's slug.
	 * @return string
	 */
	public function getPostPermalink(string $type, int $parent, string $slug = ''): string {
		return getPermalink($type, $parent, $slug);
	}
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Fetch the post's full URL.
	 * @since 2.2.3-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getPostUrl(): string {
		if(isHomePage($this->getPostId())) {
			return slash(getSetting('site_url'));
		} else {
			return getSetting('site_url') . $this->getPostPermalink(
				$this->getPostType(),
				$this->getPostParent(),
				$this->slug
			);
		}
    }
	
	/**
	 * Check whether a post has a featured image.
	 * @since 2.2.4-alpha
	 *
	 * @access public
	 * @return bool
	 */
	public function postHasFeaturedImage(): bool {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->tables[1], 'value', array(
			'post' => $this->getPostId(),
			'datakey' => 'feat_image'
		)) !== 0;
	}
}