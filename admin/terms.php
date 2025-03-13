<?php
/**
 * Admin terms page.
 * @since 1.0.4-beta
 *
 * @package ReallySimpleCMS
 */

require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if(isset($_GET['taxonomy'])) {
	$taxonomy = $_GET['taxonomy'];
} else {
	if($id === 0) {
		$taxonomy = 'category';
	} else {
		if(!termExists($id)) {
			redirect('categories.php');
		} else {
			// Set the taxonomy if it exists in the database
			$db_tax = $rs_query->selectField(array('terms', 't_'), 'taxonomy', array(
				'id' => $id
			));
			
			$taxonomy = $rs_query->selectField(array('taxonomies', 'ta_'), 'name', array(
				'id' => $db_tax
			)) ?? 'category';
		}
	}
}

// Redirect 'category' taxonomy
if($taxonomy === 'category') redirect('categories.php');

$rs_term = new \Admin\Term($id, $action, $rs_taxonomies[$taxonomy] ?? array());
?>
<article class="content">
	<?php
	// Create an id from the taxonomy's label
	$tax_id = str_replace(' ', '_', $rs_taxonomies[$taxonomy]['labels']['name_lowercase']);
	
	switch($action) {
		case 'create':
			// Create a new term
			userHasPrivilege('can_create_' . $tax_id) ? $rs_term->createRecord() :
				redirect($rs_taxonomies[$taxonomy]['menu_link']);
			break;
		case 'edit':
			// Edit an existing term
			userHasPrivilege('can_edit_' . $tax_id) ? $rs_term->editRecord() :
				redirect($rs_taxonomies[$taxonomy]['menu_link']);
			break;
		case 'delete':
			// Delete an existing term
			userHasPrivilege('can_delete_' . $tax_id) ? $rs_term->deleteRecord() :
				redirect($rs_taxonomies[$taxonomy]['menu_link']);
			break;
		default:
			// List all terms
			userHasPrivilege('can_view_' . $tax_id) ? $rs_term->listRecords() :
				redirect('index.php');
	}
	?>
</article>
<?php
require_once __DIR__ . '/footer.php';