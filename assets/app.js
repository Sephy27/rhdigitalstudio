import './bootstrap.js';
import './styles/app.css';

// Back-to-top: safe + perf-friendly
function initBackToTop() {
  const btn = document.querySelector('.back-to-top');

  if (!btn) return;

  const toggleButton = () => {
    btn.classList.toggle('show', window.scrollY > 300);
  };

  toggleButton();

  // évite de remettre plusieurs listeners
  if (!btn.dataset.initialized) {
    window.addEventListener(
      'scroll',
      () => {
        if (btn.__raf__) return;

        btn.__raf__ = requestAnimationFrame(() => {
          toggleButton();
          btn.__raf__ = null;
        });
      },
      { passive: true }
    );

    btn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    btn.dataset.initialized = 'true';
  }
}

// Chargement classique
document.addEventListener('DOMContentLoaded', initBackToTop);

// Navigation Turbo Symfony
document.addEventListener('turbo:load', initBackToTop);





function bindZoom() {
  const dlg = document.getElementById('zoomDlg');
  const img = document.getElementById('zoomImg');
  if (!dlg || !img) return;

  // Ouvrir au clic sur tout élément avec data-zoom-src
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-zoom-src]');
    if (trigger) {
      const src = trigger.getAttribute('data-zoom-src');
      if (!src) return;
      img.src = src;
      if (typeof dlg.showModal === 'function') dlg.showModal();
      else dlg.setAttribute('open', '');
      return;
    }
    // Bouton Fermer
    if (e.target.closest('[data-zoom-close]')) {
      dlg.close();
    }
  });

  // Fermer si clic sur le backdrop
  dlg.addEventListener('click', (e) => {
    if (e.target === dlg) dlg.close();
  });

  // Fermer avec Échap
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && dlg.open) dlg.close();
  });
}

// --- Ces lignes DOIVENT être hors de la fonction ---
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bindZoom);
} else {
  bindZoom();
}
// Si tu utilises Symfony UX Turbo :
document.addEventListener('turbo:load', bindZoom);
