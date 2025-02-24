<?php
/**
 * Admin comments page.
 * @since 1.1.0-beta_snap-01
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$rs_comment = new Comment($id, $action);
?>
<article class="content">
	<?php
	switch($action) {
		case 'edit':
			// Edit an existing comment
			userHasPrivilege('can_edit_comments') ? $rs_comment->editRecord() :
				redirect(ADMIN_URI);
			break;
		case 'approve':
			// Approve an existing comment
			userHasPrivilege('can_edit_comments') ? $rs_comment->approveComment() :
				redirect(ADMIN_URI);
			break;
		case 'unapprove':
			// Unapprove an existing comment
			userHasPrivilege('can_edit_comments') ? $rs_comment->unapproveComment() :
				redirect(ADMIN_URI);
			break;
		case 'spam':
			// Send an existing comment to spam
			userHasPrivilege('can_edit_comments') ? $rs_comment->spamComment() :
				redirect(ADMIN_URI);
			break;
		case 'delete':
			// Delete an existing comment
			userHasPrivilege('can_delete_comments') ? $rs_comment->deleteRecord() :
				redirect(ADMIN_URI);
			break;
		default:
			// List all comments
			userHasPrivilege('can_view_comments') ? $rs_comment->listRecords() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';