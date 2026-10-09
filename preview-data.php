<?php
declare(strict_types=1);

require_once __DIR__ . '/dashboard-helpers.php';

/**
 * PREVIEW / PLACEHOLDER DATA
 *
 * These pages have no tables in ems_db, so they cannot read from the database:
 *   - Onboarding      (not in our assigned scope)
 *   - HR Records      (not in our assigned scope)
 *   - Leave           (owned by the Leave group)
 *   - Certifications  (Performance page)
 *   - Payroll trail   (Reports page, owned by the Payroll group)
 *
 * The data below is fake and only for showcase. When the real tables or the
 * other groups' modules exist, replace the arrays in dashboard-data.php with
 * real queries and delete this file.
 */
function ems_preview_data(): array
{
    $who = static function (string $name, int $seed): array {
        return ['initials' => ems_initials($name), 'tone' => ems_avatar_tone($seed)];
    };

    // ---- Onboarding -------------------------------------------------------
    $onboardingPeople = [
        [
            'name' => 'Maria Santos', 'started' => 'Oct 1, 2026', 'buddy' => 'Paolo Ramos',
            'groups' => [
                'Orientation' => [['Welcome meeting', true], ['Office tour', true], ['Meet the team', true]],
                'Requirements' => [['Signed employment contract', true], ['Government ID forms submitted', true], ['Bank details submitted', false]],
                'Training' => [['Company overview', true], ['Systems access training', false], ['Safety briefing', false]],
                'Policies' => [['Code of conduct', true], ['Data privacy policy', false], ['Attendance policy', false]],
            ],
        ],
        [
            'name' => 'Juan Dela Cruz', 'started' => 'Sep 15, 2026', 'buddy' => 'Liza Garcia',
            'groups' => [
                'Orientation' => [['Welcome meeting', true], ['Office tour', true], ['Meet the team', true]],
                'Requirements' => [['Signed employment contract', true], ['Government ID forms submitted', true], ['Bank details submitted', true]],
                'Training' => [['Company overview', true], ['Systems access training', true], ['Safety briefing', true]],
                'Policies' => [['Code of conduct', true], ['Data privacy policy', true], ['Attendance policy', false]],
            ],
        ],
        [
            'name' => 'Ana Reyes', 'started' => 'Oct 5, 2026', 'buddy' => 'Carlo Mendoza',
            'groups' => [
                'Orientation' => [['Welcome meeting', true], ['Office tour', false], ['Meet the team', false]],
                'Requirements' => [['Signed employment contract', false], ['Government ID forms submitted', false], ['Bank details submitted', false]],
                'Training' => [['Company overview', false], ['Systems access training', false], ['Safety briefing', false]],
                'Policies' => [['Code of conduct', false], ['Data privacy policy', false], ['Attendance policy', false]],
            ],
        ],
    ];
    foreach ($onboardingPeople as $index => &$person) {
        $person += $who($person['name'], $index + 1);
    }
    unset($person);

    $onboardingTaskLists = [
        'Pre-Onboarding' => [
            ['name' => 'Send welcome email', 'assigned' => true, 'date' => '09/28/2026'],
            ['name' => 'Prepare workstation', 'assigned' => true, 'date' => '09/29/2026'],
            ['name' => 'Create system accounts', 'assigned' => false, 'date' => '--'],
        ],
        'Onboarding' => [
            ['name' => 'Orientation session', 'assigned' => true, 'date' => '10/01/2026'],
            ['name' => 'Assign buddy', 'assigned' => true, 'date' => '10/01/2026'],
            ['name' => 'Complete requirements checklist', 'assigned' => false, 'date' => '--'],
        ],
        'Post-Onboarding' => [
            ['name' => '30-day check-in', 'assigned' => false, 'date' => '--'],
            ['name' => 'Probation goals review', 'assigned' => false, 'date' => '--'],
            ['name' => 'Feedback survey', 'assigned' => false, 'date' => '--'],
        ],
    ];

    // ---- Certifications (Performance page) ---------------------------------
    $certificationRows = [
        ['Project Management Professional', 'Liza Garcia', 'PMI', 'Mar 10, 2025', 'Mar 10, 2028', 'Current'],
        ['AWS Cloud Practitioner', 'Carlo Mendoza', 'Amazon Web Services', 'Nov 2, 2023', 'Nov 2, 2026', 'Expiring soon'],
        ['Certified Public Accountant', 'Ana Reyes', 'PRC', 'Jan 20, 2024', 'Jan 20, 2027', 'Current'],
        ['First Aid and Safety', 'Paolo Ramos', 'Red Cross', 'Jun 5, 2025', 'Jun 5, 2026', 'Expiring soon'],
    ];
    $certifications = [];
    foreach ($certificationRows as $index => [$name, $employee, $issuer, $issued, $expires, $status]) {
        $certifications[] = [
            'name' => $name, 'employee' => $employee, 'issuer' => $issuer,
            'issued' => $issued, 'expires' => $expires, 'status' => $status,
        ] + $who($employee, $index + 2);
    }

    // ---- HR records --------------------------------------------------------
    $hrRows = [
        ['Late arrival warning', 'Warning', 'High', 'Carlo Mendoza', 'Oct 2, 2026', 'Third late arrival this month. Verbal warning given.', 'Open', ''],
        ['Employee of the month', 'Commendation', '', 'Liza Garcia', 'Oct 1, 2026', 'Recognized for leading the Q3 release on schedule.', 'Resolved', 'E-signed Oct 1'],
        ['Policy reminder: remote work', 'Memo', '', 'All staff', 'Sep 28, 2026', 'Reminder of the updated remote work policy.', 'Resolved', 'E-signed Sep 28'],
        ['Data privacy violation', 'Violation', 'High', 'Paolo Ramos', 'Sep 25, 2026', 'Customer file shared through a personal email address.', 'In Progress', ''],
        ['Safety briefing missed', 'Warning', '', 'Ana Reyes', 'Sep 20, 2026', 'Did not attend the mandatory safety briefing.', 'In Progress', 'E-signed Sep 21'],
        ['Customer praise', 'Commendation', '', 'Juan Dela Cruz', 'Sep 12, 2026', 'Received three positive customer reviews in one week.', 'Resolved', 'E-signed Sep 12'],
    ];
    $hrRecords = [];
    foreach ($hrRows as $index => [$title, $type, $priority, $employee, $date, $description, $status, $signature]) {
        $hrRecords[] = [
            'title' => $title, 'type' => $type, 'priority' => $priority, 'employee' => $employee,
            'date' => $date, 'description' => $description, 'status' => $status, 'signature' => $signature,
        ] + $who($employee, $index + 1);
    }

    // ---- Leave -------------------------------------------------------------
    $requestRows = [
        ['Maria Santos', 'Vacation', 'Oct 20, 2026', 'Oct 24, 2026', 5, 'Family trip'],
        ['Carlo Mendoza', 'Sick', 'Oct 12, 2026', 'Oct 13, 2026', 2, 'Medical appointment'],
        ['Liza Garcia', 'Personal', 'Nov 3, 2026', 'Nov 3, 2026', 1, ''],
    ];
    $leaveRequests = [];
    foreach ($requestRows as $index => [$employee, $type, $from, $to, $days, $note]) {
        $leaveRequests[] = [
            'employee' => $employee, 'type' => $type, 'from' => $from, 'to' => $to,
            'days' => $days, 'note' => $note,
        ] + $who($employee, $index + 3);
    }

    $historyRows = [
        ['Juan Dela Cruz', 'Vacation', 'Sep 1 - Sep 5, 2026', 5, 'Approved', ''],
        ['Paolo Ramos', 'Sick', 'Sep 9, 2026', 1, 'Approved', 'Medical certificate on file'],
        ['Ana Reyes', 'Personal', 'Sep 14, 2026', 1, 'Rejected', 'Overlaps with a team deadline'],
    ];
    $leaveHistory = [];
    foreach ($historyRows as $index => [$employee, $type, $dates, $days, $status, $note]) {
        $leaveHistory[] = [
            'employee' => $employee, 'type' => $type, 'dates' => $dates,
            'days' => $days, 'status' => $status, 'note' => $note,
        ] + $who($employee, $index + 1);
    }

    $balanceRows = [
        ['Maria Santos', 10, 7, 3],
        ['Juan Dela Cruz', 5, 8, 2],
        ['Ana Reyes', 12, 10, 4],
        ['Carlo Mendoza', 8, 5, 3],
        ['Liza Garcia', 14, 9, 2],
    ];
    $leaveBalances = [];
    foreach ($balanceRows as $index => [$name, $vacation, $sick, $personal]) {
        $leaveBalances[] = [
            'name' => $name, 'vacation' => $vacation, 'sick' => $sick, 'personal' => $personal,
        ] + $who($name, $index + 2);
    }

    // ---- Payroll trail (Reports page) ---------------------------------------
    $payrollAuditLogs = [
        ['time' => 'Oct 8, 2026 3:12 PM', 'action' => 'Annual salary adjustment', 'employee' => 'Liza Garcia', 'change' => 'PHP 720,000 to PHP 780,000', 'by' => 'HR Admin', 'handoff' => 'Sent to Payroll'],
        ['time' => 'Oct 6, 2026 10:40 AM', 'action' => 'New hire base salary', 'employee' => 'Maria Santos', 'change' => 'PHP 0 to PHP 540,000', 'by' => 'HR Admin', 'handoff' => 'Sent to Payroll'],
        ['time' => 'Oct 2, 2026 9:05 AM', 'action' => 'Promotion adjustment', 'employee' => 'Carlo Mendoza', 'change' => 'PHP 600,000 to PHP 660,000', 'by' => 'HR Admin', 'handoff' => 'Pending'],
        ['time' => 'Sep 28, 2026 1:30 PM', 'action' => 'Probation completed', 'employee' => 'Paolo Ramos', 'change' => 'PHP 480,000 to PHP 520,000', 'by' => 'HR Admin', 'handoff' => 'Pending'],
    ];

    return [
        'onboardingPeople' => $onboardingPeople,
        'onboardingTaskLists' => $onboardingTaskLists,
        'certifications' => $certifications,
        'hrRecords' => $hrRecords,
        'leaveRequests' => $leaveRequests,
        'leaveHistory' => $leaveHistory,
        'leaveBalances' => $leaveBalances,
        'payrollAuditLogs' => $payrollAuditLogs,
    ];
}