<?php
/**
 * This file handles structural changes to the database that would otherwise break the system.
 * The goal is to ensure that no existing data is lost during the update.
 * Old scripts will be removed after at least two major releases.
 * @since 1.3.5-beta
 *
 * @package ReallySimpleCMS
 */

global $rs_query;

// Various tweaks
if(version_compare(RS_VERSION, '1.3.12-beta', '>=')) {
	// Change `unapproved` comments to `pending`
	$comments = $rs_query->select(getTable('c'), 'COUNT(status)', array(
		'status' => 'unapproved'
	));
	
	if($comments > 0) {
		$rs_query->update(getTable('c'), array(
			'status' => 'pending'
		), array(
			'status' => 'unapproved'
		));
	}
	
	// Add `login_slug` setting
	$login_slug = $rs_query->selectRow(getTable('s'), 'id', array(
		'name' => 'login_slug'
	));
	
	if(empty($login_slug)) {
		$rs_query->insert(getTable('s'), array(
			'name' => 'login_slug',
			'value' => ''
		));
	}
	
	// Update table schemas and columns
	
	// `postmeta` table
	if($rs_query->columnExists('postmeta', '_key')) {
		$table = getTable('pm');
		$table_name = 'postmeta';
		
		// Add new columns
		$rs_query->doQuery("ALTER TABLE `{$table_name}` ADD datakey varchar(255) NOT NULL;");
		$rs_query->doQuery("ALTER TABLE `{$table_name}` ADD value_temp longtext NOT NULL;");
		
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
		$rs_query->doQuery("ALTER TABLE `{$table_name}` DROP INDEX _key;");
		$rs_query->doQuery("CREATE INDEX datakey ON `{$table_name}` (datakey);");
		
		// Replace the old columns
		$rs_query->doQuery("ALTER TABLE `{$table_name}` DROP COLUMN _key;");
		$rs_query->doQuery("ALTER TABLE `{$table_name}` DROP COLUMN value;");
		$rs_query->doQuery("ALTER TABLE `{$table_name}` CHANGE `value_temp` `value` longtext NOT NULL;");
	}
	
	// `usermeta` table
	if($rs_query->columnExists('usermeta', '_key')) {
		$table = getTable('um');
		$table_name = 'usermeta';
		
		// Add new columns
		$rs_query->doQuery("ALTER TABLE `{$table_name}` ADD datakey varchar(255) NOT NULL;");
		$rs_query->doQuery("ALTER TABLE `{$table_name}` ADD value_temp longtext NOT NULL;");
		
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
		$rs_query->doQuery("ALTER TABLE `{$table_name}` DROP INDEX _key;");
		$rs_query->doQuery("CREATE INDEX datakey ON `{$table_name}` (datakey);");
		
		// Replace the old columns
		$rs_query->doQuery("ALTER TABLE `{$table_name}` DROP COLUMN _key;");
		$rs_query->doQuery("ALTER TABLE `{$table_name}` DROP COLUMN value;");
		$rs_query->doQuery("ALTER TABLE `{$table_name}` CHANGE `value_temp` `value` longtext NOT NULL;");
	}
	
	// `user_roles` table
	if($rs_query->columnExists('user_roles', '_default')) {
		$table = getTable('ur');
		$table_name = 'user_roles';
		
		// Add new column
		$rs_query->doQuery("ALTER TABLE `{$table_name}` ADD is_default tinyint(1) unsigned NOT NULL default '0';");
		
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
		$rs_query->doQuery("ALTER TABLE `{$table_name}` DROP COLUMN _default;");
	}
}

// Add `is_default` column to `user_privileges` table
if(version_compare(RS_VERSION, '1.3.14-beta', '>=')) {
	if(!$rs_query->columnExists('user_privileges', 'is_default')) {
		$table = getTable('up');
		$table_name = 'user_privileges';
		
		$rs_query->doQuery("ALTER TABLE `{$table_name}` ADD is_default tinyint(1) unsigned NOT NULL default '0';");
		
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

// Add `active_modules` setting and rename `theme` to `active_theme`
if(version_compare(RS_VERSION, '1.3.15-beta', '>=')) {
	$active_modules = $rs_query->selectField(getTable('s'), 'id', array(
		'name' => 'active_modules'
	));
	
	if(empty($active_modules)) {
		$rs_query->insert(getTable('s'), array(
			'name' => 'active_modules',
			'value' => ''
		));
	}
	
	$active_theme = $rs_query->selectField(getTable('s'), 'value', array(
		'name' => 'theme'
	));
	
	if(!empty($active_theme)) {
		$rs_query->insert(getTable('s'), array(
			'name' => 'active_theme',
			'value' => $active_theme
		));
		
		$rs_query->delete(getTable('s'), array(
			'name' => 'theme'
		));
	}
}