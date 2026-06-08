import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { openAuthPanel } from '../utils/auth';

function CheckoutPage() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [error, setError] = useState('');
  const [requiresLogin, setRequiresLogin] = useState(false);
  const [checkout, setCheckout] = useState({ usuario: { nombre: '', email: '' }, items: [], total: 0, isEmpty: true });

  const [form, setForm] = useState({
    email: '',
    calle: '',
    ciudad: '',
    codigo_postal: '',
    pais: ''
  });
  const formatPrice = (value) => Number(value || 0).toFixed(2);

  const loadCheckout = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/php/api_checkout.php');
      const data = response.data;

      if (data?.requiresLogin) {
        setRequiresLogin(true);
        return;
      }

      if (!data?.ok) {
        throw new Error(data?.message || 'Error al cargar checkout');
      }

      const checkoutData = data.checkout || { usuario: { nombre: '', email: '' }, items: [], total: 0, isEmpty: true };
      setCheckout(checkoutData);
      setForm((prev) => ({ ...prev, email: checkoutData.usuario?.email || '' }));
    } catch (err) {
      setError('No se pudo cargar el checkout.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadCheckout();
  }, []);

  const isCodigoPostalValido = (value) => /^\d{5}$/.test(String(value || ''));

  const handleChange = (field, value) => {
    if (field === 'codigo_postal') {
      const soloNumeros = String(value).replace(/\D/g, '').slice(0, 5);
      setForm((prev) => ({ ...prev, [field]: soloNumeros }));
      return;
    }

    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const processPayment = async () => {
    setSubmitting(true);
    setError('');

    try {
      const response = await api.post('/php/api_checkout.php', form);
      const data = response.data;

      if (data?.ok && data?.id_pedido) {
        try {
          localStorage.removeItem('veridi:catalog-query');
          localStorage.removeItem('veridi:ai-stylist-state');
        } catch (error) {}
        navigate(`/confirmacion/${data.id_pedido}`);
        return;
      }

      setError(data?.message || 'No se pudo procesar el pago.');
    } catch (err) {
      setError('No se pudo procesar el pago.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleSubmit = (event) => {
    event.preventDefault();

    if (!isCodigoPostalValido(form.codigo_postal)) {
      setError('El código postal debe tener exactamente 5 números.');
      return;
    }

    setShowConfirmModal(true);
  };

  const handleConfirmPayment = () => {
    if (!isCodigoPostalValido(form.codigo_postal)) {
      setError('El código postal debe tener exactamente 5 números.');
      setShowConfirmModal(false);
      return;
    }

    setShowConfirmModal(false);
    processPayment();
  };

  if (loading) {
    return (
      <main>
        <div className="producto-detalle-container checkout-page">
          <h1 style={{ color: 'var(--veridi-gold)', marginBottom: 30 }}>Finalizar Compra</h1>
          <p>Cargando checkout...</p>
        </div>
      </main>
    );
  }

  if (requiresLogin) {
    return (
      <main>
        <div className="producto-detalle-container checkout-page">
          <h1 style={{ color: 'var(--veridi-gold)', marginBottom: 30 }}>Finalizar Compra</h1>
          <div className="error-message">Debes iniciar sesión para continuar.</div>
          <button type="button" className="btn-ver" onClick={() => openAuthPanel('login')}>Iniciar sesión</button>
        </div>
      </main>
    );
  }

  if (checkout.isEmpty) {
    return (
      <main>
        <div className="producto-detalle-container checkout-page">
          <h1 style={{ color: 'var(--veridi-gold)', marginBottom: 30 }}>Finalizar Compra</h1>
          <p className="carrito-vacio">Tu carrito está vacío.</p>
          <Link to="/tienda" className="btn-ver">Volver a la tienda</Link>
        </div>
      </main>
    );
  }

  return (
    <main>
      <div className="producto-detalle-container checkout-page">
        <h1 style={{ color: 'var(--veridi-gold)', marginBottom: 30 }}>Finalizar Compra</h1>

        <div className="checkout-grid">
          <div className="checkout-panel checkout-form-panel">
            <h2 style={{ color: 'var(--veridi-gold)', marginBottom: 20, fontSize: 24 }}>Tus Datos</h2>

            {error && (
              <div className="checkout-alert error-message">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="checkout-form">
              <div className="checkout-field">
                <label>Email:</label>
                <input
                  type="email"
                  value={form.email}
                  required
                  onChange={(e) => handleChange('email', e.target.value)}
                  className="checkout-input"
                />
              </div>

              <div className="checkout-field">
                <label>Calle y Número:</label>
                <input
                  type="text"
                  value={form.calle}
                  required
                  onChange={(e) => handleChange('calle', e.target.value)}
                  placeholder="Ej: Calle Principal 123"
                  className="checkout-input"
                />
              </div>

              <div className="checkout-field">
                <label>Ciudad:</label>
                <input
                  type="text"
                  value={form.ciudad}
                  required
                  onChange={(e) => handleChange('ciudad', e.target.value)}
                  placeholder="Ej: Madrid"
                  className="checkout-input"
                />
              </div>

              <div className="checkout-field">
                <label>Código Postal:</label>
                <input
                  type="text"
                  value={form.codigo_postal}
                  required
                  onChange={(e) => handleChange('codigo_postal', e.target.value)}
                  placeholder="Ej: 28001"
                  inputMode="numeric"
                  maxLength={5}
                  pattern="[0-9]{5}"
                  title="El código postal debe tener exactamente 5 números"
                  className="checkout-input"
                />
              </div>

              <div className="checkout-field">
                <label>País:</label>
                <input
                  type="text"
                  value={form.pais}
                  required
                  onChange={(e) => handleChange('pais', e.target.value)}
                  placeholder="Ej: España"
                  className="checkout-input"
                />
              </div>

              <button type="submit" disabled={submitting} className="checkout-submit-btn">
                {submitting ? 'Procesando...' : '💳 Procesar Pago'}
              </button>

              <Link to="/carrito" className="checkout-back-btn">
                Volver al Carrito
              </Link>
            </form>
          </div>

          <div className="checkout-panel checkout-summary-panel">
            <h2 style={{ color: 'var(--veridi-gold)', marginBottom: 20, fontSize: 24 }}>Resumen de Compra</h2>

            <div className="checkout-summary-card">
              {checkout.items.map((item) => (
                <div key={`${item.id_producto}_${item.id_talla}`} className="checkout-summary-item">
                  <div>
                    <p className="checkout-summary-name">{item.nombre}</p>
                    <p className="checkout-summary-meta">Talla: {item.talla} | Cantidad: {item.cantidad}</p>
                    {item.en_oferta && Number(item.precio_original) > Number(item.precio) ? (
                      <p className="checkout-summary-price">
                        <span className="checkout-summary-old">€{formatPrice(item.precio_original)}</span>
                        <span className="checkout-summary-new">€{formatPrice(item.precio)} unidad</span>
                      </p>
                    ) : (
                      <p className="checkout-summary-price">€{formatPrice(item.precio)} unidad</p>
                    )}
                  </div>
                  <p className="checkout-summary-subtotal">€{formatPrice(item.subtotal)}</p>
                </div>
              ))}

              <div className="checkout-summary-total">
                <p>Total:</p>
                <p>€{formatPrice(checkout.total)}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {showConfirmModal && (
        <div className="checkout-confirm-overlay">
          <div className="checkout-confirm-modal">
            <h3>Confirmar compra</h3>
            <p>
              ¿Seguro que quieres finalizar la compra?
            </p>

            <div className="checkout-confirm-actions">
              <button
                type="button"
                onClick={() => setShowConfirmModal(false)}
                disabled={submitting}
                className="checkout-confirm-cancel"
              >
                Cancelar
              </button>

              <button
                type="button"
                onClick={handleConfirmPayment}
                disabled={submitting}
                className="checkout-confirm-ok"
              >
                Aceptar
              </button>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}

export default CheckoutPage;
