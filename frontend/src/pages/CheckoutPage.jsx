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
        <div className="producto-detalle-container">
          <h1 style={{ color: 'var(--veridi-gold)', marginBottom: 30 }}>Finalizar Compra</h1>
          <p>Cargando checkout...</p>
        </div>
      </main>
    );
  }

  if (requiresLogin) {
    return (
      <main>
        <div className="producto-detalle-container">
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
        <div className="producto-detalle-container">
          <h1 style={{ color: 'var(--veridi-gold)', marginBottom: 30 }}>Finalizar Compra</h1>
          <p className="carrito-vacio">Tu carrito está vacío.</p>
          <Link to="/tienda" className="btn-ver">Volver a la tienda</Link>
        </div>
      </main>
    );
  }

  return (
    <main>
      <div className="producto-detalle-container">
        <h1 style={{ color: 'var(--veridi-gold)', marginBottom: 30 }}>Finalizar Compra</h1>

        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 50, marginBottom: 50 }}>
          <div>
            <h2 style={{ color: 'var(--veridi-gold)', marginBottom: 20, fontSize: 24 }}>Tus Datos</h2>

            {error && (
              <div style={{ background: 'rgba(211, 47, 47, 0.1)', border: '1px solid #d32f2f', color: '#d32f2f', padding: 15, borderRadius: 6, marginBottom: 20 }}>
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
              <div>
                <label style={{ display: 'block', color: 'var(--veridi-gold)', marginBottom: 8, fontWeight: 600 }}>Email:</label>
                <input
                  type="email"
                  value={form.email}
                  required
                  onChange={(e) => handleChange('email', e.target.value)}
                  style={{ width: '100%', padding: 12, border: '2px solid var(--veridi-gold)', borderRadius: 6, background: 'var(--veridi-dark)', color: 'var(--veridi-text)', fontSize: 14 }}
                />
              </div>

              <div>
                <label style={{ display: 'block', color: 'var(--veridi-gold)', marginBottom: 8, fontWeight: 600 }}>Calle y Número:</label>
                <input
                  type="text"
                  value={form.calle}
                  required
                  onChange={(e) => handleChange('calle', e.target.value)}
                  placeholder="Ej: Calle Principal 123"
                  style={{ width: '100%', padding: 12, border: '2px solid var(--veridi-gold)', borderRadius: 6, background: 'var(--veridi-dark)', color: 'var(--veridi-text)', fontSize: 14 }}
                />
              </div>

              <div>
                <label style={{ display: 'block', color: 'var(--veridi-gold)', marginBottom: 8, fontWeight: 600 }}>Ciudad:</label>
                <input
                  type="text"
                  value={form.ciudad}
                  required
                  onChange={(e) => handleChange('ciudad', e.target.value)}
                  placeholder="Ej: Madrid"
                  style={{ width: '100%', padding: 12, border: '2px solid var(--veridi-gold)', borderRadius: 6, background: 'var(--veridi-dark)', color: 'var(--veridi-text)', fontSize: 14 }}
                />
              </div>

              <div>
                <label style={{ display: 'block', color: 'var(--veridi-gold)', marginBottom: 8, fontWeight: 600 }}>Código Postal:</label>
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
                  style={{ width: '100%', padding: 12, border: '2px solid var(--veridi-gold)', borderRadius: 6, background: 'var(--veridi-dark)', color: 'var(--veridi-text)', fontSize: 14 }}
                />
              </div>

              <div>
                <label style={{ display: 'block', color: 'var(--veridi-gold)', marginBottom: 8, fontWeight: 600 }}>País:</label>
                <input
                  type="text"
                  value={form.pais}
                  required
                  onChange={(e) => handleChange('pais', e.target.value)}
                  placeholder="Ej: España"
                  style={{ width: '100%', padding: 12, border: '2px solid var(--veridi-gold)', borderRadius: 6, background: 'var(--veridi-dark)', color: 'var(--veridi-text)', fontSize: 14 }}
                />
              </div>

              <button type="submit" disabled={submitting} style={{ background: 'linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%)', color: 'var(--veridi-black)', padding: '16px 30px', border: 'none', borderRadius: 6, fontWeight: 700, fontSize: 16, cursor: 'pointer', textTransform: 'uppercase', letterSpacing: 1, marginTop: 10 }}>
                {submitting ? 'Procesando...' : '💳 Procesar Pago'}
              </button>

              <Link to="/carrito" style={{ textAlign: 'center', color: 'var(--veridi-gold)', textDecoration: 'none', padding: 10, border: '2px solid var(--veridi-gold)', borderRadius: 6 }}>
                Volver al Carrito
              </Link>
            </form>
          </div>

          <div>
            <h2 style={{ color: 'var(--veridi-gold)', marginBottom: 20, fontSize: 24 }}>Resumen de Compra</h2>

            <div style={{ background: 'var(--veridi-dark)', border: '2px solid var(--veridi-gold)', borderRadius: 8, padding: 20 }}>
              {checkout.items.map((item) => (
                <div key={`${item.id_producto}_${item.id_talla}`} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '12px 0', borderBottom: '1px solid rgba(212, 175, 55, 0.2)' }}>
                  <div>
                    <p style={{ color: 'var(--veridi-text)', margin: '0 0 5px 0', fontWeight: 600 }}>{item.nombre}</p>
                    <p style={{ color: 'var(--veridi-text-muted)', margin: 0, fontSize: 14 }}>Talla: {item.talla} | Cantidad: {item.cantidad}</p>
                  </div>
                  <p style={{ color: 'var(--veridi-gold-light)', fontWeight: 700 }}>€{Number(item.subtotal).toFixed(2)}</p>
                </div>
              ))}

              <div style={{ padding: '20px 0', borderTop: '2px solid var(--veridi-gold)', marginTop: 10, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <p style={{ color: 'var(--veridi-gold)', fontWeight: 700, fontSize: 18, margin: 0 }}>Total:</p>
                <p style={{ color: 'var(--veridi-gold)', fontWeight: 700, fontSize: 24, margin: 0 }}>€{Number(checkout.total).toFixed(2)}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {showConfirmModal && (
        <div
          style={{
            position: 'fixed',
            inset: 0,
            background: 'rgba(0, 0, 0, 0.55)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 999
          }}
        >
          <div
            style={{
              width: 'min(92vw, 460px)',
              background: 'var(--veridi-dark)',
              border: '2px solid var(--veridi-gold)',
              borderRadius: 8,
              padding: 24,
              boxShadow: '0 18px 45px rgba(0, 0, 0, 0.4)'
            }}
          >
            <h3 style={{ color: 'var(--veridi-gold)', margin: '0 0 10px 0', fontSize: 22 }}>Confirmar compra</h3>
            <p style={{ color: 'var(--veridi-text)', margin: '0 0 20px 0', lineHeight: 1.45 }}>
              ¿Seguro que quieres finalizar la compra?
            </p>

            <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
              <button
                type="button"
                onClick={() => setShowConfirmModal(false)}
                disabled={submitting}
                style={{
                  background: 'transparent',
                  color: 'var(--veridi-gold)',
                  border: '2px solid var(--veridi-gold)',
                  borderRadius: 6,
                  padding: '10px 16px',
                  fontWeight: 700,
                  cursor: 'pointer'
                }}
              >
                Cancelar
              </button>

              <button
                type="button"
                onClick={handleConfirmPayment}
                disabled={submitting}
                style={{
                  background: 'linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%)',
                  color: 'var(--veridi-black)',
                  border: 'none',
                  borderRadius: 6,
                  padding: '10px 18px',
                  fontWeight: 700,
                  cursor: 'pointer'
                }}
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
