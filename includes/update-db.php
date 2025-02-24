<?php
/**
 * This file handles structural changes to the database that would otherwise break the system.
 * The goal is to ensure that no existing data is lost during the update.
 * Old scripts will be removed after at least two major releases.
 * @since 1.3.5-beta
 *
 * @package ReallySimpleCMS
 */

// Various tweaks
if(version_compare(RS_VERSION, '1.3.12-beta', '>=')) {
	// Changing `unapproved` comments to `pending`
	$comments = $rs_query->select('comments', 'COUNT(status)', array(
		'status' => 'unapproved'
	));
	
	if($comments > 0) {
		$rs_query->update('comments', array(
			'status' => 'pending'
		), array(
			'status' => 'unapproved'
		));
	}
	
	// Adding `login_slug` setting
	$settings = $rs_query->selectRow('settings', 'COUNT(name)', array(
		'name' => 'login_slug'
	));
	
	if($settings === 0) {
		$rs_query->insert('settings', array(
			'name' => 'login_slug',
			'value' => ''
		));
	}
	
	// Updating table schemas and columns
	
	// `postmeta` table
	if($rs_query->columnExists('postmeta', '_key')) {
		$table = 'postmeta';
		
		// Add new columns
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD datakey varchar(255) NOT NULL;");
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD value_temp longtext NOT NULL;");
		
		$postmeta = $rs_query->select($table, array('id', '_key', 'value'));
		
		// Move the data to the new columns
		foreach($postmeta as $pmeta) {
			$rs_query->update($table, array(
				'datakey' => $pmeta['_key'],
				'value_temp' => $pmeta['value']
			), array(
				'id' => $pmeta['id']
			));
		}
		
		// Replace the old index
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP INDEX _key;");
		$rs_query->doQuery("CREATE INDEX datakey ON `{$table}` (datakey);");
		
		// Replace the old columns
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN _key;");
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN value;");
		$rs_query->doQuery("ALTER TABLE `{$table}` CHANGE `value_temp` `value` longtext NOT NULL;");
	}
	
	// `usermeta` table
	if($rs_query->columnExists('usermeta', '_key')) {
		$table = 'usermeta';
		
		// Add new columns
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD datakey varchar(255) NOT NULL;");
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD value_temp longtext NOT NULL;");
		
		$usermeta = $rs_query->select($table, array('id', '_key', 'value'));
		
		// Move the data to the new columns
		foreach($usermeta as $umeta) {
			$rs_query->update($table, array(
				'datakey' => $umeta['_key'],
				'value_temp' => $umeta['value']
			), array(
				'id' => $umeta['id']
			));
		}
		
		// Replace the old index
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP INDEX _key;");
		$rs_query->doQuery("CREATE INDEX datakey ON `{$table}` (datakey);");
		
		// Replace the old columns
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN _key;");
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN value;");
		$rs_query->doQuery("ALTER TABLE `{$table}` CHANGE `value_temp` `value` longtext NOT NULL;");
	}
	
	// `user_roles` table
	if($rs_query->columnExists('user_roles', '_default')) {
		$table = 'user_roles';
		
		// Add new column
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD is_default tinyint(1) unsigned NOT NULL default '0';");
		
		$user_roles = $rs_query->select($table, array('id', '_default'));
		
		// Move the data to the new column
		foreach($user_roles as $role) {
			switch($role['_default']) {
				case 'yes':
					$is_default = 1;
					break;
				case 'no':
					$is_default = 0;
					break;
			}
			
			$rs_query->update($table, array(
				'is_default' => $is_default
			), array(
				'id' => $role['id']
			));
		}
		
		// Replace the old column
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN _default;");
	}
}

// Adding `index_post` metadata to existing posts
if(version_compare(RS_VERSION, '1.3.9-beta', '>=')) {
	$posts = $rs_query->select('posts', 'id', array(
		'type' => array('NOT IN', 'nav_menu_item', 'widget')
	));
	
	foreach($posts as $post) {
		$index = $rs_query->selectRow('postmeta', 'COUNT(*)', array(
			'post' => $post['id'],
			'datakey' => 'index_post'
		));
		
		if($index === 0) {
			$rs_query->insert('postmeta', array(
				'post' => $post['id'],
				'datakey' => 'index_post',
				'value' => getSetting('do_robots')
			));
		}
	}
}

// Adding `is_default` column to `user_privileges` table
if(version_compare(RS_VERSION, '1.3.14-beta', '>=')) {
	$table = 'user_privileges';
	
	if(!$rs_query->columnExists($table, 'is_default')) {
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD is_default tinyint(1) unsigned NOT NULL default '0';");
		
		$privileges = $rs_query->select($table, 'id', array(), array(
			'order_by' => 'id'
		));
		
		if(!empty($privileges)) {
			foreach($privileges as $privilege) {
				$rs_query->update($table, array(
					'is_default' => 1
				), array(
					'id' => array('<=', 49)
				));
			}
		}
	}
}