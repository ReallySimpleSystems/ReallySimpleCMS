<?php
/**
 * Interface for admin pages.
 * @since 1.3.9-beta
 *
 * @package ReallySimpleCMS
 */
interface AdminInterface {
	/**
	 * Construct a list of all records in the database.
	 * @since 1.3.10-beta
	 */
	public function listRecords(): void;
	
	/**
	 * Create a new record.
	 * @since 1.3.10-beta
	 */
	public function createRecord(): void;
	
	/**
	 * Edit an existing record.
	 * @since 1.3.10-beta
	 */
	public function editRecord(): void;
	
	/**
	 * Delete an existing record.
	 * @since 1.3.10-beta
	 */
	public function deleteRecord(): void;
	
	/**
	 * Construct the page heading.
	 * @since 1.4.0-beta_snap-02
	 */
	public function pageHeading(): void;
}