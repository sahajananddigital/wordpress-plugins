<?php
/**
 * Expense Data Store.
 */

defined('ABSPATH') || exit;

class WP_Event_Manager_Expense_Data_Store
{
    /**
     * Create.
     */
    public function create(&$expense)
    {
        global $wpdb;

        $data = [
            'title' => $expense->get_title(),
            'amount' => $expense->get_amount(),
            'date' => $expense->get_date() ?: current_time('mysql'),
            'category' => $expense->get_category(),
        ];

        // Ensure table name is correct
        $table = $wpdb->prefix . 'event_expenses';
        $result = $wpdb->insert($table, $data);

        if ($result) {
            $expense->set_id($wpdb->insert_id);
        }
    }

    /**
     * Delete.
     */
    public function delete(&$expense)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_expenses';
        $wpdb->delete($table, ['id' => $expense->get_id()]);
    }
}
