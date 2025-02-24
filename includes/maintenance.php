<?php
/**
 * Maintenance page used by the CMS if it's in maintenance mode.
 * @since 1.3.6-beta
 *
 * @package ReallySimpleCMS
 *
 * Maintenance mode is useful for making potentially breaking changes on the website,
 *  and the CMS will only display it to logged out viewers.
 */
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Under Maintenance ▸ <?php putSetting('site_title'); ?></title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php
		if(isDebugMode())
			putStylesheet('style.css');
		else
			putStylesheet('style.min.css');
		?>
	</head>
	<body class="maintenance-page">
		<?php
		// Content
		echo domTag('div', array(
			'class' => 'wrapper',
			'content' => domTag('h1', array(
				'content' => 'Welcome to ' . getSetting('site_title') . '!'
			)) . domTag('p', array(
				'content' => 'This site is currently down for scheduled maintenance.'
			)) . domTag('p', array(
				'content' => 'Check back again later to see if the maintenance has ended.'
			))
		));
		
		// Copyright
		echo domTag('p', array(
			'class' => 'copyright',
				'content' => '&copy; ' . date('Y') . ' ' . domTag('a', array(
				'href' => 'https://github.com/CaptFredricks/ReallySimpleCMS',
				'target' => '_blank',
				'rel' => 'noreferrer noopener',
				'content' => RS_ENGINE
			)) . ' &ndash; ' . domTag('em', array(
				'content' => 'powered by ' . RS_DEVELOPER
			)) . ' &bull; All Rights Reserved.'
		));
		?>
	</body>
</html>
<?php
// Prevent further execution of scripts or content output
exit;