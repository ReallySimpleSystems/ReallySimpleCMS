<?php
/**
 * Perform system updates.
 * @since 1.1.0-beta_snap-01
 *
 * @package ReallySimpleCMS
 */

global $rs_update;

requireFiles(array(
	PATH . INC . '/update-db.php', // Database updater
	PATH . INC . '/backward-compat.php' // Backward compatibility changes
));

$rs_update = new \Engine\Update;