<?php
/**
 * Functions for the Carbon theme.
 * @since 1.0.0 [2.2.6-alpha]
 *
 * @package ReallySimpleCMS
 * @subpackage Carbon
 */

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
			$posts = querySelect(getTable('p'), '*', array(
				'status' => 'published',
				'type' => 'post'
			), array(
				'order_by' => 'date',
				'order' => 'DESC',
				'limit' => $count
			));
		} else {
			if($terms === 0) {
				// Fetch only the posts associated with the current term
				$posts = getTermPosts($terms, 'date', 'DESC', $count);
			} else {
				if(!is_array($terms)) $terms = (array)$terms;
				
				$posts = array();
				
				foreach($terms as $term)
					$posts[] = getTermPosts($term, 'date', 'DESC', $count);
				
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
					$feat_image = querySelectField(getTable('pm'), 'value', array(
						'post' => $post['id'],
						'datakey' => 'feat_image'
					));
					?>
					<li class="post id-<?php echo $post['id']; ?> clear">
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
								'href' => getPost($post['slug'])->getPostPermalink(
									$post['type'],
									$post['parent'],
									$post['slug']
								),
								'content' => $post['title']
							))
						));
						
						echo domTag('p', array(
							'class' => 'date',
							'content' => formatDate($post['date'], 'j M Y')
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