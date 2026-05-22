-- Daily Activity submissions from head guard portal.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS guard_daily_activity_submissions (
    da_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_code VARCHAR(32) NOT NULL,
    dgd_report_number INT NULL,
    head_guard_company_id VARCHAR(13) NOT NULL,
    head_guard_name VARCHAR(255) NULL,
    site_name VARCHAR(255) NOT NULL,
    activity_mode VARCHAR(16) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    activity_details_cipher TEXT NULL,
    scan_path_cipher TEXT NULL,
    ai_extracted_cipher TEXT NULL,
    iv VARCHAR(64) NOT NULL,
    submit_latitude DECIMAL(10, 7) NULL,
    submit_longitude DECIMAL(10, 7) NULL,
    submit_accuracy_m DECIMAL(8, 2) NULL,
    location_label VARCHAR(512) NULL,
    history_json JSON NULL,
    submitted_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (da_id),
    UNIQUE KEY uk_guard_da_reference (reference_code),
    KEY idx_guard_da_mode (activity_mode),
    KEY idx_guard_da_status (status),
    KEY idx_guard_da_head (head_guard_company_id),
    KEY idx_guard_da_dgd (dgd_report_number),
    CONSTRAINT fk_guard_da_head_user
        FOREIGN KEY (head_guard_company_id) REFERENCES users (Company_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_guard_da_dgd
        FOREIGN KEY (dgd_report_number) REFERENCES dgd (Report_Number)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
