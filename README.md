# Employee Management System (EMS)

A clean, native PHP Employee Management System built to handle basic company workflows, role-based access, dashboard charts, and dual deployment (XAMPP or Docker).

---

## ⚙️ How It Works (Database Routing)

The system automatically detects your environment and routes your database traffic without manual configuration changes:

*   **Docker Mode:** Uses the `EMS_DB_HOST=db` setting supplied by Docker Compose.
*   **XAMPP Mode:** Defaults to standard `127.0.0.1` (localhost).

---

## 🚀 Setup Instructions

Choose **one** of the paths below to get the application running on your machine.

### Option A: XAMPP Setup (Traditional Local Server)

#### Prerequisites
*   [XAMPP](https://apachefriends.org) installed with PHP 8.2 or higher.

#### Step-by-Step
1.  **Move the project folder** into your XAMPP server directory:
    `C:\xampp\htdocs\EMPLOYEE-MANAGEMENT`
2.  **Start XAMPP:** Open the XAMPP Control Panel and start both **Apache** and **MySQL**.
3.  **Create the Database:** Open your browser, go to `http://localhost/phpmyadmin`, and create a new database named **`ems_db`**.
4.  **Import Schema:** Click the **Import** tab inside `ems_db`, select the `sql/employee_management.sql` file from your project folder, and click **Go**.
5.  **Open the App:** Navigate to your login gate:
    👉 **`http://localhost/EMPLOYEE-MANAGEMENT/php/login.php`**

---

### Option B: Docker Setup (Zero-Configuration Server)

#### Prerequisites
*   [Docker Desktop](https://docker.com) installed and running.

#### Step-by-Step
1.  Open your terminal in the **root project directory**.
2.  Start the background containers:
    ```bash
    docker compose up -d
    ```
3.  **Open the App:** Navigate to:
    👉 **`http://localhost:8080/php/login.php`**
4.  *To stop the server at any time, run:* `docker compose down`

---

## 📊 Generating Mock Data (Seeder Script)

The project includes a command-line script to quickly populate your database with 100 fake employees, attendance logs, and dashboard metrics for testing.

### Running the Seeder via Terminal
Open your terminal in the project root folder and choose one of these options:

#### 1. Wipe & Fresh Start (Recommended)
This clears out any old, broken test entries and installs 100 perfectly clean, linked records:
```bash
# General Command
php php/tools/seeder.php --flush

# Windows PowerShell Fix (If the raw command just opens the file in VS Code)
& C:\xampp\php\php.exe php\tools\seeder.php --flush
```

#### 2. Append More Rows Safely
Adds a new batch of fake records without erasing your current database entries:
```bash
php php/tools/seeder.php
```

*Note: The seeder script will print out a randomized temporary password when it runs successfully. Save it to log in with the fake users! The `--flush` option permanently clears the listed tables, including leave requests and balances, after you type `FLUSH ems_db` at the confirmation prompt. Settings and views are preserved.*

---

## 🛠️ Main App Features

*   **Security Gate (`login.php`):** Checks roles, verifies active accounts, enforces tokenized **CSRF defenses** on forms, and handles session security.
*   **Live Dashboard (`dashboard-data.php`):** Pulls live database metrics directly into clean JavaScript tracking graphs.
*   **Hiring Pipeline (`hiring.php`):** Add new candidates and move them down a multi-stage tracking workflow.
*   **Employee Registry (`employees.php`):** Your main company directory sheet. Admin and HR can edit profiles.
*   **System Settings (`settings.php`):** Admin-only area to add fresh departments and adjust pay bands.

---

## 🧪 Testing Checklist

Run these quick checks to prove everything is working correctly:

1.  **Security Barrier Check:** Try navigating to `http://localhost/EMPLOYEE-MANAGEMENT/php/employees.php` while logged out. You should be automatically kicked back to `login.php`.
2.  **CSRF Form Test:** Open your browser's Developer Tools (`F12`), delete the hidden `csrf_token` input value from the login form, and hit submit. The backend should block your request.
3.  **Hiring Lifecycle Loop:** Create a test candidate in **Hiring**. Change their status dropdown to **Hired**. Go open the **Employees** list; they will have automatically vanished from hiring and generated a brand-new staff record card.

## File layout

| Folder | Contents |
| --- | --- |
| `php/` | Application pages, authentication, database access, shared helpers |
| `php/tools/` | CLI seeder and regression checks |
| `html/` | Static UI previews and sign-in launcher |
| `css/` | Stylesheets |
| `js/` | Browser scripts |
| `sql/` | Database schema and sample data |
| `config/` | Nginx configuration |

Dockerfile, Docker Compose, this README, and Apache's `.htaccess` remain at the root for deployment. Apache and Nginx redirect old root PHP and HTML URLs to the new folders, preserving query strings and POST methods. Apache must enable `mod_rewrite` and allow `.htaccess` overrides. You can open the project root to reach sign-in.

Legacy HTML module addresses redirect to the live PHP pages. Certifications are saved to the database. The PHP onboarding and HR record workflows save to the database; Overview reads their live completion and resolution summaries. Reports show saved compensation records and turnover estimates based on recorded hire/separation dates. Hiring creates a linked probationary employee in the same transaction and carries over compensation when an offer amount is present. Reactivating employees clears their separation date.

Database settings can be overridden with `EMS_DB_HOST`, `EMS_DB_PORT`, `EMS_DB_NAME`, `EMS_DB_USER`, and `EMS_DB_PASSWORD`. Local defaults are `127.0.0.1:3306`, `ems_db`, `root`, and an empty password. Docker imports the SQL file only when its database volume is first initialized; existing volumes are preserved.

Older XAMPP setups may use the database name `employee_management`. When the default local `ems_db` does not exist, the app uses that existing database. An explicit `EMS_DB_NAME` always takes precedence. The canonical app address is `http://localhost/EMPLOYEE-MANAGEMENT/` (a hyphen between the words).

## Automated checks

Realistic demo salaries use department and level bands based on Philippine market benchmarks. See [salary sources and monthly bands](docs/salary-benchmarks.md). Database monetary fields store annual base pay (monthly base multiplied by 12); employee/profile screens show monthly pay and its annual equivalent, while reports aggregate annual pay. The seeder and fresh SQL imports use the same benchmarks.

To preview updates to an existing demo database, run `php php/tools/normalize-salaries.php`. Add `--apply` to update the monetary fields; the tool saves exact rollback SQL in `sql/backups/` before making changes. It does not reset accounts or other employee records. Running it again does not multiply already normalized salaries. Custom departments and unsupported job levels retain their configured values.

```powershell
Get-ChildItem php -Recurse -Filter *.php | ForEach-Object { & C:\xampp\php\php.exe -l $_.FullName }
Get-ChildItem js -Filter *.js | ForEach-Object { node --check $_.FullName }
& C:\xampp\php\php.exe php/tools/check.php
```

The regression checks exercise hiring and roll back their database writes. They never run the seeder or reset application data.

Staff accounts use a personal dashboard, profile, reviews, and leave workspace. Page access is enforced on the server; the live API returns only the employee linked to the signed-in account, and unlinked accounts receive empty personal collections. Staff cannot access hiring, company onboarding, HR records, settings, or reports. Only completed reviews owned by the signed-in employee can be acknowledged, with a valid CSRF token. Personal tasks include review acknowledgments and assigned onboarding tasks. Employees can complete their own assigned tasks and acknowledge their own requested HR records from My dashboard.

My profile has no Request leave shortcut. Staff can submit email and phone changes for Admin approval there. Saved contact details remain unchanged while a request is pending or rejected. Admins review these requests in Employees; approval updates the employee profile and, when the email changes, the account's sign-in email. The username and password are preserved. Only one pending request is allowed per employee, and requests cannot overwrite contact details changed after submission. Decision history is retained.

Fresh imports include the contact request table. Existing databases can install it with `php php/tools/install-contact-requests.php`, which adds the table without changing saved contacts.

The regression checks also cover personal salary/attendance scoping, unlinked accounts, review ownership, draft review restrictions, and decimal score classifications.

When the application database is not installed, run `php php/tools/check.php --isolated`. This creates a uniquely named temporary database from the SQL dump, runs the checks, and removes that temporary database. The database account needs permission to create and drop databases.

## Onboarding and HR records

Existing databases can install these workflows with `php php/tools/install-workflows.php`. This adds three tables without resetting employee data. Fresh SQL imports already include them.

Admin and HR can create one onboarding plan per employee, choose a buddy, assign tasks across three phases, and save task completion. Cancel discards unsaved modal changes. Employees see only their own pending tasks and can mark them complete.

Admin and HR can file HR records, change resolution status, and request acknowledgment from an employee with an active Staff account. Only that employee can acknowledge receipt. Acknowledgment is separate from resolution and is not a handwritten or certified digital signature.

`php php/tools/check.php` also verifies workflow persistence, ownership checks, duplicate submissions, and invalid dates; all test writes are rolled back. `--isolated` checks a fresh temporary database and removes it afterward.

The seeder refuses `--flush` when saved onboarding, HR records, contact requests or certifications depend on the employees, preserving those records instead of leaving broken references.


## Functional workflows

For an existing database, run `php php/tools/install-features.php` once. This adds certification storage and corrects the current salary view without resetting employees or compensation. Fresh SQL imports include both changes. The local database has already been migrated.

- Employees: Admin and HR can add staff, edit profiles, save attendance and update annual compensation. Effective dates keep future salary changes out of current pay. Admin can create a linked Staff sign-in for an employee who has no account. Profile email edits also update the linked sign-in email.
- Hiring: enter an optional annual offer amount when extending an offer. Hiring carries this saved amount into the employee's initial compensation.
- Performance: Admin and Manager can create evaluations, edit saved drafts and finalize them. Completed and acknowledged reviews remain read-only. Admin, HR and Manager can add/edit certifications and training. Staff see their own training and credentials alongside their reviews.
- Leave: submitting reserves available days; approval deducts the balance once and records weekday attendance as On Leave. Rejection preserves the balance. Decision notes are shown in request history.
- Settings: Admin can edit existing departments, roles and annual salary bands, adjust dashboard limits and change account roles or active status. Account changes apply to existing sessions on the next request.
- My account: every role can change its password after verifying the current password.

PHP and database connections use Singapore time by default. Set `EMS_TIMEZONE` to another valid timezone identifier when needed.

The HTTP suite creates a uniquely named temporary database, exercises real sign-in and form submissions for all roles, and removes that database afterward. It needs Python 3, PHP and a database account allowed to create/drop temporary databases:

```powershell
python php/tools/check-http.py
```

For Chrome interaction checks, install the test dependency into the ignored local tools folder and run:

```powershell
npm.cmd install --prefix .test-tools --cache .test-tools/npm-cache --no-save --package-lock=false playwright
python php/tools/check-http.py --browser
```

Browser checks require installed Chrome. They verify dialogs, AJAX profile edits, compensation, evaluation finalization, certifications, training, CSV downloads, theme persistence and a narrow-screen dialog. Test screenshots and downloads stay in `.test-tools/screenshots/`.

## Reset activity while preserving profiles and pay

`php php/tools/reset-activity.php` previews the activity counts and preserved records. Adding `--apply` saves rollback SQL in `sql/backups/`, then clears attendance, evaluations, training, hiring, leave requests, onboarding, HR records and contact requests in one transaction. It sets used leave days to zero and verifies that employee profiles, accounts, compensation, salary bands, settings, certifications and leave entitlements remain unchanged. Headcount and payroll continue to show the preserved workforce.

`python php/tools/check-http.py --reset-activity` tests both the reset and exact restoration from the rollback backup in a temporary database.
