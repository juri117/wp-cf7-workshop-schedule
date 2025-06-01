<?php

require_once(dirname(__FILE__) . '/admin_calendar_editor.php');

function db_sign_up_user_for_date($event_id, $date_id, $user_name, $max_members, $form_key)
{
    global $wpdb;
    $sql = ("SELECT cf7_id, data_id, JSON_OBJECTAGG(name, value) AS data FROM {$wpdb->prefix}cf7_vdata_entry WHERE data_id={$event_id} GROUP BY data_id;");
    $events = $wpdb->get_results($sql);
    $event = $events[0];
    $json = json_decode($event->data);
    if (property_exists($json, "_date_confirm_{$date_id}") && !get_config_value($form_key, ["controls", "team_checkin", "enlist_after_confirm"], false)) {
        echo ("Anmeldungen für diesen Termin sind nicht mehr möglich.");
        return;
    }
    $free_slot = -1;
    for ($i = 1; $i <= $max_members; $i++) {
        $key = "_team{$i}_{$date_id}";
        if (property_exists($json, $key) && $json->{$key} != "") {
            if ($json->{$key} == $user_name) {
                echo "Workshop Leiter*in ist bereits eingetragen.";
                $free_slot = -1;
                return;
            }
        } else {
            if ($free_slot < 0) {
                $free_slot = $i;
                break;
            }
        }
    }
    if ($free_slot > 0) {
        $key = "_team{$free_slot}_{$date_id}";
        $data = array('cf7_id' => $event->cf7_id, 'data_id' => $event_id, 'name' => $key, 'value' => $user_name);
        $format = array('%d', '%d', '%s', '%s');
        $wpdb->insert("{$wpdb->prefix}cf7_vdata_entry", $data, $format);
    } else {
        echo "Workshop ist bereits voll.";
    }
}

function remove_user($event_id, $date_id, $user_name, $max_members)
{
    global $wpdb;
    $sql = ("SELECT cf7_id, data_id, JSON_OBJECTAGG(name, value) AS data FROM {$wpdb->prefix}cf7_vdata_entry WHERE data_id={$event_id} GROUP BY data_id;");
    $events = $wpdb->get_results($sql);
    $event = $events[0];
    $json = json_decode($event->data);
    $my_key = "";
    for ($i = 1; $i <= $max_members; $i++) {
        $key = "_team{$i}_{$date_id}";
        if (property_exists($json, $key) && $json->{$key} != "") {
            if ($json->{$key} == $user_name) {
                $my_key = $key;
                break;
            }
        }
    }
    if ($my_key != "") {
        db_remove_field($event_id, $my_key);
    }
}


function db_update_field($event_id, $field_name, $new_value)
{
    global $wpdb;
    $sql = ("SELECT cf7_id, data_id, JSON_OBJECTAGG(name, value) AS data FROM {$wpdb->prefix}cf7_vdata_entry WHERE data_id={$event_id} GROUP BY data_id;");
    $events = $wpdb->get_results($sql);
    $event = $events[0];
    $json = json_decode($event->data);
    if (property_exists($json, $field_name)) {
        # update
        $data = array('value' => $new_value);
        $where = array('cf7_id' => $event->cf7_id, 'data_id' => $event_id, 'name' => $field_name);
        $wpdb->update("{$wpdb->prefix}cf7_vdata_entry", $data, $where, array('%s'), array('%d', '%d', '%s'));
    } else {
        # insert
        $data = array('cf7_id' => $event->cf7_id, 'data_id' => $event_id, 'name' => $field_name, 'value' => $new_value);
        $format = array('%d', '%d', '%s', '%s');
        $wpdb->insert("{$wpdb->prefix}cf7_vdata_entry", $data, $format);
    }
}

function db_remove_field($event_id, $field_name)
{
    global $wpdb;
    $sql = ("SELECT cf7_id, data_id, JSON_OBJECTAGG(name, value) AS data FROM {$wpdb->prefix}cf7_vdata_entry WHERE data_id={$event_id} GROUP BY data_id;");
    $events = $wpdb->get_results($sql);
    $event = $events[0];
    $where = array('cf7_id' => $event->cf7_id, 'data_id' => $event_id, 'name' => $field_name);
    $wpdb->delete("{$wpdb->prefix}cf7_vdata_entry", $where, array('%d', '%d', '%s'));
}




function my_admin_page($form_key)
{

    if (!is_user_logged_in()) {
        echo "not logged in, abort!";
        return;
    }
    global $wpdb;
    $page_slug = $_GET['page'];

    $user = wp_get_current_user();
    $user_name = $user->user_login;
    $year = date("Y");
    $month = date("m");
    if (isset($_GET["year"])) {
        $year = $_GET["year"];
    }
    if (isset($_GET["month"])) {
        $month = str_pad($_GET["month"], 2, '0', STR_PAD_LEFT);
    }
    if (isset($_GET["event_id"]) && isset($_GET["date_id"])) {
        $sql_event_date = "SELECT value FROM {$wpdb->prefix}cf7_vdata_entry WHERE name = 'date{$_GET["date_id"]}' AND data_id = {$_GET["event_id"]};";
        //echo $sql_event_date;
        $events = $wpdb->get_results($sql_event_date);
        if (!empty($events) && isset($events[0]->value)) {
            $event_date = $events[0]->value;
            $year = date('Y', strtotime($event_date));
            $month = date('m', strtotime($event_date));
        }
    }

    # $config_form = get_config()[$form_key];
    $form_title = get_config_value($form_key, "title");
    $form_id = get_config_value($form_key, "form_id");
    #$secret_fields = get_config()[$form_key]["secret_fields"];
    $check_fields = [];
    if (array_key_exists("check_fields", get_config_value($form_key, "controls"))) {
        $check_fields = get_config_value($form_key, ["controls", "check_fields"]);
    }
    # $special_check_fields = get_config()[$form_key]["special_check_fields"];
    #$field_name_patches = get_config_value($form_key, "field_name_patches");
    #$admin_field_name_patches = get_config_value($form_key, "admin_field_name_patches");
    #$controls = get_config_value($form_key, "controls");

    $special_check_fields = [];
    if (array_key_exists("team_checkin", get_config_value($form_key, "controls"))) {
        for ($i = 1; $i <= get_config_value($form_key, ["controls", "team_checkin", "max_members"], 0); $i++) {
            $special_check_fields[] = "_check_team{$i}";
        }
    }

    #$secret_fields = array_merge($secret_fields, array_keys($check_fields), $special_check_fields);

    if (has_admin_priv()) {
        if (isset($_POST["confirm_date"])) {
            $key = "_date_confirm_{$_POST["date_id"]}";
            db_update_field($_POST["event_id"], $key, "1");
        }
        if (isset($_POST["reject_date"])) {
            $key = "_date_confirm_{$_POST["date_id"]}";
            db_update_field($_POST["event_id"], $key, "0");
        }
        if (isset($_POST["confirm_exclusive_date"])) {
            for ($i = 1; $i <= 4; $i++) {
                $key = "_date_confirm_{$i}";
                if ($i == $_POST["date_id"]) {
                    db_update_field($_POST["event_id"], $key, "1");
                } else {
                    db_update_field($_POST["event_id"], $key, "0");
                }
            }
        }
        if (isset($_POST["un_confirm_date"]) || isset($_POST["un_reject_date"])) {
            $key = "_date_confirm_{$_POST["date_id"]}";
            db_remove_field($_POST["event_id"], $key);
        }
        if (isset($_POST["date_id"])) {
            foreach (array_merge(array_keys($check_fields), $special_check_fields) as $key) {
                
                if (isset($_POST[$key])) {
                    db_update_field($_POST["event_id"], "{$key}_{$_POST["date_id"]}", date("Y-m-d"));
                }
                if (isset($_POST["un{$key}"])) {
                    db_remove_field($_POST["event_id"], "{$key}_{$_POST["date_id"]}");
                }
            }
        }
        if (isset($_POST["private_note"])) {
            db_update_field($_POST["event_id"], "_private_note_{$_POST["date_id"]}", $_POST["note"]);
        }
        if (isset($_POST["public_note"])) {
            db_update_field($_POST["event_id"], "_public_note_{$_POST["date_id"]}", $_POST["note"]);
        }
        if (isset($_POST["invoice"])) {
            $price = $_POST["price_txt"];
            if ($_POST["price"] != "custom") {
                $price = $_POST["price"];
            }
            db_update_field($_POST["event_id"], "_price_{$_POST["date_id"]}", $price);
        }
    }
    //if (isset($_POST["team_note"])) {
    //    db_update_field($_POST["event_id"], "_team_note_{$_POST["date_id"]}", $_POST["note"]);
    //}


    if (array_key_exists("team_checkin", get_config_value($form_key, "controls"))) {
        $max_members = get_config_value($form_key, ["controls", "team_checkin", "max_members"], 0);
        if (isset($_POST["add_me"])) {
            db_sign_up_user_for_date($_POST["event_id"], $_POST["date_id"], $user_name, $max_members, $form_key);
        }
        if (isset($_POST["un_add_me"])) {
            remove_user($_POST["event_id"], $_POST["date_id"], $user_name, $max_members);
        }
        if (isset($_POST["date_id"]) && has_admin_priv()) {
            for ($i = 1; $i <= $max_members; $i++) {
                $key = "_team{$i}_{$_POST["date_id"]}";
                if (isset($_POST["un_add_{$i}"])) {
                    try {
                        db_remove_field($_POST["event_id"], $key);
                        $user_note_key = "_team_note{$i}_{$_POST["date_id"]}";
                        db_remove_field($_POST["event_id"], $user_note_key);
                    } catch (Exception $e) {
                    }
                    break;
                }
            }
        }
    }

    // check team note
    if (isset($_POST["date_id"])) {
        for ($i = 1; $i <= $max_members; $i++) {
            $user_note_key = "_team_note{$i}_{$_POST["date_id"]}";
            //echo $user_note_key;
            //echo "<br>";
            if (isset($_POST[$user_note_key])) {
                # echo "set!!!!!!";
                db_update_field($_POST["event_id"], $user_note_key, $_POST["note"]);
            }
        }
    }



    $sql_event_ids = "SELECT DISTINCT data_id FROM {$wpdb->prefix}cf7_vdata_entry WHERE name LIKE 'date_' AND value LIKE '{$year}-{$month}-__';";
    $event_ids = $wpdb->get_results($sql_event_ids);
    $event_ids = array_map(function ($event) {
        return $event->data_id;
    }, $event_ids);

    $events_map = array();
    $data_json_list = array();
    $calendar_list = array();
    $calendar_json_list = array();

    if (sizeof($event_ids) > 0) {
        $sql_events = "SELECT data_id, JSON_OBJECTAGG(name, value) AS data FROM {$wpdb->prefix}cf7_vdata_entry WHERE cf7_id = {$form_id} AND data_id IN (" . implode(',', $event_ids) . ") GROUP BY data_id;";
        //echo $sql_events;
        $events = $wpdb->get_results($sql_events);
        foreach ($events as $event) {
            $json = json_decode($event->data);
            /*
        if (!has_admin_priv()) {
            # remove secret fields
            foreach ($secret_fields as $secret) {
                if ($secret[0] == "_") {
                    for ($i = 1; $i <= 4; $i++) {
                        if (property_exists($json, "{$secret}_{$i}"))
                            unset($json->{"{$secret}_{$i}"});
                    }
                } else {
                    unset($json->{$secret});
                }
            }
        }
        */

            $data_json_list[] =  "{$event->data_id}:" . json_encode($json); #$event->data;
            $events_map[$event->data_id] = $json;

            for ($i = 1; $i <= 4; $i++) {
                $date_name = "date{$i}";
                $date_str = $json->{$date_name};
                #echo ($date_name . "->" . $date_str);
                $cls = "cls-option";
                if (property_exists($json, "_date_confirm_{$i}")) {
                    if ($json->{"_date_confirm_{$i}"} == "1")
                        $cls = "cls-confirmed";
                    else
                        $cls = "cls-disabled";
                }
                if ($date_str != "" && ($cls != "cls-disabled" || has_admin_priv())) {
                    $cal_json = "{ desc: '{$json->{'name_einrichtung'}}' ,startDate: new Date('{$date_str}'),endDate: new Date('{$date_str}'), type: '{$cls}', id: {$event->data_id}, dateId: {$i} }";
                    $calendar_json_list[] = $cal_json;
                    $calendar_list[] = array(
                        'desc' => $json->{'name_einrichtung'},
                        'startDate' => $date_str,
                        'endDate' => $date_str,
                        'type' => $cls,
                        'id' => $event->data_id,
                        'dateId' => $i
                    );
                }
            }
        }
    }
    $calendar_json = implode(",", $calendar_json_list);
    //$calendar_json = json_encode($calendar_list);

    $data_json = implode(",", $data_json_list);
?>
    <h1>
        <?php esc_html_e("{$form_title} Termin Management."); ?>
    </h1>


    <div class="wrapper">

        <div id="calendar-container">
            <?php
            echo "<div class='cjs-weekRow cjs-calHeader' style='width:100%; display:flex; justify-content:space-between; align-items:center; padding:10px;'>";
            echo "<a class='button' href='?page={$page_slug}&year=" . ($year - 1) . "&month=" . $month . "'>&#8249;</a>";
            echo "<strong>{$year}</strong>";
            echo "<a class='button' href='?page={$page_slug}&year=" . ($year + 1) . "&month=" . $month . "'>&#8250;</a>";
            echo "</div>";
            echo "<div class='cjs-weekRow cjs-calHeader' style='width:100%; display:flex; justify-content:space-between; align-items:center; padding:10px; overflow-x:auto;'>";
            global $wp_locale;
            for ($i = 1; $i <= 12; $i++) {
                $month_name = substr($wp_locale->get_month($i), 0, 3);
                if ($i == $month) {
                    echo "<a class='button-primary' style='margin-right: 6px;' href='?page={$page_slug}&year=" . $year . "&month=" . $i . "'><strong>" . $month_name . ".</strong></a>";
                } else {
                    echo "<a class='button' style='margin-right: 6px;' href='?page={$page_slug}&year=" . $year . "&month=" . $i . "'>" . $month_name . ".</a>";
                }
            }
            //echo "<a class='button' href='?page={$page_slug}&year=" . $year  . "&month=" . ($month - 1) . "'>&#8249;</a>";
            echo "</div><hr>";
            ?>
            <div id="calendar"></div>
        </div>

        <!-- <div id="note">sollte hier beim Klicken im Kalender nichts erscheinen bitte einmal strg+shift+R drücken (das lädt all Skripte neu).</div> -->
        <div id="note">
            <?php
            if (isset($_GET["event_id"]) && isset($_GET["date_id"])) {
                echo updateEvent($events_map, $_GET["event_id"], $_GET["date_id"], $calendar_list, $form_key, $page_slug);
            } else if (isset($_GET["day"])) {
                echo updateDay($events_map, $calendar_list, "{$year}-{$month}-{$_GET["day"]}", $page_slug);
            } else {
                //echo "sollte hier beim Klicken im Kalender nichts erscheinen bitte einmal strg+shift+R drücken (das lädt all Skripte neu).";
            }
            ?>
        </div>

    </div>

    <script type="text/javascript">

        var calendar_json = [
            <?php echo $calendar_json; ?>
        ];

        var opts = {
            abbrDay: true,
            firstDay: 1,
            abbrYear: false,
            year: <?php echo $year; ?>,
            month: <?php echo $month; ?>,
            //onDayClick: updateDay,
            onDayClick: function onDay(day, events) {
                //alert("?page=sample-page&year=" + day.getFullYear() + "&month=" + (day.getMonth() + 1));
                window.location.href = "?page=<?php echo $page_slug ?>&year=" + day.getFullYear() + "&month=" + (day.getMonth() + 1) + "&day=" + day.getDate();
            },
            onMonthChanged: function onMonthChanged(month, year) {
                window.location.href = "?page=<?php echo $page_slug ?>&year=" + year + "&month=" + month;
            },
            onEventClick: function onEvent(event, month, year) {
                //updateEvent(event.id, event.dateId);
                window.location.href = "?page=<?php echo $page_slug ?>&event_id=" + event.id + "&date_id=" + event.dateId;
            },
            events: calendar_json
        };
        var ele = document.getElementById('calendar');
        var cal = new calendar(ele, opts);
    </script>


<?php
}
?>