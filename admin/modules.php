<?php
/**
 * Admin modules page.
 * @since 1.4.0-beta_snap-03
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$name = $_GET['name'] ?? '';
$action = $_GET['action'] ?? '';

$rs_module = new \Admin\Module($name, $action, $rs_modules[$name] ?? array());
?>
<article class="content">
	<?php
	switch($action) {
		case 'install':
			// Install a new module
			/* userHasPrivilege('can_install_modules') ? $rs_media->uploadRecordMedia() :
				redirect(ADMIN_URI); */
			break;
		case 'update':
			// Update existing module
			/* userHasPrivilege('can_update_modules') ? $rs_media->editRecordMedia() :
				redirect(ADMIN_URI); */
			$rs_module->updateModule();
			break;
		case 'delete':
			// Delete existing media
			/* userHasPrivilege('can_delete_modules') ? $rs_media->deleteRecordMedia() :
				redirect(ADMIN_URI); */
			break;
		default:
			// List all modules
			/* userHasPrivilege('can_view_modules') ? $rs_module->listRecords() :
				redirect('index.php'); */
			$rs_module->listRecords();
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';

/*
if(defined('DOMTAGS_VERSION')) {
					echo tableRow(
						thCell('DOMtags Version'),
						tdCell(DOMTAGS_VERSION . ' (' . domTag('a', array(
							'href' => 'https://github.com/CaptFredricks/DOMtags',
							'target' => '_blank',
							'rel' => 'noreferrer noopener',
							'content' => 'GitHub Repo'
						)) . ')')
					);
				} */