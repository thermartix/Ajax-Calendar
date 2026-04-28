# Ajax Calendar Clone Base

Modern PHP + AJAX calendar base with:

- Views: year, month, week, day, list
- Roles: `admin` (edit all) and `category_editor` (edit only own country category)
- Switchable country categories for visitors
- Event details: title, description, and event link (Zoom/website)
- Logo support via `assets/logo.svg`

## Quick start

1. Import database schema:
   - Run `database/schema.sql` in MySQL.
2. Configure DB credentials by environment variables (optional):
   - `CAL_DB_HOST`, `CAL_DB_USER`, `CAL_DB_PASS`, `CAL_DB_NAME`
3. Serve with PHP:
   - `php -S localhost:8000`
4. Open:
   - `http://localhost:8000`

## Notes

- Default DB fallback: `localhost`, `root`, empty password, DB `ajax_calendar_clone`.
- Replace `assets/logo.svg` with your own logo file if needed.