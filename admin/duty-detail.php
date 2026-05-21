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
        <header class="page-header page-header--inline">
            <h1 class="page-title">Duty detail</h1>
            <p class="page-subtitle">Security personnel roster and post assignments for operations.</p>
        </header>

        <section class="panel panel--duty" aria-labelledby="duty-roster-heading">
            <header class="panel-head panel-head--registry">
                <div class="panel-head__head">
                    <div class="panel-head__intro">
                        <h2 id="duty-roster-heading" class="panel-title panel-title--registry">
                            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                            Duty detail
                        </h2>
                        <div class="panel-head__subrow">
                            <p class="panel-head__note">Field personnel roster with assigned posts, rank, and supervising head.</p>
                            <div class="panel-head__table-labels panel-head__table-labels--desktop" id="duty-table-labels">
                                <span>Employee ID</span>
                                <span>Last name</span>
                                <span>First name</span>
                                <span>Middle name</span>
                                <span>Rank</span>
                                <span>Post assigned</span>
                                <span>Head ID</span>
                            </div>
                        </div>
                    </div>
                    <span class="panel-badge">Full roster</span>
                </div>
            </header>
            <div class="panel-body" style="padding: 0;">
                <div class="table-wrap">
                    <table class="data-table" aria-describedby="duty-table-labels">
                        <thead class="data-table__head--compact">
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
                            if ($roster_query && $roster_query->num_rows > 0) {
                                while ($guard = $roster_query->fetch_assoc()) {
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
