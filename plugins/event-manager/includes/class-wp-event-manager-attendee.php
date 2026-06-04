<?php
/**
 * Attendee Model.
 */

defined('ABSPATH') || exit;

class WP_Event_Manager_Attendee extends WP_Event_Manager_Data
{

    /**
     * Data array with defaults.
     *
     * @var array
     */
    protected $data = [
        'uuid' => '',
        'name' => '',
        'mobile' => '',
        'email' => '',
        'status' => 'pending',
        'payment_mode' => 'cash',
        'amount' => 0.0,
        'razorpay_payment_id' => '',
        'check_in_status' => 0,
        'check_in_time' => null,
        'date_created' => null,
        'quantity' => 1,
    ];

    /**
     * Getters
     */
    public function get_uuid()
    {
        return $this->data['uuid'];
    }
    public function get_name()
    {
        return $this->data['name'];
    }
    public function get_mobile()
    {
        return $this->data['mobile'];
    }
    public function get_email()
    {
        return $this->data['email'];
    }
    public function get_status()
    {
        return $this->data['status'];
    }
    public function get_payment_mode()
    {
        return $this->data['payment_mode'];
    }
    public function get_amount()
    {
        return $this->data['amount'];
    }
    public function get_quantity()
    {
        return isset($this->data['quantity']) ? $this->data['quantity'] : 1;
    }
    public function get_check_in_status()
    {
        return $this->data['check_in_status'];
    }

    /**
     * Setters
     */
    public function set_uuid($value)
    {
        $this->data['uuid'] = $value;
    }
    public function set_name($value)
    {
        $this->data['name'] = $value;
    }
    public function set_mobile($value)
    {
        $this->data['mobile'] = $value;
    }
    public function set_email($value)
    {
        $this->data['email'] = $value;
    }
    public function set_status($value)
    {
        $this->data['status'] = $value;
    }
    public function set_payment_mode($value)
    {
        $this->data['payment_mode'] = $value;
    }
    public function set_amount($value)
    {
        $this->data['amount'] = $value;
    }
    public function set_quantity($value)
    {
        $this->data['quantity'] = intval($value);
    }
    public function set_razorpay_payment_id($value)
    {
        $this->data['razorpay_payment_id'] = $value;
    }

    // Add getter for razorpay_payment_id too
    public function get_razorpay_payment_id()
    {
        return $this->data['razorpay_payment_id'];
    }

    /**
     * Check In
     */
    public function check_in()
    {
        $this->data['check_in_status'] = 1;
        $this->data['check_in_time'] = current_time('mysql');
    }

    /**
     * Save data to DB.
     */
    public function save()
    {
        $store = new WP_Event_Manager_Attendee_Data_Store();
        $store->create($this);
    }

    /**
     * Read data from DB.
     */
    public function read($id)
    {
        $store = new WP_Event_Manager_Attendee_Data_Store();
        $store->read($this);
    }
    /**
     * Delete data from DB.
     */
    public function delete()
    {
        $store = new WP_Event_Manager_Attendee_Data_Store();
        $store->delete($this);
    }
}
