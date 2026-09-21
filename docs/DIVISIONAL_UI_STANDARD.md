# Divisional workflow UI standard

All Divisional Coordinator, Secretary, and Treasurer workflow pages use the shared dashboard layout plus `divisional-workflows.css` and `divisional-workflows.js`.

## Page structure

1. The shared dashboard header owns the page title, description, global search, notifications, and profile.
2. The feature begins with a responsive summary-card grid.
3. A single toolbar contains the page search, primary quick filter, advanced-filter trigger, export action, and primary action.
4. Advanced filters use the shared collapsible panel directly below the toolbar.
5. Primary data appears in a `.dw-panel` table or card grid. Secondary progress and pending-action panels use the same panel style.
6. Create/review forms open in the shared modal pattern. Destructive or permanent actions require a warning and confirmation.

## Reusable classes and partials

- Summary card: `partials/divisional/summary-card.view.php`
- Status pill: `partials/divisional/status-pill.view.php`
- Empty state: `partials/divisional/empty-state.view.php`
- Buttons: `.dw-button`, with `--primary`, `--secondary`, `--danger`, and `--ghost`
- Inputs: `.dw-field` with a label and input/select/textarea
- Search: `.dw-search`
- Filters: `.dw-filter-panel` and `.dw-filter-grid`
- Table: `.dw-table-wrap` and `.dw-table`
- Modal: `.dw-modal` and `.dw-modal__dialog`
- Feedback: `.dw-alert` and `.dw-toast`

## Rules

- Use the shared CSS variables; do not introduce page-specific colors, radii, shadows, or spacing for standard components.
- Use icons from `partials/icons.view.php`; do not add emoji or one-off icon libraries.
- Keep search height, field height, button height, and table header treatment consistent.
- Scope all server queries to the actor's assigned division before returning or mutating data.
- Escape user or database content in views and protect every mutation with CSRF validation.
- Add workflow-specific CSS only for a relationship that cannot be expressed by the shared components.
