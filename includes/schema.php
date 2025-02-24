<?php
/**
 * The database schema. This outlines all of the database tables and how they should be constructed.
 * @since 1.2.6-alpha
 *
 * @package ReallySimpleCMS
 *
 * TABLE LIST:
 *
 * ## POSTS ##
 * { `posts`, `postmeta`, `comments`, `redirects` }
 * ## TERMS ##
 * { `terms`, `taxonomies`, `term_relationships` }
 * ## USERS ##
 * { `users`, `usermeta`, `user_roles`, `user_privileges`, `user_relationships` }
 * ## LOGINS ##
 * { `login_attempts`, `login_blacklist`, `login_rules` }
 * ## MISCELLANEOUS ##
 * { `settings` }
 */

/**
 * Construct the schema.
 * @since 1.2.6-alpha
 *
 * @return array
 */
function dbSchema(): array {
	
	/*------------------------------------*\
		POSTS
	\*------------------------------------*/
	
	/**
	 * `posts` table -- Stores posts of all types, including custom ones.
	 * @since 1.3.5-alpha
	 */
	$tables['posts'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"title varchar(255) NOT NULL,",
		"author bigint(20) unsigned NOT NULL default '0',",
		"date datetime default NULL,",
		"modified datetime default NULL,",
		"content longtext NOT NULL,",
		"status varchar(20) NOT NULL default 'inherit',",
		"slug varchar(255) NOT NULL default '',",
		"parent bigint(20) unsigned NOT NULL default '0',",
		"type varchar(50) NOT NULL default 'post',",
		"KEY author (author),",
		"KEY slug (slug),",
		"KEY parent (parent)"
	);
	
	/**
	 * `postmeta` table -- Stores metadata for posts.
	 * @since 1.3.5-alpha
	 */
	$tables['postmeta'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"post bigint(20) unsigned NOT NULL default '0',",
		"datakey varchar(255) NOT NULL,",
		"value longtext NOT NULL,",
		"KEY post (post),",
		"KEY datakey (datakey)"
	);
	
	/**
	 * `comments` table -- Stores post comments.
	 * @since 1.1.0-beta_snap-01
	 */
	$tables['comments'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"post bigint(20) unsigned NOT NULL default '0',",
		"author bigint(20) unsigned NOT NULL default '0',",
		"date datetime default NULL,",
		"content longtext NOT NULL,",
		"upvotes bigint(20) NOT NULL default '0',",
		"downvotes bigint(20) NOT NULL default '0',",
		"status varchar(20) NOT NULL default 'pending',",
		"parent bigint(20) unsigned NOT NULL default '0',",
		"KEY post (post),",
		"KEY author (author),",
		"KEY parent (parent)"
	);
	
	/**
	 * `redirects` table -- Stores post redirects (unused).
	 * @since 1.3.5-alpha
	 */
	$tables['redirects'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"post bigint(20) unsigned NOT NULL default '0',",
		"slug varchar(255) NOT NULL default '',",
		"KEY post (post)"
	);
	
	/*------------------------------------*\
		TERMS
	\*------------------------------------*/
	
	/**
	 * `terms` table -- Stores terms of all types, including custom ones.
	 * @since 1.4.10-alpha
	 */
	$tables['terms'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"name varchar(255) NOT NULL,",
		"slug varchar(255) NOT NULL default '',",
		"taxonomy bigint(20) unsigned NOT NULL default '0',",
		"parent bigint(20) unsigned NOT NULL default '0',",
		"count bigint(20) unsigned NOT NULL default '0',",
		"KEY slug (slug),",
		"KEY taxonomy (taxonomy)"
	);
	
	/**
	 * `taxonomies` table -- Stores taxonomy data.
	 * @since 1.4.10-alpha
	 */
	$tables['taxonomies'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"name varchar(255) NOT NULL,",
		"KEY name (name)"
	);
	
	/**
	 * `term_relationships` table -- Stores relationships between `terms` and `posts`.
	 * @since 1.4.10-alpha
	 */
	$tables['term_relationships'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"term bigint(20) unsigned NOT NULL default '0',",
		"post bigint(20) unsigned NOT NULL default '0',",
		"KEY term (term),",
		"KEY post (post)"
	);
	
	/*------------------------------------*\
		USERS
	\*------------------------------------*/
	
	/**
	 * `users` table -- Stores user data.
	 * @since 1.3.5-alpha
	 */
	$tables['users'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"username varchar(100) NOT NULL,",
		"password varchar(255) NOT NULL,",
		"email varchar(100) NOT NULL,",
		"registered datetime default NULL,",
		"last_login datetime default NULL,",
		"session varchar(50) default NULL,",
		"role bigint(20) unsigned NOT NULL default '0',",
		"security_key varchar(50) default NULL,",
		"KEY username (username),",
		"KEY role (role)"
	);
	
	/**
	 * `usermeta` table -- Stores metadata for users.
	 * @since 1.3.5-alpha
	 */
	$tables['usermeta'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"user bigint(20) unsigned NOT NULL default '0',",
		"datakey varchar(255) NOT NULL,",
		"value longtext NOT NULL,",
		"KEY user (user),",
		"KEY datakey (datakey)"
	);
	
	/**
	 * `user_roles` table -- Stores user role data.
	 * @since 1.3.5-alpha
	 */
	$tables['user_roles'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"name varchar(255) NOT NULL,",
		"is_default tinyint(1) unsigned NOT NULL default '0',",
		"KEY name (name)"
	);
	
	/**
	 * `user_privileges` table -- Stores user privilege data.
	 * @since 1.3.5-alpha
	 */
	$tables['user_privileges'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"name varchar(255) NOT NULL,",
		"is_default tinyint(1) unsigned NOT NULL default '0',",
		"KEY name (name)"
	);
	
	/**
	 * `user_relationships` table -- Stores relationships between `user_roles` and `user_privileges`.
	 * @since 1.3.5-alpha
	 */
	$tables['user_relationships'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"role bigint(20) unsigned NOT NULL default '0',",
		"privilege bigint(20) unsigned NOT NULL default '0',",
		"KEY role (role),",
		"KEY privilege (privilege)"
	);
	
	/*------------------------------------*\
		LOGINS
	\*------------------------------------*/
	
	/**
	 * `login_attempts` table -- Stores all login attempts.
	 * @since 1.2.0-beta_snap-01
	 */
	$tables['login_attempts'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"login varchar(100) NOT NULL,",
		"ip_address varchar(150) NOT NULL,",
		"date datetime default NULL,",
		"status varchar(20) NOT NULL default 'failure',",
		"last_blacklisted_login datetime default NULL,",
		"last_blacklisted_ip datetime default NULL,",
		"KEY login_ip (login, ip_address)"
	);
	
	/**
	 * `login_blacklist` table -- Stores all blacklisted logins.
	 * @since 1.2.0-beta_snap-01
	 */
	$tables['login_blacklist'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"name varchar(150) NOT NULL,",
		"attempts int(20) unsigned NOT NULL default '0',",
		"blacklisted datetime default NULL,",
		"duration bigint(20) unsigned NOT NULL default '0',",
		"reason text NOT NULL,",
		"KEY name (name)"
	);
	
	/**
	 * `login_rules` table -- Stores all login rules.
	 * @since 1.2.0-beta_snap-01
	 */
	$tables['login_rules'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"type varchar(255) NOT NULL default 'login',",
		"attempts int(20) unsigned NOT NULL default '0',",
		"duration bigint(20) unsigned NOT NULL default '0',",
		"KEY type (type)"
	);
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * `settings` table -- Stores site-wide settings.
	 * @since 1.3.5-alpha
	 */
	$tables['settings'] = array(
		"id bigint(20) unsigned PRIMARY KEY auto_increment,",
		"name varchar(255) NOT NULL,",
		"value longtext NOT NULL,",
		"KEY name (name)"
	);
	
	return $tables;
}