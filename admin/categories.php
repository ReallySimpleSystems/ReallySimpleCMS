<?php
/**
 * Admin categories page.
 * @since 1.5.0-alpha
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$rs_category = new Term($id, $action, $taxonomies['category']);
?>
<article class="content">
	<?php
	switch($action) {
		case 'create':
			// Create a new category
			userHasPrivilege('can_create_categories') ? $rs_category->createRecord() :
				redirect(ADMIN_URI);
			break;
		case 'edit':
			// Edit an existing category
			userHasPrivilege('can_edit_categories') ? $rs_category->editRecord() :
				redirect(ADMIN_URI);
			break;
		case 'delete':
			// Delete an existing category
			userHasPrivilege('can_delete_categories') ? $rs_category->deleteRecord() :
				redirect(ADMIN_URI);
			break;
		default:
			// List all categories
			userHasPrivilege('can_view_categories') ? $rs_category->listRecords() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';