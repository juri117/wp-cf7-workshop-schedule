<?php


function db_sign_up_user_for_date($event_id, $date_id, $user_name, $max_members)
{
    global $wpdb;
    $sql = ("SELECT cf7_id, data_id, JSON_OBJECTAGG(name, value) AS data FROM " .
        $wpdb->prefix . "cf7_vdata_entry WHERE data_id=" . $event_id . " GROUP BY data_id;");
    $posts = $wpdb->get_results($sql);
    $post = $posts[0];
    $json = json_decode($post->data);
    if (property_exists($json, "_date_confirm_" . $date_id)) {
        echo ("Anmeldungen für diesen Termin sind nicht mehr möglich.");
        return;
    }
    $free_slot = -1;
    for ($i = 1; $i <= $max_members; $i++) {
        $key = "_team" . $i . "_" . $date_id;
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
        $key = "_team" . $free_slot . "_" . $date_id;
        $data = array('cf7_id' => $post->cf7_id, 'data_id' => $event_id, 'name' => $key, 'value' => $user_name);
        $format = array('%d', '%d', '%s', '%s');
        $wpdb->insert($wpdb->prefix . "cf7_vdata_entry", $data, $format);
    } else {
        echo "Workshop ist bereits voll.";
    }
}

function remove_user($event_id, $date_id, $user_name, $max_members)
{
    global $wpdb;
    $sql = ("SELECT cf7_id, data_id, JSON_OBJECTAGG(name, value) AS data FROM " .
        $wpdb->prefix . "cf7_vdata_entry WHERE data_id=" . $event_id . " GROUP BY data_id;");
    $posts = $wpdb->get_results($sql);
    $post = $posts[0];
    $json = json_decode($post->data);
    $my_key = "";
    for ($i = 1; $i <= $max_members; $i++) {
        $key = "_team" . $i . "_" . $date_id;
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
    $sql = ("SELECT cf7_id, data_id, JSON_OBJECTAGG(name, value) AS data FROM " .
        $wpdb->prefix . "cf7_vdata_entry WHERE data_id=" . $event_id . " GROUP BY data_id;");
    $posts = $wpdb->get_results($sql);
    $post = $posts[0];
    $json = json_decode($post->data);
    if (property_exists($json, $field_name)) {
        # update
        $data = array('value' => $new_value);
        $where = array('cf7_id' => $post->cf7_id, 'data_id' => $event_id, 'name' => $field_name);
        $wpdb->update($wpdb->prefix . "cf7_vdata_entry", $data, $where, array('%s'), array('%d', '%d', '%s'));
    } else {
        # insert
        $data = array('cf7_id' => $post->cf7_id, 'data_id' => $event_id, 'name' => $field_name, 'value' => $new_value);
        $format = array('%d', '%d', '%s', '%s');
        $wpdb->insert($wpdb->prefix . "cf7_vdata_entry", $data, $format);
    }
}

function db_remove_field($event_id, $field_name)
{
    global $wpdb;
    $sql = ("SELECT cf7_id, data_id, JSON_OBJECTAGG(name, value) AS data FROM " .
        $wpdb->prefix . "cf7_vdata_entry WHERE data_id=" . $event_id . " GROUP BY data_id;");
    $posts = $wpdb->get_results($sql);
    $post = $posts[0];
    $where = array('cf7_id' => $post->cf7_id, 'data_id' => $event_id, 'name' => $field_name);
    $wpdb->delete($wpdb->prefix . "cf7_vdata_entry", $where, array('%d', '%d', '%s'));
}




function my_admin_page($form_key)
{

    if (!is_user_logged_in()) {
        echo "not logged in, abort!";
        return;
    }
    global $wpdb;
    $user = wp_get_current_user();
    $user_name = $user->user_login;

    $form_title = get_config()[$form_key]["title"];
    $form_id = get_config()[$form_key]["form_id"];
    $secret_fields = get_config()[$form_key]["secret_fields"];
    $check_fields = [];
    if (array_key_exists("check_fields", get_config()[$form_key]["controlls"])) {
        $check_fields = get_config()[$form_key]["controlls"]["check_fields"];
    }
    # $special_check_fields = get_config()[$form_key]["special_check_fields"];
    $field_name_patches = get_config()[$form_key]["field_name_patches"];
    $admin_field_name_patches = get_config()[$form_key]["admin_field_name_patches"];
    $controlls = get_config()[$form_key]["controlls"];

    $special_check_fields = [];
    if (array_key_exists("team_checkin", get_config()[$form_key]["controlls"])) {
        for ($i = 1; $i <= get_config()[$form_key]["controlls"]["team_checkin"]["max_members"]; $i++) {
            $special_check_fields[] = "_check_team" . $i;
        }
    }

    $secret_fields = array_merge($secret_fields, array_keys($check_fields), $special_check_fields);

    if (has_admin_priv()) {
        if (isset($_POST["confirm_date"])) {
            $key = "_date_confirm_" . $_POST["date_id"];
            db_update_field($_POST["event_id"], $key, "1");
        }
        if (isset($_POST["reject_date"])) {
            $key = "_date_confirm_" . $_POST["date_id"];
            db_update_field($_POST["event_id"], $key, "0");
        }
        if (isset($_POST["confirm_exclusive_date"])) {
            for ($i = 1; $i <= 4; $i++) {
                $key = "_date_confirm_" . $i;
                if ($i == $_POST["date_id"]) {
                    db_update_field($_POST["event_id"], $key, "1");
                } else {
                    db_update_field($_POST["event_id"], $key, "0");
                }
            }
        }
        if (isset($_POST["un_confirm_date"]) || isset($_POST["un_reject_date"])) {
            $key = "_date_confirm_" . $_POST["date_id"];
            db_remove_field($_POST["event_id"], $key);
        }
        if (isset($_POST["date_id"])) {
            foreach (array_merge(array_keys($check_fields), $special_check_fields) as $key) {
                # echo ("checks...");
                if (isset($_POST[$key])) {
                    db_update_field($_POST["event_id"], $key . "_" . $_POST["date_id"], date("Y-m-d"));
                }
                if (isset($_POST["un" . $key])) {
                    db_remove_field($_POST["event_id"], $key . "_" . $_POST["date_id"]);
                }
            }
        }
        if (isset($_POST["private_note"])) {
            db_update_field($_POST["event_id"], "_private_note_" . $_POST["date_id"], $_POST["note"]);
        }
        if (isset($_POST["public_note"])) {
            db_update_field($_POST["event_id"], "_public_note_" . $_POST["date_id"], $_POST["note"]);
        }
        if (isset($_POST["invoice"])) {
            $price = $_POST["price_txt"];
            if ($_POST["price"] != "custom") {
                $price = $_POST["price"];
            }
            db_update_field($_POST["event_id"], "_price_" . $_POST["date_id"], $price);
        }
    }
    if (isset($_POST["team_note"])) {
        db_update_field($_POST["event_id"], "_team_note_" . $_POST["date_id"], $_POST["note"]);
    }

    if (array_key_exists("team_checkin", get_config()[$form_key]["controlls"])) {
        $max_members = get_config()[$form_key]["controlls"]["team_checkin"]["max_members"];
        if (isset($_POST["add_me"])) {
            db_sign_up_user_for_date($_POST["event_id"], $_POST["date_id"], $user_name, $max_members);
        }
        if (isset($_POST["un_add_me"])) {
            remove_user($_POST["event_id"], $_POST["date_id"], $user_name, $max_members);
        }
        if (isset($_POST["date_id"]) && has_admin_priv()) {
            for ($i = 1; $i <= $max_members; $i++) {
                $key = "_team" . $i . "_" . $_POST["date_id"];
                if (isset($_POST["un_add_" . $i])) {
                    try {
                        db_remove_field($_POST["event_id"], $key);
                    } catch (Exception $e) {
                    }
                    break;
                }
            }
        }
    }


    $sql = "SELECT data_id, JSON_OBJECTAGG(name, value) AS data FROM " . $wpdb->prefix . "cf7_vdata_entry WHERE cf7_id = " . $form_id . " GROUP BY data_id;";
    $posts = $wpdb->get_results($sql);
    $data_json_list = array();
    $calendar_json_list = array();
    foreach ($posts as $post) {
        $json = json_decode($post->data);
        if (!has_admin_priv()) {
            # remove secret fields
            foreach ($secret_fields as $secret) {
                if ($secret[0] == "_") {
                    for ($i = 1; $i <= 4; $i++) {
                        if (property_exists($json, $secret . "_" . $i))
                            unset($json->{$secret . "_" . $i});
                    }
                } else {
                    unset($json->{$secret});
                }
            }
        }
        $data_json_list[] =  $post->data_id . ":" . json_encode($json); #$post->data;
        for ($i = 1; $i <= 4; $i++) {
            $date_name = "date" . $i;
            $date_str = $json->{$date_name};
            #echo ($date_name . "->" . $date_str);
            $cls = "cls-option";
            if (property_exists($json, "_date_confirm_" . $i)) {
                if ($json->{"_date_confirm_" . $i} == "1")
                    $cls = "cls-confirmed";
                else
                    $cls = "cls-disabled";
            }
            if ($date_str != "" && ($cls != "cls-disabled" || has_admin_priv())) {
                $cal_json = "{ desc: '" . $json->{'name_einrichtung'} . "' ,startDate: new Date('" . $date_str . "'),endDate: new Date('" . $date_str . "'), type: '" . $cls . "', id: " . $post->data_id . ", dateId: " . $i . "}";
                $calendar_json_list[] = $cal_json;
            }
        }
    }
    $calendar_json = implode(",", $calendar_json_list);
    $data_json = implode(",", $data_json_list);
?>
    <h1>
        <?php esc_html_e($form_title . ' Termin Management.'); ?>
    </h1>

    <div class="wrapper">
        <div id="calendar"></div>
        <div id="note">sollte hier beim Klicken im Kalender nichts erscheinen bitte einmal strg+shift+R drücken (das lädt all Skripte neu).</div>
    </div>

    <script type="text/javascript">
        var events = {
            <?php echo $data_json; ?>
        };
        var field_name_patches = <?php echo json_encode($field_name_patches); ?>;
        var admin_field_name_patches = <?php echo json_encode($admin_field_name_patches); ?>;
        var controlls = <?php echo json_encode($controlls); ?>;

        var calendar_json = [
            <?php echo $calendar_json; ?>
        ];
        var is_admin = <?php echo has_admin_priv(); ?>;
        var my_name = "<?php echo $user_name; ?>";

        var opts = {
            abbrDay: true,
            firstDay: 1,
            abbrYear: false,
            onDayClick: updateDay,
            onEventClick: function onEvent(event) {
                updateEvent(event.id, event.dateId);
            },
            events: calendar_json
        };
        var ele = document.getElementById('calendar');
        var cal = new calendar(ele, opts);
    </script>

    <?php
    if (isset($_POST["event_id"]) && isset($_POST["date_id"])) {
    ?>
        <script type="text/javascript">
            updateEvent(<?php echo $_POST["event_id"]; ?>, <?php echo $_POST["date_id"]; ?>);
            cal.loadDate(new Date(events[<?php echo $_POST["event_id"]; ?>]["date" + <?php echo $_POST["date_id"]; ?>]));
        </script>
<?php
    }
}
