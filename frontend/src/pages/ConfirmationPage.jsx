import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import api from '../services/api';
import { openAuthPanel } from '../utils/auth';

function ConfirmationPage() {
  const { id } = useParams();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [pedido, setPedido] = useState(null);
  const [detalles, setDetalles] = useState([]);
  const [yaValoro, setYaValoro] = useState(false);

  const [modalOpen, setModalOpen] = useState(false);
  const [estrellas, setEstrellas] = useState(0);
  const [comentario, setComentario] = useState('');
  const [sending, setSending] = useState(false);
  const [valoracionMsg, setValoracionMsg] = useState('');

  const numeroPedido = useMemo(() => {
    if (!pedido) return '';
    return String(pedido.id_pedido).padStart(6, '0');
  }, [pedido]);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      setError('');
      try {
        const response = await api.get('/php/api_confirmacion.php', { params: { id } });
        const data = response.data;

        if (data?.requiresLogin) {
          setError('Debes iniciar sesión para ver esta confirmación.');
          return;
        }

        if (!data?.ok) {
          setError(data?.message || 'No se pudo cargar la confirmación.');
          return;
        }

        setPedido(data.pedido);
        setDetalles(data.detalles || []);
        setYaValoro(Boolean(data.valoracion?.yaValoro));
      } catch (err) {
        setError('No se pudo cargar la confirmación de pedido.');
      } finally {
        setLoading(false);
      }
    };

    load();
  }, [id]);

  const handleEnviarValoracion = async (event) => {
    event.preventDefault();
    if (estrellas < 1 || estrellas > 5) {
      setValoracionMsg('Selecciona entre 1 y 5 estrellas.');
      return;
    }

    setSending(true);
    setValoracionMsg('');
    try {
      const response = await api.post('/php/api_valoracion.php', {
        id_pedido: Number(id),
        estrellas,
        comentario
      });
      const data = response.data;

      if (!data?.ok) {
        setValoracionMsg(data?.message || 'No se pudo guardar la valoración.');
        return;
      }

      setValoracionMsg('¡Gracias! Tu valoración se guardó correctamente.');
      setYaValoro(true);
      setModalOpen(false);
    } catch (err) {
      setValoracionMsg('No se pudo guardar la valoración.');
    } finally {
      setSending(false);
    }
  };

  if (loading) {
    return (
      <main>
        <div className="producto-detalle-container">
          <p>Cargando confirmación...</p>
        </div>
      </main>
    );
  }

  if (error || !pedido) {
    return (
      <main>
        <div className="producto-detalle-container">
          <div className="error-message">{error || 'Pedido no encontrado.'}</div>
          <Link to="/tienda" className="btn-ver">Volver a la tienda</Link>
          {error.includes('iniciar sesión') && (
            <button type="button" onClick={() => openAuthPanel('login')} className="btn-ver" style={{ marginLeft: 10 }}>
              Iniciar sesión
            </button>
          )}
        </div>
      </main>
    );
  }

  return (
    <>
      <main>
        <div className="producto-detalle-container">
          <div
            style={{
              background: 'linear-gradient(135deg, var(--veridi-dark) 0%, var(--veridi-surface) 100%)',
              border: '2px solid var(--veridi-gold)',
              borderRadius: 12,
              padding: 50,
              maxWidth: 700,
              margin: '40px auto',
              boxShadow: '0 8px 30px rgba(212, 175, 55, 0.2)'
            }}
          >
            <div style={{ textAlign: 'center', borderBottom: '2px solid var(--veridi-gold)', paddingBottom: 30, marginBottom: 30 }}>
              <h1 style={{ color: 'var(--veridi-gold)', margin: '0 0 10px 0', fontSize: 36, fontFamily: 'Georgia, serif' }}>✓ GRACIAS POR TU COMPRA</h1>
              <p style={{ color: 'var(--veridi-text-secondary)', margin: 0, fontSize: 16 }}>Tu pedido ha sido confirmado y procesado exitosamente</p>
            </div>

            <div style={{ background: 'rgba(212, 175, 55, 0.05)', padding: 20, borderRadius: 8, marginBottom: 30, border: '1px solid rgba(212, 175, 55, 0.15)' }}>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
                <div>
                  <p style={{ color: 'var(--veridi-text-muted)', margin: '0 0 5px 0', fontSize: 12, textTransform: 'uppercase', letterSpacing: 1 }}>Número de Pedido</p>
                  <p style={{ color: 'var(--veridi-gold)', fontWeight: 700, margin: 0, fontSize: 18 }}>#{numeroPedido}</p>
                </div>
                <div>
                  <p style={{ color: 'var(--veridi-text-muted)', margin: '0 0 5px 0', fontSize: 12, textTransform: 'uppercase', letterSpacing: 1 }}>Fecha</p>
                  <p style={{ color: 'var(--veridi-gold)', fontWeight: 700, margin: 0, fontSize: 18 }}>{new Date(pedido.fecha).toLocaleString('es-ES')}</p>
                </div>
              </div>
            </div>

            <div style={{ marginBottom: 30 }}>
              <h3 style={{ color: 'var(--veridi-gold)', fontSize: 14, textTransform: 'uppercase', letterSpacing: 1, marginBottom: 12 }}>Dirección de Envío</h3>
              <p style={{ color: 'var(--veridi-text)', background: 'rgba(212, 175, 55, 0.08)', padding: 15, borderRadius: 6, margin: 0, lineHeight: 1.6 }}>{pedido.direccion}</p>
            </div>

            <div style={{ marginBottom: 30, paddingBottom: 20, borderBottom: '1px solid rgba(212, 175, 55, 0.2)' }}>
              <h3 style={{ color: 'var(--veridi-gold)', fontSize: 14, textTransform: 'uppercase', letterSpacing: 1, marginBottom: 12 }}>Cliente</h3>
              <p style={{ color: 'var(--veridi-text)', margin: 0, fontWeight: 600 }}>{pedido.nombre || 'Cliente'}</p>
              <p style={{ color: 'var(--veridi-text-secondary)', margin: 0 }}>{pedido.email || ''}</p>
            </div>

            <div style={{ marginBottom: 30 }}>
              <h3 style={{ color: 'var(--veridi-gold)', fontSize: 14, textTransform: 'uppercase', letterSpacing: 1, marginBottom: 15 }}>Productos Pedidos</h3>
              {detalles.map((detalle) => (
                <div key={detalle.id_detalle} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', padding: '15px 0', borderBottom: '1px solid rgba(212, 175, 55, 0.15)' }}>
                  <div style={{ flex: 1 }}>
                    <p style={{ color: 'var(--veridi-text)', margin: '0 0 5px 0', fontWeight: 600 }}>{detalle.producto_nombre}</p>
                    <p style={{ color: 'var(--veridi-text-muted)', margin: 0, fontSize: 13 }}>
                      Talla: <strong>{detalle.talla_nombre}</strong> | Cantidad: <strong>{detalle.cantidad}</strong>
                    </p>
                  </div>
                  <div style={{ textAlign: 'right' }}>
                    <p style={{ color: 'var(--veridi-gold-light)', fontWeight: 700, margin: 0 }}>€{Number(detalle.precio_unitario).toFixed(2)}</p>
                    <p style={{ color: 'var(--veridi-text-muted)', fontSize: 12, margin: 0 }}>(x{detalle.cantidad} = €{(Number(detalle.precio_unitario) * Number(detalle.cantidad)).toFixed(2)})</p>
                  </div>
                </div>
              ))}
            </div>

            <div style={{ background: 'var(--veridi-gold)', color: 'var(--veridi-black)', padding: 25, borderRadius: 8, textAlign: 'center', marginBottom: 30 }}>
              <p style={{ margin: '0 0 8px 0', fontSize: 14, textTransform: 'uppercase', letterSpacing: 1 }}>Monto Total</p>
              <p style={{ margin: 0, fontSize: 42, fontWeight: 700 }}>€{Number(pedido.total).toFixed(2)}</p>
            </div>

            <div style={{ marginTop: 24, textAlign: 'center', borderTop: '1px dashed rgba(212, 175, 55, 0.25)', paddingTop: 24 }}>
              <p style={{ color: 'var(--veridi-gold)', fontWeight: 700, margin: '0 0 12px 0' }}>¿Qué te pareció tu experiencia?</p>
              <p style={{ color: 'var(--veridi-text-secondary)', margin: '0 0 18px 0' }}>¡Valóranos y ayuda a otros clientes!</p>
              <button
                type="button"
                disabled={yaValoro}
                onClick={() => setModalOpen(true)}
                style={{
                  background: 'linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%)',
                  color: 'var(--veridi-black)',
                  padding: '12px 28px',
                  border: 0,
                  borderRadius: 8,
                  fontWeight: 700,
                  cursor: yaValoro ? 'not-allowed' : 'pointer',
                  opacity: yaValoro ? 0.6 : 1
                }}
              >
                {yaValoro ? '✅ Ya valoraste este pedido' : '⭐ Valóranos'}
              </button>
            </div>

            {valoracionMsg && <div className={valoracionMsg.includes('Gracias') ? 'success-message' : 'error-message'} style={{ marginTop: 18 }}>{valoracionMsg}</div>}
          </div>

          <div style={{ textAlign: 'center', marginTop: 40 }}>
            <Link
              to="/tienda"
              style={{
                background: 'linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%)',
                color: 'var(--veridi-black)',
                padding: '14px 40px',
                borderRadius: 6,
                fontWeight: 700,
                fontSize: 16,
                textDecoration: 'none',
                display: 'inline-block',
                textTransform: 'uppercase',
                letterSpacing: 1,
                boxShadow: '0 6px 20px rgba(212, 175, 55, 0.3)'
              }}
            >
              ← Volver a la Tienda
            </Link>
          </div>
        </div>
      </main>

      {modalOpen && (
        <>
          <div onClick={() => setModalOpen(false)} style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.72)', zIndex: 9998 }}></div>
          <div style={{ position: 'fixed', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: 'min(92vw, 560px)', background: 'var(--veridi-surface)', border: '2px solid var(--veridi-gold)', borderRadius: 12, padding: 24, zIndex: 9999, boxShadow: '0 12px 40px rgba(0,0,0,0.5)' }}>
            <button type="button" onClick={() => setModalOpen(false)} style={{ position: 'absolute', top: 12, right: 14, background: 'transparent', border: 'none', color: 'var(--veridi-text)', fontSize: 24, cursor: 'pointer' }}>&times;</button>
            <h2 style={{ margin: '0 0 8px 0', color: 'var(--veridi-gold)' }}>Valora tu compra</h2>
            <p style={{ margin: '0 0 18px 0', color: 'var(--veridi-text-secondary)' }}>Selecciona de 1 a 5 estrellas y añade un comentario opcional.</p>

            <form onSubmit={handleEnviarValoracion} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
              <div>
                <label style={{ display: 'block', marginBottom: 8, color: 'var(--veridi-text)', fontWeight: 600 }}>Estrellas *</label>
                <div style={{ display: 'flex', gap: 8, fontSize: 30 }}>
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button
                      key={star}
                      type="button"
                      onClick={() => setEstrellas(star)}
                      style={{ background: 'transparent', border: 0, cursor: 'pointer', color: star <= estrellas ? 'var(--veridi-gold)' : '#6b6b6b', lineHeight: 1 }}
                    >
                      ★
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label style={{ display: 'block', marginBottom: 8, color: 'var(--veridi-text)', fontWeight: 600 }}>Mensaje (opcional)</label>
                <textarea
                  rows={4}
                  maxLength={500}
                  value={comentario}
                  onChange={(e) => setComentario(e.target.value)}
                  placeholder="Cuéntanos cómo fue tu experiencia"
                  style={{ width: '100%', padding: 12, border: '2px solid var(--veridi-gold)', borderRadius: 8, background: 'var(--veridi-dark)', color: 'var(--veridi-text)' }}
                />
              </div>

              <button type="submit" disabled={sending} style={{ background: 'linear-gradient(135deg, var(--veridi-gold-dark) 0%, var(--veridi-gold) 100%)', color: 'var(--veridi-black)', padding: '12px 22px', border: 0, borderRadius: 8, fontWeight: 700, cursor: 'pointer' }}>
                {sending ? 'Enviando...' : 'Enviar valoración'}
              </button>
            </form>
          </div>
        </>
      )}
    </>
  );
}

export default ConfirmationPage;
