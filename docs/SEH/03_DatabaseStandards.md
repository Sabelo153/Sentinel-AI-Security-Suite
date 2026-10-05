# Sentinel Engineering Handbook

## 03. Database Standards

### Naming
- Tables: snake_case plural
- Columns: snake_case
- Foreign keys: {table_singular}_id
- Public identifiers: uuid
- Pivot tables: explicit business names preferred over implicit names

### Required Columns
Business tables should include, unless there is a strong reason not to:
- id or domain primary key
- uuid
- created_at
- updated_at
- deleted_at
- created_by
- updated_by

### Design Rules
- Use MariaDB with InnoDB.
- Use utf8mb4 and utf8mb4_unicode_ci.
- Prefer explicit foreign keys.
- Use prepared statements only from application code.
- Avoid storing business logic in triggers unless operationally necessary.
- Soft delete important business entities with deleted_at.

### Public ID Strategy
- Public APIs expose uuid values.
- Internal joins may use numeric keys for performance.
- Android and external clients must not rely on numeric IDs.

### Transaction Policy
Use transactions for multi-step writes such as:
- authentication session creation
- incident creation with child records
- attendance workflows
- patrol completion workflows
- audit-linked updates

### Migration Policy
- Migrations must be ordered and repeatable.
- Seed data must be idempotent where possible.
- Schema changes require matching documentation updates.
