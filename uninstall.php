<?php

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;
$table_name = $wpdb->prefix . 'rt_cf7_tracker_tbl';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
