<?php
/**
 * Interface for admin pages.
 * @since 1.3.9-beta
 *
 * @package ReallySimpleCMS
 *
 * ## METHODS ##
 * LISTS, FORMS, & ACTIONS:
 * - public listRecords(): void
 * - public createRecord(): void
 * - public editRecord(): void
 * - public deleteRecord(): void
 */
interface AdminInterface {
	
	/*------------------------------------*\
		LISTS, FORMS, & ACTIONS
	\*------------------------------------*/
	
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
}