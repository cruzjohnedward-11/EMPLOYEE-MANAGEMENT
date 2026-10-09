<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/dashboard-helpers.php';
ems_require_authentication();

$employeeWriterRoles = ['Admin', 'HR'];
$employeeCanWrite = in_array($_SESSION['role'], $employeeWriterRoles, true);
$employeeFeedback = null;

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$employeeCanWrite) {
        http_response_code(403);
        exit('You are not authorized to update employee profiles.');
    }

    if (!ems_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $_SESSION['employee_feedback'] = [
            'type' => 'error',
            'message' => 'Your session token is invalid. Refresh the page and try again.',
        ];
        header('Location: employees.php', true, 303);
        exit;
    }

    $employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $emailValue = $_POST['email'] ?? '';
    $phoneValue = $_POST['phone_number'] ?? '';
    $addressValue = $_POST['address'] ?? '';
    $emergencyContactValue = $_POST['emergency_contact'] ?? '';
    $departmentValue = $_POST['department_id'] ?? '';
    $jobTitleValue = $_POST['job_title_id'] ?? '';
    $statusValue = $_POST['employment_status'] ?? null;
    $allowedStatuses = ['Active', 'Probation', 'Resigned', 'Terminated'];

    if (
        $employeeId === false
        || !is_string($emailValue)
        || (trim($emailValue) !== '' && (!filter_var(trim($emailValue), FILTER_VALIDATE_EMAIL) || strlen(trim($emailValue)) > 120))
        || !is_string($phoneValue)
        || strlen(trim($phoneValue)) > 30
        || !is_string($addressValue)
        || strlen(trim($addressValue)) > 255
        || !is_string($emergencyContactValue)
        || strlen(trim($emergencyContactValue)) > 255
        || !is_string($statusValue)
        || !in_array($statusValue, $allowedStatuses, true)
    ) {
        $_SESSION['employee_feedback'] = [
            'type' => 'error',
            'message' => 'Please check the employee profile fields and try again.',
        ];
        header('Location: employees.php', true, 303);
        exit;
    }

    foreach ([$emailValue, $phoneValue, $addressValue, $emergencyContactValue] as $textValue) {
        if (preg_match('//u', $textValue) !== 1) {
            $_SESSION['employee_feedback'] = [
                'type' => 'error',
                'message' => 'Profile text must use valid UTF-8.',
            ];
            header('Location: employees.php', true, 303);
            exit;
        }
    }

    $departmentId = null;
    if ($departmentValue !== '') {
        $departmentId = filter_var($departmentValue, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($departmentId === false) {
            $_SESSION['employee_feedback'] = [
                'type' => 'error',
                'message' => 'Select a valid department.',
            ];
            header('Location: employees.php', true, 303);
            exit;
        }
    }

    $jobTitleId = null;
    if ($jobTitleValue !== '') {
        $jobTitleId = filter_var($jobTitleValue, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($jobTitleId === false) {
            $_SESSION['employee_feedback'] = [
                'type' => 'error',
                'message' => 'Select a valid job title.',
            ];
            header('Location: employees.php', true, 303);
            exit;
        }
    }

    try {
        require_once __DIR__ . '/db.php';
        $pdo = ems_db();

        $pdo->beginTransaction();
        try {
            if ($departmentId !== null) {
                $departmentCheck = $pdo->prepare(
                    'SELECT department_id FROM departments WHERE department_id = :department_id AND is_active = 1'
                );
                $departmentCheck->execute(['department_id' => $departmentId]);
                if ($departmentCheck->fetchColumn() === false) {
                    throw new InvalidArgumentException('Select an active department.');
                }
            }

            if ($jobTitleId !== null) {
                $jobTitleCheck = $pdo->prepare(
                    'SELECT department_id FROM job_titles WHERE job_title_id = :job_title_id AND is_active = 1'
                );
                $jobTitleCheck->execute(['job_title_id' => $jobTitleId]);
                $jobTitleDepartmentId = $jobTitleCheck->fetchColumn();
                if ($jobTitleDepartmentId === false) {
                    throw new InvalidArgumentException('Select an active job title.');
                }
                if ($departmentId !== null && (int) $jobTitleDepartmentId !== $departmentId) {
                    throw new InvalidArgumentException('The selected job title does not belong to that department.');
                }
                if ($departmentId === null) {
                    $departmentId = (int) $jobTitleDepartmentId;
                }
            }

            $update = $pdo->prepare(
                'UPDATE employees
                 SET email = :email,
                     phone_number = :phone_number,
                     address = :address,
                     emergency_contact = :emergency_contact,
                     department_id = :department_id,
                     job_title_id = :job_title_id,
                     employment_status = :employment_status,
                     separation_date = CASE
                         WHEN :is_separated = 1 THEN COALESCE(separation_date, CURRENT_DATE)
                         ELSE separation_date
                     END
                 WHERE employee_id = :employee_id'
            );
            $update->execute([
                'email' => trim($emailValue) === '' ? null : trim($emailValue),
                'phone_number' => trim($phoneValue) === '' ? null : trim($phoneValue),
                'address' => trim($addressValue) === '' ? null : trim($addressValue),
                'emergency_contact' => trim($emergencyContactValue) === '' ? null : trim($emergencyContactValue),
                'department_id' => $departmentId,
                'job_title_id' => $jobTitleId,
                'employment_status' => $statusValue,
                'is_separated' => in_array($statusValue, ['Resigned', 'Terminated'], true) ? 1 : 0,
                'employee_id' => $employeeId,
            ]);

            if ($update->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT employee_id FROM employees WHERE employee_id = :employee_id');
                $exists->execute(['employee_id' => $employeeId]);
                if ($exists->fetchColumn() === false) {
                    throw new InvalidArgumentException('The employee profile was not found.');
                }
            }

            // Future audit_log writes belong here, in the same transaction as the profile update.
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        $_SESSION['employee_feedback'] = [
            'type' => 'success',
            'message' => 'Employee profile updated.',
        ];
    } catch (InvalidArgumentException $exception) {
        $_SESSION['employee_feedback'] = [
            'type' => 'error',
            'message' => $exception->getMessage(),
        ];
    } catch (Throwable $exception) {
        error_log('EMS employee profile update failed (' . get_class($exception) . ').');
        $_SESSION['employee_feedback'] = [
            'type' => 'error',
            'message' => 'The employee profile could not be updated. Please try again.',
        ];
    }

    header('Location: employees.php', true, 303);
    exit;
}

if (isset($_SESSION['employee_feedback']) && is_array($_SESSION['employee_feedback'])) {
    $employeeFeedback = $_SESSION['employee_feedback'];
    unset($_SESSION['employee_feedback']);
}

$searchValue = $_GET['search'] ?? '';
$employeeSearch = is_string($searchValue) ? trim($searchValue) : '';
$departmentFilter = $_GET['department_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$employeeRows = [];
$employeeTotalCount = 0;
$departmentOptions = [];
$jobTitleOptions = [];

try {
    require_once __DIR__ . '/db.php';
    $pdo = ems_db();

    $departments = $pdo->query(
        'SELECT department_id, department_name
         FROM departments
         WHERE is_active = 1
         ORDER BY department_name'
    );
    $departmentOptions = $departments->fetchAll(PDO::FETCH_KEY_PAIR);

    $jobTitles = $pdo->query(
        'SELECT job_title_id, job_title_name
         FROM job_titles
         WHERE is_active = 1
         ORDER BY job_title_name'
    );
    $jobTitleOptions = $jobTitles->fetchAll(PDO::FETCH_KEY_PAIR);

    $conditions = [];
    $parameters = [];
    if ($employeeSearch !== '') {
        $conditions[] = '(e.first_name LIKE :first_name OR e.last_name LIKE :last_name OR e.full_name LIKE :full_name OR e.employee_number LIKE :employee_number OR e.email LIKE :email)';
        $searchTerm = '%' . $employeeSearch . '%';
        $parameters['first_name'] = $searchTerm;
        $parameters['last_name'] = $searchTerm;
        $parameters['full_name'] = $searchTerm;
        $parameters['employee_number'] = $searchTerm;
        $parameters['email'] = $searchTerm;
    }

    if ($departmentFilter !== '') {
        $validatedDepartmentFilter = filter_var($departmentFilter, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($validatedDepartmentFilter === false) {
            throw new InvalidArgumentException('Invalid department filter.');
        }
        $conditions[] = 'e.department_id = :department_id';
        $parameters['department_id'] = $validatedDepartmentFilter;
    }

    $allowedStatuses = ['Active', 'Probation', 'Resigned', 'Terminated'];
    if ($statusFilter !== '') {
        if (!is_string($statusFilter) || !in_array($statusFilter, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Invalid employment status filter.');
        }
        $conditions[] = 'e.employment_status = :employment_status';
        $parameters['employment_status'] = $statusFilter;
    }

    $query = 'SELECT e.employee_id, e.employee_number, e.first_name, e.last_name,
                     e.full_name, e.email, e.phone_number, e.address, e.emergency_contact,
                     e.department_id, e.job_title_id, e.employment_status, e.hire_date,
                     d.department_name, j.job_title_name,
                     COALESCE(s.base_salary, 0) AS base_salary
              FROM employees e
              LEFT JOIN departments d ON d.department_id = e.department_id
              LEFT JOIN job_titles j ON j.job_title_id = e.job_title_id
              LEFT JOIN v_current_base_salary s ON s.employee_id = e.employee_id';
    if ($conditions !== []) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $query .= ' ORDER BY e.last_name, e.first_name, e.employee_id';

    $employeeStatement = $pdo->prepare($query);
    $employeeStatement->execute($parameters);
    $employeeRows = $employeeStatement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($employeeRows as &$employeeRow) {
        $employeeRow['salary'] = (float) $employeeRow['base_salary'];
        $employeeRow['name'] = (string) $employeeRow['full_name'];
        $employeeRow['role'] = (string) ($employeeRow['job_title_name'] ?? 'Unassigned');
        $employeeRow['department'] = (string) ($employeeRow['department_name'] ?? 'Unassigned');
        $employeeRow['status'] = (string) $employeeRow['employment_status'];
        $employeeRow['hired'] = date('M j, Y', strtotime((string) $employeeRow['hire_date']));
        $employeeRow['tone'] = ems_avatar_tone((int) $employeeRow['employee_id']);
        $employeeRow['initials'] = ems_initials((string) $employeeRow['full_name']);
    }
    unset($employeeRow);

    $countQuery = 'SELECT COUNT(*) FROM employees e';
    if ($conditions !== []) {
        $countQuery .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $countStatement = $pdo->prepare($countQuery);
    $countStatement->execute($parameters);
    $employeeTotalCount = (int) $countStatement->fetchColumn();
    $employees = $employeeRows;
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    $employeeFeedback = [
        'type' => 'error',
        'message' => $exception->getMessage(),
    ];
} catch (Throwable $exception) {
    error_log('EMS employee directory read failed (' . get_class($exception) . ').');
    http_response_code(503);
    $employeeFeedback = [
        'type' => 'error',
        'message' => 'Employee directory data is temporarily unavailable.',
    ];
    $employeeTotalCount = 0;
}

$pageKey = 'employees';
require __DIR__ . '/dashboard-page.php';