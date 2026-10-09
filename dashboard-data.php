<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/dashboard-helpers.php';

$dashboardDataIsApiRequest = isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__;

if ($dashboardDataIsApiRequest) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');

    if (!ems_is_authenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required.']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed.']);
        exit;
    }
} else {
    ems_require_authentication();
}

require_once __DIR__ . '/db.php';

try {
    $pdo = ems_db();
    $displayLimitDefaults = [
        'attendance_month_limit' => 12,
        'department_chart_limit' => 10,
        'evaluation_page_size' => 10,
        'employee_page_size' => 10,
    ];
    $displayLimitSettingNames = array_map(
        static fn (string $key): string => 'dashboard_' . $key,
        array_keys($displayLimitDefaults)
    );
    $displayLimitPlaceholders = implode(',', array_fill(0, count($displayLimitSettingNames), '?'));
    $displayLimitQuery = $pdo->prepare(
        'SELECT setting_name, setting_value
         FROM settings
         WHERE setting_name IN (' . $displayLimitPlaceholders . ')'
    );
    $displayLimitQuery->execute($displayLimitSettingNames);
    $storedDisplayLimits = $displayLimitQuery->fetchAll(PDO::FETCH_KEY_PAIR);
    $displayLimits = [];
    foreach ($displayLimitDefaults as $key => $default) {
        $settingName = 'dashboard_' . $key;
        $minimum = str_ends_with($key, '_page_size') ? 5 : 1;
        $maximum = str_ends_with($key, '_page_size') ? 100 : ($key === 'attendance_month_limit' ? 60 : 50);
        $storedValue = $storedDisplayLimits[$settingName] ?? null;
        $validatedValue = $storedValue === null
            ? $default
            : filter_var($storedValue, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => $minimum, 'max_range' => $maximum],
            ]);
        if ($validatedValue === false) {
            throw new RuntimeException('A dashboard display limit setting is invalid.');
        }
        $displayLimits[$key] = (int) $validatedValue;
    }

    if ($dashboardDataIsApiRequest) {
        $dashboardCollections = [
            'pipeline_counts' => $pdo
                ->query('SELECT status, total FROM v_pipeline_counts ORDER BY status')
                ->fetchAll(PDO::FETCH_ASSOC),
            'headcount_by_department' => $pdo
                ->query('SELECT department_id, department_name, headcount FROM v_headcount_by_department')
                ->fetchAll(PDO::FETCH_ASSOC),
            'attendance_pct' => $pdo
                ->query('SELECT employee_id, attendance_month, days_present, days_absent, attendance_pct FROM v_attendance_pct ORDER BY attendance_month DESC, employee_id')
                ->fetchAll(PDO::FETCH_ASSOC),
            'current_base_salary' => $pdo
                ->query('SELECT employee_id, full_name, job_title_name, base_salary, currency, effective_date FROM v_current_base_salary')
                ->fetchAll(PDO::FETCH_ASSOC),
            'display_limits' => $displayLimits,
        ];

        $json = json_encode(
            ['data' => $dashboardCollections],
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
        );
        echo $json;
        exit;
    }

    if (!isset($employees)) {
        $employees = $pdo->query(
        "SELECT e.employee_id, e.full_name AS name, e.email,
                COALESCE(j.job_title_name, 'Unassigned') AS role,
                COALESCE(d.department_name, 'Unassigned') AS department,
                e.employment_status AS status,
                COALESCE(s.base_salary, 0) AS salary,
                DATE_FORMAT(e.hire_date, '%b %e, %Y') AS hired
         FROM employees e
         LEFT JOIN departments d ON d.department_id = e.department_id
         LEFT JOIN job_titles j ON j.job_title_id = e.job_title_id
         LEFT JOIN v_current_base_salary s ON s.employee_id = e.employee_id
         ORDER BY e.last_name, e.first_name"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($employees as &$employee) {
            $employee['salary'] = (float) $employee['salary'];
            $employee['tone'] = ems_avatar_tone((int) $employee['employee_id']);
            $employee['initials'] = ems_initials((string) $employee['name']);
        }
        unset($employee);
    }

    $stageCapacityQuery = $pdo->prepare(
        'SELECT setting_value FROM settings WHERE setting_name = :setting_name'
    );
    $stageCapacityQuery->execute(['setting_name' => 'hiring_stage_capacity']);
    $stageCapacityValue = $stageCapacityQuery->fetchColumn();
    $hiringStageCapacity = $stageCapacityValue === false
        ? 1
        : filter_var($stageCapacityValue, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 50],
        ]);
    if ($hiringStageCapacity === false) {
        throw new RuntimeException('Hiring stage capacity setting is invalid.');
    }

    $candidates = $pdo->query(
        "SELECT h.hiring_id, h.applicant_name AS name, h.email,
                COALESCE(j.job_title_name, 'Unassigned') AS role,
                COALESCE(d.department_name, 'Unassigned') AS department,
                CASE h.status WHEN 'Interview' THEN 'Interviewing' ELSE h.status END AS stage,
                DATE_FORMAT(h.application_date, '%b %e, %Y') AS applied,
                GREATEST(DATEDIFF(CURRENT_DATE, h.application_date), 0) AS days,
                h.updated_at AS queued_at, h.created_at
         FROM hiring_pipeline h
         LEFT JOIN job_titles j ON j.job_title_id = h.applied_job_title_id
         LEFT JOIN departments d ON d.department_id = j.department_id
         WHERE h.status IN ('Applied', 'Screening', 'Interview', 'Offer')
         ORDER BY h.updated_at, h.created_at, h.hiring_id"
    )->fetchAll(PDO::FETCH_ASSOC);
    $stageQueuePositions = [];
    foreach ($candidates as &$candidate) {
        $candidate['days'] = (int) $candidate['days'];
        $candidate['queue_position'] = ($stageQueuePositions[$candidate['stage']] ?? 0) + 1;
        $candidate['is_stage_active'] = $candidate['queue_position'] <= $hiringStageCapacity;
        $stageQueuePositions[$candidate['stage']] = $candidate['queue_position'];
    }
    unset($candidate);

    $candidateArchive = $pdo->query(
        "SELECT u.hiring_id, u.applicant_name AS name, u.email,
                COALESCE(u.applied_job_title, 'Unassigned') AS role,
                COALESCE(d.department_name, 'Unassigned') AS department,
                DATE_FORMAT(u.application_date, '%b %e, %Y') AS applied,
                CASE u.outcome WHEN 'Withdrawn' THEN 'Withdrew' ELSE u.outcome END AS outcome,
                DATE_FORMAT(u.decision_date, '%b %e, %Y') AS closed
         FROM v_unhired_applicants u
         LEFT JOIN hiring_pipeline h ON h.hiring_id = u.hiring_id
         LEFT JOIN job_titles t ON t.job_title_id = h.applied_job_title_id
         LEFT JOIN departments d ON d.department_id = t.department_id
         ORDER BY u.decision_date DESC, u.hiring_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $hiringHistory = $pdo->query(
        "SELECT h.hiring_id, h.applicant_name AS name, h.email,
                COALESCE(j.job_title_name, 'Unassigned') AS role,
                DATE_FORMAT(h.application_date, '%b %e, %Y') AS applied,
                DATE_FORMAT(h.hire_date, '%b %e, %Y') AS hired,
                GREATEST(DATEDIFF(h.hire_date, h.application_date), 0) AS days
         FROM hiring_pipeline h
         LEFT JOIN job_titles j ON j.job_title_id = h.applied_job_title_id
         WHERE h.status = 'Hired' AND h.hire_date IS NOT NULL
         ORDER BY h.hire_date DESC, h.hiring_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hiringHistory as &$hire) {
        $hire['days'] = (int) $hire['days'];
    }
    unset($hire);

    $evaluations = $pdo->query(
        "SELECT k.kpi_id, e.full_name AS employee, e.employee_id,
                COALESCE(d.department_name, 'Unassigned') AS department,
                k.review_period AS period, k.attendance_pct, k.training_completion_pct,
                k.kpi_score AS score,
                k.status, COALESCE(ev.full_name, 'Unassigned') AS reviewer,
                COALESCE(k.comments, '') AS note
         FROM kpi_records k
         JOIN employees e ON e.employee_id = k.employee_id
         LEFT JOIN departments d ON d.department_id = e.department_id
         LEFT JOIN employees ev ON ev.employee_id = k.evaluator_id
         ORDER BY k.review_period DESC, e.last_name, e.first_name"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($evaluations as &$evaluation) {
        $evaluation['score'] = $evaluation['score'] === null ? null : (float) $evaluation['score'];
        $evaluation['attendance_pct'] = $evaluation['attendance_pct'] === null
            ? null
            : (float) $evaluation['attendance_pct'];
        $evaluation['training_completion_pct'] = $evaluation['training_completion_pct'] === null
            ? null
            : (float) $evaluation['training_completion_pct'];
        $evaluation['tone'] = ems_avatar_tone((int) $evaluation['employee_id']);
        $evaluation['initials'] = ems_initials((string) $evaluation['employee']);
    }
    unset($evaluation);

    $jobTitles = $pdo->query(
        "SELECT j.job_title_id,
                d.department_name AS department,
                j.job_title_name AS title,
                j.level,
                COALESCE(j.job_description, '') AS description,
                COALESCE(j.max_salary, j.min_salary, 0) AS salary,
                (SELECT COUNT(*) FROM employees e WHERE e.job_title_id = j.job_title_id) AS count
         FROM job_titles j
         JOIN departments d ON d.department_id = j.department_id
         WHERE j.is_active = 1
         ORDER BY d.department_name, j.job_title_name"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($jobTitles as &$jobTitle) {
        $jobTitle['salary'] = (float) $jobTitle['salary'];
        $jobTitle['count'] = (int) $jobTitle['count'];
    }
    unset($jobTitle);

    // Pages with no database tables use placeholder data (see preview-data.php).
    require_once __DIR__ . '/preview-data.php';
    $previewData = ems_preview_data();
    $onboardingPeople = $previewData['onboardingPeople'];
    $onboardingTaskLists = $previewData['onboardingTaskLists'];
    $certifications = $previewData['certifications'];
    $hrRecords = $previewData['hrRecords'];
    $leaveRequests = $leaveRequests ?? $previewData['leaveRequests']; // real data is set by leave.php
    $leaveHistory = $leaveHistory ?? $previewData['leaveHistory']; // real data is set by leave.php
    $leaveBalances = $leaveBalances ?? $previewData['leaveBalances']; // real data is set by leave.php
    $payrollAuditLogs = $previewData['payrollAuditLogs'];
} catch (Throwable $exception) {
    error_log('EMS dashboard data query failed (' . get_class($exception) . ').');

    if ($dashboardDataIsApiRequest) {
        http_response_code(500);
        echo json_encode(['error' => 'Dashboard data is temporarily unavailable.']);
        exit;
    }

    http_response_code(503);
    exit('Dashboard data is temporarily unavailable.');
}