<?php
$stats = [
    ['label' => 'Active employees', 'value' => '9', 'note' => '1 on leave', 'icon' => 'users', 'tone' => 'violet'],
    ['label' => 'Avg. time-to-hire', 'value' => '47d', 'note' => '3 hires tracked', 'icon' => 'person-add', 'tone' => 'cyan'],
    ['label' => 'Open HR records', 'value' => '2', 'note' => 'warnings, memos, violations', 'icon' => 'gavel', 'tone' => 'rose'],
    ['label' => 'Annual payroll', 'value' => '&#8369;28.3M', 'note' => 'base salaries of active staff', 'icon' => 'wallet', 'tone' => 'mint'],
];
$departments = [
    ['name' => 'Engineering', 'count' => 4],
    ['name' => 'Human Resources', 'count' => 1],
    ['name' => 'Executive', 'count' => 1],
    ['name' => 'Design', 'count' => 1],
    ['name' => 'Sales', 'count' => 1],
    ['name' => 'Marketing', 'count' => 1],
];
$leaveRequests = [
    ['initials' => 'YT', 'name' => 'Yuki Tanaka', 'detail' => '5 days · from Oct 5', 'type' => 'Vacation', 'tone' => 'rose'],
    ['initials' => 'MO', 'name' => 'Maya Okafor', 'detail' => '1 day · from Sep 30', 'type' => 'Personal', 'tone' => 'violet'],
];
$pipeline = [
    ['name' => 'Applied', 'count' => 0, 'tone' => 'slate'],
    ['name' => 'Screening', 'count' => 1, 'tone' => 'violet'],
    ['name' => 'Interviewing', 'count' => 1, 'tone' => 'indigo'],
    ['name' => 'Offer', 'count' => 1, 'tone' => 'mint'],
];
$records = [
    ['initials' => 'LF', 'title' => 'Reminder: equipment return', 'person' => 'Liam Fitzgerald', 'status' => 'Open', 'tone' => 'violet'],
    ['initials' => 'PN', 'title' => 'Missed CRM login target', 'person' => 'Priya Nair', 'status' => 'In Progress', 'tone' => 'amber'],
];
$actions = [
    ['icon' => 'percent', 'title' => 'Run an evaluation', 'detail' => 'Score team members for the current quarter', 'href' => 'performance.php'],
    ['icon' => 'badge-check', 'title' => 'Review probation', 'detail' => '1 employee in probation', 'href' => 'employees.php'],
    ['icon' => 'arrow-up-right', 'title' => 'Update job titles', 'detail' => 'Keep salary bands and levels current', 'href' => 'settings.php'],
];

function dashboard_icon(string $name): string
{
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'person-add' => '<path d="M15 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6m3-3h-6"/>',
        'sparkles' => '<path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2L12 3Z"/><path d="m19 14 1.2 2.8L23 18l-2.8 1.2L19 22l-1.2-2.8L15 18l2.8-1.2L19 14Z"/>',
        'gavel' => '<path d="m14 13 7-7-3-3-7 7M5 21l8-8M7 8l3-3 9 9-3 3z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M12 15v3m-1.5-1.5h3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.7-1.4-2.4L7.3 15a8 8 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.7 1.4 2.4-1.4 1.1a8 8 0 0 1-.1 2Z"/>',
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
    <link rel="stylesheet" href="styles.css">
</head>
<body class="overview-dashboard">
    <aside class="sidebar">
        <a class="logo" href="overview.php" aria-label="EMS People Operations home">
            <span class="logo-icon"><?= dashboard_icon('users') ?></span>
            <span><strong>EMS</strong><small>People Operations</small></span>
        </a>
        <div class="sidebar-category">Workspace</div>
        <nav class="sidebar-menu" aria-label="Workspace navigation">
            <a class="nav-link active" href="overview.php" aria-current="page"><?= dashboard_icon('grid') ?><span>Overview</span></a>
            <a class="nav-link" href="employees.php"><?= dashboard_icon('users') ?><span>Employees</span></a>
            <a class="nav-link" href="hiring.php"><?= dashboard_icon('person-add') ?><span>Hiring</span><span class="badge">2</span></a>
            <a class="nav-link" href="onboarding.php"><?= dashboard_icon('sparkles') ?><span>Onboarding</span></a>
            <a class="nav-link" href="performance.php"><?= dashboard_icon('badge-check') ?><span>Performance</span></a>
            <a class="nav-link" href="hr-records.php"><?= dashboard_icon('gavel') ?><span>HR Records</span></a>
            <a class="nav-link" href="leave.php"><?= dashboard_icon('calendar') ?><span>Leave</span><span class="badge">2</span></a>
            <a class="nav-link" href="settings.php"><?= dashboard_icon('settings') ?><span>Settings</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-profile"><strong>Signed in</strong><span>workspace member</span></div>
            <a class="sign-out" href="index.html"><?= dashboard_icon('logout') ?><span>Sign out</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header"><span class="header-section"><?= dashboard_icon('grid') ?> Overview</span><span class="company-period">Acme Inc. · FY2026</span></header>
        <div class="container dashboard-container">
            <section class="welcome-block" aria-labelledby="welcome-title">
                <span class="welcome-icon"><?= dashboard_icon('users') ?></span>
                <div><h1 id="welcome-title">Welcome</h1><p class="page-subtitle">Your people operations at a glance — pipeline, performance, records and time off.</p></div>
            </section>

            <section class="grid grid-4 dashboard-stats" aria-label="People operations summary">
                <?php foreach ($stats as $stat): ?>
                    <article class="card stat-card dashboard-stat">
                        <div><span class="card-header"><?= htmlspecialchars($stat['label']) ?></span><strong class="stat-value"><?= $stat['value'] ?></strong><span class="stat-desc"><?= htmlspecialchars($stat['note']) ?></span></div>
                        <span class="stat-icon <?= htmlspecialchars($stat['tone']) ?>"><?= dashboard_icon($stat['icon']) ?></span>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="grid grid-2 dashboard-charts" aria-label="Workforce charts">
                <article class="card chart-card performance-card">
                    <div class="section-title"><div><h2>Performance trend</h2><p>Average evaluation score by period</p></div></div>
                    <div class="performance-chart" role="img" aria-label="Average performance score increased from 90 in 2026 Q1 to 93 in 2026 Q2">
                        <div class="chart-y-labels"><span>100</span><span>75</span><span>50</span><span>25</span><span>0</span></div>
                        <div class="chart-plot">
                            <div class="chart-gridlines"><i></i><i></i><i></i><i></i><i></i></div>
                            <svg viewBox="0 0 600 200" preserveAspectRatio="none" aria-hidden="true">
                                <path class="chart-area" d="M0 20 L600 14 L600 200 L0 200 Z"></path>
                                <path class="chart-stroke" d="M0 20 L600 14"></path>
                            </svg>
                            <div class="chart-x-labels"><span>2026 Q1</span><span>2026 Q2</span></div>
                        </div>
                    </div>
                </article>
                <article class="card chart-card department-card">
                    <div class="section-title"><div><h2>Headcount by department</h2><p>9 active employees</p></div></div>
                    <div class="department-bars">
                        <?php foreach ($departments as $department): ?>
                            <div class="department-row"><span><?= htmlspecialchars($department['name']) ?></span><div class="bar-track"><i style="width: <?= (int) ($department['count'] / 4 * 100) ?>%"></i></div><b><?= (int) $department['count'] ?></b></div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>

            <section class="grid grid-3 dashboard-panels" aria-label="Items requiring attention">
                <article class="card dashboard-panel">
                    <div class="panel-heading"><h2><span class="panel-symbol amber"><?= dashboard_icon('calendar') ?></span>Leave awaiting approval</h2><a href="leave.php">View all</a></div>
                    <div class="person-list">
                        <?php foreach ($leaveRequests as $request): ?>
                            <div class="person-row"><span class="avatar <?= htmlspecialchars($request['tone']) ?>"><?= htmlspecialchars($request['initials']) ?></span><span class="person-copy"><strong><?= htmlspecialchars($request['name']) ?></strong><small><?= htmlspecialchars($request['detail']) ?></small></span><span class="pill <?= htmlspecialchars($request['tone']) ?>"><?= htmlspecialchars($request['type']) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                </article>
                <article class="card dashboard-panel">
                    <div class="panel-heading"><h2><span class="panel-symbol indigo"><?= dashboard_icon('person-add') ?></span>Hiring pipeline</h2><a href="hiring.php">View all</a></div>
                    <div class="pipeline-list">
                        <?php foreach ($pipeline as $stage): ?>
                            <div class="pipeline-stage"><div class="pipeline-label"><span class="pill <?= htmlspecialchars($stage['tone']) ?>"><?= htmlspecialchars($stage['name']) ?></span><span><?= (int) $stage['count'] ?></span></div><div class="pipeline-track"><i class="<?= htmlspecialchars($stage['tone']) ?>" style="width: <?= $stage['count'] ? '100' : '0' ?>%"></i></div></div>
                        <?php endforeach; ?>
                    </div>
                    <p class="panel-note">Avg. time-to-hire: 47 days</p>
                </article>
                <article class="card dashboard-panel resolution-panel">
                    <div class="panel-heading"><h2><span class="panel-symbol rose"><?= dashboard_icon('gavel') ?></span>Needs resolution</h2><a href="hr-records.php">View all</a></div>
                    <div class="record-list">
                        <?php foreach ($records as $record): ?>
                            <div class="record-row"><span class="avatar <?= htmlspecialchars($record['tone']) ?>"><?= htmlspecialchars($record['initials']) ?></span><span class="person-copy"><strong><?= htmlspecialchars($record['title']) ?></strong><small><?= htmlspecialchars($record['person']) ?></small></span><span class="pill <?= htmlspecialchars($record['tone']) ?>"><?= htmlspecialchars($record['status']) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="onboarding-progress"><div><span class="progress-label"><?= dashboard_icon('sparkles') ?>Onboarding completion</span><strong>81%</strong></div><div class="progress"><span style="width: 81%"></span></div></div>
                </article>
            </section>

            <section class="grid grid-3 dashboard-actions" aria-label="Quick actions">
                <?php foreach ($actions as $action): ?>
                    <a class="card quick-action" href="<?= htmlspecialchars($action['href']) ?>"><span class="action-icon"><?= dashboard_icon($action['icon']) ?></span><span class="action-copy"><strong><?= htmlspecialchars($action['title']) ?></strong><small><?= htmlspecialchars($action['detail']) ?></small></span><span class="action-arrow"><?= dashboard_icon('arrow-up-right') ?></span></a>
                <?php endforeach; ?>
            </section>
        </div>
    </main>
</body>
</html>
