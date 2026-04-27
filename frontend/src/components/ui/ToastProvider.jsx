import React, { createContext, useCallback, useContext, useState } from 'react';

const ToastContext = createContext(null);
export const useToast = () => useContext(ToastContext);

export default function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const showToast = useCallback((message, type = 'info', timeout = 3500) => {
    const id = Date.now() + Math.random();
    setToasts((t) => [...t, { id, message, type }]);
    setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), timeout);
  }, []);

  const removeToast = useCallback((id) => setToasts((t) => t.filter((x) => x.id !== id)), []);

  return (
    <ToastContext.Provider value={{ showToast, removeToast }}>
      {children}
      <div className="toast-container" aria-live="polite">
        {toasts.map((t) => (
          <div key={t.id} className="toast" role="status">
            {t.message}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}
