import { useEffect, useState } from 'react';

export default function ThemeToggle() {
  const [theme, setTheme] = useState(() => {
    try {
      return localStorage.getItem('v-theme') || 'dark';
    } catch (e) {
      return 'dark';
    }
  });

  useEffect(() => {
    try {
      document.documentElement.setAttribute('data-theme', theme === 'light' ? 'light' : 'dark');
      localStorage.setItem('v-theme', theme);
    } catch (e) {}
  }, [theme]);

  const toggle = () => setTheme((t) => (t === 'light' ? 'dark' : 'light'));

  return (
    <button
      className="theme-toggle"
      onClick={toggle}
      aria-label={`Cambiar tema a ${theme === 'light' ? 'oscuro' : 'claro'}`}
      data-theme={theme}
      title="Cambiar tema"
    >
      <div className="thumb" />
    </button>
  );
}
