<?php
/**
 * Admin dashboard page.
 * @since 1.0.2-alpha
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$action = $_GET['action'] ?? '';
?>
<article class="content">
	<?php
	switch($action) {
		case 'unhide_notices':
			$rs_notice = new \Admin\Notice;
			
			$rs_notice->unhide($rs_session['id']);
			
			redirect(ADMIN_URI);
			break;
		default:
			?>
			<section class="heading-wrap">
				<?php
				echo domTag('h1', array(
					'content' => 'Admin Dashboard'
				));
				?>
			</section>
			<section>
				<?php
				echo domTag('p', array(
					'class' => 'headline',
					'content' => 'Welcome to the administrative dashboard for ' . getSetting('site_title') . '.'
				));
				
				// Notices
				$theme_path = slash(PATH . THEMES) . getSetting('theme');
				
				if(!file_exists($theme_path . '/index.php')) {
					echo notice('Your current theme is broken. View your themes on the ' . domTag('a', array(
						'href' => ADMIN . '/themes.php',
						'content' => 'themes page'
					)) . '.', 0, false, true);
				}
				
				if($rs_session['dismissed_notices'] !== false) {
					$hidden = count($rs_session['dismissed_notices']);
					
					$message = 'You have ' . $hidden . ' hidden ' . ($hidden === 1 ? 'notice' : 'notices') .
						'. ' . domTag('a', array(
							'href' => ADMIN_URI . '?action=unhide_notices',
							'content' => 'Click here'
						)) . ' to unhide ' . ($hidden === 1 ? 'it' : 'them') . '.';
					
					echo notice($message, 2, false, true);
				}
				
				if(ctDraft() > 0 || ctDraft('page') > 0) {
					$message = 'You have ' . ctDraft('page') . ' unpublished ' . domTag('a', array(
						'href' => '/admin/posts.php?type=page&status=draft',
						'content' => 'pages'
					)) . ' and ' . ctDraft() . ' unpublished ' . domTag('a', array(
						'href' => '/admin/posts.php?status=draft',
						'content' => 'posts'
					)) . '.';
					
					if(!isDismissedNotice($message, $rs_session['dismissed_notices']))
						echo notice($message, 0);
				}
				?>
			</section>
			<section>
				<?php statsBarGraph(); ?>
			</section>
			<section class="clear">
				<?php getSetting('enable_comments') ? dashboardWidget('comments') : null; ?>
				<?php dashboardWidget('users'); ?>
				<?php getSetting('track_login_attempts') ? dashboardWidget('logins') : null; ?>
			</section>
			<?php
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';