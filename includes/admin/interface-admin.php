<?php
/**
 * Interface for admin pages.
 * @since 1.3.9-beta
 *
 * @package ReallySimpleCMS
 * @subpackage Admin
 *
 * ## METHODS ##
 * - public listRecords(): void
 * - public createRecord(): void
 * - public editRecord(): void
 * - public deleteRecord(): void
 * - public pageHeading(): void
 */
namespace Admin;

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
	 * @since 1.3.14-beta
	 */
	public function pageHeading(): void;
}