<?php
/**
 * Admin settings page.
 * @since 1.2.6-alpha
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$page = $_GET['page'] ?? 'general';

$rs_settings = new \Admin\Settings($page);
?>
<article class="content">
	<?php
	switch($page) {
		case 'design':
			// Design settings
			userHasPrivilege('can_edit_settings') ? $rs_settings->designSettings() :
				redirect('index.php');
			break;
		case 'user_roles':
			$rs_user_role = new \Admin\UserRole($id, $action, $page);
			
			switch($action) {
				case 'create':
					// Create a new user role
					userHasPrivilege('can_create_user_roles') ? $rs_user_role->createRecord() :
						redirect(ADMIN_URI . '?page=user_roles');
					break;
				case 'edit':
					// Edit an existing user role
					userHasPrivilege('can_edit_user_roles') ? $rs_user_role->editRecord() :
						redirect(ADMIN_URI . '?page=user_roles');
					break;
				case 'delete':
					// Delete an existing user role
					userHasPrivilege('can_delete_user_roles') ? $rs_user_role->deleteRecord() :
						redirect(ADMIN_URI . '?page=user_roles');
					break;
				default:
					// List all user roles
					userHasPrivilege('can_view_user_roles') ? $rs_user_role->listRecords() :
						redirect('index.php');
			}
			break;
		default:
			// General settings
			userHasPrivilege('can_edit_settings') ? $rs_settings->generalSettings() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';