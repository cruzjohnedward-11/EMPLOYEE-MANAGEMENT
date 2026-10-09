<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
ems_require_authentication();

$hiringWriterRoles = ['Admin', 'HR', 'Manager'];
$hiringCanWrite = in_array($_SESSION['role'], $hiringWriterRoles, true);

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$hiringCanWrite) {
        http_response_code(403);
        exit('You are not authorized to update hiring records.');
    }

    if (!ems_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $_SESSION['hiring_feedback'] = [
            'type' => 'error',
            'message' => 'Your session token is invalid. Refresh the page and try again.',
        ];
        header('Location: hiring.php', true, 303);
        exit;
    }

    $action = $_POST['hiring_action'] ?? null;
    $message = 'The hiring request could not be completed.';
    $feedbackType = 'error';

    try {
        require_once __DIR__ . '/db.php';
        $pdo = ems_db();

        if ($action === 'create') {
            $nameValue = $_POST['name'] ?? null;
            $emailValue = $_POST['email'] ?? null;
            $jobTitleValue = $_POST['job_title_id'] ?? null;
            $applicationDateValue = $_POST['application_date'] ?? null;
            $sourceValue = $_POST['source'] ?? '';
            $notesValue = $_POST['notes'] ?? '';
            $nameLength = is_string($nameValue) ? preg_match_all('/./us', trim($nameValue)) : false;
            $sourceLength = is_string($sourceValue) ? preg_match_all('/./us', trim($sourceValue)) : false;

            if (
                !is_string($nameValue)
                || trim($nameValue) === ''
                || $nameLength === false
                || $nameLength > 150
                || !is_string($emailValue)
                || !filter_var(trim($emailValue), FILTER_VALIDATE_EMAIL)
                || strlen(trim($emailValue)) > 120
                || !is_string($applicationDateValue)
                || !DateTimeImmutable::createFromFormat('!Y-m-d', $applicationDateValue)
                || DateTimeImmutable::createFromFormat('!Y-m-d', $applicationDateValue)->format('Y-m-d') !== $applicationDateValue
                || !is_string($sourceValue)
                || $sourceLength === false
                || $sourceLength > 80
                || !is_string($notesValue)
                || strlen(trim($notesValue)) > 65535
                || preg_match('//u', trim($notesValue)) !== 1
            ) {
                throw new InvalidArgumentException('Please check the candidate details and try again.');
            }

            $jobTitleId = null;
            if ($jobTitleValue !== '' && $jobTitleValue !== null) {
                $validatedJobTitleId = filter_var($jobTitleValue, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if ($validatedJobTitleId === false) {
                    throw new InvalidArgumentException('Select a valid job title.');
                }

                $jobTitleQuery = $pdo->prepare(
                    'SELECT job_title_id FROM job_titles WHERE job_title_id = :job_title_id AND is_active = 1'
                );
                $jobTitleQuery->execute(['job_title_id' => $validatedJobTitleId]);
                $jobTitleId = $jobTitleQuery->fetchColumn();
                if ($jobTitleId === false) {
                    throw new InvalidArgumentException('Select a valid active job title.');
                }
            }

            $lockStatement = $pdo->prepare("SELECT GET_LOCK('ems_hiring_pipeline', 10)");
            $lockStatement->execute();
            if ((int) $lockStatement->fetchColumn() !== 1) {
                throw new RuntimeException('Hiring pipeline is busy. Please retry the update.');
            }

            try {
                $pdo->beginTransaction();
                try {
                    $queueEntryQuery = $pdo->prepare(
                        'SELECT GREATEST(
                                    CURRENT_TIMESTAMP,
                                    COALESCE(MAX(updated_at) + INTERVAL 1 SECOND, CURRENT_TIMESTAMP)
                                )
                         FROM hiring_pipeline
                         WHERE status = :status'
                    );
                    $queueEntryQuery->execute(['status' => 'Applied']);
                    $queueEntryAt = $queueEntryQuery->fetchColumn();
                    $insert = $pdo->prepare(
                        "INSERT INTO hiring_pipeline
                            (applicant_name, email, credentials, applied_job_title_id,
                             application_date, source, status, updated_at)
                         VALUES
                            (:applicant_name, :email, :credentials, :job_title_id,
                             :application_date, :source, 'Applied', :updated_at)"
                    );
                    $insert->execute([
                        'applicant_name' => trim($nameValue),
                        'email' => trim($emailValue),
                        'credentials' => trim($notesValue) === '' ? null : trim($notesValue),
                        'job_title_id' => $jobTitleId,
                        'application_date' => $applicationDateValue,
                        'source' => trim($sourceValue) === '' ? null : trim($sourceValue),
                        'updated_at' => $queueEntryAt,
                    ]);

                    // Future audit_log writes belong here, in the same transaction as the candidate insert.
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

            $message = 'Candidate added to the Applied stage.';
            $feedbackType = 'success';
        } elseif ($action === 'update_status') {
            $hiringId = filter_var($_POST['hiring_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $targetStatus = $_POST['status'] ?? null;

            if ($hiringId === false || !is_string($targetStatus)) {
                throw new InvalidArgumentException('Select a valid applicant and pipeline status.');
            }

            $allowedTransitions = [
                'Applied' => ['Screening', 'Rejected', 'Withdrawn', 'No Response'],
                'Screening' => ['Interview', 'Rejected', 'Withdrawn', 'No Response'],
                'Interview' => ['Offer', 'Rejected', 'Withdrawn', 'No Response'],
                'Offer' => ['Hired', 'Rejected', 'Withdrawn', 'No Response'],
            ];
            $validStatuses = ['Applied', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected', 'Withdrawn', 'No Response'];
            if (!in_array($targetStatus, $validStatuses, true)) {
                throw new InvalidArgumentException('Select a valid pipeline status.');
            }

            $lockStatement = $pdo->prepare("SELECT GET_LOCK('ems_hiring_pipeline', 10)");
            $lockStatement->execute();
            if ((int) $lockStatement->fetchColumn() !== 1) {
                throw new RuntimeException('Hiring pipeline is busy. Please retry the update.');
            }

            try {
                $capacityQuery = $pdo->prepare(
                    'SELECT setting_value FROM settings WHERE setting_name = :setting_name'
                );
                $capacityQuery->execute(['setting_name' => 'hiring_stage_capacity']);
                $capacityValue = $capacityQuery->fetchColumn();
                $stageCapacity = $capacityValue === false ? 1 : filter_var(
                    $capacityValue,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1, 'max_range' => 50]]
                );
                if ($stageCapacity === false) {
                    throw new RuntimeException('Hiring stage capacity setting is invalid.');
                }

                $pdo->beginTransaction();
                try {
                    $currentQuery = $pdo->prepare(
                        'SELECT status, updated_at, created_at
                         FROM hiring_pipeline
                         WHERE hiring_id = :hiring_id
                         FOR UPDATE'
                    );
                    $currentQuery->execute(['hiring_id' => $hiringId]);
                    $currentApplicant = $currentQuery->fetch();
                    if (!is_array($currentApplicant)) {
                        throw new InvalidArgumentException('The applicant no longer exists.');
                    }
                    $currentStatus = (string) $currentApplicant['status'];
                    if (!in_array($targetStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
                        throw new InvalidArgumentException('That pipeline stage transition is not allowed.');
                    }

                    $activeStage = in_array($currentStatus, ['Applied', 'Screening', 'Interview', 'Offer'], true);
                    if ($activeStage) {
                        $queuePositionQuery = $pdo->prepare(
                            'SELECT COUNT(*)
                             FROM hiring_pipeline
                             WHERE status = :status
                               AND (
                                    updated_at < :updated_at
                                        OR (
                                            updated_at = :same_updated_at
                                            AND (
                                                created_at < :created_at
                                                OR (created_at = :same_created_at AND hiring_id < :hiring_id)
                                            )
                                        )
                               )'
                        );
                        $queuePositionQuery->execute([
                            'status' => $currentStatus,
                            'updated_at' => $currentApplicant['updated_at'],
                            'same_updated_at' => $currentApplicant['updated_at'],
                            'created_at' => $currentApplicant['created_at'],
                            'same_created_at' => $currentApplicant['created_at'],
                            'hiring_id' => $hiringId,
                        ]);
                        if ((int) $queuePositionQuery->fetchColumn() >= $stageCapacity) {
                            throw new InvalidArgumentException(
                                'This applicant is waiting in the stage queue. Advance the active applicant first.'
                            );
                        }
                    }

                    $terminalStatus = in_array($targetStatus, ['Rejected', 'Withdrawn', 'No Response'], true);
                    $isHired = $targetStatus === 'Hired';
                    $targetIsActiveStage = in_array($targetStatus, ['Applied', 'Screening', 'Interview', 'Offer'], true);
                    $queueEntryAt = null;
                    if ($targetIsActiveStage) {
                        $queueEntryQuery = $pdo->prepare(
                            'SELECT GREATEST(
                                        CURRENT_TIMESTAMP,
                                        COALESCE(MAX(updated_at) + INTERVAL 1 SECOND, CURRENT_TIMESTAMP)
                                    )
                             FROM hiring_pipeline
                             WHERE status = :status'
                        );
                        $queueEntryQuery->execute(['status' => $targetStatus]);
                        $queueEntryAt = $queueEntryQuery->fetchColumn();
                    }
                    $update = $pdo->prepare(
                        'UPDATE hiring_pipeline
                         SET status = :status,
                             hire_date = CASE WHEN :is_hired = 1 THEN COALESCE(hire_date, CURRENT_DATE) ELSE hire_date END,
                             decision_date = CASE WHEN :is_terminal = 1 OR :is_hired_date = 1 THEN CURRENT_DATE ELSE decision_date END,
                             unhired_stage = CASE WHEN :is_terminal_stage = 1 THEN :previous_stage ELSE NULL END,
                             updated_at = COALESCE(:updated_at, CURRENT_TIMESTAMP)
                         WHERE hiring_id = :hiring_id'
                    );
                    $update->execute([
                        'status' => $targetStatus,
                        'is_hired' => $isHired ? 1 : 0,
                        'is_terminal' => $terminalStatus ? 1 : 0,
                        'is_hired_date' => $isHired ? 1 : 0,
                        'is_terminal_stage' => $terminalStatus ? 1 : 0,
                        'previous_stage' => $terminalStatus ? $currentStatus : null,
                        'updated_at' => $queueEntryAt,
                        'hiring_id' => $hiringId,
                    ]);

                    // Future audit_log writes belong here, in the same transaction as the status transition.
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

            $message = 'Applicant status updated to ' . $targetStatus . '.';
            $feedbackType = 'success';
        } else {
            throw new InvalidArgumentException('Select a valid hiring action.');
        }
    } catch (InvalidArgumentException $exception) {
        $message = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('EMS hiring operation failed (' . get_class($exception) . ').');
        $message = 'The hiring request could not be completed. Please try again.';
    }

    $_SESSION['hiring_feedback'] = [
        'type' => $feedbackType,
        'message' => $message,
    ];
    header('Location: hiring.php', true, 303);
    exit;
}

if (isset($_SESSION['hiring_feedback']) && is_array($_SESSION['hiring_feedback'])) {
    $hiringFeedback = $_SESSION['hiring_feedback'];
    unset($_SESSION['hiring_feedback']);
}

$pageKey = 'hiring';
require __DIR__ . '/dashboard-page.php';
