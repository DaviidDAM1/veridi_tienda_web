export function openAuthPanel(tab = 'login') {
  const safeTab = tab === 'register' ? 'register' : 'login';
  window.dispatchEvent(new CustomEvent('veridi:open-auth', { detail: { tab: safeTab } }));
}
