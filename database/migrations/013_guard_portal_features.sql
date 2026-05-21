-- Guard portal: announcements, evidence attachments, duty status, staff messages.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS guard_announcements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_guard_announcements_active (is_active, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guard_report_evidence (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    report_number INT NOT NULL,
    company_id VARCHAR(13) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    gps_lat DECIMAL(10, 7) DEFAULT NULL,
    gps_lng DECIMAL(10, 7) DEFAULT NULL,
    captured_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_guard_evidence_report (report_number),
    KEY idx_guard_evidence_guard (company_id),
    CONSTRAINT fk_guard_evidence_report
        FOREIGN KEY (report_number) REFERENCES dgd (Report_Number) ON DELETE CASCADE,
    CONSTRAINT fk_guard_evidence_user
        FOREIGN KEY (company_id) REFERENCES users (Company_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guard_duty_status (
    company_id VARCHAR(13) NOT NULL,
    duty_status ENUM('active', 'off_duty', 'on_report') NOT NULL DEFAULT 'active',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (company_id),
    CONSTRAINT fk_guard_duty_status_user
        FOREIGN KEY (company_id) REFERENCES users (Company_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guard_staff_messages (
    message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_company_id VARCHAR(13) NOT NULL,
    recipient_company_id VARCHAR(13) NOT NULL,
    body_text TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    KEY idx_guard_msg_recipient (recipient_company_id, is_read, created_at),
    KEY idx_guard_msg_thread (sender_company_id, recipient_company_id, created_at),
    CONSTRAINT fk_guard_msg_sender FOREIGN KEY (sender_company_id) REFERENCES users (Company_ID) ON DELETE CASCADE,
    CONSTRAINT fk_guard_msg_recipient FOREIGN KEY (recipient_company_id) REFERENCES users (Company_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO guard_announcements (title, body) VALUES
    ('Shift briefing', 'Review post orders and radio check every hour. Report incidents through the portal immediately.'),
    ('Uniform inspection', 'Full uniform and ID must be worn during duty hours. Non-compliance will be noted in daily reports.');
