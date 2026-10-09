<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
ems_require_authentication();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/dashboard-helpers.php';

const LEAVE_TYPES = ['Vacation', 'Sick', 'Personal'];
const LEAVE_DEFAULT_DAYS = ['Vacation' => 15, 'Sick' => 10, 'Personal' => 5];

$leaveCanDecide = in_array($_SESSION['role'], ['Admin', 'HR', 'Manager'], true);
$leaveIsStaff = $_SESSION['role'] === 'Staff';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

/** Counts Monday-Friday days between two dates, inclusive. */
function leave_working_days(DateTimeImmutable $start, DateTimeImmutable $end): int
{
    $days = 0;
    for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
        if ((int) $day->format('N') <= 5) {
            $days++;
        }
    }
    return $days;
}

function leave_parse_date(mixed $value): DateTimeImmutable
{
    if (!is_string($value)) {
        throw new InvalidArgumentException('Enter a valid start and end date.');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (
        $date === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $date->format('Y-m-d') !== $value
    ) {
        throw new InvalidArgumentException('Enter a valid start and end date.');
    }
    return $date;
}

/** Makes sure the employee has the three balance rows for the year. */
function leave_ensure_balances(PDO $pdo, int $employeeId, int $year): void
{
    $insert = $pdo->prepare(
        'INSERT IGNORE INTO leave_balances (employee_id, leave_year, leave_type, days_total, days_used)
         VALUES (:employee_id, :leave_year, :leave_type, :days_total, 0)'
    );
    foreach (LEAVE_DEFAULT_DAYS as $type => $days) {
        $insert->execute([
            'employee_id' => $employeeId,
            'leave_year' => $year,
            'leave_type' => $type,
            'days_total' => $days,
        ]);
    }
}

function leave_own_employee_id(PDO $pdo, int $userId): ?int
{
    $query = $pdo->prepare('SELECT employee_id FROM employees WHERE user_id = :user_id LIMIT 1');
    $query->execute(['user_id' => $userId]);
    $value = $query->fetchColumn();
    return $value === false ? null : (int) $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = [
        'type' => 'error',
        'message' => 'The leave request could not be processed. Please check the submitted details.',
    ];

    if (!ems_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $feedback['message'] = 'Your session token is invalid. Refresh the page and try again.';
    } else {
        try {
            $action = $_POST['leave_action'] ?? null;
            if (!is_string($action) || !in_array($action, ['create', 'approve', 'reject'], true)) {
                throw new InvalidArgumentException('Select a supported leave action.');
            }

            $pdo = ems_db();
            $ownEmployeeId = leave_own_employee_id($pdo, (int) $_SESSION['user_id']);

            if ($action === 'create') {
                $employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                $type = $_POST['leave_type'] ?? null;
                $reason = $_POST['reason'] ?? '';

                if (
                    $employeeId === false
                    || !is_string($type)
                    || !in_array($type, LEAVE_TYPES, true)
                    || !is_string($reason)
                    || preg_match('//u', $reason) !== 1
                    || preg_match_all('/./us', trim($reason)) > 500
                ) {
                    throw new InvalidArgumentException('Enter a valid employee, leave type and reason (500 characters max).');
                }

                if ($leaveIsStaff && $employeeId !== $ownEmployeeId) {
                    throw new InvalidArgumentException('You can only file leave requests for yourself.');
                }

                $start = leave_parse_date($_POST['start_date'] ?? null);
                $end = leave_parse_date($_POST['end_date'] ?? null);
                if ($end < $start) {
                    throw new InvalidArgumentException('The end date cannot be before the start date.');
                }
                if ($start->format('Y') !== $end->format('Y')) {
                    throw new InvalidArgumentException('A request cannot span two calendar years. File one request per year.');
                }

                $days = leave_working_days($start, $end);
                if ($days < 1) {
                    throw new InvalidArgumentException('The selected dates only cover weekends. Pick at least one working day.');
                }
                $year = (int) $start->format('Y');

                $pdo->beginTransaction();
                try {
                    $employeeQuery = $pdo->prepare(
                        "SELECT employee_id FROM employees
                         WHERE employee_id = :employee_id
                           AND employment_status IN ('Active', 'Probation')
                         FOR UPDATE"
                    );
                    $employeeQuery->execute(['employee_id' => $employeeId]);
                    if ($employeeQuery->fetchColumn() === false) {
                        throw new InvalidArgumentException('Select an active employee.');
                    }

                    $overlap = $pdo->prepare(
                        "SELECT COUNT(*) FROM leave_requests
                         WHERE employee_id = :employee_id
                           AND status IN ('Pending', 'Approved')
                           AND start_date <= :end_date
                           AND end_date >= :start_date"
                    );
                    $overlap->execute([
                        'employee_id' => $employeeId,
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $end->format('Y-m-d'),
                    ]);
                    if ((int) $overlap->fetchColumn() > 0) {
                        throw new InvalidArgumentException('This employee already has a pending or approved request overlapping those dates.');
                    }

                    leave_ensure_balances($pdo, $employeeId, $year);

                    $balanceQuery = $pdo->prepare(
                        'SELECT days_total - days_used
                         FROM leave_balances
                         WHERE employee_id = :employee_id AND leave_year = :leave_year AND leave_type = :leave_type
                         FOR UPDATE'
                    );
                    $balanceQuery->execute(['employee_id' => $employeeId, 'leave_year' => $year, 'leave_type' => $type]);
                    $remaining = (float) $balanceQuery->fetchColumn();

                    $pendingQuery = $pdo->prepare(
                        "SELECT COALESCE(SUM(days_requested), 0) FROM leave_requests
                         WHERE employee_id = :employee_id AND leave_type = :leave_type
                           AND status = 'Pending' AND YEAR(start_date) = :leave_year"
                    );
                    $pendingQuery->execute(['employee_id' => $employeeId, 'leave_type' => $type, 'leave_year' => $year]);
                    $pending = (float) $pendingQuery->fetchColumn();

                    if ($days > $remaining - $pending) {
                        throw new InvalidArgumentException(sprintf(
                            'Not enough %s days left in %d. Requested %d, available %s (after pending requests).',
                            strtolower($type),
                            $year,
                            $days,
                            rtrim(rtrim(number_format(max($remaining - $pending, 0), 1), '0'), '.') ?: '0'
                        ));
                    }

                    $insert = $pdo->prepare(
                        'INSERT INTO leave_requests
                            (employee_id, leave_type, start_date, end_date, days_requested, reason, requested_by)
                         VALUES
                            (:employee_id, :leave_type, :start_date, :end_date, :days_requested, :reason, :requested_by)'
                    );
                    $insert->execute([
                        'employee_id' => $employeeId,
                        'leave_type' => $type,
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $end->format('Y-m-d'),
                        'days_requested' => $days,
                        'reason' => trim($reason) === '' ? null : trim($reason),
                        'requested_by' => (int) $_SESSION['user_id'],
                    ]);
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }

                $feedback = [
                    'type' => 'success',
                    'message' => sprintf('Leave request submitted (%d working day%s).', $days, $days === 1 ? '' : 's'),
                ];
            } else {
                if (!$leaveCanDecide) {
                    http_response_code(403);
                    exit('Only administrators, HR and managers can decide leave requests.');
                }

                $leaveId = filter_var($_POST['leave_id'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                $note = $_POST['decision_note'] ?? '';
                if (
                    $leaveId === false
                    || !is_string($note)
                    || preg_match('//u', $note) !== 1
                    || preg_match_all('/./us', trim($note)) > 255
                ) {
                    throw new InvalidArgumentException('Select a valid request and keep the note under 255 characters.');
                }

                $pdo->beginTransaction();
                try {
                    $requestQuery = $pdo->prepare(
                        'SELECT employee_id, leave_type, days_requested, status, YEAR(start_date) AS leave_year
                         FROM leave_requests WHERE leave_id = :leave_id FOR UPDATE'
                    );
                    $requestQuery->execute(['leave_id' => $leaveId]);
                    $request = $requestQuery->fetch();
                    if ($request === false) {
                        throw new InvalidArgumentException('That leave request no longer exists.');
                    }
                    if ($request['status'] !== 'Pending') {
                        throw new InvalidArgumentException('That request has already been decided.');
                    }
                    if ($ownEmployeeId !== null && (int) $request['employee_id'] === $ownEmployeeId) {
                        throw new InvalidArgumentException('You cannot decide your own leave request.');
                    }

                    if ($action === 'approve') {
                        $year = (int) $request['leave_year'];
                        $days = (float) $request['days_requested'];
                        leave_ensure_balances($pdo, (int) $request['employee_id'], $year);

                        $balanceQuery = $pdo->prepare(
                            'SELECT days_total - days_used
                             FROM leave_balances
                             WHERE employee_id = :employee_id AND leave_year = :leave_year AND leave_type = :leave_type
                             FOR UPDATE'
                        );
                        $balanceQuery->execute([
                            'employee_id' => (int) $request['employee_id'],
                            'leave_year' => $year,
                            'leave_type' => $request['leave_type'],
                        ]);
                        if ($days > (float) $balanceQuery->fetchColumn()) {
                            throw new InvalidArgumentException('The employee no longer has enough days left to approve this request.');
                        }

                        $deduct = $pdo->prepare(
                            'UPDATE leave_balances SET days_used = days_used + :days
                             WHERE employee_id = :employee_id AND leave_year = :leave_year AND leave_type = :leave_type'
                        );
                        $deduct->execute([
                            'days' => $days,
                            'employee_id' => (int) $request['employee_id'],
                            'leave_year' => $year,
                            'leave_type' => $request['leave_type'],
                        ]);
                    }

                    $decide = $pdo->prepare(
                        'UPDATE leave_requests
                         SET status = :status, decided_by = :decided_by, decided_at = NOW(), decision_note = :note
                         WHERE leave_id = :leave_id AND status = \'Pending\''
                    );
                    $decide->execute([
                        'status' => $action === 'approve' ? 'Approved' : 'Rejected',
                        'decided_by' => (int) $_SESSION['user_id'],
                        'note' => trim($note) === '' ? null : trim($note),
                        'leave_id' => $leaveId,
                    ]);
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }

                $feedback = [
                    'type' => 'success',
                    'message' => $action === 'approve'
                        ? 'Request approved and the days were deducted from the balance.'
                        : 'Request rejected. No days were deducted.',
                ];
            }
        } catch (InvalidArgumentException $exception) {
            $feedback['message'] = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log('EMS leave database operation failed (' . (string) $exception->getCode() . ').');
            $feedback['message'] = 'The leave request could not be saved. Make sure leave_tables.sql has been imported, then try again.';
        } catch (Throwable $exception) {
            error_log('EMS leave operation failed (' . get_class($exception) . ').');
            $feedback['message'] = 'The leave request could not be processed. Please try again.';
        }
    }

    $_SESSION['leave_feedback'] = $feedback;
    header('Location: leave.php', true, 303);
    exit;
}

$leaveFeedback = null;
if (isset($_SESSION['leave_feedback']) && is_array($_SESSION['leave_feedback'])) {
    $leaveFeedback = $_SESSION['leave_feedback'];
    unset($_SESSION['leave_feedback']);
}

// ---- Real data for the page (replaces the preview arrays) -----------------
$leaveRequests = [];
$leaveHistory = [];
$leaveBalances = [];
$leaveEmployeeOptions = [];
$leaveApprovedCount = 0;
$leaveYear = (int) date('Y');
$leaveLoadError = null;

try {
    $pdo = ems_db();
    $ownEmployeeId = leave_own_employee_id($pdo, (int) $_SESSION['user_id']);

    // Staff only ever see their own leave; everyone else sees the whole company.
    $scopeSql = '';
    $scopeParams = [];
    if ($leaveIsStaff) {
        $scopeSql = ' AND e.employee_id = :scope_employee_id';
        $scopeParams = ['scope_employee_id' => $ownEmployeeId ?? 0];
    }

    // Create balance rows for anyone who does not have them yet this year.
    $seed = $pdo->prepare(
        "INSERT IGNORE INTO leave_balances (employee_id, leave_year, leave_type, days_total, days_used)
         SELECT e.employee_id, :leave_year, t.leave_type, t.days, 0
         FROM employees e
         CROSS JOIN (
             SELECT 'Vacation' AS leave_type, " . LEAVE_DEFAULT_DAYS['Vacation'] . " AS days
             UNION ALL SELECT 'Sick', " . LEAVE_DEFAULT_DAYS['Sick'] . "
             UNION ALL SELECT 'Personal', " . LEAVE_DEFAULT_DAYS['Personal'] . "
         ) t
         WHERE e.employment_status IN ('Active', 'Probation')"
    );
    $seed->execute(['leave_year' => $leaveYear]);

    $optionsQuery = $pdo->prepare(
        "SELECT e.employee_id, e.full_name
         FROM employees e
         WHERE e.employment_status IN ('Active', 'Probation')" . $scopeSql . '
         ORDER BY e.last_name, e.first_name'
    );
    $optionsQuery->execute($scopeParams);
    foreach ($optionsQuery->fetchAll() as $row) {
        $leaveEmployeeOptions[(int) $row['employee_id']] = (string) $row['full_name'];
    }

    $balanceQuery = $pdo->prepare(
        "SELECT e.employee_id, e.full_name, b.leave_type, (b.days_total - b.days_used) AS remaining
         FROM leave_balances b
         JOIN employees e ON e.employee_id = b.employee_id
         WHERE b.leave_year = :leave_year
           AND e.employment_status IN ('Active', 'Probation')" . $scopeSql . '
         ORDER BY e.last_name, e.first_name'
    );
    $balanceQuery->execute(['leave_year' => $leaveYear] + $scopeParams);
    $balanceRows = [];
    foreach ($balanceQuery->fetchAll() as $row) {
        $id = (int) $row['employee_id'];
        $balanceRows[$id] ??= [
            'name' => (string) $row['full_name'],
            'vacation' => 0,
            'sick' => 0,
            'personal' => 0,
            'tone' => ems_avatar_tone($id),
            'initials' => ems_initials((string) $row['full_name']),
        ];
        $balanceRows[$id][strtolower((string) $row['leave_type'])] = (float) $row['remaining'];
    }
    $leaveBalances = array_values($balanceRows);

    $formatRange = static function (string $from, string $to): string {
        $start = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);
        if ($from === $to) {
            return $start->format('M j, Y');
        }
        return $start->format('Y') === $end->format('Y')
            ? $start->format('M j') . ' - ' . $end->format('M j, Y')
            : $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
    };

    $pendingQuery = $pdo->prepare(
        "SELECT r.leave_id, e.employee_id, e.full_name, r.leave_type, r.start_date, r.end_date,
                r.days_requested, COALESCE(r.reason, '') AS reason
         FROM leave_requests r
         JOIN employees e ON e.employee_id = r.employee_id
         WHERE r.status = 'Pending'" . $scopeSql . '
         ORDER BY r.created_at, r.leave_id'
    );
    $pendingQuery->execute($scopeParams);
    foreach ($pendingQuery->fetchAll() as $row) {
        $leaveRequests[] = [
            'leave_id' => (int) $row['leave_id'],
            'employee' => (string) $row['full_name'],
            'type' => (string) $row['leave_type'],
            'from' => (new DateTimeImmutable((string) $row['start_date']))->format('M j, Y'),
            'to' => (new DateTimeImmutable((string) $row['end_date']))->format('M j, Y'),
            'days' => (int) $row['days_requested'],
            'note' => (string) $row['reason'],
            'tone' => ems_avatar_tone((int) $row['employee_id']),
            'initials' => ems_initials((string) $row['full_name']),
        ];
    }

    $historyQuery = $pdo->prepare(
        "SELECT r.leave_id, e.employee_id, e.full_name, r.leave_type, r.start_date, r.end_date,
                r.days_requested, r.status, COALESCE(NULLIF(r.decision_note, ''), r.reason, '') AS note
         FROM leave_requests r
         JOIN employees e ON e.employee_id = r.employee_id
         WHERE r.status IN ('Approved', 'Rejected')" . $scopeSql . '
         ORDER BY r.decided_at DESC, r.leave_id DESC
         LIMIT 25'
    );
    $historyQuery->execute($scopeParams);
    foreach ($historyQuery->fetchAll() as $row) {
        $leaveHistory[] = [
            'employee' => (string) $row['full_name'],
            'type' => (string) $row['leave_type'],
            'dates' => $formatRange((string) $row['start_date'], (string) $row['end_date']),
            'days' => (int) $row['days_requested'],
            'status' => (string) $row['status'],
            'note' => (string) $row['note'],
            'tone' => ems_avatar_tone((int) $row['employee_id']),
            'initials' => ems_initials((string) $row['full_name']),
        ];
    }

    $approvedQuery = $pdo->prepare(
        "SELECT COUNT(*)
         FROM leave_requests r
         JOIN employees e ON e.employee_id = r.employee_id
         WHERE r.status = 'Approved' AND YEAR(r.start_date) = :leave_year" . $scopeSql
    );
    $approvedQuery->execute(['leave_year' => $leaveYear] + $scopeParams);
    $leaveApprovedCount = (int) $approvedQuery->fetchColumn();
} catch (Throwable $exception) {
    error_log('EMS leave data query failed (' . get_class($exception) . ').');
    $leaveLoadError = 'Leave data could not be loaded. Make sure leave_tables.sql has been imported into ems_db.';
}

$pageKey = 'leave';
require __DIR__ . '/dashboard-page.php';