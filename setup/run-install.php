<?php
/**
 * Run the database installation.
 * @since 1.2.6-beta
 *
 * @package ReallySimpleCMS
 */

// Minimum username length
const UN_LENGTH = 4;

// Minimum password length
const PW_LENGTH = 8;

// Check if we're submitting via AJAX
if(isset($_POST['submit_ajax']) && $_POST['submit_ajax']) {
	require_once dirname(__DIR__) . '/includes/constants.php';
	require_once RS_CRIT_FUNC;
	
	checkPHPVersion();
	
	require_once RS_CONFIG;
	require_once RS_DEBUG_FUNC;
	require_once GLOBAL_FUNC;
	
	$rs_query = new \Engine\Query;
	checkDBStatus();
	
	require_once RS_SCHEMA;
	
	$result = runInstall($_POST);
	
	echo implode(';', $result);
}

/**
 * Run the installation.
 * @since 1.2.6-beta
 *
 * @param array $data -- The submitted data.
 * @return array
 */
function runInstall(array $data): array {
	global $rs_query;
	
	// Site title
	$data['site_title'] = !empty($data['site_title']) ? trim(strip_tags($data['site_title'])) : 'My Website';
	
	// Username
	$data['username'] = isset($data['username']) ? trim(strip_tags($data['username'])) : '';
	
	// Password
	$data['password'] = isset($data['password']) ? strip_tags($data['password']) : '';
	
	// Admin email
	$data['admin_email'] = isset($data['admin_email']) ? trim(strip_tags($data['admin_email'])) : '';
	
	// Search engine visibility (visible by default)
	$data['do_robots'] = isset($data['do_robots']) ? (int)$data['do_robots'] : 1;
	
	// Validate input data
	if(empty($data['username']))
		return array(true, 'You must provide a username.');
	elseif(strlen($data['username']) < UN_LENGTH)
		return array(true, 'Username must be at least ' . UN_LENGTH . ' characters long.');
	elseif(empty($data['password']))
		return array(true, 'You must provide a password.');
	elseif(strlen($data['password']) < PW_LENGTH)
		return array(true, 'Password must be at least ' . PW_LENGTH . ' characters long.');
	elseif(empty($data['admin_email']))
		return array(true, 'You must provide an email.');
	
	$schema = dbSchema();
	
	// Create the tables
	foreach($schema as $key => $value) $rs_query->createTable($key, $value);
	
	$user_data = array(
		'username' => $data['username'],
		'password' => $data['password'],
		'email' => $data['admin_email']
	);
	
	$site_url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
		'https://' : 'http://') . $_SERVER['HTTP_HOST'];
	
	$settings_data = array(
		'site_title' => $data['site_title'],
		'site_url' => $site_url,
		'admin_email' => $data['admin_email'],
		'do_robots' => $data['do_robots']
	);
	
	populateTables($user_data, $settings_data);
	
	if(is_writable(PATH)) {
		$file_path = PATH . '/robots.txt';
		$handle = fopen($file_path, 'w');
		
		if($handle !== false) {
			fwrite($handle, 'User-agent: *' . chr(10));
			
			if((int)$data['do_robots'] === 0)
				fwrite($handle, 'Disallow: /');
			else
				fwrite($handle, 'Disallow: /admin/');
			
			fclose($handle);
		}
		
		// Set file permissions
		chmod($file_path, 0666);
	}
	
	return array(false, domTag('p', array(
		'content' => 'The database has successfully been installed! You are now ready to start using your website.'
	)) . domTag('div', array(
		'class' => 'button-wrap centered',
		'content' => domTag('a', array(
			'class' => 'button',
			'href' => '/login.php',
			'content' => 'Log In'
		)) # data-h="Urm75uNsIn#"
	)));
}