-- Upgrade legacy `users` table: hashed passwords + RBAC role (keeps Company_ID as PK)

ALTER TABLE users
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER Pin,
    ADD COLUMN role_id TINYINT UNSIGNED NULL AFTER password_hash,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role_id,
    ADD COLUMN failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active,
    ADD COLUMN locked_until DATETIME NULL AFTER failed_login_attempts,
    ADD COLUMN last_login_at DATETIME NULL AFTER locked_until,
    ADD COLUMN password_changed_at DATETIME NULL AFTER last_login_at,
    ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER password_changed_at;

ALTER TABLE users
    ADD KEY idx_users_role (role_id);

-- FK only if roles table exists (created in 001)
ALTER TABLE users
    ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id);
