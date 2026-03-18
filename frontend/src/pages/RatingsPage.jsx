import { useEffect, useMemo, useState } from 'react';
import api from '../services/api';

function pintarEstrellas(cantidad) {
  const value = Math.max(1, Math.min(5, Number(cantidad) || 1));
  return '★'.repeat(value) + '☆'.repeat(5 - value);
}

function RatingsPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filtroEstrellas, setFiltroEstrellas] = useState(0);
  const [resumen, setResumen] = useState({ total: 0, promedio: 0 });
  const [valoraciones, setValoraciones] = useState([]);

  const loadRatings = async (stars = 0) => {
    setLoading(true);
    setError('');
    try {
      const params = {};
      if (Number(stars) > 0) {
        params.estrellas = Number(stars);
      }

      const response = await api.get('/php/api_valoraciones.php', { params });
      const data = response.data;

      if (!data?.ok) {
        throw new Error(data?.message || 'No se pudieron cargar las valoraciones.');
      }

      setResumen(data.resumen || { total: 0, promedio: 0 });
      setValoraciones(data.valoraciones || []);
      setFiltroEstrellas(Number(data.filtro_estrellas || 0));
    } catch (err) {
      setError('No se pudieron cargar las valoraciones.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadRatings(0);
  }, []);

  const promedioTexto = useMemo(() => Number(resumen.promedio || 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }), [resumen.promedio]);

  const handleAplicarFiltro = (event) => {
    event.preventDefault();
    loadRatings(filtroEstrellas);
  };

  const handleLimpiar = (event) => {
    event.preventDefault();
    setFiltroEstrellas(0);
    loadRatings(0);
  };

  return (
    <main>
      <section className="producto-detalle-container" style={{ maxWidth: 980, marginTop: 32 }}>
        <h1 style={{ color: 'var(--veridi-gold)', marginBottom: 8 }}>Valoraciones de clientes</h1>
        <p style={{ color: 'var(--veridi-text-secondary)', marginBottom: 24 }}>Consulta la experiencia de compra y filtra por estrellas.</p>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16, marginBottom: 24 }}>
          <div style={{ background: 'var(--veridi-surface)', border: '1px solid rgba(212, 175, 55, 0.3)', borderRadius: 10, padding: 16 }}>
            <p style={{ margin: 0, color: 'var(--veridi-text-muted)' }}>Total valoraciones</p>
            <p style={{ margin: '6px 0 0 0', color: 'var(--veridi-gold)', fontSize: 28, fontWeight: 700 }}>{Number(resumen.total || 0)}</p>
          </div>
          <div style={{ background: 'var(--veridi-surface)', border: '1px solid rgba(212, 175, 55, 0.3)', borderRadius: 10, padding: 16 }}>
            <p style={{ margin: 0, color: 'var(--veridi-text-muted)' }}>Promedio</p>
            <p style={{ margin: '6px 0 0 0', color: 'var(--veridi-gold)', fontSize: 28, fontWeight: 700 }}>{promedioTexto}/5</p>
          </div>
        </div>

        <form onSubmit={handleAplicarFiltro} style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap', marginBottom: 26 }}>
          <label htmlFor="filtro-estrellas" style={{ color: 'var(--veridi-text)', fontWeight: 600 }}>Filtrar por estrellas:</label>
          <select
            id="filtro-estrellas"
            value={filtroEstrellas}
            onChange={(e) => setFiltroEstrellas(Number(e.target.value))}
            style={{ padding: '10px 12px', border: '2px solid var(--veridi-gold)', borderRadius: 8, background: 'var(--veridi-dark)', color: 'var(--veridi-text)' }}
          >
            <option value={0}>Todas</option>
            <option value={5}>5 estrellas</option>
            <option value={4}>4 estrellas</option>
            <option value={3}>3 estrellas</option>
            <option value={2}>2 estrellas</option>
            <option value={1}>1 estrella</option>
          </select>
          <button type="submit" style={{ background: 'var(--veridi-gold)', color: 'var(--veridi-black)', border: 0, borderRadius: 8, padding: '10px 16px', fontWeight: 700, cursor: 'pointer' }}>
            Aplicar
          </button>
          <a href="#" onClick={handleLimpiar} style={{ color: 'var(--veridi-gold)', textDecoration: 'none', fontWeight: 600 }}>
            Limpiar
          </a>
        </form>

        {loading ? (
          <div style={{ padding: 20, border: '1px dashed rgba(212, 175, 55, 0.4)', borderRadius: 10, color: 'var(--veridi-text-secondary)' }}>
            Cargando valoraciones...
          </div>
        ) : error ? (
          <div className="error-message">{error}</div>
        ) : valoraciones.length === 0 ? (
          <div style={{ padding: 20, border: '1px dashed rgba(212, 175, 55, 0.4)', borderRadius: 10, color: 'var(--veridi-text-secondary)' }}>
            No hay valoraciones para el filtro seleccionado.
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
            {valoraciones.map((valoracion) => {
              const comentario = String(valoracion.comentario || '').trim();
              return (
                <article key={valoracion.id_valoracion} style={{ background: 'var(--veridi-surface)', border: '1px solid rgba(212, 175, 55, 0.25)', borderRadius: 10, padding: 16 }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', gap: 10, flexWrap: 'wrap', marginBottom: 8 }}>
                    <strong style={{ color: 'var(--veridi-text)' }}>{valoracion.nombre}</strong>
                    <span style={{ color: 'var(--veridi-text-muted)', fontSize: 13 }}>{new Date(valoracion.fecha).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                  </div>
                  <div style={{ color: 'var(--veridi-gold)', fontSize: 22, letterSpacing: 1, marginBottom: 8 }}>{pintarEstrellas(valoracion.estrellas)}</div>
                  <p style={{ margin: 0, color: 'var(--veridi-text-secondary)', lineHeight: 1.5 }}>{comentario !== '' ? comentario : 'Sin comentario adicional.'}</p>
                </article>
              );
            })}
          </div>
        )}
      </section>
    </main>
  );
}

export default RatingsPage;
