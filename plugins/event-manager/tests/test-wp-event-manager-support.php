<?php
/**
 * Test Support and Status functionality.
 */
class Test_WP_Event_Manager_Support extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Clean up table
        global $wpdb;
        $table = $wpdb->prefix . 'event_attendees';
        $wpdb->query("TRUNCATE TABLE $table");
    }

    public function test_create_attendee_default_status()
    {
        $controller = new WP_Event_Manager_REST_Controller();
        $request = new WP_REST_Request('POST', '/event-manager/v1/register');
        $request->set_body_params([
            'name' => 'John Doe',
            'mobile' => '1234567890',
            'email' => 'john@example.com',
            'amount' => 100,
            'quantity' => 1,
            'payment_mode' => 'cash'
        ]);

        // Should default to pending or active depending on controller logic? 
        // We set default in model to 'pending', but frontend sends 'active'.
        // Backend default is 'pending'. 

        $response = $controller->create_attendee($request);
        $data = $response->get_data();

        $this->assertTrue($data['success']);

        $attendee_id = $data['uuid'];
        $attendee = new WP_Event_Manager_Attendee();
        $attendee->set_uuid($attendee_id);
        $attendee->load();

        // If frontend sends nothing (like here), model default is 'pending'
        $this->assertEquals('pending', $attendee->get_status());
    }

    public function test_create_supporter_quantity_zero()
    {
        $controller = new WP_Event_Manager_REST_Controller();
        $request = new WP_REST_Request('POST', '/event-manager/v1/register');
        $request->set_body_params([
            'name' => 'Generous Donor',
            'mobile' => '9876543210',
            'email' => 'donor@example.com',
            'amount' => 500,
            'quantity' => 0, // Support
            'payment_mode' => 'online',
            'status' => 'active'
        ]);

        $response = $controller->create_attendee($request);
        $data = $response->get_data();
        $this->assertTrue($data['success']);

        $attendee = new WP_Event_Manager_Attendee();
        $attendee->set_uuid($data['uuid']);
        $attendee->load();

        $this->assertEquals(0, $attendee->get_quantity());
        $this->assertEquals('active', $attendee->get_status());
    }

    public function test_stats_counts()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_attendees';

        // Add 1 Attendee (quantity 1)
        $wpdb->insert($table, [
            'uuid' => wp_generate_uuid4(),
            'name' => 'Attendee 1',
            'quantity' => 1,
            'status' => 'active',
            'payment_mode' => 'cash',
            'amount' => 100
        ]);

        // Add 1 Supporter (quantity 0)
        $wpdb->insert($table, [
            'uuid' => wp_generate_uuid4(),
            'name' => 'Supporter 1',
            'quantity' => 0,
            'status' => 'active',
            'payment_mode' => 'online',
            'amount' => 50
        ]);

        $controller = new WP_Event_Manager_REST_Controller();
        $request = new WP_REST_Request('GET', '/event-manager/v1/stats');
        $response = $controller->get_stats($request);
        $stats = $response->get_data();

        // Total should be 1 (Attendees)
        $this->assertEquals(1, $stats['total']);
        // Total Supporters should be 1
        $this->assertEquals(1, $stats['total_supporters']);
        // Total Funds
        $this->assertEquals(100, $stats['cash_collected']); // Attendee
        $this->assertEquals(50, $stats['online_collected']); // Supporter
    }

    public function test_update_status()
    {
        $controller = new WP_Event_Manager_REST_Controller();
        $request = new WP_REST_Request('POST', '/event-manager/v1/register');
        $request->set_body_params([
            'name' => 'Status Changer',
            'mobile' => '1112223333',
            'status' => 'pending'
        ]);
        $response = $controller->create_attendee($request);
        $uuid = $response->get_data()['uuid'];

        // Update to active
        $update_request = new WP_REST_Request('POST', '/event-manager/v1/register');
        $update_request->set_body_params([
            'uuid' => $uuid,
            'name' => 'Status Changer',
            'mobile' => '1112223333',
            'status' => 'active'
        ]);
        $controller->create_attendee($update_request);

        $attendee = new WP_Event_Manager_Attendee();
        $attendee->set_uuid($uuid);
        $attendee->load();

        $this->assertEquals('active', $attendee->get_status());
    }
}
