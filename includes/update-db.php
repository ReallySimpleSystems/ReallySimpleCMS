<?php
/**
 * This file handles structural changes to the database that would otherwise break the system.
 * The goal is to ensure that no existing data is lost during the update.
 * Old scripts will be removed after at least two major releases.
 * @since 1.3.5-beta
 *
 * @package ReallySimpleCMS
 */

// Prefixing database tables (this must run before the other scripts to avoid errors)
if(version_compare(RS_VERSION, '1.4.0-beta_snap-02', '>=')) {
	$schema = dbSchema();
	
	$px = array(
		'posts' => 'p_',
		'postmeta' => 'pm_',
		'comments' => 'c_',
		'redirects' => 'r_',
		'terms' => 't_',
		'taxonomies' => 'ta_',
		'term_relationships' => 'tr_',
		'users' => 'u_',
		'usermeta' => 'um_',
		'user_roles' => 'ur_',
		'user_privileges' => 'up_',
		'user_relationships' => 'ue_',
		'login_attempts' => 'la_',
		'login_blacklist' => 'lb_',
		'login_rules' => 'lr_',
		'settings' => 's_'
	);
	
	foreach($schema as $key => $value) {
		$prefixed = false;
		
		if($rs_query->columnExists($key, $px[$key] . 'id'))
			$prefixed = true;
		
		// Skip tables that are already updated
		if($prefixed) continue;
		
		$temp_table = $key . '_temp';
		
		if(!$rs_query->tableExists($temp_table)) {
			// Create a temp table
			$rs_query->createTable($temp_table, $value);
			
			$old_data = $rs_query->select($key);
			
			for($i = 0; $i < count($old_data); $i++) {
				foreach($old_data[$i] as $dkey => $dval) {
					// Update columns whose names have changed
					switch($key) {
						case 'posts':
						case 'comments':
							if($dkey === 'date') {
								$dkey = 'created';
								$old_data[$i][$dkey] = $dval;
								unset($old_data[$i]['date']);
							}
							break;
						case 'users':
							if($dkey === 'security_key') {
								$dkey = 'token';
								$old_data[$i][$dkey] = $dval;
								unset($old_data[$i]['security_key']);
							}
							break;
						case 'postmeta':
						case 'usermeta':
							if($dkey === 'datakey') {
								$dkey = 'key';
								$old_data[$i][$dkey] = $dval;
								unset($old_data[$i]['datakey']);
							}
							break;
					}
					
					// Prefix all of the old keys
					$old_data[$i][$px[$key] . $dkey] = $old_data[$i][$dkey];
					unset($old_data[$i][$dkey]);
				}
				
				// Move the data to the temp table
				$rs_query->insert($temp_table, $old_data[$i]);
			}
			
			// Delete the old table and rename the temp one
			$rs_query->dropTable($key);
			$rs_query->doQuery("ALTER TABLE `" . $temp_table . "` RENAME TO `" . $key . "`;");
		}
	}
}

// Various tweaks
if(version_compare(RS_VERSION, '1.3.12-beta', '>=')) {
	// Changing `unapproved` comments to `pending`
	$comments = $rs_query->select('comments', 'COUNT(c_status)', array(
		'c_status' => 'unapproved'
	));
	
	if($comments > 0) {
		$rs_query->update('comments', array(
			'c_status' => 'pending'
		), array(
			'c_status' => 'unapproved'
		));
	}
	
	// Adding `login_slug` setting
	$settings = $rs_query->selectRow('settings', 'COUNT(s_name)', array(
		's_name' => 'login_slug'
	));
	
	if($settings === 0) {
		$rs_query->insert('settings', array(
			's_name' => 'login_slug',
			's_value' => ''
		));
	}
	
	// Updating table schemas and columns
	
	// `postmeta` table
	if($rs_query->columnExists('postmeta', '_key')) {
		$table = 'postmeta';
		
		// Add new columns
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD pm_key varchar(255) NOT NULL;");
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD value_temp longtext NOT NULL;");
		
		$postmeta = $rs_query->select($table, array('pm_id', '_key', 'pm_value'));
		
		// Move the data to the new columns
		foreach($postmeta as $pmeta) {
			$rs_query->update($table, array(
				'pm_key' => $pmeta['_key'],
				'value_temp' => $pmeta['pm_value']
			), array(
				'pm_id' => $pmeta['pm_id']
			));
		}
		
		// Replace the old index
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP INDEX _key;");
		$rs_query->doQuery("CREATE INDEX metakey ON `{$table}` (pm_key);");
		
		// Replace the old columns
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN _key;");
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN pm_value;");
		$rs_query->doQuery("ALTER TABLE `{$table}` CHANGE `value_temp` `pm_value` longtext NOT NULL;");
	}
	
	// `usermeta` table
	if($rs_query->columnExists('usermeta', '_key')) {
		$table = 'usermeta';
		
		// Add new columns
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD um_key varchar(255) NOT NULL;");
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD value_temp longtext NOT NULL;");
		
		$usermeta = $rs_query->select($table, array('um_id', '_key', 'um_value'));
		
		// Move the data to the new columns
		foreach($usermeta as $umeta) {
			$rs_query->update($table, array(
				'um_key' => $umeta['_key'],
				'value_temp' => $umeta['um_value']
			), array(
				'um_id' => $umeta['um_id']
			));
		}
		
		// Replace the old index
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP INDEX _key;");
		$rs_query->doQuery("CREATE INDEX metakey ON `{$table}` (um_key);");
		
		// Replace the old columns
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN _key;");
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN um_value;");
		$rs_query->doQuery("ALTER TABLE `{$table}` CHANGE `value_temp` `um_value` longtext NOT NULL;");
	}
	
	// `user_roles` table
	if($rs_query->columnExists('user_roles', '_default')) {
		$table = 'user_roles';
		
		// Add new column
		$rs_query->doQuery("ALTER TABLE `{$table}` ADD ur_is_default tinyint(1) unsigned NOT NULL default '0';");
		
		$user_roles = $rs_query->select($table, array('ur_id', '_default'));
		
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
				'ur_is_default' => $is_default
			), array(
				'ur_id' => $role['ur_id']
			));
		}
		
		// Replace the old column
		$rs_query->doQuery("ALTER TABLE `{$table}` DROP COLUMN _default;");
	}
}

// Adding `index_post` metadata to existing posts
if(version_compare(RS_VERSION, '1.3.9-beta', '>=')) {
	$posts = $rs_query->select('posts', 'p_id', array(
		'p_type' => array('NOT IN', 'nav_menu_item', 'widget')
	));
	
	foreach($posts as $post) {
		$index = $rs_query->selectRow('postmeta', 'COUNT(*)', array(
			'pm_post' => $post['p_id'],
			'pm_key' => 'index_post'
		));
		
		if($index === 0) {
			$rs_query->insert('postmeta', array(
				'pm_post' => $post['p_id'],
				'pm_key' => 'index_post',
				'pm_value' => getSetting('do_robots')
			));
		}
	}
}

// Update privileges
if(version_compare(RS_VERSION, '1.3.13-beta', '>=')) {
	$tables = array('user_roles', 'user_relationships', 'user_privileges');
	$schema = dbSchema();
	
	if($rs_query->selectRow($tables[2], 'COUNT(*)', array(
		'up_name' => 'can_update_core'
	)) === 0) {
		// Check if we have custom roles
		if($rs_query->selectRow($tables[0], 'COUNT(*)', array(
			'ur_id' => array('>', 4)
		)) > 0) {
			// Fetch all the non-default data
			$roles = $rs_query->select($tables[0], '*', array(
				'ur_id' => array('>', 4)
			));
		} else {
			$roles = array();
		}
		
		// Fetch all the non-default data
		$privileges = $rs_query->select($tables[2], '*', array(
			'up_id' => array('>', 49)
		));
		
		$relationships = $rs_query->select($tables[1], '*', array(
			'ue_privilege' => array('>', 49)
		));
		
		// Replace the data
		foreach($tables as $table) {
			$rs_query->dropTable($table);
			$rs_query->createTable($table, $schema[$table]);
			
			populateTable($table);
		}
		
		// Add in the non-default data
		if(!empty($roles)) {
			foreach($roles as $role) {
				$rs_query->insert($tables[0], array(
					'ur_name' => $role['ur_name'],
					'ur_is_default' => $role['ur_is_default']
				));
			}
		}
		
		foreach($privileges as $privilege) {
			$rs_query->insert($tables[2], array(
				'up_name' => $privilege['up_name'],
				'up_is_default' => $privilege['up_is_default']
			));
		}
		
		foreach($relationships as $relationship) {
			$rs_query->insert($tables[1], array(
				'ue_role' => $relationship['ue_role'],
				'ue_privilege' => $relationship['ue_privilege']
			));
		}
	}
}