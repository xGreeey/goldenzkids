-- Superadmin recovery vault for admin report delete / archive actions.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admin_report_recovery (
    recovery_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    report_kind VARCHAR(32) NOT NULL,
    action_type ENUM('deleted', 'archived') NOT NULL,
    record_id VARCHAR(64) NOT NULL,
    record_ref VARCHAR(64) NOT NULL,
    payload_json JSON NOT NULL,
    actor_company_id VARCHAR(13) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    restored_at DATETIME NULL,
    PRIMARY KEY (recovery_id),
    KEY idx_recovery_kind_action (report_kind, action_type),
    KEY idx_recovery_record (record_id),
    KEY idx_recovery_pending (report_kind, restored_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
