# Ajax Calendar Clone Base

Features:
- Views: year, month, week, day, list
- EU date/time formatting (dd/mm/yyyy, 24h)
- Roles: visitor (not logged in / registered visitor), `editor`, `admin`
- Admin approval flow for newly registered users
- Admin user handling: approve users and assign countries
- Admin-editable countries (upsert)
- User profile fields: first name, last name, default country
- Events show creator name
- Event fields: title, description, event link
- Separate login page: `/login/` (password + OTP)
- User/admin page: `admin.php`

Database:
- Default DB: `d130770_jxcal`
- New install: import `database/schema.sql`
- Existing install: run `database/migration_2026_04_user_admin_upgrade.sql`
- Timezone setting: run `database/migration_2026_04_timezone_setting.sql`
- Event media fields: run `database/migration_2026_04_event_media.sql`
- Event multi-country fields: run `database/migration_2026_04_event_multi_country.sql`
- Recurring monthly events: run `database/migration_2026_04_recurring_events.sql`

Config:
- Local private config: `includes/database.config.php` (git-ignored)
- Template in git: `includes/database.config.php.txt`
