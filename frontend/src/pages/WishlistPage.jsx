import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { buildBackendAssetUrl } from '../services/api';
import { openAuthPanel } from '../utils/auth';

function WishlistPage() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [requiresLogin, setRequiresLogin] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [deseos, setDeseos] = useState([]);

  const loadDeseos = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/php/api_deseos.php');
      const data = response.data;

      if (data?.requiresLogin) {
        setRequiresLogin(true);
        setDeseos([]);
        return;
      }

      if (!data?.ok) {
        throw new Error(data?.message || 'No se pudieron cargar tus favoritos.');
      }

      setRequiresLogin(false);
      setDeseos(data.deseos || []);
      window.dispatchEvent(new CustomEvent('veridi:update-contador', {
        detail: {
          deseos: Number.isFinite(Number(data.total)) ? Number(data.total) : (data.deseos || []).length
        }
      }));
    } catch (err) {
      const status = err?.response?.status;
      if (status === 401 || err?.response?.data?.requiresLogin) {
        setRequiresLogin(true);
        setDeseos([]);
      } else {
        setError('No se pudieron cargar tus favoritos.');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDeseos();
  }, []);

  const handleRemove = async (idProducto) => {
    setError('');
    setMessage('');
    try {
      const response = await api.post('/php/api_deseos.php', {
        action: 'remove',
        id_producto: Number(idProducto)
      });
      const data = response.data;

      if (!data?.ok) {
        setError(data?.message || 'No se pudo eliminar de favoritos.');
        return;
      }

      setDeseos((prev) => prev.filter((item) => Number(item.id_producto) !== Number(idProducto)));
      window.dispatchEvent(new CustomEvent('veridi:update-contador', {
        detail: {
          deseos: Number.isFinite(Number(data.total)) ? Number(data.total) : undefined
        }
      }));
    } catch (err) {
      setError('No se pudo eliminar de favoritos.');
    }
  };

  const handleMoveToCart = async (idProducto) => {
    setError('');
    setMessage('');
    try {
      const response = await api.post('/php/api_deseos.php', {
        action: 'move_to_cart',
        id_producto: Number(idProducto)
      });
      const data = response.data;

      if (!data?.ok) {
        setError(data?.message || 'No se pudo continuar con este producto.');
        return;
      }

      setMessage('Producto movido al carrito. Selecciona la talla para añadirlo.');
      navigate(data.redirect || `/producto/${idProducto}`);
    } catch (err) {
      setError('No se pudo continuar con este producto.');
    }
  };

  if (loading) {
    return (
      <main>
        <section className="carrito-section">
          <h2>Tus productos favoritos</h2>
          <p>Cargando favoritos...</p>
        </section>
      </main>
    );
  }

  if (requiresLogin) {
    return (
      <main>
        <section className="carrito-section">
          <h2>Tus productos favoritos</h2>
          <div className="error-message">Debes iniciar sesión para ver tus favoritos.</div>
          <button type="button" className="btn-ver" onClick={() => openAuthPanel('login')}>Iniciar sesión</button>
        </section>
      </main>
    );
  }

  return (
    <main>
      <section className="carrito-section">
        <h2>Tus productos favoritos</h2>

        {message && <div className="success-message">{message}</div>}
        {error && <div className="error-message">{error}</div>}

        {deseos.length === 0 ? (
          <>
            <p className="carrito-vacio">No tienes productos guardados en tus favoritos.</p>
            <Link to="/tienda" className="btn-ver">Explorar tienda</Link>
          </>
        ) : (
          <div className="carrito-lista">
            {deseos.map((item) => (
              <article key={item.id_producto} className="carrito-item">
                <div className="carrito-info">
                  {item.imagen && (
                    <img
                      src={buildBackendAssetUrl(item.imagen)}
                      alt={item.nombre}
                      className="wishlist-img"
                    />
                  )}
                  <h3>{item.nombre}</h3>
                  <p>{Number(item.precio).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €</p>
                </div>

                <div className="carrito-acciones">
                  <button type="button" className="btn-pagar" onClick={() => handleMoveToCart(item.id_producto)}>
                    Mover al carrito
                  </button>

                  <button type="button" className="btn-eliminar" onClick={() => handleRemove(item.id_producto)}>
                    Eliminar
                  </button>
                </div>
              </article>
            ))}
          </div>
        )}
      </section>
    </main>
  );
}

export default WishlistPage;
