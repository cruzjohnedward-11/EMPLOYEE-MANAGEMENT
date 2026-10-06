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

    applicantBrowser.addEventListener('click', (event) => {
        const action = event.target.closest('[data-preview-action]');
        if (!action) return;
        const candidate = action.closest('.candidate-card, tr')?.querySelector('strong')?.textContent;
        applicantBrowser.querySelector('[data-applicant-feedback]').textContent =
            `${action.dataset.previewAction}${candidate ? ` for ${candidate}` : ''} is a UI preview only; applicant updates require backend integration.`;
        action.closest('details')?.removeAttribute('open');
    });

    applicantBrowser.addEventListener('change', (event) => {
        const actionSelect = event.target.closest('[data-preview-action-select]');
        if (!actionSelect || actionSelect.value === '') return;
        const candidate = actionSelect.closest('tr')?.querySelector('strong')?.textContent;
        applicantBrowser.querySelector('[data-applicant-feedback]').textContent =
            `${actionSelect.value}${candidate ? ` for ${candidate}` : ''} is a UI preview only; applicant updates require backend integration.`;
        actionSelect.value = '';
    });

    renderActivePanel();
}

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
