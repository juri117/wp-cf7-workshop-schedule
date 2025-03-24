<?php


function datesAreOnSameDay($first, $second)
{
    return $first->format('Y') === $second->format('Y') &&
        $first->format('m') === $second->format('m') &&
        $first->format('d') === $second->format('d');
}

function updateEvent($events, $event_id, $date_id, $calendar_list, $config_form, $page_slug)
{
    return getEventDataHtml($events, $event_id, $date_id, $calendar_list, $config_form, $page_slug);
}

function isConfirmed($data, $date_id)
{
    $key = "_date_confirm_{$date_id}";
    if (property_exists($data, $key)) {
        return $data->$key == "1";
    }
    return false;
}

function isRejected($data, $date_id)
{
    $key = "_date_confirm_{$date_id}";
    if (property_exists($data, $key)) {
        return $data->$key == "0";
    }
    return false;
}

function getEventDataHtml($events, $event_id, $date_id, $calendar_list, $config_form, $page_slug)
{
    //global  $field_name_patches, $admin_field_name_patches, $controlls, $calendar_json;

    //echo var_dump($events);
    $data = $events[$event_id];

    $header_cls = "cls-option";
    if (isConfirmed($data, $date_id)) {
        $header_cls = "cls-confirmed";
    }
    if (isRejected($data, $date_id)) {
        $header_cls = "cls-disabled";
    }

    $out = "<div class=\"info-box\">";
    $out .= "<table class=\"table-info\">";

    $date_key = "date{$date_id}";
    $time_key = "time{$date_id}";
    $out .= "<tr class=\"{$header_cls}\"><th><strong>" . get_date_str($data->$date_key) . "</strong> um <strong>{$data->$time_key}</strong></th><th>{$data->name_einrichtung}</th></tr>";

    //echo var_dump($config_form);
    $out .= display_fields($data, $config_form["field_name_patches"]);

    $i_am_team = false;
    if (isset($config_form["controlls"]["team_checkin"])) {
        $out .= "<tr class=\"tr-new-sub-section\"><td>Workshopleiter_innen</dt><td>";


        $user = wp_get_current_user();
        for ($i = 1; $i <= 10; $i++) {
            $key = "_team{$i}_{$date_id}";
            if (property_exists($data, $key)) {
                if ($data->$key == $user->user_login) {
                    $i_am_team = true;
                }
            }
        }

        $confirm_key = "_date_confirm_{$date_id}";
        if ($i_am_team) {
            $out .= add_form_button($event_id, $date_id, "un_add_me", "mich austragen", !property_exists($data, $confirm_key));
        } else {
            $out .= add_form_button($event_id, $date_id, "add_me", "mich eintragen", !property_exists($data, $confirm_key));
        }

        $out .= "</td></tr>";

        for ($i = 1; $i <= 10; $i++) {
            $key = "_team{$i}_{$date_id}";
            if (property_exists($data, $key) && $data->$key != "") {
                $out .= "<tr><td>{$i}.</dt><td>";
                $out .= "<div class='card' style='max-width: 100% !important; background-color:rgb(216, 236, 248);'>";
                $out .= "<h2 class='title'>{$data->$key}</h2>";

                $out .= "<div class='infobox'>";
                if (isset($config_form["controlls"]["team_checkin"]["check_field"]) && has_admin_priv() && isConfirmed($data, $date_id)) {
                    $check_key = "_check_team{$i}_{$date_id}";
                    if (!property_exists($data, $check_key)) {
                        $out .= " " . add_form_button($event_id, $date_id, "_check_team{$i}", "AWE bezahlt", true);
                    } else {
                        $out .= " - <span style=\"color:green;\">AWE bezahlt am " . get_date_str($data->$check_key) . "</span> ";
                        $out .= add_form_button($event_id, $date_id, "un_check_team{$i}", "rückgängig", true);
                    }
                }

                if ($config_form["controlls"]["team_checkin"]["admin_can_remove"] && has_admin_priv()) {
                    $out .= " " . add_form_button($event_id, $date_id, "un_add_{$i}", "austragen", true);
                }
                $out .= "</div>";
                $out .= "<hr style='border: solid 1px gray;'>";
                //$out .= "<br class='clear'>";

                $out .= "<div class='inside'>";
                // note: display/editor
                $user_not = "";
                $user_note_key = "_team_note{$i}_{$date_id}";
                if (property_exists($data, $key)) {
                    $user_not = $data->$user_note_key;
                }
                if($data->$key == $user->user_login) {
                    // note editor
                    $out .= "meine Nachricht/Notiz ans Team</br>";
                    $out .= add_text_area($event_id, $date_id, $user_note_key, $user_not);
                } else {
                    // note text
                    $out .= "<span style=\"white-space: pre-line\">{$user_not}</span>";
                }

                $out .= "</div>";
                $out .= "</div>";
                $out .= "</td></tr>";
            }
        }
    }

    // show this for legacy reasons, now we use per team member notes
    $team_note_key = "_team_note_{$date_id}";
    if (property_exists($data, $team_note_key)){
        $out .= "<tr class=\"tr-new-sub-section\"><td>Notiz ans Team</dt><td>";
        
        //$team_note_txt = "";
        //if (property_exists($data, $team_note_key)) {
        $team_note_txt = $data->$team_note_key;
        //}

        //if ($i_am_team || has_admin_priv()) {
        //    $out .= add_text_area($event_id, $date_id, "team_note", $team_note_txt);
        //} else {
        $out .= "<span style=\"white-space: pre-line\">{$team_note_txt}</span>";
        //}
        $out .= "</td></tr>";
    }

    // Admin area
    if (has_admin_priv()) {
        $out .= "<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>Administration</strong></dt></tr>";
        $out .= "<tr class=\"tr-new-sub-sub-section\"><td>Termin auswählen</dt><td>";

        $confirm_key = "_date_confirm_{$date_id}";
        if (!property_exists($data, $confirm_key)) {
            $out .= add_form_button($event_id, $date_id, "confirm_date", "bestätigen", true);
            $out .= add_form_button($event_id, $date_id, "reject_date", "ablehnen", true);
            $out .= add_form_button($event_id, $date_id, "confirm_exclusive_date", "bestätigen + andere ablehnen", true);
        } else {
            if (isConfirmed($data, $date_id)) {
                $out .= add_form_button($event_id, $date_id, "un_confirm_date", "doch nicht bestätigen", true);
            } else {
                $out .= add_form_button($event_id, $date_id, "un_reject_date", "doch nicht ablehnen", true);
            }
        }

        $out .= "</td></tr>";

        // Check fields
        if (isset($config_form["controlls"]["check_fields"])) {
            $out .= "<tr class=\"tr-new-sub-sub-section\"><td><strong>Checkliste</strong></dt><td></td></tr>";
            foreach ($config_form["controlls"]["check_fields"] as $key => $field) {
                $out .= "<tr><td>{$field["text"]}</dt><td>";
                $check_key = "{$key}_{$date_id}";
                if (property_exists($data, $check_key)) {
                    $out .= "<span style=\"color:green;\">am " . get_date_str($data->$check_key) . " </span>";
                    $out .= add_form_button($event_id, $date_id, "un{$key}", "un-check", (!isRejected($data, $date_id) || !$field["disable_if_cancled"]));
                } else {
                    $out .= add_form_button($event_id, $date_id, $key, "check", (!isRejected($data, $date_id) || !$field["disable_if_cancled"]));
                }
                $out .= "</td></tr>";
            }
        }

        // Invoice
        if (isset($config_form["controlls"]["invoice"])) {
            $price_sel_0 = "";
            $price_sel_75 = "";
            $price_sel_150 = "";
            $price_sel_custom = "";
            $price_custom_txt = "";

            $price_key = "_price_{$date_id}";
            if (property_exists($data, $price_key)) {
                switch ($data->$price_key) {
                    case "0":
                        $price_sel_0 = " checked";
                        break;
                    case "75":
                        $price_sel_75 = " checked";
                        break;
                    case "150":
                        $price_sel_150 = " checked";
                        break;
                    default:
                        $price_sel_custom = " checked";
                        $price_custom_txt = $data->$price_key;
                }
            }

            $out .= "<tr class=\"tr-new-sub-sub-section\"><td>Kosten</dt><td>";
            $out .= "<form action=\"\" method=\"POST\">";
            $out .= "<input hidden type=\"text\" id=\"event_id\" name=\"event_id\" value=\"{$event_id}\">";
            $out .= "<input hidden type=\"text\" id=\"date_id\" name=\"date_id\" value=\"{$date_id}\">";
            $out .= "<input type=\"radio\" id=\"free\" name=\"price\" value=\"0\"{$price_sel_0}><label for=\"free\">kostenlos</label></br>";
            $out .= "<input type=\"radio\" id=\"75eu\" name=\"price\" value=\"75\"{$price_sel_75}><label for=\"75eu\">75 €</label></br>";
            $out .= "<input type=\"radio\" id=\"150eu\" name=\"price\" value=\"150\"{$price_sel_150}><label for=\"150eu\">150 €</label></br>";
            $out .= "<input type=\"radio\" id=\"custom\" name=\"price\" value=\"custom\"{$price_sel_custom}>";
            $out .= "<input type=\"text\" id=\"price_txt\" name=\"price_txt\" value=\"{$price_custom_txt}\" style=\"width:80px;\"> €</br>";

            $dis = "";
            if (!isConfirmed($data, $date_id)) {
                $dis = " disabled";
            }

            $out .= "<input type=\"submit\" name=\"invoice\" value=\"speichern\"{$dis} class=\"cls-input button\">";
            $out .= "</form>";
            $out .= "</td></tr>";
        }

        if (isset($config_form["controlls"]["public_note"])) {
            $out .= "<tr class=\"tr-new-sub-section\"><td>Info für ALLE sichtbar</dt><td>";
            $pub_note_key = "_public_note_{$date_id}";
            $pub_note_txt = "";
            if (property_exists($data, $pub_note_key)) {
                $pub_note_txt = $data->$pub_note_key;
            }
            $out .= add_text_area($event_id, $date_id, "public_note", $pub_note_txt);
            $out .= "</td></tr>";
        }

        if (isset($config_form["controlls"]["private_note"])) {
            $out .= "<tr class=\"tr-new-sub-section\"><td>private Notiz<br><small>(nur für Admins sichtbar)</small></dt><td>";
            $priv_note_key = "_private_note_{$date_id}";
            $priv_note_txt = "";
            if (property_exists($data, $priv_note_key)) {
                $priv_note_txt = $data->$priv_note_key;
            }
            $out .= add_text_area($event_id, $date_id, "private_note", $priv_note_txt);
            $out .= "</td></tr>";
        }
    } else {
        if (isset($config_form["controlls"]["public_note"])) {
            $pub_note_key = "_public_note_{$date_id}";
            if (property_exists($data, $pub_note_key)) {
                $out .= "<tr class=\"tr-new-sub-section\"><td>Info für ALLE</dt><td><span style=\"white-space: pre-line\">{$data->$pub_note_key}</span></td></tr>";
            }
        }
    }

    // Other fields
    if (has_admin_priv()) {
        $out .= "<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>Sonstiges</strong></dt></tr>";
        $out .= display_fields($data, $config_form["admin_field_name_patches"]);
    }

    // Link other dates for this event
    $out .= "<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>alle Terminoptionen für dieses Event</strong></dt></tr>";
    $counter = 1;
    $out .= "<table style=\"width:100%; border-spacing: 0px 2px;\">";
    foreach ($calendar_list as $event) {
        if ($event["id"] == $event_id) {
            $current = "";
            if ($event["dateId"] == $date_id) {
                $current = "ausgewählt ↑";
            }
            $time_key = "time{$event["dateId"]}";
            $out .= "<tr style=\"cursor: pointer;\" class=\"table-info {$event["type"]}\" onclick=\"window.location='?page={$page_slug}&event_id={$event["id"]}&date_id={$event["dateId"]}';\">";
            //updateEvent(" . $event["id"] . ", " . $event["dateId"] . ");cal.loadDate(new Date('" . $event["startDate"] . "'));window.scrollTo(0,0)\">";
            $out .= "<th>{$counter}</th><th>{$current}</th><th><strong>" . get_date_str($event["startDate"]) . "</strong> um <strong>{$events[$event["id"]]->$time_key}</strong></th><th>{$event["desc"]}</th>";
            $out .= "</tr>";
            $counter++;
        }
    }
    $out .= "</table>";

    $out .= "</table></div>";
    return $out;
}

function display_fields($data, $field_definitions)
{
    $out = "";
    //echo var_dump($field_definitions);
    foreach ($field_definitions as $key => $parts) {
        $class_name = "tr-new-sub-sub-section";
        $display_key = $key;
        if (strpos($key, "-") === 0) {
            $class_name = "tr-new-sub-section";
            $display_key = substr($display_key, 1);
        }
        $out .= "<tr class=\"{$class_name}\"><td>{$display_key}</dt><td><span style=\"white-space: pre-line\">";
        foreach ($parts as $part) {
            if (strpos($part, "_") === 0) {
                $field_key = substr($part, 1);
                $value = $data->$field_key;
                // Exchange check(bool) fields with word
                if ((strpos($part, "_zustimmung") === 0 || strpos($part, "_check") === 0) && !str_ends_with($key, "_txt")) {
                    $value = ($value == "1") ? "ja" : "nein";
                }
                $out .= $value;
            } else {
                $out .= $part;
            }
        }
        $out .= "</span></td></tr>";
    }
    return $out;
}

function add_form_button($event_id, $date_id, $action_name, $button_text, $enabled)
{
    $dis = $enabled ? "" : " disabled";
    $out = "<form action=\"\" method=\"POST\" style=\"display: inline;\">";
    $out .= "<input hidden type=\"text\" id=\"event_id\" name=\"event_id\" value=\"{$event_id}\">";
    $out .= "<input hidden type=\"text\" id=\"date_id\" name=\"date_id\" value=\"{$date_id}\">";
    $out .= "<input type=\"submit\" name=\"{$action_name}\" value=\"{$button_text}\"{$dis} class=\"cls-input button\">";
    $out .= "</form>";
    return $out;
}

function add_text_area($event_id, $date_id, $action_name, $text)
{
    $out = "<form action=\"\" method=\"POST\">";
    $out .= "<input hidden type=\"text\" id=\"event_id\" name=\"event_id\" value=\"{$event_id}\">";
    $out .= "<input hidden type=\"text\" id=\"date_id\" name=\"date_id\" value=\"{$date_id}\">";
    $out .= "<textarea id=\"note\" name=\"note\" rows=\"4\" style=\"width:100%;\" class=\"cls-input\">";
    $out .= $text;
    $out .= "</textarea></br>";
    $out .= "<input type=\"submit\" name=\"{$action_name}\" value=\"speichern\" class=\"cls-input button\">";
    $out .= "</form>";
    return $out;
}

function add_selection($event_id, $date_id, $action_name, $options, $selected, $txt)
{
    $out = "<form action=\"\" method=\"POST\">";
    $out .= "<input hidden type=\"text\" id=\"event_id\" name=\"event_id\" value=\"{$event_id}\">";
    $out .= "<input hidden type=\"text\" id=\"date_id\" name=\"date_id\" value=\"{$date_id}\">";
    $out .= "<input type=\"radio\" id=\"huey\" name=\"drone\" value=\"huey\">";
    $out .= "<input type=\"submit\" name=\"{$action_name}\" value=\"speichern\" class=\"cls-input button\">";
    $out .= "</form>";
    return $out;
}


function updateDay($events, $calendar_list, $date, $page_slug)
{
    //global $calendar_json, $events;

    $out = "";
    foreach ($calendar_list as $event) {
        if (datesAreOnSameDay(new DateTime($date), new DateTime($event["startDate"]))) {
            $time_key = "time{$event["dateId"]}";
            $out .= "<table style=\"cursor: pointer;\" class=\"table-info\" onclick=\"window.location='?page={$page_slug}&event_id={$event["id"]}&date_id={$event["dateId"]}';\">";
            $out .= "<tr class=\"{$event["type"]}\"><th><strong>" . get_date_str($date) . "</strong> um <strong>{$events[$event["id"]]->$time_key}</strong></th><th>{$event["desc"]}</th></tr>";
            $out .= "</table>";
        }
    }
    return $out;
}


function get_date_str($date)
{
    return wp_date('j.n.Y', strtotime($date));
}
