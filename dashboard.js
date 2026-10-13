const themeSelect = document.querySelector('[data-theme-select]');
const themeStorageKey = 'ems-theme';
const validThemes = new Set(['light', 'dark-sidebar']);
const applyTheme = (theme) => {
    const selectedTheme = validThemes.has(theme) ? theme : 'light';
    if (selectedTheme === 'light') document.documentElement.removeAttribute('data-theme');
    else document.documentElement.dataset.theme = selectedTheme;
    if (themeSelect) themeSelect.value = selectedTheme;
};

if (themeSelect) {
    let savedTheme = 'light';
    try {
        savedTheme = localStorage.getItem(themeStorageKey) || 'light';
    } catch (error) {
        console.warn('Unable to read the saved EMS theme preference.', error);
    }
    applyTheme(savedTheme);
    themeSelect.addEventListener('change', () => {
        applyTheme(themeSelect.value);
        try {
            localStorage.setItem(themeStorageKey, themeSelect.value);
        } catch (error) {
            console.warn('Unable to save the EMS theme preference.', error);
        }
    });
}

const dashboardMetrics = document.querySelector('[data-dashboard-metrics]');

if (dashboardMetrics) {
    const feedback = dashboardMetrics.querySelector('[data-dashboard-metrics-feedback]');
    const setMetric = (name, value) => {
        const element = dashboardMetrics.querySelector(`[data-metric="${name}"]`);
        if (element) element.textContent = value;
    };

    fetch('dashboard-data.php', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    })
        .then(async (response) => {
            let payload;
            try {
                payload = await response.json();
            } catch {
                throw new Error('Invalid dashboard response.');
            }

            if (!response.ok || !payload?.data) {
                throw new Error('Dashboard request failed.');
            }

            const collections = payload.data;
            if (
                !Array.isArray(collections.pipeline_counts)
                || !Array.isArray(collections.headcount_by_department)
                || !Array.isArray(collections.attendance_pct)
                || !Array.isArray(collections.current_base_salary)
                || !collections.display_limits
            ) {
                throw new Error('Dashboard response is incomplete.');
            }
            const attendanceMonthLimit = Number(collections.display_limits.attendance_month_limit) || 12;
            const departmentLimit = Number(collections.display_limits.department_chart_limit) || 10;
            const attendanceLimitLabel = document.querySelector('[data-attendance-limit-label]');
            if (attendanceLimitLabel) attendanceLimitLabel.textContent = `Monthly average · latest ${attendanceMonthLimit} months`;
            const departmentLimitLabel = document.querySelector('[data-department-limit-label]');
            if (departmentLimitLabel) departmentLimitLabel.textContent = `Showing up to ${departmentLimit} departments`;

            const counts = new Map(collections.pipeline_counts.map((row) => [row.status, Number(row.total) || 0]));
            const openStages = ['Applied', 'Screening', 'Interview', 'Offer'];
            const activePipelineTotal = openStages.reduce((total, stage) => total + (counts.get(stage) || 0), 0);
            const headcount = collections.headcount_by_department.reduce(
                (total, row) => total + (Number(row.headcount) || 0),
                0,
            );
            const payroll = collections.current_base_salary.reduce(
                (total, row) => total + (Number(row.base_salary) || 0),
                0,
            );
            const latestAttendanceMonth = collections.attendance_pct
                .map((row) => row.attendance_month)
                .filter((month) => typeof month === 'string')
                .sort()
                .at(-1);
            const monthlyAttendance = latestAttendanceMonth
                ? collections.attendance_pct.filter((row) => row.attendance_month === latestAttendanceMonth)
                : [];
            const attendanceValues = monthlyAttendance
                .filter((row) => row.attendance_pct !== null && row.attendance_pct !== '')
                .map((row) => Number(row.attendance_pct))
                .filter(Number.isFinite);
            const attendanceAverage = attendanceValues.length
                ? `${(attendanceValues.reduce((total, value) => total + value, 0) / attendanceValues.length).toFixed(1)}%`
                : '—';

            setMetric('headcount', String(headcount));
            setMetric('active-pipeline', String(activePipelineTotal));
            setMetric('attendance', attendanceAverage);
            setMetric('attendance-period', latestAttendanceMonth || 'No attendance recorded');
            setMetric('payroll', new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                maximumFractionDigits: 0,
            }).format(payroll));
            setMetric('department-total', `${headcount} employees`);
            setMetric('pipeline-total', `${activePipelineTotal} candidates in active stages`);

            const departmentBars = document.querySelector('[data-department-bars]');
            if (departmentBars) {
                departmentBars.replaceChildren();
                const visibleDepartments = [...collections.headcount_by_department]
                    .sort((left, right) => (Number(right.headcount) || 0) - (Number(left.headcount) || 0))
                    .slice(0, departmentLimit);
                const maxHeadcount = Math.max(
                    1,
                    ...visibleDepartments.map((row) => Number(row.headcount) || 0),
                );
                visibleDepartments.forEach((row) => {
                    const count = Number(row.headcount) || 0;
                    const line = document.createElement('div');
                    line.className = 'department-row';
                    const name = document.createElement('span');
                    name.textContent = row.department_name;
                    const track = document.createElement('div');
                    track.className = 'bar-track';
                    const bar = document.createElement('i');
                    bar.style.width = `${(count / maxHeadcount) * 100}%`;
                    track.append(bar);
                    const value = document.createElement('b');
                    value.textContent = String(count);
                    line.append(name, track, value);
                    departmentBars.append(line);
                });
            }

            const attendanceTrend = document.querySelector('[data-attendance-trend]');
            if (attendanceTrend) {
                attendanceTrend.replaceChildren();
                const monthlyValues = new Map();
                collections.attendance_pct.forEach((row) => {
                    if (row.attendance_pct === null || row.attendance_pct === '' || typeof row.attendance_month !== 'string') return;
                    const value = Number(row.attendance_pct);
                    if (!Number.isFinite(value)) return;
                    const values = monthlyValues.get(row.attendance_month) || [];
                    values.push(value);
                    monthlyValues.set(row.attendance_month, values);
                });
                const months = [...monthlyValues.keys()].sort().slice(-attendanceMonthLimit);
                if (!months.length) {
                    const empty = document.createElement('p');
                    empty.className = 'empty-state';
                    empty.textContent = 'No attendance has been recorded.';
                    attendanceTrend.append(empty);
                } else {
                    months.forEach((month) => {
                        const values = monthlyValues.get(month);
                        const average = values.reduce((total, value) => total + value, 0) / values.length;
                        const row = document.createElement('div');
                        row.className = 'department-row';
                        const label = document.createElement('span');
                        label.textContent = month;
                        const track = document.createElement('div');
                        track.className = 'bar-track';
                        const bar = document.createElement('i');
                        bar.style.width = `${Math.min(100, Math.max(0, average))}%`;
                        track.append(bar);
                        const value = document.createElement('b');
                        value.textContent = `${average.toFixed(1)}%`;
                        row.append(label, track, value);
                        attendanceTrend.append(row);
                    });
                }
            }

            const pipelineContainer = document.querySelector('[data-pipeline-stages]');
            if (pipelineContainer) {
                pipelineContainer.replaceChildren();
                const toneByStage = {
                    Applied: 'slate',
                    Screening: 'violet',
                    Interview: 'indigo',
                    Offer: 'mint',
                };
                openStages.forEach((stage) => {
                    const count = counts.get(stage) || 0;
                    const tone = toneByStage[stage];
                    const line = document.createElement('div');
                    line.className = 'pipeline-stage';
                    const label = document.createElement('div');
                    label.className = 'pipeline-label';
                    const pill = document.createElement('span');
                    pill.className = `pill ${tone}`;
                    pill.textContent = stage;
                    const value = document.createElement('span');
                    value.textContent = String(count);
                    label.append(pill, value);
                    const track = document.createElement('div');
                    track.className = 'pipeline-track';
                    const bar = document.createElement('i');
                    bar.className = tone;
                    bar.style.width = `${count ? Math.max(8, (count / Math.max(1, activePipelineTotal)) * 100) : 0}%`;
                    track.append(bar);
                    line.append(label, track);
                    pipelineContainer.append(line);
                });
            }
        })
        .catch(() => {
            if (feedback) feedback.textContent = 'Dashboard metrics could not be loaded.';
            setMetric('department-total', 'Workforce data unavailable');
            setMetric('pipeline-total', 'Pipeline data unavailable');
            document.querySelectorAll('[data-department-bars], [data-attendance-trend], [data-pipeline-stages]').forEach((container) => {
                container.replaceChildren();
                const empty = document.createElement('p');
                empty.className = 'empty-state';
                empty.textContent = 'Dashboard data could not be loaded.';
                container.append(empty);
            });
            console.error('Unable to load EMS dashboard metrics.');
        });
}

const dialog = document.querySelector('#dashboard-form-dialog');

if (dialog) {
    document.querySelectorAll('[data-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => {
            dialog.showModal();
            dialog.querySelector('[role="tab"], input, select, textarea')?.focus();
        });
    });

    dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('close', () => {
        dialog.querySelector('form')?.reset();
        const feedback = dialog.querySelector('.dialog-feedback');
        if (feedback) feedback.textContent = '';
    });

    dialog.querySelector('[data-preview-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        dialog.querySelector('.dialog-feedback').textContent = 'Preview only: this form is not connected to saved data yet.';
    });
}

const taskPanel = document.querySelector('[data-onboarding-task-panel]');

if (taskPanel) {
    const taskGroups = JSON.parse(taskPanel.dataset.taskGroups);
    const tabs = [...taskPanel.querySelectorAll('[role="tab"]')];
    const taskRows = taskPanel.querySelector('[data-task-rows]');
    const tabPanel = taskPanel.querySelector('[role="tabpanel"]');

    const todayString = () => {
        const today = new Date();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${month}/${day}/${today.getFullYear()}`;
    };

    const renderTasks = (phase) => {
        taskRows.replaceChildren();

        taskGroups[phase].forEach((task) => {
            const row = document.createElement('div');
            row.className = 'onboarding-task-row';
            row.setAttribute('role', 'listitem');

            const checkbox = document.createElement('input');
            checkbox.className = 'onboarding-task-checkbox';
            checkbox.type = 'checkbox';
            checkbox.checked = task.assigned;
            checkbox.setAttribute('aria-label', task.name);

            const copy = document.createElement('span');
            copy.className = 'onboarding-task-copy';

            const title = document.createElement('span');
            title.className = 'task-title';
            title.textContent = task.name;

            const metadata = document.createElement('span');
            metadata.className = 'onboarding-task-metadata';

            const assignee = document.createElement('span');
            assignee.className = 'task-assignee';

            const date = document.createElement('time');
            date.className = 'task-date';

            const updateStatus = () => {
                assignee.textContent = task.assigned ? 'Assigned' : 'Unassigned';
                date.textContent = task.date;
            };

            updateStatus();
            metadata.append(assignee, date);
            copy.append(title, metadata);
            row.append(checkbox, copy);
            taskRows.append(row);

            checkbox.addEventListener('change', () => {
                task.assigned = checkbox.checked;
                task.date = todayString();
                updateStatus();
            });
        });
    };

    const activateTab = (activeTab) => {
        tabs.forEach((tab) => {
            const isActive = tab === activeTab;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
        });

        tabPanel.setAttribute('aria-labelledby', activeTab.id);
        renderTasks(activeTab.dataset.phase);
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab));
        tab.addEventListener('keydown', (event) => {
            let nextIndex;
            if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
            else if (event.key === 'Home') nextIndex = 0;
            else if (event.key === 'End') nextIndex = tabs.length - 1;
            else return;

            event.preventDefault();
            tabs[nextIndex].focus();
            activateTab(tabs[nextIndex]);
        });
    });

    activateTab(tabs[0]);
}

const applicantBrowser = document.querySelector('[data-applicant-browser]');

const renderPageNumbers = (container, page, pageCount, onNavigate) => {
    if (!container) return;
    container.replaceChildren();
    const visiblePages = new Set([1, pageCount]);
    for (let number = Math.max(1, page - 2); number <= Math.min(pageCount, page + 2); number += 1) {
        visiblePages.add(number);
    }

    let previousPage = 0;
    [...visiblePages].sort((left, right) => left - right).forEach((number) => {
        if (number - previousPage > 1) {
            const gap = document.createElement('span');
            gap.textContent = '…';
            gap.setAttribute('aria-hidden', 'true');
            container.append(gap);
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = String(number);
        button.setAttribute('aria-label', `Page ${number}`);
        if (number === page) button.setAttribute('aria-current', 'page');
        button.addEventListener('click', () => onNavigate(number));
        container.append(button);
        previousPage = number;
    });
};

if (applicantBrowser) {
    const tabs = [...applicantBrowser.querySelectorAll('[data-applicant-tab]')];
    const panels = [...applicantBrowser.querySelectorAll('[data-applicant-panel]')];
    const searchInput = applicantBrowser.querySelector('[data-applicant-search]');
    const departmentSelect = applicantBrowser.querySelector('[data-applicant-department]');
    const pageSizeSelect = applicantBrowser.querySelector('[data-page-size]');
    let activeView = 'active';

    const renderApplicants = (panel) => {
        const rows = [...panel.querySelectorAll('[data-applicant-row]')];
        const emptyState = panel.querySelector('[data-applicant-empty]');
        const search = searchInput.value.trim().toLocaleLowerCase();
        const department = departmentSelect.value;
        const matchingRows = rows.filter((row) => {
            const matchesSearch = row.dataset.search.toLocaleLowerCase().includes(search);
            const matchesDepartment = department === '' || row.dataset.department === department;
            return matchesSearch && matchesDepartment;
        });
        const pageSize = Number(pageSizeSelect.value);
        const pageCount = Math.max(1, Math.ceil(matchingRows.length / pageSize));
        const page = Math.min(Number(panel.dataset.page || 1), pageCount);
        const start = (page - 1) * pageSize;

        panel.dataset.page = String(page);
        rows.forEach((row) => { row.hidden = true; });
        matchingRows.slice(start, start + pageSize).forEach((row) => { row.hidden = false; });
        emptyState.hidden = matchingRows.length > 0;
        panel.querySelector('[data-page-summary]').textContent = matchingRows.length
            ? `Showing ${start + 1}–${Math.min(start + pageSize, matchingRows.length)} of ${matchingRows.length}`
            : 'Showing 0 applicants';
        panel.querySelector('[data-page-indicator]').textContent = `Page ${page} of ${pageCount}`;
        panel.querySelector('[data-page-prev]').disabled = page <= 1;
        panel.querySelector('[data-page-next]').disabled = page >= pageCount;
        renderPageNumbers(panel.querySelector('[data-page-numbers]'), page, pageCount, (targetPage) => {
            panel.dataset.page = String(targetPage);
            renderApplicants(panel);
        });
    };

    const renderActivePanel = () => {
        renderApplicants(applicantBrowser.querySelector(`[data-applicant-panel="${activeView}"]`));
    };

    const activateTab = (activeTab) => {
        activeView = activeTab.dataset.applicantTab;
        tabs.forEach((tab) => {
            const isActive = tab === activeTab;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
        });
        panels.forEach((panel) => { panel.hidden = panel.dataset.applicantPanel !== activeView; });
        renderActivePanel();
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab));
        tab.addEventListener('keydown', (event) => {
            let nextIndex;
            if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
            else if (event.key === 'Home') nextIndex = 0;
            else if (event.key === 'End') nextIndex = tabs.length - 1;
            else return;

            event.preventDefault();
            tabs[nextIndex].focus();
            activateTab(tabs[nextIndex]);
        });
    });

    [searchInput, departmentSelect, pageSizeSelect].forEach((control) => {
        control.addEventListener('input', () => {
            applicantBrowser.querySelector(`[data-applicant-panel="${activeView}"]`).dataset.page = '1';
            renderActivePanel();
        });
        control.addEventListener('change', () => {
            applicantBrowser.querySelector(`[data-applicant-panel="${activeView}"]`).dataset.page = '1';
            renderActivePanel();
        });
    });

    applicantBrowser.querySelectorAll('[data-page-prev], [data-page-next]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = applicantBrowser.querySelector(`[data-applicant-panel="${activeView}"]`);
            panel.dataset.page = String(Number(panel.dataset.page || 1) + (button.hasAttribute('data-page-next') ? 1 : -1));
            renderApplicants(panel);
        });
    });

    renderActivePanel();
}

document.querySelectorAll('[data-record-pager]').forEach((pager) => {
    const rows = [...pager.querySelectorAll('[data-paginated-row]')];
    const emptyState = pager.querySelector('[data-pager-empty]');
    const pageSize = Math.max(1, Number(pager.dataset.pageSize) || 10);
    const pageCount = Math.max(1, Math.ceil(rows.length / pageSize));
    let page = 1;

    const render = () => {
        const start = (page - 1) * pageSize;
        rows.forEach((row) => { row.hidden = true; });
        rows.slice(start, start + pageSize).forEach((row) => { row.hidden = false; });
        if (emptyState) emptyState.hidden = rows.length > 0;
        pager.querySelector('[data-page-summary]').textContent = rows.length
            ? `Showing ${start + 1}–${Math.min(start + pageSize, rows.length)} of ${rows.length}`
            : 'Showing 0 records';
        pager.querySelector('[data-page-indicator]').textContent = `Page ${page} of ${pageCount}`;
        pager.querySelector('[data-page-prev]').disabled = page <= 1;
        pager.querySelector('[data-page-next]').disabled = page >= pageCount;
        renderPageNumbers(pager.querySelector('[data-page-numbers]'), page, pageCount, (targetPage) => {
            page = targetPage;
            render();
        });
    };

    pager.querySelector('[data-page-prev]').addEventListener('click', () => {
        page = Math.max(1, page - 1);
        render();
    });
    pager.querySelector('[data-page-next]').addEventListener('click', () => {
        page = Math.min(pageCount, page + 1);
        render();
    });
    render();
});

document.querySelectorAll('[data-export-table]').forEach((button) => {
    button.addEventListener('click', () => {
        const table = button.closest('.report-card, .audit-section')?.querySelector('[data-report-table]');
        if (!table) return;

        const csvValue = (value) => {
            const safeValue = /^[=+\-@]/.test(value) ? `'${value}` : value;
            return `"${safeValue.replaceAll('"', '""')}"`;
        };
        const rows = [...table.querySelectorAll('tr')].map((row) =>
            [...row.querySelectorAll('th, td')].map((cell) => csvValue(cell.innerText.trim())).join(',')
        );
        const blob = new Blob([`\uFEFF${rows.join('\r\n')}`], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = button.dataset.exportTable;
        document.body.append(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
});

// Employees: Edit profile modal closes on backdrop click or Escape (typed values stay until the page reloads)
(() => {
    const openPopups = () => [...document.querySelectorAll('details.employee-edit[open]')];

    document.addEventListener('click', (event) => {
        openPopups().forEach((popup) => {
            // target === popup means the dark backdrop (its ::before) was clicked
            if (!popup.contains(event.target) || event.target === popup) popup.open = false;
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        openPopups().forEach((popup) => {
            popup.open = false;
            popup.querySelector('summary')?.focus();
        });
    });

    // Lets the CSS lift the layout containment that would otherwise trap the fixed modal
    document.addEventListener('toggle', (event) => {
        if (!event.target.matches?.('details.employee-edit')) return;
        document.body.classList.toggle('edit-modal-open', openPopups().length > 0);
    }, true);
})();

// Employee Profile Update: Corner modal toast notification & in-place update
(() => {
    const escapeHtml = (str) => {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    };

    const dismissToast = (toast) => {
        if (!toast || toast.dataset.dismissing === 'true') return;
        toast.dataset.dismissing = 'true';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(16px) scale(0.95)';
        window.setTimeout(() => toast.remove(), 220);
    };

    const setupToastTimer = (toast) => {
        let timer = window.setTimeout(() => dismissToast(toast), 6500);
        toast.addEventListener('mouseenter', () => {
            window.clearTimeout(timer);
            const bar = toast.querySelector('.corner-toast-progress');
            if (bar) bar.style.animationPlayState = 'paused';
        });
        toast.addEventListener('mouseleave', () => {
            const bar = toast.querySelector('.corner-toast-progress');
            if (bar) bar.style.animationPlayState = 'running';
            timer = window.setTimeout(() => dismissToast(toast), 2500);
        });
    };

    const renderEmployeeCornerToast = (emp) => {
        let container = document.querySelector('#corner-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'corner-toast-container';
            container.className = 'corner-toast-container';
            document.body.append(container);
        }

        const existingToast = container.querySelector('.corner-modal-toast');
        if (existingToast) existingToast.remove();

        let changesHtml = '';
        if (emp.changed_fields && Object.keys(emp.changed_fields).length > 0) {
            const rows = Object.values(emp.changed_fields).map((c) => `
                <li class="corner-toast-change-row">
                    <span class="change-field-name">${escapeHtml(c.label)}</span>
                    <span class="change-transition">
                        <span class="change-old">${escapeHtml(c.old)}</span>
                        <span class="change-arrow" aria-hidden="true">&rarr;</span>
                        <span class="change-new">${escapeHtml(c.new)}</span>
                    </span>
                </li>
            `).join('');
            changesHtml = `
                <div class="corner-toast-changes-label">Changed Information:</div>
                <ul class="corner-toast-changes-list">${rows}</ul>
            `;
        } else {
            changesHtml = '<p class="corner-toast-no-change">Saved with no field changes.</p>';
        }

        const toast = document.createElement('aside');
        toast.className = 'corner-modal-toast';
        toast.id = 'employee-corner-modal';
        toast.setAttribute('role', 'status');

        toast.innerHTML = `
            <div class="corner-toast-header">
                <div class="corner-toast-title-wrap">
                    <span class="corner-toast-icon" aria-hidden="true">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </span>
                    <div>
                        <h4 class="corner-toast-title">Update Successful</h4>
                        <p class="corner-toast-emp">${escapeHtml(emp.full_name)} <small>(${escapeHtml(emp.employee_number)})</small></p>
                    </div>
                </div>
                <button type="button" class="corner-toast-close" data-dismiss-corner-toast aria-label="Close notification">&times;</button>
            </div>
            <div class="corner-toast-body">
                ${changesHtml}
            </div>
            <div class="corner-toast-progress-bar"><div class="corner-toast-progress"></div></div>
        `;

        container.append(toast);
        setupToastTimer(toast);
    };

    // Close button click listener
    document.addEventListener('click', (event) => {
        const closeBtn = event.target.closest('[data-dismiss-corner-toast]');
        if (!closeBtn) return;
        const toast = closeBtn.closest('.corner-modal-toast');
        dismissToast(toast);
    });

    // Auto-dismiss any server-rendered toast
    const serverToast = document.querySelector('#employee-corner-modal');
    if (serverToast) {
        setupToastTimer(serverToast);
    }

    // Form submit listener for inline profile edits
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('.employee-edit-form');
        if (!form) return;

        event.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving profile...';
        }

        try {
            const formData = new FormData(form);
            const response = await fetch('employees.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            let result;
            try {
                result = await response.json();
            } catch {
                throw new Error('Server returned non-JSON response');
            }

            if (!response.ok || result.status !== 'success' || !result.updated_employee) {
                alert(result.message || 'Could not update employee profile.');
                return;
            }

            const emp = result.updated_employee;

            // Close the edit details popover
            const details = form.closest('details.employee-edit');
            if (details) details.open = false;

            // Update corresponding table row in-place
            const row = form.closest('tr');
            if (row) {
                // 1. Employee cell: small email under name
                const emailSmall = row.querySelector('.employee-cell small');
                if (emailSmall) emailSmall.textContent = emp.email || '';

                // 2. Role & Department cell (3rd column)
                const roleCell = row.children[2];
                if (roleCell) {
                    const textNode = [...roleCell.childNodes].find((node) => node.nodeType === Node.TEXT_NODE);
                    if (textNode) {
                        textNode.textContent = emp.job_title_name || 'Unassigned';
                    }
                    const deptSmall = roleCell.querySelector('small');
                    if (deptSmall) {
                        deptSmall.textContent = emp.department_name || 'Unassigned';
                    }
                }

                // 3. Status cell (4th column)
                const statusCell = row.children[3];
                if (statusCell) {
                    const pill = statusCell.querySelector('.pill');
                    if (pill) {
                        pill.textContent = emp.employment_status;
                        pill.className = 'pill ' + (emp.employment_status === 'Probation'
                            ? 'amber'
                            : (['Resigned', 'Terminated'].includes(emp.employment_status) ? 'rose' : 'mint'));
                    }
                }

                // 4. Update the form values inside this row so future edits start with updated data
                const emailInput = form.querySelector('input[name="email"]');
                if (emailInput) emailInput.value = emp.email || '';
                const phoneInput = form.querySelector('input[name="phone_number"]');
                if (phoneInput) phoneInput.value = emp.phone_number || '';
                const addressInput = form.querySelector('input[name="address"]');
                if (addressInput) addressInput.value = emp.address || '';
                const emergencyInput = form.querySelector('input[name="emergency_contact"]');
                if (emergencyInput) emergencyInput.value = emp.emergency_contact || '';
                const deptSelect = form.querySelector('select[name="department_id"]');
                if (deptSelect && emp.department_id) deptSelect.value = String(emp.department_id);
                const jobSelect = form.querySelector('select[name="job_title_id"]');
                if (jobSelect && emp.job_title_id) jobSelect.value = String(emp.job_title_id);
                const statusSelect = form.querySelector('select[name="employment_status"]');
                if (statusSelect) statusSelect.value = emp.employment_status;
            }

            // Render corner modal toast showing update successful and changed information
            renderEmployeeCornerToast(emp);

        } catch (error) {
            console.warn('AJAX profile update failed, falling back to standard submit:', error);
            form.submit();
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    });
})();