<?php
/**
 * REST API Controller.
 */

defined('ABSPATH') || exit;

class WP_Event_Manager_REST_Controller extends WP_REST_Controller
{

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'event-manager/v1';

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/register', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_attendee'],
            'permission_callback' => '__return_true',
            'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
        ]);

        register_rest_route($this->namespace, '/attendees', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        register_rest_route($this->namespace, '/checkin', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'check_in'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        register_rest_route($this->namespace, '/attendees/(?P<uuid>[a-zA-Z0-9-]+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_attendee'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        register_rest_route($this->namespace, '/expenses', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_expenses'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_expense'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/expenses/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_expense'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);
    }

    /**
     * Permission check.
     */
    public function get_items_permissions_check($request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Create Attendee (Register or Update).
     */
    public function create_attendee($request)
    {
        $data = $request->get_json_params();

        // Validation could be moved to validate_callback in args
        if (empty($data['name']) || empty($data['mobile'])) {
            return new WP_Error('missing_fields', 'Name and Mobile are required.', ['status' => 400]);
        }

        try {
            $attendee = new WP_Event_Manager_Attendee();

            // If UUID is present, try to load existing attendee for update
            if (!empty($data['uuid'])) {
                $attendee->set_uuid(sanitize_text_field($data['uuid']));
                // We should ideally check if it exists, but for now we assume client sends valid UUID
            }

            $attendee->set_name(sanitize_text_field($data['name']));
            $attendee->set_mobile(sanitize_text_field($data['mobile']));
            $attendee->set_email(sanitize_email($data['email'] ?? '')); // Added Email

            if (!empty($data['payment_mode'])) {
                $attendee->set_payment_mode(sanitize_text_field($data['payment_mode']));
            }
            if (!empty($data['amount'])) {
                $attendee->set_amount(floatval($data['amount']));
            }
            if (!empty($data['razorpay_payment_id'])) {
                $attendee->set_razorpay_payment_id(sanitize_text_field($data['razorpay_payment_id']));
            }
            if (isset($data['quantity'])) {
                $attendee->set_quantity(intval($data['quantity']));
            }

            if (!empty($data['status'])) {
                $attendee->set_status(sanitize_text_field($data['status']));
            }

            if (!empty($data['uuid'])) {
                // Start a new Data Store instance to check/update
                $store = new WP_Event_Manager_Attendee_Data_Store();
                $store->update($attendee);
            } else {
                $attendee->save();
            }

            return rest_ensure_response([
                'success' => true,
                'uuid' => $attendee->get_uuid(),
                'message' => !empty($data['uuid']) ? 'Attendee updated successfully.' : 'Attendee registered successfully.',
            ]);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Duplicate Razorpay Payment ID') {
                return rest_ensure_response([
                    'success' => false,
                    'code' => 'duplicate_payment_id',
                    'message' => 'Attendee with this Payment ID already exists.',
                ]);
            }
            return new WP_Error('create_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get Attendees.
     */
    public function get_items($request)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_attendees';
        $search = $request->get_param('q');

        // ideally use Data Store for searching
        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE name LIKE %s OR mobile LIKE %s OR email LIKE %s ORDER BY date_created DESC LIMIT 1000", $like, $like, $like));
        } else {
            $results = $wpdb->get_results("SELECT * FROM $table ORDER BY date_created DESC LIMIT 1000");
        }

        return rest_ensure_response($results);
    }

    /**
     * Get Stats.
     */
    public function get_stats($request)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_attendees';

        $stats = [];
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE quantity > 0"); // Ticket holders only
        $stats['total_supporters'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE quantity = 0"); // Supporters only
        $stats['checked_in'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE check_in_status = 1");
        $stats['total_passes'] = (int) ($wpdb->get_var("SELECT SUM(quantity) FROM $table WHERE status = 'active'") ?: 0);

        // Ticket Sales (Quantity > 0)
        $stats['cash_collected'] = (float) ($wpdb->get_var("SELECT SUM(amount) FROM $table WHERE payment_mode = 'cash' AND status = 'active' AND quantity > 0") ?: 0);
        $stats['online_collected'] = (float) ($wpdb->get_var("SELECT SUM(amount) FROM $table WHERE payment_mode = 'razorpay' AND status = 'active' AND quantity > 0") ?: 0);
        $stats['qr_collected'] = (float) ($wpdb->get_var("SELECT SUM(amount) FROM $table WHERE payment_mode = 'qrcode' AND status = 'active' AND quantity > 0") ?: 0);

        // Support/Donations (Quantity = 0)
        $stats['support_collected'] = (float) ($wpdb->get_var("SELECT SUM(amount) FROM $table WHERE status = 'active' AND quantity = 0") ?: 0);

        $table_expenses = $wpdb->prefix . 'event_expenses';
        $stats['total_expenses'] = (float) ($wpdb->get_var("SELECT SUM(amount) FROM $table_expenses") ?: 0);

        return rest_ensure_response($stats);
    }

    /**
     * Check In.
     */
    public function check_in($request)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_attendees';
        $uuid = $request->get_param('uuid');

        if (!$uuid) {
            return new WP_Error('missing_uuid', 'UUID required', ['status' => 400]);
        }

        // Use Data Store/Model ideally
        $wpdb->update(
            $table,
            ['check_in_status' => 1, 'check_in_time' => current_time('mysql')],
            ['uuid' => $uuid]
        );

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Delete Attendee.
     */
    public function delete_attendee($request)
    {
        $uuid = $request->get_param('uuid');

        if (!$uuid) {
            return new WP_Error('missing_uuid', 'UUID required', ['status' => 400]);
        }

        try {
            $attendee = new WP_Event_Manager_Attendee();
            $attendee->set_uuid($uuid);
            $attendee->delete();

            return rest_ensure_response(['success' => true, 'message' => 'Attendee deleted successfully.']);
        } catch (Exception $e) {
            return new WP_Error('delete_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Delete Items (Bulk).
     */
    public function delete_items($request)
    {
        if ($request->get_param('all') === 'true') {
            try {
                $store = new WP_Event_Manager_Attendee_Data_Store();
                $store->delete_all();
                return rest_ensure_response(['success' => true, 'message' => 'All attendees deleted successfully.']);
            } catch (Exception $e) {
                return new WP_Error('delete_error', $e->getMessage(), ['status' => 500]);
            }
        }

        return new WP_Error('invalid_request', 'To delete all items, pass ?all=true', ['status' => 400]);
    }

    /**
     * Get Expenses.
     */
    public function get_expenses($request)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'event_expenses';
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY date DESC");
        return rest_ensure_response($results);
    }

    /**
     * Create Expense.
     */
    public function create_expense($request)
    {
        $data = $request->get_json_params();

        if (empty($data['title']) || empty($data['amount'])) {
            return new WP_Error('missing_fields', 'Title and Amount are required.', ['status' => 400]);
        }

        try {
            $expense = new WP_Event_Manager_Expense();
            $expense->set_title(sanitize_text_field($data['title']));
            $expense->set_amount(floatval($data['amount']));
            $expense->set_category(sanitize_text_field($data['category'] ?? 'general'));

            // Allow manual date, otherwise defaults to now in Data Store
            if (!empty($data['date'])) {
                $expense->set_date(sanitize_text_field($data['date']));
            }

            $expense->save();

            return rest_ensure_response([
                'success' => true,
                'id' => $expense->get_id(),
                'message' => 'Expense added successfully.',
            ]);
        } catch (Exception $e) {
            return new WP_Error('create_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Delete Expense.
     */
    public function delete_expense($request)
    {
        $id = $request->get_param('id');

        try {
            $expense = new WP_Event_Manager_Expense();
            $expense->set_id($id);
            $expense->delete();

            return rest_ensure_response(['success' => true, 'message' => 'Expense deleted successfully.']);
        } catch (Exception $e) {
            return new WP_Error('delete_error', $e->getMessage(), ['status' => 500]);
        }
    }
}
