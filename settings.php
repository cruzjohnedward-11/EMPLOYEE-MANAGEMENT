<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
ems_require_authentication();

$settingsCanWrite = $_SESSION['role'] === 'Admin';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$settingsCanWrite) {
        http_response_code(403);
        exit('Only administrators can change settings.');
    }

    $response = [
        'type' => 'error',
        'message' => 'The settings request could not be completed.',
    ];
    $action = $_POST['settings_action'] ?? null;

    if (!ems_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $response['message'] = 'Your session token is invalid. Refresh the page and try again.';
    } else {
        try {
            require_once __DIR__ . '/db.php';
            $pdo = ems_db();

            if ($action === 'create_department') {
                $name = $_POST['department_name'] ?? null;
                $code = $_POST['department_code'] ?? null;
                $description = $_POST['description'] ?? '';

                $nameLength = is_string($name) ? preg_match_all('/./us', trim($name)) : false;
                $codeLength = is_string($code) ? preg_match_all('/./us', trim($code)) : false;
                if (
                    !is_string($name)
                    || trim($name) === ''
                    || $nameLength === false
                    || $nameLength > 100
                    || !is_string($code)
                    || trim($code) === ''
                    || $codeLength === false
                    || $codeLength > 20
                    || !is_string($description)
                    || strlen(trim($description)) > 65535
                    || preg_match('//u', trim((string) $description)) !== 1
                ) {
                    throw new InvalidArgumentException('Enter a valid department name, code, and description.');
                }

                $pdo->beginTransaction();
                try {
                    $insert = $pdo->prepare(
                        'INSERT INTO departments (department_name, department_code, description)
                         VALUES (:department_name, :department_code, :description)'
                    );
                    $insert->execute([
                        'department_name' => trim($name),
                        'department_code' => trim($code),
                        'description' => trim($description) === '' ? null : trim($description),
                    ]);

                    // Future audit-log writes belong here, within the department transaction.
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }

                $response = [
                    'type' => 'success',
                    'message' => 'Department created.',
                ];
            } elseif ($action === 'create_job_title') {
                $name = $_POST['job_title_name'] ?? null;
                $description = $_POST['job_description'] ?? '';
                $level = $_POST['level'] ?? '';
                $payGrade = $_POST['pay_grade'] ?? '';
                $departmentValue = $_POST['department_id'] ?? null;
                $minSalaryValue = $_POST['min_salary'] ?? '';
                $maxSalaryValue = $_POST['max_salary'] ?? '';

                $nameLength = is_string($name) ? preg_match_all('/./us', trim($name)) : false;
                $descriptionLength = is_string($description) ? strlen(trim($description)) : false;
                $payGradeLength = is_string($payGrade) ? preg_match_all('/./us', trim($payGrade)) : false;
                if (
                    !is_string($name)
                    || trim($name) === ''
                    || $nameLength === false
                    || $nameLength > 100
                    || !is_string($description)
                    || $descriptionLength === false
                    || $descriptionLength > 65535
                    || preg_match('//u', trim($description)) !== 1
                    || !is_string($level)
                    || ($level !== '' && !in_array($level, ['Junior', 'Mid', 'Lead'], true))
                    || !is_string($payGrade)
                    || $payGradeLength === false
                    || $payGradeLength > 20
                    || preg_match('//u', trim($payGrade)) !== 1
                    || !is_string($departmentValue)
                ) {
                    throw new InvalidArgumentException('Enter valid job-title details and choose a department.');
                }

                $departmentId = filter_var($departmentValue, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if ($departmentId === false) {
                    throw new InvalidArgumentException('Choose a valid department.');
                }

                $parseSalary = static function (mixed $value): ?string {
                    if ($value === '' || $value === null) {
                        return null;
                    }
                    if (
                        !is_string($value)
                        || !preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $value)
                        || (float) $value < 0
                        || (float) $value > 9999999999.99
                    ) {
                        throw new InvalidArgumentException('Enter non-negative salary amounts with up to two decimal places.');
                    }

                    return $value;
                };
                $minSalary = $parseSalary($minSalaryValue);
                $maxSalary = $parseSalary($maxSalaryValue);
                if ($minSalary !== null && $maxSalary !== null && (float) $minSalary > (float) $maxSalary) {
                    throw new InvalidArgumentException('Minimum salary cannot exceed maximum salary.');
                }

                $pdo->beginTransaction();
                try {
                    $departmentCheck = $pdo->prepare(
                        'SELECT department_id FROM departments WHERE department_id = :department_id AND is_active = 1'
                    );
                    $departmentCheck->execute(['department_id' => $departmentId]);
                    if ($departmentCheck->fetchColumn() === false) {
                        throw new InvalidArgumentException('Choose an active department.');
                    }

                    $insert = $pdo->prepare(
                        'INSERT INTO job_titles
                            (job_title_name, job_description, level, pay_grade, min_salary, max_salary, department_id)
                         VALUES
                            (:job_title_name, :job_description, :level, :pay_grade, :min_salary, :max_salary, :department_id)'
                    );
                    $insert->execute([
                        'job_title_name' => trim($name),
                        'job_description' => trim($description) === '' ? null : trim($description),
                        'level' => $level === '' ? null : $level,
                        'pay_grade' => trim($payGrade) === '' ? null : trim($payGrade),
                        'min_salary' => $minSalary,
                        'max_salary' => $maxSalary,
                        'department_id' => $departmentId,
                    ]);

                    // Future audit-log writes belong here, within the job-title transaction.
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }

                $response = [
                    'type' => 'success',
                    'message' => 'Job title created.',
                ];
            } elseif ($action === 'update_hiring_capacity') {
                $capacityValue = $_POST['hiring_stage_capacity'] ?? null;
                $stageCapacity = filter_var($capacityValue, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1, 'max_range' => 50],
                ]);
                if ($stageCapacity === false) {
                    throw new InvalidArgumentException('Set stage capacity to a whole number from 1 to 50.');
                }

                $lockStatement = $pdo->prepare("SELECT GET_LOCK('ems_hiring_pipeline', 10)");
                $lockStatement->execute();
                if ((int) $lockStatement->fetchColumn() !== 1) {
                    throw new RuntimeException('The hiring pipeline is busy. Please try again.');
                }

                try {
                    $pdo->beginTransaction();
                    try {
                        $saveSetting = $pdo->prepare(
                            "INSERT INTO settings (setting_name, setting_value, category)
                             VALUES (:setting_name, :setting_value, 'hiring')
                             ON DUPLICATE KEY UPDATE
                                setting_value = VALUES(setting_value),
                                category = VALUES(category)"
                        );
                        $saveSetting->execute([
                            'setting_name' => 'hiring_stage_capacity',
                            'setting_value' => (string) $stageCapacity,
                        ]);

                        // Future audit-log writes belong here, within the capacity-setting transaction.
                        $pdo->commit();
                    } catch (Throwable $exception) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $exception;
                    }
                } finally {
                    $pdo->query("SELECT RELEASE_LOCK('ems_hiring_pipeline')");
                }

                $response = [
                    'type' => 'success',
                    'message' => 'Hiring stage capacity updated.',
                ];
            } elseif ($action === 'update_display_limits') {
                $displayLimitDefinitions = [
                    'attendance_month_limit' => ['min' => 1, 'max' => 60, 'label' => 'attendance trend months'],
                    'department_chart_limit' => ['min' => 1, 'max' => 50, 'label' => 'departments in the chart'],
                    'evaluation_page_size' => ['min' => 5, 'max' => 100, 'label' => 'evaluations per page'],
                    'employee_page_size' => ['min' => 5, 'max' => 100, 'label' => 'employees per page'],
                ];
                $validatedDisplayLimits = [];
                foreach ($displayLimitDefinitions as $key => $definition) {
                    $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, [
                        'options' => [
                            'min_range' => $definition['min'],
                            'max_range' => $definition['max'],
                        ],
                    ]);
                    if ($value === false) {
                        throw new InvalidArgumentException(
                            'Set ' . $definition['label'] . ' to a whole number from '
                            . $definition['min'] . ' to ' . $definition['max'] . '.'
                        );
                    }
                    $validatedDisplayLimits['dashboard_' . $key] = (string) $value;
                }

                $pdo->beginTransaction();
                try {
                    $saveSetting = $pdo->prepare(
                        "INSERT INTO settings (setting_name, setting_value, category)
                         VALUES (:setting_name, :setting_value, 'display')
                         ON DUPLICATE KEY UPDATE
                            setting_value = VALUES(setting_value),
                            category = VALUES(category)"
                    );
                    foreach ($validatedDisplayLimits as $settingName => $settingValue) {
                        $saveSetting->execute([
                            'setting_name' => $settingName,
                            'setting_value' => $settingValue,
                        ]);
                    }

                    // Future audit-log writes belong here, within the display-limit transaction.
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }

                $response = [
                    'type' => 'success',
                    'message' => 'Dashboard and history display limits updated.',
                ];
            } else {
                throw new InvalidArgumentException('Select a supported settings action.');
            }
        } catch (InvalidArgumentException $exception) {
            $response = [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ];
        } catch (PDOException $exception) {
            error_log('EMS settings database operation failed (' . (string) $exception->getCode() . ').');
            $sqlState = (string) $exception->getCode();
            $driverCode = isset($exception->errorInfo[1]) ? (int) $exception->errorInfo[1] : 0;
            $response = [
                'type' => 'error',
                'message' => $sqlState === '23000' && $driverCode === 1062
                    ? 'That department code/name or job title already exists.'
                    : 'The settings request could not be completed. Please try again.',
            ];
        } catch (Throwable $exception) {
            error_log('EMS settings operation failed (' . get_class($exception) . ').');
            $response = [
                'type' => 'error',
                'message' => 'The settings request could not be completed. Please try again.',
            ];
        }
    }

    $_SESSION['settings_feedback'] = $response;
    header('Location: settings.php', true, 303);
    exit;
}

$settingsFeedback = null;
if (isset($_SESSION['settings_feedback']) && is_array($_SESSION['settings_feedback'])) {
    $settingsFeedback = $_SESSION['settings_feedback'];
    unset($_SESSION['settings_feedback']);
}

try {
    require_once __DIR__ . '/db.php';
    $departmentStatement = ems_db()->query(
        'SELECT department_id, department_name
         FROM departments
         WHERE is_active = 1
         ORDER BY department_name'
    );
    $departmentOptions = $departmentStatement->fetchAll(PDO::FETCH_KEY_PAIR);
    $capacityStatement = ems_db()->prepare(
        'SELECT setting_value FROM settings WHERE setting_name = :setting_name'
    );
    $capacityStatement->execute(['setting_name' => 'hiring_stage_capacity']);
    $capacityValue = $capacityStatement->fetchColumn();
    $hiringStageCapacity = $capacityValue === false
        ? 1
        : filter_var($capacityValue, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 50],
        ]);
    if ($hiringStageCapacity === false) {
        throw new RuntimeException('Hiring stage capacity setting is invalid.');
    }
    $displayLimitDefaults = [
        'attendance_month_limit' => 12,
        'department_chart_limit' => 10,
        'evaluation_page_size' => 10,
        'employee_page_size' => 10,
    ];
    $displayLimitNames = array_map(
        static fn (string $key): string => 'dashboard_' . $key,
        array_keys($displayLimitDefaults)
    );
    $displayLimitQuery = ems_db()->prepare(
        'SELECT setting_name, setting_value FROM settings WHERE setting_name IN (?, ?, ?, ?)'
    );
    $displayLimitQuery->execute($displayLimitNames);
    $storedDisplayLimits = $displayLimitQuery->fetchAll(PDO::FETCH_KEY_PAIR);
    $displayLimits = [];
    foreach ($displayLimitDefaults as $key => $default) {
        $minimum = str_ends_with($key, '_page_size') ? 5 : 1;
        $maximum = str_ends_with($key, '_page_size') ? 100 : ($key === 'attendance_month_limit' ? 60 : 50);
        $storedValue = $storedDisplayLimits['dashboard_' . $key] ?? null;
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
} catch (Throwable $exception) {
    error_log('EMS settings department list read failed (' . get_class($exception) . ').');
    http_response_code(503);
    $settingsFeedback = [
        'type' => 'error',
        'message' => 'Settings data is temporarily unavailable.',
    ];
    $departmentOptions = [];
    $hiringStageCapacity = 1;
    $displayLimits = [
        'attendance_month_limit' => 12,
        'department_chart_limit' => 10,
        'evaluation_page_size' => 10,
        'employee_page_size' => 10,
    ];
}

$pageKey = 'settings';
require __DIR__ . '/dashboard-page.php';
