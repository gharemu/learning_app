const header = document.querySelector('.ag-header');
const panelButtons = [...document.querySelectorAll('[data-panel]')];
const panels = {
  menu: document.querySelector('#ag-mobile-menu'),
  search: document.querySelector('#ag-search'),
  notifications: document.querySelector('#ag-notifications'),
  courses: document.querySelector('#ag-courses'),
  gurukul: document.querySelector('#ag-gurukul'),
};
function closePanels() {
  for (const [key, panel] of Object.entries(panels)) {
    if (panel) { panel.hidden = true; if (key === 'menu') panel.style.display = 'none'; }
    panelButtons.filter((button) => button.dataset.panel === key).forEach((button) => {
      button.setAttribute('aria-expanded', 'false');
    });
  }
}
for (const button of panelButtons) {
  button.addEventListener('click', () => {
    const key = button.dataset.panel;
    const panel = panels[key];
    if (!panel) return;
    const willOpen = panel.hidden;
    closePanels();
    panel.hidden = !willOpen;
    if (key === 'menu') panel.style.display = willOpen ? '' : 'none';
    button.setAttribute('aria-expanded', String(willOpen));
    if (key === 'search' && willOpen) panel.querySelector('input')?.focus();
  });
}
document.addEventListener('pointerdown', (event) => {
  if (header && !header.contains(event.target)) closePanels();
});
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') closePanels();
});
for (const button of document.querySelectorAll('[data-shelf]')) {
  button.addEventListener('click', () => {
    const shelf = button.parentElement?.querySelector('.ag-course-shelf');
    shelf?.scrollBy({ left: Number(button.dataset.shelf) * shelf.clientWidth * 0.8, behavior: 'smooth' });
  });
}
const portalButton = document.querySelector('[data-portal-menu]');
const sidebar = document.querySelector('.rp-sidebar');
const shade = document.querySelector('.rp-drawer-shade');
portalButton?.addEventListener('click', () => {
  const open = !sidebar?.classList.contains('is-open');
  sidebar?.classList.toggle('is-open', open);
  portalButton.setAttribute('aria-expanded', String(open));
  if (shade) { shade.hidden = !open; shade.style.display = open ? '' : 'none'; }
});
shade?.addEventListener('click', () => {
  sidebar?.classList.remove('is-open');
  shade.hidden = true;
  shade.style.display = 'none';
  portalButton?.setAttribute('aria-expanded', 'false');
});
const noticeButton = document.querySelector('[data-notices]');
const notices = document.querySelector('.rp-notification-panel');
noticeButton?.addEventListener('click', () => { if (notices) notices.hidden = !notices.hidden; });
closePanels();

const countrySelect = document.querySelector('select[name="country"]');
const stateSelect = document.querySelector('#state-select');
const stateText = document.querySelector('#state-text');
function syncStateField() {
  if (!countrySelect || !stateSelect || !stateText) return;
  const india = countrySelect.value === 'India';
  stateSelect.hidden = !india; stateSelect.disabled = !india; stateSelect.required = india;
  stateText.hidden = india; stateText.disabled = india; stateText.required = !india;
}
countrySelect?.addEventListener('change', syncStateField);
syncStateField();
