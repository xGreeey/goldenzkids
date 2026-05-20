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



## Guard role module



```

guard/

  portal.php          # pages (URLs unchanged)

  inbox.php

  corner.php

  php/

    bootstrap.php     # loads app + guard helpers

    guard.php         # head/footer, layout partials, styles/scripts helpers

    submit-report.php # POST handler for portal uploads

  assets/

    css/guard.css     # all guard module CSS

    js/guard.js       # portal + inbox client behavior

```



**Every guard page:**



```php

require_once __DIR__ . '/php/bootstrap.php';

auth_require_permission('guard....');



guard_head('Page Title', 'guard-portal guard-inbox'); // body class per page

guard_layout_header_nav(); // or guard_layout_header_back()

// ... page HTML ...

guard_footer(); // loads guard.js + theme toggle

```



Guard CSS/JS live under `guard/assets/` — not mixed into `includes/theme.php`.



---



*May 2026.*

