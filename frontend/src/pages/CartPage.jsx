import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { buildBackendAssetUrl } from '../services/api';
import { openAuthPanel } from '../utils/auth';

function CartPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [requiresLogin, setRequiresLogin] = useState(false);
  const [items, setItems] = useState([]);
  const [total, setTotal] = useState(0);
  const [working, setWorking] = useState(false);

  const hasItems = useMemo(() => items.length > 0, [items]);

  const loadCart = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/php/api_carrito.php');
      const data = response.data;

      if (data?.requiresLogin) {
        setRequiresLogin(true);
        setItems([]);
        setTotal(0);
        window.dispatchEvent(new CustomEvent('veridi:update-contador', { detail: { carrito: 0 } }));
      } else if (data?.ok) {
        setRequiresLogin(false);
        setItems(data.items || []);
        setTotal(Number(data.total || 0));
        const cantidadCarrito = (data.items || []).reduce((acc, item) => acc + Number(item.cantidad || 0), 0);
        window.dispatchEvent(new CustomEvent('veridi:update-contador', { detail: { carrito: cantidadCarrito } }));
      } else {
        setError(data?.message || 'No se pudo cargar el carrito.');
      }
    } catch (err) {
      setError('No se pudo cargar el carrito.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadCart();
  }, []);

  const doCartAction = async (payload) => {
    setWorking(true);
    setError('');
    try {
      const response = await api.post('/php/api_carrito.php', payload);
      const data = response.data;
      if (data?.ok) {
        setItems(data.items || []);
        setTotal(Number(data.total || 0));
        const cantidadCarrito = (data.items || []).reduce((acc, item) => acc + Number(item.cantidad || 0), 0);
        window.dispatchEvent(new CustomEvent('veridi:update-contador', { detail: { carrito: cantidadCarrito } }));
      } else {
        setError(data?.message || 'No se pudo actualizar el carrito.');
      }
    } catch (err) {
      setError('No se pudo actualizar el carrito.');
    } finally {
      setWorking(false);
    }
  };

  if (loading) {
    return (
      <main>
        <section className="carrito-section">
          <h2>Tu carrito</h2>
          <p>Cargando carrito...</p>
        </section>
      </main>
    );
  }

  if (requiresLogin) {
    return (
      <main>
        <section className="carrito-section">
          <h2>Tu carrito</h2>
          <div className="error-message">Debes iniciar sesión para ver el carrito.</div>
          <button type="button" className="btn-ver" onClick={() => openAuthPanel('login')}>Iniciar sesión</button>
        </section>
      </main>
    );
  }

  return (
    <main>
      <section className="carrito-section">
        <h2>Tu carrito</h2>

        {error && <div className="error-message">{error}</div>}

        {!hasItems ? (
          <>
            <p className="carrito-vacio">Tu carrito está vacío.</p>
            <Link to="/tienda" className="btn-ver">Volver a la tienda</Link>
          </>
        ) : (
          <>
            <div className="carrito-lista">
              {items.map((item) => (
                <article className="carrito-item" key={item.item_key}>
                  <div className="carrito-info">
                    <h3>{item.nombre}</h3>
                    {!!item.imagen && (
                      <img
                        src={buildBackendAssetUrl(item.imagen)}
                        alt={item.nombre}
                        className="carrito-img"
                        style={{ width: 80, height: 80, objectFit: 'cover', borderRadius: 4, marginBottom: 8 }}
                      />
                    )}
                    <p>{Number(item.precio).toFixed(2)} € unidad</p>
                    {item.talla_nombre && <p><strong>Talla:</strong> {item.talla_nombre}</p>}
                    <p><strong>Subtotal:</strong> {Number(item.subtotal).toFixed(2)} €</p>
                  </div>

                  <div className="carrito-acciones">
                    <button
                      type="button"
                      className="cantidad-btn"
                      disabled={working}
                      onClick={() => doCartAction({ action: 'update_quantity', id_producto: item.id_producto, id_talla: item.id_talla, delta: -1 })}
                    >
                      -
                    </button>

                    <span className="cantidad-numero">{item.cantidad}</span>

                    <button
                      type="button"
                      className="cantidad-btn"
                      disabled={working}
                      onClick={() => doCartAction({ action: 'update_quantity', id_producto: item.id_producto, id_talla: item.id_talla, delta: 1 })}
                    >
                      +
                    </button>

                    <button
                      type="button"
                      className="btn-eliminar"
                      disabled={working}
                      onClick={() => doCartAction({ action: 'remove_item', id_producto: item.id_producto, id_talla: item.id_talla })}
                    >
                      Eliminar
                    </button>
                  </div>
                </article>
              ))}
            </div>

            <div className="carrito-resumen">
              <p><strong>Total:</strong> {total.toFixed(2)} €</p>
              <div style={{ display: 'flex', gap: 10, marginTop: 20, flexDirection: 'column' }}>
                <Link
                  to="/checkout"
                  style={{
                    background: 'linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%)',
                    color: 'var(--veridi-black)',
                    padding: '14px 24px',
                    borderRadius: 6,
                    fontWeight: 700,
                    fontSize: 15,
                    textDecoration: 'none',
                    textTransform: 'uppercase',
                    letterSpacing: 1,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: 8,
                    transition: 'all 0.3s ease',
                    boxShadow: '0 6px 20px rgba(212, 175, 55, 0.3)'
                  }}
                >
                  💳 Ir a Pagar
                </Link>

                <button
                  type="button"
                  className="btn-eliminar"
                  disabled={working}
                  style={{ width: '100%', padding: '12px 24px', border: '2px solid var(--veridi-gold)', background: 'transparent', color: 'var(--veridi-gold)', fontWeight: 700, borderRadius: 6, cursor: 'pointer', transition: 'all 0.3s ease' }}
                  onClick={() => doCartAction({ action: 'clear_cart' })}
                >
                  Vaciar carrito
                </button>
              </div>
            </div>
          </>
        )}
      </section>
    </main>
  );
}

export default CartPage;
