import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/veridi_tienda_web/frontend/dist/' : '/',
  plugins: [react()],
  server: {
    host: true,
    port: 5173,
    proxy: {
      // Proxy API calls starting with /php to the Apache backend
      '/php': {
        target: 'http://localhost',
        changeOrigin: true,
        rewrite: (path) => `/veridi_tienda_web${path}`
      },
      // Proxy images requests to backend (optional)
      '/img': {
        target: 'http://localhost',
        changeOrigin: true,
        rewrite: (path) => `/veridi_tienda_web${path}`
      }
    }
  }
}));

