<?php
/**
 * Handle admin AJAX requests to the server.
 * @since 1.3.8-beta
 *
 * @package ReallySimpleCMS
 */

// Only initialize the base files and functions
define('BASE_INIT', true);

require_once dirname(dirname(__DIR__)) . '/init.php';
require_once ADMIN_FUNC;
require_once RS_FUNC;

// Fetch the user's session data if they're logged in
if(isset($_COOKIE['session']) && isValidSession($_COOKIE['session']))
	$rs_session = getOnlineUser($_COOKIE['session']);

if(isset($_POST)) {
	// Dismiss an admin notice
	if(isset($_POST['dismiss_notice']) && (bool)$_POST['dismiss_notice']) {
		if(isset($rs_session['dismissed_notices']))
			$rs_session['dismissed_notices'][] = $_POST['notice_id'];
		else
			$rs_session['dismissed_notices'] = array($_POST['notice_id']);
		
		$dismissed = serialize($rs_session['dismissed_notices']);
		
		$rs_query->update('usermeta', array(
			'value' => $dismissed
		), array(
			'user' => $rs_session['id'],
			'datakey' => 'dismissed_notices'
		));
	}
}