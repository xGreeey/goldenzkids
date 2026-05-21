<?php
declare(strict_types=1);

/**
 * Group messaging: admin-created chats with selected head guards.
 */
return static function (PDO $conn): void {
    if (db_table_exists($conn, 'message_groups')) {
        echo "  [skip] message_groups tables already exist.\n";
        return;
    }

    $statements = [
        'CREATE TABLE message_groups (
            group_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_name VARCHAR(120) NOT NULL,
            created_by_company_id VARCHAR(13) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (group_id),
            KEY idx_message_groups_creator (created_by_company_id, created_at),
            CONSTRAINT fk_message_groups_creator
                FOREIGN KEY (created_by_company_id) REFERENCES users (Company_ID)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE message_group_members (
            member_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id INT UNSIGNED NOT NULL,
            company_id VARCHAR(13) NOT NULL,
            joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (member_id),
            UNIQUE KEY uk_message_group_member (group_id, company_id),
            KEY idx_message_group_members_user (company_id),
            CONSTRAINT fk_message_group_members_group
                FOREIGN KEY (group_id) REFERENCES message_groups (group_id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_message_group_members_user
                FOREIGN KEY (company_id) REFERENCES users (Company_ID)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE message_group_messages (
            message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id INT UNSIGNED NOT NULL,
            sender_company_id VARCHAR(13) NOT NULL,
            body_text TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (message_id),
            KEY idx_message_group_messages_group (group_id, created_at),
            CONSTRAINT fk_message_group_messages_group
                FOREIGN KEY (group_id) REFERENCES message_groups (group_id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_message_group_messages_sender
                FOREIGN KEY (sender_company_id) REFERENCES users (Company_ID)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE message_group_read_state (
            group_id INT UNSIGNED NOT NULL,
            company_id VARCHAR(13) NOT NULL,
            last_read_message_id BIGINT UNSIGNED NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (group_id, company_id),
            CONSTRAINT fk_message_group_read_group
                FOREIGN KEY (group_id) REFERENCES message_groups (group_id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_message_group_read_user
                FOREIGN KEY (company_id) REFERENCES users (Company_ID)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    ];

    foreach ($statements as $sql) {
        $conn->exec($sql);
    }

    echo "  Created message_groups, message_group_members, message_group_messages, message_group_read_state.\n";
};
