import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import api from '../services/api';
import { buildBackendAssetUrl } from '../services/api';
import { openAuthPanel } from '../utils/auth';
import './ProductDetailPage.css';

function ProductDetailPage() {
  const navigate = useNavigate();
  const { id } = useParams();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [cartMessage, setCartMessage] = useState('');
  const [cartLoading, setCartLoading] = useState(false);
  const [favMessage, setFavMessage] = useState('');
  const [favLoading, setFavLoading] = useState(false);
  const [producto, setProducto] = useState(null);
  const [tallas, setTallas] = useState([]);
  const [relacionados, setRelacionados] = useState([]);
  const [usuario, setUsuario] = useState({ logueado: false, esFavorito: false });
  const [selectedTalla, setSelectedTalla] = useState(null);

  useEffect(() => {
    const fetchDetalle = async () => {
      setLoading(true);
      setError('');
      try {
        const response = await api.get('/php/api_producto_detalle.php', { params: { id } });
        const data = response.data;

        if (!data?.ok) {
          throw new Error('No encontrado');
        }

        setProducto(data.producto);
        setTallas(data.tallas || []);
        setRelacionados(data.relacionados || []);
        setUsuario(data.usuario || { logueado: false, esFavorito: false });
      } catch (err) {
        setError('No se pudo cargar el detalle del producto.');
      } finally {
        setLoading(false);
      }
    };

    fetchDetalle();
  }, [id]);

  if (loading) {
    return <main className="producto-detalle-container"><p>Cargando producto...</p></main>;
  }

  if (error || !producto) {
    return (
      <main className="producto-detalle-container">
        <p className="error-message">{error || 'Producto no encontrado'}</p>
        <Link to="/tienda" className="btn-volver">← Volver a la tienda</Link>
      </main>
    );
  }

  const selectedTallaObj = tallas.find((t) => Number(t.id_talla) === Number(selectedTalla));

  const handleToggleFavorito = async () => {
    if (!usuario.logueado) {
      setFavMessage('Debes iniciar sesión para añadir favoritos.');
      openAuthPanel('login');
      return;
    }

    setFavLoading(true);
    setFavMessage('');
    try {
      const action = usuario.esFavorito ? 'remove' : 'add';
      const response = await api.post('/php/api_deseos.php', {
        action,
        id_producto: Number(producto.id_producto),
        nombre: producto.nombre,
        precio: Number(producto.precio),
        imagen: producto.imagen
      });
      const data = response.data;

      if (!data?.ok) {
        setFavMessage(data?.message || 'No se pudo actualizar favoritos.');
        return;
      }

      setUsuario((prev) => ({ ...prev, esFavorito: Boolean(data.esFavorito) }));
      window.dispatchEvent(new CustomEvent('veridi:update-contador', {
        detail: {
          deseos: Number.isFinite(Number(data.total)) ? Number(data.total) : undefined
        }
      }));
      setFavMessage(data.message || 'Favoritos actualizado.');
    } catch (err) {
      setFavMessage('No se pudo actualizar favoritos.');
    } finally {
      setFavLoading(false);
    }
  };

  const handleAddToCart = async () => {
    if (!usuario.logueado) {
      setCartMessage('Debes iniciar sesión para comprar.');
      openAuthPanel('login');
      return;
    }

    if (!selectedTalla) {
      setCartMessage('Selecciona una talla antes de añadir al carrito.');
      return;
    }

    setCartLoading(true);
    setCartMessage('');
    try {
      const response = await api.post('/php/api_carrito.php', {
        action: 'add_item',
        id_producto: Number(producto.id_producto),
        id_talla: Number(selectedTalla),
        nombre: producto.nombre,
        precio: Number(producto.precio),
        imagen: producto.imagen
      });

      const data = response.data;
      if (!data?.ok) {
        setCartMessage(data?.message || 'No se pudo añadir el producto al carrito.');
        return;
      }

      const cantidadCarrito = (data.items || []).reduce((acc, item) => acc + Number(item.cantidad || 0), 0);
      window.dispatchEvent(new CustomEvent('veridi:update-contador', {
        detail: {
          carrito: cantidadCarrito
        }
      }));

      setCartMessage('✓ Producto añadido al carrito.');
      navigate('/carrito');
    } catch (err) {
      setCartMessage('No se pudo añadir el producto al carrito.');
    } finally {
      setCartLoading(false);
    }
  };

  return (
    <main className="producto-detalle-container">
      <div className="breadcrumb">
        <Link to="/">Inicio</Link> &gt;
        <Link to="/tienda">Tienda</Link> &gt;
        <span>{producto.nombre}</span>
      </div>

      <div className="detalle-grid">
        <div className="detalle-imagen">
          <img src={buildBackendAssetUrl(producto.imagen)} alt={producto.nombre} className="imagen-grande" />
        </div>

        <div className="detalle-info">
          <h1>{producto.nombre}</h1>

          <div className="detalle-precio">
            <span className="precio-grande">{Number(producto.precio).toFixed(2)} €</span>
          </div>

          <div className="detalle-descripcion">
            <h3>Descripción</h3>
            <p>{producto.descripcion || 'Sin descripción'}</p>
          </div>

          <div className="detalle-caracteristicas">
            <h3>Características</h3>
            <ul>
              <li><strong>Categoría:</strong> {producto.categoria}</li>
              {producto.color && <li><strong>Color:</strong> {producto.color}</li>}
              {producto.material && <li><strong>Material:</strong> {producto.material}</li>}
              {producto.estilo && <li><strong>Estilo:</strong> {producto.estilo}</li>}
            </ul>
          </div>

          <div className="detalle-tallas">
            <h3>Tallas disponibles</h3>
            {tallas.length === 0 ? (
              <p className="sin-stock">Producto agotado</p>
            ) : (
              <>
                <div className="selector-tallas">
                  {tallas.map((talla) => (
                    <label className="talla-option" key={talla.id_talla}>
                      <input
                        type="radio"
                        name="id_talla"
                        value={talla.id_talla}
                        checked={Number(selectedTalla) === Number(talla.id_talla)}
                        onChange={() => setSelectedTalla(talla.id_talla)}
                      />
                      <span className="talla-label">{talla.nombre}</span>
                    </label>
                  ))}
                </div>
                <p className="stock-info">
                  {selectedTallaObj ? `Stock disponible: ${selectedTallaObj.stock}` : 'Selecciona una talla para ver stock'}
                </p>
              </>
            )}
          </div>

          <div className="detalle-acciones">
            <button type="button" className="btn-agregar-carrito" onClick={handleAddToCart} disabled={cartLoading || tallas.length === 0}>
              {usuario.logueado ? 'Agregar al carrito' : '🔒 Inicia sesión para comprar'}
            </button>
            <button type="button" className={`btn-favorito ${usuario.esFavorito ? 'es-favorito' : ''}`} onClick={handleToggleFavorito} disabled={favLoading}>
              {usuario.logueado ? (usuario.esFavorito ? '❤️ Eliminar de favoritos' : '🤍 Añadir a favoritos') : '🔒 Inicia sesión para añadir a favoritos'}
            </button>
          </div>

          {cartMessage && <div className={cartMessage.includes('✓') ? 'success-message' : 'error-message'} style={{ marginTop: 12 }}>{cartMessage}</div>}

          {favMessage && <div className={favMessage.includes('No se pudo') ? 'error-message' : 'success-message'} style={{ marginTop: 12 }}>{favMessage}</div>}

          <div className="detalle-volver">
            <Link to="/tienda" className="btn-volver">← Volver a la tienda</Link>
          </div>
        </div>
      </div>

      {relacionados.length > 0 && (
        <section className="productos-relacionados">
          <h2>Productos relacionados de {producto.categoria}</h2>
          <div className="cards-relacionados">
            {relacionados.map((prod) => (
              <div className="card-relacionado" key={prod.id_producto}>
                <img src={buildBackendAssetUrl(prod.imagen)} alt={prod.nombre} className="producto-img-rel" />
                <h4>{prod.nombre}</h4>
                <p className="precio-rel">{Number(prod.precio).toFixed(2)} €</p>
                <Link to={`/producto/${prod.id_producto}`} className="btn-ver-relacionado">Ver producto</Link>
              </div>
            ))}
          </div>
        </section>
      )}
    </main>
  );
}

export default ProductDetailPage;
