// public/js/app.js
console.log('[zoom] app.js chargé');

function bindZoom() {
  const dlg = document.getElementById('zoomDlg');
  const img = document.getElementById('zoomImg');
  if (!dlg || !img) return;

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-zoom-src]');
    if (trigger) {
      const src = trigger.getAttribute('data-zoom-src');
      if (!src) return;
      img.src = src;
      try { dlg.showModal(); } catch { dlg.setAttribute('open', ''); }
      return;
    }
    if (e.target.closest('[data-zoom-close]')) dlg.close();
  });

  dlg.addEventListener('click', (e) => {
    if (e.target === dlg) dlg.close();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && dlg.open) dlg.close();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bindZoom);
} else {
  bindZoom();
}
// Si tu utilises Symfony UX Turbo, il relancera le binding après navigation :
document.addEventListener('turbo:load', bindZoom);
