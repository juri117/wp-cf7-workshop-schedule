<?php

defined( 'ABSPATH' ) || exit;

require_once(dirname(__FILE__) . '/config.php');

/**
 * Adding WP List table class if it's not available.
 */
if ( ! class_exists( \WP_List_Table::class ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Drafts_List_Table extends \WP_List_Table {

	/**
	 * Const to declare number of posts to show per page in the table.
	 */
	const POSTS_PER_PAGE = 10;

	protected string $form_key;
	protected string $year;

	/**
	 * Draft_List_Table constructor.
	 */
	public function __construct(string $form_key, string $year) {

		parent::__construct(
			array(
				'singular' => 'Draft',
				'plural'   => 'Drafts',
				'ajax'     => false,
			)
		);
		
		$this->form_key = $form_key;
		$this->year = $year;

	}

	/**
	 * Display text for when there are no items.
	 */
	public function no_items() {
		esc_html_e( 'Keine Einträge gefunden.', 'admin-table-tut' );
	}

	/**
	 * The Default columns
	 *
	 * @param  array  $item        The Item being displayed.
	 * @param  string $column_name The column we're currently in.
	 * @return string              The Content to display
	 */
	public function column_default( $item, $column_name ) {
		$result = '';
		switch ( $column_name ) {
			case 'date':
				$t_time    = get_the_time( 'Y/m/d g:i:s a', $item['id'] );
				$time      = get_post_timestamp( $item['id'] );
				$time_diff = time() - $time;

				if ( $time && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
					/* translators: %s: Human-readable time difference. */
					$h_time = sprintf( __( '%s ago', 'admin-table-tut' ), human_time_diff( $time ) );
				} else {
					$h_time = get_the_time( 'Y/m/d', $item['id'] );
				}

				$result = '<span title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $item['id'], 'date', 'list' ) . '</span>';
				break;

			case 'author':
				$result = $item['author'];
				break;

			case 'type':
				$result = $item['type'];
				break;
			default:
				$result = $item[$column_name];
				break;
		}

		return $result;
	}


	/**
	 * Prepare the data for the WP List Table
	 *
	 * @return void
	 */
	public function prepare_items() {
		$form_id = get_config()[$this->form_key]["form_id"];
		$columns_config = get_config()[$this->form_key]["admin_list_columns"];

		$columns = array();
		foreach ($columns_config as $key => $value) {
			$columns[$value] = __( $key, 'admin-table-tut' );
		}
		$sortable = array(
			#'title'  => array( 'title', true ),
			#'type'   => array( 'type', false ),
			#'date'   => array( 'date', false ),
			#'author' => array( 'author', false ),
		);
		$hidden                = array();
		$primary               = 'name_einrichtung';
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary);



		global $wpdb;
		$sql = "SELECT data_id, JSON_OBJECTAGG(name, value) AS data FROM " . $wpdb->prefix . "cf7_vdata_entry WHERE cf7_id = " . $form_id . " GROUP BY data_id;";
		$posts = $wpdb->get_results($sql);
		$data = array();
		
		foreach ($posts as $post) {
			$json = json_decode($post->data, false);
			for ($i = 1; $i <= 4; $i++) {
				$date_str = $json->{"date" . $i};
				$time_str = $json->{"time" . $i};
				# if($date_str != ""){
				if (str_starts_with($date_str,$this->year)){
					if (property_exists($json, "_date_confirm_" . $i)) {
						if ($json->{"_date_confirm_" . $i} == "1"){
							$team = array();
							for ($i_team = 1; $i_team <= 10; $i_team++) {
								$key = "_team" . $i_team . "_" . $i;
								if (property_exists($json, $key) && $json->{$key} != "") {
									$team[] = $json->{$key};
								}
							}
							$row = ["_no"=>0,
									"_date"=>$date_str." ".$time_str,
									"_team" => implode(", ", $team)];
							foreach ($columns_config as $key => $value) {
								if($value[0] != "_"){
									$row[$value] = $json->{$value};
								}
							}
							$data[] = $row;
						}
					}
				}
			}
		}

		usort($data, 'date_sort');
		for($i = 0; $i < sizeof($data); $i++){
			$data[$i]["_no"] = $i + 1;
		}

		$this->items = $data;

		#$this->set_pagination_args(
		#	array(
		#		'total_items' => $get_posts_obj->found_posts,
		#		'per_page'    => $get_posts_obj->post_count,
		#		'total_pages' => $get_posts_obj->max_num_pages,
		#	)
		#);
	}

}



/**
 * This function is responsible for render the drafts table
 */
function bootload_drafts_table($form_key) : void {
	
	$year = date("Y");
	if (isset($_POST['year'])) {
		if($_POST['year'] != ""){
			$year = $_POST['year'];
		}
	}

	$drafts_table = new Drafts_List_Table($form_key, $year);
	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Liste', 'admin-table-tut' ); ?></h2>
		<form id="year-selection" method="post">
			<select name="year" id="year" onchange="this.form.submit()">
			<?php
				$current = date("Y");
				for($i = 0; $i < 4; $i++){
					$y = $current - $i;
					$selected = "";
					if($year == $current - $i){
						$selected = " selected";
					}
					echo "<option value=\"".$y."\"".$selected.">".$y."</option>";
				}
			?>
			</select>
		</form>
		<form id="schedule-list-page" method="get">
			<input type="hidden" name="page" value="schedule-list-page" />

			<?php
			$drafts_table->prepare_items();
			#$drafts_table->search_box( 'Search', 'search' );
			$drafts_table->display();
			?>
		</form>
	</div>
	<?php
}

function date_sort($object1, $object2) {
    return $object1["_date"] > $object2["_date"];
}