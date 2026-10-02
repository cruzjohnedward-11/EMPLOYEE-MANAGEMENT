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

            const name = document.createElement('span');
            name.className = 'onboarding-task-name';
            name.textContent = task.name;

            const status = document.createElement('span');
            status.className = 'onboarding-task-status';

            const updateStatus = () => {
                status.textContent = `${task.assigned ? 'Assigned' : 'Unassigned'} ${task.date}`;
            };

            updateStatus();
            copy.append(name, status);
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
