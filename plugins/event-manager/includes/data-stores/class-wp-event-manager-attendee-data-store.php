<?php
/**
 * Attendee Data Store.
 */

defined('ABSPATH') || exit;

class WP_Event_Manager_Attendee_Data_Store
{

    /**
     * Create (Invalidate this for now, need proper create logic).
     * 
     * @param WP_Event_Manager_Attendee $attendee Attendee object.
     */
    public function create(&$attendee)
    {
        global $wpdb;

        // Uniquness Check for Razorpay ID
        if ($attendee->get_razorpay_payment_id()) {
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}event_attendees WHERE razorpay_payment_id = %s",
                $attendee->get_razorpay_payment_id()
            ));

            if ($existing_id) {
                // If exists, maybe update? Or throws exception?
                // User said "Do not import duplicate".
                // We will throw exception so Controller can catch and skip.
                throw new Exception('Duplicate Razorpay Payment ID');
            }
        }

        if (!$attendee->get_uuid()) {
            $attendee->set_uuid(wp_generate_uuid4());
        }

        $data = [
            'uuid' => $attendee->get_uuid(),
            'name' => $attendee->get_name(),
            'mobile' => $attendee->get_mobile(),
            'email' => $attendee->get_email(),
            'status' => $attendee->get_status(),
            'payment_mode' => $attendee->get_payment_mode(),
            'amount' => $attendee->get_amount(),
            'quantity' => $attendee->get_quantity(),
            'razorpay_payment_id' => $attendee->get_razorpay_payment_id(),
            'date_created' => current_time('mysql'),
        ];

        $result = $wpdb->insert($wpdb->prefix . 'event_attendees', $data);

        if ($result) {
            $attendee->set_id($wpdb->insert_id);
        }
    }

    /**
     * Read.
     */
    public function read(&$attendee)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'event_attendees';
        $data = null;

        if ($attendee->get_id()) {
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $attendee->get_id()));
        } elseif ($attendee->get_uuid()) {
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE uuid = %s", $attendee->get_uuid()));
        }

        if (isset($data) && $data) {
            $attendee->set_id($data->id);
            $attendee->set_uuid($data->uuid);
            $attendee->set_name($data->name);
            $attendee->set_mobile($data->mobile);
            $attendee->set_email($data->email);
            $attendee->data['status'] = $data->status;
            $attendee->data['payment_mode'] = $data->payment_mode;
            $attendee->data['amount'] = $data->amount;
            $attendee->data['quantity'] = $data->quantity;
            $attendee->data['razorpay_payment_id'] = $data->razorpay_payment_id;
            $attendee->data['check_in_status'] = $data->check_in_status;
            $attendee->data['check_in_time'] = $data->check_in_time;
            $attendee->data['date_created'] = $data->date_created;
        }
    }
    /**
     * Update.
     */
    public function update(&$attendee)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_attendees';

        $data = [
            'name' => $attendee->get_name(),
            'mobile' => $attendee->get_mobile(),
            'email' => $attendee->get_email(),
            'status' => $attendee->get_status(),
            'payment_mode' => $attendee->get_payment_mode(),
            'amount' => $attendee->get_amount(),
            'quantity' => $attendee->get_quantity(),
            'razorpay_payment_id' => $attendee->get_razorpay_payment_id(),
            'check_in_status' => $attendee->get_check_in_status(),
        ];

        $wpdb->update($table, $data, ['uuid' => $attendee->get_uuid()]);
    }

    /**
     * Delete.
     */
    public function delete(&$attendee)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_attendees';
        $wpdb->delete($table, ['uuid' => $attendee->get_uuid()]);
    }

    /**
     * Delete All.
     */
    public function delete_all()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_attendees';
        $wpdb->query("TRUNCATE TABLE $table");
    }
}
