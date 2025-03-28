<?php

const VERSION = "1.01";

/*
Plugin Name: CF7 workshop scheduler
Plugin URI: https://github.com/juri117/wp-workshop-schedule
Description: Erweiterung für contact-form-7 und advanced-cf7-db zum Planen von workshops, zeigt einen Kalender für Angemeldete user an
Requires Plugins: contact-form-7, advanced-cf7-db
Version: 1.01
Author: Juri Bieler
Author URI: https://github.com/juri117
*/

# used for forcing scripts and css update


defined('ABSPATH') || exit;
require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/admin_list_page.php');
require_once(dirname(__FILE__) . '/admin_calendar_page.php');
require_once(dirname(__FILE__) . '/admin_config_page.php');
require_once(dirname(__FILE__) . '/cf7.php');



###############################################################
# custom script for contact-from7

function my_plugin_wpcf7_properties($properties, $instance /* unused */)
{
	$properties['additional_settings'] .= "document.body.querySelectorAll('details').forEach((e) => e.open=true);";
	return $properties;
}

add_filter('wpcf7_contact_form_properties', 'my_plugin_wpcf7_properties', 10, 2);

###############################################################
# custom user field

add_action('show_user_profile', 'workshop_shedule_user_profile_fields');
add_action('edit_user_profile', 'workshop_shedule_user_profile_fields');

function workshop_shedule_user_profile_fields($user)
{
	if (current_user_can(ADMIN_ROLE)) {
		echo ("<h3>Team-Zugehörigkeit</h3>");

		foreach (get_config() as $key => $value) {
			$checked = "";
			$team = explode(",", get_the_author_meta('workshop-team', $user->ID));
			if (in_array($key, $team)) {
				$checked = " checked";
			}
			echo "<input type='checkbox' name='team-" . $key . "'" . $checked . ">";
			echo "<label for='" . $key . "'>" . $value["title"] . "</label><br>";
		}
	}
}

add_action('personal_options_update', 'workshop_shedule_save_user_profile_fields');
add_action('edit_user_profile_update', 'workshop_shedule_save_user_profile_fields');

function workshop_shedule_save_user_profile_fields($user_id)
{
	if (empty($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
		return;
	}

	if (!current_user_can('edit_user', $user_id)) {
		return false;
	}
	if (!current_user_can(ADMIN_ROLE)) {
		return false;
	}
	$team = [];
	foreach (get_config() as $key => $value) {
		if (isset($_POST["team-" . $key])) {
			$team[] = $key;
		}
	}
	update_user_meta($user_id, 'workshop-team', implode(",", $team));
}


###############################################################
# custom side menu entry for admin page

function current_user_is_in_team($team_key)
{
	$team = explode(",", get_the_author_meta('workshop-team', get_current_user_id()));
	return in_array($team_key, $team);
}

function workshop_schedule_admin_menu()
{
	$i = 10;
	if (is_array(get_config()) || is_object(get_config())) {
		foreach (get_config() as $key => $value) {
			if (current_user_is_in_team($key) && current_user_can($value["calendar_cap"])) {
				$page = add_menu_page(
					__($value["title"], $key . '-page'),
					__($value["title"], $key . '-page'),
					'read',
					$key . '-page',
					'my_admin_page_main',
					'dashicons-calendar-alt',
					$i
				);
				$i++;
				add_action('admin_print_scripts-' . $page, 'my_plugin_admin_scripts');
				add_action('admin_print_styles-' . $page, 'my_plugin_admin_styles');
			}
			if (current_user_is_in_team($key) && current_user_can($value["list_cap"])) {
				add_menu_page(
					__($value["title"] . " Liste", $key . '-list-page'),
					__($value["title"] . " Liste", $key . '-list-page'),
					#'manage_options',
					'read',
					$key . '-list-page',
					'my_admin_list_page_main',
					'dashicons-list-view',
					$i
				);
				$i++;
			}
		}
	}
}

add_action('admin_menu', 'workshop_schedule_admin_menu');



###############################################################
# custom styles and scripts for admin page 

# function load_my_plugin_scripts($hook)
function my_plugin_admin_scripts()
{
	// Load style & scripts.
	wp_enqueue_style('my-plugin', plugins_url('cf7-workshop-scheduler/plugin.css'), array(), VERSION);
	wp_enqueue_style('my-calendar', plugins_url('cf7-workshop-scheduler/calendar/calendar.css'), array(), VERSION);
	wp_enqueue_style('my-calendar-colors', plugins_url('cf7-workshop-scheduler/calendar/calendar_colors.css'), array(), VERSION);
	wp_enqueue_script('my-calendar', plugins_url('cf7-workshop-scheduler/calendar/calendar.js'), array(), VERSION);
	//wp_enqueue_script('my-workshop-skripts', plugins_url('cf7-workshop-scheduler/plugin.js'), array(), VERSION);
}

function my_plugin_admin_styles()
{
	// Load style & scripts.
	wp_enqueue_style('my-plugin', plugins_url('cf7-workshop-scheduler/plugin.css'), array(), VERSION);
	wp_enqueue_style('my-calendar', plugins_url('cf7-workshop-scheduler/calendar/calendar.css'), array(), VERSION);
	wp_enqueue_style('my-calendar-colors', plugins_url('cf7-workshop-scheduler/calendar/calendar_colors.css'), array(), VERSION);
}

# add_action('admin_enqueue_scripts', 'load_my_plugin_scripts');


###############################################################
# schedule page functions

function my_admin_page_main()
{
	foreach (get_config() as $key => $value) {
		if ($value["title"] == get_admin_page_title()) {
			if (current_user_is_in_team($key)) {
				my_admin_page($key);
			}
			return;
		}
	}
}

function has_admin_priv()
{
	if (current_user_can(ADMIN_ROLE)) {
		return 1;
	}
	return 0;
}



###############################################################
# list page functions

function my_admin_list_page_main()
{
	foreach (get_config() as $key => $value) {
		if ($value["title"] . " Liste" == get_admin_page_title()) {
			bootload_drafts_table($key);
			return;
		}
	}
}
