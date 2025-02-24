<?php
/**
 * Admin logins page.
 * @since 1.2.0-beta_snap-01
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$page = $_GET['page'] ?? 'attempts';

$rs_login = new Login($id, $action, $page);
?>
<article class="content">
	<?php
	switch($page) {
		case 'blacklist':
			switch($action) {
				case 'create':
					// Create a new blacklisted login
					userHasPrivilege('can_create_login_blacklist') ? $rs_login->createBlacklist() :
						redirect(ADMIN_URI . '?page=blacklist');
					break;
				case 'edit':
					// Edit a blacklisted login
					userHasPrivilege('can_edit_login_blacklist') ? $rs_login->editBlacklist() :
						redirect(ADMIN_URI . '?page=blacklist');
					break;
				case 'whitelist':
					// Whitelist a blacklisted login or IP address
					userHasPrivilege('can_delete_login_blacklist') ? $rs_login->whitelistLoginIP() :
						redirect(ADMIN_URI . '?page=blacklist');
					break;
				default:
					// List the login blacklist
					userHasPrivilege('can_view_login_blacklist') ? $rs_login->loginBlacklist() :
						redirect(ADMIN_URI);
			}
			break;
		case 'rules':
			switch($action) {
				case 'create':
					// Create a new login rule
					userHasPrivilege('can_create_login_rules') ? $rs_login->createRule() :
						redirect(ADMIN_URI . '?page=rules');
					break;
				case 'edit':
					// Edit an existing login rule
					userHasPrivilege('can_edit_login_rules') ? $rs_login->editRule() :
						redirect(ADMIN_URI . '?page=rules');
					break;
				case 'delete':
					// Delete an existing login rule
					userHasPrivilege('can_delete_login_rules') ? $rs_login->deleteRule() :
						redirect(ADMIN_URI . '?page=rules');
					break;
				default:
					// List all login rules
					userHasPrivilege('can_view_login_rules') ? $rs_login->loginRules() :
						redirect(ADMIN_URI);
			}
			break;
		default:
			switch($action) {
				case 'blacklist_login':
					// Blacklist a user's login
					userHasPrivilege('can_create_login_blacklist') ? $rs_login->blacklistLogin() :
						redirect(ADMIN_URI);
					break;
				case 'blacklist_ip':
					// Blacklist a user's IP address
					userHasPrivilege('can_create_login_blacklist') ? $rs_login->blacklistIPAddress() :
						redirect(ADMIN_URI);
					break;
				default:
					// List all login attempts
					userHasPrivilege('can_view_login_attempts') ? $rs_login->loginAttempts() :
						redirect('index.php');
			}
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';