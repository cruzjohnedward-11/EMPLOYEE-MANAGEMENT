const navLinks = document.querySelectorAll('.nav-link');
const views = document.querySelectorAll('.view');
const headerTitle = document.querySelector('#header-title');
const toast = document.querySelector('.toast');
let toastTimer;

function showView(viewName) {
    const target = document.querySelector(`#view-${viewName}`);
    if (!target) return;

    views.forEach((view) => view.classList.toggle('active', view === target));
    navLinks.forEach((link) => link.classList.toggle('active', link.dataset.view === viewName));
    headerTitle.textContent = target.dataset.title;
    document.title = `${target.dataset.title} - EMS`;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showToast(message) {
    toast.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('show'), 2200);
}

navLinks.forEach((link) => link.addEventListener('click', () => showView(link.dataset.view)));
document.querySelectorAll('[data-view-target]').forEach((button) => {
    button.addEventListener('click', () => showView(button.dataset.viewTarget));
});
document.querySelectorAll('[data-toast]').forEach((button) => {
    button.addEventListener('click', () => showToast(button.dataset.toast));
});

document.querySelectorAll('.candidate .small-button').forEach((button) => {
    button.addEventListener('click', () => showToast(`${button.textContent} action selected`));
});
