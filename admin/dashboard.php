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
$memo_guards_query = $conn->query('SELECT Company_ID, First_Name, Last_Name FROM guards ORDER BY Last_Name ASC');

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
            <p class="page-subtitle">Monitor field personnel, daily guard reports, and pending review items. Compose secured internal communications from this workspace.</p>
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

            <section class="panel panel--compose" aria-labelledby="compose-heading">
                <div class="panel-head">
                    <h2 id="compose-heading" class="panel-title">
                        <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                        Internal communications
                    </h2>
                </div>
                <div class="panel-body">
                    <form action="send-memo.php" method="POST" id="memoForm" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="distribution_type" id="distTypeValue" value="">

                        <span class="form-section-label">Delivery scope<span class="required-mark">*</span></span>
                        <div class="delivery-options" role="group" aria-label="Delivery scope">
                            <button type="button" class="delivery-btn" id="btnBroadcast" data-protocol="broadcast"<?= ui_tooltip('Send to all personnel on roster') ?>>
                                <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                                <span class="delivery-btn-title">Company-wide</span>
                                <span class="delivery-btn-desc">Send to all personnel on roster</span>
                            </button>
                            <button type="button" class="delivery-btn" id="btnTargeted" data-protocol="targeted"<?= ui_tooltip('Send to one selected employee') ?>>
                                <i class="fa-solid fa-user-pen" aria-hidden="true"></i>
                                <span class="delivery-btn-title">Individual recipient</span>
                                <span class="delivery-btn-desc">Directed memo, including notice to explain</span>
                            </button>
                        </div>

                        <div id="memoDetailsContainer" class="form-details">
                            <div id="targetGuardContainer" class="recipient-block">
                                <div class="field">
                                    <label for="targetGuardInput" class="field-label field-label--alert">Select recipient<span class="required-mark">*</span></label>
                                    <select name="target_guard" id="targetGuardInput" class="field-select">
                                        <option value="" disabled selected>Choose an employee…</option>
                                        <?php
                                        if ($memo_guards_query && $memo_guards_query->num_rows > 0) {
                                            $memo_guards_query->data_seek(0);
                                            while ($row = $memo_guards_query->fetch_assoc()) {
                                                $label = htmlspecialchars((string) $row['Last_Name'])
                                                    . ', ' . htmlspecialchars((string) $row['First_Name'])
                                                    . ' (ID: ' . htmlspecialchars((string) $row['Company_ID']) . ')';
                                                echo '<option value="' . htmlspecialchars((string) $row['Company_ID']) . '">' . $label . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="deadlineDate" class="field-label field-label--alert">Response due date</label>
                                    <input type="date" name="deadline_date" id="deadlineDate" class="field-input">
                                    <p class="field-hint">Optional — required for compliance-related notices</p>
                                </div>
                            </div>

                            <div class="field">
                                <label for="memoTypeInput" class="field-label">Message category<span class="required-mark">*</span></label>
                                <select name="memo_type" id="memoTypeInput" class="field-select" required>
                                    <option value="" disabled selected>Select a category…</option>
                                    <option value="DIRECTIVE">Policy directive — rules and procedure updates</option>
                                    <option value="NOTICE">General notice — informational updates</option>
                                    <option value="NTE">Notice to explain — formal compliance request</option>
                                    <option value="BOLO">Security advisory — threat or watch notice</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="memoContentInput" class="field-label">Memo body<span class="required-mark">*</span></label>
                                <textarea name="content" id="memoContentInput" class="field-textarea" rows="8" placeholder="Enter the official memo text. Content is encrypted (AES-256) before storage."></textarea>
                            </div>

                            <button type="submit" name="generate_memo" class="btn-primary"<?= ui_tooltip('Encrypt and publish secured memo') ?>>
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                Publish secured memo
                            </button>

                            <p class="security-note">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                <span>All memo content is encrypted at rest. Distribution is logged for audit compliance.</span>
                            </p>
                        </div>
                    </form>
                </div>
            </section>
        </div>
        </main>
    </div>

    <script>
        function setProtocol(type) {
            const distTypeInput = document.getElementById('distTypeValue');
            const detailsContainer = document.getElementById('memoDetailsContainer');
            const targetContainer = document.getElementById('targetGuardContainer');
            const targetInput = document.getElementById('targetGuardInput');
            const btnBroadcast = document.getElementById('btnBroadcast');
            const btnTargeted = document.getElementById('btnTargeted');

            distTypeInput.value = type;
            detailsContainer.classList.add('is-visible');

            if (type === 'broadcast') {
                btnBroadcast.classList.add('active');
                btnTargeted.classList.remove('active');
                targetContainer.classList.remove('is-visible');
                targetInput.value = '';
            } else if (type === 'targeted') {
                btnTargeted.classList.add('active');
                btnBroadcast.classList.remove('active');
                targetContainer.classList.add('is-visible');
            }
        }

        document.getElementById('btnBroadcast').addEventListener('click', () => setProtocol('broadcast'));
        document.getElementById('btnTargeted').addEventListener('click', () => setProtocol('targeted'));

        document.getElementById('memoForm').addEventListener('submit', function (event) {
            const errors = [];
            const distType = document.getElementById('distTypeValue').value;
            const memoType = document.getElementById('memoTypeInput').value;
            const content = document.getElementById('memoContentInput').value.trim();

            if (!distType) {
                errors.push('Delivery scope (company-wide or individual)');
            } else if (distType === 'targeted' && !document.getElementById('targetGuardInput').value) {
                errors.push('Recipient employee');
            }

            if (!memoType) errors.push('Message category');
            if (content === '') errors.push('Memo body');

            if (errors.length > 0) {
                event.preventDefault();
                alert('Please complete the required fields before publishing:\n\n• ' + errors.join('\n• '));
            }
        });

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
