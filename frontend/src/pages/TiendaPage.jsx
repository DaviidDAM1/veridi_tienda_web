import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { buildBackendAssetUrl } from '../services/api';
import { useToast } from '../components/ui/ToastProvider';
import { openAuthPanel } from '../utils/auth';
import AiStylistChat from '../components/AiStylistChat';

const PRODUCTOS_POR_PAGINA = 16;

const COLOR_SWATCH_MAP = {
  rojo: '#ef4444',
  red: '#ef4444',
  granate: '#7f1d1d',
  burdeos: '#881337',
  rosa: '#ec4899',
  fucsia: '#d946ef',
  morado: '#8b5cf6',
  violeta: '#7c3aed',
  lila: '#a78bfa',
  azul: '#2563eb',
  celeste: '#38bdf8',
  turquesa: '#14b8a6',
  verde: '#22c55e',
  lima: '#84cc16',
  amarillo: '#facc15',
  naranja: '#f97316',
  marron: '#7c2d12',
  marrón: '#7c2d12',
  beige: '#d6c6a6',
  crema: '#f5e7c6',
  blanco: '#f8fafc',
  negro: '#111827',
  gris: '#6b7280',
  grisclaro: '#d1d5db',
  grisoscuro: '#374151',
  plateado: '#cbd5e1',
  dorado: '#f59e0b'
};

const toColorKey = (value) => String(value || '').toLowerCase().replace(/\s+/g, '');

const getColorSwatchStyle = (value) => {
  const raw = String(value || '').trim();
  const normalized = toColorKey(raw);

  if (COLOR_SWATCH_MAP[normalized]) {
    return { backgroundColor: COLOR_SWATCH_MAP[normalized] };
  }

  // Keep support for hex/rgb/hsl values coming directly from backend.
  if (/^(#|rgb\(|rgba\(|hsl\(|hsla\()/i.test(raw)) {
    return { backgroundColor: raw };
  }

  return { background: 'linear-gradient(135deg, #94a3b8, #64748b)' };
};

const formatColorLabel = (value) => {
  const text = String(value || '').trim();
  if (!text) return '';
  return text.charAt(0).toUpperCase() + text.slice(1);
};

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
  const { showToast } = useToast();

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

  const paginasVisibles = useMemo(() => {
    const total = paginacion.totalPaginas || 1;
    const actual = paginacion.paginaActual || 1;

    if (total <= 7) {
      return Array.from({ length: total }, (_, index) => index + 1);
    }

    const pages = [1];
    const start = Math.max(2, actual - 1);
    const end = Math.min(total - 1, actual + 1);

    if (start > 2) {
      pages.push('...prev');
    }

    for (let page = start; page <= end; page += 1) {
      pages.push(page);
    }

    if (end < total - 1) {
      pages.push('...next');
    }

    pages.push(total);
    return pages;
  }, [paginacion]);

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
          pagina: query.pagina,
          limite: PRODUCTOS_POR_PAGINA
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
      try { showToast(data.message || 'Favoritos actualizado.', 'success'); } catch (e) {}
    } catch (err) {
      const requiresLogin = err?.response?.data?.requiresLogin;
      if (requiresLogin) {
        setFavMessage('Debes iniciar sesión para añadir favoritos.');
        openAuthPanel('login');
        try { showToast('Debes iniciar sesión para añadir favoritos.', 'info'); } catch (e) {}
      } else {
        setFavMessage('No se pudo actualizar favoritos.');
        try { showToast('No se pudo actualizar favoritos.', 'error'); } catch (e) {}
      }
    } finally {
      setFavLoadingId(null);
    }
  };

  const handleGoToPage = (pagina) => {
    if (!Number.isInteger(pagina)) return;
    if (pagina < 1 || pagina > paginacion.totalPaginas) return;
    if (pagina === paginacion.paginaActual) return;

    setQuery((prev) => ({ ...prev, pagina }));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <>
      <div className="search-section">
        <div className="catalog-hero-card">
          <span className="catalog-eyebrow">Catálogo Veridi</span>
          <h2>Encuentra tu próximo look</h2>
          <p>Filtros rápidos, diseño más inmersivo y una navegación por páginas para explorar mejor cada colección.</p>
          {!loading && !error && (
            <div className="catalog-stats">
              <span>{paginacion.totalProductos} productos</span>
              <span>Página {paginacion.paginaActual} de {paginacion.totalPaginas}</span>
              <span>{PRODUCTOS_POR_PAGINA} por página</span>
            </div>
          )}
        </div>

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
                      {modalFiltro === 'color' && <span className="color-swatch" style={getColorSwatchStyle(item)}></span>}
                      {modalFiltro === 'color' ? formatColorLabel(item) : item}
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

      <AiStylistChat />

      <main className="catalog-main">
        {loading && <p>Cargando productos...</p>}
        {error && <p className="error-message">{error}</p>}
        {!loading && !error && favMessage && (
          <p className={favMessage.includes('No se pudo') ? 'error-message' : 'success-message'}>{favMessage}</p>
        )}

        {!loading && !error && (
          <>
            <div className="cards catalog-grid">
              {productos.length > 0 ? (
                productos.map((producto) => (
                  <div className="card catalog-card" key={producto.id_producto}>
                    <div className="catalog-card-top">
                      <span className="catalog-chip">{producto.categoria}</span>
                    </div>
                    <img src={buildBackendAssetUrl(producto.imagen)} alt={producto.nombre} className="producto-img" />
                    <h3>{producto.nombre}</h3>
                    <p>{producto.descripcion}</p>
                    <p className="precio">{Number(producto.precio).toFixed(2)} €</p>
                    <div className="botones-card">
                      <Link className="btn-anadir" to={`/producto/${producto.id_producto}`}>Ver producto</Link>
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
                  <button type="button" className="paginacion-nav" onClick={() => handleGoToPage(paginacion.paginaActual - 1)}>
                    &laquo; Anterior
                  </button>
                )}

                {paginasVisibles.map((item) => {
                  if (typeof item !== 'number') {
                    return <span key={item} className="paginacion-ellipsis">…</span>;
                  }

                  return (
                    <button
                      key={item}
                      type="button"
                      className={item === paginacion.paginaActual ? 'active' : ''}
                      onClick={() => handleGoToPage(item)}
                    >
                      {item}
                    </button>
                  );
                })}

                {paginacion.paginaActual < paginacion.totalPaginas && (
                  <button type="button" className="paginacion-nav" onClick={() => handleGoToPage(paginacion.paginaActual + 1)}>
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
