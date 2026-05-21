<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.duty.view');

$roster_query = $conn->query(
    'SELECT Company_ID, Head_ID, Rank, Last_Name, First_Name, Middle_Name, Post_Assigned
     FROM guards
     ORDER BY Last_Name ASC, First_Name ASC'
);

$adminNavActive = 'duty';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Duty detail</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
    </style>
</head>
<body class="light-mode">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Duty detail</h1>
            <p class="page-subtitle">Field personnel roster with assigned posts, rank, and supervising head.</p>
        </header>

        <section class="panel" aria-labelledby="duty-roster-heading">
            <div class="panel-head">
                <h2 id="duty-roster-heading" class="panel-title">
                    <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                    Security personnel &amp; duty posts
                </h2>
                <span class="panel-badge">Full roster</span>
            </div>
            <div class="panel-body" style="padding: 0;">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Employee ID</th>
                                <th scope="col">Last name</th>
                                <th scope="col">First name</th>
                                <th scope="col">Middle name</th>
                                <th scope="col">Rank</th>
                                <th scope="col">Post assigned</th>
                                <th scope="col">Head ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($roster_query) {
                                while ($guard = $roster_query->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                            <tr>
                                <td><?= e((string) $guard['Company_ID']) ?></td>
                                <td><?= e((string) $guard['Last_Name']) ?></td>
                                <td><?= e((string) $guard['First_Name']) ?></td>
                                <td><?= e((string) ($guard['Middle_Name'] ?? '')) ?></td>
                                <td><?= e((string) ($guard['Rank'] ?? '')) ?></td>
                                <td><?= e((string) ($guard['Post_Assigned'] ?? '')) ?></td>
                                <td><?= e((string) ($guard['Head_ID'] ?? '')) ?></td>
                            </tr>
                                    <?php
                                }
                            } else {
                                ?>
                            <tr>
                                <td colspan="7" class="table-empty">No personnel records found.</td>
                            </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
