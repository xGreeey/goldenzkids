<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin_incident_status.php';

/**
 * PDO connection for incident guide reference data.
 */
function admin_incident_guide_pdo(): ?PDO
{
    $conn = $GLOBALS['conn'] ?? null;

    return $conn instanceof PDO ? $conn : null;
}

function admin_incident_guide_tables_ready(?PDO $conn = null): bool
{
    $conn ??= admin_incident_guide_pdo();
    if (!$conn instanceof PDO) {
        return false;
    }

    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    $ready = db_table_exists($conn, 'incident_types')
        && db_table_exists($conn, 'incident_guide_sections');

    return $ready;
}

function admin_incident_guide_has_seed_data(?PDO $conn = null): bool
{
    $conn ??= admin_incident_guide_pdo();
    if (!$conn instanceof PDO || !admin_incident_guide_tables_ready($conn)) {
        return false;
    }

    static $has = null;
    if ($has !== null) {
        return $has;
    }

    $row = db_fetch_one($conn, 'SELECT 1 FROM incident_types WHERE is_active = 1 LIMIT 1');
    $has = $row !== null;

    return $has;
}

function admin_incident_type_slug(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

    return trim($slug, '-') !== '' ? trim($slug, '-') : 'incident-type';
}

/**
 * @return list<array{
 *   incident_type: string,
 *   category: string,
 *   category_label: string,
 *   severity: string,
 *   filing_basis: string,
 *   filing_trigger: string,
 *   initial_status: string,
 *   response_sla: string,
 *   responsible: string,
 *   system_action: string,
 *   remarks: string,
 *   steps: list<string>
 * }>|null
 */
function admin_incident_types_reference_from_db(?PDO $conn = null): ?array
{
    $conn ??= admin_incident_guide_pdo();
    if (!$conn instanceof PDO || !admin_incident_guide_has_seed_data($conn)) {
        return null;
    }

    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    if (!function_exists('admin_incident_category_label')) {
        require_once __DIR__ . '/admin_incident_reports.php';
    }

    $typeRows = db_fetch_all(
        $conn,
        'SELECT id, slug, incident_type, category, severity, filing_basis, filing_trigger,
                initial_status, response_sla, responsible, system_action, remarks
         FROM incident_types
         WHERE is_active = 1
         ORDER BY sort_order ASC, incident_type ASC'
    );

    if ($typeRows === []) {
        return null;
    }

    $ids = array_map(static fn (array $r): int => (int) $r['id'], $typeRows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $detailStmt = db_query(
        $conn,
        "SELECT incident_type_id, step_order, step_text
         FROM incident_type_detail_steps
         WHERE incident_type_id IN ({$placeholders})
         ORDER BY incident_type_id ASC, step_order ASC",
        str_repeat('i', count($ids)),
        $ids
    );

    $detailByType = [];
    if ($detailStmt !== false) {
        foreach ($detailStmt->fetchAll(PDO::FETCH_ASSOC) as $step) {
            $tid = (int) $step['incident_type_id'];
            $detailByType[$tid][] = (string) $step['step_text'];
        }
    }

    $types = [];
    foreach ($typeRows as $row) {
        $slug = admin_incident_category_normalize((string) $row['category']);
        $types[] = admin_incident_apply_status_terminology_to_type_row([
            'incident_type' => (string) $row['incident_type'],
            'category' => $slug,
            'category_label' => admin_incident_category_label($slug),
            'severity' => (string) $row['severity'],
            'filing_basis' => (string) $row['filing_basis'],
            'filing_trigger' => (string) $row['filing_trigger'],
            'initial_status' => (string) $row['initial_status'],
            'response_sla' => (string) $row['response_sla'],
            'responsible' => (string) $row['responsible'],
            'system_action' => (string) $row['system_action'],
            'remarks' => (string) ($row['remarks'] ?? ''),
            'steps' => $detailByType[(int) $row['id']] ?? [],
        ]);
    }

    $cache = $types;

    return $cache;
}

/**
 * @return array<string, list<string>>|null slug => four workflow step labels
 */
function admin_incident_workflow_steps_map_from_db(?PDO $conn = null): ?array
{
    $conn ??= admin_incident_guide_pdo();
    if (!$conn instanceof PDO || !admin_incident_guide_has_seed_data($conn)) {
        return null;
    }

    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $rows = db_fetch_all(
        $conn,
        'SELECT t.slug, w.step_order, w.step_label
         FROM incident_type_workflow_steps w
         INNER JOIN incident_types t ON t.id = w.incident_type_id
         WHERE t.is_active = 1
         ORDER BY t.slug ASC, w.step_order ASC'
    );

    if ($rows === []) {
        return null;
    }

    $map = [];
    foreach ($rows as $row) {
        $slug = (string) $row['slug'];
        $map[$slug][(int) $row['step_order']] = admin_incident_apply_status_terminology((string) $row['step_label']);
    }

    foreach ($map as $slug => $steps) {
        ksort($steps);
        $map[$slug] = array_values($steps);
    }

    $cache = $map;

    return $cache;
}

/**
 * @param list<string> $groups Section groups to load (e.g. operations, violation).
 * @return list<array{
 *   id: string,
 *   title: string,
 *   intro: string,
 *   columns: list<string>,
 *   rows: list<list<string>>
 * }>|null
 */
function admin_incident_guide_sections_from_db(array $groups, ?PDO $conn = null): ?array
{
    $conn ??= admin_incident_guide_pdo();
    if (!$conn instanceof PDO || !admin_incident_guide_has_seed_data($conn)) {
        return null;
    }

    if ($groups === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($groups), '?'));
    $sectionRows = db_fetch_all(
        $conn,
        "SELECT id, slug, title, intro
         FROM incident_guide_sections
         WHERE is_active = 1 AND section_group IN ({$placeholders})
         ORDER BY sort_order ASC, title ASC",
        str_repeat('s', count($groups)),
        $groups
    );

    if ($sectionRows === []) {
        return null;
    }

    $sectionIds = array_map(static fn (array $r): int => (int) $r['id'], $sectionRows);
    $idPlaceholders = implode(',', array_fill(0, count($sectionIds), '?'));
    $bindTypes = str_repeat('i', count($sectionIds));

    $columnRows = db_fetch_all(
        $conn,
        "SELECT id, section_id, col_order, column_label
         FROM incident_guide_columns
         WHERE section_id IN ({$idPlaceholders})
         ORDER BY section_id ASC, col_order ASC",
        $bindTypes,
        $sectionIds
    );

    $columnsBySection = [];
    $columnIdToSection = [];
    foreach ($columnRows as $col) {
        $sid = (int) $col['section_id'];
        $columnsBySection[$sid][] = [
            'id' => (int) $col['id'],
            'label' => admin_incident_apply_status_terminology((string) $col['column_label']),
        ];
        $columnIdToSection[(int) $col['id']] = $sid;
    }

    $rowMeta = db_fetch_all(
        $conn,
        "SELECT id, section_id, row_order
         FROM incident_guide_rows
         WHERE section_id IN ({$idPlaceholders})
         ORDER BY section_id ASC, row_order ASC",
        $bindTypes,
        $sectionIds
    );

    $rowIds = array_map(static fn (array $r): int => (int) $r['id'], $rowMeta);
    $cellsByRow = [];
    if ($rowIds !== []) {
        $rowPlaceholders = implode(',', array_fill(0, count($rowIds), '?'));
        $cellRows = db_fetch_all(
            $conn,
            "SELECT c.row_id, c.column_id, c.cell_value, col.section_id, col.col_order
             FROM incident_guide_cells c
             INNER JOIN incident_guide_columns col ON col.id = c.column_id
             WHERE c.row_id IN ({$rowPlaceholders})
             ORDER BY c.row_id ASC, col.col_order ASC",
            str_repeat('i', count($rowIds)),
            $rowIds
        );

        foreach ($cellRows as $cell) {
            $rid = (int) $cell['row_id'];
            $cellsByRow[$rid][(int) $cell['col_order']] = admin_incident_apply_status_terminology((string) $cell['cell_value']);
        }
    }

    $rowsBySection = [];
    foreach ($rowMeta as $row) {
        $rid = (int) $row['id'];
        $sid = (int) $row['section_id'];
        $colCount = count($columnsBySection[$sid] ?? []);
        $cells = $cellsByRow[$rid] ?? [];
        $line = [];
        for ($i = 0; $i < $colCount; $i++) {
            $line[] = $cells[$i] ?? '';
        }
        $rowsBySection[$sid][] = $line;
    }

    $sections = [];
    foreach ($sectionRows as $section) {
        $sid = (int) $section['id'];
        $columns = array_map(
            static fn (array $c): string => (string) $c['label'],
            $columnsBySection[$sid] ?? []
        );
        $sections[] = [
            'id' => (string) $section['slug'],
            'title' => admin_incident_apply_status_terminology((string) $section['title']),
            'intro' => admin_incident_apply_status_terminology((string) ($section['intro'] ?? '')),
            'columns' => $columns,
            'rows' => admin_incident_apply_status_terminology_rows($rowsBySection[$sid] ?? []),
        ];
    }

    return $sections;
}
