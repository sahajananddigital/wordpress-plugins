<?php
/**
 * Abstract Data Class.
 */

defined('ABSPATH') || exit;

abstract class WP_Event_Manager_Data
{

    /**
     * ID of the object.
     *
     * @var int
     */
    protected $id = 0;

    /**
     * Data array.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Constructor.
     *
     * @param int|object|array $read ID to read from DB.
     */
    public function __construct($read = 0)
    {
        if ($read) {
            $this->set_id($read);
            $this->read($read);
        }
    }

    /**
     * Get ID.
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set ID.
     *
     * @param int $id ID.
     */
    public function set_id($id)
    {
        $this->id = absint($id);
    }

    /**
     * Read from DB.
     *
     * @param int $id ID.
     */
    public function read($id)
    {
        // To be implemented by concrete class using Data Store
    }

    /**
     * Save to DB.
     */
    public function save()
    {
        // To be implemented by concrete class using Data Store
    }
}
