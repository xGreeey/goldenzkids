# Schema audit — ABC Security (`abc_security`)

Review before importing `abc_security.sql` or running `database/migrate.php`.

## Critical mismatches (code vs old dump)

| Issue | App code | Old `abc_security.sql` | Fix |
|-------|----------|------------------------|-----|
| Memo inbox table | `memo_recipients` + `is_read` | `memo_reception` + `Is_Read` | Migration **008** renames/normalizes |
| DGD table name | `DGD` (PHP) | `dgd` (dump) | View `DGD` → `dgd` + PHP uses `dgd` |
| AI text column | `AI_Extracted_Text` | Missing on `dgd` | Migration **008** adds column |
| Send memo | Should create `memo_recipients` per guard | Inserts wrong `Company_ID` on reception | **send-memo.php** fixed |
| `users.Designation` | Redundant with `role` | Still present | Dropped in clean schema |
| `list_of_establishment` | Legacy file (singular) | `list_of_establishments` | Use `establishments` |

## Role model (current)

`users.role` — **do not** use a `roles` table:

| Value | Role |
|------:|------|
| 0 | Head guard |
| 1 | Admin |
| 2 | Super admin |

## Table purposes (after cleanup)

| Table | Purpose |
|-------|---------|
| `users` | Login only (`password_hash`, `role`) |
| `guards` | Employee roster (name, post); `Company_ID` → `users` |
| `establishments` | Posts/sites (replaces `list_of_establishments`) |
| `dgd` | Encrypted DGD reports |
| `memos` | Memo content + sender |
| `memo_recipients` | Per-guard delivery + read flag |
| `recording` | Login/logout audit trail |
| `schema_migrations` | Migration history |

## Removed / obsolete

- `roles`, `permissions`, `role_permissions`, `portal_users` (dropped by migration 007)
- Plain `users.Pin` (dropped by migration 005/006)

## Pre-migration checklist

1. **Backup** the database in phpMyAdmin (Export → SQL).
2. Run: `c:\xampp\php\php.exe database\migrate.php`
3. Confirm migration **008** applied in `schema_migrations`.
4. Test: login (admin + superadmin), admin inbox, send memo (targeted + broadcast), staff messaging.

## Fresh install

1. Create database `abc_security` in phpMyAdmin.
2. Import `abc_security.sql` from project root.
3. Run `database/migrate.php` (applies any newer migrations not in the dump).
4. Create users: `database/scripts/create_user.php`
