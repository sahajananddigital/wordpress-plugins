# WordPress Event Manager Documentation

## Overview
**WordPress Event Manager** is a lightweight, React-powered plugin designed to streamline event attendee management, financial tracking, and check-ins within the WordPress admin dashboard. It bypasses standard WordPress posts for a custom table approach, ensuring speed and efficiency for events with thousands of attendees.

## Key Features
- **Fast Dashboard**: React-based UI for instant search and filtering.
- **Attendee Management**: Add, edit, check-in, and delete attendees.
- **Import/Export**: Bulk import via CSV (optimized for Razorpay reports) and export to CSV/PDF.
- **Financial Tracking**: Separate tracking for **Ticket Sales** (Attendees) vs **Donations/Support** (Non-attendees).
- **Expense Manager**: Log event expenses to calculate real-time **Net Profit/Loss**.
- **Real-time Stats**: Instant visualization of `Total Attendees`, `Cash vs Online`, `Check-ins`, and `Funds Raised`.

---

## Installation

1. **Prerequisites**: Ensure you have `Node.js` and `npm` installed.
2. **Clone & Install**:
   ```bash
   git clone <repo-url>
   cd wordpress-event-manager
   npm install
   ```
3. **Build**:
   ```bash
   npm run build
   ```
4. **Activate**:
   - copy the folder to your `wp-content/plugins/` directory.
   - Go to **WP Admin > Plugins** and activate **WordPress Event Manager**.

---

## User Guide

### 1. The Dashboard
The dashboard is the central hub. It displays key metrics at the top:
- **Total Attendees**: Count of people with valid passes (Quantity > 0).
- **Total Passes**: Sum of all passes (e.g., if one person bought 5 tickets).
- **Checked In**: Real-time count of guests who have arrived.
- **Financial Breakdown**:
    - `Cash Collected`: Payments marked as 'cash'.
    - `Online (Razorpay)`: Payments via gateway.
    - `QR Code`: Payments via QR scan.
    - `Support / Donations`: Contributions from people **not** attending (0 passes).
- **Net Amount**: Total Income (Tickets + Support) minus Total Expenses.

### 2. Managing Attendees
- **Add Attendee**: Click **"Add Attendee"**. Enter Name, Mobile (required), Amount, and Payment Mode.
- **Search**: Use the search bar to find attendees by **Name** or **Mobile**. The search is "debounced" (waits 500ms after typing) for performance.
- **Check-In**: Click the **"Check In"** button next to an attendee. The button disables after check-in to prevent duplicates.
- **Edit/Delete**: Use the action buttons (Edit Icon / Trash Icon) in the table row.

### 3. Support & Donations
Donors who are contributing money but **not attending** are handled separately to keep your attendee list clean.
- **Add Support**: Click **"Add Support"** (Heart Icon) on the top bar or next to an attendee (to convert/log them as a supporter).
    - *Note*: Support entries automatically have **Quantity = 0**.
- **View Supporters**: In the **Support / Donations** card at the top, click **"View Details"**. This opens a simplified list of all donors.

### 4. Expense Management
Track where your money is going.
- **View Expenses**: Click **"View Expenses"** to toggle the expense table.
- **Add Expense**: Click **"Add Expense"**.
    - Categories: `General`, `Food`, `Marketing`, `Venue`, `Logistics`.
- **Impact**: Expenses are automatically subtracted from your **Net Amount** calculation.

### 5. Import & Export
- **Import CSV**:
    - Click **"Import CSV"**.
    - Supported Format: Standard CSVs or Razorpay Payment Exports.
    - *Smart Logic*: The importer automatically maps fields like `Name`, `Phone`, `Email`, `Amount`. It detects duplicate Payment IDs and skips them.
- **Export Data**:
    - **Export CSV**: Downloads the full attendee database.
    - **Export PDF**: Generates a printable table for offline check-ins or reporting.

---

## Technical Details (For Developers)
- **Database**: Creates two custom tables:
    - `wp_event_attendees`: Stores attendee data (indexed by UUID).
    - `wp_event_expenses`: Stores expense logs.
- **API**: Custom REST API endpoints under `/event-manager/v1/`.
- **Frontend**: Built with **React** (`@wordpress/element`) and **WordPress Components**.
- **Security**: All API requests use `current_user_can('manage_options')` and standard nonce verification.
