<?php
/**
 * Carbon theme - functions.
 * @since 2.2.6-alpha
 *
 * @package ReallySimpleCMS
 * @subpackage Carbon
 */

define('THEME_VERSION', '1.12');

registerTheme('carbon', array(
	'author' => array(
		'name' => 'Jace Fincham',
		'url' => 'https://jacefincham.com/'
	),
	'version' => THEME_VERSION
));

/**
 * Register custom post types.
 * @since 1.0.0-beta
 */
// registerPostType($slug, $args);

/**
 * Register custom taxonomies.
 * @since 1.0.1-beta
 */
// registerTaxonomy($name, $post_type, $args);

/**
 * Register theme menus.
 * @since 1.0.0-beta
 */
registerMenu('Main Menu', 'main-menu');
registerMenu('Footer Menu', 'footer-menu');

/**
 * Register theme widgets.
 * @since 1.0.0-beta
 */
registerWidget('Social Media', 'social-media');
registerWidget('Get in contact with us!', 'business-info');
registerWidget('Copyright', 'copyright');

/**
 * Fetch the most recent posts in a taxonomy.
 * @since 2.2.6-alpha
 *
 * @param int $count (optional) -- The post count.
 * @param mixed $terms (optional) -- The terms to query.
 * @param bool $display_title (optional) -- Whether to display the widget title.
 */
function getRecentPosts(int $count = 3, mixed $terms = null, bool $display_title = false): void {
	global $rs_query;
	?>
	<div class="recent-posts clear">
		<?php
		if($display_title) {
			echo domTag('h3', array(
				'content' => 'Recent Posts'
			));
		}
		
		if(is_null($terms)) {
			// Fetch all published posts regardless of taxonomy
			$posts = querySelect('posts', '*', array(
				'p_status' => 'published',
				'p_type' => 'post'
			), array(
				'order_by' => 'p_created',
				'order' => 'DESC',
				'limit' => $count
			));
		} else {
			if($terms === 0) {
				// Fetch only the posts associated with the current term
				$posts = getTermPosts($terms, 'p_created', 'DESC', $count);
			} else {
				if(!is_array($terms)) $terms = (array)$terms;
				
				$posts = array();
				
				foreach($terms as $term)
					$posts[] = getTermPosts($term, 'p_created', 'DESC', $count);
				
				$posts = array_merge(...$posts);
			}
		}
		
		if(empty($posts)) {
			echo domTag('h4', array(
				'content' => 'Sorry, there are no posts to display.'
			));
		} else {
			?>
			<ul>
				<?php
				foreach($posts as $post) {
					$feat_image = querySelectField('postmeta', 'pm_value', array(
						'pm_post' => $post['p_id'],
						'pm_key' => 'feat_image'
					));
					?>
					<li class="post id-<?php echo $post['p_id']; ?> clear">
						<?php
						// Featured image
						if($feat_image) {
							echo getMedia($feat_image, array(
								'class' => 'feat-image',
								'width' => 80
							));
						}
						
						echo domTag('h4', array(
							'content' => domTag('a', array(
								'href' => getPost($post['p_slug'])->getPostPermalink($post['p_type'], $post['p_parent'],
									$post['p_slug']
								),
								'content' => $post['p_title']
							))
						));
						
						echo domTag('p', array(
							'class' => 'date',
							'content' => formatDate($post['p_created'], 'j M Y')
						));
						?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>
	</div>
	<?php
}