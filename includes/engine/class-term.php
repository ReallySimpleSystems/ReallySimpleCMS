<?php
/**
 * Core class used to implement the Term object.
 * This class loads data from the terms table of the database for use on the front end of the CMS.
 * @since 2.4.0-alpha
 *
 * @package ReallySimpleCMS
 * @subpackage Engine
 *
 * ## VARIABLES ##
 * - private string $slug
 * - private string $table
 * - private string $px
 *
 * ## METHODS ##
 * - public __construct(string $slug)
 * GETTER METHODS:
 * - public getTermId(): int
 * - public getTermName(): string
 * - public getTermSlug(int $id): string
 * - public getTermTaxonomy(): string
 * - public getTermParent(): int
 * MISCELLANEOUS:
 * - public getTermUrl(): string
 */
namespace Engine;

class Term {
	/**
	 * The currently queried term's slug.
	 * @since 2.4.0-alpha
	 *
	 * @access private
	 * @var string
	 */
	private $slug;
	
	/**
	 * The associated database table.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $table = 'terms';
	
	/**
	 * The table prefix.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $px = 't_';
	
	/**
	 * Class constructor. Sets the default queried term slug.
	 * @since 2.4.0-alpha
	 *
	 * @access public
	 * @param string $slug (optional) -- The term's slug.
	 */
	public function __construct(string $slug = '') {
		global $rs_query, $taxonomies;
		
		if(!empty($slug)) {
			$this->slug = $slug;
		} else {
			$raw_uri = $_SERVER['REQUEST_URI'];
			$uri = explode('/', $raw_uri);
			
			// Filter out any empty array values
			$uri = array_filter($uri);
			
			// Check whether the last element of the array is the slug
			if(str_starts_with(end($uri), '?')) {
				// Fetch the query string at the end of the array
				$query_string = array_pop($uri);
			}
			
			$this->slug = array_pop($uri);
			$id = $this->getTermId();
			$permalink = getPermalink($this->getTermTaxonomy(), $this->getTermParent(), $this->getTermSlug($id));
			
			if(empty($id)) redirect('/404.php');
			
			if(isset($query_string)) $permalink .= $query_string;
			if($raw_uri !== $permalink) redirect($permalink);
		}
		
		if(!array_key_exists($this->getTermTaxonomy(), $taxonomies)) {
			// Unrecognized taxonomy, abort
			redirect('/404.php');
		}
	}
	
	/*------------------------------------*\
		GETTER METHODS
	\*------------------------------------*/
	
	/**
	 * Fetch the term's id.
	 * @since 2.4.0-alpha
	 *
	 * @access public
	 * @return int
	 */
	public function getTermId(): int {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->table, $this->px . 'id', array(
			$this->px . 'slug' => $this->slug
		));
	}
	
	/**
	 * Fetch the term's name.
	 * @since 2.4.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getTermName(): string {
		global $rs_query;
		
		return $rs_query->selectField($this->table, $this->px . 'name', array(
			$this->px . 'slug' => $this->slug
		));
    }
	
	/**
	 * Fetch the term's slug.
	 * @since 2.4.0-alpha
	 *
	 * @access public
	 * @param int $id -- The term's id.
	 * @return string
	 */
    public function getTermSlug(int $id): string {
		global $rs_query;
		
		return $rs_query->selectField($this->table, $this->px . 'slug', array(
			$this->px . 'id' => $id
		));
    }
	
	/**
	 * Fetch the term's taxonomy.
	 * @since 2.4.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getTermTaxonomy(): string {
		global $rs_query;
		
		$taxonomy = $rs_query->selectField($this->table, $this->px . 'taxonomy', array(
			$this->px . 'slug' => $this->slug
		));
		
		return $rs_query->selectField('taxonomies', 'ta_name', array(
			'ta_id' => $taxonomy
		));
	}
	
	/**
	 * Fetch the term's parent.
	 * @since 2.4.0-alpha
	 *
	 * @access public
	 * @return int
	 */
	public function getTermParent(): int {
		global $rs_query;
		
		return (int)$rs_query->selectField($this->table, $this->px . 'parent', array(
			$this->px . 'slug' => $this->slug
		));
    }
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Fetch the term's full URL.
	 * @since 2.4.0-alpha
	 *
	 * @access public
	 * @return string
	 */
	public function getTermUrl(): string {
		return getSetting('site_url') . getPermalink($this->getTermTaxonomy(), $this->getTermParent(), $this->slug);
    }
}