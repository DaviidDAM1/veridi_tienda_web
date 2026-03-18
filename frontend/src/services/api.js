import axios from 'axios';

// Allow an explicit empty string in VITE_BACKEND_BASE_URL to use relative paths
const rawEnvBackend = import.meta.env.VITE_BACKEND_BASE_URL;
const BACKEND_BASE_URL = (rawEnvBackend === undefined
  ? 'http://localhost/veridi_tienda_web'
  : String(rawEnvBackend)
).replace(/\/$/, '');

export function buildBackendAssetUrl(path) {
  const value = String(path || '').trim();
  if (!value) return '';
  if (value.startsWith('http://') || value.startsWith('https://')) return value;
  return `${BACKEND_BASE_URL}/${value.replace(/^\//, '')}`;
}

export { BACKEND_BASE_URL };

const api = axios.create({
  baseURL: BACKEND_BASE_URL,
  withCredentials: true,
  timeout: 10000,
  headers: {
    'X-Requested-With': 'XMLHttpRequest'
  }
});

export default api;
