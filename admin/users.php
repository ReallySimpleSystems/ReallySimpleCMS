<?php
/**
 * Admin users page.
 * @since 1.1.0-alpha
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$rs_user = new User($id, $action);
?>
<article class="content">
	<?php
	switch($action) {
		case 'create':
			// Create a new user
			userHasPrivilege('can_create_users') ? $rs_user->createRecord() :
				redirect(ADMIN_URI);
			break;
		case 'edit':
			// Edit an existing user
			userHasPrivilege('can_edit_users') ? $rs_user->editRecord() :
				redirect(ADMIN_URI);
			break;
		case 'delete':
			// Delete an existing user
			userHasPrivilege('can_delete_users') ? $rs_user->deleteRecord() :
				redirect(ADMIN_URI);
			break;
		case 'reset_password':
			// Reset a user's password
			userHasPrivilege('can_edit_users') ? $rs_user->resetPassword() :
				redirect(ADMIN_URI);
			break;
		case 'reassign_content':
			// Reassign a user's content
			userHasPrivilege('can_delete_users') ? $rs_user->reassignContent() :
				redirect(ADMIN_URI);
			break;
		default:
			// List all users
			userHasPrivilege('can_view_users') ? $rs_user->listRecords() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';