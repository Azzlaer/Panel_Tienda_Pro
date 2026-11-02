(function () {
  'use strict';

  const body = document.body;
  const mobileToggle = document.getElementById('adminMenuToggle');
  const overlay = document.getElementById('adminSidebarOverlay');
  const collapse = document.getElementById('adminSidebarCollapse');

  function closeMobileMenu() {
    body.classList.remove('sidebar-open');
  }

  mobileToggle?.addEventListener('click', function () {
    body.classList.toggle('sidebar-open');
  });

  overlay?.addEventListener('click', closeMobileMenu);

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeMobileMenu();
  });

  if (localStorage.getItem('adminSidebarCompact') === '1' && window.innerWidth > 760) {
    body.classList.add('admin-sidebar-compact');
  }

  collapse?.addEventListener('click', function () {
    body.classList.toggle('admin-sidebar-compact');
    localStorage.setItem(
      'adminSidebarCompact',
      body.classList.contains('admin-sidebar-compact') ? '1' : '0'
    );
  });

  document.addEventListener('click', function (event) {
    if (event.target.matches('[data-confirm]') && !confirm(event.target.dataset.confirm || '¿Confirmar esta acción?')) {
      event.preventDefault();
    }
  });

  document.querySelectorAll('[data-filter-table]').forEach(function (input) {
    input.addEventListener('input', function () {
      const q = input.value.toLowerCase();
      document.querySelectorAll(input.dataset.filterTable + ' tbody tr').forEach(function (row) {
        row.hidden = !row.textContent.toLowerCase().includes(q);
      });
    });
  });
})();
