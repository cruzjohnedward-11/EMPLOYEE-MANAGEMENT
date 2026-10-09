<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if (ems_is_authenticated()) {
    header('Location: overview.php', true, 303);
    exit;
}

$identifier = '';
$feedback = '';
$feedbackStatus = 200;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawIdentifier = $_POST['identifier'] ?? null;
    $identifier = is_string($rawIdentifier) ? trim($rawIdentifier) : '';
    $password = $_POST['password'] ?? null;
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!ems_valid_csrf_token($csrfToken)) {
        $feedback = 'Your sign-in session expired. Refresh the page and try again.';
        $feedbackStatus = 403;
    } elseif (
        $identifier === ''
        || strlen($identifier) > 120
        || !is_string($password)
        || $password === ''
        || strlen($password) > 4096
    ) {
        $feedback = 'Enter your username or email and password.';
        $feedbackStatus = 400;
    } else {
        try {
            $statement = ems_db()->prepare(
                'SELECT user_id, username, password_hash, role, is_active
                 FROM users
                 WHERE username = :username OR email = :email
                 ORDER BY (username = :username_order) DESC
                 LIMIT 2'
            );
            $statement->execute([
                'username' => $identifier,
                'email' => $identifier,
                'username_order' => $identifier,
            ]);

            $authenticatedUser = null;
            foreach ($statement->fetchAll() as $user) {
                if (
                    (int) $user['is_active'] === 1
                    && password_verify($password, (string) $user['password_hash'])
                ) {
                    $authenticatedUser = $user;
                    break;
                }
            }

            if (
                $authenticatedUser === null
                || !in_array($authenticatedUser['role'], EMS_ALLOWED_ROLES, true)
            ) {
                if ($authenticatedUser !== null) {
                    error_log('EMS login rejected an account with a role outside the users.role enum.');
                }
                $feedback = 'The username/email or password is incorrect, or the account is inactive.';
                $feedbackStatus = 401;
            } else {
                $update = ems_db()->prepare(
                    'UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id'
                );
                $update->execute(['user_id' => (int) $authenticatedUser['user_id']]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $authenticatedUser['user_id'];
                $_SESSION['username'] = (string) $authenticatedUser['username'];
                $_SESSION['role'] = (string) $authenticatedUser['role'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $roleRedirects = [
                    'Admin' => 'overview.php',
                    'HR' => 'overview.php',
                    'Manager' => 'overview.php',
                    'Staff' => 'overview.php',
                ];
                header('Location: ' . $roleRedirects[$_SESSION['role']], true, 303);
                exit;
            }
        } catch (PDOException $exception) {
            error_log('EMS login database operation failed: ' . $exception->getMessage());
            $feedback = 'Sign-in is temporarily unavailable. Please try again later.';
            $feedbackStatus = 503;
        }
    }

    http_response_code($feedbackStatus);
}

$dashboardStylesVersion = (int) filemtime(__DIR__ . '/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f6f8">
    <title>Sign in - EMS</title>
    <link rel="stylesheet" href="dashboard.css?v=<?= $dashboardStylesVersion ?>">
</head>
<body class="login-page">
    <main class="login-layout">
        <section class="login-story" aria-labelledby="login-story-title">
            <a class="login-brand" href="login.php" aria-label="EMS People Operations">
                <span class="login-brand-mark" aria-hidden="true">E</span>
                <span><strong>EMS</strong><small>People Operations</small></span>
            </a>
            <div class="login-story-copy">
                <span class="login-eyebrow">PEOPLE, IN FOCUS</span>
                <h1 id="login-story-title">A clearer view of your people operations.</h1>
                <p>Bring employee records, hiring, performance, and time off into one considered workspace.</p>
            </div>
            <div class="login-story-summary" aria-label="Workspace overview">
                <div><strong>09</strong><span>Active employees</span></div>
                <div><strong>04</strong><span>Teams in view</span></div>
                <div><strong>01</strong><span>Shared workspace</span></div>
            </div>
            <p class="login-story-footer">A practical workspace for better people decisions.</p>
        </section>

        <section class="login-content" aria-labelledby="login-title">
            <div class="login-card">
                <p class="login-kicker">WELCOME BACK</p>
                <h2 id="login-title">Sign in to EMS</h2>
                <p class="login-description">Use your work account to continue to your workspace.</p>

                <form class="login-form" method="post" action="login.php" aria-label="Sign in">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <label for="login-identifier">Username or work email</label>
                    <input id="login-identifier" name="identifier" type="text" value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>" placeholder="Username or you@company.com" autocomplete="username" required>

                    <label for="login-password">Password</label>
                    <input id="login-password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>

                    <button class="btn-primary login-submit" type="submit">Sign in</button>
                    <p class="login-feedback" role="status" aria-live="polite"><?= htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8') ?></p>
                </form>

            </div>
            <p class="login-legal">Employee Management System <span aria-hidden="true">&middot;</span> Demo environment</p>
        </section>
    </main>
</body>
</html>
