<?php
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

                <div class="login-form" role="group" aria-label="Sign-in preview">
                    <label for="login-email">Work email</label>
                    <input id="login-email" type="email" placeholder="you@company.com" autocomplete="username" required>

                    <label for="login-password">Password</label>
                    <input id="login-password" type="password" placeholder="Enter your password" autocomplete="current-password" required>

                    <button class="btn-primary login-submit" type="button" id="login-submit">Sign in</button>
                    <p class="login-feedback" id="login-feedback" role="status" aria-live="polite"></p>
                </div>

                <div class="login-preview-note">
                    <strong>UI preview</strong>
                    <span>Sign-in is not connected yet. No credentials are sent or saved.</span>
                </div>
                <a class="login-preview-link" href="overview.php">Explore the demo workspace <span aria-hidden="true">&rarr;</span></a>
            </div>
            <p class="login-legal">Employee Management System <span aria-hidden="true">&middot;</span> Demo environment</p>
        </section>
    </main>
    <script>
        document.getElementById('login-submit').addEventListener('click', function () {
            const email = document.getElementById('login-email');
            const password = document.getElementById('login-password');
            const feedback = document.getElementById('login-feedback');

            if (!email.reportValidity() || !password.reportValidity()) {
                return;
            }

            password.value = '';
            feedback.textContent = 'Authentication is not configured. Use the demo workspace link to explore EMS.';
        });
    </script>
</body>
</html>
