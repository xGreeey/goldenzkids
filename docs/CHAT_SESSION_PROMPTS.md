# Chat Session — User Prompts & Instructions



**Project:** `d:\xampp\htdocs\goldenzkids` · **DB:** `abc_security` · **Stack:** PHP, MySQL, XAMPP



---



## Shared theme



| File | Role |

|------|------|

| `includes/theme.php` | Site-wide palette (Coolors: `#8ecae6` `#219ebc` `#023047` `#ffb703` `#fb8500`), typography, auth shell |

| `includes/admin_shell.php` | Admin layout CSS/JS (`admin_shell_styles()`, `admin_shell_scripts()`) |



Loaded via `config/app.php`.



**Auth pages** — `<body class="auth-shell">` + `<?php theme_styles(); ?>` inside `<style>`.



**Admin pages** — `<?php admin_shell_styles(); ?>` and `<?php admin_shell_scripts(); ?>`.



---



## Field guard portal (removed)



The former `guard/` head-guard mobile portal, PIN-only legacy login, and `auth/logout-guard.php` were removed. Old URLs redirect to the admin area or shared logout. Staff messaging is **admin ↔ superadmin** only (`includes/internal_messaging.php`).



---



*May 2026.*

