import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { buildBackendAssetUrl } from '../services/api';

function HomePage() {
  const [loadingDestacados, setLoadingDestacados] = useState(true);
  const [errorDestacados, setErrorDestacados] = useState('');
  const [destacados, setDestacados] = useState({ mas_vendido: null, nuevo: null, oferta: null });

  useEffect(() => {
    const loadDestacados = async () => {
      setLoadingDestacados(true);
      setErrorDestacados('');
      try {
        const response = await api.get('/php/api_inicio.php');
        const data = response.data;

        if (!data?.ok) {
          throw new Error('No se pudieron cargar los destacados.');
        }

        setDestacados(data.destacados || { mas_vendido: null, nuevo: null, oferta: null });
      } catch (err) {
        setErrorDestacados('No se pudieron cargar los productos destacados.');
      } finally {
        setLoadingDestacados(false);
      }
    };

    loadDestacados();
  }, []);

  const cardsDestacadas = useMemo(() => {
    return [
      {
        key: 'mas_vendido',
        titulo: '🏆 Más vendido',
        subtitulo: 'El favorito de nuestros clientes',
        producto: destacados.mas_vendido
      },
      {
        key: 'nuevo',
        titulo: '🆕 Nuevo',
        subtitulo: 'Recién llegado al catálogo',
        producto: destacados.nuevo
      },
      {
        key: 'oferta',
        titulo: '🔥 En oferta',
        subtitulo: 'Mejor precio disponible',
        producto: destacados.oferta
      }
    ];
  }, [destacados]);

  return (
    <main>
      <div className="theme-selector">
        <span className="theme-label">Personaliza tu experiencia:</span>
        <div className="theme-buttons">
          <button className="theme-btn" title="Modo Claro" aria-label="Cambiar a tema claro">☀️</button>
          <button className="theme-btn" title="Modo Oscuro" aria-label="Cambiar a tema oscuro">🌙</button>
        </div>
      </div>

      <div className="hero-section">
        <h2>Bienvenido a Veridi 👕</h2>
        <p>Descubre nuestra colección exclusiva de ropa de calidad</p>
        <Link to="/tienda" className="btn-productos">Ver Tienda</Link>
      </div>

      <div className="cards">
        {loadingDestacados && <p>Cargando destacados...</p>}
        {!loadingDestacados && errorDestacados && <p className="error-message">{errorDestacados}</p>}

        {!loadingDestacados && !errorDestacados && cardsDestacadas.map((card) => {
          const producto = card.producto;
          if (!producto) {
            return (
              <div className="card" key={card.key}>
                <img src={buildBackendAssetUrl('img/camisetaNegraVeridi.png')} alt={card.titulo} className="producto-img" />
                <h3>{card.titulo}</h3>
                <p>{card.subtitulo}</p>
                <p>Sin producto disponible</p>
                <p>--</p>
                <Link to="/tienda" className="btn-ver">Ver tienda</Link>
              </div>
            );
          }

          return (
            <div className="card" key={card.key}>
              <img src={buildBackendAssetUrl(producto.imagen)} alt={producto.nombre} className="producto-img" />
              <h3>{card.titulo}</h3>
              <p>{card.subtitulo}</p>
              <p>{producto.nombre}</p>
              <p>{Number(producto.precio).toFixed(2)} €</p>
              <Link to={`/producto/${producto.id_producto}`} className="btn-ver">Ver producto</Link>
            </div>
          );
        })}
      </div>
    </main>
  );
}

export default HomePage;
