<?php

defined('ABSPATH') || exit;

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/tables/workshop_list_table.php');
require_once(dirname(__FILE__) . '/tables/team_list_table.php');



/**
 * This function is responsible for render the drafts table
 */
function bootload_workshop_table($form_key): void
{

	$year = date("Y");
	if (isset($_GET['year'])) {
		if ($_GET['year'] != "") {
			$year = $_GET['year'];
		}
	}

	$drafts_table = new Workshop_List_Table($form_key, $year);
	$team_table = new Team_List_Table($form_key, $year);
?>
	<div class="wrap">
		<h2><?php esc_html_e('Liste', 'admin-table-tut'); ?></h2>
		<form id="year-selection" method="get">
			<input type='hidden' name='page' value='<?php echo $_GET['page']; ?>' />
			<select name="year" id="year" onchange="this.form.submit()">
				<?php
				$current = date("Y");
				for ($i = 0; $i < 4; $i++) {
					$y = $current - $i;
					$selected = "";
					if ($year == $current - $i) {
						$selected = " selected";
					}

					echo "<option value=\"" . $y . "\"" . $selected . ">" . $y . "</option>";
				}
				?>
			</select>
		</form>
		<h2>alle Workshops</h2>
		<form id="schedule-list-page" method="get">
			<input type="hidden" name="page" value="schedule-list-page" />
			<?php
			$drafts_table->prepare_items();
			#$drafts_table->search_box( 'Search', 'search' );
			$drafts_table->display();
			?>
		</form>
		<h2>Workshops pro Workshopleiter_in</h2>
		<form id="team-list-page" method="get">
			<input type="hidden" name="page" value="team-list-page" />
			<?php
			$team_table->prepare_items();
			#$drafts_table->search_box( 'Search', 'search' );
			$team_table->display();
			?>
		</form>
	</div>
<?php
}

function date_sort($object1, $object2)
{
	if ($object1["_date"] == $object2["_date"]) {
		return 0;
	}
	return ($object1["_date"] > $object2["_date"]) ? 1 : -1;
}
