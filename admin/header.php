<?php
/**
 * Admin dashboard header.
 * @since 1.0.2-alpha
 *
 * @package ReallySimpleCMS
 */

require_once dirname(__DIR__) . '/init.php';
requireFiles(array(RS_ADMIN_FUNC, RS_FUNC));

ob_start();

// Verify that the user is logged in
if(!isset($_COOKIE['session']) || !isValidSession($_COOKIE['session'])) {
	$login_slug = getSetting('login_slug');
	$redirect = ($_SERVER['REQUEST_URI'] !== '/admin/' ? 'redirect=' . urlencode($_SERVER['PHP_SELF']) : '');
	
	// If not, redirect to the login page
	if(!empty($login_slug))
		redirect('/login.php?secure_login=' . $login_slug . (!empty($redirect) ? '&' . $redirect : ''));
	else
		redirect('/login.php' . (!empty($redirect) ? '?' . $redirect : ''));
}

$current_page = getCurrentPage();
$notices = array();
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo getPageTitle(); ?> ▸ <?php putSetting('site_title'); ?> &mdash; <?php echo RS_ENGINE; ?></title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex, nofollow">
		<meta name="theme-color" content="#e0e0e0">
		<link type="image/x-icon" href="<?php echo getMediaSrc(getSetting('site_icon')); ?>" rel="icon">
		<?php adminHeaderScripts(); ?>
	</head>
	<body class="<?php echo $current_page; ?>">
		<header id="admin-header">
			<?php
			// Site title
			echo domTag('a', array(
				'id' => 'site-title',
				'href' => '/',
				'content' => domTag('i', array(
					'class' => 'fa-solid fa-house-chimney'
				)) . domTag('span', array(
					'content' => getSetting('site_title')
				))
 			));
			?>
			<div class="user-dropdown">
				<?php
				// Display name
				echo domTag('span', array(
					'content' => 'Welcome, ' . $rs_session['display_name']
				));
				
				// Small avatar
				echo getMedia($rs_session['avatar'], array(
					'class' => 'avatar',
					'width' => 20,
					'height' => 20
				));
				?>
				<ul class="user-dropdown-menu">
					<?php
					// Large avatar
					echo getMedia($rs_session['avatar'], array(
						'class' => 'avatar-large',
						'width' => 100,
						'height' => 100
					));
					
					// User profile
					echo domTag('li', array(
						'content' => domTag('a', array(
							'href' => slash(ADMIN) . 'profile.php',
							'content' => 'My Profile'
						))
					));
					
					// User stats
					echo domTag('li', array(
						'content' => domTag('a', array(
							'href' => slash(ADMIN) . 'stats.php',
							'content' => 'My Stats'
						))
					));
 					
 					// Log out
 					echo domTag('li', array(
 						'content' => domTag('a', array(
 							'href' => '../login.php?action=logout',
 							'content' => 'Log Out'
 						))
 					));
					?>
				</ul>
			</div>
		</header>
		<div id="admin-nav-wrap"></div>
		<nav id="admin-nav-menu">
			<ul class="menu">
				<?php registerAdminMenu(); ?>
			</ul>
		</nav>
		<noscript id="no-js" class="header-notice">Warning! Your browser either does not support or is set to disable <a href="https://www.w3schools.com/js/default.asp" target="_blank" rel="noreferrer noopener">JavaScript</a>. Some features may not work as expected.</noscript>
		<?php
		if(version_compare(PHP_VERSION, PHP_RECOMMENDED, '<')) {
			echo domTag('div', array(
				'id' => 'php-deprecation',
				'class' => 'header-notice',
				'content' => domTag('strong', array(
					'content' => 'Notice'
				)) . ': Your server\'s PHP version, ' . domTag('strong', array(
					'content' => PHP_VERSION
				)) . ', is below the recommended PHP version, ' . domTag('strong', array(
					'content' => PHP_RECOMMENDED
				)) . '. Consider upgrading to the recommended version.'
			));
		}
		?>
		<div class="wrapper clear">