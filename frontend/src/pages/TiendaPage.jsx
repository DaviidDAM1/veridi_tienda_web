import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { buildBackendAssetUrl } from '../services/api';
import { openAuthPanel } from '../utils/auth';

function TiendaPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [productos, setProductos] = useState([]);
  const [filtrosData, setFiltrosData] = useState({ categorias: [], tallas: [], colores: [], estilos: [] });
  const [contador, setContador] = useState({ carrito: 0, deseos: 0 });
  const [paginacion, setPaginacion] = useState({ paginaActual: 1, totalPaginas: 1, totalProductos: 0 });

  const [query, setQuery] = useState({
    buscar: '',
    categoria: '',
    ordenar: '',
    precio_min: '',
    precio_max: '',
    talla: [],
    color: [],
    estilo: [],
    pagina: 1
  });
  const [selectFiltro, setSelectFiltro] = useState('');
  const [modalFiltro, setModalFiltro] = useState('');
  const [draftPrecioMin, setDraftPrecioMin] = useState('');
  const [draftPrecioMax, setDraftPrecioMax] = useState('');
  const [draftArray, setDraftArray] = useState([]);
  const [favLoadingId, setFavLoadingId] = useState(null);
  const [favMessage, setFavMessage] = useState('');

  const filtrosActivos = useMemo(() => {
    const activos = [];
    if (query.precio_min || query.precio_max) {
      activos.push(`Precio: ${query.precio_min || '0'}€ - ${query.precio_max || '∞'}€`);
    }
    if (query.talla.length) activos.push(`Talla: ${query.talla.join(', ')}`);
    if (query.color.length) activos.push(`Color: ${query.color.join(', ')}`);
    if (query.estilo.length) activos.push(`Estilo: ${query.estilo.join(', ')}`);
    return activos;
  }, [query]);

  useEffect(() => {
    const fetchProductos = async () => {
      setLoading(true);
      setError('');
      try {
        const params = {
          buscar: query.buscar || undefined,
          categoria: query.categoria || undefined,
          ordenar: query.ordenar || undefined,
          precio_min: query.precio_min || undefined,
          precio_max: query.precio_max || undefined,
          pagina: query.pagina
        };

        if (query.talla.length) params.talla = query.talla;
        if (query.color.length) params.color = query.color;
        if (query.estilo.length) params.estilo = query.estilo;

        const response = await api.get('/php/api_tienda.php', { params });
        const data = response.data;

        if (!data?.ok) {
          throw new Error('Respuesta inválida del servidor');
        }

        setProductos(data.productos || []);
        setFiltrosData(data.filtros || { categorias: [], tallas: [], colores: [], estilos: [] });
        setPaginacion(data.paginacion || { paginaActual: 1, totalPaginas: 1, totalProductos: 0 });
        setContador(data.contador || { carrito: 0, deseos: 0 });
      } catch (err) {
        setError('No se pudieron cargar los productos.');
      } finally {
        setLoading(false);
      }
    };

    fetchProductos();
  }, [query]);

  const handleBasicChange = (field, value) => {
    setQuery((prev) => ({ ...prev, [field]: value, pagina: 1 }));
  };

  const toggleArrayFilter = (field, value) => {
    setQuery((prev) => {
      const exists = prev[field].includes(value);
      return {
        ...prev,
        [field]: exists ? prev[field].filter((item) => item !== value) : [...prev[field], value],
        pagina: 1
      };
    });
  };

  const clearFilters = () => {
    setQuery({
      buscar: '',
      categoria: '',
      ordenar: '',
      precio_min: '',
      precio_max: '',
      talla: [],
      color: [],
      estilo: [],
      pagina: 1
    });
  };

  const opcionesFiltro = {
    talla: filtrosData.tallas || [],
    color: filtrosData.colores || [],
    estilo: filtrosData.estilos || []
  };

  const openFiltroModal = (tipo) => {
    if (!tipo) return;
    setModalFiltro(tipo);
    if (tipo === 'precio') {
      setDraftPrecioMin(query.precio_min || '');
      setDraftPrecioMax(query.precio_max || '');
      setDraftArray([]);
      return;
    }
    setDraftArray([...(query[tipo] || [])]);
  };

  const closeFiltroModal = () => {
    setModalFiltro('');
    setSelectFiltro('');
  };

  const toggleDraftArray = (value) => {
    setDraftArray((prev) => (prev.includes(value) ? prev.filter((item) => item !== value) : [...prev, value]));
  };

  const applyFiltroModal = () => {
    if (modalFiltro === 'precio') {
      setQuery((prev) => ({
        ...prev,
        precio_min: draftPrecioMin,
        precio_max: draftPrecioMax,
        pagina: 1
      }));
      closeFiltroModal();
      return;
    }

    setQuery((prev) => ({
      ...prev,
      [modalFiltro]: [...draftArray],
      pagina: 1
    }));
    closeFiltroModal();
  };

  const handleToggleFavorito = async (producto) => {
    const idProducto = Number(producto.id_producto);
    if (!idProducto) return;
    if (favLoadingId === idProducto) return;

    setFavLoadingId(idProducto);
    setFavMessage('');

    try {
      const action = producto.es_favorito ? 'remove' : 'add';
      const response = await api.post('/php/api_deseos.php', {
        action,
        id_producto: idProducto,
        nombre: producto.nombre,
        precio: Number(producto.precio),
        imagen: producto.imagen
      });

      const data = response.data;
      if (!data?.ok) {
        setFavMessage(data?.message || 'No se pudo actualizar favoritos.');
        return;
      }

      setProductos((prev) => prev.map((item) => (
        Number(item.id_producto) === idProducto
          ? { ...item, es_favorito: Boolean(data.esFavorito) }
          : item
      )));
      setContador((prev) => ({
        ...prev,
        deseos: Number.isFinite(Number(data.total)) ? Number(data.total) : prev.deseos
      }));
      const totalDeseos = Number.isFinite(Number(data.total)) ? Number(data.total) : undefined;
      window.dispatchEvent(new CustomEvent('veridi:update-contador', {
        detail: {
          deseos: totalDeseos
        }
      }));
      setFavMessage(data.message || 'Favoritos actualizado.');
    } catch (err) {
      const requiresLogin = err?.response?.data?.requiresLogin;
      if (requiresLogin) {
        setFavMessage('Debes iniciar sesión para añadir favoritos.');
        openAuthPanel('login');
      } else {
        setFavMessage('No se pudo actualizar favoritos.');
      }
    } finally {
      setFavLoadingId(null);
    }
  };

  return (
    <>
      <div className="search-section">
        <h3>Encuentra el producto que estás buscando</h3>

        <div className="barra-busqueda">
          <form onSubmit={(event) => event.preventDefault()} id="form-busqueda">
            <input
              type="text"
              name="buscar"
              placeholder="Buscar producto..."
              value={query.buscar}
              onChange={(event) => handleBasicChange('buscar', event.target.value)}
            />

            <select
              name="categoria"
              id="select-categoria"
              value={query.categoria}
              onChange={(event) => handleBasicChange('categoria', event.target.value)}
            >
              <option value="">Todas las categorías</option>
              {filtrosData.categorias.map((cat) => (
                <option key={cat.id_categoria} value={cat.id_categoria}>{cat.nombre}</option>
              ))}
            </select>

            <select
              name="ordenar"
              id="select-ordenar"
              value={query.ordenar}
              onChange={(event) => handleBasicChange('ordenar', event.target.value)}
            >
              <option value="">Ordenar por</option>
              <option value="nombre_asc">📝 Nombre: A - Z</option>
              <option value="nombre_desc">📝 Nombre: Z - A</option>
              <option value="precio_asc">💰 Precio: Menor a Mayor</option>
              <option value="precio_desc">💰 Precio: Mayor a Menor</option>
            </select>

            <select
              id="select-filtro"
              value={selectFiltro}
              onChange={(event) => {
                const value = event.target.value;
                setSelectFiltro(value);
                openFiltroModal(value);
              }}
            >
              <option value="">+ Añadir Filtro</option>
              <option value="precio">Precio</option>
              <option value="talla">Talla</option>
              <option value="color">Color</option>
              <option value="estilo">Estilo</option>
            </select>

            <button type="submit" title="Buscar">🔍 Buscar</button>
          </form>

          <Link to="/carrito" className="btn-carrito">🛒 Carrito ({contador.carrito})</Link>
          <Link to="/lista-deseos" className="btn-deseos">💙 Productos Favoritos ({contador.deseos})</Link>
        </div>

        <div id="filtros-activos" className="filtros-activos">
          {filtrosActivos.length > 0 && (
            <div className="filtros-tags">
              <strong>Filtros aplicados:</strong>
              {filtrosActivos.map((filtro) => (
                <span key={filtro} className="filtro-tag">{filtro}</span>
              ))}
              <button type="button" className="btn-limpiar-filtros" onClick={clearFilters}>✖ Limpiar filtros</button>
            </div>
          )}
        </div>
      </div>

      <div className="modal-overlay" id="overlay" style={{ display: modalFiltro ? 'block' : 'none' }} onClick={closeFiltroModal}></div>
      <div className="modal-filtro" id="modal-filtro" style={{ display: modalFiltro ? 'block' : 'none' }}>
        {modalFiltro === 'precio' && (
          <>
            <div className="modal-header">
              <h3>Filtrar por Precio</h3>
              <button className="modal-close" onClick={closeFiltroModal}>✕</button>
            </div>
            <div className="modal-body">
              <div className="precio-inputs">
                <div className="input-group">
                  <label htmlFor="precio-min">Precio mínimo (€)</label>
                  <input id="precio-min" type="number" min="0" step="0.01" placeholder="0" value={draftPrecioMin} onChange={(e) => setDraftPrecioMin(e.target.value)} />
                </div>
                <div className="input-group">
                  <label htmlFor="precio-max">Precio máximo (€)</label>
                  <input id="precio-max" type="number" min="0" step="0.01" placeholder="9999" value={draftPrecioMax} onChange={(e) => setDraftPrecioMax(e.target.value)} />
                </div>
              </div>
            </div>
            <div className="modal-footer">
              <button className="btn-cancelar" onClick={closeFiltroModal}>Cancelar</button>
              <button className="btn-aceptar" onClick={applyFiltroModal}>Aceptar</button>
            </div>
          </>
        )}

        {['talla', 'color', 'estilo'].includes(modalFiltro) && (
          <>
            <div className="modal-header">
              <h3>Filtrar por {modalFiltro.charAt(0).toUpperCase() + modalFiltro.slice(1)}</h3>
              <button className="modal-close" onClick={closeFiltroModal}>✕</button>
            </div>
            <div className="modal-body">
              <div className={`${modalFiltro}-options`}>
                {(opcionesFiltro[modalFiltro] || []).map((item) => (
                  <label className={`checkbox-container ${modalFiltro === 'color' ? 'color-checkbox' : ''}`} key={item}>
                    <input type="checkbox" value={item} checked={draftArray.includes(item)} onChange={() => toggleDraftArray(item)} />
                    <span className={`color-label ${modalFiltro === 'color' ? 'color-filter-label' : ''}`}>
                      {modalFiltro === 'color' && <span className="color-swatch" style={{ backgroundColor: item }}></span>}
                      {item}
                    </span>
                  </label>
                ))}
              </div>
            </div>
            <div className="modal-footer">
              <button className="btn-cancelar" onClick={closeFiltroModal}>Cancelar</button>
              <button className="btn-aceptar" onClick={applyFiltroModal}>Aceptar</button>
            </div>
          </>
        )}
      </div>

      <main>
        {loading && <p>Cargando productos...</p>}
        {error && <p className="error-message">{error}</p>}
        {!loading && !error && favMessage && (
          <p className={favMessage.includes('No se pudo') ? 'error-message' : 'success-message'}>{favMessage}</p>
        )}

        {!loading && !error && (
          <>
            <div className="cards">
              {productos.length > 0 ? (
                productos.map((producto) => (
                  <div className="card" key={producto.id_producto}>
                    <img src={buildBackendAssetUrl(producto.imagen)} alt={producto.nombre} className="producto-img" />
                    <h3>{producto.nombre}</h3>
                    <p>{producto.descripcion}</p>
                    <p>Categoria: {producto.categoria}</p>
                    <p className="precio">{Number(producto.precio).toFixed(2)} €</p>
                    <div className="botones-card">
                      <Link className="btn-anadir" to={`/producto/${producto.id_producto}`}>Agregar al carrito</Link>
                      <button
                        type="button"
                        className={`btn-deseo-card ${producto.es_favorito ? 'es-favorito' : ''}`}
                        onClick={() => handleToggleFavorito(producto)}
                        disabled={favLoadingId === Number(producto.id_producto)}
                      >
                        {favLoadingId === Number(producto.id_producto)
                          ? 'Actualizando...'
                          : (producto.es_favorito ? '❤️ En favoritos' : '🤍 Añadir a favoritos')}
                      </button>
                    </div>
                  </div>
                ))
              ) : (
                <p>No hay productos disponibles.</p>
              )}
            </div>

            {paginacion.totalPaginas > 1 && (
              <div className="paginacion">
                {paginacion.paginaActual > 1 && (
                  <button type="button" onClick={() => setQuery((prev) => ({ ...prev, pagina: prev.pagina - 1 }))}>
                    &laquo; Anterior
                  </button>
                )}

                {Array.from({ length: paginacion.totalPaginas }, (_, index) => index + 1).map((pagina) => (
                  <button
                    key={pagina}
                    type="button"
                    onClick={() => setQuery((prev) => ({ ...prev, pagina }))}
                    style={pagina === paginacion.paginaActual ? { fontWeight: 'bold' } : undefined}
                  >
                    {pagina}
                  </button>
                ))}

                {paginacion.paginaActual < paginacion.totalPaginas && (
                  <button type="button" onClick={() => setQuery((prev) => ({ ...prev, pagina: prev.pagina + 1 }))}>
                    Siguiente &raquo;
                  </button>
                )}
              </div>
            )}
          </>
        )}
      </main>
    </>
  );
}

export default TiendaPage;
