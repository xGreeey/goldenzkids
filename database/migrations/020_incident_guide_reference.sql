-- Incident types catalog, workflow steps, and guard-guide reference tables (admin reports).

CREATE TABLE IF NOT EXISTS incident_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(128) NOT NULL,
    incident_type VARCHAR(255) NOT NULL,
    category ENUM('per_post', 'outside_post') NOT NULL,
    severity ENUM('High', 'Medium', 'Low') NOT NULL DEFAULT 'Medium',
    filing_basis VARCHAR(255) NOT NULL DEFAULT '',
    filing_trigger TEXT NOT NULL,
    initial_status VARCHAR(64) NOT NULL DEFAULT 'Ongoing',
    response_sla VARCHAR(255) NOT NULL DEFAULT '',
    responsible VARCHAR(128) NOT NULL DEFAULT '',
    system_action VARCHAR(255) NOT NULL DEFAULT '',
    remarks TEXT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_incident_types_slug (slug),
    KEY idx_incident_types_category (category),
    KEY idx_incident_types_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_type_detail_steps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incident_type_id INT UNSIGNED NOT NULL,
    step_order TINYINT UNSIGNED NOT NULL,
    step_text TEXT NOT NULL,
    UNIQUE KEY uk_incident_type_detail_step (incident_type_id, step_order),
    CONSTRAINT fk_incident_type_detail_steps_type
        FOREIGN KEY (incident_type_id) REFERENCES incident_types (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_type_workflow_steps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incident_type_id INT UNSIGNED NOT NULL,
    step_order TINYINT UNSIGNED NOT NULL,
    step_label VARCHAR(255) NOT NULL,
    UNIQUE KEY uk_incident_type_workflow_step (incident_type_id, step_order),
    CONSTRAINT fk_incident_type_workflow_steps_type
        FOREIGN KEY (incident_type_id) REFERENCES incident_types (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_guide_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(64) NOT NULL,
    section_group VARCHAR(32) NOT NULL DEFAULT 'operations',
    title VARCHAR(255) NOT NULL,
    intro TEXT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_incident_guide_sections_slug (slug),
    KEY idx_incident_guide_sections_group (section_group, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_guide_columns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    col_order TINYINT UNSIGNED NOT NULL,
    column_label VARCHAR(255) NOT NULL,
    UNIQUE KEY uk_incident_guide_columns_order (section_id, col_order),
    CONSTRAINT fk_incident_guide_columns_section
        FOREIGN KEY (section_id) REFERENCES incident_guide_sections (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_guide_rows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    row_order SMALLINT UNSIGNED NOT NULL,
    UNIQUE KEY uk_incident_guide_rows_order (section_id, row_order),
    CONSTRAINT fk_incident_guide_rows_section
        FOREIGN KEY (section_id) REFERENCES incident_guide_sections (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incident_guide_cells (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    row_id INT UNSIGNED NOT NULL,
    column_id INT UNSIGNED NOT NULL,
    cell_value TEXT NOT NULL,
    UNIQUE KEY uk_incident_guide_cells (row_id, column_id),
    CONSTRAINT fk_incident_guide_cells_row
        FOREIGN KEY (row_id) REFERENCES incident_guide_rows (id) ON DELETE CASCADE,
    CONSTRAINT fk_incident_guide_cells_column
        FOREIGN KEY (column_id) REFERENCES incident_guide_columns (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
