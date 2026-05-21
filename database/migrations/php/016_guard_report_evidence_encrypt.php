<?php
declare(strict_types=1);

/**
 * Ensure guard_report_evidence exists and supports encrypted paths + metadata.
 */
return static function (PDO $conn): void {
    if (!db_table_exists($conn, 'guard_report_evidence')) {
        $conn->exec(
            'CREATE TABLE guard_report_evidence (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                report_number INT NOT NULL,
                company_id VARCHAR(13) NOT NULL,
                file_name TEXT NOT NULL,
                meta_cipher TEXT NULL,
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        echo "  [ok] Created guard_report_evidence.\n";

        return;
    }

    if (!db_column_exists($conn, 'guard_report_evidence', 'meta_cipher')) {
        $conn->exec('ALTER TABLE guard_report_evidence ADD COLUMN meta_cipher TEXT NULL AFTER file_name');
        echo "  [ok] Added guard_report_evidence.meta_cipher.\n";
    }

    $conn->exec('ALTER TABLE guard_report_evidence MODIFY COLUMN file_name TEXT NOT NULL');
    echo "  [ok] guard_report_evidence.file_name ready for encrypted paths.\n";
};
