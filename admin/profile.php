<?php
/**
 * Admin profile page.
 * @since 2.0.0-alpha
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$action = $_GET['action'] ?? '';

$rs_profile = new \Admin\Profile($rs_session['id'], $action);
?>
<article class="content">
	<?php
	switch($action) {
		case 'reset_password':
			// Reset password
			$rs_profile->resetPassword();
			break;
		default:
			// Edit profile
			$rs_profile->editProfile();
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';