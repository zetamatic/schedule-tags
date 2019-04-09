<?php
/*
Plugin Name: Schedule Tags
Plugin URI: https://zetamatic.com
Description: This plugin allows you to schedule tags automatically according to the date defined for the tag.
Version: 0.1
Author: zetamatic
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

define('WPTS_FILE', __FILE__);
define('WPTS_PATH', plugin_dir_path(__FILE__));
define('WPTS_BASE', plugin_basename(__FILE__));


/**
 * Plugin Localization
 */
add_action('plugins_loaded', 'tags_scheduler_text_domain');

function tags_scheduler_text_domain() {
	load_plugin_textdomain('tags_scheduler', false, basename( dirname( __FILE__ ) ) . '/lang' );
}

require_once dirname( __FILE__ ) . '/inc/wp-tags-schedule.php';

new Schedule_Tags();