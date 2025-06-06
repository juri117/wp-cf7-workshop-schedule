<?php

const ADMIN_ROLE = "administrator";
const SUBSCRIBER_ROLE = "read";

# title - the title shown in menu
# form_id - the form id of the contact-form7
# calendar_cap - the capability to view the calendar (default: read)
# list_cap - the capability to view the list (default: administrator)
# controls - controls that the admin can do
# field_name_patches - (non-admin) fields and their display name
# admin_field_name_patches - (admin) fields and their display name  
# admin_list_columns - (admin) columns in the list table

global $my_config;
$my_config = (object)[];

if (is_admin()) {
    $config_str = get_option('cf7_workshop_scheduler_config');
    global $my_config;
    $my_config = json_decode($config_str, true);
}


function get_config()
{
    global $my_config;
    return $my_config;
}

function get_config_value($form_key, $value_key, $default = null)
{
    global $my_config;
    if (is_array($value_key)) {
        $current = $my_config[$form_key] ?? null;
        foreach ($value_key as $key) {
            if(!is_array($current)) {
                return $default;
            }
            if (!isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }
        return $current;
    }
    return $my_config[$form_key][$value_key] ?? $default;
}

