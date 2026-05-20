# Database migrations & RBAC

SQL migrations and PHP data migrations for the ABC Security Agency portal.

## Structure

```
database/
  migrate.php              # Run all pending migrations
  migrations/              # Ordered SQL files (001_, 002_, …)
  migrations/php/          # PHP migrations (legacy data, hashing)
  scripts/
    create_user.php        # Create a portal user with hashed password
    hash_password.php      # Generate a bcrypt hash for manual SQL
```

## User roles (`users.role`)

Roles are stored as a **number on `users`** (no `roles` table after migration 007):

| Value | Role | Portal |
|------:|------|--------|
| **0** | Head guard | `guard/*` |
| **1** | Admin | `admin/*` |
| **2** | Super admin | `admin/*` (all admin permissions) |

Permissions are defined in PHP (`includes/auth.php`), not in the database.

| Table | Purpose |
|-------|---------|
| `users` | Login: `Company_ID`, `password_hash`, `role`, `Email`, … |
| `schema_migrations` | Tracks applied migrations |

Migrations 001–002 create temporary RBAC tables; **007** converts to numeric `users.role` and drops `roles`, `permissions`, `role_permissions`, and `portal_users`.

## Run migrations (XAMPP)

From the project root:

```bash
c:\xampp\php\php.exe database\migrate.php
```

Or in browser (local dev only):  
`http://localhost/goldenzkids/database/migrate.php`

## Create a new user

```bash
c:\xampp\php\php.exe database\scripts\create_user.php ABC-2001-0042 123456 1
c:\xampp\php\php.exe database\scripts\create_user.php ABC-2001-0099 654321 0 guard@example.com
c:\xampp\php\php.exe database\scripts\create_user.php ABC-2001-0001 654321 superadmin
```

Arguments: `company_id` `password` `role` `[email]` — role is `0`, `1`, `2` or `headguard`, `admin`, `superadmin`

## Hash a password manually

```bash
c:\xampp\php\php.exe database\scripts\hash_password.php "your-access-code"
```

## Session fields after login

- `user_id`, `company_id`, `role_slug`, `role_name`
- `permissions` — array of permission slugs for `auth_user_can()`

## Alter legacy `users` table (005–006)

Adds `password_hash`, `role_id`, hashes each `Pin`, drops plain `Pin`, keeps `Company_ID` as primary key.

```bash
c:\xampp\php\php.exe database\migrate.php
```

| After migration | Column | Notes |
|-----------------|--------|--------|
| `Company_ID` | varchar(13) PK | Unchanged |
| `password_hash` | varchar(255) | Bcrypt — use this for login |
| `role_id` | FK → `roles` | Replaces `Designation` for auth |
| `Designation` | varchar(5) | Kept for display / legacy |
| `Email` | varchar(255) | Unchanged |
| ~~`Pin`~~ | removed | Dropped after hashing |

If `Pin` was dropped before hashes were copied, run migrations again; `006_repair_users_password_hashes.php` syncs from `portal_users`.

## Consolidated schema (`abc_security.sql`)

The project root `abc_security.sql` is the **clean reference** for new databases. See `database/SCHEMA_AUDIT.md` for known issues and the pre-migration checklist.

After import, always run:

```bash
c:\xampp\php\php.exe database\migrate.php
```

Migration **008** aligns an old messy dump with the app (memo table names, `dgd.AI_Extracted_Text`, etc.).

## Rollback

Migrations are forward-only. Restore from a database backup before re-running if needed.
