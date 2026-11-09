# Deployment value breakdown — Golden Z-5 Security Portal

**Client:** Golden Z-5 Security & Investigation Agency, Inc.  
**System:** Security agency operations portal (Admin · Head Guard · Super Admin)  
**Total contract value:** **₱35,000.00** (Philippine Peso)  
**Status:** **Deployed / live**  
**Stack:** PHP 8+, MySQL, HTTPS-ready web hosting  

This document itemizes what is included in the ₱35,000 deployment so the agency can review scope, value, and ongoing responsibilities.

---

## Summary

| Category | Amount (₱) | Share |
|----------|----------:|------:|
| Security & access control | 3,850 | 11% |
| Core platform & infrastructure | 2,100 | 6% |
| Admin operations dashboard | 1,050 | 3% |
| Staff messaging | 2,800 | 8% |
| Memos & announcements | 1,750 | 5% |
| Weekly summary reports | 2,450 | 7% |
| Daily activity reports | 2,450 | 7% |
| DTR / DAD (Daily Time Record) | 3,150 | 9% |
| Incident reports & workflow | 3,850 | 11% |
| Head guard field portal | 3,150 | 9% |
| OCR & document capture | 2,100 | 6% |
| Super admin & governance | 2,800 | 8% |
| Head guard post management | 1,050 | 3% |
| Deployment, training & go-live | 2,450 | 7% |
| **Total** | **35,000** | **100%** |

---

## 1. Security & access control — ₱3,850

Dedicated security layer for a system that handles guard reports, incident evidence, and staff communications.

| Capability | What it does |
|------------|--------------|
| **Role-based access (RBAC)** | Three roles — Head Guard, Admin, Super Admin — each limited to their portal (`guard/`, `admin/`, `superadmin/`). Cross-portal access is blocked automatically. |
| **Permission checks** | Fine-grained permissions (dashboard, inbox, reports, memos, audit, etc.) enforced in PHP before any sensitive action runs. |
| **Bcrypt passwords** | Access codes stored as one-way bcrypt hashes — plain-text passwords are never kept in the database. |
| **Password policy** | Minimum 8 characters with uppercase, lowercase, number, and special character. Enforced on create and reset. |
| **First-login password change** | New or reset accounts must change their temporary access code before using the system. |
| **OTP password recovery** | Forgot-access-code flow sends a 6-digit email OTP (15-minute expiry) before allowing a reset. |
| **Session security** | HttpOnly cookies, SameSite=Lax, secure flag on HTTPS, 8-hour session lifetime, session ID regenerated on login. |
| **CSRF protection** | Every state-changing POST (forms and AJAX) requires a valid CSRF token; invalid tokens return 403. |
| **XSS prevention** | All user-facing output escaped for HTML, attributes, and JavaScript; untrusted input stripped before storage. |
| **HTTP security headers** | Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, and Permissions-Policy applied on every page. |
| **Encrypted evidence at rest** | Report photos and scan files encrypted on disk with OpenSSL; file paths and metadata stored as ciphertext in the database. |
| **Encrypted sensitive fields** | AI-extracted form text, activity details, and template paths encrypted in the database — not stored as plain text. |
| **Append-only audit trail** | Login, logout, account changes, memos, and portal actions logged to the `recording` table; staff cannot delete audit entries from the UI. |
| **Server hardening** | `.htaccess` blocks direct web access to `config/`, `database/migrations/`, `.env`, and credentials; production traffic forced to HTTPS. |
| **SQL injection protection** | Database queries use parameterized prepared statements throughout the application layer. |

**Primary code:** `includes/security.php`, `includes/auth.php`, `config/session.php`, `includes/portal_audit.php`, `includes/guard_portal.php`, `.htaccess`

---

## 2. Core platform & infrastructure — ₱2,100

| Included | Detail |
|----------|--------|
| Shared application shell | Branded layout, sidebar navigation, top bar, light/dark theme |
| Configuration layer | Environment-aware URLs, agency name/logo, database connection |
| Database schema | Full `abc_security` schema with **36 migrations** for upgrades |
| Migration runner | `database/migrate.php` — applies schema updates safely on deploy |
| Notification system | In-app flash alerts and global alert polling |
| Email integration | PHPMailer for OTP and system mail (SMTP configured on server) |

---

## 3. Admin operations dashboard — ₱1,050

| Included | Detail |
|----------|--------|
| Operations dashboard | KPI overview and quick navigation for daily admin work |
| Workspace navigation | Sidebar with Dashboard, Inbox, Announcement, Reports, Head guard posts |
| Account settings | Profile and password management for admin users |

**Portal URL:** `/admin/`

---

## 4. Staff messaging — ₱2,800

| Included | Detail |
|----------|--------|
| Messaging board | Admin ↔ superadmin internal inbox |
| Direct messages | 1:1 threads with read tracking |
| Group messaging | Create and manage message groups |
| Real-time polling | AJAX poll for new messages without full page reload |
| Messaging audit | Send/edit actions recorded in the audit trail |

---

## 5. Memos & announcements — ₱1,750

| Included | Detail |
|----------|--------|
| Memo composer | Rich memo creation from admin |
| Targeted delivery | Send to one head guard or broadcast to all |
| Guard corner | Head guards read memos in their portal |
| Read tracking | Per-recipient read status via `memo_recipients` |

---

## 6. Weekly summary reports — ₱2,450

| Included | Detail |
|----------|--------|
| Head guard submission | Weekly accomplishment / summary from the field |
| Admin review | Browse, filter, and review weekly submissions |
| Print support | Print-friendly report output |
| Recovery (super admin) | Deleted and archived weekly reports recoverable |

---

## 7. Daily activity reports — ₱2,450

| Included | Detail |
|----------|--------|
| Field activity logs | Shift and on-site activity submitted by head guards |
| Status pipeline | Submission → review workflow with status tracking |
| Photo evidence | Encrypted on-site photos attached to daily activity |
| Admin review | Daily activity registry and detail views |

---

## 8. DTR / DAD (Daily Time Record) — ₱3,150

| Included | Detail |
|----------|--------|
| DTR registry | Time-in/out, NTE, and missing-value tracking |
| Duty detail | Per-guard duty breakdown and attendance issues |
| DAD submissions | Daily Activity Detail forms from head guards |
| Dual-location support | Separate location fields for DAD records |
| Document AI scan | OCR reads scanned DTR/DAD forms on submit |
| Encrypted scans | Uploaded scan files stored encrypted |

---

## 9. Incident reports & workflow — ₱3,850

| Included | Detail |
|----------|--------|
| Incident submission | Head guards file incident reports from the field |
| Severity pipeline | Status tracking from submission through resolution |
| Violation workflow | Structured violation handling with responsible parties |
| Incident type catalog | Reference data: severity, SLA, filing basis, remarks |
| Guard incident guide | Step-by-step guide and workflow columns per incident type |
| Outside-post catalog | Incidents occurring off assigned post |
| Encrypted evidence | Photos and documents encrypted at rest |
| Admin monitoring | Full incident inbox with archive and print |

---

## 10. Head guard field portal — ₱3,150

Mobile-friendly portal for supervisors in the field.

| Page / feature | Purpose |
|----------------|---------|
| Dashboard | Quick actions — submit report, inbox, guard corner |
| Submit report | Daily activity, DTR/DAD, and incident submissions |
| Inbox | Messages and notifications |
| Guard corner | Agency memos and announcements |
| Report history | View past submissions |
| Account settings | Profile and password |
| Camera / GPS | Guard portal permissions for on-site capture and location (where enabled) |

**Portal URL:** `/guard/`

---

## 11. OCR & document capture — ₱2,100

| Included | Detail |
|----------|--------|
| Form scan preview | API preview before final submit |
| Google Document AI | Integration for reading filled paper forms |
| Python prep script | `scripts/incident_form_ocr_prep.py` for image preprocessing |
| Encrypted OCR storage | Extracted text stored encrypted in the database |

> **Note:** Google Cloud / Document AI API usage is billed separately by Google to the agency's API account.

---

## 12. Super admin & governance — ₱2,800

| Included | Detail |
|----------|--------|
| System dashboard | Agency-wide overview |
| User account management | Create, edit, enable/disable portal accounts |
| Role assignment | Head Guard · Admin · Super Admin per account |
| Full audit log | Searchable log of all portal events |
| Accountability trail | Per-account history (created, role changed, disabled, password reset) |
| Report recovery | Restore or review deleted/archived reports (weekly, daily, DTR, incident) |
| Transparency views | Super-admin oversight pages |

**Portal URL:** `/superadmin/`

---

## 13. Head guard post management — ₱1,050

| Included | Detail |
|----------|--------|
| Post assignment | Link head guard accounts to duty posts / establishments |
| Establishment registry | Sites and posts managed in `establishments` table |
| Roster integration | Guard roster tied to company IDs (e.g. `ABC-2024-0021`) |

---

## 14. Deployment, training & go-live — ₱2,450

| Delivered | Detail |
|-----------|--------|
| Production deployment | Application installed on live server with HTTPS |
| Database setup | Schema imported and migrations applied |
| Initial user accounts | Super admin, admin, and head guard accounts created |
| Agency branding | Golden Z-5 name and logo configured |
| URL routing | Legacy URL redirects and portal paths configured |
| Handover / walkthrough | Admin and head guard orientation session |
| Go-live support | Initial bug fixes during warranty period |

---

## Portal overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Golden Z-5 Security Portal                │
├──────────────┬──────────────────────┬───────────────────────┤
│  HEAD GUARD  │        ADMIN         │     SUPER ADMIN       │
│  (field)     │    (operations)      │    (governance)       │
├──────────────┼──────────────────────┼───────────────────────┤
│ Submit       │ Dashboard            │ User accounts         │
│ reports      │ Inbox & messaging    │ Audit log             │
│ Inbox        │ Memos / announcements│ Report recovery       │
│ Guard corner │ 4 report modules     │ Accountability        │
│ Settings     │ Head guard posts     │ Full admin access     │
└──────────────┴──────────────────────┴───────────────────────┘
         ▲                    ▲                    ▲
         └──────── Security layer (RBAC, encryption,
                    CSRF, audit) wraps all portals ─┘
```

---

## What the ₱35,000 covers vs. ongoing costs

### Covered by ₱35,000 (one-time)

- Full system as itemized above  
- Production deployment and database setup  
- Initial accounts and branding  
- Training / handover session  
- Warranty-period bug fixes for delivered features  

### Ongoing — agency responsibility (not included in ₱35,000)

| Item | Typical cost | Notes |
|------|-------------|-------|
| Web hosting / VPS | ₱200 – ₱800+ / month | Server that runs the portal |
| Domain name | ₱500 – ₱1,500 / year | Agency web address |
| SSL certificate | Free – ₱2,000 / year | Let's Encrypt is free on most hosts |
| Google Document AI | Pay-per-use | Only if OCR scanning is active |
| Google Maps API | Pay-per-use | Only if map features are used |
| Email SMTP | Free – ₱500 / month | Gmail or paid relay for OTP emails |
| Future enhancements | Quoted separately | New modules, custom reports, integrations |
| Annual maintenance (optional) | ~15–20% of license / year | If purchased from vendor |

---

## Payment reference

| Milestone | Amount (₱) | Typical trigger |
|-----------|----------:|-----------------|
| Kickoff / reservation | 17,500 | Project start (50%) |
| Go-live | 14,000 | System deployed and accepted (40%) |
| Warranty completion | 3,500 | End of warranty period (10%) |
| **Total** | **35,000** | |

*Adjust dates and paid status to match your actual invoice records.*

---

## Revision log

| Date | Change |
|------|--------|
| 2026-07-06 | Initial breakdown at ₱35,000 |
| 2026-07-06 | Remade for deployed client; security module added as dedicated line item |

---

*This breakdown reflects the delivered Golden Z-5 Security Portal as deployed. For support requests or scope changes, contact your system provider.*
