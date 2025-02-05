<?php
/**
 * Admin class used to implement the Theme object.
 * @since 2.3.0-alpha
 *
 * @package ReallySimpleCMS
 *
 * Themes are used on the front end of the CMS to allow complete customization for the user's website.
 * Themes can be created, modified, and deleted.
 *
 * ## VARIABLES ##
 * - private string $name
 * - private string $action
 * - private array $paged
 * - private int $count
 *
 * ## METHODS ##
 * - public __construct(string $name, string $action)
 * LISTS, FORMS, & ACTIONS:
 * - public listThemes(): void
 * - public createTheme(): void
 * - public activateTheme(): void
 * - public deleteTheme(): void
 * VALIDATION:
 * - private validateSubmission(array $data): string
 * MISCELLANEOUS:
 * - public pageHeading(): void
 * - private exitNotice(string $exit_status, int $status_code): string
 * - private themeExists(string $name): bool
 * - private isActiveTheme(string $name): bool
 * - private isBrokenTheme(string $path): bool
 * - private recursiveDelete(string $dir): void
 */
class Theme {
	/**
	 * The theme's name.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $name;
	
	/**
	 * The current action.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var string
	 */
	private $action;
	
	/**
	 * The pagination.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var array
	 */
	private $paged = array();
	
	/**
	 * The number of available themes.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access private
	 * @var int
	 */
	private $count;
	
	/**
	 * Class constructor.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access public
	 * @param string $name -- The theme's name.
	 * @param string $action -- The current action.
	 */
	public function __construct(string $name, string $action) {
		$this->name = $name;
		$this->action = $action;
	}
	
	/*------------------------------------*\
		LISTS, FORMS, & ACTIONS
	\*------------------------------------*/
	
	/**
	 * Construct a list of all installed themes.
	 * @since 2.3.0-alpha
	 *
	 * @access public
	 */
	public function listThemes(): void {
		global $rs_query;
		
		// Pagination still needs work, listed as experimental in changelog
		// Revisit in later update
		$per_page = 6;
		
		// Query vars
		$search = $_GET['search'] ?? null;
		$this->paged = paginate((int)($_GET['paged'] ?? 1), $per_page);
		
		if(file_exists(PATH . THEMES)) {
			$themes = array_diff(scandir(PATH . THEMES), array('.', '..'));
			
			if(!is_null($search))
				$this->count = 0;
			else
				$this->count = count($themes);
		} else {
			$themes = array();
			$this->count = 0;
		}
		
		if(is_null($search) && $this->paged['current'] === 1) {
			// Remove the active theme from the array
			$active = array_search(getSetting('theme'), $themes, true);
			unset($themes[$active]);
		}
		
		#if(is_null($search))
		$themes = array_slice($themes, $this->paged['start'], $per_page - 1);
		
		if(is_null($search) && $this->paged['current'] === 1) {
			// Place the active theme at the begining of the array
			array_unshift($themes, getSetting('theme'));
		}
		
		if(!is_null($search)) {
			foreach($themes as $theme) {
				if(!str_contains($theme, $search)) continue;
				
				$this->count++;
			}
		}
		
		$this->pageHeading();
		?>
		<ul class="data-list clear">
			<?php
			foreach($themes as $theme) {
				if(!is_null($search) && !str_contains($theme, $search)) continue;
				
				$theme_path = slash(PATH . THEMES) . $theme;
				$is_broken = $this->isBrokenTheme($theme_path);
				
				$actions = array(
					// Activate
					userHasPrivilege('can_edit_themes') && !$is_broken ? actionLink('activate', array(
						'caption' => 'Activate',
						'name' => $theme
					)) : null,
					// Delete
					userHasPrivilege('can_delete_themes') ? actionLink('delete', array(
						'classes' => 'modal-launch delete-item',
						'data_item' => 'theme',
						'caption' => 'Delete',
						'name' => $theme
					)) : null
				);
				
				// Filter out any empty actions
				$actions = array_filter($actions);
				?>
				<li>
					<div class="theme-preview">
						<?php
						if($is_broken) {
							echo domTag('span', array(
								'class' => 'error',
								'content' => 'Warning:' . domTag('br') . 'missing index.php file'
							));
						} elseif(file_exists($theme_path . '/preview.png')) {
							echo domTag('img', array(
								'src' => slash(THEMES) . $theme . '/preview.png',
								'alt' => ucwords(str_replace('-', ' ', $theme)) . ' theme preview'
							));
						} else {
							echo domTag('span', array(
								'content' => 'No theme preview'
							));
						}
						?>
					</div>
					<h2 class="theme-name">
						<?php
						echo ucwords(str_replace('-', ' ', $theme)) . ($this->isActiveTheme($theme) ?
							' &mdash; ' . domTag('small', array(
								'content' => domTag('em', array(
									'content' => 'active'
								))
							)) : '');
						
						echo domTag('span', array(
							'class' => 'actions',
							'content' => (!$this->isActiveTheme($theme) ? implode(' &bull; ', $actions) : '')
						));
						?>
					</h2>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
		// Set up page navigation
		echo pagerNav($this->paged['current'], $this->paged['count']);
		
        include_once PATH . ADMIN . INC . '/modal-delete.php';
	}
	
	/**
	 * Create a new theme.
	 * @since 2.3.1-alpha
	 *
	 * @access public
	 */
	public function createTheme(): void {
		$this->pageHeading();
		?>
		<div class="data-form-wrap clear">
			<form class="data-form" action="" method="post" autocomplete="off">
				<table class="form-table">
					<?php
					// Name
					echo formRow(array('Name', true), array(
						'tag' => 'input',
						'id' => 'slug-field',
						'class' => 'text-input required invalid init',
						'name' => 'name',
						'value' => ($_POST['name'] ?? ''),
						'autocomplete' => 'off'
					));
					
					// Separator
					echo formRow('', array(
						'tag' => 'hr',
						'class' => 'separator'
					));
					
					// Submit button
					echo formRow('', array(
						'tag' => 'input',
						'type' => 'submit',
						'class' => 'submit-input button',
						'name' => 'submit',
						'value' => 'Create Theme'
					));
					?>
				</table>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Activate an inactive theme.
	 * @since 2.3.1-alpha
	 *
	 * @access public
	 */
	public function activateTheme(): void {
		global $rs_query;
		
		$theme_path = slash(PATH . THEMES) . $this->name;
		
		if(!empty($this->name) && $this->themeExists($this->name) && !$this->isActiveTheme($this->name) &&
			!$this->isBrokenTheme($theme_path)
		) {
			$rs_query->update('settings', array(
				's_value' => $this->name
			), array(
				's_name' => 'theme'
			));
		}
		
		redirect(ADMIN_URI . '?exit_status=activate_success');
	}
	
	/**
	 * Delete an existing theme.
	 * @since 2.3.1-alpha
	 *
	 * @access public
	 */
	public function deleteTheme(): void {
		if(!empty($this->name) && $this->themeExists($this->name) && !$this->isActiveTheme($this->name)) {
			$this->recursiveDelete(slash(PATH . THEMES) . $this->name);
			
			redirect(ADMIN_URI . '?exit_status=del_success');
		}
		
		redirect(ADMIN_URI . '?exit_status=del_failure');
	}
	
	/*------------------------------------*\
		VALIDATION
	\*------------------------------------*/
	
	/**
	 * Validate the form data.
	 * @since 2.3.1-alpha
	 *
	 * @access private
	 * @param array $data -- The submission data.
	 * @return string
	 */
	private function validateSubmission(array $data): string {
		if(empty($data['name'])) {
			return exitNotice('REQ', -1);
			exit;
		}
		
		$name = sanitize($data['name'], '/[^a-z0-9\-]/');
		
		if($this->themeExists($name)) {
			return exitNotice('That theme already exists. Please choose a different name.', -1);
			exit;
		}
		
		$theme_path = slash(PATH . THEMES) . $name;
		
		// Create the theme directory and index.php
		mkdir($theme_path);
		file_put_contents($theme_path . '/index.php', array("<?php\r\n", '// Start building your new theme!'));
		
		redirect(ADMIN_URI . '?exit_status=create_success');
	}
	
	/*------------------------------------*\
		MISCELLANEOUS
	\*------------------------------------*/
	
	/**
	 * Construct the page heading.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @access public
	 */
	public function pageHeading(): void {
		switch($this->action) {
			case 'create':
				$title = 'Create Theme';
				$message = isset($_POST['submit']) ? $this->validateSubmission($_POST) : '';
				break;
			default:
				$title = 'Themes';
				$search = $_GET['search'] ?? null;
		}
		?>
		<div class="heading-wrap clear">
			<?php
			// Page title
			echo domTag('h1', array(
				'content' => $title
			));
			
			if(!empty($this->action)) {
				// Status messages
				echo $message;
				
				// Exit notices
				if(isset($_GET['exit_status']))
					echo $this->exitNotice($_GET['exit_status']);
			} else {
				// Create button
				if(userHasPrivilege('can_create_themes')) {
					echo actionLink('create', array(
						'classes' => 'button',
						'caption' => 'Create New'
					));
				}
				
				// Search
				recordSearch();
				
				//Info
				adminInfo();
				
				echo domTag('hr');
				
				// Exit notices
				if(isset($_GET['exit_status'])) {
					if($_GET['exit_status'] === 'del_failure')
						$status_code = -1;
					
					if(isset($status_code))
						echo $this->exitNotice($_GET['exit_status'], $status_code);
					else
						echo $this->exitNotice($_GET['exit_status']);
				}
				
				// Record count
				$count = $this->count;
				
				echo domTag('div', array(
					'class' => 'entry-count',
					'content' => $count . ' ' . ($count === 1 ? 'entry' : 'entries')
				));
				
				$this->paged['count'] = ceil($count / $this->paged['per_page']);
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Generate an exit notice.
	 * @since 1.4.0-beta_snap-02
	 *
	 * @param string $exit_status -- The exit status.
	 * @param int $status_code (optional) -- The type of notice to display.
	 * @return string
	 */
	private function exitNotice(string $exit_status, int $status_code = 1): string {
		return exitNotice(match($exit_status) {
			'create_success' => 'The theme was successfully created.',
			'activate_success' => 'The theme was successfully activated.',
			'del_success' => 'The theme was successfully deleted.',
			'del_failure' => 'The theme could not be deleted.',
			default => 'The action was completed successfully.'
		}, $status_code);
	}
	
	/**
	 * Check whether a specified theme exists.
	 * @since 2.3.1-alpha
	 *
	 * @access private
	 * @param string $name -- The theme's name.
	 * @return bool
	 */
	private function themeExists(string $name): bool {
		$themes = array_diff(scandir(PATH . THEMES), array('.', '..'));
		
		foreach($themes as $theme)
			if($theme === $name) return true;
		
		return false;
	}
	
	/**
	 * Check whether a specified theme is the active theme.
	 * @since 2.3.1-alpha
	 *
	 * @access private
	 * @param string $name -- The theme's name.
	 * @return bool
	 */
	private function isActiveTheme(string $name): bool {
		return $name === getSetting('theme');
	}
	
	/**
	 * Check whether a theme is broken.
	 * @since 1.3.9-beta
	 *
	 * @access private
	 * @param string $path -- The theme's file path.
	 * @return bool
	 */
	private function isBrokenTheme(string $path): bool {
		return !file_exists($path . '/index.php');
	}
	
	/**
	 * Recursively delete files and directories.
	 * @since 2.3.1-alpha
	 *
	 * @access private
	 * @param string $dir
	 */
	private function recursiveDelete(string $dir): void {
		$contents = array_diff(scandir($dir), array('.', '..'));
		
		foreach($contents as $content) {
			// If the content is a directory, recursively delete its contents, otherwise delete the file
			is_dir($dir . '/' . $content) ? recursiveDelete($dir . '/' . $content) : unlink($dir . '/' . $content);
		}
		
		rmdir($dir);
	}
}