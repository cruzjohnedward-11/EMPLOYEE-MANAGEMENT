<?php
$employees = [
    ['name' => 'Amara Chen', 'email' => 'amara.chen@acme.co', 'role' => 'Chief Executive Officer', 'department' => 'Executive', 'status' => 'Active', 'salary' => 8400000, 'hired' => 'Jan 14, 2019', 'initials' => 'AC', 'tone' => 'mint'],
    ['name' => 'Devon Park', 'email' => 'devon.park@acme.co', 'role' => 'Engineering Manager', 'department' => 'Engineering', 'status' => 'Active', 'salary' => 4800000, 'hired' => 'Mar 2, 2020', 'initials' => 'DP', 'tone' => 'cyan'],
    ['name' => 'Liam Fitzgerald', 'email' => 'liam.fitzgerald@acme.co', 'role' => 'Software Engineer', 'department' => 'Engineering', 'status' => 'Probation', 'salary' => 2400000, 'hired' => 'Nov 4, 2024', 'initials' => 'LF', 'tone' => 'violet'],
    ['name' => 'Maya Okafor', 'email' => 'maya.okafor@acme.co', 'role' => 'Product Designer', 'department' => 'Design', 'status' => 'Active', 'salary' => 2400000, 'hired' => 'Feb 7, 2022', 'initials' => 'MO', 'tone' => 'violet'],
    ['name' => 'Noah Kim', 'email' => 'noah.kim@acme.co', 'role' => 'HR Generalist', 'department' => 'Human Resources', 'status' => 'Active', 'salary' => 1440000, 'hired' => 'May 1, 2023', 'initials' => 'NK', 'tone' => 'rose'],
    ['name' => 'Priya Nair', 'email' => 'priya.nair@acme.co', 'role' => 'Sales Representative', 'department' => 'Sales', 'status' => 'Active', 'salary' => 1560000, 'hired' => 'Sep 13, 2021', 'initials' => 'PN', 'tone' => 'amber'],
    ['name' => 'Sofia Reyes', 'email' => 'sofia.reyes@acme.co', 'role' => 'HR Manager', 'department' => 'Human Resources', 'status' => 'Active', 'salary' => 2160000, 'hired' => 'Aug 17, 2020', 'initials' => 'SR', 'tone' => 'mint'],
    ['name' => 'Tom Becker', 'email' => 'tom.becker@acme.co', 'role' => 'Marketing Specialist', 'department' => 'Marketing', 'status' => 'On Leave', 'salary' => 1560000, 'hired' => 'Oct 24, 2022', 'initials' => 'TB', 'tone' => 'cyan'],
    ['name' => 'Yuki Tanaka', 'email' => 'yuki.tanaka@acme.co', 'role' => 'Senior Software Engineer', 'department' => 'Engineering', 'status' => 'Active', 'salary' => 3600000, 'hired' => 'Jun 21, 2021', 'initials' => 'YT', 'tone' => 'rose'],
];

$candidates = [
    ['name' => 'Marcus Webb', 'role' => 'Software Engineer', 'stage' => 'Screening', 'days' => 41],
    ['name' => 'Elena Marquez', 'role' => 'Software Engineer', 'stage' => 'Interviewing', 'days' => 53],
];

$hiringHistory = [
    ['name' => 'Grace Lin', 'email' => 'grace.l@jobstack.dev', 'role' => 'Senior Software Engineer', 'applied' => 'Apr 20, 2026', 'hired' => 'Jun 1, 2026', 'days' => 42],
    ['name' => 'Omar Haddad', 'email' => 'omar.h@brightpath.io', 'role' => 'Sales Representative', 'applied' => 'May 11, 2026', 'hired' => 'Jun 29, 2026', 'days' => 49],
    ['name' => 'Jonas Weber', 'email' => 'jonas.w@applynow.co', 'role' => 'Marketing Specialist', 'applied' => 'Jun 15, 2026', 'hired' => 'Aug 3, 2026', 'days' => 49],
    ['name' => 'Aisha Bello', 'email' => 'aisha.b@hirelane.com', 'role' => 'Product Designer', 'applied' => 'Jul 28, 2026', 'hired' => 'Oct 2, 2026', 'days' => 66],
];

$onboardingPeople = [
    ['name' => 'Liam Fitzgerald', 'initials' => 'LF', 'tone' => 'violet', 'started' => '2024-11-04', 'buddy' => 'Yuki Tanaka', 'groups' => [
        'Orientation' => [['Day-one orientation session', true], ['Meet the team & assigned buddy', true], ['Office / remote setup walkthrough', true]],
        'Requirements' => [['Signed employment contract', true], ['Government ID & tax forms submitted', true], ['Bank details for payroll', true], ['Work equipment issued', false]],
        'Training' => [['Role-specific training plan assigned', true], ['Security & data-privacy training', false], ['Tools & access provisioned', false]],
        'Policies' => [['Employee handbook reviewed', true], ['Code of conduct acknowledged', false], ['Leave & attendance policy briefed', false]],
    ]],
    ['name' => 'Yuki Tanaka', 'initials' => 'YT', 'tone' => 'rose', 'started' => '2021-06-21', 'buddy' => 'Devon Park', 'groups' => [
        'Orientation' => [['Day-one orientation session', true], ['Meet the team & assigned buddy', true], ['Office / remote setup walkthrough', true]],
        'Requirements' => [['Signed employment contract', true], ['Government ID & tax forms submitted', true], ['Bank details for payroll', true], ['Work equipment issued', true]],
        'Training' => [['Role-specific training plan assigned', true], ['Security & data-privacy training', true], ['Tools & access provisioned', true]],
        'Policies' => [['Employee handbook reviewed', true], ['Code of conduct acknowledged', true], ['Leave & attendance policy briefed', true]],
    ]],
];

/** @var array<string, list<array{id: string, name: string, assigned: bool, date: string}>> $onboardingTaskLists */
$onboardingTaskLists = [
    'Pre-Onboarding' => [
        ['id' => 'w4-form', 'name' => 'Sign W-4 Form', 'assigned' => true, 'date' => '07/20/2020'],
        ['id' => 'i9-form', 'name' => 'Sign I-9 Form', 'assigned' => true, 'date' => '07/20/2020'],
        ['id' => 'nda', 'name' => 'Sign Non-Disclosure Agreement', 'assigned' => false, 'date' => '07/29/2020'],
        ['id' => 'hr-meeting', 'name' => 'Meeting with HR manager', 'assigned' => false, 'date' => '07/29/2020'],
    ],
    'Onboarding' => [
        ['id' => 'orientation', 'name' => 'Complete new-hire orientation', 'assigned' => false, 'date' => '07/29/2020'],
        ['id' => 'team-introduction', 'name' => 'Meet the team', 'assigned' => false, 'date' => '07/29/2020'],
        ['id' => 'equipment-setup', 'name' => 'Set up equipment and accounts', 'assigned' => false, 'date' => '07/29/2020'],
    ],
    'Post-Onboarding' => [
        ['id' => 'first-check-in', 'name' => 'Complete first-week check-in', 'assigned' => false, 'date' => '07/29/2020'],
        ['id' => 'training-review', 'name' => 'Review required training', 'assigned' => false, 'date' => '07/29/2020'],
        ['id' => 'feedback', 'name' => 'Submit onboarding feedback', 'assigned' => false, 'date' => '07/29/2020'],
    ],
];

$evaluations = [
    ['employee' => 'Sofia Reyes', 'initials' => 'SR', 'department' => 'Human Resources', 'period' => '2026 Q1', 'score' => 90, 'status' => 'Acknowledged', 'reviewer' => 'Amara Chen', 'note' => 'Reduced time-to-hire by 22%'],
    ['employee' => 'Priya Nair', 'initials' => 'PN', 'department' => 'Sales', 'period' => '2026 Q2', 'score' => 85, 'status' => 'Acknowledged', 'reviewer' => 'Amara Chen', 'note' => '118% of quarterly quota'],
    ['employee' => 'Liam Fitzgerald', 'initials' => 'LF', 'department' => 'Engineering', 'period' => '2026 Q2', 'score' => 74, 'status' => 'Draft', 'reviewer' => 'Devon Park', 'note' => 'Improve deployment documentation'],
    ['employee' => 'Maya Okafor', 'initials' => 'MO', 'department' => 'Design', 'period' => '2026 Q2', 'score' => 95, 'status' => 'Completed', 'reviewer' => 'Amara Chen', 'note' => 'Design system adoption across 3 teams'],
    ['employee' => 'Yuki Tanaka', 'initials' => 'YT', 'department' => 'Engineering', 'period' => '2026 Q2', 'score' => 88, 'status' => 'Completed', 'reviewer' => 'Devon Park', 'note' => 'Cut p99 API latency by 40%'],
    ['employee' => 'Devon Park', 'initials' => 'DP', 'department' => 'Engineering', 'period' => '2026 Q2', 'score' => 92, 'status' => 'Acknowledged', 'reviewer' => 'Amara Chen', 'note' => 'Shipped v2 platform migration ahead of schedule'],
];

$hrRecords = [
    ['employee' => 'Tom Becker', 'initials' => 'TB', 'tone' => 'cyan', 'title' => 'Campaign of the quarter', 'type' => 'Commendation', 'priority' => '', 'date' => 'May 30, 2026', 'description' => 'Spring relaunch campaign beat sign-up targets by 34%.', 'status' => 'Resolved', 'signature' => 'Signed by Tom Becker'],
    ['employee' => 'Priya Nair', 'initials' => 'PN', 'tone' => 'amber', 'title' => 'Expense policy violation', 'type' => 'Violation', 'priority' => 'Medium', 'date' => 'Jun 18, 2026', 'description' => 'Client dinner submitted without itemized receipt; exceeds per-head limit.', 'status' => 'Resolved', 'signature' => 'Signed by Priya Nair'],
    ['employee' => 'Priya Nair', 'initials' => 'PN', 'tone' => 'amber', 'title' => 'Missed CRM logging (first notice)', 'type' => 'Warning', 'priority' => 'Low', 'date' => 'Aug 5, 2026', 'description' => 'Three client calls in July were not logged in the CRM within 48h per policy.', 'status' => 'In Progress', 'signature' => ''],
    ['employee' => 'Liam Fitzgerald', 'initials' => 'LF', 'tone' => 'violet', 'title' => 'Reminder: equipment return policy', 'type' => 'Memo', 'priority' => '', 'date' => 'Aug 12, 2026', 'description' => 'Test laptop issued for onboarding must be returned or swapped within 30 days.', 'status' => 'Open', 'signature' => ''],
    ['employee' => 'Yuki Tanaka', 'initials' => 'YT', 'tone' => 'rose', 'title' => 'Exceptional incident response', 'type' => 'Commendation', 'priority' => '', 'date' => 'Sep 2, 2026', 'description' => 'Coordinated the production incident response and customer updates.', 'status' => 'Resolved', 'signature' => 'Signed by Yuki Tanaka'],
];

$leaveRequests = [
    ['employee' => 'Yuki Tanaka', 'initials' => 'YT', 'tone' => 'rose', 'type' => 'Vacation', 'days' => 5, 'from' => 'Oct 5, 2026', 'to' => 'Oct 9, 2026', 'note' => 'Family trip'],
    ['employee' => 'Maya Okafor', 'initials' => 'MO', 'tone' => 'violet', 'type' => 'Personal', 'days' => 1, 'from' => 'Sep 30, 2026', 'to' => 'Sep 30, 2026', 'note' => ''],
];

$leaveHistory = [
    ['employee' => 'Devon Park', 'initials' => 'DP', 'tone' => 'cyan', 'type' => 'Personal', 'status' => 'Rejected', 'days' => 2, 'dates' => 'Jul 17, 2026 - Jul 18, 2026', 'note' => 'Overlaps with release freeze'],
    ['employee' => 'Priya Nair', 'initials' => 'PN', 'tone' => 'amber', 'type' => 'Vacation', 'status' => 'Approved', 'days' => 10, 'dates' => 'Aug 3, 2026 - Aug 14, 2026', 'note' => ''],
    ['employee' => 'Tom Becker', 'initials' => 'TB', 'tone' => 'cyan', 'type' => 'Sick', 'status' => 'Approved', 'days' => 5, 'dates' => 'Sep 14, 2026 - Sep 18, 2026', 'note' => 'Get well soon'],
];

$leaveBalances = [
    ['name' => 'Amara Chen', 'initials' => 'AC', 'tone' => 'mint', 'vacation' => 20, 'sick' => 10, 'personal' => 5],
    ['name' => 'Devon Park', 'initials' => 'DP', 'tone' => 'cyan', 'vacation' => 20, 'sick' => 10, 'personal' => 5],
    ['name' => 'Yuki Tanaka', 'initials' => 'YT', 'tone' => 'rose', 'vacation' => 20, 'sick' => 10, 'personal' => 5],
    ['name' => 'Maya Okafor', 'initials' => 'MO', 'tone' => 'violet', 'vacation' => 20, 'sick' => 10, 'personal' => 5],
    ['name' => 'Liam Fitzgerald', 'initials' => 'LF', 'tone' => 'violet', 'vacation' => 20, 'sick' => 10, 'personal' => 5],
    ['name' => 'Sofia Reyes', 'initials' => 'SR', 'tone' => 'mint', 'vacation' => 20, 'sick' => 10, 'personal' => 5],
    ['name' => 'Noah Kim', 'initials' => 'NK', 'tone' => 'rose', 'vacation' => 20, 'sick' => 10, 'personal' => 5],
    ['name' => 'Priya Nair', 'initials' => 'PN', 'tone' => 'amber', 'vacation' => 20, 'sick' => 10, 'personal' => 5],
    ['name' => 'Tom Becker', 'initials' => 'TB', 'tone' => 'cyan', 'vacation' => 20, 'sick' => 5, 'personal' => 5],
];

$jobTitles = [
    ['department' => 'Design', 'title' => 'Product Designer', 'level' => 'Mid', 'description' => 'Owns product design and UX', 'salary' => 2400000, 'count' => 1],
    ['department' => 'Engineering', 'title' => 'Engineering Manager', 'level' => 'Lead', 'description' => 'Leads the engineering team and delivery', 'salary' => 4800000, 'count' => 1],
    ['department' => 'Engineering', 'title' => 'Senior Software Engineer', 'level' => 'Senior', 'description' => 'Designs and ships core product features', 'salary' => 3600000, 'count' => 1],
    ['department' => 'Engineering', 'title' => 'Software Engineer', 'level' => 'Mid', 'description' => 'Builds and maintains product features', 'salary' => 2400000, 'count' => 1],
    ['department' => 'Executive', 'title' => 'Chief Executive Officer', 'level' => 'Executive', 'description' => 'Leads company vision and strategy', 'salary' => 8400000, 'count' => 1],
    ['department' => 'Human Resources', 'title' => 'HR Manager', 'level' => 'Lead', 'description' => 'Runs hiring, onboarding and people ops', 'salary' => 2160000, 'count' => 1],
    ['department' => 'Human Resources', 'title' => 'HR Generalist', 'level' => 'Mid', 'description' => 'Supports day-to-day HR operations', 'salary' => 1440000, 'count' => 1],
    ['department' => 'Marketing', 'title' => 'Marketing Specialist', 'level' => 'Mid', 'description' => 'Executes campaigns and brand programs', 'salary' => 1560000, 'count' => 1],
    ['department' => 'Sales', 'title' => 'Sales Representative', 'level' => 'Mid', 'description' => 'Drives new business and account growth', 'salary' => 1560000, 'count' => 1],
];

function ems_h(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ems_money(int $amount): string
{
    return '&#8369;' . number_format($amount);
}

function ems_icon(string $name): string
{
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'person-add' => '<path d="M15 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6m3-3h-6"/>',
        'sparkles' => '<path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2L12 3Z"/><path d="m19 14 1.2 2.8L23 18l-2.8 1.2L19 22l-1.2-2.8L15 18l2.8-1.2L19 14Z"/>',
        'performance' => '<path d="M12 3 14.3 4.2l2.6-.1 1.2 2.2 2.2 1.3-.5 2.6.5 2.6-2.2 1.3-1.2 2.2-2.6-.1L12 17l-2.3 1.2-2.6-.1-1.2-2.2-2.2-1.3.5-2.6-.5-2.6 2.2-1.3 1.2-2.2 2.6.1L12 3Z"/><path d="m9 10.5 2 2 4-4"/>',
        'gavel' => '<path d="m14 13 7-7-3-3-7 7M5 21l8-8M7 8l3-3 9 9-3 3z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.7-1.4-2.4L7.3 15a8 8 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.7 1.4 2.4-1.4 1.1a8 8 0 0 1-.1 2Z"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2"/>',
        'logout' => '<path d="m10 17 5-5-5-5m5 5H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>',
    ];
    $content = $paths[$name] ?? '';
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $content . '</svg>';
}
