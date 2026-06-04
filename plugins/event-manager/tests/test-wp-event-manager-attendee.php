<?php
/**
 * Class Test_WP_Event_Manager_Attendee
 *
 * @package Wp_Event_Manager
 */

class Test_WP_Event_Manager_Attendee extends WP_UnitTestCase
{

    public function test_create_attendee()
    {
        $attendee = new WP_Event_Manager_Attendee();
        $attendee->set_name('Test User');
        $attendee->set_mobile('1234567890');
        $attendee->set_email('test@example.com');
        $attendee->save();

        $this->assertNotEmpty($attendee->get_id());
        $this->assertNotEmpty($attendee->get_uuid());

        // Fetch from DB to verify
        $fetched = new WP_Event_Manager_Attendee($attendee->get_id());
        $this->assertEquals('Test User', $fetched->get_name());
    }

    public function test_check_in()
    {
        $attendee = new WP_Event_Manager_Attendee();
        $attendee->set_name('Checkin User');
        $attendee->set_mobile('0000000000');
        $attendee->save();

        $this->assertEquals(0, $attendee->get_check_in_status());

        $attendee->check_in();
        // Save is required to persist check-in? 
        // In my implementation of check_in() in the model, I only updated the data array.
        // The REST API controller did a direct SQL update.
        // Let's verify the logic in the Model.

        $this->assertEquals(1, $attendee->get_check_in_status());
    }
}
