// Light / dark theme toggle, persisted in localStorage.
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('theme-toggle-btn');
  if (!btn) return;

  const root = document.documentElement;

  const apply = (theme) => {
    if (theme === 'dark') {
      root.setAttribute('data-theme', 'dark');
      btn.textContent = '☀️';
    } else {
      root.removeAttribute('data-theme');
      btn.textContent = '🌙';
    }
  };

  apply(localStorage.getItem('mbn-theme') || 'light');

  btn.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    localStorage.setItem('mbn-theme', next);
    apply(next);
  });
});
