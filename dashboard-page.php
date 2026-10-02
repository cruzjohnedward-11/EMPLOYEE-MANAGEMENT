<?php
require_once __DIR__ . '/dashboard-data.php';

$pages = [
    'employees' => ['title' => 'Employees', 'icon' => 'users', 'subtitle' => 'The full directory - profiles, roles, salaries and status.'],
    'hiring' => ['title' => 'Hiring', 'icon' => 'person-add', 'subtitle' => 'Applicant tracking with a live pipeline and automatic time-to-hire on every completed hire.'],
    'onboarding' => ['title' => 'Onboarding', 'icon' => 'sparkles', 'subtitle' => 'Orientation, requirements, training and company policies - every new hire gets a clear checklist.'],
    'performance' => ['title' => 'Performance', 'icon' => 'performance', 'subtitle' => 'Records employee performance, evaluations and achievements - from draft to signed acknowledgment.'],
    'hr-records' => ['title' => 'HR Records', 'icon' => 'gavel', 'subtitle' => 'Memos, warning notices, policy violations and formal commendations - with resolution tracking and signed acknowledgment.'],
    'leave' => ['title' => 'Leave Management', 'icon' => 'calendar', 'subtitle' => 'Time-off requests, approval workflow and balance tracking - approvals deduct days automatically.'],
    'settings' => ['title' => 'Settings', 'icon' => 'settings', 'subtitle' => 'Job titles, departments and base salary bands used across hiring, profiles and payroll views.'],
];

if (!isset($pageKey, $pages[$pageKey])) {
    http_response_code(404);
    exit('Dashboard not found.');
}
$page = $pages[$pageKey];
$navItems = [
    ['key' => 'overview', 'title' => 'Overview', 'icon' => 'grid', 'href' => 'overview.php'],
    ['key' => 'employees', 'title' => 'Employees', 'icon' => 'users', 'href' => 'employees.php'],
    ['key' => 'hiring', 'title' => 'Hiring', 'icon' => 'person-add', 'href' => 'hiring.php', 'badge' => 2],
    ['key' => 'onboarding', 'title' => 'Onboarding', 'icon' => 'sparkles', 'href' => 'onboarding.php'],
    ['key' => 'performance', 'title' => 'Performance', 'icon' => 'performance', 'href' => 'performance.php'],
    ['key' => 'hr-records', 'title' => 'HR Records', 'icon' => 'gavel', 'href' => 'hr-records.php'],
    ['key' => 'leave', 'title' => 'Leave', 'icon' => 'calendar', 'href' => 'leave.php', 'badge' => 2],
    ['key' => 'settings', 'title' => 'Settings', 'icon' => 'settings', 'href' => 'settings.php'],
];

$employeeSearch = trim((string) ($_GET['search'] ?? ''));
$departmentFilter = (string) ($_GET['department'] ?? '');
$statusFilter = (string) ($_GET['status'] ?? '');
$employeeRows = array_values(array_filter($employees, static function (array $employee) use ($employeeSearch, $departmentFilter, $statusFilter): bool {
    $matchesSearch = $employeeSearch === '' || stripos($employee['name'] . ' ' . $employee['email'], $employeeSearch) !== false;
    $matchesDepartment = $departmentFilter === '' || $employee['department'] === $departmentFilter;
    $matchesStatus = $statusFilter === '' || $employee['status'] === $statusFilter;
    return $matchesSearch && $matchesDepartment && $matchesStatus;
}));

$hiringSearch = trim((string) ($_GET['search'] ?? ''));
$hiringRows = array_values(array_filter($hiringHistory, static function (array $candidate) use ($hiringSearch): bool {
    return $hiringSearch === '' || stripos($candidate['name'] . ' ' . $candidate['email'] . ' ' . $candidate['role'], $hiringSearch) !== false;
}));

$recordType = (string) ($_GET['type'] ?? 'All');
$visibleRecords = array_values(array_filter($hrRecords, static function (array $record) use ($recordType): bool {
    return $recordType === 'All' || $record['type'] === $recordType;
}));
$recordTypes = ['All', 'Memos', 'Warnings', 'Violations', 'Commendations'];
$recordTypeMap = ['Memos' => 'Memo', 'Warnings' => 'Warning', 'Violations' => 'Violation', 'Commendations' => 'Commendation'];
$employeeOptions = array_column($employees, 'name');
$roleOptions = array_values(array_unique(array_column($jobTitles, 'title')));
$departmentOptions = array_values(array_unique(array_column($jobTitles, 'department')));
$dialogConfigs = [
    'employees' => ['title' => 'Add employee', 'description' => 'New hires start in probation by default.', 'submit' => 'Add employee', 'fields' => [
        ['label' => 'Full name', 'name' => 'full_name', 'type' => 'text', 'placeholder' => 'Enter full name', 'required' => true],
        ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'placeholder' => 'Enter email address', 'required' => true],
        ['label' => 'Job title', 'name' => 'job_title', 'type' => 'select', 'placeholder' => 'Choose a role', 'options' => $roleOptions, 'required' => true],
        ['label' => 'Employment type', 'name' => 'employment_type', 'type' => 'select', 'placeholder' => 'Choose employment type', 'options' => ['Full-time', 'Part-time', 'Contract'], 'required' => true],
        ['label' => 'Base salary (PHP/year)', 'name' => 'salary', 'type' => 'number', 'placeholder' => 'Enter annual base salary', 'required' => true],
        ['label' => 'Hire date', 'name' => 'hire_date', 'type' => 'date', 'required' => true],
        ['label' => 'Status', 'name' => 'status', 'type' => 'select', 'placeholder' => 'Choose status', 'options' => ['Probation', 'Active', 'On Leave'], 'required' => true],
        ['label' => 'Location', 'name' => 'location', 'type' => 'text', 'placeholder' => 'Enter location'],
        ['label' => 'Phone', 'name' => 'phone', 'type' => 'tel', 'placeholder' => 'Enter phone number'],
        ['label' => 'Emergency contact', 'name' => 'emergency_contact', 'type' => 'text', 'placeholder' => 'Enter contact name and phone'],
    ]],
    'hiring' => ['title' => 'Add candidate', 'description' => 'New candidates enter the pipeline as Applied.', 'submit' => 'Add to pipeline', 'fields' => [
        ['label' => 'Name', 'name' => 'name', 'type' => 'text', 'placeholder' => 'Enter full name', 'required' => true],
        ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'placeholder' => 'Enter email address', 'required' => true],
        ['label' => 'Role', 'name' => 'role', 'type' => 'select', 'placeholder' => 'Choose open role', 'options' => $roleOptions, 'required' => true],
        ['label' => 'Application date', 'name' => 'application_date', 'type' => 'date', 'required' => true],
        ['label' => 'Source', 'name' => 'source', 'type' => 'text', 'placeholder' => 'Enter candidate source', 'wide' => true],
        ['label' => 'Notes', 'name' => 'notes', 'type' => 'textarea', 'placeholder' => 'Enter screening notes or portfolio links', 'wide' => true],
    ]],
    'performance' => ['title' => 'New evaluation', 'description' => 'Score an employee for a review period and record key achievements.', 'submit' => 'Save evaluation', 'fields' => [
        ['label' => 'Employee', 'name' => 'employee', 'type' => 'select', 'placeholder' => 'Choose employee', 'options' => $employeeOptions, 'required' => true],
        ['label' => 'Period', 'name' => 'period', 'type' => 'text', 'placeholder' => 'Enter review period', 'required' => true],
        ['label' => 'Evaluator', 'name' => 'evaluator', 'type' => 'text', 'placeholder' => 'Enter evaluator name', 'required' => true],
        ['label' => 'Score (0-100)', 'name' => 'score', 'type' => 'number', 'placeholder' => 'Enter score', 'min' => 0, 'max' => 100, 'required' => true],
        ['label' => 'Key achievements', 'name' => 'achievements', 'type' => 'textarea', 'placeholder' => 'Enter key achievements', 'wide' => true],
        ['label' => 'Save as', 'name' => 'save_as', 'type' => 'select', 'placeholder' => 'Choose evaluation status', 'options' => ['Draft', 'Completed'], 'required' => true],
    ]],
    'hr-records' => ['title' => 'File HR record', 'description' => 'Add a memo, warning, violation or commendation to an employee record.', 'submit' => 'File record', 'fields' => [
        ['label' => 'Employee', 'name' => 'employee', 'type' => 'select', 'placeholder' => 'Choose employee', 'options' => $employeeOptions, 'required' => true],
        ['label' => 'Type', 'name' => 'type', 'type' => 'select', 'placeholder' => 'Choose record type', 'options' => ['Memo', 'Warning', 'Violation', 'Commendation'], 'required' => true],
        ['label' => 'Title', 'name' => 'title', 'type' => 'text', 'placeholder' => 'Enter a short summary', 'wide' => true, 'required' => true],
        ['label' => 'Details', 'name' => 'details', 'type' => 'textarea', 'placeholder' => 'Enter details and expected action', 'wide' => true, 'required' => true],
        ['label' => 'Date', 'name' => 'date', 'type' => 'date', 'required' => true],
        ['label' => 'Status', 'name' => 'status', 'type' => 'select', 'placeholder' => 'Choose status', 'options' => ['Open', 'In Progress', 'Resolved'], 'required' => true],
    ]],
    'leave' => ['title' => 'New leave request', 'description' => 'Choose an employee, leave type and dates for this request.', 'submit' => 'Submit request', 'fields' => [
        ['label' => 'Employee', 'name' => 'employee', 'type' => 'select', 'placeholder' => 'Choose employee', 'options' => $employeeOptions, 'required' => true],
        ['label' => 'Type', 'name' => 'type', 'type' => 'select', 'placeholder' => 'Choose leave type', 'options' => ['Vacation', 'Sick', 'Personal'], 'required' => true],
        ['label' => 'Start date', 'name' => 'start_date', 'type' => 'date', 'required' => true],
        ['label' => 'End date', 'name' => 'end_date', 'type' => 'date', 'required' => true],
        ['label' => 'Reason (optional)', 'name' => 'reason', 'type' => 'textarea', 'placeholder' => 'Enter a short reason', 'wide' => true],
    ]],
    'settings' => ['title' => 'New job title', 'description' => 'Add a job title and its department, level and base salary.', 'submit' => 'Create title', 'fields' => [
        ['label' => 'Title', 'name' => 'title', 'type' => 'text', 'placeholder' => 'Enter job title', 'required' => true],
        ['label' => 'Base salary (PHP/year)', 'name' => 'salary', 'type' => 'number', 'placeholder' => 'Enter annual base salary', 'required' => true],
        ['label' => 'Department', 'name' => 'department', 'type' => 'select', 'placeholder' => 'Choose department', 'options' => $departmentOptions, 'required' => true],
        ['label' => 'Level', 'name' => 'level', 'type' => 'select', 'placeholder' => 'Choose level', 'options' => ['Junior', 'Mid', 'Senior', 'Lead', 'Executive'], 'required' => true],
        ['label' => 'Description', 'name' => 'description', 'type' => 'textarea', 'placeholder' => 'Enter what this role owns', 'wide' => true],
    ]],
];
$dialogConfig = $dialogConfigs[$pageKey] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ems_h($page['title']) ?> - EMS</title>
    <link rel="stylesheet" href="styles.css">
        <link rel="stylesheet" href="dashboard.css">
</head>
<body class="module-dashboard">
    <aside class="sidebar">
        <a class="logo" href="overview.php" aria-label="EMS People Operations home">
            <span class="logo-icon"><?= ems_icon('users') ?></span>
            <span><strong>EMS</strong><small>People Operations</small></span>
        </a>
        <div class="sidebar-category">Workspace</div>
        <nav class="sidebar-menu" aria-label="Workspace navigation">
            <?php foreach ($navItems as $item): ?>
                <a class="nav-link<?= $item['key'] === $pageKey ? ' active' : '' ?>" href="<?= ems_h($item['href']) ?>"<?= $item['key'] === $pageKey ? ' aria-current="page"' : '' ?>>
                    <?= ems_icon($item['icon']) ?><span><?= ems_h($item['title']) ?></span>
                    <?php if (!empty($item['badge'])): ?><span class="badge"><?= (int) $item['badge'] ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-profile"><strong>Signed in</strong><span>workspace member</span></div>
            <a class="sign-out" href="overview.php"><?= ems_icon('logout') ?><span>Sign out</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <span class="header-section"><?= ems_icon($page['icon']) ?> <?= ems_h($page['title']) ?></span>
            <span class="company-period">Acme Inc. &middot; FY2026</span>
        </header>
        <div class="container module-container">
            <section class="module-intro">
                <span class="module-icon"><?= ems_icon($page['icon']) ?></span>
                <div><h1><?= ems_h($page['title'] === 'Performance' ? 'Performance · KPI' : $page['title']) ?></h1><p class="page-subtitle"><?= ems_h($page['subtitle']) ?></p></div>
                <?php if (in_array($pageKey, ['employees', 'hiring', 'onboarding', 'performance', 'hr-records', 'leave', 'settings'], true)): ?>
                    <button class="btn-primary" type="button" data-dialog-open="dashboard-form-dialog" aria-haspopup="dialog">
                        <span aria-hidden="true">+</span>
                        <?= ['employees' => 'Add employee', 'hiring' => 'Add candidate', 'onboarding' => 'New onboarding', 'performance' => 'New evaluation', 'hr-records' => 'File record', 'leave' => 'New request', 'settings' => 'New job title'][$pageKey] ?>
                    </button>
                <?php endif; ?>
            </section>

            <?php if ($pageKey === 'employees'): ?>
                <form class="filter-bar" method="get" action="employees.php">
                    <label class="search-control"><?= ems_icon('search') ?><input type="search" name="search" value="<?= ems_h($employeeSearch) ?>" placeholder="Search name or email..." aria-label="Search name or email"></label>
                    <label class="select-control"><span class="sr-only">Department</span><select name="department" onchange="this.form.submit()"><option value="">All departments</option><?php foreach (array_unique(array_column($employees, 'department')) as $department): ?><option value="<?= ems_h($department) ?>"<?= $departmentFilter === $department ? ' selected' : '' ?>><?= ems_h($department) ?></option><?php endforeach; ?></select></label>
                    <label class="select-control"><span class="sr-only">Status</span><select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach (['Active', 'Probation', 'On Leave'] as $status): ?><option value="<?= ems_h($status) ?>"<?= $statusFilter === $status ? ' selected' : '' ?>><?= ems_h($status) ?></option><?php endforeach; ?></select></label>
                    <button class="filter-submit" type="submit">Search</button>
                </form>
                <div class="card table-card employee-table-card">
                    <table class="data-table employee-table">
                        <thead><tr><th>Employee</th><th>Role</th><th>Status</th><th>Base salary</th><th>Hired</th><th><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody>
                            <?php foreach ($employeeRows as $employee): ?>
                                <tr>
                                    <td><span class="employee-cell"><span class="avatar <?= ems_h($employee['tone']) ?>"><?= ems_h($employee['initials']) ?></span><span><strong><?= ems_h($employee['name']) ?></strong><small><?= ems_h($employee['email']) ?></small></span></span></td>
                                    <td><?= ems_h($employee['role']) ?><small><?= ems_h($employee['department']) ?></small></td>
                                    <td><span class="pill <?= $employee['status'] === 'On Leave' ? 'blue' : ($employee['status'] === 'Probation' ? 'amber' : 'mint') ?>"><?= ems_h($employee['status']) ?></span></td>
                                    <td><?= ems_money($employee['salary']) ?></td><td><?= ems_h($employee['hired']) ?></td><td class="row-menu">&bull;&bull;&bull;</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$employeeRows): ?><tr><td colspan="6" class="empty-state">No employees match those filters.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="table-count">Showing <?= count($employeeRows) ?> of <?= count($employees) ?> employees</p>

            <?php elseif ($pageKey === 'hiring'): ?>
                <section class="metric-grid three-columns">
                    <article class="card metric-card"><span>Avg. time-to-hire</span><strong>52 days</strong><small>4 completed hires</small></article>
                    <article class="card metric-card"><span>Fastest hire</span><strong>42 days</strong><small>application &rarr; hire date</small></article>
                    <article class="card metric-card"><span>In pipeline</span><strong>2</strong><small>actively being processed</small></article>
                </section>
                <section class="pipeline-board" aria-label="Hiring pipeline">
                    <?php foreach (['Applied', 'Screening', 'Interviewing', 'Offer'] as $stage): $stageCandidates = array_values(array_filter($candidates, static fn (array $candidate): bool => $candidate['stage'] === $stage)); ?>
                        <article class="stage-card"><div class="stage-heading"><span><?= ems_icon('person-add') ?> <?= ems_h($stage) ?></span><span><?= count($stageCandidates) ?></span></div>
                            <?php if (!$stageCandidates): ?><div class="stage-empty">Empty</div><?php endif; ?>
                            <?php foreach ($stageCandidates as $candidate): ?><div class="candidate-card"><strong><?= ems_h($candidate['name']) ?></strong><small><?= ems_h($candidate['role']) ?></small><span class="candidate-age"><?= (int) $candidate['days'] ?>d in pipeline</span><div class="candidate-actions"><button type="button">&larr; Back</button><button type="button">Advance &rarr;</button></div></div><?php endforeach; ?>
                        </article>
                    <?php endforeach; ?>
                </section>
                <section class="card tracker-card"><div class="tracker-heading"><div><h2>Basic hiring tracker</h2><p>Days between application date and hire date</p></div><form method="get" action="hiring.php" class="tracker-search"><label class="search-control"><?= ems_icon('search') ?><input type="search" name="search" value="<?= ems_h($hiringSearch) ?>" placeholder="Search..." aria-label="Search hiring tracker"></label></form></div>
                    <div class="table-card"><table class="data-table"><thead><tr><th>Candidate</th><th>Role</th><th>Applied</th><th>Hired</th><th>Time to hire</th></tr></thead><tbody><?php foreach ($hiringRows as $candidate): ?><tr><td><strong><?= ems_h($candidate['name']) ?></strong><small><?= ems_h($candidate['email']) ?></small></td><td><?= ems_h($candidate['role']) ?></td><td><?= ems_h($candidate['applied']) ?></td><td><?= ems_h($candidate['hired']) ?></td><td class="hire-days"><?= (int) $candidate['days'] ?> days</td></tr><?php endforeach; ?><?php if (!$hiringRows): ?><tr><td colspan="5" class="empty-state">No completed hires match your search.</td></tr><?php endif; ?></tbody></table></div>
                </section>

            <?php elseif ($pageKey === 'onboarding'): ?>
                <section class="onboarding-grid">
                    <?php foreach ($onboardingPeople as $person):
                        $totalTasks = array_sum(array_map('count', $person['groups']));
                        $doneTasks = 0;
                        foreach ($person['groups'] as $tasks) { foreach ($tasks as $task) { $doneTasks += $task[1] ? 1 : 0; } }
                        $completion = (int) round($doneTasks / $totalTasks * 100);
                    ?>
                        <article class="card onboarding-person-card">
                            <div class="onboarding-summary"><div class="onboarding-person"><span class="avatar <?= ems_h($person['tone']) ?>"><?= ems_h($person['initials']) ?></span><span><strong><?= ems_h($person['name']) ?></strong><small>Started <?= ems_h($person['started']) ?> &middot; Buddy: <?= ems_h($person['buddy']) ?></small></span></div><div class="completion-value"><?= $completion ?>%<small><?= $doneTasks ?>/<?= $totalTasks ?> done</small></div></div>
                            <div class="progress"><span style="width: <?= $completion ?>%"></span></div>
                            <div class="check-grid">
                                <?php foreach ($person['groups'] as $group => $tasks): $groupDone = count(array_filter($tasks, static fn (array $task): bool => $task[1])); ?>
                                    <section class="check-group"><h3><?= ems_h($group) ?><span><?= $groupDone ?>/<?= count($tasks) ?></span></h3><?php foreach ($tasks as [$label, $complete]): ?><label><input type="checkbox" <?= $complete ? 'checked' : '' ?> disabled><span><?= ems_h($label) ?></span></label><?php endforeach; ?></section>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

            <?php elseif ($pageKey === 'performance'): ?>
                <?php $completedEvaluations = array_values(array_filter($evaluations, static fn (array $evaluation): bool => $evaluation['status'] !== 'Draft')); $averageScore = (int) round(array_sum(array_column($completedEvaluations, 'score')) / count($completedEvaluations)); $topPerformers = $completedEvaluations; usort($topPerformers, static fn (array $a, array $b): int => $b['score'] <=> $a['score']); ?>
                <section class="metric-grid three-columns">
                    <article class="card metric-card"><span>Average score</span><strong><?= $averageScore ?></strong><small><?= count($completedEvaluations) ?> completed evaluations</small></article>
                    <article class="card metric-card"><span>Awaiting acknowledgment</span><strong>2</strong><small>completed, not yet signed</small></article>
                    <article class="card metric-card"><span>Drafts in progress</span><strong><?= count($evaluations) - count($completedEvaluations) ?></strong><small>not yet finalized</small></article>
                </section>
                <section class="performance-summary">
                    <article class="card performers-card"><div class="section-title"><h2>Top performers</h2><p>Best completed score per employee</p></div><ol class="performer-list"><?php foreach (array_slice($topPerformers, 0, 5) as $rank => $evaluation): ?><li><span class="rank-number"><?= $rank + 1 ?></span><span class="avatar <?= ems_h(array_column($employees, 'tone', 'name')[$evaluation['employee']] ?? 'violet') ?>"><?= ems_h($evaluation['initials']) ?></span><span class="performer-copy"><strong><?= ems_h($evaluation['employee']) ?></strong><small><?= ems_h($evaluation['department']) ?></small></span><span class="score-bar"><i style="width: <?= (int) $evaluation['score'] ?>%"></i></span><b><?= (int) $evaluation['score'] ?></b></li><?php endforeach; ?></ol></article>
                    <article class="card distribution-card"><div class="section-title"><h2>Score distribution</h2><p>Completed evaluations</p></div><?php $scoreBands = [['90-100', 90, 100], ['75-89', 75, 89], ['60-74', 60, 74], ['<60', 0, 59]]; foreach ($scoreBands as [$label, $min, $max]): $bandCount = count(array_filter($completedEvaluations, static fn (array $evaluation): bool => $evaluation['score'] >= $min && $evaluation['score'] <= $max)); ?><div class="distribution-row"><div><span><?= ems_h($label) ?></span><span><?= $bandCount ?></span></div><div class="distribution-track"><i style="width: <?= count($completedEvaluations) ? (int) round($bandCount / count($completedEvaluations) * 100) : 0 ?>%"></i></div></div><?php endforeach; ?><p class="distribution-note">90+ Outstanding &middot; 75-89 Exceeds &middot; 60-74 Meets &middot; below 60 needs attention.</p></article>
                </section>
                <h2 class="list-heading">All evaluations</h2>
                <section class="evaluation-list"><?php foreach ($evaluations as $evaluation): ?><article class="card evaluation-row"><span class="avatar <?= ems_h(array_column($employees, 'tone', 'name')[$evaluation['employee']] ?? 'violet') ?>"><?= ems_h($evaluation['initials']) ?></span><div class="evaluation-copy"><strong><?= ems_h($evaluation['employee']) ?></strong><span><?= ems_h($evaluation['period']) ?></span><span class="pill <?= $evaluation['status'] === 'Acknowledged' ? 'mint' : ($evaluation['status'] === 'Draft' ? 'slate' : 'blue') ?>"><?= ems_h($evaluation['status']) ?></span><small>by <?= ems_h($evaluation['reviewer']) ?> &middot; <?= ems_h($evaluation['note']) ?></small></div><div class="evaluation-score"><strong><?= (int) $evaluation['score'] ?></strong><small><?= $evaluation['score'] >= 90 ? 'Outstanding' : ($evaluation['score'] >= 75 ? 'Exceeds expectations' : 'Meets expectations') ?></small></div><?php if ($evaluation['status'] === 'Draft'): ?><button class="small-button" type="button">Finalize</button><?php elseif ($evaluation['status'] === 'Completed'): ?><button class="small-button" type="button">Acknowledge</button><?php endif; ?><button class="icon-button" type="button" aria-label="More actions">&times;</button></article><?php endforeach; ?></section>

            <?php elseif ($pageKey === 'hr-records'): ?>
                <section class="metric-grid three-columns">
                    <article class="card metric-card"><span>Needs resolution</span><strong>2</strong><small>open or in-progress records</small></article>
                    <article class="card metric-card"><span>Awaiting signature</span><strong>2</strong><small>records without e-signature</small></article>
                    <article class="card metric-card"><span>Commendations</span><strong>2</strong><small>formal recognition on file</small></article>
                </section>
                <nav class="filter-tabs" aria-label="Filter HR records"><?php foreach ($recordTypes as $type): $queryType = $recordTypeMap[$type] ?? 'All'; ?><a class="filter-tab<?= $recordType === $queryType ? ' active' : '' ?>" href="hr-records.php<?= $queryType === 'All' ? '' : '?type=' . rawurlencode($queryType) ?>"><?= ems_h($type) ?></a><?php endforeach; ?></nav>
                <section class="record-cards"><?php foreach ($visibleRecords as $record): ?><article class="card hr-record-card"><span class="avatar <?= ems_h($record['tone']) ?>"><?= ems_h($record['initials']) ?></span><div class="hr-record-copy"><div class="record-title-line"><strong><?= ems_h($record['title']) ?></strong><span class="pill <?= $record['type'] === 'Commendation' ? 'mint' : ($record['type'] === 'Violation' ? 'rose' : 'slate') ?>"><?= ems_h($record['type']) ?></span><?php if ($record['priority']): ?><span class="pill amber"><?= ems_h($record['priority']) ?></span><?php endif; ?></div><small><?= ems_h($record['employee']) ?> &middot; issued <?= ems_h($record['date']) ?></small><p><?= ems_h($record['description']) ?></p></div><div class="record-actions"><span class="pill <?= $record['status'] === 'Resolved' ? 'mint' : ($record['status'] === 'Open' ? 'rose' : 'amber') ?>"><?= ems_h($record['status']) ?></span><?php if ($record['signature']): ?><span class="signature-label"><?= ems_h($record['signature']) ?></span><?php else: ?><button type="button" class="small-button">Get signature</button><?php endif; ?></div></article><?php endforeach; ?><?php if (!$visibleRecords): ?><p class="empty-state">No records in this category.</p><?php endif; ?></section>

            <?php elseif ($pageKey === 'leave'): ?>
                <section class="metric-grid three-columns">
                    <article class="card metric-card"><span>Pending requests</span><strong><?= count($leaveRequests) ?></strong><small>awaiting your decision</small></article>
                    <article class="card metric-card"><span>Approved this year</span><strong>2</strong><small>across all types</small></article>
                    <article class="card metric-card"><span>Employees tracked</span><strong><?= count($leaveBalances) ?></strong><small>2026 balances</small></article>
                </section>
                <section class="card balance-section"><div class="section-title"><h2>Balances &middot; 2026</h2><p>Vacation / sick / personal days remaining</p></div><div class="balance-grid"><?php foreach ($leaveBalances as $balance): ?><article class="balance-card"><h3><span class="avatar <?= ems_h($balance['tone']) ?>"><?= ems_h($balance['initials']) ?></span><?= ems_h($balance['name']) ?></h3><?php foreach (['Vacation' => ['vacation', 20], 'Sick' => ['sick', 10], 'Personal' => ['personal', 5]] as $label => [$key, $total]): $remaining = (int) $balance[$key]; ?><div class="balance-row"><div><span><?= ems_h($label) ?></span><span><?= $remaining ?> of <?= $total ?> left</span></div><div class="balance-track"><i style="width: <?= (int) round($remaining / $total * 100) ?>%"></i></div></div><?php endforeach; ?></article><?php endforeach; ?></div></section>
                <h2 class="list-heading">Approval queue</h2>
                <section class="leave-list"><?php foreach ($leaveRequests as $request): ?><article class="card leave-request"><span class="avatar <?= ems_h($request['tone']) ?>"><?= ems_h($request['initials']) ?></span><div class="leave-copy"><strong><?= ems_h($request['employee']) ?></strong><span class="pill <?= $request['type'] === 'Vacation' ? 'cyan' : 'violet' ?>"><?= ems_h($request['type']) ?></span><span class="leave-days"><?= (int) $request['days'] ?> <?= $request['days'] === 1 ? 'day' : 'days' ?></span><small><?= ems_h($request['from']) ?> &rarr; <?= ems_h($request['to']) ?><?= $request['note'] ? ' · ' . ems_h($request['note']) : '' ?></small></div><div class="leave-actions"><button type="button" class="approve-button">&#10003; Approve</button><button type="button" class="small-button">&times; Reject</button></div></article><?php endforeach; ?></section>
                <h2 class="list-heading">Decision history</h2>
                <section class="leave-list history-list"><?php foreach ($leaveHistory as $request): ?><article class="card leave-request history-request"><span class="avatar <?= ems_h($request['tone']) ?>"><?= ems_h($request['initials']) ?></span><div class="leave-copy"><strong><?= ems_h($request['employee']) ?></strong><span class="pill <?= $request['status'] === 'Approved' ? 'mint' : 'rose' ?>"><?= ems_h($request['status']) ?></span><small><?= (int) $request['days'] ?>d &middot; <?= ems_h($request['dates']) ?><?= $request['note'] ? ' · "' . ems_h($request['note']) . '"' : '' ?></small></div><button class="icon-button" type="button" aria-label="Dismiss history item">&times;</button></article><?php endforeach; ?></section>

            <?php elseif ($pageKey === 'settings'): ?>
                <?php $rolesByDepartment = []; foreach ($jobTitles as $role) { $rolesByDepartment[$role['department']][] = $role; } ?>
                <section class="settings-departments"><?php foreach ($rolesByDepartment as $department => $roles): ?><section class="department-section"><h2><span class="department-icon"><?= ems_icon('briefcase') ?></span><?= ems_h($department) ?><small><?= count($roles) ?> <?= count($roles) === 1 ? 'role' : 'roles' ?></small></h2><div class="role-grid"><?php foreach ($roles as $role): ?><article class="card role-card"><strong><?= ems_h($role['title']) ?></strong><span class="level"><?= ems_h($role['level']) ?></span><p><?= ems_h($role['description']) ?></p><div><b><?= ems_money($role['salary']) ?></b><small><?= (int) $role['count'] ?> employee<?= $role['count'] === 1 ? '' : 's' ?></small></div></article><?php endforeach; ?></div></section><?php endforeach; ?></section>
            <?php endif; ?>
        </div>
    </main>
    <?php if ($pageKey === 'onboarding'): ?>
        <dialog class="dashboard-dialog onboarding-dialog" id="dashboard-form-dialog" aria-labelledby="dialog-title">
            <div class="dialog-form">
                <header class="dialog-header"><div><h2 id="dialog-title">New onboarding</h2><p>Review tasks by phase and update assignment status.</p></div><button class="dialog-close" type="button" data-dialog-close aria-label="Close">&times;</button></header>
                <section class="onboarding-task-panel" data-onboarding-task-panel data-task-groups="<?= ems_h((string) json_encode($onboardingTaskLists, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">
                    <div class="onboarding-phase-tabs" role="tablist" aria-label="Onboarding phases">
                        <?php foreach (array_keys($onboardingTaskLists) as $index => $phase): $tabId = 'onboarding-phase-' . $index; ?>
                            <button class="onboarding-phase-tab<?= $index === 0 ? ' active' : '' ?>" id="<?= ems_h($tabId) ?>" type="button" role="tab" aria-controls="onboarding-task-content" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>" tabindex="<?= $index === 0 ? '0' : '-1' ?>" data-phase="<?= ems_h($phase) ?>"><?= ems_h($phase) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <section class="onboarding-task-card" id="onboarding-task-content" role="tabpanel" aria-labelledby="onboarding-phase-0" tabindex="0">
                        <h3>Tasks</h3>
                        <div class="onboarding-task-rows" data-task-rows role="list"></div>
                    </section>
                </section>
                <p class="dialog-feedback" role="status" aria-live="polite"></p>
                <footer class="dialog-actions"><button class="dialog-cancel" type="button" data-dialog-close>Close</button></footer>
            </div>
        </dialog>
        <script src="dashboard.js" defer></script>
    <?php elseif ($dialogConfig): ?>
        <dialog class="dashboard-dialog" id="dashboard-form-dialog" aria-labelledby="dialog-title">
            <form class="dialog-form" method="dialog" data-preview-form>
                <header class="dialog-header"><div><h2 id="dialog-title"><?= ems_h($dialogConfig['title']) ?></h2><p><?= ems_h($dialogConfig['description']) ?></p></div><button class="dialog-close" type="button" data-dialog-close aria-label="Close">&times;</button></header>
                <div class="dialog-fields">
                    <?php foreach ($dialogConfig['fields'] as $field): ?>
                        <label class="dialog-field<?= !empty($field['wide']) ? ' wide' : '' ?>">
                            <span><?= ems_h($field['label']) ?></span>
                            <?php if ($field['type'] === 'select'): ?>
                                <select name="<?= ems_h($field['name']) ?>"<?= !empty($field['required']) ? ' required' : '' ?>><option value=""><?= ems_h($field['placeholder']) ?></option><?php foreach ($field['options'] as $option): ?><option value="<?= ems_h($option) ?>"><?= ems_h($option) ?></option><?php endforeach; ?></select>
                            <?php elseif ($field['type'] === 'textarea'): ?>
                                <textarea name="<?= ems_h($field['name']) ?>" placeholder="<?= ems_h($field['placeholder']) ?>" rows="3"<?= !empty($field['required']) ? ' required' : '' ?>></textarea>
                            <?php else: ?>
                                <input type="<?= ems_h($field['type']) ?>" name="<?= ems_h($field['name']) ?>"<?= isset($field['placeholder']) ? ' placeholder="' . ems_h($field['placeholder']) . '"' : '' ?><?= isset($field['min']) ? ' min="' . (int) $field['min'] . '"' : '' ?><?= isset($field['max']) ? ' max="' . (int) $field['max'] . '"' : '' ?><?= !empty($field['required']) ? ' required' : '' ?>>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="dialog-feedback" role="status" aria-live="polite"></p>
                <footer class="dialog-actions"><button class="dialog-cancel" type="button" data-dialog-close>Cancel</button><button class="btn-primary" type="submit"><?= ems_h($dialogConfig['submit']) ?></button></footer>
            </form>
        </dialog>
        <script src="dashboard.js" defer></script>
    <?php endif; ?>
</body>
</html>
