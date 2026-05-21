-- Daily Attendance Document (DAD) submissions from head guard portal (role 0).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS guard_dad_submissions (
    dad_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_code VARCHAR(32) NOT NULL,
    dgd_report_number INT NULL,
    head_guard_company_id VARCHAR(13) NOT NULL,
    head_guard_name VARCHAR(255) NULL,
    post_name VARCHAR(255) NOT NULL,
    shift_date DATE NOT NULL,
    shift_display VARCHAR(255) NULL,
    guard_id VARCHAR(64) NULL,
    guard_name VARCHAR(255) NULL,
    issue VARCHAR(64) NOT NULL DEFAULT 'roster_review',
    time_record TEXT NULL,
    recorded VARCHAR(32) NOT NULL DEFAULT 'missing',
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    summary TEXT NULL,
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
    PRIMARY KEY (dad_id),
    UNIQUE KEY uk_guard_dad_reference (reference_code),
    KEY idx_guard_dad_status (status),
    KEY idx_guard_dad_shift (shift_date),
    KEY idx_guard_dad_head (head_guard_company_id),
    KEY idx_guard_dad_dgd (dgd_report_number),
    CONSTRAINT fk_guard_dad_head_user
        FOREIGN KEY (head_guard_company_id) REFERENCES users (Company_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_guard_dad_dgd
        FOREIGN KEY (dgd_report_number) REFERENCES dgd (Report_Number)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
