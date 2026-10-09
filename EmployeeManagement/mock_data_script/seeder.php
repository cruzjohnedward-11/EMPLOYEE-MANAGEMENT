<?php
declare(strict_types=1);

define('TOTAL_RECORDS_COUNT', 100);
define('TARGET_MODULE', 'ALL');
define('MAX_AGE_YEARS', 2);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This seeder is available only from the command line.\n");
}

require_once dirname(__DIR__) . '/db.php';

const SEED_TABLES_TO_FLUSH = [
    'users',
    'departments',
    'job_titles',
    'employees',
    'compensation',
    'hiring_pipeline',
    'attendance_records',
    'training_records',
    'kpi_records',
];

const SEED_PASSWORD_CHOICES = ['pig', 'password', '1234', 'january', 'hotdogs'];

function validateConfiguration(): void
{
    if (!is_int(TOTAL_RECORDS_COUNT) || TOTAL_RECORDS_COUNT < 1 || TOTAL_RECORDS_COUNT > 1000) {
        throw new InvalidArgumentException('TOTAL_RECORDS_COUNT must be between 1 and 1000.');
    }
    if (!in_array(TARGET_MODULE, ['ALL', 'INFRASTRUCTURE', 'PERSONNEL'], true)) {
        throw new InvalidArgumentException('TARGET_MODULE must be ALL, INFRASTRUCTURE, or PERSONNEL.');
    }
    if (!is_int(MAX_AGE_YEARS) || MAX_AGE_YEARS < 1 || MAX_AGE_YEARS > 10) {
        throw new InvalidArgumentException('MAX_AGE_YEARS must be between 1 and 10.');
    }
}

function assertTargetDatabase(PDO $pdo): void
{
    $databaseName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($databaseName !== 'ems_db') {
        throw new RuntimeException('Seeder is restricted to the ems_db database.');
    }
}

function flushDatabase(PDO $pdo): void
{
    $checksDisabled = false;

    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $checksDisabled = true;

        foreach (SEED_TABLES_TO_FLUSH as $table) {
            $pdo->exec('TRUNCATE TABLE `' . $table . '`');
        }
    } finally {
        if ($checksDisabled) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}

function confirmFlush(): bool
{
    fwrite(STDERR, "WARNING: --flush permanently truncates these ems_db tables:\n");
    foreach (SEED_TABLES_TO_FLUSH as $table) {
        fwrite(STDERR, "  - {$table}\n");
    }
    fwrite(STDERR, "The settings table and database views are not modified.\n");
    fwrite(STDERR, "Type FLUSH ems_db to continue: ");

    $confirmation = fgets(STDIN);
    return is_string($confirmation) && trim($confirmation) === 'FLUSH ems_db';
}

function buildDateBounds(): array
{
    $today = new DateTimeImmutable('today');
    return [$today->modify('-' . MAX_AGE_YEARS . ' years'), $today];
}

function randomDate(DateTimeImmutable $start, DateTimeImmutable $end): DateTimeImmutable
{
    $startTimestamp = $start->getTimestamp();
    $endTimestamp = $end->getTimestamp();
    if ($startTimestamp > $endTimestamp) {
        throw new InvalidArgumentException('The generated date range is invalid.');
    }

    $timestamp = random_int($startTimestamp, $endTimestamp);
    return (new DateTimeImmutable('@' . $timestamp))->setTimezone($start->getTimezone());
}

function randomCreatedAt(DateTimeImmutable $notBefore, DateTimeImmutable $notAfter): string
{
    return randomDate($notBefore, $notAfter)->format('Y-m-d H:i:s');
}

function randomPersonName(int $index): array
{
    $firstNames = [
        'Alex', 'Ari', 'Casey', 'Dana', 'Jamie', 'Jordan', 'Kai', 'Morgan',
        'Noah', 'Riley', 'Sam', 'Taylor', 'Avery', 'Cameron', 'Drew', 'Robin',
    ];
    $lastNames = [
        'Anderson', 'Bennett', 'Carter', 'Davis', 'Ellis', 'Flores', 'Garcia', 'Hayes',
        'Lopez', 'Morgan', 'Parker', 'Reyes', 'Rivera', 'Santos', 'Turner', 'Young',
    ];

    return [
        $firstNames[$index % count($firstNames)],
        $lastNames[intdiv($index, count($firstNames)) % count($lastNames)],
    ];
}

function ensureInfrastructure(PDO $pdo): array
{
    $departments = [
        ['name' => 'Engineering', 'code' => 'ENG', 'description' => 'Product engineering and platform operations.'],
        ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'People operations and employee experience.'],
        ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Brand, communications, and demand generation.'],
        ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Financial planning and business operations.'],
    ];
    $departmentLookup = $pdo->prepare(
        'SELECT department_id FROM departments WHERE department_name = :department_name'
    );
    $insertDepartment = $pdo->prepare(
        'INSERT INTO departments (department_name, department_code, description)
         VALUES (:department_name, :department_code, :description)'
    );
    $departmentIds = [];

    foreach ($departments as $department) {
        $departmentLookup->execute(['department_name' => $department['name']]);
        $departmentId = $departmentLookup->fetchColumn();
        if ($departmentId === false) {
            $insertDepartment->execute([
                'department_name' => $department['name'],
                'department_code' => $department['code'],
                'description' => $department['description'],
            ]);
            $departmentId = $pdo->lastInsertId();
        }
        $departmentIds[$department['name']] = (int) $departmentId;
    }

    $titleLookup = $pdo->prepare(
        'SELECT job_title_id
         FROM job_titles
         WHERE job_title_name = :job_title_name AND department_id = :department_id
         ORDER BY job_title_id
         LIMIT 1'
    );
    $insertTitle = $pdo->prepare(
        'INSERT INTO job_titles
            (job_title_name, job_description, level, pay_grade, min_salary, max_salary, department_id)
         VALUES
            (:job_title_name, :job_description, :level, :pay_grade, :min_salary, :max_salary, :department_id)'
    );
    $titleDefinitions = [
        ['suffix' => 'Associate', 'level' => 'Junior', 'grade' => 'J1', 'min' => '42000.00', 'max' => '62000.00'],
        ['suffix' => 'Specialist', 'level' => 'Mid', 'grade' => 'M1', 'min' => '62000.00', 'max' => '95000.00'],
        ['suffix' => 'Lead', 'level' => 'Lead', 'grade' => 'L1', 'min' => '95000.00', 'max' => '145000.00'],
    ];
    $jobTitleIdsByDepartment = [];

    foreach ($departments as $department) {
        foreach ($titleDefinitions as $definition) {
            $titleName = $department['name'] === 'Engineering'
                ? 'Software ' . $definition['suffix']
                : $department['name'] . ' ' . $definition['suffix'];
            $departmentId = $departmentIds[$department['name']];
            $titleLookup->execute([
                'job_title_name' => $titleName,
                'department_id' => $departmentId,
            ]);
            $jobTitleId = $titleLookup->fetchColumn();
            if ($jobTitleId === false) {
                $insertTitle->execute([
                    'job_title_name' => $titleName,
                    'job_description' => $titleName . ' role in ' . $department['name'] . '.',
                    'level' => $definition['level'],
                    'pay_grade' => $definition['grade'],
                    'min_salary' => $definition['min'],
                    'max_salary' => $definition['max'],
                    'department_id' => $departmentId,
                ]);
                $jobTitleId = $pdo->lastInsertId();
            }
            $jobTitleIdsByDepartment[$departmentId][] = (int) $jobTitleId;
        }
    }

    $managerQuery = $pdo->query(
        'SELECT department_id, manager_id
         FROM departments
         WHERE manager_id IS NOT NULL'
    );
    $managerIdsByDepartment = [];
    foreach ($managerQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $managerIdsByDepartment[(int) $row['department_id']] = (int) $row['manager_id'];
    }

    return [
        'departments' => array_values($departmentIds),
        'department_ids' => $departmentIds,
        'job_title_ids_by_department' => $jobTitleIdsByDepartment,
        'manager_ids_by_department' => $managerIdsByDepartment,
    ];
}

function seedPersonnel(PDO $pdo, array $infrastructure): array
{
    [$oldestDate, $today] = buildDateBounds();
    $seedPassword = getenv('EMS_SEED_PASSWORD');
    $generatedPassword = $seedPassword === false || $seedPassword === '';
    if ($generatedPassword) {
        $seedPassword = SEED_PASSWORD_CHOICES[random_int(0, count(SEED_PASSWORD_CHOICES) - 1)];
    }
    if (!in_array($seedPassword, SEED_PASSWORD_CHOICES, true)) {
        throw new InvalidArgumentException(
            'EMS_SEED_PASSWORD must be one of: ' . implode(', ', SEED_PASSWORD_CHOICES) . '.'
        );
    }
    $passwordHash = password_hash($seedPassword, PASSWORD_DEFAULT);
    if (
        !is_string($passwordHash)
        || !password_verify($seedPassword, $passwordHash)
        || password_verify('invalid-' . $seedPassword, $passwordHash)
    ) {
        throw new RuntimeException('Unable to generate password hashes for seeded users.');
    }

    $insertUser = $pdo->prepare(
        'INSERT INTO users (username, password_hash, email, role, created_at, is_active)
         VALUES (:username, :password_hash, :email, :role, :created_at, 1)'
    );
    $insertEmployee = $pdo->prepare(
        'INSERT INTO employees
            (user_id, employee_number, first_name, last_name, email, phone_number, address,
             gender, birth_date, emergency_contact, sss_no, philhealth_no, pagibig_no, tin_no,
             department_id, job_title_id, manager_id,
             employment_status, hire_date, regularization_date, separation_date, separation_reason,
             created_at)
         VALUES
            (:user_id, :employee_number, :first_name, :last_name, :email, :phone_number, :address,
             :gender, :birth_date, :emergency_contact, :sss_no, :philhealth_no, :pagibig_no, :tin_no,
             :department_id, :job_title_id, :manager_id, :employment_status, :hire_date,
             :regularization_date, :separation_date, :separation_reason, :created_at)'
    );
    $insertCompensation = $pdo->prepare(
        'INSERT INTO compensation
            (employee_id, job_title_id, base_salary, currency, effective_date, end_date, reason, updated_by, created_at)
         VALUES
            (:employee_id, :job_title_id, :base_salary, \'PHP\', :effective_date, :end_date, :reason, :updated_by, :created_at)'
    );

    $departmentIds = $infrastructure['departments'];
    $employeeIds = [];
    $employeeDetails = [];
    $managerByDepartment = $infrastructure['manager_ids_by_department'];

    for ($index = 0; $index < TOTAL_RECORDS_COUNT; $index++) {
        [$firstName, $lastName] = randomPersonName($index);
        $emailToken = bin2hex(random_bytes(4));
        $baseUsername = strtolower($firstName . '.' . $lastName);
        $username = substr($baseUsername, 0, 41) . '.' . $emailToken;
        $email = $username . '@example.test';
        $role = match ($index % 20) {
            0 => 'Admin',
            1 => 'HR',
            2 => 'Manager',
            default => 'Staff',
        };
        $departmentId = $departmentIds[$index % count($departmentIds)];
        $jobTitles = $infrastructure['job_title_ids_by_department'][$departmentId];
        $jobTitleId = $jobTitles[$index % count($jobTitles)];
        $hireDate = randomDate($oldestDate, $today);
        $employeeStatus = match ($index % 20) {
            13, 14 => 'Probation',
            15, 16, 17, 18 => 'Resigned',
            19 => 'Terminated',
            default => 'Active',
        };
        $isSeparated = in_array($employeeStatus, ['Resigned', 'Terminated'], true);
        $separationDate = $isSeparated
            ? randomDate($hireDate, $today)
            : null;
        $separationReasons = [
            'Resigned' => [
                'Accepted another opportunity.',
                'Relocated to another region.',
                'Personal reasons.',
                'Pursuing further studies.',
            ],
            'Terminated' => [
                'Position ended following organizational restructuring.',
                'Employment ended after a performance review.',
                'Employment ended following a policy review.',
            ],
        ];
        $separationReason = $isSeparated
            ? $separationReasons[$employeeStatus][random_int(0, count($separationReasons[$employeeStatus]) - 1)]
            : null;
        $accountCreatedAt = randomCreatedAt($hireDate, $separationDate ?? $today);
        $regularizationDate = $employeeStatus === 'Active'
            ? $hireDate->modify('+3 months')
            : null;
        if ($regularizationDate instanceof DateTimeImmutable && $regularizationDate > $today) {
            $regularizationDate = null;
        }
        $employeeNumber = 'SEED-' . strtoupper(bin2hex(random_bytes(6)));
        $gender = ['Female', 'Male', 'Non-binary'][random_int(0, 2)];
        $birthDate = randomDate(
            $today->modify('-60 years'),
            $today->modify('-21 years')
        );
        $managerId = $managerByDepartment[$departmentId] ?? null;

        $insertUser->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
            'email' => $email,
            'role' => $role,
            'created_at' => $accountCreatedAt,
        ]);
        $userId = (int) $pdo->lastInsertId();

        $insertEmployee->execute([
            'user_id' => $userId,
            'employee_number' => $employeeNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone_number' => sprintf('+63 9%02d %03d %04d', random_int(10, 99), random_int(100, 999), random_int(1000, 9999)),
            'address' => random_int(100, 999) . ' Sample Street, Metro Manila',
            'gender' => $gender,
            'birth_date' => $birthDate->format('Y-m-d'),
            'emergency_contact' => 'Sample Contact ' . $index . ' +63 900 000 0000',
            'sss_no' => sprintf('%02d-%07d-%d', random_int(1, 99), random_int(1000000, 9999999), random_int(0, 9)),
            'philhealth_no' => sprintf('%02d%09d', random_int(1, 99), random_int(0, 999999999)),
            'pagibig_no' => sprintf('%04d%08d', random_int(1, 9999), random_int(0, 99999999)),
            'tin_no' => sprintf('%03d-%03d-%03d-%03d', random_int(1, 999), random_int(1, 999), random_int(1, 999), random_int(1, 999)),
            'department_id' => $departmentId,
            'job_title_id' => $jobTitleId,
            'manager_id' => $managerId,
            'employment_status' => $employeeStatus,
            'hire_date' => $hireDate->format('Y-m-d'),
            'regularization_date' => $regularizationDate?->format('Y-m-d'),
            'separation_date' => $separationDate?->format('Y-m-d'),
            'separation_reason' => $separationReason,
            'created_at' => $accountCreatedAt,
        ]);
        $employeeId = (int) $pdo->lastInsertId();
        $employeeIds[] = $employeeId;
        $employeeDetails[] = [
            'employee_id' => $employeeId,
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'department_id' => $departmentId,
            'job_title_id' => $jobTitleId,
            'hire_date' => $hireDate,
            'separation_date' => $separationDate,
            'created_at' => $accountCreatedAt,
        ];

        $salaryBounds = [
            ['min' => 42000, 'max' => 62000],
            ['min' => 62000, 'max' => 95000],
            ['min' => 95000, 'max' => 145000],
        ][$index % 3];
        $salary = random_int($salaryBounds['min'], $salaryBounds['max']);
        $insertCompensation->execute([
            'employee_id' => $employeeId,
            'job_title_id' => $jobTitleId,
            'base_salary' => number_format((float) $salary, 2, '.', ''),
            'effective_date' => $hireDate->format('Y-m-d'),
            'end_date' => $separationDate?->format('Y-m-d'),
            'reason' => $isSeparated
                ? 'Seeded salary history before separation.'
                : 'Seeded current base salary.',
            'updated_by' => $userId,
            'created_at' => $accountCreatedAt,
        ]);

        if ($role === 'Manager' && !isset($managerByDepartment[$departmentId])) {
            $managerByDepartment[$departmentId] = $employeeId;
            $updateDepartmentManager = $pdo->prepare(
                'UPDATE departments
                 SET manager_id = :manager_id
                 WHERE department_id = :department_id AND manager_id IS NULL'
            );
            $updateDepartmentManager->execute([
                'manager_id' => $employeeId,
                'department_id' => $departmentId,
            ]);
            $updateTeamManager = $pdo->prepare(
                'UPDATE employees
                 SET manager_id = :manager_id
                 WHERE department_id = :department_id
                   AND employee_id <> :employee_id
                   AND manager_id IS NULL'
            );
            $updateTeamManager->execute([
                'manager_id' => $employeeId,
                'department_id' => $departmentId,
                'employee_id' => $employeeId,
            ]);
        }
    }

    fwrite(STDOUT, "Seed users use this demo-only password: {$seedPassword}\n");

    return [
        'employee_ids' => $employeeIds,
        'employees' => $employeeDetails,
        'managers_by_department' => $managerByDepartment,
    ];
}

function seedHiring(PDO $pdo, array $personnel, array $infrastructure): int
{
    [$oldestDate, $today] = buildDateBounds();
    $statuses = [
        'Applied', 'Screening', 'Interview', 'Offer', 'Hired',
        'Rejected', 'Withdrawn', 'No Response',
    ];
    $insert = $pdo->prepare(
        'INSERT INTO hiring_pipeline
            (applicant_name, email, phone, credentials, applied_job_title_id, application_date,
             source, recruiter_name, status, interview_stage, interview_notes, offer_amount,
             hire_date, employee_id, unhired_stage, unhired_reason, decision_date, created_at)
         VALUES
            (:applicant_name, :email, :phone, :credentials, :job_title_id, :application_date,
             :source, :recruiter_name, :status, :interview_stage, :interview_notes, :offer_amount,
             :hire_date, :employee_id, :unhired_stage, :unhired_reason, :decision_date, :created_at)'
    );
    $stageForTerminal = [
        'Rejected' => ['Applied', 'Screening', 'Interview', 'Offer'],
        'Withdrawn' => ['Applied', 'Screening', 'Interview', 'Offer'],
        'No Response' => ['Applied', 'Screening', 'Interview', 'Offer'],
    ];

    for ($index = 0; $index < TOTAL_RECORDS_COUNT; $index++) {
        [$firstName, $lastName] = randomPersonName($index + TOTAL_RECORDS_COUNT);
        $applicationDate = randomDate($oldestDate, $today);
        $status = $statuses[$index % count($statuses)];
        $isHired = $status === 'Hired';
        $isTerminal = isset($stageForTerminal[$status]);
        $decisionDate = $isHired || $isTerminal
            ? randomDate($applicationDate, $today)
            : null;
        $hireDate = $isHired ? $decisionDate : null;
        $employeeId = $isHired
            ? $personnel['employee_ids'][$index % count($personnel['employee_ids'])]
            : null;
        $createdAt = randomCreatedAt($applicationDate, $today);
        $titleDepartmentId = $infrastructure['departments'][$index % count($infrastructure['departments'])];
        $jobTitleIds = $infrastructure['job_title_ids_by_department'][$titleDepartmentId];
        $unhiredStage = $isTerminal
            ? $stageForTerminal[$status][random_int(0, count($stageForTerminal[$status]) - 1)]
            : null;

        $insert->execute([
            'applicant_name' => $firstName . ' ' . $lastName,
            'email' => strtolower($firstName . '.' . $lastName . '.' . bin2hex(random_bytes(4)) . '@applicants.example.test'),
            'phone' => sprintf('+63 9%02d %03d %04d', random_int(10, 99), random_int(100, 999), random_int(1000, 9999)),
            'credentials' => 'Seeded sample applicant profile.',
            'job_title_id' => $jobTitleIds[random_int(0, count($jobTitleIds) - 1)],
            'application_date' => $applicationDate->format('Y-m-d'),
            'source' => ['Company careers page', 'Referral', 'Recruitment event'][random_int(0, 2)],
            'recruiter_name' => 'Seed Recruiter',
            'status' => $status,
            'interview_stage' => in_array($status, ['Interview', 'Offer', 'Hired'], true) ? 'Technical' : null,
            'interview_notes' => in_array($status, ['Interview', 'Offer', 'Hired'], true) ? 'Seeded sample interview notes.' : null,
            'offer_amount' => $isHired ? number_format(random_int(60000, 180000), 2, '.', '') : null,
            'hire_date' => $hireDate?->format('Y-m-d'),
            'employee_id' => $employeeId,
            'unhired_stage' => $unhiredStage,
            'unhired_reason' => $isTerminal ? 'Seeded historical outcome.' : null,
            'decision_date' => $decisionDate?->format('Y-m-d'),
            'created_at' => $createdAt,
        ]);
    }

    return TOTAL_RECORDS_COUNT;
}

function seedAttendance(PDO $pdo, array $personnel): int
{
    [$oldestDate, $today] = buildDateBounds();
    $insert = $pdo->prepare(
        'INSERT INTO attendance_records
            (employee_id, attendance_date, status, absence_type, absence_reason, created_at)
         VALUES
            (:employee_id, :attendance_date, :status, :absence_type, :absence_reason, :created_at)'
    );
    $count = 0;

    foreach ($personnel['employees'] as $employee) {
        $hireDate = $employee['hire_date'];
        $firstAttendance = $hireDate > $oldestDate ? $hireDate : $oldestDate;
        $period = new DatePeriod(
            $firstAttendance,
            new DateInterval('P1D'),
            $today->modify('+1 day')
        );

        foreach ($period as $date) {
            if ((int) $date->format('N') > 5) {
                continue;
            }
            $attendanceStatus = match (random_int(1, 100)) {
                1, 2, 3, 4, 5, 6, 7, 8, 9, 10 => 'Absent',
                11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25 => 'Late',
                default => 'Present',
            };
            $createdAtStart = $date;
            $createdAtEnd = $date->setTime(23, 59, 59);
            $now = new DateTimeImmutable();
            if ($createdAtEnd > $now) {
                $createdAtEnd = $now;
            }
            if ($createdAtStart > $createdAtEnd) {
                $createdAtStart = $createdAtEnd;
            }

            $insert->execute([
                'employee_id' => $employee['employee_id'],
                'attendance_date' => $date->format('Y-m-d'),
                'status' => $attendanceStatus,
                'absence_type' => $attendanceStatus === 'Absent'
                    ? (random_int(0, 1) === 1 ? 'Excused' : 'Unexcused')
                    : null,
                'absence_reason' => $attendanceStatus === 'Absent' ? 'Seeded sample absence.' : null,
                'created_at' => randomCreatedAt($createdAtStart, $createdAtEnd),
            ]);
            $count++;
        }
    }

    return $count;
}

function seedTraining(PDO $pdo, array $personnel): int
{
    [$oldestDate, $today] = buildDateBounds();
    $insert = $pdo->prepare(
        'INSERT INTO training_records
            (employee_id, training_name, hours, status, date_completed, created_at)
         VALUES
            (:employee_id, :training_name, :hours, :status, :date_completed, :created_at)'
    );
    $trainingNames = [
        'Security Awareness',
        'Data Privacy Fundamentals',
        'Workplace Safety',
        'Communication Skills',
    ];
    $count = 0;

    foreach ($personnel['employees'] as $index => $employee) {
        $trainingCount = random_int(1, 3);
        for ($trainingIndex = 0; $trainingIndex < $trainingCount; $trainingIndex++) {
            $trainingStart = $employee['hire_date'] > $oldestDate
                ? $employee['hire_date']
                : $oldestDate;
            $trainingDate = randomDate($trainingStart, $today);
            $status = ['Not Started', 'In Progress', 'Completed'][random_int(0, 2)];
            $insert->execute([
                'employee_id' => $employee['employee_id'],
                'training_name' => $trainingNames[($index + $trainingIndex) % count($trainingNames)],
                'hours' => number_format((float) random_int(1, 80), 1, '.', ''),
                'status' => $status,
                'date_completed' => $status === 'Completed' ? $trainingDate->format('Y-m-d') : null,
                'created_at' => randomCreatedAt($employee['hire_date'], $today),
            ]);
            $count++;
        }
    }

    return $count;
}

function seedEvaluations(PDO $pdo, array $personnel): int
{
    [$oldestDate, $today] = buildDateBounds();
    $insert = $pdo->prepare(
        'INSERT INTO kpi_records
            (employee_id, review_period, attendance_pct, training_completion_pct, kpi_score,
             evaluator_id, status, comments, created_at)
         VALUES
            (:employee_id, :review_period, :attendance_pct, :training_completion_pct, :kpi_score,
             :evaluator_id, :status, :comments, :created_at)'
    );
    $reviewPeriods = [];
    $cursor = $oldestDate->modify('first day of this month');
    $lastMonth = $today->modify('first day of this month');
    while ($cursor <= $lastMonth) {
        $reviewPeriods[] = $cursor->format('Y-m');
        $cursor = $cursor->modify('+1 month');
    }

    $count = 0;
    foreach ($personnel['employees'] as $index => $employee) {
        $employeePeriods = array_values(array_filter(
            $reviewPeriods,
            static function (string $period) use ($employee): bool {
                return $period >= $employee['hire_date']->format('Y-m');
            }
        ));
        if ($employeePeriods === []) {
            $employeePeriods[] = $today->format('Y-m');
        }
        $periodStride = max(1, intdiv(count($employeePeriods) + 3, 4));
        foreach ($employeePeriods as $periodIndex => $period) {
            if ($periodIndex % $periodStride !== 0) {
                continue;
            }
            $reviewMonthStart = DateTimeImmutable::createFromFormat('!Y-m', $period);
            if (!$reviewMonthStart instanceof DateTimeImmutable) {
                throw new RuntimeException('Unable to construct KPI review period dates.');
            }
            $createdAtStart = max($employee['hire_date'], $oldestDate, $reviewMonthStart);
            $createdAtEnd = min($reviewMonthStart->modify('last day of this month')->setTime(23, 59, 59), new DateTimeImmutable());
            $insert->execute([
                'employee_id' => $employee['employee_id'],
                'review_period' => $period,
                'attendance_pct' => number_format((float) random_int(8500, 10000) / 100, 2, '.', ''),
                'training_completion_pct' => number_format((float) random_int(4000, 10000) / 100, 2, '.', ''),
                'kpi_score' => number_format((float) random_int(5500, 10000) / 100, 2, '.', ''),
                'evaluator_id' => $personnel['employee_ids'][($index + 1) % count($personnel['employee_ids'])],
                'status' => ['Draft', 'Completed', 'Acknowledged'][random_int(0, 2)],
                'comments' => 'Seeded sample review for ' . $period . '.',
                'created_at' => randomCreatedAt($createdAtStart, $createdAtEnd),
            ]);
            $count++;
        }
    }

    return $count;
}

function runSeeder(array $arguments): void
{
    validateConfiguration();

    $flushRequested = in_array('--flush', $arguments, true);
    foreach ($arguments as $argument) {
        if ($argument !== '--flush') {
            throw new InvalidArgumentException('Usage: php mock_data_script/seeder.php [--flush]');
        }
    }

    if ($flushRequested && !confirmFlush()) {
        fwrite(STDOUT, "Flush cancelled. No database changes were made.\n");
        return;
    }

    $pdo = ems_db();
    assertTargetDatabase($pdo);
    if ($flushRequested) {
        flushDatabase($pdo);
        fwrite(STDOUT, "Configured tables were truncated.\n");
    }

    $pdo->beginTransaction();
    try {
        $infrastructure = ensureInfrastructure($pdo);
        fwrite(STDOUT, 'Infrastructure ready: '
            . count($infrastructure['departments']) . " departments and "
            . array_sum(array_map('count', $infrastructure['job_title_ids_by_department']))
            . " job titles.\n");

        if (TARGET_MODULE === 'INFRASTRUCTURE') {
            $pdo->commit();
            fwrite(STDOUT, "Infrastructure seeding completed.\n");
            return;
        }

        $personnel = seedPersonnel($pdo, $infrastructure);
        $hiringCount = seedHiring($pdo, $personnel, $infrastructure);
        $attendanceCount = seedAttendance($pdo, $personnel);
        $trainingCount = seedTraining($pdo, $personnel);
        $evaluationCount = seedEvaluations($pdo, $personnel);
        $pdo->commit();

        fwrite(STDOUT, 'Personnel seeding completed: '
            . count($personnel['employee_ids']) . ' employees, '
            . $hiringCount . ' applicants, '
            . $attendanceCount . ' attendance rows, '
            . $trainingCount . ' training rows, '
            . $evaluationCount . " KPI rows.\n");
        fwrite(STDOUT, "Onboarding tasks, HR case records, and leave requests/balances were not seeded because ems_db has no corresponding tables.\n");
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

try {
    runSeeder(array_slice($argv, 1));
} catch (Throwable $exception) {
    fwrite(STDERR, 'Seeder failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
