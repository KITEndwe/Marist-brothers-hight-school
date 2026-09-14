// Role tab switcher on the login page
document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.role-tabs button');
  const roleInput = document.getElementById('role-input');
  if (!tabs.length) return;

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      if (roleInput) roleInput.value = tab.dataset.role;
    });
  });
});
