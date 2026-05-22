<?php
declare(strict_types=1);

return static function (PDO $conn): void {
    if (!db_table_exists($conn, 'incident_types')) {
        throw new RuntimeException('Run 020_incident_guide_reference.sql before the PHP seed.');
    }

    $existing = db_fetch_one($conn, 'SELECT COUNT(*) AS c FROM incident_types');
    if ((int) ($existing['c'] ?? 0) > 0) {
        echo "  [skip] incident guide reference data already seeded.\n";

        return;
    }

    $appRoot = dirname(__DIR__, 3);
    require_once $appRoot . '/includes/admin_incident_status.php';
    require_once $appRoot . '/includes/admin_incident_violation_workflow.php';
    require_once $appRoot . '/includes/admin_incident_outside_post_catalog.php';
    require_once $appRoot . '/includes/admin_incident_guide_store.php';
    require_once $appRoot . '/includes/admin_incident_guidelines.php';

    $types = admin_incident_types_reference_legacy();
    $sort = 0;
    foreach ($types as $row) {
        $sort += 10;
        $slug = admin_incident_type_slug((string) $row['incident_type']);
        $category = (string) ($row['category'] ?? 'per_post');
        if (!in_array($category, ['per_post', 'outside_post'], true)) {
            $category = 'per_post';
        }

        db_execute(
            $conn,
            'INSERT INTO incident_types (
                slug, incident_type, category, severity, filing_basis, filing_trigger,
                initial_status, response_sla, responsible, system_action, remarks, sort_order
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'sssssssssssi',
            [
                $slug,
                (string) $row['incident_type'],
                $category,
                (string) ($row['severity'] ?? 'Medium'),
                (string) ($row['filing_basis'] ?? ''),
                (string) ($row['filing_trigger'] ?? ''),
                (string) ($row['initial_status'] ?? 'Ongoing'),
                (string) ($row['response_sla'] ?? ''),
                (string) ($row['responsible'] ?? ''),
                (string) ($row['system_action'] ?? ''),
                (string) ($row['remarks'] ?? ''),
                $sort,
            ]
        );

        $typeId = db_last_insert_id($conn);
        $detailSteps = is_array($row['steps'] ?? null) ? $row['steps'] : [];
        foreach ($detailSteps as $stepIndex => $stepText) {
            db_execute(
                $conn,
                'INSERT INTO incident_type_detail_steps (incident_type_id, step_order, step_text) VALUES (?, ?, ?)',
                'iis',
                [$typeId, $stepIndex + 1, (string) $stepText]
            );
        }

        $workflowSteps = incident_guide_seed_workflow_four_steps($row);
        foreach ($workflowSteps as $stepIndex => $label) {
            db_execute(
                $conn,
                'INSERT INTO incident_type_workflow_steps (incident_type_id, step_order, step_label) VALUES (?, ?, ?)',
                'iis',
                [$typeId, $stepIndex + 1, $label]
            );
        }
    }

    $sectionSort = 0;
    foreach (admin_incident_guidelines_sections_legacy() as $section) {
        $sectionSort += 10;
        incident_guide_seed_section($conn, $section, 'operations', $sectionSort);
    }

    $sectionSort = 0;
    foreach (admin_incident_violation_workflow_sections_legacy() as $section) {
        $sectionSort += 10;
        incident_guide_seed_section($conn, $section, 'violation', $sectionSort);
    }

    echo '  [ok] Seeded ' . count($types) . ' incident types and guide sections.' . PHP_EOL;
};

/**
 * @param array<string, mixed> $row
 * @return list<string>
 */
function incident_guide_seed_workflow_four_steps(array $row): array
{
    $key = strtolower((string) ($row['incident_type'] ?? ''));
    $overrides = admin_incident_guard_workflow_step_overrides();
    if (isset($overrides[$key])) {
        return $overrides[$key];
    }

    return [
        'Guard files report',
        'Admin reviews report',
        'Admin requests evidence',
        'Guard submits evidence',
    ];
}

/**
 * @param array{id: string, title: string, intro: string, columns: list<string>, rows: list<list<string>>} $section
 */
function incident_guide_seed_section(PDO $conn, array $section, string $group, int $sortOrder): void
{
    db_execute(
        $conn,
        'INSERT INTO incident_guide_sections (slug, section_group, title, intro, sort_order)
         VALUES (?, ?, ?, ?, ?)',
        'ssssi',
        [
            (string) $section['id'],
            $group,
            (string) $section['title'],
            (string) ($section['intro'] ?? ''),
            $sortOrder,
        ]
    );

    $sectionId = db_last_insert_id($conn);
    $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
    $columnIds = [];
    foreach ($columns as $colIndex => $label) {
        db_execute(
            $conn,
            'INSERT INTO incident_guide_columns (section_id, col_order, column_label) VALUES (?, ?, ?)',
            'iis',
            [$sectionId, $colIndex + 1, (string) $label]
        );
        $columnIds[$colIndex + 1] = db_last_insert_id($conn);
    }

    $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
    foreach ($rows as $rowIndex => $cells) {
        db_execute(
            $conn,
            'INSERT INTO incident_guide_rows (section_id, row_order) VALUES (?, ?)',
            'ii',
            [$sectionId, $rowIndex + 1]
        );
        $rowId = db_last_insert_id($conn);
        if (!is_array($cells)) {
            continue;
        }
        foreach ($cells as $colIndex => $value) {
            $columnId = $columnIds[$colIndex + 1] ?? null;
            if ($columnId === null) {
                continue;
            }
            db_execute(
                $conn,
                'INSERT INTO incident_guide_cells (row_id, column_id, cell_value) VALUES (?, ?, ?)',
                'iis',
                [$rowId, $columnId, (string) $value]
            );
        }
    }
}
