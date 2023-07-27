<?php
/*
Plugin Name: RT cf7 tracker
Description: Відслідковує кількість запитів з контакт-форми та надсилає щоденні звіти по електронній пошті.
Version: 1.0
Author: Ruslan Toloshnyi 
*/

defined('ABSPATH') || exit;

// Create new table in db
function rt_cf7_tracker_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'rt_cf7_tracker_tbl';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,            
            date datetime NOT NULL,
            counter int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'rt_cf7_tracker_table');


function rt_cf7_tracker_increment_counter()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'rt_cf7_tracker_tbl';

    // increase counter by 1
    if ($wpdb->get_var("SELECT COUNT(*) FROM $table_name") !== 0) {
        $wpdb->query("UPDATE $table_name SET counter = counter + 1, date = NOW()");
    }

    // if the table is empty, add a new row with the counter value
    if ($wpdb->get_var("SELECT COUNT(*) FROM $table_name") == 0) {
        $wpdb->insert(
            $table_name,
            array(
                'counter' => 1,
                'date' => current_time('mysql', 1),
            )
        );
    }
}
add_action('wpcf7_mail_sent', 'rt_cf7_tracker_increment_counter');


function rt_cf7_send_data_and_reset_counter()
{
    // get data to send to email
    global $wpdb;
    $table_name = $wpdb->prefix . 'rt_cf7_tracker_tbl';
    $count = $wpdb->get_var("SELECT counter FROM $table_name");

    // send email
    if ($count) {
        $to = 'ruslantoloshnyi@gmail.com, toloshnyi@gmail.com';
        $subject = get_bloginfo('name');
        $message = 'Кількість запитів з Contact form: ' . $count;
        wp_mail($to, $subject, $message);

        // reset counter
        $wpdb->query("UPDATE $table_name SET counter = 0");
    }
}
add_action('rt_cf7_daily_mail_cron', 'rt_cf7_send_data_and_reset_counter');

// register cron when plugin is activated
function rt_cf7_activate_cron()
{
    if (!wp_next_scheduled('rt_cf7_daily_mail_cron')) {
        wp_schedule_event(strtotime('23:59:00'), 'daily', 'rt_cf7_daily_mail_cron');
    }
}
register_activation_hook(__FILE__, 'rt_cf7_activate_cron');

// remove cron when plugin is deactivated
function rt_cf7_deactivate_cron()
{
    wp_clear_scheduled_hook('rt_cf7_daily_mail_cron');
}
register_deactivation_hook(__FILE__, 'rt_cf7_deactivate_cron');
