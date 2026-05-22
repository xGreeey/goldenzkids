<?php
declare(strict_types=1);

return static function (PDO $conn): void {
    if (!db_table_exists($conn, 'guards') || !db_table_exists($conn, 'users')) {
        throw new RuntimeException('users and guards tables are required.');
    }

    require_once dirname(__DIR__, 3) . '/includes/auth.php';

    $existing = db_fetch_one($conn, "SELECT COUNT(*) AS c FROM guards WHERE Company_ID LIKE 'ABC-2026-03%'");
    if ((int) ($existing['c'] ?? 0) >= 35) {
        echo "  [skip] Field guard roster (ABC-2026-03xx) already seeded.\n";

        return;
    }

    $roleCol = auth_users_role_column($conn);
    $hasProfileNames = auth_users_has_profile_names($conn);
    $hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

    /** @var list<array{0:string,1:string,2:string,3:string}> */
    $roster = [
        ['ABC-2026-0301', 'Paulo', 'Emmanuel', 'Garcia'],
        ['ABC-2026-0302', 'Janelle', 'Marie', 'Flores'],
        ['ABC-2026-0303', 'Christian', 'Paolo', 'Navarro'],
        ['ABC-2026-0304', 'Samantha', 'Louise', 'Torres'],
        ['ABC-2026-0305', 'Vincent', 'Adrian', 'Bautista'],
        ['ABC-2026-0306', 'Patricia', 'Anne', 'Castillo'],
        ['ABC-2026-0307', 'John', 'Carlo', 'Herrera'],
        ['ABC-2026-0308', 'Nicole', 'Andrea', 'Fernandez'],
        ['ABC-2026-0309', 'Rafael', 'Dominic', 'Aquino'],
        ['ABC-2026-0310', 'Bea', 'Camille', 'Salazar'],
        ['ABC-2026-0311', 'Adrian', 'Miguel', 'Lim'],
        ['ABC-2026-0312', 'Mark', 'Anthony', 'Ramirez'],
        ['ABC-2026-0313', 'Mikaela', 'Joy', 'Gutierrez'],
        ['ABC-2026-0314', 'Kevin', 'Lawrence', 'Diaz'],
        ['ABC-2026-0315', 'Alyssa', 'Nicole', 'Rivera'],
        ['ABC-2026-0316', 'Francis', 'Xavier', 'Morales'],
        ['ABC-2026-0317', 'Katrina', 'Mae', 'Santiago'],
        ['ABC-2026-0318', 'Elijah', 'Matthew', 'Cruz'],
        ['ABC-2026-0319', 'Camille', 'Therese', 'Lopez'],
        ['ABC-2026-0320', 'Nathaniel', 'James', 'Romero'],
        ['ABC-2026-0321', 'Bianca', 'Sofia', 'Valdez'],
        ['ABC-2026-0322', 'Angelo', 'Marcus', 'Perez'],
        ['ABC-2026-0323', 'Chelsea', 'Anne', 'Velasco'],
        ['ABC-2026-0324', 'Carla', 'Denise', 'Mendoza'],
        ['ABC-2026-0325', 'Gabriel', 'Lorenzo', 'Chavez'],
        ['ABC-2026-0326', 'Danielle', 'Faith', 'Manalo'],
        ['ABC-2026-0327', 'Joshua', 'Daniel', 'Mercado'],
        ['ABC-2026-0328', 'Trisha', 'Mae', 'Evangelista'],
        ['ABC-2026-0329', 'Carl', 'Benedict', 'Ramos'],
        ['ABC-2026-0330', 'Princess', 'Mae', 'Cabrera'],
        ['ABC-2026-0331', 'Ivan', 'Cedrick', 'Dominguez'],
        ['ABC-2026-0332', 'Elaine', 'Patricia', 'Soriano'],
        ['ABC-2026-0333', 'Kurt', 'Raphael', 'Mendoza'],
        ['ABC-2026-0334', 'Hazel', 'Marie', 'Alonzo'],
        ['ABC-2026-0335', 'Nathan', 'Kyle', 'Pascual'],
    ];

    $inserted = 0;
    foreach ($roster as [$companyId, $first, $middle, $last]) {
        $email = strtolower(str_replace('-', '', $companyId)) . '@roster.local';

        $user = db_fetch_one($conn, 'SELECT Company_ID FROM users WHERE Company_ID = ? LIMIT 1', 's', [$companyId]);
        if ($user === null) {
            if ($hasProfileNames) {
                db_execute(
                    $conn,
                    "INSERT INTO users (Company_ID, Email, First_Name, Last_Name, password_hash, {$roleCol}, is_active, password_changed_at)
                     VALUES (?, ?, ?, ?, ?, ?, 0, NOW())",
                    'sssssi',
                    [$companyId, $email, $first, $last, $hash, AUTH_ROLE_ADMIN]
                );
            } else {
                db_execute(
                    $conn,
                    "INSERT INTO users (Company_ID, Email, password_hash, {$roleCol}, is_active, password_changed_at)
                     VALUES (?, ?, ?, ?, 0, NOW())",
                    'sssi',
                    [$companyId, $email, $hash, AUTH_ROLE_ADMIN]
                );
            }
        }

        $guard = db_fetch_one($conn, 'SELECT Company_ID FROM guards WHERE Company_ID = ? LIMIT 1', 's', [$companyId]);
        if ($guard === null) {
            db_execute(
                $conn,
                'INSERT INTO guards (Company_ID, Head_ID, Rank, Last_Name, First_Name, Middle_Name, Post_Assigned)
                 VALUES (?, NULL, ?, ?, ?, ?, NULL)',
                'sssss',
                [$companyId, 'Field', $last, $first, $middle]
            );
            ++$inserted;
        }
    }

    echo "  [ok] Seeded {$inserted} field guard(s) with roster user accounts.\n";
};
