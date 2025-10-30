# Data Models

This document summarizes the database schema based on discovered SQL files in `database/` and repo root.

## Sources
- `database/schema.sql`
- `create_mirza_db.sql` (database creation and user grants)

## Tables (from schema.sql)

### admin
- id_admin: INT, PK, auto-increment
- username_admin: VARCHAR(255), unique
- password_admin: VARCHAR(255)
- rule: VARCHAR(50), default 'administrator'
- created_at: TIMESTAMP (default CURRENT_TIMESTAMP)

### admin_activity_log
- id: INT, PK, auto-increment
- admin_id: INT, FK → admin.id_admin
- action: VARCHAR(255)
- description: TEXT
- ip_address: VARCHAR(45)
- created_at: TIMESTAMP (default CURRENT_TIMESTAMP)

### web_settings
- id: INT, PK, auto-increment
- setting_key: VARCHAR(255), unique
- setting_value: TEXT
- updated_at: TIMESTAMP (auto-updated on change)

## Notes
- Storage engine: InnoDB; charset/collation: utf8mb4/utf8mb4_unicode_ci
- Additional tables likely exist in other modules (payments, users, products). Further scanning required.

## Next Steps
- Extract remaining tables from PHP code and any additional SQL files _(To be generated)_
- Map relationships and constraints across modules _(To be generated)_
- Define migration plan to PostgreSQL (types, defaults, indexes) _(To be generated)_
