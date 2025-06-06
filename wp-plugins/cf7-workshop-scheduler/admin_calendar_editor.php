<?php


function datesAreOnSameDay($first, $second)
{
    return $first->format('Y') === $second->format('Y') &&
        $first->format('m') === $second->format('m') &&
        $first->format('d') === $second->format('d');
}

function updateEvent($events, $event_id, $date_id, $calendar_list, $form_key, $page_slug)
{
    return getEventDataHtml($events, $event_id, $date_id, $calendar_list, $form_key, $page_slug);
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

function getEventDataHtml($events, $event_id, $date_id, $calendar_list, $form_key, $page_slug)
{
    //global  $field_name_patches, $admin_field_name_patches, $controls, $calendar_json;

    //echo var_dump($events);
    $data = $events[$event_id];

    $header_cls = "cls-option";
    if (isConfirmed($data, $date_id)) {
        $header_cls = "cls-confirmed";
    }
    if (isRejected($data, $date_id)) {
        $header_cls = "cls-disabled";
    }

    $out = "<div class=\"info-box card\">";
    $out .= "<h2>{$data->name_einrichtung}</h2>";
    //$out .= "<hr style='border: solid 1px gray;'>";
    $out .= "<br>";

    // show date options:

    $out .= "<table class=\"table-info\">";
    $out .= "<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>Terminoptionen für dieses Event</strong></dt></tr>";
    $out .= "<tr> <td colspan=\"2\">";
    
    $counter = 1;
    $out .= "<table class=\"table-info\" style=\"border-spacing: 0px 2px !important; border-collapse: separate;\">";
    //$out .= "<tr class=\"tr-new-section\"><td colspan=\"3\"><strong>Terminoptionen für dieses Event</strong></td></tr>";
    foreach ($calendar_list as $event) {
        if ($event["id"] == $event_id) {
            $time_key = "time{$event["dateId"]}";
            $out .= "<tr style=\"cursor: pointer;\" class=\"{$event["type"]}\" onclick=\"window.location='?page={$page_slug}&event_id={$event["id"]}&date_id={$event["dateId"]}';\">";
            //updateEvent(" . $event["id"] . ", " . $event["dateId"] . ");cal.loadDate(new Date('" . $event["startDate"] . "'));window.scrollTo(0,0)\">";
            if ($event["dateId"] == $date_id) {
                $out .= "<td><b>{$counter}</b></td><td><b>ausgewählt ↓</b></td><td><b>" . get_date_str($event["startDate"]) . " um {$events[$event["id"]]->$time_key}<b></td>";
            } else {
                $out .= "<td>{$counter}</td><td></td><td>" . get_date_str($event["startDate"]) . " um {$events[$event["id"]]->$time_key}</td>";
            }
            $out .= "</tr>";
            $counter++;
        }
    }
    $out .= "</table>";
    //$out .= "<br>";
    $out .= "</td></tr>";



    
    $out .= "<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>Infos zum Event</strong></dt></tr>";

    //$date_key = "date{$date_id}";
    //$time_key = "time{$date_id}";
    //$out .= "<tr class=\"{$header_cls}\"><th><strong>" . get_date_str($data->$date_key) . "</strong> um <strong>{$data->$time_key}</strong></th><th>{$data->name_einrichtung}</th></tr>";

    //echo var_dump($config_form);
    $out .= display_fields($data, get_config_value($form_key, "field_name_patches"));

    // Other fields
    if (has_admin_priv()) {
        $out .= "<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>Infos zum Event (nur für Admins)</strong></dt></tr>";
        $out .= display_fields($data, get_config_value($form_key, "admin_field_name_patches"));
    }

    $out .= "<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>Event planen</strong></dt></tr>";

    $i_am_team = false;
    if (get_config_value($form_key, ["controls", "team_checkin", "check_field"],false)) {
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
            $out .= add_form_button($event_id, $date_id, "add_me", "mich eintragen", !property_exists($data, $confirm_key) || get_config_value($form_key, ["controls", "team_checkin", "enlist_after_confirm"], false));
        }

        $out .= "</td></tr>";

        for ($i = 1; $i <= 10; $i++) {
            $key = "_team{$i}_{$date_id}";
            if (property_exists($data, $key) && $data->$key != "") {
                //$out .= "<tr><td>{$i}.</dt>";
                $out .= "<td colspan=\"2\">";
                $out .= "<div class='card' style='max-width: 100% !important; background-color:rgb(216, 236, 248); margin-top: 10px; margin-bottom: 10px;'>";
                $out .= "<h2 class='title'>{$i}. {$data->$key}</h2>";

                $out .= "<div class='infobox'>";
                if (get_config_value($form_key, ["controls", "team_checkin","check_field"], false) && has_admin_priv() && isConfirmed($data, $date_id)) {
                    $check_key = "_check_team{$i}_{$date_id}";
                    if (!property_exists($data, $check_key)) {
                        $out .= " " . add_form_button($event_id, $date_id, "_check_team{$i}", "AWE bezahlt", true);
                    } else {
                        $out .= " - <span style=\"color:green;\">AWE bezahlt am " . get_date_str($data->$check_key) . "</span> ";
                        $out .= add_form_button($event_id, $date_id, "un_check_team{$i}", "rückgängig", true);
                    }
                }

                if (get_config_value($form_key, ["controls", "team_checkin", "admin_can_remove"], false) && has_admin_priv()) {
                    $out .= " " . add_form_button($event_id, $date_id, "un_add_{$i}", "austragen", true);
                }
                $out .= "</div>";
                $out .= "<hr style='border: solid 1px gray;'>";
                //$out .= "<br class='clear'>";

                $out .= "<div class='inside'>";
                // note: display/editor
                $user_note = "";
                $user_note_key = "_team_note{$i}_{$date_id}";
                if (property_exists($data, $user_note_key)) {
                    $user_note = $data->$user_note_key;
                }
                if($data->$key == $user->user_login) {
                    // note editor
                    $out .= "meine Nachricht/Notiz ans Team</br>";
                    $out .= add_text_area($event_id, $date_id, $user_note_key, $user_note);
                } else {
                    // note text
                    if($user_note != ""){
                        $out .= "<small>Nachricht/Notiz von {$data->$key}</small><br>";
                        $out .= "<span class=\"cls-input-readonly\">{$user_note}</span>";
                        //$out .= "<textarea id=\"note\" name=\"note\" rows=\"4\" style=\"width:100%; color:black;\" class=\"cls-input\" disabled>{$user_not}</textarea>";
                    }
                }

                $out .= "</div>";
                $out .= "</div>";
                $out .= "</td></tr>";
            }
        }
    }
    
    // one note for whole team
    $team_note_key = "_team_note_{$date_id}";
    $team_note_txt = "";
    if (property_exists($data, $team_note_key)) {
        $team_note_txt = $data->$team_note_key;
    }
    if(get_config_value($form_key, ["controls", "team_note"], false) || $team_note_txt != "") {
        $out .= "<tr class=\"tr-new-sub-section\"><td>Team Notiz</dt><td>";
        if ($i_am_team || has_admin_priv()) {
            $out .= add_text_area($event_id, $date_id, "team_note", $team_note_txt);
        } else {
            $out .= "<span style=\"white-space: pre-line\">{$team_note_txt}</span>";
        }
        $out .= "</td></tr>";
    }

    // show note from admin for all
    if (has_admin_priv()) {
        if (get_config_value($form_key, ["controls", "public_note"], false)) {
            $out .= "<tr class=\"tr-new-sub-section\"><td>Nachricht/Notiz<br>vom Admin an ALLE</dt><td>";
            $pub_note_key = "_public_note_{$date_id}";
            $pub_note_txt = "";
            if (property_exists($data, $pub_note_key)) {
                $pub_note_txt = $data->$pub_note_key;
            }
            $out .= add_text_area($event_id, $date_id, "public_note", $pub_note_txt);
            $out .= "</td></tr>";
        }
    }    else {
        if (get_config_value($form_key, ["controls", "public_note"], false)) {
            $pub_note_key = "_public_note_{$date_id}";
            if (property_exists($data, $pub_note_key)) {
                if($data->$pub_note_key != "") {
                    $out .= "<tr class=\"tr-new-sub-section\"><td colspan=\"2\">"; //Nachricht/Notiz an alle</dt><td>";
                    $out .= "<div class='card' style='max-width: 100% !important; background-color:rgb(216, 236, 248); margin-top: 10px; margin-bottom: 10px;'>";
                    $out .= "<h2>Nachricht/Notiz an alle</h2>";

                    //$out .= "<span style=\"white-space: pre-line\">{$data->$pub_note_key}</span>";
                    //$out .= "<textarea id=\"note\" name=\"note\" rows=\"4\" style=\"width:100%; color:black;\" class=\"cls-input\" disabled>{$data->$pub_note_key}</textarea>";
                    $out .= "<span class=\"cls-input-readonly\">{$data->$pub_note_key}</span>";
                    $out .= "</div>";
                    $out .= "</td></tr>";
                }
            }
        }
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
        if (get_config_value($form_key, ["controls", "check_fields"], false)) {
            $out .= "<tr class=\"tr-new-sub-sub-section\"><td><strong>Checkliste</strong></dt><td></td></tr>";
            foreach (get_config_value($form_key, ["controls", "check_fields"], false) as $key => $field) {
                $out .= "<tr><td>{$field["text"]}</dt><td>";
                $check_key = "{$key}_{$date_id}";
                if (property_exists($data, $check_key)) {
                    $out .= "<span style=\"color:green;\">am " . get_date_str($data->$check_key) . " </span>";
                    $out .= add_form_button($event_id, $date_id, "un{$key}", "un-check", (!isRejected($data, $date_id) || !$field["disable_if_cancelled"]));
                } else {
                    $out .= add_form_button($event_id, $date_id, $key, "check", (!isRejected($data, $date_id) || !$field["disable_if_cancelled"]));
                }
                $out .= "</td></tr>";
            }
        }

        // Invoice
        if (get_config_value($form_key, ["controls", "invoice"], false)) {
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
            $out .= "<input type=\"radio\" id=\"75eu\" name=\"price\" value=\"75\"{$price_sel_75}><label for=\"75eu\">100 €</label></br>";
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

        

        if (get_config_value($form_key, ["controls", "private_note"], false)) {
            $out .= "<tr class=\"tr-new-sub-section\"><td>private Notiz<br><small>(nur für Admins sichtbar)</small></dt><td>";
            $priv_note_key = "_private_note_{$date_id}";
            $priv_note_txt = "";
            if (property_exists($data, $priv_note_key)) {
                $priv_note_txt = $data->$priv_note_key;
            }
            $out .= add_text_area($event_id, $date_id, "private_note", $priv_note_txt);
            $out .= "</td></tr>";
        }
    } 

    /*
    else {
        if (isset($config_form["controls"]["public_note"])) {
            $pub_note_key = "_public_note_{$date_id}";
            if (property_exists($data, $pub_note_key)) {
                $out .= "<tr class=\"tr-new-sub-section\"><td>Info für ALLE</dt><td><span style=\"white-space: pre-line\">{$data->$pub_note_key}</span></td></tr>";
            }
        }
    }
    */



    // Link other dates for this event
    /*
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
    */

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