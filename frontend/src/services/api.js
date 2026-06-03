import axios from 'axios';

// Allow an explicit empty string in VITE_BACKEND_BASE_URL to use relative paths
const rawEnvBackend = import.meta.env.VITE_BACKEND_BASE_URL;
const normalizedEnvBackend = (rawEnvBackend === undefined
  ? (import.meta.env.DEV ? 'http://localhost/veridi_tienda_web' : '')
  : String(rawEnvBackend)
).replace(/\/$/, '');

// In production, keep API calls same-origin so session cookies are always consistent.
const forceSameOriginApi = !import.meta.env.DEV && String(import.meta.env.VITE_FORCE_SAME_ORIGIN_API || 'true') !== 'false';
const BACKEND_BASE_URL = forceSameOriginApi ? '' : normalizedEnvBackend;
const BACKEND_FALLBACK_URL = String(import.meta.env.VITE_BACKEND_FALLBACK_URL || 'https://davidvaldes.masterendaw.es').replace(/\/$/, '');

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
    const url = String(config.url || '');
    const isTimeout = String(error?.code || '') === 'ECONNABORTED';
    const isNetworkError = !error?.response;
    const isRetryableStatus = Number(status) >= 500;
    const isProxyLikelyFailure = Number(status) === 404 || Number(status) === 429;
    const shouldRetry = method === 'get' && (isTimeout || isNetworkError || isRetryableStatus);

    const isPhpApiRequest = /^\/?php\//i.test(url);
    const isAbsolute = /^https?:\/\//i.test(url);
    const alreadyUsingFallback = String(config.baseURL || '').startsWith(BACKEND_FALLBACK_URL) || url.startsWith(BACKEND_FALLBACK_URL);

    if (
      method === 'get'
      && isPhpApiRequest
      && !isAbsolute
      && !alreadyUsingFallback
      && !config.__directFallbackTried
      && (isNetworkError || isRetryableStatus || isProxyLikelyFailure)
    ) {
      config.__directFallbackTried = true;
      config.baseURL = BACKEND_FALLBACK_URL;
      config.url = url.startsWith('/') ? url : `/${url}`;
      return api(config);
    }

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
