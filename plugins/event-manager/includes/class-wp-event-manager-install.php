<?php
/**
 * Installation related functions and actions.
 */

defined('ABSPATH') || exit;

class WP_Event_Manager_Install
{

    /**
     * Install WP Event Manager.
     */
    public static function install()
    {
        self::create_tables();
    }

    public static function update_db_check()
    {
        if (get_option('wp_event_manager_version') !== '1.1.0') {
            self::install();
            update_option('wp_event_manager_version', '1.1.0');
        }
    }

    /**
     * Create tables.
     */
    private static function create_tables()
    {
        global $wpdb;

        $wpdb->hide_errors();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'event_attendees';

        $sql = "
CREATE TABLE $table_name (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    uuid varchar(64) NOT NULL,
    name text NOT NULL,
    mobile varchar(20) NOT NULL,
    email varchar(100),
    status varchar(20) DEFAULT 'pending',
    payment_mode varchar(20),
    amount float DEFAULT 0,
    quantity int DEFAULT 1,
    razorpay_payment_id varchar(100),
    check_in_status tinyint(1) DEFAULT 0,
    check_in_time datetime,
    date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    PRIMARY KEY  (id),
    UNIQUE KEY uuid (uuid),
    KEY mobile (mobile)
) $collate;
        ";

        $table_expenses = $wpdb->prefix . 'event_expenses';
        $sql_expenses = "
CREATE TABLE $table_expenses (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    title text NOT NULL,
    amount float NOT NULL,
    date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    category varchar(50) DEFAULT 'general',
    PRIMARY KEY  (id)
) $collate;
        ";

        dbDelta($sql);
        dbDelta($sql_expenses);
    }
}
