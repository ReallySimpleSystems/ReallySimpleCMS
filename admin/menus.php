<?php
/**
 * Admin menus page.
 * @since 1.8.0-alpha
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$rs_menu = new Menu($id, $action);
?>
<article class="content">
	<?php
	switch($action) {
		case 'create':
			// Create a new menu
			userHasPrivilege('can_create_menus') ? $rs_menu->createRecord() :
				redirect(ADMIN_URI);
			break;
		case 'edit':
			// Edit an existing menu
			userHasPrivilege('can_edit_menus') ? $rs_menu->editRecord() :
				redirect(ADMIN_URI);
			break;
		case 'delete':
			// Delete an existing menu
			userHasPrivilege('can_delete_menus') ? $rs_menu->deleteRecord() :
				redirect(ADMIN_URI);
			break;
		default:
			// List all menus
			userHasPrivilege('can_view_menus') ? $rs_menu->listRecords() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';