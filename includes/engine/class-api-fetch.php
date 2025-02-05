<?php
/**
 * Link to the ReallySimpleSystems API.
 * @since 1.4.0-beta_snap-01
 *
 * @package ReallySimpleCMS
 * @subpackage Engine
 *
 * ## VARIABLES ##
 * - private string $endpoint
 *
 * ## METHODS ##
 * - public __construct(string $project)
 * - public getVersion(): string
 * - public getDownload(): string
 */
namespace Engine;

class ApiFetch extends CurlFetch {
	/**
	 * The API endpoint URL.
	 * @since 1.4.0-beta_snap-01
	 *
	 * @access private
	 * @var string
	 */
	private $endpoint;
	
	/**
	 * Class constructor.
	 * @since 1.4.0-beta_snap-01
	 *
	 * @access public
	 * @param string $project -- The project to query.
	 */
	public function __construct(string $project = 'rscms') {
		$this->endpoint = 'https://api.jacefincham.com/' . slash($project);
		
		parent::__construct($this->endpoint);
	}
	
	/**
	 * Fetch the software version.
	 * @since 1.4.0-beta_snap-01
	 *
	 * @access public
	 */
	public function getVersion(): string {
		return $this->curlGet('version');
	}
	
	/**
	 * Fetch the download file.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access public
	 */
	public function getDownload(): string {
		return $this->curlGet('download');
	}
}