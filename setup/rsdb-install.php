<?php
/**
 * Run the database installation script.
 * @since 1.3.0-alpha
 *
 * @package ReallySimpleCMS
 */

require_once dirname(__DIR__) . '/includes/constants.php';
require_once RS_CRIT_FUNC;

checkPHPVersion();

if(!file_exists(RS_CONFIG)) redirect(SETUP . '/rsdb-config.php');

requireFiles(array(RS_CONFIG, RS_DEBUG_FUNC, GLOBAL_FUNC));

$rs_query = new \Engine\Query;
checkDBStatus();

requireFile(RS_SCHEMA);

$schema = dbSchema();
$tables = $rs_query->showTables();

if(!empty($tables)) {
	// Create any missing tables
	foreach($schema as $key => $value)
		if(!$rs_query->tableExists($key)) $rs_query->createTable($key, $value);
	
	exit(RS_ENGINE . ' is already installed!');
}

// Installation engine
requireFile(PATH . SETUP . '/run-install.php');

$debug = false;

if(isDebugMode()) $debug = true;

$step = (int)($_GET['step'] ?? 1);
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo RS_ENGINE; ?> Database Installation</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex, nofollow">
		<?php
		putStylesheet('button' . ($debug ? '' : '.min') . '.css');
		putStylesheet('setup' . ($debug ? '' : '.min') . '.css');
		putStylesheet('font-awesome.min.css', ICONS_VERSION);
		putStylesheet('font-awesome-rules.min.css');
		?>
	</head>
	<body class="rscms-install">
		<div class="wrapper">
			<?php
			echo domTag('h1', array(
				'content' => RS_ENGINE
			));
			?>
			<main class="content" role="main">
				<?php
				/**
				 * Display the installation form.
				 * @since 1.3.0-alpha
				 *
				 * @param string|null $error (optional) -- The error to display.
				 */
				function displayInstallForm(?string $error = null): void {
					echo domTag('p', array(
						'content' => 'You\'re almost ready to begin using the ' . RS_ENGINE . '. Fill in the form below to proceed with the installation. All of the settings below can be changed at a later date. They\'re required in order to set up the CMS, though.'
					));
					
					// Site title
					$site_title = isset($_POST['site_title']) ? trim(strip_tags($_POST['site_title'])) : '';
					
					// Username
					$username = isset($_POST['username']) ? trim(strip_tags($_POST['username'])) : '';
					
					// Email
					$admin_email = isset($_POST['admin_email']) ? trim(strip_tags($_POST['admin_email'])) : '';
					
					// Search engine visibility
					$do_robots = isset($_POST['do_robots']) ? (int)$_POST['do_robots'] : 1;
					
					if(!is_null($error)) {
						echo domTag('p', array(
							'class' => 'status-message failure',
							'content' => $error
						));
					}
					?>
					<form class="data-form" action="?step=2" method="post">
						<table class="form-table">
							<tr>
								<th><label for="site-title">Site Title</label></th>
								<td><input type="text" id="site-title" name="site_title" value="<?php echo $site_title; ?>" autofocus></td>
							</tr>
							<tr>
								<th><label for="username">Username</label></th>
								<td><input type="text" id="username" name="username" value="<?php echo $username; ?>" autocomplete="on"></td>
							</tr>
							<tr>
								<th><label for="password">Password</label></th>
								<td><input type="text" id="password" name="password" value="<?php echo generatePassword(); ?>" autocomplete="off"></td>
							</tr>
							<tr>
								<th><label for="admin-email">Email</label></th>
								<td><input type="email" id="admin-email" name="admin_email" value="<?php echo $admin_email; ?>"></td>
							</tr>
							<tr>
								<th><label for="do-robots">Search Engine Visibility</label></th>
								<td><label class="checkbox-label"><input type="checkbox" id="do-robots" name="do_robots" value="0"> <span>Discourage search engines from indexing this site</span></label></td>
							</tr>
						</table>
						<?php
						echo domTag('input', array(
							'type' => 'hidden',
							'id' => 'submit-ajax',
							'name' => 'submit_ajax',
							'value' => 0
						));
						
						echo domTag('div', array(
							'class' => 'button-wrap',
							'content' => domTag('input', array(
								'type' => 'submit',
								'class' => 'button',
								'name' => 'submit',
								'value' => 'Install'
							))
						));
						?>
					</form>
					<?php
				}
				
				switch($step) {
					case 1:
						displayInstallForm();
						break;
					case 2:
						list($error, $message) = runInstall($_POST);
						
						if($error)
							displayInstallForm($message);
						else
							echo $message;
						break;
				}
				?>
			</main>
			<?php
			echo domTag('p', array(
				'class' => 'powered-by',
				'content' => 'Powered by ' . RS_DEVELOPER
			));
			?>
		</div>
		<?php
		putScript('jquery.min.js', JQUERY_VERSION);
		putScript('setup' . ($debug ? '' : '.min') . '.js');
		?>
	</body>
</html>