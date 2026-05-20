<?php
declare(strict_types=1);

/**
 * Staff messaging between administrators and super administrators.
 */
return static function (mysqli $conn): void {
    $exists = $conn->query("SHOW TABLES LIKE 'internal_messages'");
    if ($exists && $exists->num_rows > 0) {
        echo "  [skip] internal_messages table already exists.\n";
        return;
    }

    if (!$conn->query(
        'CREATE TABLE internal_messages (
            message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sender_company_id VARCHAR(13) NOT NULL,
            recipient_company_id VARCHAR(13) NOT NULL,
            body_text TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (message_id),
            KEY idx_internal_messages_recipient (recipient_company_id, is_read, created_at),
            KEY idx_internal_messages_pair (sender_company_id, recipient_company_id, created_at),
            CONSTRAINT fk_internal_messages_sender
                FOREIGN KEY (sender_company_id) REFERENCES users (Company_ID) ON DELETE CASCADE,
            CONSTRAINT fk_internal_messages_recipient
                FOREIGN KEY (recipient_company_id) REFERENCES users (Company_ID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    )) {
        throw new RuntimeException('Could not create internal_messages: ' . $conn->error);
    }

    echo "  Created internal_messages table.\n";
};
