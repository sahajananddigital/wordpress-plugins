# AI Context Brain

**Project**: WordPress Simple Customise Helper
**Type**: Modular WordPress Plugin (PHP/React)
**Purpose**: General WordPress customizations (Non-WooCommerce specific).

## Architecture
- **Modular Backend**: Feature-based modules in `includes/modules/`.
- **Modular Frontend**: React modules in `src/modules/`.
- **Hybrid**: PHP backend + React Admin UI.

## Key Structures
- `includes/class-wsch-core.php`: Plugin loader & Backend Module Registry.
- `includes/admin/class-wsch-settings-page.php`: Admin Menu & React Root.
- `includes/api/class-wsch-rest-controller.php`: Settings REST API.
- `src/components/SettingsApp.js`: React App Entry (Single View Navigation).
- `src/components/Dashboard.js`: Main Dashboard (Modal-based "Add" flow).
- `src/modules/`: Directory for React module definitions.

## UI/UX Patterns (Standard for new plugins)
- **Dashboard Strategy**: Use a "Marketplace" style Dashboard.
    - **Active State**: Only show enabled/active modules as cards on the main Dashboard.
    - **Discovery**: Use an "Add New Customization" button to open a **Modal** listing available (inactive) modules.
- **Navigation Flow**:
    - **Single View**: Avoid persistent tab bars for settings.
    - **Drill-down**: Clicking "Edit" on a module card replaces the Dashboard view with the Module Settings view.
    - **Return Path**: Always provide a generic **"← Back"** button in the header when inside a module view to return to the Dashboard.
- **Module Actions**:
    - **Activate**: Via the "Add" Modal.
    - **Deactivate**: Via a generic "Remove" (Trash icon) button on the module card in the Dashboard.

## Modules
- **Contact Icons**: `src/modules/contact-icons/`. Floating WhatsApp/Phone buttons.

## Workflows
- **Build**: `npm run build`
- **Release**: GitHub Action (`release.yml`) builds and attaches zip on `v*` tags.
- **Lint**: `php -l <file>`

## Conventions
- **Prefix**: `WSCH_` (WordPress Simple Customise Helper).
- **Style**: React functional components, PHP snake_case.
- **UI**: Use `@wordpress/components`.
- **Dashboard**: "Marketplace" style. Active modules listed on Dashboard. Add new modules via Modal.
