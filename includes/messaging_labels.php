<?php
declare(strict_types=1);

/**
 * SQL expression for a person's display name: First Last (users profile, then guards roster, then email).
 */
function messaging_sql_display_label(PDO $conn, string $userAlias = 'u', string $guardAlias = 'g'): string
{
    $u = preg_match('/^[a-z][a-z0-9_]*$/i', $userAlias) ? $userAlias : 'u';
    $g = preg_match('/^[a-z][a-z0-9_]*$/i', $guardAlias) ? $guardAlias : 'g';

    if (auth_users_has_profile_names($conn)) {
        return "COALESCE(
            NULLIF(TRIM(CONCAT(
                COALESCE(NULLIF(TRIM({$u}.First_Name), ''), {$g}.First_Name),
                ' ',
                COALESCE(NULLIF(TRIM({$u}.Last_Name), ''), {$g}.Last_Name)
            )), ''),
            NULLIF(TRIM(CONCAT(TRIM({$g}.First_Name), ' ', TRIM({$g}.Last_Name))), ''),
            NULLIF(TRIM({$u}.Email), ''),
            {$u}.Company_ID
        )";
    }

    return "COALESCE(
        NULLIF(TRIM(CONCAT(TRIM({$g}.First_Name), ' ', TRIM({$g}.Last_Name))), ''),
        NULLIF(TRIM(CONCAT({$g}.Last_Name, ', ', {$g}.First_Name)), ','),
        NULLIF(TRIM({$u}.Email), ''),
        {$u}.Company_ID
    )";
}

/**
 * @param string $prefixExpr SQL fragment ending with comma, e.g. "NULLIF(TRIM(hg.display_name), ''),"
 */
function messaging_sql_label_with_prefix(PDO $conn, string $prefixExpr, string $userAlias = 'u', string $guardAlias = 'g'): string
{
    $core = messaging_sql_display_label($conn, $userAlias, $guardAlias);
    $prefix = trim($prefixExpr);

    if ($prefix === '') {
        return $core;
    }

    if (!str_ends_with($prefix, ',')) {
        $prefix .= ',';
    }

    return "COALESCE({$prefix} {$core})";
}

function messaging_resolve_user_label(PDO $conn, string $companyId): string
{
    if ($companyId === '') {
        return '';
    }

    $labelSql = messaging_sql_display_label($conn, 'u', 'g');
    $row = db_fetch_one(
        $conn,
        "SELECT {$labelSql} AS label
         FROM users u
         LEFT JOIN guards g ON g.Company_ID = u.Company_ID
         WHERE u.Company_ID = ?
         LIMIT 1",
        's',
        [$companyId]
    );

    if ($row === null) {
        return $companyId;
    }

    $label = trim((string) ($row['label'] ?? ''));

    return $label !== '' ? $label : $companyId;
}
