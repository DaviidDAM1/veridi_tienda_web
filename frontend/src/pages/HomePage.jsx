import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { buildBackendAssetUrl } from '../services/api';
import PlasmaWave from '../components/ui/PlasmaWave';

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

  const formatPrice = (value) => Number(value || 0).toFixed(2);

  return (
    <main>
      <div className="hero-section">
        <div className="hero-plasma-bg" aria-hidden="true">
          <PlasmaWave
            colors={['#8b5cf6', '#06b6d4']}
            speed1={0.05}
            speed2={0.04}
            bend1={1}
            bend2={0.5}
            focalLength={0.8}
          />
        </div>

        <div className="hero-content">
          <h2>Bienvenido a Veridi</h2>
          <p>Descubre nuestra colección exclusiva de ropa de calidad</p>
          <Link to="/tienda" className="btn-productos">Ver Tienda</Link>
        </div>
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
              {card.key === 'oferta' && producto.precio_original !== null && Number(producto.precio_original) > Number(producto.precio) ? (
                <p>
                  <span style={{ textDecoration: 'line-through', opacity: 0.75, marginRight: 8 }}>{formatPrice(producto.precio_original)} €</span>
                  <span style={{ fontWeight: 700, color: '#c92a2a' }}>{formatPrice(producto.precio)} €</span>
                </p>
              ) : (
                <p>{formatPrice(producto.precio)} €</p>
              )}
              <Link to={`/producto/${producto.id_producto}`} className="btn-ver">Ver producto</Link>
            </div>
          );
        })}
      </div>
    </main>
  );
}

export default HomePage;
