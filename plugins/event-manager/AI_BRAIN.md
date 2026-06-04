# AI Brain: WordPress Event Manager

## 🧠 Project Context
**WordPress Event Manager** is a high-performance plugin designed to handle event attendees significantly faster than standard WordPress Custom Post Types (CPT) by using custom database tables and a React-based dashboard.

**Core Philosophy**:
1.  **Speed**: No CPTs. Direct SQL queries via `$wpdb`.
2.  **UX**: Single-page React Application (SPA) dashboard.
3.  **Separation**: Strict separation between API (Backend) and UI (Frontend).

## 📂 File Structure
```text
wordpress-event-manager/
├── wordpress-event-manager.php       # Main plugin entry point
├── README.md                         # General entry point
├── DOCUMENTATION.md                  # User & Developer Guide
├── package.json                      # NPM dependencies (React, Build scripts)
├── includes/                         # PHP Backend Logic
│   ├── class-wp-event-manager.php    # Singleton Main Class
│   ├── class-wp-event-manager-install.php # DB Schema & Installer
│   ├── api/
│   │   └── class-wp-event-manager-rest-controller.php # REST API Endpoints
│   ├── models/
│   │   └── class-wp-event-manager-expense.php # Expense Model
│   ├── data-stores/
│   │   ├── class-wp-event-manager-attendee-data-store.php # Attendee SQL Logic
│   │   └── class-wp-event-manager-expense-data-store.php # Expense SQL Logic
│   └── class-wp-event-manager-attendee.php # Attendee Model
├── src/                              # React Frontend
│   ├── index.js                      # React Entry point
│   ├── index.css                     # Styles
│   └── components/
│       ├── Dashboard.js              # Main SPA Logic (Tables, Modals, State)
│       └── Settings.js               # Settings Page (Clear Data)
└── tests/                            # PHPUnit Tests
    ├── test-wp-event-manager-attendee.php
    └── test-wp-event-manager-support.php
```

## 🏗 Architecture

### 1. Database Schema
Custom tables created on activation (`includes/class-wp-event-manager-install.php`):
-   `wp_event_attendees`: Stores attendees and supporters.
    -   `uuid` (Unique ID)
    -   `quantity`: `>0` for Attendees, `0` for Support/Donations.
    -   `status`: 'active', 'pending', 'cancelled'.
    -   `check_in_status`: boolean.
-   `wp_event_expenses`: Stores operational costs.

### 2. The REST API
Located in `includes/api/class-wp-event-manager-rest-controller.php`.
-   **Namespace**: `event-manager/v1`
-   **Endpoints**:
    -   `GET /attendees`: Lists attendees (supports search).
    -   `POST /register`: Creates/Updates attendees.
    -   `GET /stats`: Aggregated financial and count data.
    -   `POST /checkin`: Toggles check-in status.
    -   `GET/POST /expenses`: Manage expense records.

### 3. The Frontend
Built with `@wordpress/element` (React abstraction) and `@wordpress/components`.
-   **Dashboard.js**: Contains 90% of the UI logic.
    -   Manages local state (`attendees`, `stats`, `expenses`).
    -   Handles debounced searching.
    -   Implements "Add Support" vs "Add Attendee" modes using the same Modal component.

## 🔄 Key Workflows
1.  **Registration**: Frontend sends JSON to `POST /register`. Backend validates -> saves to `wp_event_attendees`.
2.  **Support**: A registration with `quantity: 0` is treated as a Donation/Support entry. It is excluded from the main attendee list but counted in stats.
3.  **Import**: Frontend parses CSV -> Iterates rows -> Calls `POST /register` for each. Handles Razorpay export formats.

## ⚠️ Common Pitfalls / Notes
-   **Version Check**: `WP_Event_Manager_Install::update_db_check()` must be updated when schema changes.
-   **Build Process**: Requires `npm run build` to compile `src/` into `build/`.
