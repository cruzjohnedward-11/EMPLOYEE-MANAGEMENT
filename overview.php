<?php
require_once __DIR__ . '/auth.php';
ems_require_authentication();
require_once __DIR__ . '/preview-data.php';
$previewData = ems_preview_data();
$previewOpenRecords = array_slice(array_values(array_filter($previewData['hrRecords'], static fn (array $record): bool => in_array($record['status'], ['Open', 'In Progress'], true))), 0, 3);
$previewCompletion = 0;
if ($previewData['onboardingPeople']) {
    $previewDone = 0;
    $previewTotal = 0;
    foreach ($previewData['onboardingPeople'] as $previewPerson) {
        foreach ($previewPerson['groups'] as $previewTasks) {
            foreach ($previewTasks as $previewTask) {
                $previewTotal++;
                $previewDone += $previewTask[1] ? 1 : 0;
            }
        }
    }
    $previewCompletion = $previewTotal ? (int) round($previewDone / $previewTotal * 100) : 0;
}

$actions = [
    ['icon' => 'percent', 'title' => 'Run an evaluation', 'detail' => 'Score team members for the current quarter', 'href' => 'performance.php'],
    ['icon' => 'badge-check', 'title' => 'Review probation', 'detail' => 'Review employees in probation', 'href' => 'employees.php'],
    ['icon' => 'arrow-up-right', 'title' => 'Update job titles', 'detail' => 'Keep salary bands and levels current', 'href' => 'settings.php'],
];
$navItems = [
    ['title' => 'Overview', 'icon' => 'grid', 'href' => 'overview.php'],
    ['title' => 'Employees', 'icon' => 'users', 'href' => 'employees.php'],
    ['title' => 'Hiring', 'icon' => 'person-add', 'href' => 'hiring.php'],
    ['title' => 'Onboarding', 'icon' => 'sparkles', 'href' => 'onboarding.php'],
    ['title' => 'Performance', 'icon' => 'performance', 'href' => 'performance.php'],
    ['title' => 'HR Records', 'icon' => 'gavel', 'href' => 'hr-records.php'],
    ['title' => 'Leave', 'icon' => 'calendar', 'href' => 'leave.php'],
    ['title' => 'Settings', 'icon' => 'settings', 'href' => 'settings.php'],
    ['title' => 'Reports', 'icon' => 'chart', 'href' => 'reports.php'],
];

function dashboard_icon(string $name): string
{
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'person-add' => '<path d="M15 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6m3-3h-6"/>',
        'sparkles' => '<path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2L12 3Z"/><path d="m19 14 1.2 2.8L23 18l-2.8 1.2L19 22l-1.2-2.8L15 18l2.8-1.2L19 14Z"/>',
        'performance' => '<path d="M12 3 14.3 4.2l2.6-.1 1.2 2.2 2.2 1.3-.5 2.6.5 2.6-2.2 1.3-1.2 2.2-2.6-.1L12 17l-2.3 1.2-2.6-.1-1.2-2.2-2.2-1.3.5-2.6-.5-2.6 2.2-1.3 1.2-2.2 2.6.1L12 3Z"/><path d="m9 10.5 2 2 4-4"/>',
        'gavel' => '<path d="m14 13 7-7-3-3-7 7M5 21l8-8M7 8l3-3 9 9-3 3z"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5h6a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1ZM8 11h8M8 15h8"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M12 15v3m-1.5-1.5h3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.7-1.4-2.4L7.3 15a8 8 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.7 1.4 2.4-1.4 1.1a8 8 0 0 1-.1 2Z"/>',
        'chart' => '<path d="M4 19V5m0 14h17"/><path d="m7 15 4-4 3 2 6-7"/>',
        'wallet' => '<rect x="3" y="5" width="18" height="15" rx="2"/><path d="M3 8h18M16 14h2"/><path d="M3 5V4a2 2 0 0 1 2-2h13"/>',
        'percent' => '<path d="m19 5-14 14"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'badge-check' => '<path d="m12 3 2.2 1.2 2.5-.1 1.2 2.2 2.1 1.4-.5 2.5.5 2.5-2.1 1.4-1.2 2.2-2.5-.1L12 18l-2.2-1.2-2.5.1-1.2-2.2L4 13.3l.5-2.5L4 8.3l2.1-1.4 1.2-2.2 2.5.1L12 3Z"/><path d="m9 10.5 2 2 4-4"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M7 7h10v10"/>',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>',
    ];
    $content = $paths[$name] ?? '';
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $content . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview - EMS</title>
    <script>
        try {
            const savedTheme = localStorage.getItem('ems-theme');
            if (savedTheme === 'dark-sidebar') document.documentElement.dataset.theme = savedTheme;
        } catch (error) {
            console.warn('Unable to restore the saved EMS theme preference.', error);
        }
    </script>
    <link rel="stylesheet" href="dashboard.css?v=<?= (int) filemtime(__DIR__ . '/dashboard.css') ?>">
</head>
<body class="module-dashboard">
    <aside class="sidebar">
        <a class="logo" href="overview.php" aria-label="EMS People Operations home">
            <span class="logo-icon"><?= dashboard_icon('users') ?></span>
            <span><strong>EMS</strong><small>People Operations</small></span>
        </a>
        <div class="sidebar-category">Workspace</div>
        <nav class="sidebar-menu" aria-label="Workspace navigation">
            <?php foreach ($navItems as $index => $item): ?>
                <a class="nav-link<?= $index === 0 ? ' active' : '' ?>" href="<?= htmlspecialchars($item['href']) ?>"<?= $index === 0 ? ' aria-current="page"' : '' ?>>
                    <?= dashboard_icon($item['icon']) ?><span><?= htmlspecialchars($item['title']) ?></span>
                    <?php if (!empty($item['badge'])): ?><span class="badge"><?= (int) $item['badge'] ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <details class="account-menu">
                <summary class="account-trigger">
                    <span class="account-avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr((string) $_SESSION['username'], 0, 2)), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="account-name"><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="account-caret" aria-hidden="true"></span>
                </summary>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <button class="sign-out" type="submit"><?= dashboard_icon('logout') ?><span>Sign out</span></button>
                </form>
            </details>
        </div>
    </aside>

    <main class="main-content">
        <header class="header"><span class="header-section"><?= dashboard_icon('grid') ?> Overview</span><span class="company-period">Acme Inc. · FY2026</span></header>
        <div class="container dashboard-container">
            <section class="welcome-block" aria-labelledby="welcome-title">
                <span class="welcome-icon"><?= dashboard_icon('users') ?></span>
                <div><h1 id="welcome-title">Welcome</h1><p class="page-subtitle">Your people operations at a glance — pipeline, performance, records and time off.</p></div>
            </section>

            <section class="grid grid-4 dashboard-stats metrics-grid" aria-label="People operations summary" data-dashboard-metrics>
                <article class="card stat-card dashboard-stat">
                    <div><span class="card-header">Employees</span><strong class="stat-value" data-metric="headcount">—</strong><span class="stat-desc">Active and probationary</span></div>
                    <span class="stat-icon violet"><?= dashboard_icon('users') ?></span>
                </article>
                <article class="card stat-card dashboard-stat">
                    <div><span class="card-header">Active pipeline</span><strong class="stat-value" data-metric="active-pipeline">—</strong><span class="stat-desc">Applied through offer</span></div>
                    <span class="stat-icon cyan"><?= dashboard_icon('person-add') ?></span>
                </article>
                <article class="card stat-card dashboard-stat">
                    <div><span class="card-header">Attendance</span><strong class="stat-value" data-metric="attendance">—</strong><span class="stat-desc" data-metric="attendance-period">Latest recorded month</span></div>
                    <span class="stat-icon amber"><?= dashboard_icon('calendar') ?></span>
                </article>
                <article class="card stat-card dashboard-stat card-featured">
                    <div><span class="card-header">Current base payroll</span><strong class="stat-value" data-metric="payroll">—</strong><span class="stat-desc">From current compensation records</span></div>
                    <span class="stat-icon mint"><?= dashboard_icon('wallet') ?></span>
                </article>
                <p class="sr-only" data-dashboard-metrics-feedback role="status" aria-live="polite"></p>
            </section>

            <section class="grid grid-2 dashboard-charts" aria-label="Workforce charts">
                <article class="card chart-card performance-card">
                    <div class="section-title"><div><h2>Attendance trend</h2><p data-attendance-limit-label>Monthly average across employees with recorded attendance</p></div></div>
                    <div class="department-bars" data-attendance-trend><p class="empty-state">Loading attendance data</p></div>
                </article>
                <article class="card chart-card department-card">
                    <div class="section-title"><div><h2>Headcount by department</h2><p data-metric="department-total">Loading workforce data</p><small data-department-limit-label></small></div></div>
                    <div class="department-bars" data-department-bars></div>
                </article>
            </section>

            <section class="grid grid-3 dashboard-panels" aria-label="Items requiring attention">
                <article class="card dashboard-panel">
                    <div class="panel-heading"><h2><span class="panel-symbol amber"><?= dashboard_icon('calendar') ?></span>Leave awaiting approval</h2><a href="leave.php">View all</a></div>
                    <div class="person-list">
                        <p class="empty-state">Leave requests are not stored in the current database schema.</p>
                    </div>
                </article>
                <article class="card dashboard-panel">
                    <div class="panel-heading"><h2><span class="panel-symbol indigo"><?= dashboard_icon('person-add') ?></span>Hiring pipeline</h2><a href="hiring.php">View all</a></div>
                            <div class="pipeline-list" data-pipeline-stages><p class="empty-state">Loading pipeline data</p></div>
                    <p class="panel-note" data-metric="pipeline-total"></p>
                </article>
                <article class="card dashboard-panel resolution-panel">
                    <div class="panel-heading"><h2><span class="panel-symbol rose"><?= dashboard_icon('gavel') ?></span>Needs resolution</h2><a href="hr-records.php">View all</a></div>
                    <div class="record-list">
                        <?php foreach ($previewOpenRecords as $previewRecord): ?><div class="record-row"><span class="avatar <?= ems_h($previewRecord['tone']) ?>"><?= ems_h($previewRecord['initials']) ?></span><span><strong><?= ems_h($previewRecord['title']) ?></strong><small><?= ems_h($previewRecord['employee']) ?></small></span><span class="pill <?= $previewRecord['status'] === 'Open' ? 'rose' : 'amber' ?>"><?= ems_h($previewRecord['status']) ?></span></div><?php endforeach; ?>
                        <p class="panel-note">Preview data. Not connected to the database.</p>
                    </div>
                    <div class="onboarding-progress"><div><span class="progress-label"><?= dashboard_icon('sparkles') ?>Onboarding completion</span><strong><?= $previewCompletion ?>%</strong></div><div class="progress"><span style="width: <?= $previewCompletion ?>%"></span></div></div>
                </article>
            </section>

            <section class="grid grid-3 dashboard-actions" aria-label="Quick actions">
                <?php foreach ($actions as $action): ?>
                    <a class="card quick-action" href="<?= htmlspecialchars($action['href']) ?>"><span class="action-icon"><?= dashboard_icon($action['icon']) ?></span><span class="action-copy"><strong><?= htmlspecialchars($action['title']) ?></strong><small><?= htmlspecialchars($action['detail']) ?></small></span><span class="action-arrow"><?= dashboard_icon('arrow-up-right') ?></span></a>
                <?php endforeach; ?>
            </section>
        </div>
    </main>
    <script src="dashboard.js?v=<?= (int) filemtime(__DIR__ . '/dashboard.js') ?>" defer></script>
</body>
</html>