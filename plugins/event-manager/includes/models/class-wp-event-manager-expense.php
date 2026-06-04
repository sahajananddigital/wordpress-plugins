<?php
/**
 * Expense Model.
 */

defined('ABSPATH') || exit;

class WP_Event_Manager_Expense extends WP_Event_Manager_Data
{
    /**
     * Data array with defaults.
     *
     * @var array
     */
    protected $data = [
        'id' => 0,
        'title' => '',
        'amount' => 0.0,
        'date' => null,
        'category' => 'general',
    ];

    /**
     * Getters
     */
    public function get_title()
    {
        return $this->data['title'];
    }
    public function get_amount()
    {
        return $this->data['amount'];
    }
    public function get_date()
    {
        return $this->data['date'];
    }
    public function get_category()
    {
        return $this->data['category'];
    }

    /**
     * Setters
     */
    public function set_title($value)
    {
        $this->data['title'] = $value;
    }
    public function set_amount($value)
    {
        $this->data['amount'] = floatval($value);
    }
    public function set_date($value)
    {
        $this->data['date'] = $value;
    }
    public function set_category($value)
    {
        $this->data['category'] = $value;
    }

    /**
     * Save data to DB.
     */
    public function save()
    {
        $store = new WP_Event_Manager_Expense_Data_Store();
        $store->create($this);
    }

    /**
     * Delete data from DB.
     */
    public function delete()
    {
        $store = new WP_Event_Manager_Expense_Data_Store();
        $store->delete($this);
    }
}
