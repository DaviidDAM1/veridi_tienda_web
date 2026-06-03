import axios from 'axios';

// Allow an explicit empty string in VITE_BACKEND_BASE_URL to use relative paths
const rawEnvBackend = import.meta.env.VITE_BACKEND_BASE_URL;
const normalizedEnvBackend = (rawEnvBackend === undefined
  ? (import.meta.env.DEV ? 'http://localhost/veridi_tienda_web' : '')
  : String(rawEnvBackend)
).replace(/\/$/, '');

const productionBackendUrl = String(import.meta.env.VITE_BACKEND_BASE_URL_PROD || 'https://davidvaldes.masterendaw.es').replace(/\/$/, '');

// In production, always talk to the same backend origin so the session cookie is not split
// between the Vercel proxy and Hostinger direct requests.
const BACKEND_BASE_URL = import.meta.env.DEV ? normalizedEnvBackend : productionBackendUrl;

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
  timeout: 20000,
  headers: {
    'X-Requested-With': 'XMLHttpRequest'
  }
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const config = error?.config || {};
    const method = String(config.method || '').toLowerCase();
    const status = error?.response?.status;
    const isTimeout = String(error?.code || '') === 'ECONNABORTED';
    const isNetworkError = !error?.response;
    const isRetryableStatus = Number(status) >= 500;
    const shouldRetry = method === 'get' && (isTimeout || isNetworkError || isRetryableStatus);

    if (!shouldRetry) {
      return Promise.reject(error);
    }

    const retryCount = Number(config.__retryCount || 0);
    if (retryCount >= 2) {
      return Promise.reject(error);
    }

    config.__retryCount = retryCount + 1;
    await new Promise((resolve) => setTimeout(resolve, 450));
    return api(config);
  }
);

export default api;
