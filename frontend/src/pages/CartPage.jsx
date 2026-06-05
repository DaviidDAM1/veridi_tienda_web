import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { buildBackendAssetUrl } from '../services/api';
import { openAuthPanel } from '../utils/auth';
import { formatMobileTallaLabel, useIsMobile } from '../utils/responsive';

function CartPage() {
  const isMobile = useIsMobile();
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
                    {item.talla_nombre && <p><strong>Talla actual:</strong> {item.talla_nombre}</p>}
                    {Array.isArray(item.available_tallas) && item.available_tallas.length > 0 && (
                      <div className="carrito-talla-selector">
                        <label htmlFor={`talla-${item.item_key}`}>Talla</label>
                        <select
                          id={`talla-${item.item_key}`}
                          value={Number(item.id_talla || 0)}
                          disabled={working}
                          onChange={(event) => {
                            const newSizeId = Number(event.target.value || 0);
                            if (!newSizeId || newSizeId === Number(item.id_talla || 0)) {
                              return;
                            }
                            doCartAction({
                              action: 'update_size',
                              id_producto: item.id_producto,
                              id_talla: item.id_talla,
                              new_id_talla: newSizeId
                            });
                          }}
                        >
                          {item.available_tallas.map((talla) => (
                            <option key={`${item.item_key}-${talla.id_talla}`} value={talla.id_talla}>
                              {formatMobileTallaLabel(talla.nombre, isMobile)} (Disponible)
                            </option>
                          ))}
                        </select>
                        {(() => {
                          const tallaActual = (item.available_tallas || []).find(
                            (talla) => Number(talla.id_talla) === Number(item.id_talla)
                          );
                          const stockActual = Number(tallaActual?.stock || 0);
                          return (
                            <p style={{ marginTop: 6, marginBottom: 0, fontSize: 13, color: 'var(--veridi-text-secondary)' }}>
                              {stockActual > 0 && stockActual <= 20 ? 'Queda poco stock. ¡Date prisa!' : 'Stock disponible'}
                            </p>
                          );
                        })()}
                      </div>
                    )}
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
              <div className="carrito-resumen-acciones">
                <Link to="/tienda" className="btn-ver" style={{ width: '100%' }}>
                  ← Volver a la tienda
                </Link>
                <Link
                  to="/checkout"
                  className="btn-checkout"
                >
                  💳 Ir a Pagar
                </Link>

                <button
                  type="button"
                  className="btn-clear-cart"
                  disabled={working}
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
