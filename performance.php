<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
ems_require_authentication();

$performanceCanWrite = in_array($_SESSION['role'], ['Admin', 'Manager'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$performanceCanWrite) {
        http_response_code(403);
        exit('Only administrators and managers can create evaluations.');
    }

    $feedback = [
        'type' => 'error',
        'message' => 'The evaluation could not be saved. Please check the submitted details.',
    ];

    if (!ems_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $feedback['message'] = 'Your session token is invalid. Refresh the page and try again.';
    } else {
        try {
            if (($_POST['performance_action'] ?? null) !== 'create') {
                throw new InvalidArgumentException('Select a supported evaluation action.');
            }

            require_once __DIR__ . '/db.php';
            $pdo = ems_db();

            $employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $periodValue = $_POST['review_period'] ?? null;
            $attendanceValue = $_POST['attendance_pct'] ?? '';
            $trainingValue = $_POST['training_completion_pct'] ?? '';
            $okrValue = $_POST['okr_achievement'] ?? null;
            $competenciesValue = $_POST['core_competencies'] ?? null;
            $feedbackValue = $_POST['feedback_score'] ?? null;
            $status = $_POST['status'] ?? null;
            $comments = $_POST['comments'] ?? '';

            $periodLength = is_string($periodValue)
                ? preg_match_all('/./us', trim($periodValue))
                : false;
            if (
                $employeeId === false
                || !is_string($periodValue)
                || trim($periodValue) === ''
                || $periodLength === false
                || $periodLength > 30
                || !is_string($status)
                || !in_array($status, ['Draft', 'Completed'], true)
                || !is_string($comments)
                || strlen(trim($comments)) > 65535
                || preg_match('//u', trim($comments)) !== 1
            ) {
                throw new InvalidArgumentException('Enter a valid employee, review period, status, and comments.');
            }

            $parsePercentage = static function (mixed $value, bool $required = false): ?string {
                if ($value === '' || $value === null) {
                    if ($required) {
                        throw new InvalidArgumentException('OKR Achievement, Core Competencies and Peer / Manager Feedback are required (0 to 100).');
                    }
                    return null;
                }

                if (
                    !is_string($value)
                    || !preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $value)
                    || (float) $value > 100
                ) {
                    throw new InvalidArgumentException('KPI percentages must be between 0 and 100, with up to two decimal places.');
                }

                return $value;
            };

            $attendance = $parsePercentage($attendanceValue);
            $trainingCompletion = $parsePercentage($trainingValue);
            $okr = $parsePercentage($okrValue, true);
            $competencies = $parsePercentage($competenciesValue, true);
            $feedbackScore = $parsePercentage($feedbackValue, true);

            // Total Score = (OKR x 50%) + (Core Competencies x 30%) + (Peer / Manager Feedback x 20%)
            $score = number_format(
                ((float) $okr * 0.50) + ((float) $competencies * 0.30) + ((float) $feedbackScore * 0.20),
                2,
                '.',
                ''
            );

            $pdo->beginTransaction();
            try {
                $employeeQuery = $pdo->prepare(
                    "SELECT employee_id
                     FROM employees
                     WHERE employee_id = :employee_id
                       AND employment_status IN ('Active', 'Probation')
                     FOR UPDATE"
                );
                $employeeQuery->execute(['employee_id' => $employeeId]);
                $validEmployeeId = $employeeQuery->fetchColumn();
                if ($validEmployeeId === false) {
                    throw new InvalidArgumentException('Select an active employee.');
                }

                $evaluatorQuery = $pdo->prepare(
                    'SELECT employee_id
                     FROM employees
                     WHERE user_id = :user_id
                     LIMIT 1'
                );
                $evaluatorQuery->execute(['user_id' => $_SESSION['user_id']]);
                $evaluatorId = $evaluatorQuery->fetchColumn();
                $evaluatorId = $evaluatorId === false ? null : (int) $evaluatorId;

                $insert = $pdo->prepare(
                    'INSERT INTO kpi_records
                        (employee_id, review_period, attendance_pct, training_completion_pct,
                         okr_achievement, core_competencies, feedback_score,
                         kpi_score, evaluator_id, status, comments)
                     VALUES
                        (:employee_id, :review_period, :attendance_pct, :training_completion_pct,
                         :okr_achievement, :core_competencies, :feedback_score,
                         :kpi_score, :evaluator_id, :status, :comments)'
                );
                $insert->execute([
                    'employee_id' => (int) $validEmployeeId,
                    'review_period' => trim($periodValue),
                    'attendance_pct' => $attendance,
                    'training_completion_pct' => $trainingCompletion,
                    'okr_achievement' => $okr,
                    'core_competencies' => $competencies,
                    'feedback_score' => $feedbackScore,
                    'kpi_score' => $score,
                    'evaluator_id' => $evaluatorId,
                    'status' => $status,
                    'comments' => trim($comments) === '' ? null : trim($comments),
                ]);

                // Deferred audit tracking inserts belong here, inside this transaction.
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }

            $feedback = [
                'type' => 'success',
                'message' => 'Evaluation saved.',
            ];
        } catch (InvalidArgumentException $exception) {
            $feedback['message'] = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log('EMS performance database operation failed (' . (string) $exception->getCode() . ').');
            $sqlState = (string) $exception->getCode();
            $driverCode = isset($exception->errorInfo[1]) ? (int) $exception->errorInfo[1] : 0;
            $feedback['message'] = $sqlState === '23000' && $driverCode === 1062
                ? 'An evaluation already exists for this employee and review period.'
                : 'The evaluation could not be saved. Please try again.';
        } catch (Throwable $exception) {
            error_log('EMS performance operation failed (' . get_class($exception) . ').');
            $feedback['message'] = 'The evaluation could not be saved. Please try again.';
        }
    }

    $_SESSION['performance_feedback'] = $feedback;
    header('Location: performance.php', true, 303);
    exit;
}

$performanceFeedback = null;
if (isset($_SESSION['performance_feedback']) && is_array($_SESSION['performance_feedback'])) {
    $performanceFeedback = $_SESSION['performance_feedback'];
    unset($_SESSION['performance_feedback']);
}

$pageKey = 'performance';
require __DIR__ . '/dashboard-page.php';