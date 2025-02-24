<?php
/**
 * PHP Exception/Error page.
 * @since 1.3.14-beta
 *
 * @package ReallySimpleCMS
 *
 * Displays a user-friendly message if ini `display_errors` is off,
 *  and the backtrace of the error if the setting is on.
 */
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Error ▸ <?php echo RS_ENGINE; ?></title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php
		if(isDebugMode())
			putStylesheet('style.css');
		else
			putStylesheet('style.min.css');
		?>
	</head>
	<body class="error-page">
		<div class="wrapper">
			<h1>An Error Occurred</h1>
			<main class="content" role="main">
				<?php $rs_error->generateError(); ?>
			</main>
			<p class="powered-by">Powered by <?php echo RS_DEVELOPER; ?></p>
		</div>
	</body>
</html>
<?php
// Prevent further execution of scripts or content output
exit;