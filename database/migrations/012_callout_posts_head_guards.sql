-- Call-out reference data for admin (role 1) and head guard (role 0) portals.
-- Run in phpMyAdmin on database `abc_security`, or via migrate.php after adding to the runner.
-- Safe to re-run: uses INSERT IGNORE on unique keys.

SET NAMES utf8mb4;

-- Duty posts / sites
CREATE TABLE IF NOT EXISTS callout_posts (
    post_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_callout_post_name (post_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Head guards (optional link to users.Company_ID when a login account exists)
CREATE TABLE IF NOT EXISTS callout_head_guards (
    head_guard_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id VARCHAR(13) NULL COMMENT 'users.Company_ID when account exists',
    first_name VARCHAR(255) NOT NULL,
    middle_name VARCHAR(255) NULL,
    last_name VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL COMMENT 'Full name for UI and reports',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_callout_head_guard_display (display_name),
    KEY idx_callout_head_guard_company (company_id),
    CONSTRAINT fk_callout_head_guard_user
        FOREIGN KEY (company_id) REFERENCES users (Company_ID)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which head guard supervises which post (configure in admin; optional at seed time)
CREATE TABLE IF NOT EXISTS callout_post_assignments (
    assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    head_guard_id INT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_callout_post_head (post_id, head_guard_id),
    CONSTRAINT fk_callout_assign_post
        FOREIGN KEY (post_id) REFERENCES callout_posts (post_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_callout_assign_head_guard
        FOREIGN KEY (head_guard_id) REFERENCES callout_head_guards (head_guard_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Duty posts (Manila sites — assign to head guards in admin Head guard posts)
INSERT IGNORE INTO callout_posts (post_name) VALUES
    ('Quiapo, Manila'),
    ('Tondo, Manila'),
    ('Sta. Ana, Manila');

-- Head guards (set company_id after creating users with role 0)
INSERT IGNORE INTO callout_head_guards (company_id, first_name, middle_name, last_name, display_name) VALUES
    (NULL, 'Jose', 'Abad', 'Cruz', 'Jose Abad Cruz'),
    (NULL, 'Lucy', NULL, 'Heartfillia', 'Lucy Heartfillia'),
    (NULL, 'James', NULL, 'Harbor', 'James Harbor'),
    (NULL, 'Sova Russ', NULL, 'Del Rosario Jr.', 'Sova Russ Del Rosario Jr.');
