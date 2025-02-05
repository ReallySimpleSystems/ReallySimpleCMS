<?php
/**
 * Generate sitemaps for all public posts.
 * @since 1.1.2-beta
 *
 * @package ReallySimpleCMS
 */

// Stop execution if the file is accessed directly
if(!defined('PATH')) exit('You do not have permission to access this directory.');

$public_post_types = array();
$px = 'p_';

foreach($post_types as $post_type) {
	// Skip the `media` post type
	if($post_type['name'] === 'media') continue;
	
	if($post_type['public']) $public_post_types[] = $post_type['name'];
}

foreach($public_post_types as $type) {
	if(is_writable(PATH)) {
		$sitemap_file_path = PATH . '/sitemap-' . str_replace('_', '-', $type) . '.xml';
		
		$posts = $rs_query->select('posts', array(
			$px . 'id', $px . 'created',
			$px . 'modified', $px . 'slug',
			$px . 'parent', $px . 'type'
		), array(
			$px . 'status' => 'published',
			$px . 'type' => $type
		), array(
			'order_by' => $px . 'created',
			'order' => 'DESC'
		));
		
		if(file_exists($sitemap_file_path)) {
			$file = simplexml_load_file($sitemap_file_path);
			$count = count($file->url);
		}
		
		if(!file_exists($sitemap_file_path) || (file_exists($sitemap_file_path) && $count !== count($posts))) {
			$handle = fopen($sitemap_file_path, 'w');
			
			fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' .
				'<?xml-stylesheet href="/includes/sitemap.xsl" type="text/xsl"?>' . chr(10) .
				'<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . chr(10));
			
			foreach($posts as $post) {
				$permalink = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] .
					(isHomePage($post[$px . 'id']) ? '/' : getPermalink($type, $post[$px . 'parent'], $post[$px . 'slug']));
				
				fwrite($handle, '<url>' . chr(10) . '<loc>' . $permalink . '</loc>' . chr(10) .
					'<lastmod>' . (formatDate((is_null($post[$px . 'modified']) ? $post[$px . 'created'] :
						$post[$px . 'modified']), 'Y-m-d\TH:i:s')) . '</lastmod>' . chr(10) . '</url>' . chr(10));
			}
			
			fwrite($handle, '</urlset>');
			fclose($handle);
			
			// Set file permissions
			chmod($sitemap_file_path, 0666);
		}
	}
}