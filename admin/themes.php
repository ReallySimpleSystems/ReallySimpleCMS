<?php
/**
 * Admin themes page.
 * @since 2.3.0-alpha
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$name = $_GET['name'] ?? '';
$action = $_GET['action'] ?? '';

$rs_theme = new Theme($name, $action);
?>
<article class="content">
	<?php
	switch($action) {
		case 'create':
			// Create a new theme
			userHasPrivilege('can_create_themes') ? $rs_theme->createTheme() :
				redirect(ADMIN_URI);
			break;
		case 'activate':
			// Activate an inactive theme
			userHasPrivilege('can_edit_themes') ? $rs_theme->activateTheme() :
				redirect(ADMIN_URI);
			break;
		case 'delete':
			// Delete an existing theme
			userHasPrivilege('can_delete_themes') ? $rs_theme->deleteTheme() :
				redirect(ADMIN_URI);
			break;
		default:
			// List all themes
			userHasPrivilege('can_view_themes') ? $rs_theme->listThemes() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';