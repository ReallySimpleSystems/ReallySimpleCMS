<?php
/**
 * Admin widgets page.
 * @since 1.6.0-alpha
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$rs_widget = new Widget($id, $action);
?>
<article class="content">
	<?php
	switch($action) {
		case 'create':
			// Create a new widget
			userHasPrivilege('can_create_widgets') ? $rs_widget->createRecord() :
				redirect(ADMIN_URI);
			break;
		case 'edit':
			// Edit an existing widget
			userHasPrivilege('can_edit_widgets') ? $rs_widget->editRecord() :
				redirect(ADMIN_URI);
			break;
		case 'delete':
			// Delete an existing widget
			userHasPrivilege('can_delete_widgets') ? $rs_widget->deleteRecord() :
				redirect(ADMIN_URI);
			break;
		default:
			// List all widgets
			userHasPrivilege('can_view_widgets') ? $rs_widget->listRecords() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';