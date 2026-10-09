<?php
require_once __DIR__ . '/auth.php';
ems_require_authentication();
require_once __DIR__ . '/dashboard-data.php';

$pages = [
    'employees' => ['title' => 'Employees', 'icon' => 'users', 'subtitle' => 'The full directory - profiles, roles, salaries and status.'],
    'hiring' => ['title' => 'Hiring', 'icon' => 'person-add', 'subtitle' => 'Applicant tracking with a live pipeline and automatic time-to-hire on every completed hire.'],
    'onboarding' => ['title' => 'Onboarding', 'icon' => 'sparkles', 'subtitle' => 'Orientation, requirements, training and company policies - every new hire gets a clear checklist.'],
    'performance' => ['title' => 'Performance', 'icon' => 'performance', 'subtitle' => 'Records employee performance, evaluations and achievements - from draft to signed acknowledgment.'],
    'hr-records' => ['title' => 'HR Records', 'icon' => 'gavel', 'subtitle' => 'Memos, warning notices, policy violations and formal commendations - with resolution tracking and signed acknowledgment.'],
    'leave' => ['title' => 'Leave Management', 'icon' => 'calendar', 'subtitle' => 'Time-off requests, approval workflow and balance tracking - approvals deduct days automatically.'],
    'settings' => ['title' => 'Settings', 'icon' => 'settings', 'subtitle' => 'Job titles, departments and base salary bands used across hiring, profiles and payroll views.'],
    'reports' => ['title' => 'Reports', 'icon' => 'chart', 'subtitle' => 'Workforce, compensation, turnover and recruiting summaries for operational planning.'],
];

if (!isset($pageKey, $pages[$pageKey])) {
    http_response_code(404);
    exit('Dashboard not found.');
}
$page = $pages[$pageKey];
$navItems = [
    ['key' => 'overview', 'title' => 'Overview', 'icon' => 'grid', 'href' => 'overview.php'],
    ['key' => 'employees', 'title' => 'Employees', 'icon' => 'users', 'href' => 'employees.php'],
    ['key' => 'hiring', 'title' => 'Hiring', 'icon' => 'person-add', 'href' => 'hiring.php', 'badge' => count($candidates)],
    ['key' => 'onboarding', 'title' => 'Onboarding', 'icon' => 'sparkles', 'href' => 'onboarding.php'],
    ['key' => 'performance', 'title' => 'Performance', 'icon' => 'performance', 'href' => 'performance.php'],
    ['key' => 'hr-records', 'title' => 'HR Records', 'icon' => 'gavel', 'href' => 'hr-records.php'],
    ['key' => 'leave', 'title' => 'Leave', 'icon' => 'calendar', 'href' => 'leave.php'],
    ['key' => 'settings', 'title' => 'Settings', 'icon' => 'settings', 'href' => 'settings.php'],
    ['key' => 'reports', 'title' => 'Reports', 'icon' => 'chart', 'href' => 'reports.php'],
];

$employeeSearch = trim((string) ($_GET['search'] ?? ''));
$departmentFilter = (string) ($_GET['department'] ?? '');
$statusFilter = (string) ($_GET['status'] ?? '');
$employeeRows = isset($employeeRows) ? $employeeRows : array_values(array_filter($employees, static function (array $employee) use ($employeeSearch, $departmentFilter, $statusFilter): bool {
    $matchesSearch = $employeeSearch === '' || stripos($employee['name'] . ' ' . $employee['email'], $employeeSearch) !== false;
    $matchesDepartment = $departmentFilter === '' || $employee['department'] === $departmentFilter;
    $matchesStatus = $statusFilter === '' || $employee['status'] === $statusFilter;
    return $matchesSearch && $matchesDepartment && $matchesStatus;
}));

$hiringSearch = trim((string) ($_GET['search'] ?? ''));
$hiringRows = array_values(array_filter($hiringHistory, static function (array $candidate) use ($hiringSearch): bool {
    return $hiringSearch === '' || stripos($candidate['name'] . ' ' . $candidate['email'] . ' ' . $candidate['role'], $hiringSearch) !== false;
}));
$applicantDepartments = array_values(array_unique(array_column(array_merge($candidates, $candidateArchive), 'department')));
$stageActions = [
    'Applied' => ['Screening' => 'Move to Screening', 'Rejected' => 'Reject', 'Withdrawn' => 'Mark withdrawn', 'No Response' => 'No response'],
    'Screening' => ['Interview' => 'Move to Interview', 'Rejected' => 'Reject', 'Withdrawn' => 'Mark withdrawn', 'No Response' => 'No response'],
    'Interviewing' => ['Offer' => 'Extend offer', 'Rejected' => 'Reject', 'Withdrawn' => 'Mark withdrawn', 'No Response' => 'No response'],
    'Offer' => ['Hired' => 'Hire', 'Rejected' => 'Reject', 'Withdrawn' => 'Mark withdrawn', 'No Response' => 'No response'],
];

$recordType = (string) ($_GET['type'] ?? 'All');
$visibleRecords = array_values(array_filter($hrRecords, static fn (array $record): bool => $recordType === 'All' || $record['type'] === $recordType));
$recordTypes = ['All', 'Memos', 'Warnings', 'Violations', 'Commendations'];
$recordTypeMap = ['Memos' => 'Memo', 'Warnings' => 'Warning', 'Violations' => 'Violation', 'Commendations' => 'Commendation'];
$employeeOptions = array_column($employees, 'name');
$performanceEmployeeOptions = [];
foreach ($employees as $employee) {
    if (in_array($employee['status'], ['Active', 'Probation'], true)) {
        $performanceEmployeeOptions[$employee['employee_id']] = $employee['name'];
    }
}

$roleOptions = array_values(array_unique(array_column($jobTitles, 'title')));
$hiringRoleOptions = array_column($jobTitles, 'title', 'job_title_id');
$departmentOptions = isset($departmentOptions)
    ? $departmentOptions
    : array_values(array_unique(array_column($jobTitles, 'department')));
$activeEmployees = array_values(array_filter(
    $employees,
    static fn (array $employee): bool => in_array($employee['status'], ['Active', 'Probation'], true)
));
$headcountByDepartment = [];
$payrollByDepartment = [];
foreach ($activeEmployees as $employee) {
    $department = $employee['department'];
    $headcountByDepartment[$department] = ($headcountByDepartment[$department] ?? 0) + 1;
    $payrollByDepartment[$department] = ($payrollByDepartment[$department] ?? 0) + $employee['salary'];
}
ksort($headcountByDepartment);
ksort($payrollByDepartment);
$annualPayroll = array_sum(array_column($activeEmployees, 'salary'));
$averageTimeToHire = count($hiringHistory) ? (int) round(array_sum(array_column($hiringHistory, 'days')) / count($hiringHistory)) : 0;
$fastestTimeToHire = count($hiringHistory) ? min(array_column($hiringHistory, 'days')) : 0;
$dialogConfigs = [
    'employees' => ['title' => 'Add employee', 'description' => 'New hires start in probation by default.', 'submit' => 'Add employee', 'fields' => [
        ['label' => 'Full name', 'name' => 'full_name', 'type' => 'text', 'placeholder' => 'Enter full name', 'required' => true],
        ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'placeholder' => 'Enter email address', 'required' => true],
        ['label' => 'Job title', 'name' => 'job_title', 'type' => 'select', 'placeholder' => 'Choose a role', 'options' => $roleOptions, 'required' => true],
        ['label' => 'Employment type', 'name' => 'employment_type', 'type' => 'select', 'placeholder' => 'Choose employment type', 'options' => ['Full-time', 'Part-time', 'Contract'], 'required' => true],
        ['label' => 'Base salary (PHP/year)', 'name' => 'salary', 'type' => 'number', 'placeholder' => 'Enter annual base salary', 'required' => true],
        ['label' => 'Hire date', 'name' => 'hire_date', 'type' => 'date', 'required' => true],
        ['label' => 'Status', 'name' => 'status', 'type' => 'select', 'placeholder' => 'Choose status', 'options' => ['Probation', 'Active'], 'required' => true],
        ['label' => 'Location', 'name' => 'location', 'type' => 'text', 'placeholder' => 'Enter location'],
        ['label' => 'Phone', 'name' => 'phone', 'type' => 'tel', 'placeholder' => 'Enter phone number'],
        ['label' => 'Emergency contact', 'name' => 'emergency_contact', 'type' => 'text', 'placeholder' => 'Enter contact name and phone'],
    ]],
    'hiring' => ['title' => 'Add candidate', 'description' => 'New candidates enter the pipeline as Applied.', 'submit' => 'Add to pipeline', 'fields' => [
        ['label' => 'Name', 'name' => 'name', 'type' => 'text', 'placeholder' => 'Enter full name', 'required' => true],
        ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'placeholder' => 'Enter email address', 'required' => true],
        ['label' => 'Role', 'name' => 'job_title_id', 'type' => 'select', 'placeholder' => 'Choose open role', 'options' => $hiringRoleOptions, 'required' => true],
        ['label' => 'Application date', 'name' => 'application_date', 'type' => 'date', 'required' => true],
        ['label' => 'Source', 'name' => 'source', 'type' => 'text', 'placeholder' => 'Enter candidate source', 'wide' => true],
        ['label' => 'Notes', 'name' => 'notes', 'type' => 'textarea', 'placeholder' => 'Enter screening notes or portfolio links', 'wide' => true],
    ]],
    'performance' => ['title' => 'New evaluation', 'description' => 'Total score = OKR x 50% + Core Competencies x 30% + Peer / Manager Feedback x 20%. It is calculated automatically.', 'submit' => 'Save evaluation', 'fields' => [
        ['label' => 'Employee', 'name' => 'employee_id', 'type' => 'select', 'placeholder' => 'Choose employee', 'options' => $performanceEmployeeOptions, 'required' => true],
        ['label' => 'Review period', 'name' => 'review_period', 'type' => 'text', 'placeholder' => 'For example, 2026 Q3', 'maxlength' => 30, 'required' => true],
        ['label' => 'Attendance (%)', 'name' => 'attendance_pct', 'type' => 'number', 'placeholder' => 'Optional', 'min' => 0, 'max' => 100, 'step' => '0.01'],
        ['label' => 'Training completion (%)', 'name' => 'training_completion_pct', 'type' => 'number', 'placeholder' => 'Optional', 'min' => 0, 'max' => 100, 'step' => '0.01'],
        ['label' => 'OKR Achievement (0-100) - 50%', 'name' => 'okr_achievement', 'type' => 'number', 'placeholder' => 'Enter score', 'min' => 0, 'max' => 100, 'step' => '0.01', 'required' => true],
        ['label' => 'Core Competencies (0-100) - 30%', 'name' => 'core_competencies', 'type' => 'number', 'placeholder' => 'Enter score', 'min' => 0, 'max' => 100, 'step' => '0.01', 'required' => true],
        ['label' => 'Peer / Manager Feedback (0-100) - 20%', 'name' => 'feedback_score', 'type' => 'number', 'placeholder' => 'Enter score', 'min' => 0, 'max' => 100, 'step' => '0.01', 'required' => true],
        ['label' => 'Status', 'name' => 'status', 'type' => 'select', 'placeholder' => 'Choose status', 'options' => ['Draft', 'Completed'], 'required' => true],
        ['label' => 'Comments', 'name' => 'comments', 'type' => 'textarea', 'placeholder' => 'Optional review notes', 'wide' => true],
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
        ['label' => 'Employee', 'name' => 'employee_id', 'type' => 'select', 'placeholder' => 'Choose employee', 'options' => $leaveEmployeeOptions ?? [], 'required' => true],
        ['label' => 'Type', 'name' => 'leave_type', 'type' => 'select', 'placeholder' => 'Choose leave type', 'options' => ['Vacation', 'Sick', 'Personal'], 'required' => true],
        ['label' => 'Start date', 'name' => 'start_date', 'type' => 'date', 'required' => true],
        ['label' => 'End date', 'name' => 'end_date', 'type' => 'date', 'required' => true],
        ['label' => 'Reason (optional)', 'name' => 'reason', 'type' => 'textarea', 'placeholder' => 'Enter a short reason', 'wide' => true],
    ]],
    'settings' => ['title' => 'New job title', 'description' => 'Add a job title, department, level and salary band.', 'submit' => 'Create title', 'fields' => [
        ['label' => 'Job title', 'name' => 'job_title_name', 'type' => 'text', 'placeholder' => 'Enter job title', 'required' => true],
        ['label' => 'Department', 'name' => 'department_id', 'type' => 'select', 'placeholder' => 'Choose department', 'options' => $departmentOptions, 'required' => true],
        ['label' => 'Level', 'name' => 'level', 'type' => 'select', 'placeholder' => 'Choose level', 'options' => ['Junior', 'Mid', 'Lead'], 'required' => true],
        ['label' => 'Pay grade', 'name' => 'pay_grade', 'type' => 'text', 'placeholder' => 'Optional pay grade'],
        ['label' => 'Minimum salary (PHP/year)', 'name' => 'min_salary', 'type' => 'number', 'placeholder' => 'Optional minimum', 'min' => 0, 'step' => '0.01'],
        ['label' => 'Maximum salary (PHP/year)', 'name' => 'max_salary', 'type' => 'number', 'placeholder' => 'Optional maximum', 'min' => 0, 'step' => '0.01'],
        ['label' => 'Description', 'name' => 'job_description', 'type' => 'textarea', 'placeholder' => 'Enter what this role owns', 'wide' => true],
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
    <script>
        try {
            const savedTheme = localStorage.getItem('ems-theme');
            if (savedTheme === 'dark-sidebar') document.documentElement.dataset.theme = savedTheme;
        } catch (error) {
            console.warn('Unable to restore the saved EMS theme preference.', error);
        }
    </script>
    <link rel="stylesheet" href="styles.css?v=<?= (int) filemtime(__DIR__ . '/styles.css') ?>">
    <link rel="stylesheet" href="dashboard.css?v=<?= (int) filemtime(__DIR__ . '/dashboard.css') ?>">
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
            <details class="account-menu">
                <summary class="account-trigger">
                    <span class="account-avatar" aria-hidden="true"><?= ems_h(strtoupper(substr((string) $_SESSION['username'], 0, 2))) ?></span>
                    <span class="account-name"><?= ems_h($_SESSION['username']) ?></span>
                    <span class="account-caret" aria-hidden="true"></span>
                </summary>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                    <button class="sign-out" type="submit"><?= ems_icon('logout') ?><span>Sign out</span></button>
                </form>
            </details>
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
                <?php if (in_array($pageKey, ['hiring', 'onboarding', 'performance', 'hr-records', 'leave'], true) && ($pageKey !== 'hiring' || $hiringCanWrite) && ($pageKey !== 'performance' || $performanceCanWrite)): ?>
                    <button class="btn-primary" type="button" data-dialog-open="dashboard-form-dialog" aria-haspopup="dialog">
                        <span aria-hidden="true">+</span>
                        <?= ['employees' => 'Add employee', 'hiring' => 'Add candidate', 'onboarding' => 'New onboarding', 'performance' => 'New evaluation', 'hr-records' => 'File record', 'leave' => 'New request', 'settings' => 'New job title'][$pageKey] ?>
                    </button>
                <?php endif; ?>
                <?php if ($pageKey === 'settings' && $settingsCanWrite): ?>
                    <button class="btn-primary" type="button" data-dialog-open="dashboard-form-dialog" aria-haspopup="dialog"><span aria-hidden="true">+</span>New job title</button>
                <?php endif; ?>
            </section>
            <?php if ($pageKey === 'hiring' && isset($hiringFeedback)): ?>
                <p class="applicant-feedback" role="<?= $hiringFeedback['type'] === 'error' ? 'alert' : 'status' ?>"><?= ems_h($hiringFeedback['message']) ?></p>
            <?php endif; ?>
            <?php if ($pageKey === 'settings' && isset($settingsFeedback)): ?>
                <p class="applicant-feedback" role="<?= $settingsFeedback['type'] === 'error' ? 'alert' : 'status' ?>"><?= ems_h($settingsFeedback['message']) ?></p>
            <?php endif; ?>
            <?php if ($pageKey === 'leave' && !empty($leaveFeedback)): ?>
                <p class="applicant-feedback" role="<?= $leaveFeedback['type'] === 'error' ? 'alert' : 'status' ?>"><?= ems_h($leaveFeedback['message']) ?></p>
            <?php endif; ?>
            <?php if ($pageKey === 'leave' && !empty($leaveLoadError)): ?>
                <p class="applicant-feedback" role="alert"><?= ems_h($leaveLoadError) ?></p>
            <?php endif; ?>
            <?php if ($pageKey === 'performance' && isset($performanceFeedback)): ?>
                <p class="applicant-feedback" role="<?= $performanceFeedback['type'] === 'error' ? 'alert' : 'status' ?>"><?= ems_h($performanceFeedback['message']) ?></p>
            <?php endif; ?>

            <?php if (in_array($pageKey, ['onboarding', 'hr-records'], true)): ?>
                <p class="applicant-feedback" role="note">Preview only: sample data. This page is not connected to the database.</p>
            <?php endif; ?>
            <?php if ($pageKey === 'employees'): ?>
                <form class="filter-bar" method="get" action="employees.php">
                    <input type="hidden" name="page" value="1">
                    <label class="search-control"><?= ems_icon('search') ?><input type="search" name="search" value="<?= ems_h($employeeSearch) ?>" placeholder="Search name or email..." aria-label="Search name or email"></label>
                    <label class="select-control"><span class="sr-only">Department</span><select name="department_id" onchange="this.form.submit()"><option value="">All departments</option><?php foreach ($departmentOptions as $departmentId => $departmentName): ?><option value="<?= (int) $departmentId ?>"<?= (string) $departmentFilter === (string) $departmentId ? ' selected' : '' ?>><?= ems_h($departmentName) ?></option><?php endforeach; ?></select></label>
                    <label class="select-control"><span class="sr-only">Status</span><select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach (['Active', 'Probation', 'Resigned', 'Terminated'] as $status): ?><option value="<?= ems_h($status) ?>"<?= $statusFilter === $status ? ' selected' : '' ?>><?= ems_h($status) ?></option><?php endforeach; ?></select></label>
                    <button class="filter-submit" type="submit">Search</button>
                </form>
                <?php if ($employeeFeedback): ?><p class="applicant-feedback" role="<?= $employeeFeedback['type'] === 'error' ? 'alert' : 'status' ?>"><?= ems_h($employeeFeedback['message']) ?></p><?php endif; ?>
                <section class="employee-browser" data-record-pager data-page-size="<?= (int) ($displayLimits['employee_page_size'] ?? 10) ?>">
                <div class="card table-card employee-table-card">
                    <table class="data-table employee-table">
                        <thead><tr><th>Employee</th><th>Employee number</th><th>Role</th><th>Status</th><th>Base salary</th><th>Hired</th><?php if ($employeeCanWrite): ?><th><span class="sr-only">Actions</span></th><?php endif; ?></tr></thead>
                        <tbody>
                            <?php foreach ($employeeRows as $employee): ?>
                                <tr data-paginated-row>
                                    <td><span class="employee-cell"><span class="avatar <?= ems_h($employee['tone']) ?>"><?= ems_h($employee['initials']) ?></span><span><strong><?= ems_h($employee['name']) ?></strong><small><?= ems_h($employee['email']) ?></small></span></span></td>
                                    <td><?= ems_h($employee['employee_number']) ?></td>
                                    <td><?= ems_h($employee['role']) ?><small><?= ems_h($employee['department']) ?></small></td>
                                    <td><span class="pill <?= $employee['status'] === 'Probation' ? 'amber' : (in_array($employee['status'], ['Resigned', 'Terminated'], true) ? 'rose' : 'mint') ?>"><?= ems_h($employee['status']) ?></span></td>
                                    <td><?= ems_money($employee['salary']) ?></td><td><?= ems_h($employee['hired']) ?></td>
                                    <?php if ($employeeCanWrite): ?><td class="employee-actions-cell"><details class="employee-edit"><summary class="small-button">Edit profile</summary><form method="post" action="employees.php" class="employee-edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="employee_id" value="<?= (int) $employee['employee_id'] ?>">
                                        <label>Email<input type="email" name="email" maxlength="120" value="<?= ems_h($employee['email']) ?>"></label>
                                        <label>Phone<input type="tel" name="phone_number" maxlength="30" value="<?= ems_h($employee['phone_number']) ?>"></label>
                                        <label>Address<input type="text" name="address" maxlength="255" value="<?= ems_h($employee['address']) ?>"></label>
                                        <label>Emergency contact<input type="text" name="emergency_contact" maxlength="255" value="<?= ems_h($employee['emergency_contact']) ?>"></label>
                                        <label>Department<select name="department_id"><option value="">Unassigned</option><?php foreach ($departmentOptions as $departmentId => $departmentName): ?><option value="<?= (int) $departmentId ?>"<?= (string) $employee['department_id'] === (string) $departmentId ? ' selected' : '' ?>><?= ems_h($departmentName) ?></option><?php endforeach; ?></select></label>
                                        <label>Job title<select name="job_title_id"><option value="">Unassigned</option><?php foreach ($jobTitleOptions as $jobTitleId => $jobTitleName): ?><option value="<?= (int) $jobTitleId ?>"<?= (string) $employee['job_title_id'] === (string) $jobTitleId ? ' selected' : '' ?>><?= ems_h($jobTitleName) ?></option><?php endforeach; ?></select></label>
                                        <label>Employment status<select name="employment_status" required><?php foreach (['Active', 'Probation', 'Resigned', 'Terminated'] as $status): ?><option value="<?= ems_h($status) ?>"<?= $employee['employment_status'] === $status ? ' selected' : '' ?>><?= ems_h($status) ?></option><?php endforeach; ?></select></label>
                                        <button class="btn-primary" type="submit">Save profile</button>
                                    </form></details></td><?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            <tr data-pager-empty<?= $employeeRows ? ' hidden' : '' ?>><td colspan="<?= $employeeCanWrite ? 7 : 6 ?>" class="empty-state">No employees match those filters.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="applicant-pagination"><span data-page-summary aria-live="polite"></span><div><button type="button" data-page-prev disabled>Previous</button><span data-page-numbers></span><span data-page-indicator></span><button type="button" data-page-next disabled>Next</button></div></div>
                </section>

            <?php elseif ($pageKey === 'hiring'): ?>
                <section class="metric-grid three-columns">
                    <article class="card metric-card"><span>Avg. time-to-hire</span><strong><?= $averageTimeToHire ?> days</strong><small><?= count($hiringHistory) ?> completed hires</small></article>
                    <article class="card metric-card"><span>Fastest hire</span><strong><?= $fastestTimeToHire ?> days</strong><small>application &rarr; hire date</small></article>
                    <article class="card metric-card"><span>In pipeline</span><strong><?= count($candidates) ?></strong><small>actively being processed</small></article>
                </section>
                <section class="pipeline-board" aria-label="Hiring pipeline">
                    <?php foreach (['Applied', 'Screening', 'Interviewing', 'Offer'] as $stage): $stageCandidates = array_values(array_filter($candidates, static fn (array $candidate): bool => $candidate['stage'] === $stage)); ?>
                            <?php $activeStageCandidates = array_values(array_filter($stageCandidates, static fn (array $candidate): bool => $candidate['is_stage_active'])); $waitingCount = count($stageCandidates) - count($activeStageCandidates); ?>
                            <article class="stage-card"><div class="stage-heading"><span><?= ems_icon('person-add') ?> <?= ems_h($stage) ?></span><span><?= count($activeStageCandidates) ?>/<?= (int) $hiringStageCapacity ?> active</span></div>
                                <?php if (!$activeStageCandidates): ?><div class="stage-empty">No candidates currently being processed</div><?php endif; ?>
                                <?php foreach ($activeStageCandidates as $candidate): ?><div class="candidate-card"><strong><?= ems_h($candidate['name']) ?></strong><small><?= ems_h($candidate['role']) ?></small><span class="candidate-age"><?= (int) $candidate['days'] ?>d in pipeline</span><?php if ($hiringCanWrite): ?><details class="applicant-actions"><summary>Actions</summary><div class="applicant-action-menu"><?php foreach ($stageActions[$stage] as $targetStatus => $actionLabel): ?><form method="post" action="hiring.php"><input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>"><input type="hidden" name="hiring_action" value="update_status"><input type="hidden" name="hiring_id" value="<?= (int) $candidate['hiring_id'] ?>"><input type="hidden" name="status" value="<?= ems_h($targetStatus) ?>"><button class="<?= in_array($targetStatus, ['Rejected', 'Withdrawn', 'No Response'], true) ? 'is-negative' : '' ?>" type="submit"><?= ems_h($actionLabel) ?></button></form><?php endforeach; ?></div></details><?php endif; ?></div><?php endforeach; ?>
                                <?php if ($waitingCount > 0): ?><p class="stage-queue-note"><?= $waitingCount ?> waiting in FIFO queue</p><?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                </section>
                <section class="card applicant-section" data-applicant-browser>
                    <div class="applicant-heading">
                        <div><h2>Applicants</h2><p>Search and review candidates across the active pipeline and archive.</p></div>
                        <span class="data-notice">Live database records</span>
                    </div>
                    <div class="applicant-toolbar">
                        <label class="search-control"><?= ems_icon('search') ?><input type="search" data-applicant-search placeholder="Search name, email, or role..." aria-label="Search applicants"></label>
                        <label class="select-control"><span class="sr-only">Department</span><select data-applicant-department><option value="">All departments</option><?php foreach ($applicantDepartments as $department): ?><option value="<?= ems_h($department) ?>"><?= ems_h($department) ?></option><?php endforeach; ?></select></label>
                        <label class="select-control page-size-control"><span>Rows</span><select data-page-size aria-label="Applicants per page"><option value="10">10</option><option value="25">25</option><option value="50">50</option></select></label>
                    </div>
                    <div class="applicant-tabs" role="tablist" aria-label="Applicant status">
                        <button type="button" class="applicant-tab active" id="applicants-active-tab" role="tab" aria-selected="true" aria-controls="applicants-active-panel" data-applicant-tab="active">Active <span><?= count($candidates) ?></span></button>
                        <button type="button" class="applicant-tab" id="applicants-archive-tab" role="tab" aria-selected="false" aria-controls="applicants-archive-panel" data-applicant-tab="archive" tabindex="-1">Archive <span><?= count($candidateArchive) ?></span></button>
                    </div>
                    <div class="applicant-panel" id="applicants-active-panel" role="tabpanel" aria-labelledby="applicants-active-tab" data-applicant-panel="active">
                        <div class="table-card applicant-table-wrap"><table class="data-table applicant-table"><thead><tr><th>Candidate</th><th>Role</th><th>Department</th><th>Stage</th><th>Applied</th><th>In pipeline</th><th>Actions</th></tr></thead><tbody>
                            <?php foreach ($candidates as $candidate): ?><tr data-applicant-row data-department="<?= ems_h($candidate['department']) ?>" data-search="<?= ems_h($candidate['name'] . ' ' . $candidate['email'] . ' ' . $candidate['role'] . ' ' . $candidate['department']) ?>"><td><strong><?= ems_h($candidate['name']) ?></strong><small><?= ems_h($candidate['email']) ?></small></td><td><?= ems_h($candidate['role']) ?></td><td><?= ems_h($candidate['department']) ?></td><td><span class="pill blue"><?= ems_h($candidate['stage']) ?></span><small><?= $candidate['is_stage_active'] ? 'Active slot' : 'Queue #' . (int) $candidate['queue_position'] ?></small></td><td><?= ems_h($candidate['applied']) ?></td><td><?= (int) $candidate['days'] ?> days</td><td><?php if (!$candidate['is_stage_active']): ?><span class="muted">Waiting in FIFO queue</span><?php elseif ($hiringCanWrite): ?><form method="post" action="hiring.php" class="applicant-status-form"><input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>"><input type="hidden" name="hiring_action" value="update_status"><input type="hidden" name="hiring_id" value="<?= (int) $candidate['hiring_id'] ?>"><select class="applicant-action-select" name="status" aria-label="Next status for <?= ems_h($candidate['name']) ?>" required><option value="">Choose action</option><?php foreach ($stageActions[$candidate['stage']] as $targetStatus => $actionLabel): ?><option value="<?= ems_h($targetStatus) ?>"><?= ems_h($actionLabel) ?></option><?php endforeach; ?></select><button type="submit" class="small-button">Update</button></form><?php else: ?><span>View only</span><?php endif; ?></td></tr><?php endforeach; ?>
                            <tr class="applicant-empty-row" data-applicant-empty hidden><td colspan="7"><strong>No applicants found</strong><span>Try changing your search or department filter.</span></td></tr>
                        </tbody></table></div>
                        <div class="applicant-pagination"><span data-page-summary aria-live="polite"></span><div><button type="button" data-page-prev disabled>Previous</button><span data-page-numbers></span><span data-page-indicator></span><button type="button" data-page-next disabled>Next</button></div></div>
                    </div>
                    <div class="applicant-panel" id="applicants-archive-panel" role="tabpanel" aria-labelledby="applicants-archive-tab" data-applicant-panel="archive" hidden>
                        <div class="table-card applicant-table-wrap"><table class="data-table applicant-table"><thead><tr><th>Candidate</th><th>Role</th><th>Department</th><th>Applied</th><th>Outcome</th><th>Closed</th></tr></thead><tbody>
                            <?php foreach ($candidateArchive as $candidate): ?><tr data-applicant-row data-department="<?= ems_h($candidate['department']) ?>" data-search="<?= ems_h($candidate['name'] . ' ' . $candidate['email'] . ' ' . $candidate['role'] . ' ' . $candidate['department'] . ' ' . $candidate['outcome']) ?>"><td><strong><?= ems_h($candidate['name']) ?></strong><small><?= ems_h($candidate['email']) ?></small></td><td><?= ems_h($candidate['role']) ?></td><td><?= ems_h($candidate['department']) ?></td><td><?= ems_h($candidate['applied']) ?></td><td><span class="pill <?= $candidate['outcome'] === 'Rejected' ? 'rose' : 'slate' ?>"><?= ems_h($candidate['outcome']) ?></span></td><td><?= ems_h($candidate['closed']) ?></td></tr><?php endforeach; ?>
                            <tr class="applicant-empty-row" data-applicant-empty hidden><td colspan="6"><strong>No archived applicants found</strong><span>Try changing your search or department filter.</span></td></tr>
                        </tbody></table></div>
                        <div class="applicant-pagination"><span data-page-summary aria-live="polite"></span><div><button type="button" data-page-prev disabled>Previous</button><span data-page-numbers></span><span data-page-indicator></span><button type="button" data-page-next disabled>Next</button></div></div>
                    </div>
                    <p class="applicant-feedback" data-applicant-feedback role="status" aria-live="polite"></p>
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
                    <?php if (!$onboardingPeople): ?><p class="empty-state">No onboarding task data is available in the current database schema.</p><?php endif; ?>
                </section>

            <?php elseif ($pageKey === 'performance'): ?>
                <?php $completedEvaluations = array_values(array_filter($evaluations, static fn (array $evaluation): bool => $evaluation['status'] !== 'Draft')); $scoredCompletedEvaluations = array_values(array_filter($completedEvaluations, static fn (array $evaluation): bool => $evaluation['score'] !== null)); $averageScore = $scoredCompletedEvaluations ? (int) round(array_sum(array_column($scoredCompletedEvaluations, 'score')) / count($scoredCompletedEvaluations)) : 0; $bestScoreByEmployee = []; foreach ($scoredCompletedEvaluations as $evaluation) { $employeeId = (int) $evaluation['employee_id']; if (!isset($bestScoreByEmployee[$employeeId]) || $evaluation['score'] > $bestScoreByEmployee[$employeeId]['score']) { $bestScoreByEmployee[$employeeId] = $evaluation; } } $topPerformers = array_values($bestScoreByEmployee); usort($topPerformers, static fn (array $a, array $b): int => $b['score'] <=> $a['score']); ?>
                <section class="metric-grid three-columns">
                    <article class="card metric-card"><span>Average score</span><strong><?= $averageScore ?></strong><small><?= count($completedEvaluations) ?> completed evaluations</small></article>
                    <article class="card metric-card"><span>Awaiting acknowledgment</span><strong><?= count(array_filter($evaluations, static fn (array $evaluation): bool => $evaluation['status'] === 'Completed')) ?></strong><small>completed, not yet signed</small></article>
                    <article class="card metric-card"><span>Drafts in progress</span><strong><?= count(array_filter($evaluations, static fn (array $evaluation): bool => $evaluation['status'] === 'Draft')) ?></strong><small>not yet finalized</small></article>
                </section>
                <section class="performance-summary">
                    <article class="card performers-card"><div class="section-title"><h2>Top performers</h2><p>Best completed score per employee</p></div><ol class="performer-list"><?php foreach (array_slice($topPerformers, 0, 5) as $rank => $evaluation): ?><li><span class="rank-number"><?= $rank + 1 ?></span><span class="avatar <?= ems_h($evaluation['tone']) ?>"><?= ems_h($evaluation['initials']) ?></span><span class="performer-copy"><strong><?= ems_h($evaluation['employee']) ?></strong><small><?= ems_h($evaluation['department']) ?></small></span><span class="score-bar"><i style="width: <?= (int) $evaluation['score'] ?>%"></i></span><b><?= (int) $evaluation['score'] ?></b></li><?php endforeach; ?><?php if (!$topPerformers): ?><li class="empty-state">No completed scored evaluations yet.</li><?php endif; ?></ol></article>
                    <article class="card distribution-card"><div class="section-title"><h2>Score distribution</h2><p>Completed evaluations with a score</p></div><?php $scoreBands = [['90-100', 90, 100], ['75-89', 75, 89], ['60-74', 60, 74], ['<60', 0, 59]]; foreach ($scoreBands as [$label, $min, $max]): $bandCount = count(array_filter($scoredCompletedEvaluations, static fn (array $evaluation): bool => $evaluation['score'] >= $min && $evaluation['score'] <= $max)); ?><div class="distribution-row"><div><span><?= ems_h($label) ?></span><span><?= $bandCount ?></span></div><div class="distribution-track"><i style="width: <?= count($scoredCompletedEvaluations) ? (int) round($bandCount / count($scoredCompletedEvaluations) * 100) : 0 ?>%"></i></div></div><?php endforeach; ?><p class="distribution-note">90+ Outstanding &middot; 75-89 Exceeds &middot; 60-74 Meets &middot; below 60 needs attention.</p></article>
                </section>
                <h2 class="list-heading">All evaluations</h2>
                <div class="evaluation-browser" data-record-pager data-page-size="<?= (int) ($displayLimits['evaluation_page_size'] ?? 10) ?>">
                <section class="evaluation-list"><?php foreach ($evaluations as $evaluation): ?><article class="card evaluation-row" data-paginated-row><span class="avatar <?= ems_h($evaluation['tone']) ?>"><?= ems_h($evaluation['initials']) ?></span><div class="evaluation-copy"><strong><?= ems_h($evaluation['employee']) ?></strong><span><?= ems_h($evaluation['period']) ?></span><span class="pill <?= $evaluation['status'] === 'Acknowledged' ? 'mint' : ($evaluation['status'] === 'Draft' ? 'slate' : 'blue') ?>"><?= ems_h($evaluation['status']) ?></span><small>by <?= ems_h($evaluation['reviewer']) ?> &middot; <?= ems_h($evaluation['note']) ?></small><small>Attendance <?= $evaluation['attendance_pct'] === null ? '—' : ems_h($evaluation['attendance_pct']) . '%' ?> &middot; Training <?= $evaluation['training_completion_pct'] === null ? '—' : ems_h($evaluation['training_completion_pct']) . '%' ?></small><?php if ($evaluation['okr_achievement'] !== null): ?><small>OKR <?= ems_h($evaluation['okr_achievement']) ?> (50%) &middot; Competencies <?= ems_h($evaluation['core_competencies']) ?> (30%) &middot; Feedback <?= ems_h($evaluation['feedback_score']) ?> (20%)</small><?php endif; ?></div><div class="evaluation-score"><strong><?= $evaluation['score'] === null ? '—' : ems_h($evaluation['score']) ?></strong><small><?= $evaluation['score'] === null ? 'No score recorded' : ($evaluation['score'] >= 90 ? 'Outstanding' : ($evaluation['score'] >= 75 ? 'Exceeds expectations' : 'Meets expectations')) ?></small></div></article><?php endforeach; ?><p class="empty-state" data-pager-empty<?= $evaluations ? ' hidden' : '' ?>>No KPI evaluations have been recorded.</p></section>
                <div class="applicant-pagination"><span data-page-summary aria-live="polite"></span><div><button type="button" data-page-prev disabled>Previous</button><span data-page-numbers></span><span data-page-indicator></span><button type="button" data-page-next disabled>Next</button></div></div>
                </div>
                <section class="certification-section" aria-labelledby="certification-heading">
                    <div class="section-title"><h2 id="certification-heading">Certifications</h2><p>Professional credentials and renewal dates (preview data)</p></div>
                    <div class="certification-list">
                        <?php foreach ($certifications as $certification): ?>
                            <article class="card certification-card">
                                <span class="avatar <?= ems_h($certification['tone']) ?>"><?= ems_h($certification['initials']) ?></span>
                                <div class="certification-copy">
                                    <strong><?= ems_h($certification['name']) ?></strong>
                                    <span><?= ems_h($certification['employee']) ?> &middot; <?= ems_h($certification['issuer']) ?></span>
                                    <small>Issued <?= ems_h($certification['issued']) ?> &middot; Expires <?= ems_h($certification['expires']) ?></small>
                                </div>
                                <span class="pill <?= $certification['status'] === 'Current' ? 'mint' : 'amber' ?>"><?= ems_h($certification['status']) ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

            <?php elseif ($pageKey === 'hr-records'): ?>
                <section class="metric-grid three-columns">
                    <article class="card metric-card"><span>Needs resolution</span><strong><?= count(array_filter($hrRecords, static fn (array $record): bool => in_array($record['status'], ['Open', 'In Progress'], true))) ?></strong><small>open or in-progress records</small></article>
                    <article class="card metric-card"><span>Awaiting signature</span><strong><?= count(array_filter($hrRecords, static fn (array $record): bool => $record['signature'] === '')) ?></strong><small>records without e-signature</small></article>
                    <article class="card metric-card"><span>Commendations</span><strong><?= count(array_filter($hrRecords, static fn (array $record): bool => $record['type'] === 'Commendation')) ?></strong><small>formal recognition on file</small></article>
                </section>
                <nav class="filter-tabs" aria-label="Filter HR records"><?php foreach ($recordTypes as $type): $queryType = $recordTypeMap[$type] ?? 'All'; ?><a class="filter-tab<?= $recordType === $queryType ? ' active' : '' ?>" href="hr-records.php<?= $queryType === 'All' ? '' : '?type=' . rawurlencode($queryType) ?>"><?= ems_h($type) ?></a><?php endforeach; ?></nav>
                <section class="record-cards"><?php foreach ($visibleRecords as $record): ?><article class="card hr-record-card"><span class="avatar <?= ems_h($record['tone']) ?>"><?= ems_h($record['initials']) ?></span><div class="hr-record-copy"><div class="record-title-line"><strong><?= ems_h($record['title']) ?></strong><span class="pill <?= $record['type'] === 'Commendation' ? 'mint' : ($record['type'] === 'Violation' ? 'rose' : 'slate') ?>"><?= ems_h($record['type']) ?></span><?php if ($record['priority']): ?><span class="pill amber"><?= ems_h($record['priority']) ?></span><?php endif; ?></div><small><?= ems_h($record['employee']) ?> &middot; issued <?= ems_h($record['date']) ?></small><p><?= ems_h($record['description']) ?></p></div><div class="record-actions"><span class="pill <?= $record['status'] === 'Resolved' ? 'mint' : ($record['status'] === 'Open' ? 'rose' : 'amber') ?>"><?= ems_h($record['status']) ?></span><?php if ($record['signature']): ?><span class="signature-label"><?= ems_h($record['signature']) ?></span><?php else: ?><button type="button" class="small-button">Get signature</button><?php endif; ?></div></article><?php endforeach; ?><?php if (!$visibleRecords): ?><p class="empty-state">No records in this category.</p><?php endif; ?></section>

            <?php elseif ($pageKey === 'leave'): ?>
                <section class="metric-grid three-columns">
                    <article class="card metric-card"><span>Pending requests</span><strong><?= count($leaveRequests) ?></strong><small>awaiting your decision</small></article>
                    <article class="card metric-card"><span>Approved this year</span><strong><?= (int) ($leaveApprovedCount ?? 0) ?></strong><small>across all types</small></article>
                    <article class="card metric-card"><span>Employees tracked</span><strong><?= count($leaveBalances) ?></strong><small><?= (int) ($leaveYear ?? date('Y')) ?> balances</small></article>
                </section>
                <section class="card leave-table-section"><div class="section-title"><h2>Balances &middot; <?= (int) ($leaveYear ?? date('Y')) ?></h2><p>Remaining vacation, sick and personal days</p></div><div class="table-card compact-table-wrap"><table class="data-table compact-data-table leave-balance-table"><thead><tr><th>Employee</th><th>Vacation</th><th>Sick</th><th>Personal</th></tr></thead><tbody><?php foreach ($leaveBalances as $balance): ?><tr><td><span class="employee-cell"><span class="avatar <?= ems_h($balance['tone']) ?>"><?= ems_h($balance['initials']) ?></span><strong><?= ems_h($balance['name']) ?></strong></span></td><?php foreach (['vacation', 'sick', 'personal'] as $key): ?><td><strong><?= (int) $balance[$key] ?></strong><small>days remaining</small></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></section>
                <h2 class="list-heading">Approval queue</h2>
                <section class="card leave-table-section"><div class="table-card compact-table-wrap"><table class="data-table compact-data-table"><thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Duration</th><th>Note</th><th>Actions</th></tr></thead><tbody><?php foreach ($leaveRequests as $request): ?><tr><td><span class="employee-cell"><span class="avatar <?= ems_h($request['tone']) ?>"><?= ems_h($request['initials']) ?></span><strong><?= ems_h($request['employee']) ?></strong></span></td><td><span class="pill <?= $request['type'] === 'Vacation' ? 'cyan' : 'violet' ?>"><?= ems_h($request['type']) ?></span></td><td><?= ems_h($request['from']) ?> &ndash; <?= ems_h($request['to']) ?></td><td><?= (int) $request['days'] ?> <?= $request['days'] === 1 ? 'day' : 'days' ?></td><td><?= $request['note'] ? ems_h($request['note']) : '<span class="muted">—</span>' ?></td><td><?php if (!empty($leaveCanDecide)): ?><div class="compact-leave-actions"><form method="post" action="leave.php" style="display:contents"><input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>"><input type="hidden" name="leave_id" value="<?= (int) $request['leave_id'] ?>"><button type="submit" name="leave_action" value="approve" class="approve-button">&#10003; Approve</button><button type="submit" name="leave_action" value="reject" class="small-button" onclick="return confirm('Reject this leave request?')">Reject</button></form></div><?php else: ?><span class="muted">Awaiting review</span><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$leaveRequests): ?><tr><td colspan="6" class="empty-state">No leave requests are awaiting review.</td></tr><?php endif; ?></tbody></table></div></section>
                <h2 class="list-heading">Decision history</h2>
                <section class="card leave-table-section"><div class="table-card compact-table-wrap"><table class="data-table compact-data-table"><thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Duration</th><th>Decision</th><th>Note</th></tr></thead><tbody><?php foreach ($leaveHistory as $request): ?><tr><td><span class="employee-cell"><span class="avatar <?= ems_h($request['tone']) ?>"><?= ems_h($request['initials']) ?></span><strong><?= ems_h($request['employee']) ?></strong></span></td><td><?= ems_h($request['type']) ?></td><td><?= ems_h($request['dates']) ?></td><td><?= (int) $request['days'] ?> days</td><td><span class="pill <?= $request['status'] === 'Approved' ? 'mint' : 'rose' ?>"><?= ems_h($request['status']) ?></span></td><td><?= $request['note'] ? ems_h($request['note']) : '<span class="muted">—</span>' ?></td></tr><?php endforeach; ?><?php if (!$leaveHistory): ?><tr><td colspan="6" class="empty-state">No leave decisions have been recorded.</td></tr><?php endif; ?></tbody></table></div></section>

            <?php elseif ($pageKey === 'settings'): ?>
                <?php if ($settingsCanWrite): ?>
                    <section class="card settings-create-section">
                        <div class="section-title"><h2>Hiring pipeline capacity</h2><p>Maximum candidates actively handled in each stage. Additional candidates remain queued in arrival order.</p></div>
                        <form method="post" action="settings.php" class="settings-create-form">
                            <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="settings_action" value="update_hiring_capacity">
                            <label>Candidates per stage<input type="number" name="hiring_stage_capacity" min="1" max="50" step="1" value="<?= (int) $hiringStageCapacity ?>" required></label>
                            <button class="btn-primary" type="submit">Save capacity</button>
                        </form>
                    </section>
                    <section class="card settings-create-section">
                        <div class="section-title"><h2>Dashboard and history limits</h2><p>Limit chart density and set the number of employee and evaluation records shown on each page.</p></div>
                        <form method="post" action="settings.php" class="settings-create-form">
                            <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="settings_action" value="update_display_limits">
                            <label>Attendance trend months<input type="number" name="attendance_month_limit" min="1" max="60" step="1" value="<?= (int) $displayLimits['attendance_month_limit'] ?>" required></label>
                            <label>Departments in chart<input type="number" name="department_chart_limit" min="1" max="50" step="1" value="<?= (int) $displayLimits['department_chart_limit'] ?>" required></label>
                            <label>Evaluations per page<input type="number" name="evaluation_page_size" min="5" max="100" step="1" value="<?= (int) $displayLimits['evaluation_page_size'] ?>" required></label>
                            <label>Employees per page<input type="number" name="employee_page_size" min="5" max="100" step="1" value="<?= (int) $displayLimits['employee_page_size'] ?>" required></label>
                            <button class="btn-primary" type="submit">Save display limits</button>
                        </form>
                    </section>
                    <section class="card settings-create-section">
                        <div class="section-title"><h2>Add department</h2><p>Department names and codes must be unique.</p></div>
                        <form method="post" action="settings.php" class="settings-create-form">
                            <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="settings_action" value="create_department">
                            <label>Department name<input type="text" name="department_name" maxlength="100" required></label>
                            <label>Department code<input type="text" name="department_code" maxlength="20" required></label>
                            <label>Description<input type="text" name="description" maxlength="65535"></label>
                            <button class="btn-primary" type="submit">Create department</button>
                        </form>
                    </section>
                <?php endif; ?>
                <section class="card appearance-card" aria-labelledby="appearance-title">
                    <div class="appearance-copy">
                        <h2 id="appearance-title">Appearance</h2>
                        <p>Choose a workspace theme. Your preference stays with you across EMS modules.</p>
                    </div>
                    <label class="appearance-control" for="theme-select">
                        <span>Workspace theme</span>
                        <select id="theme-select" data-theme-select>
                            <option value="light">Default Light</option>
                            <option value="dark-sidebar">Modern Dark Sidebar</option>
                        </select>
                    </label>
                </section>
                <section class="card settings-table-section"><div class="settings-table-heading"><div><h2>Job titles &amp; salary bands</h2><p>Configured roles by department and career level</p></div><span><?= count($jobTitles) ?> roles</span></div><div class="table-card compact-table-wrap"><table class="data-table settings-table"><thead><tr><th>Role</th><th>Department</th><th>Level</th><th>Salary Band</th></tr></thead><tbody><?php foreach ($jobTitles as $role): ?><tr><td><strong><?= ems_h($role['title']) ?></strong><small><?= ems_h($role['description']) ?></small></td><td><?= ems_h($role['department']) ?></td><td><span class="pill slate"><?= ems_h($role['level']) ?></span></td><td><?= ems_money($role['salary']) ?><small>annual base salary</small></td></tr><?php endforeach; ?></tbody></table></div></section>

            <?php elseif ($pageKey === 'reports'): ?>
                <section class="metric-grid report-metrics">
                    <article class="card metric-card"><span>Headcount</span><strong><?= count($activeEmployees) ?></strong><small>active and probationary employees</small></article>
                    <article class="card metric-card"><span>Annual base payroll</span><strong><?= ems_money($annualPayroll) ?></strong><small>across <?= count($activeEmployees) ?> employees</small></article>
                    <article class="card metric-card"><span>Turnover rate</span><strong>—</strong><small>Not available from the current schema</small></article>
                    <article class="card metric-card"><span>Avg. time to hire</span><strong><?= $averageTimeToHire ?> days</strong><small><?= count($hiringHistory) ?> completed hires</small></article>
                </section>
                <section class="report-grid">
                    <article class="card report-card"><div class="report-card-heading"><div><h2>Headcount by department</h2><p>Current employee distribution</p></div><button type="button" class="small-button" data-export-table="headcount-report.csv">Export CSV</button></div><div class="table-card report-table-wrap"><table class="data-table report-table" data-report-table><thead><tr><th>Department</th><th>Employees</th><th>Share</th></tr></thead><tbody><?php foreach ($headcountByDepartment as $department => $count): ?><tr><td><?= ems_h($department) ?></td><td><?= $count ?></td><td><?= count($activeEmployees) ? number_format($count / count($activeEmployees) * 100, 1) : '0.0' ?>%</td></tr><?php endforeach; ?><tr><th>Total</th><th><?= count($activeEmployees) ?></th><th><?= count($activeEmployees) ? '100%' : '0%' ?></th></tr></tbody></table></div></article>
                    <article class="card report-card"><div class="report-card-heading"><div><h2>Payroll distribution</h2><p>Annual base payroll by department</p></div><button type="button" class="small-button" data-export-table="payroll-distribution.csv">Export CSV</button></div><div class="table-card report-table-wrap"><table class="data-table report-table" data-report-table><thead><tr><th>Department</th><th>Annual payroll</th><th>Share</th></tr></thead><tbody><?php foreach ($payrollByDepartment as $department => $amount): ?><tr><td><?= ems_h($department) ?></td><td><?= ems_money($amount) ?></td><td><?= $annualPayroll ? number_format($amount / $annualPayroll * 100, 1) : '0.0' ?>%</td></tr><?php endforeach; ?><tr><th>Total</th><th><?= ems_money($annualPayroll) ?></th><th>100%</th></tr></tbody></table></div></article>
                    <article class="card report-card"><div class="report-card-heading"><div><h2>Turnover snapshot</h2><p>Not available from the current schema</p></div><button type="button" class="small-button" data-export-table="turnover-snapshot.csv">Export CSV</button></div><div class="table-card report-table-wrap"><table class="data-table report-table" data-report-table><thead><tr><th>Period</th><th>Avg. headcount</th><th>Separations</th><th>Turnover</th></tr></thead><tbody><tr><td colspan="4" class="empty-state">Separation history is not available in the report views.</td></tr></tbody></table></div></article>
                    <article class="card report-card"><div class="report-card-heading"><div><h2>Hiring velocity</h2><p>Days from application to hire</p></div><button type="button" class="small-button" data-export-table="hiring-velocity.csv">Export CSV</button></div><div class="table-card report-table-wrap"><table class="data-table report-table" data-report-table><thead><tr><th>Completed hires</th><th>Average days</th><th>Fastest</th></tr></thead><tbody><tr><td><?= count($hiringHistory) ?></td><td><?= $averageTimeToHire ?></td><td><?= $fastestTimeToHire ?></td></tr></tbody></table></div></article>
                </section>
                <section class="card audit-section"><div class="report-card-heading"><div><h2>Compensation history</h2><p>Preview data. The audit trail is owned by the Payroll group.</p></div></div><div class="table-card report-table-wrap"><table class="data-table audit-table" data-report-table><thead><tr><th>Date &amp; time</th><th>HR action</th><th>Employee</th><th>Annual salary change</th><th>Changed by</th><th>Payroll handoff</th></tr></thead><tbody><?php foreach ($payrollAuditLogs as $log): ?><tr><td><?= ems_h($log['time']) ?></td><td><?= ems_h($log['action']) ?></td><td><?= ems_h($log['employee']) ?></td><td><?= ems_h($log['change']) ?></td><td><?= ems_h($log['by']) ?></td><td><span class="pill <?= $log['handoff'] === 'Sent to Payroll' ? 'mint' : 'amber' ?>"><?= ems_h($log['handoff']) ?></span></td></tr><?php endforeach; ?><?php if (!$payrollAuditLogs): ?><tr><td colspan="6" class="empty-state">No audit-trail data is available.</td></tr><?php endif; ?></tbody></table></div></section>
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
    <?php elseif ($dialogConfig && ($pageKey !== 'settings' || $settingsCanWrite) && ($pageKey !== 'performance' || $performanceCanWrite)): ?>
        <dialog class="dashboard-dialog" id="dashboard-form-dialog" aria-labelledby="dialog-title">
            <form class="dialog-form" method="<?= in_array($pageKey, ['hiring', 'settings', 'performance', 'leave'], true) ? 'post' : 'dialog' ?>"<?= $pageKey === 'hiring' ? ' action="hiring.php"' : ($pageKey === 'settings' ? ' action="settings.php"' : ($pageKey === 'performance' ? ' action="performance.php"' : ($pageKey === 'leave' ? ' action="leave.php"' : ' data-preview-form'))) ?>>
                <header class="dialog-header"><div><h2 id="dialog-title"><?= ems_h($dialogConfig['title']) ?></h2><p><?= ems_h($dialogConfig['description']) ?></p></div><button class="dialog-close" type="button" data-dialog-close aria-label="Close">&times;</button></header>
                <?php if ($pageKey === 'hiring'): ?>
                    <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="hiring_action" value="create">
                <?php elseif ($pageKey === 'settings'): ?>
                    <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="settings_action" value="create_job_title">
                <?php elseif ($pageKey === 'performance'): ?>
                    <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="performance_action" value="create">
                <?php elseif ($pageKey === 'leave'): ?>
                    <input type="hidden" name="csrf_token" value="<?= ems_h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="leave_action" value="create">
                <?php endif; ?>
                <div class="dialog-fields">
                    <?php foreach ($dialogConfig['fields'] as $field): ?>
                        <label class="dialog-field<?= !empty($field['wide']) ? ' wide' : '' ?>">
                            <span><?= ems_h($field['label']) ?></span>
                            <?php if ($field['type'] === 'select'): ?>
                                <select name="<?= ems_h($field['name']) ?>"<?= !empty($field['required']) ? ' required' : '' ?>><option value=""><?= ems_h($field['placeholder']) ?></option><?php foreach ($field['options'] as $optionValue => $optionLabel): $hasIdOptions = in_array($field['name'], ['job_title_id', 'department_id', 'employee_id'], true); $selectValue = $hasIdOptions ? (string) $optionValue : (is_int($optionValue) ? $optionLabel : $optionValue); ?><option value="<?= ems_h($selectValue) ?>"><?= ems_h($optionLabel) ?></option><?php endforeach; ?></select>
                            <?php elseif ($field['type'] === 'textarea'): ?>
                                <textarea name="<?= ems_h($field['name']) ?>" placeholder="<?= ems_h($field['placeholder']) ?>" rows="3"<?= !empty($field['required']) ? ' required' : '' ?>></textarea>
                            <?php else: ?>
                                <input type="<?= ems_h($field['type']) ?>" name="<?= ems_h($field['name']) ?>"<?= isset($field['placeholder']) ? ' placeholder="' . ems_h($field['placeholder']) . '"' : '' ?><?= isset($field['min']) ? ' min="' . ems_h($field['min']) . '"' : '' ?><?= isset($field['max']) ? ' max="' . ems_h($field['max']) . '"' : '' ?><?= isset($field['maxlength']) ? ' maxlength="' . ems_h($field['maxlength']) . '"' : '' ?><?= isset($field['step']) ? ' step="' . ems_h($field['step']) . '"' : '' ?><?= !empty($field['required']) ? ' required' : '' ?>>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="dialog-feedback" role="status" aria-live="polite"></p>
                <footer class="dialog-actions"><button class="dialog-cancel" type="button" data-dialog-close>Cancel</button><button class="btn-primary" type="submit"><?= ems_h($dialogConfig['submit']) ?></button></footer>
            </form>
        </dialog>
    <?php endif; ?>
    <script src="dashboard.js?v=<?= (int) filemtime(__DIR__ . '/dashboard.js') ?>" defer></script>
</body>
</html>