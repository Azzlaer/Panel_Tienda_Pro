/* Keep the active group open; collapse links without hiding group toggles. */
(() => {
  document.querySelectorAll('.enterprise-group-toggle').forEach(button => {
    const links = button.parentElement?.querySelector('.enterprise-nav-items');
    if (!links) return;
    const expanded = Boolean(links.querySelector('a.active'));
    links.hidden = !expanded;
    button.setAttribute('aria-expanded', String(expanded));
    button.addEventListener('click', () => {
      links.hidden = !links.hidden;
      button.setAttribute('aria-expanded', String(!links.hidden));
    });
  });
})();
