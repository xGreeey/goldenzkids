-- Post incident submissions from head guard portal (role 0) → Admin incident reports registry.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS guard_incident_submissions (
    inc_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_code VARCHAR(32) NOT NULL,
    dgd_report_number INT NULL,
    head_guard_company_id VARCHAR(13) NOT NULL,
    head_guard_name VARCHAR(255) NULL,
    category VARCHAR(32) NOT NULL DEFAULT 'per_post',
    incident_type VARCHAR(255) NOT NULL,
    severity VARCHAR(16) NOT NULL DEFAULT 'Medium',
    site_name VARCHAR(255) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'ongoing',
    summary TEXT NULL,
    incident_description TEXT NULL,
    action_taken TEXT NULL,
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
    PRIMARY KEY (inc_id),
    UNIQUE KEY uk_guard_inc_reference (reference_code),
    KEY idx_guard_inc_status (status),
    KEY idx_guard_inc_category (category),
    KEY idx_guard_inc_severity (severity),
    KEY idx_guard_inc_head (head_guard_company_id),
    KEY idx_guard_inc_dgd (dgd_report_number),
    CONSTRAINT fk_guard_inc_head_user
        FOREIGN KEY (head_guard_company_id) REFERENCES users (Company_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_guard_inc_dgd
        FOREIGN KEY (dgd_report_number) REFERENCES dgd (Report_Number)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
