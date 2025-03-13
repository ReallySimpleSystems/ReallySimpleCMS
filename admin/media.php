<?php
/**
 * Admin media page.
 * @since 2.1.0-alpha
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$rs_media = new \Admin\Media($id, $action);
?>
<article class="content">
	<?php
	switch($action) {
		case 'upload':
			// Upload new media
			userHasPrivilege('can_upload_media') ? $rs_media->uploadRecordMedia() :
				redirect(ADMIN_URI);
			break;
		case 'edit':
			// Edit existing media
			userHasPrivilege('can_edit_media') ? $rs_media->editRecordMedia() :
				redirect(ADMIN_URI);
			break;
		case 'replace':
			// Replace existing media
			userHasPrivilege('can_edit_media') ? $rs_media->replaceRecordMedia() :
				redirect(ADMIN_URI);
			break;
		case 'delete':
			// Delete existing media
			userHasPrivilege('can_delete_media') ? $rs_media->deleteRecordMedia() :
				redirect(ADMIN_URI);
			break;
		default:
			// List all media
			userHasPrivilege('can_view_media') ? $rs_media->listRecordsMedia() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';