
// CALENDAR

const datesAreOnSameDay = (first, second) =>
    first.getFullYear() === second.getFullYear() &&
    first.getMonth() === second.getMonth() &&
    first.getDate() === second.getDate();

function updateEvent(event_id, date_id) {
    document.getElementById("note").innerHTML = getEventDataHtml(event_id, date_id);
    //event_id + ": " + data.name_einrichtung;
}

function isConfirmed(data, date_id) {
    if (("_date_confirm_" + date_id) in data) {
        return data["_date_confirm_" + date_id] == "1";
    }
    return false;
}

function isRejected(data, date_id) {
    if (("_date_confirm_" + date_id) in data) {
        return data["_date_confirm_" + date_id] == "0";
    }
    return false;
}

function getEventDataHtml(event_id, date_id) {

    var custom_fields = ["name_einrichtung", "vorname", "nachname", "adresse",
        "adresse_extra", "adresse_plz", "adresse_bezirk", "klassenstufe", "anzahl",
        "dauer", "pause", "abholort", "anlass", "schwerpunkt", "besonderes", "corona_regeln", "anmerkung_frage",
        "date1", "time1",
        "date2", "time2",
        "date3", "time3",
        "date4", "time4",
        "submit_ip"]

    var data = events[event_id];

    var header_cls = "cls-option";
    if (isConfirmed(data, date_id)) {
        header_cls = "cls-confirmed";
    }
    if (isRejected(data, date_id)) {
        header_cls = "cls-disabled";
    }

    var out = "<div class=\"info-box\">"
    out += "<table class=\"table-info\">";
    out += ("<tr class=\"" + header_cls + "\"><th><strong>" + get_date_str(data['date' + date_id]) + "</strong> um <strong>" + data['time' + date_id] + "</strong></th><th>" + data.name_einrichtung + "</th></tr>");
    out += display_fields(data, field_name_patches);

    if ("team_checkin" in controlls) {
        out += ("<tr class=\"tr-new-sub-section\"><td>Workshopleiter_innen</dt><td>");

        var i_am_team = false;
        for (let i = 1; i <= 10; i++) {
            var key = "_team" + i + "_" + date_id;
            if (key in data) {
                if (data[key] == my_name) {
                    i_am_team = true;
                }
            }
        }
        if (i_am_team) {
            out += add_form_button(event_id, date_id, "un_add_me", "mich austragen", !(("_date_confirm_" + date_id) in data));
        } else {
            out += add_form_button(event_id, date_id, "add_me", "mich eintragen", !(("_date_confirm_" + date_id) in data));
        }

        out += ("</td></tr>");

        for (let i = 1; i <= 10; i++) {
            var key = "_team" + i + "_" + date_id;
            if (key in data) {
                if (data[key] != "") {
                    out += "<tr><td>" + i + ".</dt><td><div>";
                    out += "<strong>" + data[key] + "</strong>";
                    if ("check_field" in controlls["team_checkin"] && is_admin && isConfirmed(data, date_id)) {
                        var check_key = "_check_team" + i + "_" + date_id;
                        if (!(check_key in data)) {
                            out += " " + add_form_button(event_id, date_id, "_check_team" + i, "AWE bezahlt", true);
                        } else {
                            out += " - <span style=\"color:green;\">AWE bezahlt am " + get_date_str(data[check_key]) + "</span> ";
                            out += add_form_button(event_id, date_id, "un_check_team" + i, "rückgängig", true);
                        }
                    }
                    if (controlls["team_checkin"]["admin_can_remove"] && is_admin) {
                        out += " " + add_form_button(event_id, date_id, "un_add_" + i, "austragen", true);
                    }
                    out += "</div></td></tr>";
                }
            }

        }
    }

    out += ("<tr class=\"tr-new-sub-section\"><td>Team-Notizen</dt><td>");
    var team_note_txt = "";
    if (("_team_note" + "_" + date_id) in data) {
        team_note_txt = data["_team_note" + "_" + date_id];
    }
    if (i_am_team || is_admin) {
        out += add_text_area(event_id, date_id, "team_note", team_note_txt);
    } else {
        out += ("<span style=\"white-space: pre-line\">" + team_note_txt + "</span>");
    }
    out += ("</td></tr>");

    // admin area
    if (is_admin) {
        out += ("<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>Administration</strong></dt></tr>");
        out += ("<tr class=\"tr-new-sub-sub-section\"><td>Termin auswählen</dt><td>");

        if (!(("_date_confirm_" + date_id) in data)) {
            out += add_form_button(event_id, date_id, "confirm_date", "bestätigen", true);
            out += add_form_button(event_id, date_id, "reject_date", "ablehnen", true);
            out += add_form_button(event_id, date_id, "confirm_exclusive_date", "bestätigen + andere ablehnen", true);
        } else {
            if (isConfirmed(data, date_id)) {
                out += add_form_button(event_id, date_id, "un_confirm_date", "doch nicht bestätigen", true);
            } else {
                out += add_form_button(event_id, date_id, "un_reject_date", "doch nicht ablehnen", true);
            }
        }

        out += ("</td></tr>");

        // check fields
        if ("check_fields" in controlls) {
            out += ("<tr class=\"tr-new-sub-sub-section\"><td><strong>Checkliste</strong></dt><td></td></tr>");
            var check_fields = controlls["check_fields"];
            for (key in check_fields) {
                out += ("<tr><td>" + check_fields[key]["text"] + "</dt><td>");
                if ((key + "_" + date_id) in data) {
                    out += "<span style=\"color:green;\">am " + get_date_str(data[key + "_" + date_id]) + " </span>";
                    out += add_form_button(event_id, date_id, "un" + key, "un-check", (!isRejected(data, date_id) || !check_fields[key]["disable_if_cancled"]));
                } else {
                    out += add_form_button(event_id, date_id, key, "check", (!isRejected(data, date_id) || !check_fields[key]["disable_if_cancled"]));
                }
                out += ("</td></tr>");
            }
        }

        // invoice
        if ("invoice" in controlls) {
            var price_sel_0 = "";
            var price_sel_75 = "";
            var price_sel_150 = "";
            var price_sel_custom = "";
            var price_custom_txt = "";
            if (("_price" + "_" + date_id) in data) {
                switch (data["_price" + "_" + date_id]) {
                    case "0":
                        price_sel_0 = " checked";
                        break;
                    case "75":
                        price_sel_75 = " checked";
                        break;
                    case "150":
                        price_sel_150 = " checked";
                        break;
                    default:
                        price_sel_custom = " checked";
                        price_custom_txt = data["_price" + "_" + date_id];
                }
            }
            out += ("<tr class=\"tr-new-sub-sub-section\"><td>Kosten</dt><td>");
            out += "<form action=\"\" method=\"POST\">";
            out += "<input hidden type=\"text\" id=\"event_id\" name=\"event_id\" value=\"" + event_id + "\">";
            out += "<input hidden type=\"text\" id=\"date_id\" name=\"date_id\" value=\"" + date_id + "\">";
            out += "<input type=\"radio\" id=\"free\" name=\"price\" value=\"0\"" + price_sel_0 + "><label for=\"free\">kostenlos</label></br>";
            out += "<input type=\"radio\" id=\"75eu\" name=\"price\" value=\"75\"" + price_sel_75 + "><label for=\"75eu\">75 €</label></br>";
            out += "<input type=\"radio\" id=\"150eu\" name=\"price\" value=\"150\"" + price_sel_150 + "><label for=\"150eu\">150 €</label></br>";
            out += "<input type=\"radio\" id=\"custom\" name=\"price\" value=\"custom\"" + price_sel_custom + ">"
            out += "<input type=\"text\" id=\"price_txt\" name=\"price_txt\" value=\"" + price_custom_txt + "\" style=\"width:80px;\"> €</br>";
            var dis = "";
            if (!isConfirmed(data, date_id)) {
                dis = " disabled";
            }
            out += "<input type=\"submit\" name=\"invoice\" value=\"speichern\"" + dis + " class=\"cls-input button\">";
            out += "</form>";
            out += "</td></tr>";
        }

        if ("public_note" in controlls) {
            out += ("<tr class=\"tr-new-sub-section\"><td>Info für ALLE</dt><td>");
            var pub_note_txt = "";
            if (("_public_note" + "_" + date_id) in data) {
                pub_note_txt = data["_public_note" + "_" + date_id];
            }
            out += add_text_area(event_id, date_id, "public_note", pub_note_txt);
            out += ("</td></tr>");
        }
        if ("private_note" in controlls) {
            out += ("<tr class=\"tr-new-sub-section\"><td>Admin-Notizen</dt><td>");
            var priv_note_txt = "";
            if (("_private_note" + "_" + date_id) in data) {
                priv_note_txt = data["_private_note" + "_" + date_id];
            }
            out += add_text_area(event_id, date_id, "private_note", priv_note_txt);
            out += ("</td></tr>");
        }

    } else {
        if ("public_note" in controlls) {
            if (("_public_note" + "_" + date_id) in data) {
                out += ("<tr class=\"tr-new-sub-section\"><td>Info für ALLE</dt><td><span style=\"white-space: pre-line\">" + data["_public_note" + "_" + date_id] + "</span></td></tr>");
            }
        }
    }

    // other fields
    if (is_admin) {
        out += ("<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>Sonstiges</strong></dt></tr>");
        out += display_fields(data, admin_field_name_patches);

    }
    // link other dates for this event
    out += ("<tr class=\"tr-new-section\"><td colspan=\"2\"><strong>alle Terminoptionen für dieses Event</strong></dt></tr>");
    var counter = 1;
    out += "<table style=\"width:100%; border-spacing: 0px 2px;\">";
    for (const event of calendar_json) {
        if (event.id == event_id) {
            var current = "";
            if (event.dateId == date_id) {
                current = "ausgewählt ↑";
            }
            out += "<tr style=\"cursor: pointer;\" class=\"table-info " + event.type + "\" onclick=\"updateEvent(" + event.id + ", " + event.dateId + ");cal.loadDate(new Date('" + event.startDate + "'));window.scrollTo(0,0)\">";
            out += ("<th>" + counter + "</th><th>" + current + "</th><th><strong>" + get_date_str(event.startDate) + "</strong> um <strong>" + events[event.id]["time" + event.dateId] + "</strong></th><th>" + event.desc + "</th>");
            out += "</tr>";
            counter++;
        }
    }
    out += "</table>";

    out += "</table></div>";
    return out;
}


function display_fields(data, field_definitions) {
    var out = "";
    for (var key in field_definitions) {
        var class_name = "tr-new-sub-sub-section";
        var display_key = key;
        if (key.startsWith("-")) {
            class_name = "tr-new-sub-section";
            display_key = display_key.substring(1);
        }
        out += ("<tr class=\"" + class_name + "\"><td>" + display_key + "</dt><td><span style=\"white-space: pre-line\">");
        for (const part of field_definitions[key]) {
            if (part.startsWith("_")) {
                var value = data[part.substring(1)];
                // exchange check(bool) fields with word
                if ((part.startsWith("_zustimmung")
                    || part.startsWith("_check"))
                    && !key.endsWith("_txt")) {
                    if (value == "1")
                        value = "ja";
                    else
                        value = "nein";
                }
                out += value;
            } else {
                out += part;
            }
        }
        out += "</span></td></tr>";
    }
    return out;
}

function add_form_button(event_id, date_id, action_name, button_text, enabled) {
    var dis = "";
    if (!enabled) {
        dis = " disabled";
    }
    out = "<form action=\"\" method=\"POST\" style=\"display: inline;\">";
    out += "<input hidden type=\"text\" id=\"event_id\" name=\"event_id\" value=\"" + event_id + "\">";
    out += "<input hidden type=\"text\" id=\"date_id\" name=\"date_id\" value=\"" + date_id + "\">";
    out += "<input type=\"submit\" name=\"" + action_name + "\" value=\"" + button_text + "\"" + dis + " class=\"cls-input button\">";
    out += "</form>";
    return out;
}

function add_text_area(event_id, date_id, action_name, text) {
    var out = "<form action=\"\" method=\"POST\">";
    out += "<input hidden type=\"text\" id=\"event_id\" name=\"event_id\" value=\"" + event_id + "\">";
    out += "<input hidden type=\"text\" id=\"date_id\" name=\"date_id\" value=\"" + date_id + "\">";
    out += "<textarea id=\"note\" name=\"note\" rows=\"4\" style=\"width:100%;\" class=\"cls-input\">";
    out += text;
    out += "</textarea></br>";
    out += "<input type=\"submit\" name=\"" + action_name + "\" value=\"speichern\" class=\"cls-input button\">";
    out += "</form>";
    return out;
}

function add_selection(event_id, date_id, action_name, options, selected, txt) {
    var out = "<form action=\"\" method=\"POST\">";
    out += "<input hidden type=\"text\" id=\"event_id\" name=\"event_id\" value=\"" + event_id + "\">";
    out += "<input hidden type=\"text\" id=\"date_id\" name=\"date_id\" value=\"" + date_id + "\">";
    out += "<input type=\"radio\" id=\"huey\" name=\"drone\" value=\"huey\">";
    out += "<input type=\"submit\" name=\"" + action_name + "\" value=\"speichern\" class=\"cls-input button\">";
    out += "</form>";
    return out;
}

function updateDay(day) {
    var out = "";
    for (const event of calendar_json) {
        if (datesAreOnSameDay(day, event.startDate)) {
            out += "<table style=\"cursor: pointer;\" class=\"table-info\" onclick=\"updateEvent(" + event.id + ", " + event.dateId + ")\">";
            out += ("<tr class=\"" + event.type + "\"><th><strong>" + get_date_str(day) + "</strong> um <strong>" + events[event.id]["time" + event.dateId] + "</strong></th><th>" + event.desc + "</th></tr>");
            out += "</table>";//</div><br>";
        }
    }
    document.getElementById("note").innerHTML = out;
}

function get_date_str(date) {
    var date_obj = new Date(date);
    return date_obj.getDate() + '.' + (date_obj.getMonth() + 1) + '.' + date_obj.getFullYear();
}
