<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.dashboard.view');

$company_id = (string) $_SESSION['company_id'];

// --- Operations metrics (live) ---

$guard_count_query = $conn->query('SELECT COUNT(*) AS total FROM guards');
$total_guards = $guard_count_query ? (int) $guard_count_query->fetch_assoc()['total'] : 0;

$reports_today_query = $conn->query('SELECT COUNT(*) AS total FROM dgd WHERE DATE(Time_of_Report) = CURDATE()');
$total_today = $reports_today_query ? (int) $reports_today_query->fetch_assoc()['total'] : 0;

$pending_query = $conn->query("SELECT COUNT(*) AS total FROM dgd WHERE Status = 'Pending'");
$total_pending = $pending_query ? (int) $pending_query->fetch_assoc()['total'] : 0;

$reports_week_query = $conn->query('SELECT COUNT(*) AS total FROM dgd WHERE YEARWEEK(Time_of_Report, 1) = YEARWEEK(CURDATE(), 1)');
$total_weekly = $reports_week_query ? (int) $reports_week_query->fetch_assoc()['total'] : 0;

$roster_query = $conn->query('SELECT Company_ID, First_Name, Last_Name, Post_Assigned FROM guards ORDER BY Last_Name ASC LIMIT 10');

$adminNavActive = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Operations Dashboard</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
    </style>
</head>
<body class="light-mode">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Operations Dashboard</h1>
            <p class="page-subtitle">Monitor field personnel, daily guard reports, and pending review items.</p>
        </header>

        <section class="kpi-grid" aria-label="Key performance indicators">
            <article class="kpi-card kpi-card--personnel">
                <div class="kpi-stat">
                    <i class="fa-solid fa-user-shield kpi-icon" aria-hidden="true"></i>
                    <span class="kpi-value"><?= $total_guards ?></span>
                </div>
                <p class="kpi-label">Personnel on roster</p>
            </article>
            <article class="kpi-card kpi-card--reports">
                <div class="kpi-stat">
                    <i class="fa-solid fa-file-lines kpi-icon" aria-hidden="true"></i>
                    <span class="kpi-value"><?= $total_today ?></span>
                </div>
                <p class="kpi-label">Daily guard reports</p>
            </article>
            <article class="kpi-card kpi-card--pending">
                <div class="kpi-stat">
                    <i class="fa-solid fa-clock kpi-icon" aria-hidden="true"></i>
                    <span class="kpi-value"><?= $total_pending ?></span>
                </div>
                <p class="kpi-label">Awaiting review</p>
            </article>
        </section>

        <div class="content-grid">
            <div class="analytics-col">
                <section class="panel" aria-labelledby="chart-heading">
                    <div class="panel-head">
                        <h2 id="chart-heading" class="panel-title">
                            <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                            Report activity overview
                        </h2>
                        <span class="panel-badge">This week</span>
                    </div>
                    <div class="panel-body panel-body--chart">
                        <div class="chart-wrap">
                            <div class="chart-visual">
                                <div class="chart-ring">
                                    <canvas id="reportsChart" role="img" aria-label="Doughnut chart of report statuses"></canvas>
                                    <div class="chart-center" aria-hidden="true">
                                        <span class="chart-center__value"><?= (int) $total_weekly ?></span>
                                        <span class="chart-center__label">This week</span>
                                    </div>
                                </div>
                            </div>
                            <ul class="chart-legend" id="chartLegend" aria-label="Report breakdown">
                                <li class="chart-legend__item" data-segment="0">
                                    <span class="chart-legend__swatch" aria-hidden="true"></span>
                                    <span class="chart-legend__text">
                                        <span class="chart-legend__label">Awaiting review</span>
                                        <span class="chart-legend__value"><?= (int) $total_pending ?></span>
                                    </span>
                                </li>
                                <li class="chart-legend__item" data-segment="1">
                                    <span class="chart-legend__swatch" aria-hidden="true"></span>
                                    <span class="chart-legend__text">
                                        <span class="chart-legend__label">Received today</span>
                                        <span class="chart-legend__value"><?= (int) $total_today ?></span>
                                    </span>
                                </li>
                                <li class="chart-legend__item" data-segment="2">
                                    <span class="chart-legend__swatch" aria-hidden="true"></span>
                                    <span class="chart-legend__text">
                                        <span class="chart-legend__label">Total this week</span>
                                        <span class="chart-legend__value"><?= (int) $total_weekly ?></span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="panel" aria-labelledby="roster-heading">
                    <div class="panel-head">
                        <h2 id="roster-heading" class="panel-title">
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                            Security personnel roster
                        </h2>
                        <span class="panel-badge">Latest 10</span>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Employee ID</th>
                                        <th scope="col">Last name</th>
                                        <th scope="col">First name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($roster_query && $roster_query->num_rows > 0) {
                                        while ($guard = $roster_query->fetch_assoc()) {
                                            echo '<tr>'
                                                . '<td>' . htmlspecialchars((string) $guard['Company_ID']) . '</td>'
                                                . '<td>' . htmlspecialchars((string) $guard['Last_Name']) . '</td>'
                                                . '<td>' . htmlspecialchars((string) $guard['First_Name']) . '</td>'
                                                . '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="table-empty">No personnel records found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

        </div>
</main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;
            const pendingCount = <?= $total_pending ?>;
            const todayCount = <?= $total_today ?>;
            const weeklyCount = <?= $total_weekly ?>;

            const chartColors = {
                light: {
                    pending: '#e85d04',
                    today: '#f5c400',
                    weekly: '#003049',
                    ring: 'rgba(0, 48, 73, 0.08)',
                    border: '#ffffff'
                },
                dark: {
                    pending: '#ff8c42',
                    today: '#ffdd00',
                    weekly: '#4da3c7',
                    ring: 'rgba(255, 255, 255, 0.06)',
                    border: '#002534'
                }
            };

            const segmentLabels = ['Awaiting review', 'Received today', 'Total this week'];
            const segmentValues = [pendingCount, todayCount, weeklyCount];
            const chartCanvas = document.getElementById('reportsChart');
            const chartLegend = document.getElementById('chartLegend');
            const ctx = chartCanvas.getContext('2d');
            let dssChart;

            function getPalette() {
                return body.classList.contains('light-mode') ? chartColors.light : chartColors.dark;
            }

            function syncLegendSwatches(p) {
                const colors = [p.pending, p.today, p.weekly];
                chartLegend.querySelectorAll('.chart-legend__item').forEach((item, i) => {
                    const swatch = item.querySelector('.chart-legend__swatch');
                    if (swatch) {
                        swatch.style.backgroundColor = colors[i];
                    }
                });
            }

            function initChart() {
                const p = getPalette();
                const total = segmentValues.reduce((a, b) => a + b, 0);
                const isEmpty = total === 0;

                if (dssChart) {
                    dssChart.destroy();
                }

                syncLegendSwatches(p);
                chartCanvas.closest('.chart-wrap').classList.toggle('chart-wrap--empty', isEmpty);

                dssChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: isEmpty ? ['No activity'] : segmentLabels,
                        datasets: [{
                            data: isEmpty ? [1] : segmentValues,
                            backgroundColor: isEmpty
                                ? [p.ring]
                                : [p.pending, p.today, p.weekly],
                            borderColor: p.border,
                            borderWidth: isEmpty ? 0 : 2,
                            borderRadius: isEmpty ? 0 : 6,
                            spacing: isEmpty ? 0 : 3,
                            hoverOffset: isEmpty ? 0 : 10,
                            hoverBorderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 1,
                        layout: { padding: 4 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: !isEmpty,
                                backgroundColor: 'rgba(0, 48, 73, 0.96)',
                                titleColor: '#fff',
                                bodyColor: 'rgba(255, 255, 255, 0.92)',
                                titleFont: { family: "'Inter', sans-serif", size: 12, weight: '600' },
                                bodyFont: { family: "'Inter', sans-serif", size: 13, weight: '500' },
                                padding: { top: 10, right: 14, bottom: 10, left: 14 },
                                cornerRadius: 10,
                                displayColors: true,
                                boxPadding: 6,
                                callbacks: {
                                    label: (c) => ' ' + c.parsed + ' report' + (c.parsed === 1 ? '' : 's')
                                }
                            }
                        },
                        cutout: '72%',
                        animation: {
                            animateRotate: true,
                            duration: 700,
                            easing: 'easeOutQuart'
                        },
                        onHover: (evt, elements) => {
                            chartCanvas.style.cursor = elements.length && !isEmpty ? 'pointer' : 'default';
                        }
                    }
                });

                chartLegend.querySelectorAll('.chart-legend__item').forEach((item) => {
                    item.classList.toggle('chart-legend__item--muted', isEmpty);
                });
            }

            chartLegend.querySelectorAll('.chart-legend__item').forEach((item) => {
                item.addEventListener('mouseenter', () => {
                    const idx = Number(item.dataset.segment);
                    const arc = dssChart?.getDatasetMeta(0)?.data[idx];
                    if (!arc || chartCanvas.closest('.chart-wrap').classList.contains('chart-wrap--empty')) {
                        return;
                    }
                    dssChart.setActiveElements([{ datasetIndex: 0, index: idx }]);
                    dssChart.tooltip.setActiveElements([{ datasetIndex: 0, index: idx }], { x: 0, y: 0 });
                    dssChart.update('none');
                });
                item.addEventListener('mouseleave', () => {
                    if (!dssChart) {
                        return;
                    }
                    dssChart.setActiveElements([]);
                    dssChart.tooltip.setActiveElements([]);
                    dssChart.update('none');
                });
            });

            initChart();

            document.querySelectorAll('.theme-switch').forEach((toggle) => {
                toggle.addEventListener('click', () => setTimeout(initChart, 50));
            });
        });
    </script>
<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
